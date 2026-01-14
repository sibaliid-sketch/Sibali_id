<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OperationalController extends Controller
{
    public function rooms(Request $request)
    {
        return response()->json(['data' => []]);
    }

    public function storeRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'facilities' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Room created'], 201);
    }

    public function bookResource(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Auto-detect double-bookings, capacity checks
        return response()->json(['booking_id' => 'BK-001'], 201);
    }

    public function assignTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task' => 'required|string',
            'staff_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Auto-assignment rules, escalation
        return response()->json(['task_id' => 'TSK-001'], 201);
    }
}
