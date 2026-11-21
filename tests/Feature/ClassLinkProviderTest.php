<?php

use App\Enums\Sis;
use App\Enums\UserType;
use App\Models\Course;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\SisProviders\ClassLinkProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const ROSTER = 'https://oneroster.test/ims/oneroster/rostering/v1p2';

function oneRosterUser(string $id, string $role, array $extra = []): array
{
    return [
        'sourcedId' => $id,
        'status' => 'active',
        'givenName' => "First{$id}",
        'familyName' => "Last{$id}",
        'email' => "{$id}@Example.test",
        'roles' => [['role' => $role]],
        ...$extra,
    ];
}

/** @param  array<string, mixed>  $routes  path (after the rostering base) => body */
function fakeOneRoster(array $routes): void
{
    Http::fake([
        'oneroster.test/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3600]),
        // Longest paths first so '/schools/x' doesn't swallow '/schools/x/teachers'
        ...collect($routes)->sortByDesc(fn ($body, $path) => strlen($path))->mapWithKeys(fn ($body, $path) => [ROSTER.$path.'*' => Http::response($body)])->all(),
    ]);
}

beforeEach(function () {
    Cache::flush();
    $this->tenant->update([
        'sis_provider' => Sis::CLASS_LINK,
        'sis_config' => [
            'url' => ROSTER.'/',
            'token_url' => 'https://oneroster.test/token',
            'client_id' => 'cid',
            'client_secret' => 'secret',
        ],
    ]);
    $this->tenant->schools()->delete();
    $this->provider = new ClassLinkProvider($this->tenant);
});

it('requests a client credentials token once and reuses it', function () {
    fakeOneRoster(['/schools' => ['orgs' => []]]);

    $this->provider->getAllSchools();
    $this->provider->getAllSchools();

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://oneroster.test/token'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('cid:secret'))
        && $request['grant_type'] === 'client_credentials'
        && str_contains($request['scope'], 'roster-core.readonly'));
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), ROSTER.'/schools')
        && $request->hasHeader('Authorization', 'Bearer tok-1'));
});

it('syncs schools with string sourced ids and skips deleted ones', function () {
    fakeOneRoster(['/schools' => ['orgs' => [
        ['sourcedId' => 'sch-a', 'status' => 'active', 'name' => 'Alpha', 'identifier' => '100'],
        ['sourcedId' => 'sch-b', 'status' => 'tobedeleted', 'name' => 'Beta', 'identifier' => '200'],
    ]]]);

    $this->provider->syncSchools();

    expect(School::pluck('name', 'sis_id')->all())->toBe(['sch-a' => 'Alpha'])
        ->and(School::first()->sis_key)->toBe("{$this->tenant->id}|sch-a");
});

it('pages through results until a short page', function () {
    $page = fn (int $from, int $count) => ['orgs' => array_map(
        fn (int $i) => ['sourcedId' => "s{$i}", 'name' => "School {$i}"],
        range($from, $from + $count - 1)
    )];
    Http::fake([
        'oneroster.test/token' => Http::response(['access_token' => 'tok-1']),
        ROSTER.'/schools?limit=100&offset=0' => Http::response($page(1, 100)),
        ROSTER.'/schools?limit=100&offset=100' => Http::response($page(101, 3)),
    ]);

    $this->provider->syncSchools();

    expect(School::count())->toBe(103);
});

