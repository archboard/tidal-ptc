<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolLanguagesRequest;

class SaveSchoolLanguagesController extends Controller
{
    public function __invoke(UpdateSchoolLanguagesRequest $request)
    {
        $school = $request->school();
        $languages = $request->collect('languages')
            ->map(fn (array $language) => [
                'language_code' => $language['code'],
                'request_max' => $language['request_max'],
                'overlap_max' => $language['overlap_max'],
            ]);

        $school->languages()->delete();
        $school->languages()->createMany($languages->values()->all());

        session()->flash('success', __('Language settings updated successfully.'));

        return to_route('settings.school.edit');
    }
}
