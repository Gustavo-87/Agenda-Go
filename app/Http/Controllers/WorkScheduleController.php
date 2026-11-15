<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function index(Request $request, Professional $professional)
    {
        abort_unless($request->user()->can('manage', $professional), 403);

        return response()->json([
            'data' => $professional->workSchedules()
                ->orderBy('weekday')->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request, Professional $professional)
    {
        abort_unless($request->user()->can('manage', $professional), 403);

        $data = $request->validate([
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_break' => ['sometimes', 'boolean'],
        ]);

        $schedule = WorkSchedule::create([
            ...$data,
            'professional_id' => $professional->id,
        ]);

        return response()->json([
            'message' => 'Horario configurado',
            'schedule_id' => $schedule->id,
        ], 201);
    }

    public function destroy(Request $request, WorkSchedule $schedule)
    {
        abort_unless($request->user()->can('manage', $schedule->professional), 403);

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado']);
    }
}
