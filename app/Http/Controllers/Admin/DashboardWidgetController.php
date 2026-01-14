<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardWidgetController extends Controller
{
    public function index(Request $request)
    {
        // TODO: Permission-based visibility
        return response()->json(['widgets' => []]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'data_source' => 'required|string',
            'position' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['widget_id' => 'WDG-001'], 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Widget updated']);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Widget deleted']);
    }

    public function refresh($id)
    {
        // TODO: Manual refresh endpoint, cached with TTL
        return response()->json(['data' => []]);
    }
}
