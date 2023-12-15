<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class InstallFirstUserController extends Controller
{
    public function index()
    {
        return inertia('InstallUser', [
            'endpoint' => route('install.user'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user.sis_id' => ['required'],
        ], ['user.sis_id.required' => 'Please select a user.']);

        $user = new User([
            ...$data['user'],
            'tenant_id' => $request->tenant()->id,
            'user_type' => UserType::staff,
        ]);
        $user->syncFromSis();
        BouncerFacade::allow(Role::DISTRICT_ADMIN->value)->everything();
        $user->assignRole(Role::DISTRICT_ADMIN);
        auth()->login($user);

        return to_route('select-school');
    }
}
