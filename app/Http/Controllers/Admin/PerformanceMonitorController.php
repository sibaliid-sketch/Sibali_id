<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PerformanceMonitorController extends Controller
{
    public function dashboard()
    {
        // TODO: Top endpoints by latency, service-map
        return response()->json([
            'top_endpoints' => [],
            'avg_response_time' => 0,
            'error_rate' => 0,
        ]);
    }

    public function traces(Request $request)
    {
        $endpoint = $request->input('endpoint');

        // TODO: Flamegraphs, drill-down to traces
        return response()->json(['traces' => []]);
    }

    public function baseline(Request $request)
    {
        // TODO: Compare current percentiles to baseline
        return response()->json(['baseline' => [], 'current' => []]);
    }

    public function alerts()
    {
        return response()->json(['alerts' => []]);
    }

    public function createIncident(Request $request)
    {
        // TODO: Open incident ticket from view
        return response()->json(['incident_id' => 'INC-001'], 201);
    }
}
