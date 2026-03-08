<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolLanguagesRequest;
use App\Models\SchoolLanguage;

class SaveSchoolLanguagesController extends Controller
{
    public function __invoke(UpdateSchoolLanguagesRequest $request)
    {
        $school = $request->school();
        $now = now()->toDateTimeString();
        $languages = $request->collect('languages')
            ->map(fn (array $language) => [
                'school_id' => $school->id,
                'language' => $language['code'],
                'request_max' => $language['request_max'],
                'overlap_max' => $language['overlap_max'],
                'updated_at' => $now,
                'created_at' => $now,
            ])
            ->toArray();

        SchoolLanguage::upsert(
            $languages,
            ['school_id', 'language'],
            ['request_max', 'overlap_max', 'language']
        );

        session()->flash('success', __('Language settings updated successfully.'));

        return to_route('settings.school.edit');
    }
}
