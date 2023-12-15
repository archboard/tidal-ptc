<?php

namespace App\SisProviders;

use App\Models\Course;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

interface SisProvider
{
    public function __construct(Tenant $tenant);

    public function configured(): bool;

    public function getAllSchools(): Collection;

    public function syncSchools(): static;

    public function syncSchool(School $school): School;

    public function fullSchoolSync(School $school): void;

    public function syncSchoolStaff(School $school): static;

    public function syncSchoolStudents(School $school): static;

    public function syncSchoolCourses(School $school): static;

    public function syncSchoolSections(School $school): static;

    public function syncStudent(Student $student): Student;

    public function syncUser(User $user): User;

    public function syncSection(Section $section): Section;

    public function syncCourse(Course $course): Course;
}
