<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PaymentsRepository
{
    public function getInvoices(array $filters = [])
    {
        $query = DB::table('invoices')
            ->join('users', 'invoices.student_id', '=', 'users.id')
            ->select('invoices.*', 'users.name as student_name');

        if (isset($filters['status'])) {
            $query->where('invoices.status', $filters['status']);
        }

        if (isset($filters['student_id'])) {
            $query->where('invoices.student_id', $filters['student_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('invoices.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('invoices.created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('invoices.created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createInvoice(array $data)
    {
        $id = DB::table('invoices')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return DB::table('invoices')->find($id);
    }

    public function updateInvoice($id, array $data)
    {
        $updated = DB::table('invoices')
            ->where('id', $id)
            ->update(array_merge($data, [
                'updated_at' => now(),
            ]));

        if ($updated) {
            return DB::table('invoices')->find($id);
        }

        return null;
    }

    public function findPaymentById($id)
    {
        return DB::table('payments')->find($id);
    }

    public function createRefund(array $data)
    {
        $id = DB::table('refunds')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return DB::table('refunds')->find($id);
    }

    public function getPaymentsByDateRange($startDate, $endDate)
    {
        return DB::table('payments')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'verified')
            ->get();
    }

    public function getInvoicesByDateRange($startDate, $endDate)
    {
        return DB::table('invoices')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getFinancialReport($type, $period)
    {
        // Implementation depends on report type
        // This is a placeholder
        return [
            'type' => $type,
            'period' => $period,
            'data' => [],
        ];
    }

    public function createAdjustment(array $data)
    {
        $id = DB::table('payment_adjustments')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return DB::table('payment_adjustments')->find($id);
    }
}
