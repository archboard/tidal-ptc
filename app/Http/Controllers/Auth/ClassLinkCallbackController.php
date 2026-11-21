<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Sis;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BrokerAssertion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ClassLinkCallbackController extends Controller
{
    public function __invoke(Request $request, BrokerAssertion $broker): RedirectResponse
    {
        $nonce = $request->session()->pull('classlink.nonce');
        $isConnect = $request->session()->pull('classlink.purpose') === 'connect';

        $failure = $isConnect
            ? redirect()->route('settings.tenant.edit')
            : redirect()->route('login');
        $failure->withErrors(['email' => __('ClassLink sign-in failed.')]);

        $assertion = $request->query('assertion');

        if (! is_string($assertion) || ! is_string($nonce)) {
            return $failure;
        }

        try {
            $claims = $broker->verify($assertion, $request->getSchemeAndHttpHost());
        } catch (RuntimeException) {
            return $failure;
        }

        $jti = $claims['jti'] ?? null;
        $idpTenantId = $claims['idp_tenant_id'] ?? null;
        $role = $claims['role'] ?? null;

        if (
            ! hash_equals($nonce, (string) ($claims['nonce'] ?? ''))
            || ! is_string($jti)
            || ! Cache::add('auth_broker.jti.'.$jti, true, 120)
            || ! is_string($idpTenantId)
            || $idpTenantId === ''
        ) {
            return $failure;
        }

        $tenant = $request->tenant();

        if ($isConnect) {
            if ($role !== 'staff' || ! $request->user()?->can('edit tenant settings')) {
                return $failure;
            }

            $tenant->update(['classlink_tenant_id' => $idpTenantId]);

            return redirect()->route('settings.tenant.edit')
                ->with('success', __('Settings updated successfully.'));
        }

        if (
            $tenant->classlink_tenant_id !== $idpTenantId
            || ! in_array($role, ['staff', 'guardian'], true)
            || ! is_string($claims['email'] ?? null)
        ) {
            return $failure;
        }

        $users = User::query()->where('user_type', $role);

        // The sub is only a SIS id when the roster itself comes from ClassLink; elsewhere it could collide with another system's ids
        $user = $tenant->sis_provider === Sis::CLASS_LINK && is_string($claims['sub'] ?? null)
            ? (clone $users)->where('sis_id', $claims['sub'])->first()
            : null;

        $user ??= $users
            ->whereRaw('lower(email) = ?', [mb_strtolower($claims['email'])])
            ->first();

        if (! $user) {
            return $failure;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
