<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Enums\Role;
use App\Enums\UserType;
use App\Models\Course;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\TimeSlot;
use App\Models\Translator;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Uri;
use Silber\Bouncer\BouncerFacade;

/**
 * Demo mode data, rebuilt on a schedule when app.demo is on: one tenant with two schools,
 * courses, sections (a few with a teacher override), enrolled students, a district admin,
 * a guardian of three students, and a week of open time slots for every teacher.
 */
class DemoSeeder extends Seeder
{
    public const string ADMIN_EMAIL = 'admin@demo.test';

    public const string GUARDIAN_EMAIL = 'guardian@demo.test';

    /**
     * @var array<string, array{timezone: string, courses: list<string>, teachers: list<array{0: string, 1: string}>}>
     */
    protected const array SCHOOLS = [
        'Tidal High School' => [
            'timezone' => 'America/Chicago',
            'courses' => ['Algebra II', 'Geometry', 'Pre-Calculus', 'Biology', 'Chemistry', 'Physics', 'English 10', 'AP Literature', 'US History', 'World History', 'Spanish II', 'Art Studio'],
            'teachers' => [['Maria', 'Alvarez'], ['James', 'Okafor'], ['Linda', 'Park'], ['Robert', 'Nguyen'], ['Sarah', 'Thompson'], ['David', 'Cohen'], ['Emily', 'Brooks'], ['Michael', 'Rossi']],
        ],
        'Harbor Middle School' => [
            'timezone' => 'America/Chicago',
            'courses' => ['Math 6', 'Math 7', 'Pre-Algebra', 'Earth Science', 'Life Science', 'Physical Science', 'Language Arts 6', 'Language Arts 7', 'Social Studies', 'Band', 'Physical Education', 'Computer Science'],
            'teachers' => [['Karen', 'Liu'], ['Anthony', 'Baker'], ['Priya', 'Shah'], ['Thomas', 'Wright'], ['Grace', 'Kim'], ['Daniel', 'Murphy'], ['Olivia', 'Grant'], ['Samuel', 'Adeyemi']],
        ],
    ];

    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Tidal School District',
            'domain' => Uri::of(config('app.url'))->host(),
            'allow_password_auth' => true,
            'allow_oidc_login' => false,
            // ponytail: placeholder credentials only satisfy Tenant::installed(); nothing syncs in demo mode
            'sis_config' => [
                'url' => 'https://powerschool.demo.test',
                'client_id' => 'demo',
                'client_secret' => 'demo',
            ],
        ]);
        $tenant->makeCurrent();

        BouncerFacade::allow(Role::DISTRICT_ADMIN->value)->everything();

        $schools = collect(self::SCHOOLS)
            ->map(fn (array $config, string $name) => $this->seedSchool($tenant, $name, $config))
            ->values();

        $admin = $this->makeUser($schools->first(), [
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'email' => self::ADMIN_EMAIL,
        ]);
        $admin->schools()->attach($schools->skip(1)->pluck('id'));
        $admin->assignRole(Role::DISTRICT_ADMIN);

        $guardian = $this->makeUser($schools->first(), [
            'first_name' => 'Gwen',
            'last_name' => 'Guardian',
            'email' => self::GUARDIAN_EMAIL,
            'user_type' => UserType::guardian,
        ]);
        $children = Student::query()->where('school_id', $schools[0]->id)->take(2)->get()
            ->merge(Student::query()->where('school_id', $schools[1]->id)->take(1)->get());
        $guardian->students()->attach($children, ['relationship' => 'Mother']);
    }

    /**
     * @param  array{timezone: string, courses: list<string>, teachers: list<array{0: string, 1: string}>}  $config
     */
    protected function seedSchool(Tenant $tenant, string $name, array $config): School
    {
        $school = School::factory()->for($tenant)->create([
            'name' => $name,
            'timezone' => $config['timezone'],
            'open_for_contacts_at' => now()->subDay(),
            'close_for_contacts_at' => now()->addMonth(),
            'open_for_teachers_at' => now()->subDay(),
            'close_for_teachers_at' => now()->addMonth(),
            'allow_online_meetings' => true,
            'allow_translator_requests' => true,
        ]);
        $school->languages()->createMany([
            ['language' => Language::SPANISH, 'request_max' => 0, 'overlap_max' => 2],
            ['language' => Language::CHINESE_SIMPLIFIED, 'request_max' => 10, 'overlap_max' => 1],
        ]);
        $scope = ['tenant_id' => $tenant->id, 'school_id' => $school->id];

        Translator::factory()->create([...$scope, 'name' => 'Lucia Moreno', 'languages' => [Language::SPANISH]]);
        Translator::factory()->create([...$scope, 'name' => 'Wei Chen', 'languages' => [Language::CHINESE_SIMPLIFIED, Language::SPANISH]]);

        $teachers = collect($config['teachers'])->map(fn (array $teacher, int $i) => $this->makeUser($school, [
            'first_name' => $teacher[0],
            'last_name' => $teacher[1],
            'email' => strtolower("{$teacher[0]}.{$teacher[1]}@demo.test"),
            'room' => 'Room '.(101 + $i),
        ]));

        $sections = collect($config['courses'])->flatMap(function (string $course, int $i) use ($scope, $teachers) {
            $course = Course::factory()->create([...$scope, 'name' => $course, 'course_number' => 100 + $i]);

            return collect(range(1, $i % 3 === 0 ? 2 : 1))->map(fn (int $number) => Section::factory()->for($course)->create([
                ...$scope,
                'section_number' => $number,
                'expression' => $number.'(A)',
                'user_id' => $teachers[($i + $number) % $teachers->count()]->id,
            ]));
        });

        $sections->take(3)->each(fn (Section $section) => $section->update([
            'alt_user_id' => $teachers->firstWhere('id', '!=', $section->user_id)->id,
        ]));

        Student::factory(50)->create($scope)
            ->each(fn (Student $student) => $student->sections()->attach($sections->random(rand(4, 6))->pluck('id')));

        $teachers->each(fn (User $teacher) => $this->seedTimeSlots($teacher, $school));

        return $school;
    }

    /**
     * Open 15-minute slots from 15:00 to 17:00 school time over the next five weekdays.
     */
    protected function seedTimeSlots(User $teacher, School $school): void
    {
        foreach (range(1, 5) as $day) {
            $start = now($school->timezone)->addWeekdays($day)->setTime(15, 0)->utc();

            foreach (range(0, 7) as $i) {
                TimeSlot::factory()->for($teacher)->create([
                    'tenant_id' => $school->tenant_id,
                    'school_id' => $school->id,
                    'starts_at' => $start->addMinutes($i * 15),
                    'ends_at' => $start->addMinutes($i * 15 + 15),
                    'contact_can_book' => true,
                    'allow_translator_requests' => true,
                    'allow_online_meetings' => true,
                    'location' => $teacher->room,
                ]);
            }
        }
    }

    /** @param array<string, mixed> $attributes */
    protected function makeUser(School $school, array $attributes): User
    {
        return tap(User::factory()->create([
            'tenant_id' => $school->tenant_id,
            'school_id' => $school->id,
            'user_type' => UserType::staff,
            'timezone' => $school->timezone,
            ...$attributes,
        ]), fn (User $user) => $user->schools()->attach($school));
    }
}
