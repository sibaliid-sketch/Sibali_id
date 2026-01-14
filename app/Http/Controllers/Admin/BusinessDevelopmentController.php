<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\B2BService;
use Illuminate\Http\Request;

class BusinessDevelopmentController extends Controller
{
    protected $b2bService;

    public function __construct(B2BService $b2bService)
    {
        $this->b2bService = $b2bService;
        $this->middleware(['auth', 'role:admin|bd_manager']);
    }

    public function partners(Request $request)
    {
        $partners = $this->b2bService->getPartners($request->all());

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    public function createPartner(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:150',
            'email' => 'required|email',
            'phone' => 'required|string',
            'industry' => 'required|string',
            'company_size' => 'required|in:small,medium,large,enterprise',
        ]);

        $partner = $this->b2bService->createPartner($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Partner created successfully',
            'data' => $partner,
        ], 201);
    }

    public function bulkEnroll(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:corporate_partners,id',
            'students' => 'required|array|min:1',
            'students.*.name' => 'required|string',
            'students.*.email' => 'required|email',
            'program_id' => 'required|exists:services,id',
        ]);

        $result = $this->b2bService->bulkEnrollStudents($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment initiated',
            'data' => $result,
        ], 201);
    }

    public function uploadContract(Request $request, $partnerId)
    {
        $request->validate([
            'contract_file' => 'required|file|mimes:pdf|max:5120',
            'contract_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $contract = $this->b2bService->uploadContract($partnerId, $request->all());

        if (! $contract) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract uploaded successfully',
            'data' => $contract,
        ], 201);
    }

    public function getContractStatus($contractId)
    {
        $status = $this->b2bService->getContractStatus($contractId);

        if (! $status) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function proposals(Request $request)
    {
        $proposals = $this->b2bService->getProposals($request->all());

        return response()->json([
            'success' => true,
            'data' => $proposals,
        ]);
    }

    public function createProposal(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:corporate_partners,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'pricing' => 'required|array',
            'valid_until' => 'required|date|after:now',
        ]);

        $proposal = $this->b2bService->createProposal($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proposal created successfully',
            'data' => $proposal,
        ], 201);
    }

    public function sendProposal($proposalId)
    {
        $result = $this->b2bService->sendProposal($proposalId);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proposal sent successfully',
        ]);
    }

    public function partnerAnalytics($partnerId)
    {
        $analytics = $this->b2bService->getPartnerAnalytics($partnerId);

        if (! $analytics) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    public function renewContract(Request $request, $contractId)
    {
        $request->validate([
            'new_end_date' => 'required|date|after:now',
            'terms' => 'nullable|array',
        ]);

        $contract = $this->b2bService->renewContract($contractId, $request->all());

        if (! $contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract renewed successfully',
            'data' => $contract,
        ]);
    }
}
