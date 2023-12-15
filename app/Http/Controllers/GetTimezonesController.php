<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetTimezonesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Support\Collection|string
     */
    public function __invoke(Request $request)
    {
        return timezones()
            ->map(fn (string $label, string $key) => [
                'value' => $key,
                'label' => $label,
            ])
            ->values();
    }
}