it('syncs a full school: staff, students, guardians, courses, sections and enrollment', function () {
    $school = School::factory()->for($this->tenant)->create(['sis_id' => 'sch-a']);
    $teacher = oneRosterUser('t-1', 'teacher');
    $student = oneRosterUser('stu-1', 'student', [
        'identifier' => '12345',
        'grades' => ['09'],
        'agents' => [['sourcedId' => 'par-1'], ['sourcedId' => 'oth-1']],
    ]);

    fakeOneRoster([
        '/schools/sch-a' => ['org' => ['sourcedId' => 'sch-a', 'name' => 'Alpha', 'identifier' => '100']],
        '/schools/sch-a/teachers' => ['users' => [$teacher, oneRosterUser('t-2', 'teacher', ['status' => 'tobedeleted'])]],
        '/schools/sch-a/students' => ['users' => [$student]],
        '/users/par-1' => ['user' => oneRosterUser('par-1', 'parent')],
        '/users/oth-1' => ['user' => oneRosterUser('oth-1', 'aide')],
        '/schools/sch-a/courses' => ['courses' => [['sourcedId' => 'crs-1', 'status' => 'active', 'title' => 'Algebra', 'courseCode' => 'ALG1']]],
        '/schools/sch-a/classes' => ['classes' => [[
            'sourcedId' => 'cls-1', 'status' => 'active', 'classCode' => 'ALG1-1', 'periods' => ['1', '2'],
            'course' => ['sourcedId' => 'crs-1'],
        ]]],
        '/classes/cls-1/teachers' => ['users' => [$teacher]],
        '/classes/cls-1/students' => ['users' => [$student]],
    ]);

    $this->provider->fullSchoolSync($school);

    $staff = User::where('sis_id', 't-1')->sole();
    expect(User::where('sis_id', 't-2')->exists())->toBeFalse()
        ->and($staff->user_type)->toBe(UserType::staff)
        ->and($staff->email)->toBe('t-1@example.test')
        ->and($staff->schools->pluck('id')->all())->toBe([$school->id]);

    $student = Student::where('sis_id', 'stu-1')->sole();
    $guardian = User::where('sis_id', 'par-1')->sole();
    expect($student->student_number)->toBe('12345')
        ->and($student->grade_level)->toBe(9)
        ->and($guardian->user_type)->toBe(UserType::guardian)
        ->and(User::where('sis_id', 'oth-1')->exists())->toBeFalse()
        ->and($student->contacts->pluck('id')->all())->toBe([$guardian->id])
        ->and($student->contacts->first()->pivot->relationship)->toBe('parent');

    $section = Section::where('sis_id', 'cls-1')->sole();
    expect(Course::where('sis_id', 'crs-1')->sole()->name)->toBe('Algebra')
        ->and($section->user_id)->toBe($staff->id)
        ->and($section->expression)->toBe('1,2')
        ->and($section->students->pluck('id')->all())->toBe([$student->id]);
});

it('skips sections whose course is unknown', function () {
    $school = School::factory()->for($this->tenant)->create(['sis_id' => 'sch-a']);
    fakeOneRoster(['/schools/sch-a/classes' => ['classes' => [[
        'sourcedId' => 'cls-1', 'status' => 'active', 'course' => ['sourcedId' => 'missing'],
    ]]]]);

    $this->provider->syncSchoolSections($school);

    expect(Section::count())->toBe(0);
});

it('searches users with an escaped OneRoster filter', function () {
    fakeOneRoster(['/users' => ['users' => [oneRosterUser('t-1', 'teacher')]]]);

    $users = $this->provider->searchForUser("O'Brien");

    expect($users)->toHaveCount(1)
        ->and($users->first()->sis_id)->toBe('t-1')
        ->and($users->first()->user_type)->toBe(UserType::staff);
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), ROSTER.'/users')
        && $request['filter'] === "email~'O\\'Brien' OR familyName~'O\\'Brien'");
});

it('refreshes a single user from the roster', function () {
    $user = User::factory()->for($this->tenant)->create(['sis_id' => 't-1', 'user_type' => UserType::staff]);
    fakeOneRoster(['/teachers/t-1' => ['user' => oneRosterUser('t-1', 'teacher', ['givenName' => 'Renamed'])]]);

    $this->provider->syncUser($user);

    expect($user->fresh()->first_name)->toBe('Renamed');
});

it('reports whether it is configured', function () {
    expect($this->provider->configured())->toBeTrue();

    $this->tenant->update(['sis_config' => ['url' => ROSTER]]);

    expect((new ClassLinkProvider($this->tenant->fresh()))->configured())->toBeFalse();
});
