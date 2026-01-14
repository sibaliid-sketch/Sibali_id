<?php

namespace App\Repositories;

use App\Models\Business\Contract;
use App\Models\Business\CorporatePartner;
use App\Models\Business\Proposal;
use Illuminate\Support\Facades\DB;

class B2BRepository
{
    public function getAllPartners(array $filters = [])
    {
        $query = CorporatePartner::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('company_name', 'like', "%{$filters['search']}%")
                    ->orWhere('contact_person', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with('contracts')
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createPartner(array $data)
    {
        return CorporatePartner::create($data);
    }

    public function findPartnerById($id)
    {
        return CorporatePartner::with(['contracts', 'contacts', 'students'])->find($id);
    }

    public function createContact(array $data)
    {
        return DB::table('partner_contacts')->insert(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function enrollStudent(array $data)
    {
        return DB::table('corporate_enrollments')->insert(array_merge($data, [
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function createContract(array $data)
    {
        return Contract::create($data);
    }

    public function findContractById($id)
    {
        return Contract::with('partner')->find($id);
    }

    public function createContractRenewal(array $data)
    {
        return DB::table('contract_renewals')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function getAllProposals(array $filters = [])
    {
        $query = Proposal::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        return $query->with('partner')
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createProposal(array $data)
    {
        return Proposal::create($data);
    }

    public function findProposalById($id)
    {
        return Proposal::with('partner')->find($id);
    }

    public function updateProposal($id, array $data)
    {
        $proposal = $this->findProposalById($id);

        if (! $proposal) {
            return null;
        }

        $proposal->update($data);

        return $proposal->fresh();
    }
}
