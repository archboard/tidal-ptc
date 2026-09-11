<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateCurrentSchoolController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'school_id' => [
                'required',
                Rule::exists('schools', 'id')
                    ->whereIn('id', $user->adminSchools()->pluck('id')),
            ],
        ], [
            'school_id.exists' => __('You do not have access to that school.'),
        ]);
        $user->update($data);

        session()->flash('success', __('School changed to :school.', [
            'school' => School::findOrFail((int) $data['school_id'])->name,
        ]));

        return back();
    }
}
