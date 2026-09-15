<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolSettingsRequest;
use App\Jobs\SyncSchoolItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $school = $request->school();
        $school->load('languages');

        return inertia('settings/School', [
            'title' => __('Settings for :school', ['school' => $school->name]),
            'syncing' => fn () => array_values(array_filter(
                array_keys(SyncSchoolItem::METHODS),
                fn (string $item) => Cache::has(SyncSchoolItem::syncingKey($school, $item)),
            )),
            'enrollmentSync' => function () use ($school): ?array {
                $batch = Bus::findBatch(Cache::get(SyncSchoolItem::enrollmentBatchKey($school), ''));

                return $batch && ! $batch->finished() ? [
                    'processed' => $batch->processedJobs(),
                    'total' => $batch->totalJobs,
                    'failed' => $batch->failedJobs,
                ] : null;
            },
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

    public function update(UpdateSchoolSettingsRequest $request): RedirectResponse
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

        return back();
    }
}
