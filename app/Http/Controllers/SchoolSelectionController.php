<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Exceptions\SisNotConfiguredException;
use App\Http\Resources\SchoolResource;
use App\Jobs\SyncSchoolItem;
use App\Jobs\SyncSchools;
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

        // Plugin registration installs the tenant without running InstallationController, so the first sync can land here
        if ($schools->isEmpty() && ! $isGuardian && $tenant->schools()->doesntExist()) {
            SyncSchools::dispatchSync($tenant);
            $request->session()->put('first_sync', true);
            $schools = $tenant->schools()->where('active', true)->get();
        }

        // Guardians are scoped to their students' schools; an empty list means no linked students, not a missing SIS config
        throw_if($schools->isEmpty() && ! $isGuardian, new SisNotConfiguredException('No schools configured'));

        if ($schools->count() === 1) {
            return $this->selectSchool($request, $user, $schools->sole()->id);
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
        session()->flash('success', __('School selected successfully'));

        return $this->selectSchool($request, $user, (int) $data['school_id']);
    }

    /**
     * Right after onboarding, sync the whole school and land on tenant settings to watch it run.
     */
    protected function selectSchool(Request $request, User $user, int $schoolId): RedirectResponse
    {
        $user->update(['school_id' => $schoolId]);
        $user->unsetRelation('school');
        $user->schools()->syncWithoutDetaching($schoolId);

        if (! $request->session()->pull('first_sync')) {
            return to_route('home');
        }

        SyncSchoolItem::start($user->school, 'school', $user);
        session()->flash('success', __('Sync started. You will be notified when it finishes.'));

        return to_route('settings.tenant.edit');
    }
}
