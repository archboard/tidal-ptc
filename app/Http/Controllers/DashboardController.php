<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\UserType;
use App\Http\Resources\PublicUserResource;
use App\Http\Resources\StudentResource;
use App\Http\Resources\TimeSlotResource;
use App\Models\School;
use App\Models\Student;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $school = $request->school();

        $props = match ($user->user_type) {
            UserType::guardian => $this->guardianProps($user, $school),
            UserType::staff => $this->staffProps($user, $school),
            default => [],
        };

        return inertia('Dashboard', [
            'title' => __('Dashboard'),
            ...$props,
        ]);
    }

    /** @return array<string, mixed> */
    protected function guardianProps(User $user, School $school): array
    {
        $students = $user->students()
            ->where('school_id', $school->id)
            ->with(['sections' => fn ($query) => $query->with('course', 'teacher', 'altTeacher')])
            ->orderBy('first_name')
            ->get();

        $teacherIds = $students->flatMap(fn (Student $student) => $student->sections
            ->flatMap(fn ($section) => [$section->user_id, $section->alt_user_id]))
            ->filter()
            ->unique();

        $otherStaff = User::query()
            ->where('school_id', $school->id)
            ->whereKeyNot($teacherIds)
            ->whereCan(Permission::ownTimeSlots->value)
            ->whereHas('timeSlots', fn (Builder $query) => $query
                ->whereNull('student_id')
                ->where('contact_can_book', true)
                ->where('starts_at', '>', now()->addHours($school->booking_buffer_hours)))
            ->orderBy('last_name')
            ->get();

        // Per teacher: 'open' (bookable slot available), 'full' (slots exist but all taken), 'none' (no slots)
        $withSlots = TimeSlot::query()
            ->where('school_id', $school->id)
            ->where('contact_can_book', true)
            ->where('starts_at', '>', now()->addHours($school->booking_buffer_hours))
            ->whereIn('user_id', $teacherIds->merge($otherStaff->modelKeys()));
        $open = (clone $withSlots)->notReserved()->distinct()->pluck('user_id');
        $availability = $withSlots->distinct()->pluck('user_id')
            ->mapWithKeys(fn (int $id) => [$id => $open->contains($id) ? 'open' : 'full']);

        return [
            'students' => StudentResource::collection($students),
            'otherStaff' => PublicUserResource::collection($otherStaff),
            'slotAvailability' => $availability,
            'reservations' => TimeSlotResource::collection(
                $user->bookedTimeSlots()->notExpired()->with('user', 'student')->orderBy('starts_at')->get()
            ),
            'bookingOpen' => $school->contacts_can_book,
            'opensAt' => $school->local_open_for_contacts_at?->format('Y-m-d H:i'),
            'closesAt' => $school->local_close_for_contacts_at?->format('Y-m-d H:i'),
        ];
    }

    /** @return array<string, mixed> */
    protected function staffProps(User $user, School $school): array
    {
        $upcoming = $user->timeSlots()->notExpired();

        $props = [
            'myReservations' => TimeSlotResource::collection(
                (clone $upcoming)->reserved()->with('student', 'reservedBy')->orderBy('starts_at')->get()
            ),
            'openCount' => (clone $upcoming)->notReserved()->count(),
            'canManageTimeSlots' => $user->can('createOrForSelf', TimeSlot::class),
            'canEditSchoolSettings' => $user->can(Permission::editSchoolSettings->value),
        ];

        if ($user->can(Permission::viewAny, TimeSlot::class)) {
            $schoolSlots = TimeSlot::query()->where('school_id', $school->id)->notExpired();
            $languages = $school->languages()->get()->keyBy(fn ($language) => $language->language->value);
            $requested = (clone $schoolSlots)->reserved()->whereNotNull('language')
                ->selectRaw('language, count(*) as used')
                ->groupBy('language')
                ->pluck('used', 'language');

            $props['schoolStats'] = [
                'slots' => (clone $schoolSlots)->count(),
                'reserved' => (clone $schoolSlots)->reserved()->count(),
                'translators' => $languages->map(fn ($language, string $code) => [
                    'language' => $language->language->name(),
                    'used' => (int) ($requested[$code] ?? 0),
                    'max' => $language->request_max,
                ])->values(),
            ];
        }

        return $props;
    }
}
