<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        return response()->json([
            'data' => $business->holidays()->orderBy('date')->get(),
        ]);
    }

    public function store(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $data = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('holidays', 'date')->where('business_id', $business->id),
            ],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $holiday = Holiday::create([
            ...$data,
            'business_id' => $business->id,
        ]);

        return response()->json([
            'message' => 'Festivo configurado',
            'holiday_id' => $holiday->id,
        ], 201);
    }

    public function destroy(Request $request, Holiday $holiday)
    {
        abort_unless($request->user()->can('manage', $holiday->business), 403);

        $holiday->delete();

        return response()->json(['message' => 'Festivo eliminado']);
    }
}
