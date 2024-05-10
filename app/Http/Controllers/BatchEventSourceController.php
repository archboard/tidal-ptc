<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchEventSourceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Batch $batch)
    {
        return $batch->getTimeSlotsFromFullCalendarRequest($request);
    }
}
