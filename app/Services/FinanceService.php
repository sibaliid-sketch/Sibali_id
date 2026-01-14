<?php

namespace App\Services;

use App\Repositories\PaymentsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceService
{
    protected $paymentsRepository;

    public function __construct(PaymentsRepository $paymentsRepository)
    {
        $this->paymentsRepository = $paymentsRepository;
    }

    public function getInvoices(array $filters = [])
    {
        return $this->paymentsRepository->getInvoices($filters);
    }

    public function generateInvoice(array $data)
    {
        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $tax = $subtotal * 0.11; // 11% VAT
            $total = $subtotal + $tax;

            $invoiceData = [
                'student_id' => $data['student_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'items' => json_encode($data['items']),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'due_date' => $data['due_date'],
                'status' => 'pending',
                'created_by' => auth()->id()
            ];

            $invoice = $this->paymentsRepository->createInvoice($invoiceData);

            Log::info('Invoice generated', [
                'invoice_id' => $invoice->id,
                'student_id' => $data['student_id'],
                'total' => $total
            ]);

            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate invoice', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function markInvoicePaid($id)
    {
        try {
            DB::beginTransaction();

            $invoice = $this->paymentsRepository->updateInvoice($id, [
                'status' => 'paid',
                'paid_at' => now(),
                'verified_by' => auth()->id()
            ]);

            if ($invoice) {
                Log::info('Invoice marked as paid', ['invoice_id' => $id]);
            }

            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function initiateRefund($paymentId, array $data)
    {
        try {
            DB::beginTransaction();

            $payment = $this->paymentsRepository->findPaymentById($paymentId);

            if (!$payment || $payment->status !== 'verified') {
                return null;
            }

            if ($data['amount'] > $payment->amount) {
                throw new \Exception('Refund amount cannot exceed payment amount');
            }

            $refund = $this->paymentsRepository->createRefund([
                'payment_id' => $paymentId,
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'requested_by' => auth()->id()
            ]);

            Log::info('Refund initiated', [
                'refund_id' => $refund->id,
                'payment_id' => $paymentId,
                'amount' => $data['amount']
            ]);

            DB::commit();
            return $refund;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate refund', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function runReconciliation(array $data)
    {
        try {
            $startDate = $data['start_date'];
            $endDate = $data['end_date'];

            $payments = $this->paymentsRepository->getPaymentsByDateRange($startDate, $endDate);
            $invoices = $this->paymentsRepository->getInvoicesByDateRange($startDate, $endDate);

            $totalPayments = $payments->sum('amount');
            $totalInvoices = $invoices->sum('total');
            $difference = $totalPayments - $totalInvoices;

            $result = [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'summary' => [
                    'total_payments' => $totalPayments,
                    'total_invoices' => $totalInvoices,
                    'difference' => $difference,
                    'payment_count' => $payments->count(),
                    'invoice_count' => $invoices->count()
                ],
                'unmatched_payments' => $this->findUnmatchedPayments($payments, $invoices),
                'unpaid_invoices' => $invoices->where('status', 'pending')
            ];

            Log::info('Reconciliation completed', [
                'period' => "{$startDate} to {$endDate}",
                'difference' => $difference
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to run reconciliation', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateFinancialReport(array $data)
    {
        try {
            $type = $data['type'];
            $period = $data['period'];

            $report = $this->paymentsRepository->getFinancialReport($type, $period);

            Log::info('Financial report generated', [
                'type' => $type,
                'period' => $period
            ]);

            return $report;
        } catch (\Exception $e) {
            Log::error('Failed to generate financial report', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createManualAdjustment(array $data)
    {
        try {
            DB::beginTransaction();

            $adjustment = $this->paymentsRepository->createAdjustment([
                'payment_id' => $data['payment_id'],
                'type' => $data['adjustment_type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'created_by' => auth()->id()
            ]);

            Log::info('Manual adjustment created', [
                'adjustment_id' => $adjustment->id,
                'payment_id' => $data['payment_id']
            ]);

            DB::commit();
            return $adjustment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create manual adjustment', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    protected function findUnmatchedPayments($payments, $invoices)
    {
        $invoiceIds = $invoices->pluck('id')->toArray();

        return $payments->filter(function ($payment) use ($invoiceIds) {
            return !in_array($payment->invoice_id, $invoiceIds);
        });
    }
}
