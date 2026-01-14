<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ErrorTrackerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        // TODO: Error grouping, integration with Sentry-like
        return response()->json(['errors' => []]);
    }

    public function show($id)
    {
        // TODO: Stacktrace → blame (git commit)
        return response()->json(['error' => []]);
    }

    public function assign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'assignee_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Error assigned']);
    }

    public function resolve($id)
    {
        return response()->json(['message' => 'Error resolved']);
    }

    public function addNote(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Note added']);
    }

    public function attachCommit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'commit_sha' => 'required|string',
            'pr_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['message' => 'Commit attached']);
    }
}
