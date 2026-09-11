<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetTimezonesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public function __invoke(Request $request): Collection
    {
        $timezones = timezones();

        if (is_string($timezones)) {
            abort(500);
        }

        return $timezones
            ->map(fn (string $label, string $key) => [
                'value' => $key,
                'label' => $label,
            ])
            ->values();
    }
}
