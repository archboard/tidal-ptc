<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SisUserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'search' => ['required', 'string', 'min:3'],
        ]);

        return $tenant->getSisProvider()
            ->searchForUser($data['search']);
    }
}
