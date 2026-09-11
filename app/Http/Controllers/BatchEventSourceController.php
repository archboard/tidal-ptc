<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(Request $request, Batch $batch): array
    {
        return $batch->getTimeSlotsFromFullCalendarRequest($request);
    }
}
