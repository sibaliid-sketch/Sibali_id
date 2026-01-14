<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SystemHealthController extends Controller
{
    public function dashboard()
    {
        // TODO: Aggregate from monitoring, APM, DB health, backup status
        return response()->json([
            'uptime' => '99.9%',
            'queue_lengths' => ['default' => 0, 'high' => 0],
            'error_rate' => 0.01,
            'services' => [],
        ]);
    }

    public function services()
    {
        return response()->json(['services' => []]);
    }

    public function oncall()
    {
        // TODO: Quick links to on-call contacts
        return response()->json(['oncall' => []]);
    }

    public function runbooks()
    {
        return response()->json(['runbooks' => []]);
    }
}
