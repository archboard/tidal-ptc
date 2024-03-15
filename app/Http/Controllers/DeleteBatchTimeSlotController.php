<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class DeleteBatchTimeSlotController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
        ]);
        ray($data);

        $batch->timeSlots()
            ->where('starts_at', $data['starts_at'])
            ->where('ends_at', $data['ends_at'])
            ->whereNull('student_id')
            ->delete();

        return $this->toSuccess($request, __('Time slots deleted successfully.'));
    }
}
