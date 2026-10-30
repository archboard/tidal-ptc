<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Batch;
use App\Models\TimeSlot;
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
        $this->authorize(Permission::viewAny, TimeSlot::class);

        return $batch->getTimeSlotsFromFullCalendarRequest($request);
    }
}
