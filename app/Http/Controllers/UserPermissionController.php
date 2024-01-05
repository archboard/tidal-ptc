<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\UserResource;
use App\Models\School;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UserPermissionController extends Controller
{
    public function index(Request $request, User $user)
    {
        $title = __('Permissions for :name', ['name' => $user->name]);
        $authUser = $request->user();

        return inertia('users/Permissions', [
            'title' => $title,
            'subject' => new UserResource($user),
            'permissions' => $user->getPermissionMatrix($authUser, $authUser->school),
        ])->withViewData(compact('title'));
    }

    public function update(Request $request, Tenant $tenant, User $user)
    {
        $validModels = array_reduce($user->getPermissionSubjectModels(), function (array $carry, string $model) {
            $carry[] = (new $model)->getMorphClass();

            return $carry;
        }, ['*']);

        $data = Validator::make($request->all(), [
            'permission' => ['required', new Enum(Permission::class)],
            'granted' => ['required', 'boolean'],
            'school' => ['nullable', Rule::in($tenant->schools()->active()->pluck('id'))],
            'model' => ['nullable', Rule::in($validModels)],
        ])->after(function (\Illuminate\Validation\Validator $validator) use ($request) {
            $permission = Permission::from($request->input('permission'));
            $authUser = $request->user();
            $noSchool = ! $request->input('school');

            if ($permission->shouldBeScoped() && $noSchool) {
                $validator->errors()->add('school', __('This permission requires a school to be selected.'));
            } elseif ($noSchool && $authUser->cant(Permission::editTenantSettings->value)) {
                $validator->errors()->add('permission', __('You are not allowed to grant this permission.'));
            }
        })->validateWithBag('default');

        $permission = Permission::from($data['permission']);
        $school = School::find($data['school']);

        $user->updateAppPermission($permission, $data['granted'], $school, $data['model']);

        return response()->json([
            'level' => 'success',
            'message' => __('Permission updated successfully.'),
        ]);
    }
}
