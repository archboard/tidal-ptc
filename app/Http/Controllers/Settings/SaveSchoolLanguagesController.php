<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolLanguagesRequest;
use Illuminate\Http\Request;

class SaveSchoolLanguagesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateSchoolLanguagesRequest $request)
    {
        $school = $request->school();
        $languages = $request->collect('languages')
            ->mapWithKeys(fn (array $language) => [$language['id'] => [
                'request_max' => $language['request_max'],
                'overlap_max' => $language['overlap_max'],
            ]]);

        $school->languages()->sync($languages);

        session()->flash('success', __('Language settings updated successfully.'));

        return to_route('settings.school.edit');
    }
}
