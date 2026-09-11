<?php

namespace App\Http\Controllers\Search;

use App\Exceptions\SisNotConfiguredException;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SisUserController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Collection<int, User>
     */
    public function __invoke(Request $request, Tenant $tenant): Collection
    {
        $data = $request->validate([
            'search' => ['required', 'string', 'min:3'],
        ]);

        $provider = $tenant->getSisProvider();

        if (! $provider) {
            throw new SisNotConfiguredException(__('SIS is not configured. Please contact your systems administrator.'));
        }

        return $provider->searchForUser($data['search']);
    }
}
