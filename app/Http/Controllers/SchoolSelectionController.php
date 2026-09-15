<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Exceptions\SisNotConfiguredException;
use App\Http\Resources\SchoolResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class SchoolSelectionController extends Controller
{
    public function index(Request $request, Tenant $tenant): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isGuardian = $user->user_type === UserType::guardian;
        $schools = $isGuardian
            ? $user->adminSchools()->get()
            : $tenant->schools()->where('active', true)->get();
        $title = __('Select school');

        // Guardians are scoped to their students' schools; an empty list means no linked students, not a missing SIS config
        throw_if($schools->isEmpty() && ! $isGuardian, new SisNotConfiguredException('No schools configured'));

        if ($schools->count() === 1) {
            $this->selectSchool($user, $schools->sole()->id);

            return to_route('home');
        }

        return inertia('SchoolSelection', [
            'schools' => SchoolResource::collection($schools),
            'title' => $title,
            'endpoint' => route('select-school'),
        ])->withViewData(compact('title'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => [
                'required',
                Rule::exists('schools', 'id')
                    ->where('tenant_id', $tenant->id),
            ],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->selectSchool($user, (int) $data['school_id']);

        session()->flash('success', __('School selected successfully'));

        return to_route('home');
    }

    protected function selectSchool(User $user, int $schoolId): void
    {
        $user->update(['school_id' => $schoolId]);
        $user->unsetRelation('school');
        $user->schools()->syncWithoutDetaching($schoolId);
    }
}
