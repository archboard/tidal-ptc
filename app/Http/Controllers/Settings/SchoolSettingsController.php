<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
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
}
