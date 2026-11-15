<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\ScheduleException;
use Illuminate\Http\Request;

class ScheduleExceptionController extends Controller
{
    public function index(Request $request, Professional $professional)
    {
        abort_unless($request->user()->can('manage', $professional), 403);

        return response()->json([
            'data' => $professional->scheduleExceptions()->orderBy('starts_on')->get(),
        ]);
    }

    public function store(Request $request, Professional $professional)
    {
        abort_unless($request->user()->can('manage', $professional), 403);

        $data = $request->validate([
            'kind' => ['required', 'in:vacation,block,extra'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'start_time' => ['required_if:kind,extra', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:kind,extra', 'nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        $exception = ScheduleException::create([
            ...$data,
            'professional_id' => $professional->id,
        ]);

        return response()->json([
            'message' => 'Excepción registrada',
            'exception_id' => $exception->id,
        ], 201);
    }

    public function destroy(Request $request, ScheduleException $exception)
    {
        abort_unless($request->user()->can('manage', $exception->professional), 403);

        $exception->delete();

        return response()->json(['message' => 'Excepción eliminada']);
    }
}
