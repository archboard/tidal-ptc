<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use GrantHolle\PowerSchool\Auth\Traits\AuthenticatesUsingPowerSchoolWithOidc;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PowerSchoolOidcLoginController extends Controller
{
    use AuthenticatesUsingPowerSchoolWithOidc;

    protected function getPowerSchoolUrl(): string
    {
        return Tenant::current()->sis_config['url'] ?? '';
    }

    protected function getClientId(): string
    {
        return Tenant::current()->sis_config['client_id'] ?? '';
    }

    public function getClientSecret(): string
    {
        return Tenant::current()->sis_config['client_secret'] ?? '';
    }

    protected function getRedirectToRoute(string $userType): string
    {
        return '/';
    }

    protected function authenticated(Request $request, Authenticatable $user, Collection $data)
    {
        if (method_exists($user, 'syncFromSis')) {
            $user->syncFromSis();
        }
    }
}
