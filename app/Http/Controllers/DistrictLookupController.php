<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DistrictLookupController extends Controller
{
    public function create(): Response
    {
        $title = __('Find your district');

        return inertia('DistrictLookup', compact('title'))
            ->withViewData(compact('title'));
    }

    /**
     * Sends the visitor to their district's login page, which lives on another host.
     */
    public function store(Request $request): SymfonyResponse
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:4', 'alpha_num:ascii'],
        ]);

        $tenant = Tenant::query()->where('code', $validated['code'])->first();

        if (! $tenant) {
            throw ValidationException::withMessages([
                'code' => __('No district was found with that code.'),
            ]);
        }

        return Inertia::location("https://{$tenant->domain}/login");
    }
}
