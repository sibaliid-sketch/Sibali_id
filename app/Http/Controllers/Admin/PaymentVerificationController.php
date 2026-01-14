<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentVerificationController extends Controller
{
    public function unverified(Request $request)
    {
        return response()->json(['data' => []]);
    }

    public function uploadProof(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Encrypt stored proofs
        return response()->json(['proof_id' => 'PRF-001'], 201);
    }

    public function verify(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'notes' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Store reviewer decision, fraud heuristics
        return response()->json(['message' => 'Payment verified']);
    }

    public function dispute(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // TODO: Escalate to fraud team
        return response()->json(['dispute_id' => 'DSP-001'], 201);
    }

    public function forceSettle($id)
    {
        // TODO: Idempotency, reconciliation alignment
        return response()->json(['message' => 'Payment force settled']);
    }
}
