<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request, UserType $userType): RedirectResponse
    {
        abort_unless(config('app.demo'), 404);

        $email = match ($userType) {
            UserType::staff => DemoSeeder::ADMIN_EMAIL,
            UserType::guardian => DemoSeeder::GUARDIAN_EMAIL,
            default => abort(404),
        };

        Auth::login(User::query()->where('email', $email)->where('user_type', $userType)->firstOrFail());
        $request->session()->regenerate();

        return redirect('/');
    }
}
