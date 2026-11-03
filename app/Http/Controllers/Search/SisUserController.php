<?php

namespace App\Http\Controllers\Search;

use App\Exceptions\SisNotConfiguredException;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
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

        $sis = $tenant->sis_provider;

        if (! $sis) {
            throw new SisNotConfiguredException(__('SIS is not configured. Please contact your systems administrator.'));
        }

        try {
            return $sis->getProvider($tenant)->searchForUser($data['search']);
        } catch (GuzzleException) {
            abort(422, $sis->connectionErrorMessage());
        }
    }
}
