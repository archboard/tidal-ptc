<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class BatchEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Batch $batch)
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $start = CarbonImmutable::parse($request->input('start'))
            ->setTimezone(config('app.timezone'));
        $end = CarbonImmutable::parse($request->input('end'))
            ->setTimezone(config('app.timezone'));

        return $batch->fullCalendarEvents($start, $end);
    }
}
