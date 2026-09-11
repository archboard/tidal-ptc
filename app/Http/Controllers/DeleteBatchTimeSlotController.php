<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Batch;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteBatchTimeSlotController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Batch $batch): JsonResponse|RedirectResponse
    {
        $this->authorize(Permission::delete, TimeSlot::class);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
        ]);

        $batch->timeSlots()
            ->where('starts_at', $data['starts_at'])
            ->where('ends_at', $data['ends_at'])
            ->whereNull('student_id')
            ->delete();

        return $this->toSuccess($request, __('Time slots deleted successfully.'));
    }
}
