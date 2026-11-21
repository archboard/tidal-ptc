<?php

namespace App\SisProviders;

use App\Enums\UserType;
use App\Exceptions\LicenseException;
use App\Models\Course;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\SisProviders\Concerns\LimitsNewActiveSchools;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Roster sync over OneRoster 1.2 using OAuth2 client credentials.
 */
class ClassLinkProvider implements SisProvider
{
    use LimitsNewActiveSchools;

    protected const int PAGE_SIZE = 100;

    protected const string SCOPES = 'https://purl.imsglobal.org/spec/or/v1p2/scope/roster-core.readonly https://purl.imsglobal.org/spec/or/v1p2/scope/roster.readonly';

    /** @var array<int, string> */
    protected const array GUARDIAN_ROLES = ['parent', 'guardian', 'relativeOrGuardian'];

    public function __construct(protected Tenant $tenant) {}

    public function configured(): bool
    {
        return (bool) (
            $this->config('url') &&
            $this->config('token_url') &&
            $this->config('client_id') &&
            $this->config('client_secret')
        );
    }

    /** @return Collection<array-key, mixed> */
    public function getAllSchools(): Collection
    {
        return collect(iterator_to_array($this->pages('/schools', 'orgs'), false))->flatten(1);
    }

    public function syncSchools(): static
    {
        $now = now()->toDateTimeString();
        $data = $this->getAllSchools()
            ->reject(fn (array $school) => $this->isDeleted($school))
            ->map(fn (array $school) => [
                'tenant_id' => $this->tenant->id,
                'sis_id' => $school['sourcedId'],
                'name' => $school['name'],
                'school_number' => $this->intOrNull($school['identifier'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
                'sis_key' => $this->makeSisKey($school['sourcedId']),
            ])
            ->sortBy([['school_number', 'asc'], ['name', 'asc']])
            ->values();

        School::upsert(
            $this->limitNewActiveSchools($data->all()),
            ['sis_key'],
            ['name', 'school_number', 'updated_at']
        );

        return $this;
    }

    public function syncSchool(School $school): School
    {
        throw_if(
            config('app.cloud') && $this->tenant->schools()->where('sis_id', $school->sis_id)->doesntExist(),
            new LicenseException('Your license does not support this school. Please update your license and try again.')
        );

        $sisSchool = $this->request("/schools/{$school->sis_id}")->json('org');

        /** @var School $school */
        $school = $this->tenant->schools()->updateOrCreate(
            ['sis_id' => $sisSchool['sourcedId']],
            [
                'name' => $sisSchool['name'],
                'school_number' => $this->intOrNull($sisSchool['identifier'] ?? null),
                'sis_key' => $this->makeSisKey($sisSchool['sourcedId']),
            ]
        );

        return $school;
    }

    /**
     * ponytail: OneRoster 1.2 has no per-school administrator endpoint, so only teachers are
     * synced. District admins can be added from the install flow or by hand.
     */
    public function syncSchoolStaff(School $school): static
    {
        $now = now()->toDateTimeString();

        foreach ($this->pages("/schools/{$school->sis_id}/teachers", 'users') as $page) {
            $entries = collect($page)
                ->reject(fn (array $user) => $this->isDeleted($user))
                ->map(fn (array $user) => [
                    'tenant_id' => $this->tenant->id,
                    'school_id' => $school->id,
                    'sis_id' => $user['sourcedId'],
                    'sis_key' => UserType::staff->getSisKeyFromSisId($user['sourcedId']),
                    'user_type' => UserType::staff->value,
                    ...$this->userAttributes($user),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            User::upsert(
                $entries->all(),
                ['sis_key'],
                ['email', 'first_name', 'last_name', 'updated_at']
            );

            // Staff can belong to several schools; school_user records that beyond their current school
            $users = $this->tenant->users()
                ->whereIn('sis_key', $entries->pluck('sis_key'))
                ->pluck('id');

            DB::table('school_user')->insertOrIgnore(
                $users->map(fn (int $id) => ['school_id' => $school->id, 'user_id' => $id])->all()
            );
        }

        return $this;
    }

    public function syncSchoolStudents(School $school): static
    {
        $now = now()->toDateTimeString();
        $guardians = [];

        foreach ($this->pages("/schools/{$school->sis_id}/students", 'users') as $page) {
            $page = collect($page)->reject(fn (array $user) => $this->isDeleted($user));

            Student::upsert(
                $page->map(fn (array $student) => [
                    'tenant_id' => $this->tenant->id,
                    'school_id' => $school->id,
                    'sis_key' => $this->makeSisKey($student['sourcedId']),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                    ...$this->studentAttributes($student),
                ])->all(),
                ['sis_key'],
                ['student_number', 'school_id', 'first_name', 'last_name', 'email', 'grade_level', 'updated_at', 'deleted_at']
            );

            $studentIds = $this->tenant->students()
                ->whereIn('sis_id', $page->pluck('sourcedId'))
                ->pluck('id', 'sis_id');

            foreach ($page as $student) {
                $this->syncGuardians($studentIds->get($student['sourcedId']), $student, $guardians);
            }
        }

        return $this;
    }

    public function syncSchoolCourses(School $school): static
    {
        $now = now()->toDateTimeString();

        foreach ($this->pages("/schools/{$school->sis_id}/courses", 'courses') as $page) {
            Course::upsert(
                collect($page)
                    ->reject(fn (array $course) => $this->isDeleted($course))
                    ->map(fn (array $course) => [
                        'tenant_id' => $this->tenant->id,
                        'school_id' => $school->id,
                        'name' => $course['title'],
                        'course_number' => $course['courseCode'] ?? null,
                        'sis_id' => $course['sourcedId'],
                        'created_at' => $now,
                        'updated_at' => $now,
                        'sis_key' => $this->makeSisKey($course['sourcedId']),
                    ])->all(),
                ['sis_key'],
                ['name', 'course_number', 'updated_at']
            );
        }

        return $this;
    }

    /**
     * ponytail: one request per class to find its teacher, because OneRoster classes don't
     * embed teachers. Fine for a school; batch or cache if districts report slow syncs.
     */
    public function syncSchoolSections(School $school): static
    {
        $now = now()->toDateTimeString();

        foreach ($this->pages("/schools/{$school->sis_id}/classes", 'classes') as $page) {
            $classes = collect($page)->reject(fn (array $class) => $this->isDeleted($class));
            $courses = $this->tenant->courses()
                ->whereIn('sis_id', $classes->pluck('course.sourcedId')->filter())
                ->pluck('id', 'sis_id');

            $entries = [];

            foreach ($classes as $class) {
                $course = $courses->get(Arr::get($class, 'course.sourcedId'));
                $teacher = $course ? $this->classTeacherId($class['sourcedId']) : null;

                // A section needs both its course and its teacher
                if (! $course || ! $teacher) {
                    continue;
                }

                $entries[] = [
                    'tenant_id' => $this->tenant->id,
                    'school_id' => $school->id,
                    'course_id' => $course,
                    'user_id' => $teacher,
                    'sis_id' => $class['sourcedId'],
                    'section_number' => $class['classCode'] ?? null,
                    'expression' => ($class['periods'] ?? []) ? implode(',', $class['periods']) : null,
                    'external_expression' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'sis_key' => $this->makeSisKey($class['sourcedId']),
                ];
            }

            Section::upsert(
                $entries,
                ['sis_key'],
                ['course_id', 'user_id', 'section_number', 'expression', 'updated_at']
            );
        }

        return $this;
    }

    /**
     * ponytail: one request per section. Fine for a school; there is no bulk enrollment feed in core rostering.
     */
    public function syncSchoolStudentEnrollment(School $school): static
    {
        $school->sections()->get()->each(fn (Section $section) => $this->syncEnrollment($section));

        return $this;
    }

    /**
     * Syncs everything for a school:
     * staff, students, courses, sections, and enrollment
     */
    public function fullSchoolSync(School $school): void
    {
        $this->syncSchool($school);
        $this->syncSchoolStaff($school);
        $this->syncSchoolStudents($school);
        $this->syncSchoolCourses($school);
        $this->syncSchoolSections($school);
        $this->syncSchoolStudentEnrollment($school);
    }

    public function syncStudent(Student $student): Student
    {
        $record = $this->request("/students/{$student->sis_id}")->json('user');

        return $this->tenant->students()->updateOrCreate(
            ['sis_id' => $student->sis_id],
            [...$this->studentAttributes($record), 'sis_key' => $this->makeSisKey($student->sis_id)],
        );
    }

    public function syncUser(User $user): User
    {
        if ($user->sis_id === null) {
            return $user;
        }

        $path = match ($user->user_type) {
            UserType::staff => "/teachers/{$user->sis_id}",
            UserType::guardian => "/users/{$user->sis_id}",
            default => null,
        };

        if ($path === null) {
            return $user;
        }

        $user->forceFill([
            ...$this->userAttributes($this->request($path)->json('user')),
            'sis_key' => $user->user_type->getSisKeyFromSisId($user->sis_id),
        ])->save();

        return $user;
    }

    /** @return Collection<int, User> */
    public function searchForUser(string $search): Collection
    {
        $term = str_replace("'", "\\'", $search);
        $response = $this->request('/users', [
            'filter' => "email~'{$term}' OR familyName~'{$term}'",
            'limit' => 10,
        ]);

        return collect($response->json('users', []))
            ->reject(fn (array $user) => $this->isDeleted($user))
            ->slice(0, 10)
            ->map(fn (array $user) => new User([
                'sis_id' => $user['sourcedId'],
                'name' => trim(($user['givenName'] ?? '').' '.($user['familyName'] ?? '')) ?: null,
                ...$this->userAttributes($user),
                'sis_key' => UserType::staff->getSisKeyFromSisId($user['sourcedId']),
                'user_type' => UserType::staff,
            ]))
            ->values();
    }

    public function syncSection(Section $section): Section
    {
        $class = $this->request("/classes/{$section->sis_id}")->json('class');

        $section->update([
            'section_number' => $class['classCode'] ?? null,
            'expression' => ($class['periods'] ?? []) ? implode(',', $class['periods']) : null,
        ]);

        $this->syncEnrollment($section);

        return $section;
    }

    public function syncCourse(Course $course): Course
    {
        $record = $this->request("/courses/{$course->sis_id}")->json('course');

        $course->update([
            'name' => $record['title'],
            'course_number' => $record['courseCode'] ?? null,
        ]);

        return $course;
    }

    protected function syncEnrollment(Section $section): void
    {
        $sisIds = [];

        foreach ($this->pages("/classes/{$section->sis_id}/students", 'users') as $page) {
            foreach ($page as $student) {
                if (! $this->isDeleted($student)) {
                    $sisIds[] = $student['sourcedId'];
                }
            }
        }

        $section->students()->sync(
            $this->tenant->students()->whereIn('sis_id', $sisIds)->pluck('id')
        );
    }

    protected function classTeacherId(string $classId): ?int
    {
        $teacher = collect($this->request("/classes/{$classId}/teachers")->json('users', []))
            ->first(fn (array $user) => ! $this->isDeleted($user));

        if (! $teacher) {
            return null;
        }

        return $this->tenant->users()
            ->where('sis_key', UserType::staff->getSisKeyFromSisId($teacher['sourcedId']))
            ->value('id');
    }

    /**
     * Upserts the guardians among a student's agents and links them to the student.
     *
     * ponytail: one request per distinct agent. Guardians are remembered for the rest of the
     * school sync, but a large school is still a lot of calls.
     *
     * @param  array<string, mixed>  $student
     * @param  array<string, string|null>  $guardians  relationship by agent sourcedId, null when the agent isn't a guardian
     */
    protected function syncGuardians(?int $studentId, array $student, array &$guardians): void
    {
        if (! $studentId) {
            return;
        }

        $now = now()->toDateTimeString();
        $sync = [];

        foreach (Arr::pluck($student['agents'] ?? [], 'sourcedId') as $agentId) {
            if (! array_key_exists($agentId, $guardians)) {
                $agent = $this->request("/users/{$agentId}")->json('user');
                $role = collect($agent['roles'] ?? [])->pluck('role')->intersect(self::GUARDIAN_ROLES)->first();
                $guardians[$agentId] = $role && ! $this->isDeleted($agent) ? $role : null;

                if ($guardians[$agentId]) {
                    User::upsert([[
                        'tenant_id' => $this->tenant->id,
                        'sis_id' => $agent['sourcedId'],
                        'sis_key' => UserType::guardian->getSisKeyFromSisId($agent['sourcedId']),
                        'user_type' => UserType::guardian->value,
                        ...$this->userAttributes($agent),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]], ['sis_key'], ['email', 'first_name', 'last_name', 'updated_at']);
                }
            }

            if ($guardians[$agentId]) {
                $sync[$agentId] = $guardians[$agentId];
            }
        }

        $ids = $this->tenant->users()
            ->whereIn('sis_key', array_map(fn ($id) => UserType::guardian->getSisKeyFromSisId($id), array_keys($sync)))
            ->pluck('id', 'sis_id');

        $student = $this->tenant->students()->find($studentId);
        $student->contacts()->sync(
            $ids->mapWithKeys(fn (int $id, string $sisId) => [$id => ['relationship' => $sync[$sisId]]])->all()
        );
    }

    /**
     * @param  array<string, mixed>  $user
     * @return array{first_name: ?string, last_name: ?string, email: ?string}
     */
    protected function userAttributes(array $user): array
    {
        return [
            'first_name' => $user['givenName'] ?? null,
            'last_name' => $user['familyName'] ?? null,
            'email' => strtolower($user['email'] ?? '') ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $student
     * @return array<string, mixed>
     */
    protected function studentAttributes(array $student): array
    {
        return [
            'sis_id' => $student['sourcedId'],
            'student_number' => $student['identifier'] ?? null,
            'grade_level' => $this->intOrNull(Arr::first($student['grades'] ?? [])),
            ...$this->userAttributes($student),
        ];
    }

    /** @param  array<string, mixed>  $record */
    protected function isDeleted(array $record): bool
    {
        return ($record['status'] ?? null) === 'tobedeleted';
    }

    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    protected function config(string $key): mixed
    {
        return Arr::get($this->tenant->sis_config, $key);
    }

    /**
     * Yields each page of records under `$key`, until a page comes back short.
     *
     * @param  array<string, mixed>  $query
     * @return Generator<int, array<int, array<string, mixed>>>
     */
    protected function pages(string $path, string $key, array $query = []): Generator
    {
        $offset = 0;

        do {
            $records = $this->request($path, [...$query, 'limit' => self::PAGE_SIZE, 'offset' => $offset])
                ->json($key, []);

            if ($records) {
                yield $records;
            }

            $offset += self::PAGE_SIZE;
        } while (count($records) >= self::PAGE_SIZE);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function request(string $path, array $query = []): Response
    {
        return $this->client()
            ->get(rtrim($this->config('url'), '/').$path, $query)
            ->throw();
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson();
    }

    protected function accessToken(): string
    {
        $key = 'classlink.token.'.$this->tenant->id.'.'.md5($this->config('client_id'));

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->config('client_id'), $this->config('client_secret'))
            ->post($this->config('token_url'), [
                'grant_type' => 'client_credentials',
                'scope' => self::SCOPES,
            ])
            ->throw();

        $token = $response->json('access_token');
        Cache::put($key, $token, max((int) $response->json('expires_in', 3060) - 60, 1));

        return $token;
    }

    protected function makeSisKey(string $sisId): string
    {
        return $this->tenant->id.'|'.$sisId;
    }
}
