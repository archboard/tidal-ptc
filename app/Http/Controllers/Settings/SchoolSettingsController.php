<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolSettingsRequest;
use Illuminate\Http\Request;

class SchoolSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $school = $request->school();

        return inertia('settings/School', [
            'title' => __('Settings for :school', ['school' => $school->name]),
            'counts' => [
                [
                    'key' => 'staff',
                    'label' => __('Total staff'),
                    'value' => $school->users()
                        ->where('user_type', UserType::staff)
                        ->count(),
                ],
                [
                    'key' => 'students',
                    'label' => __('Total students'),
                    'value' => $school->students()
                        ->count(),
                ],
                [
                    'key' => 'courses',
                    'label' => __('Total courses'),
                    'value' => $school->courses()
                        ->count(),
                ],
                [
                    'key' => 'sections',
                    'label' => __('Total sections'),
                    'value' => $school->sections()
                        ->count(),
                ],
            ],
        ]);
    }

    public function update(UpdateSchoolSettingsRequest $request)
    {
        $school = $request->school();
        $validated = $request->validated();
        $dates = [
            'open_for_contacts_at',
            'close_for_contacts_at',
            'open_for_teachers_at',
            'close_for_teachers_at',
        ];
        $school->timezone = $validated['timezone'];

        foreach ($dates as $key) {
            $validated[$key] = $validated[$key]
                ? $school->dateToApp($validated[$key])
                : null;
        }

        $school->update($validated);

        session()->flash('success', __('Settings saved.'));

        return to_route('settings.school.edit');
    }
}
