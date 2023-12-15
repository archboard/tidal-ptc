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
use GrantHolle\PowerSchool\Api\RequestBuilder;
use GrantHolle\PowerSchool\Api\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PowerSchoolProvider implements SisProvider
{
    protected RequestBuilder $builder;

    public function __construct(protected Tenant $tenant)
    {
        if (Arr::has($this->tenant->sis_config, ['url', 'client_id', 'client_secret'])) {
            $this->builder = new RequestBuilder(
                Arr::get($tenant->sis_config, 'url'),
                Arr::get($tenant->sis_config, 'client_id'),
                Arr::get($tenant->sis_config, 'client_secret')
            );
        }
    }

    public function getAllSchools(): Collection
    {
        $response = $this->builder
            ->to('/ws/v1/district/school')
            ->get();

        // Only get the schools that exist already
        if (config('app.cloud')) {
            $schools = $this->tenant->schools()->pluck('sis_id');

            return $response->collect()
                ->filter(fn (array $school) => $schools->contains($school['id']));
        }

        return $response->collect();
    }

    public function syncSchools(): static
    {
        $now = now()->toDateTimeString();
        $data = $this->getAllSchools()
            ->map(fn ($school) => [
                'tenant_id' => $this->tenant->id,
                'sis_id' => $school['id'],
                'name' => $school['name'],
                'school_number' => $school['school_number'],
                'low_grade' => $school['low_grade'],
                'high_grade' => $school['high_grade'],
                'created_at' => $now,
                'updated_at' => $now,
                'sis_key' => $this->makeSisKey($school),
            ]);

        School::upsert(
            $data->toArray(),
            ['sis_key'],
            ['name', 'school_number', 'low_grade', 'high_grade', 'updated_at']
        );

        return $this;
    }

    public function getSchool(School $school): array
    {
        $results = $this->builder
            ->to("/ws/v1/school/{$school->sis_id}")
            ->get();

        throw_if(
            config('app.cloud') && $this->tenant->schools()->where('sis_id', $school->sis_id)->doesntExist(),
            new LicenseException('Your license does not support this school. Please update your license and try again.')
        );

        return $results->toArray();
    }

    public function syncSchool(School $school): School
    {
        $sisSchool = $this->getSchool($school);

        /** @var School $school */
        $school = $this->tenant
            ->schools()
            ->updateOrCreate(
                ['sis_id' => $sisSchool['id']],
                [
                    'name' => $sisSchool['name'],
                    'school_number' => $sisSchool['school_number'],
                    'low_grade' => $sisSchool['low_grade'],
                    'high_grade' => $sisSchool['high_grade'],
                    'sis_key' => $this->makeSisKey($sisSchool),
                ]
            );

        return $school;
    }

    public function syncSchoolStaff(School $school): static
    {
        $builder = $this->builder
            ->method('get')
            ->to("/ws/v1/school/{$school->sis_id}/staff")
            ->expansions('emails');
        $now = now()->toDateTimeString();
        $count = 0;

        while ($results = $builder->paginate()) {
            $filteredStaff = $results->collect()
                ->filter(fn (array $user) => isset($user['users_dcid']));

            $entries = $filteredStaff->map(fn (array $user) => [
                'tenant_id' => $this->tenant->id,
                'school_id' => $school->id,
                'email' => strtolower(Arr::get($user, 'emails.work_email', '')) ?: null,
                'first_name' => Arr::get($user, 'name.first_name'),
                'last_name' => Arr::get($user, 'name.last_name'),
                'sis_id' => $user['users_dcid'],
                'sis_key' => $this->makeSisKey(UserType::staff->value.'|'.$user['users_dcid']),
                'user_type' => UserType::staff->value,
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            User::upsert(
                $entries->toArray(),
                ['sis_key'],
                ['email', 'first_name', 'last_name', 'updated_at']
            );

            // After these users exist, we need to associate them with this school
            // outside the direct school relationship (which is just their current school)
            $users = $this->tenant->users()
                ->whereIn('sis_key', Arr::pluck($entries, 'sis_key'))
                ->pluck('id', 'sis_id');

            $pivotEntries = $filteredStaff->map(fn (array $entry) => [
                'school_id' => $school->id,
                'user_id' => $users->get($entry['users_dcid']),
                'staff_id' => $entry['id'],
            ]);

            DB::table('school_user')->upsert(
                $pivotEntries->toArray(),
                ['school_id', 'user_id'],
                ['staff_id']
            );

            if (++$count > 5) {
                rd($results, $builder);
            }
        }

        return $this;
    }

    public function syncSchoolStudents(School $school): static
    {
        $builder = $this->builder
            ->method('get')
            ->to("/ws/v1/school/{$school->sis_id}/student")
            ->q('school_enrollment.enroll_status==(A,P)')
            ->expansions('contact_info,school_enrollment');
        $now = now()->toDateTimeString();

        while ($results = $builder->paginate()) {
            $entries = $results->collect()
                ->map(fn (array $student) => [
                    'tenant_id' => $this->tenant->id,
                    'school_id' => $school->id,
                    'sis_id' => $student['id'],
                    'student_number' => $student['local_id'],
                    'first_name' => Arr::get($student, 'name.first_name'),
                    'last_name' => Arr::get($student, 'name.last_name'),
                    'email' => strtolower(Arr::get($student, 'contact_info.email', '')) ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'sis_key' => $this->makeSisKey($student),
                    'grade_level' => Arr::get($student, 'school_enrollment.grade_level'),
                    'deleted_at' => null,
                ]);

            Student::upsert(
                $entries->toArray(),
                ['sis_key'],
                ['student_number', 'school_id', 'first_name', 'last_name', 'email', 'grade_level', 'updated_at', 'deleted_at']
            );
        }

        // Process students that shouldn't be enrolled anymore
        //        $builder = $this->builder
        //            ->method('get')
        //            ->to("/ws/v1/school/{$school->sis_id}/student")
        //            ->q('school_enrollment.enroll_status==(T,G,H,I)');
        //
        //        while ($results = $builder->paginate()) {
        //            $entries = $results->collect()
        //                ->map(fn (array $student) => $this->makeSisKey($student));
        //
        //            ray(Student::query()
        //                ->whereIn('sis_key', $entries)
        //                ->delete());
        //        }

        return $this;
    }

    public function syncSchoolCourses(School $school): static
    {
        $builder = $this->builder
            ->method('get')
            ->to("/ws/v1/school/{$school->sis_id}/course");
        $now = now()->toDateTimeString();

        while ($results = $builder->paginate()) {
            $entries = $results->collect()
                ->map(fn (array $course) => [
                    'tenant_id' => $this->tenant->id,
                    'school_id' => $school->id,
                    'name' => $course['course_name'],
                    'course_number' => $course['course_number'],
                    'sis_id' => $course['id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'sis_key' => $this->makeSisKey($course),
                ]);

            Course::upsert(
                $entries->toArray(),
                ['sis_key'],
                ['name', 'course_number', 'updated_at']
            );
        }

        return $this;
    }

    public function syncSchoolSections(School $school): static
    {
        $builder = $this->builder
            ->method('get')
            ->to("/ws/v1/school/{$school->sis_id}/section");
        $now = now()->toDateTimeString();

        while ($results = $builder->paginate()) {
            // Get courses that exist already
            $courses = $this->tenant->courses()
                ->whereIn('sis_id', $results->collect()->pluck('course_id'))
                ->pluck('id', 'sis_id');
            $staff = $school->users()
                ->whereIn('school_user.staff_id', $results->collect()->pluck('staff_id'))
                ->pluck('id', 'school_user.staff_id');

            $entries = $results->collect()
                ->reduce(function (array $entries, array $section) use ($school, $staff, $courses, $now) {
                    $course = $courses->get($section['course_id']);
                    $teacher = $staff->get($section['staff_id']);

                    // If the course or staff doesn't exist, don't do anything
                    if (! $course || ! $teacher) {
                        return $entries;
                    }

                    // It's a new section
                    $entries[] = [
                        'tenant_id' => $this->tenant->id,
                        'school_id' => $school->id,
                        'course_id' => $course,
                        'user_id' => $teacher,
                        'sis_id' => $section['id'],
                        'section_number' => $section['section_number'],
                        'expression' => $section['expression'],
                        'external_expression' => $section['external_expression'],
                        'created_at' => $now,
                        'updated_at' => $now,
                        'sis_key' => $this->makeSisKey($section),
                    ];

                    return $entries;
                }, []);

            Section::upsert(
                $entries,
                ['sis_key'],
                ['section_number', 'expression', 'external_expression', 'updated_at']
            );
        }

        return $this;
    }

    /**
     * This is pretty inefficient...
     * It should probably be done with a PQ
     * instead of individual requests for each section
     * TODO: Improve performance
     */
    public function syncSchoolStudentEnrollment(School $school): static
    {
        $students = $this->tenant->students()
            ->pluck('id', 'sis_id');

        $school->sections
            ->each(function (Section $section) use ($students) {
                $results = $this->builder
                    ->to("/ws/v1/section/{$section->sis_id}/section_enrollment")
                    ->get();

                // When there is only one result, it gets returned as a single entry
                // rather than an array of one entry...
                $enrollments = Arr::isAssoc($results->toArray())
                    ? collect([$results->toArray()])
                    : $results->collect();

                // Get the sis id's of the students who haven't dropped
                $studentEnrollment = $enrollments
                    ->filter(fn (array $enrollment) => ! $enrollment['dropped'] &&
                        $students->has($enrollment['student_id']))
                    ->map(fn (array $enrollment) => $students->get($enrollment['student_id']));

                $section->students()->sync($studentEnrollment);
            });

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

    public function getBuilder(): RequestBuilder
    {
        return $this->builder;
    }

    public function syncStudent(Student $student): Student
    {
        $results = $this->builder
            ->to("/ws/v1/student/{$student->sis_id}")
            ->q('school_enrollment.enroll_status==A')
            ->expansions('contact_info')
            ->get();

        return $this->tenant
            ->students()
            ->updateOrCreate(
                ['sis_id' => $student->sis_id],
                [
                    'student_number' => $results['local_id'],
                    'first_name' => Arr::get($results, 'name.first_name'),
                    'last_name' => Arr::get($results, 'name.last_name'),
                    'email' => strtolower(Arr::get($results, 'contact_info.email')) ?: null,
                    'sis_key' => $this->makeSisKey($student),
                ],
            );
    }

    public function syncUser(User $user): User
    {
        [$tenantId, $type, $sisId] = explode('|', $user->sis_key);
        $method = 'sync'.ucfirst($type);

        if (method_exists($this, $method)) {
            $this->$method($user);
        }

        return $user;
    }

    protected function syncStaff(User $user): void
    {
        // Fetch from custom PQ
        /** @var Response $data */
        $data = $this->builder
            ->pq('com.archboard.starter_sample.user.get', [
                'dcid' => $user->sis_id,
            ]);

        if ($data->count() === 1) {
            $user->update([
                'first_name' => $data[0]['first_name'] ?? null,
                'last_name' => $data[0]['last_name'] ?? null,
                'email' => $data[0]['email'] ?? null,
            ]);
        }
    }

    protected function syncGuardian(User $user): void
    {
        // First get the contact id, then the contact
        /** @var Response $response */
        $response = $this->builder
            ->pq('com.archboard.starter_sample.guardian.contactid', [
                'guardianid' => $user->sis_id,
            ]);

        if ($response->count() !== 1) {
            return;
        }

        $contactId = $response[0]['contact_id'];
        $data = $this->builder
            ->get("/ws/contacts/contact/{$contactId}");

        $user->update([
            'first_name' => $data['firstName'] ?? $user->first_name,
            'last_name' => $data['lastName'] ?? $user->last_name,
            'email' => collect($data['emails'])
                ->firstWhere('primary', true)['address'] ?? $user->email,
        ]);

        // Sync the student relationships
        $students = collect($data['contactStudents'])
            ->filter(fn (array $student) => $student['deleted'] === false &&
                $student['canAccessData'] === true &&
                Arr::get($student, 'studentDetails.0.active') === true
            )
            ->keyBy('dcid');

        $studentIds = $this->tenant
            ->students()
            ->whereIn('sis_id', $students->keys())
            ->pluck('id', 'sis_id');
        $sync = $studentIds->mapWithKeys(fn ($id, $dcid) => [
            $id => [
                'relationship' => Arr::get($students->get($dcid), 'studentDetails.0.relationship'),
            ],
        ]);

        $user->students()
            ->sync($sync->toArray());
    }

    public function configured(): bool
    {
        return Arr::get($this->tenant->sis_config, 'url') &&
            Arr::get($this->tenant->sis_config, 'client_id') &&
            Arr::get($this->tenant->sis_config, 'client_secret');
    }

    public function syncSection(Section $section): Section
    {
        $results = $this->builder->get("/ws/v1/section/{$section->sis_id}");

        $section->update([
            'section_number' => $results['section_number'],
            'expression' => $results['expression'],
            'external_expression' => $results['external_expression'],
        ]);

        // Sync enrollment
        $results = $this->builder
            ->get("/ws/v1/section/{$section->sis_id}/section_enrollment");

        // When there is only one result, it gets returned as a single entry
        // rather than an array of one entry...
        $enrollments = Arr::isAssoc($results->toArray())
            ? collect([$results->toArray()])
            : $results->collect();
        $students = $this->tenant->students()
            ->whereIn(
                'sis_id',
                $enrollments
                    ->filter(fn (array $enrollment) => ! $enrollment['dropped'])
                    ->pluck('student_id')
            )
            ->pluck('id');

        $section->students()->sync($students);

        return $section;
    }

    public function syncCourse(Course $course): Course
    {
        $results = $this->builder->get("/ws/v1/course/{$course->sis_id}");

        $course->update([
            'name' => $results['course_name'],
            'course_number' => $results['course_number'],
        ]);

        return $course;
    }

    protected function makeSisKey($subject): string
    {
        if (is_array($subject)) {
            return $this->tenant->id.'|'.$subject['id'];
        }

        if ($subject instanceof Model) {
            return $this->tenant->id.'|'.$subject->sis_id;
        }

        return $this->tenant->id.'|'.$subject;
    }
}
