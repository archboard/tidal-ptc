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
 * Local QA data: one tenant on APP_URL with an admin, a teacher with a section of three
 * students, a guardian of the first student, two translators, and a week of open time slots.
 * All users share the password "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create(['domain' => Uri::of(config('app.url'))->host()]);
        $tenant->makeCurrent();

        $school = School::factory()->for($tenant)->create([
            'name' => 'Tidal High School',
            'timezone' => config('app.timezone'),
            'open_for_contacts_at' => now()->subDay(),
            'close_for_contacts_at' => now()->addMonth(),
            'open_for_teachers_at' => now()->subDay(),
            'close_for_teachers_at' => now()->addMonth(),
            'allow_online_meetings' => true,
            'allow_translator_requests' => true,
        ]);
        $school->languages()->createMany([
            ['language' => Language::CHINESE_SIMPLIFIED, 'request_max' => 0, 'overlap_max' => 2],
            ['language' => Language::JAPANESE, 'request_max' => 10, 'overlap_max' => 1],
        ]);
        $scope = ['tenant_id' => $tenant->id, 'school_id' => $school->id];

        $makeUser = fn (array $attributes) => tap(User::factory()->create([
            ...$scope,
            'user_type' => UserType::staff,
            ...$attributes,
        ]), fn (User $user) => $user->schools()->attach($school));

        BouncerFacade::allow(Role::DISTRICT_ADMIN->value)->everything();
        $makeUser(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.com'])
            ->assignRole(Role::DISTRICT_ADMIN);

        $teacher = $makeUser(['first_name' => 'Tom', 'last_name' => 'Teacher', 'email' => 'teacher@example.com']);
        $section = Section::factory()
            ->for(Course::factory()->create([...$scope, 'name' => 'Algebra I']))
            ->create([...$scope, 'user_id' => $teacher->id]);
        $students = Student::factory(3)->create($scope);
        $section->students()->attach($students);

        $makeUser(['first_name' => 'Gwen', 'last_name' => 'Guardian', 'email' => 'guardian@example.com', 'user_type' => UserType::guardian])
            ->students()->attach($students->first(), ['relationship' => 'Mother']);

        Translator::factory()->create([...$scope, 'name' => 'Yuki Sato', 'languages' => [Language::JAPANESE]]);
        Translator::factory()->create([...$scope, 'name' => 'Wei Chen', 'languages' => [Language::CHINESE_SIMPLIFIED, Language::JAPANESE]]);

        foreach (range(1, 5) as $day) {
            $start = now()->addDays($day)->setTime(15, 0);
            foreach (range(0, 5) as $i) {
                TimeSlot::factory()->for($teacher)->create([
                    ...$scope,
                    'starts_at' => $start->addMinutes($i * 15),
                    'ends_at' => $start->addMinutes($i * 15 + 15),
                    'contact_can_book' => true,
                    'allow_translator_requests' => true,
                    'allow_online_meetings' => true,
                    'location' => 'Room 101',
                ]);
            }
        }
    }
}
