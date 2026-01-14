<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        return response()->json(['data' => []]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json(['user_id' => 1], 201);
    }

    public function updateRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Audit trail with reason and operator id
        return response()->json(['message' => 'Role updated']);
    }

    public function impersonate(Request $request, $id)
    {
        // TODO: Elevated perm required, log impersonation
        return response()->json(['token' => 'impersonation-token']);
    }

    public function forceLogout($id)
    {
        // TODO: Force logout user, invalidate sessions
        return response()->json(['message' => 'User logged out']);
    }

    public function resetPassword(Request $request, $id)
    {
        // TODO: Generate signed token, send reset email
        return response()->json(['message' => 'Password reset link sent']);
    }
}
