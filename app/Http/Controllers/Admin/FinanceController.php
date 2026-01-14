<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
        $this->middleware(['auth', 'role:admin|finance_admin']);
    }

    public function invoices(Request $request)
    {
        $invoices = $this->financeService->getInvoices($request->all());

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function generateInvoice(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'items' => 'required|array',
            'due_date' => 'required|date',
        ]);

        $invoice = $this->financeService->generateInvoice($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully',
            'data' => $invoice,
        ], 201);
    }

    public function markPaid($id)
    {
        $invoice = $this->financeService->markInvoicePaid($id);

        if (! $invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid',
            'data' => $invoice,
        ]);
    }

    public function initiateRefund(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $refund = $this->financeService->initiateRefund($id, $request->all());

        if (! $refund) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found or refund not allowed',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund initiated successfully',
            'data' => $refund,
        ]);
    }

    public function reconciliation(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->financeService->runReconciliation($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation completed',
            'data' => $result,
        ]);
    }

    public function financialReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:monthly,quarterly,yearly',
            'period' => 'required|string',
        ]);

        $report = $this->financeService->generateFinancialReport($request->all());

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function manualAdjustment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'adjustment_type' => 'required|in:credit,debit',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
        ]);

        $adjustment = $this->financeService->createManualAdjustment($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Manual adjustment created',
            'data' => $adjustment,
        ], 201);
    }
}
