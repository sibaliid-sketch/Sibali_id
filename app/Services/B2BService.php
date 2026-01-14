<?php

namespace App\Services;

use App\Repositories\B2BRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class B2BService
{
    protected $b2bRepository;

    protected $mediaService;

    public function __construct(B2BRepository $b2bRepository, MediaService $mediaService)
    {
        $this->b2bRepository = $b2bRepository;
        $this->mediaService = $mediaService;
    }

    public function getPartners(array $filters = [])
    {
        return $this->b2bRepository->getAllPartners($filters);
    }

    public function createPartner(array $data)
    {
        try {
            DB::beginTransaction();

            $data['created_by'] = auth()->id();
            $data['status'] = 'prospect';

            $partner = $this->b2bRepository->createPartner($data);

            // Create initial contact record
            $this->createContactRecord($partner, $data);

            Log::info('Partner created', [
                'partner_id' => $partner->id,
                'company_name' => $partner->company_name,
            ]);

            DB::commit();

            return $partner;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create partner', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function bulkEnrollStudents(array $data)
    {
        try {
            DB::beginTransaction();

            $partnerId = $data['partner_id'];
            $students = $data['students'];
            $programId = $data['program_id'];

            $results = [
                'success' => [],
                'failed' => [],
            ];

            foreach ($students as $studentData) {
                try {
                    $student = $this->enrollStudent($partnerId, $studentData, $programId);
                    $results['success'][] = $student;
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'data' => $studentData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Bulk enrollment completed', [
                'partner_id' => $partnerId,
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
            ]);

            DB::commit();

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed bulk enrollment', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function uploadContract($partnerId, array $data)
    {
        try {
            DB::beginTransaction();

            $partner = $this->b2bRepository->findPartnerById($partnerId);

            if (! $partner) {
                return null;
            }

            // Upload contract file
            $file = $data['contract_file'];
            $uploadResult = $this->mediaService->upload($file, 'contracts');

            $contract = $this->b2bRepository->createContract([
                'partner_id' => $partnerId,
                'contract_type' => $data['contract_type'],
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['url'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'pending_signature',
                'uploaded_by' => auth()->id(),
            ]);

            Log::info('Contract uploaded', [
                'contract_id' => $contract->id,
                'partner_id' => $partnerId,
            ]);

            DB::commit();

            return $contract;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload contract', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getContractStatus($contractId)
    {
        $contract = $this->b2bRepository->findContractById($contractId);

        if (! $contract) {
            return null;
        }

        return [
            'contract' => $contract,
            'signatures' => $this->getContractSignatures($contractId),
            'history' => $this->getContractHistory($contractId),
            'renewal_status' => $this->checkRenewalStatus($contract),
        ];
    }

    public function getProposals(array $filters = [])
    {
        return $this->b2bRepository->getAllProposals($filters);
    }

    public function createProposal(array $data)
    {
        try {
            DB::beginTransaction();

            $data['created_by'] = auth()->id();
            $data['status'] = 'draft';
            $data['proposal_number'] = $this->generateProposalNumber();

            $proposal = $this->b2bRepository->createProposal($data);

            Log::info('Proposal created', [
                'proposal_id' => $proposal->id,
                'partner_id' => $data['partner_id'],
            ]);

            DB::commit();

            return $proposal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create proposal', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendProposal($proposalId)
    {
        try {
            DB::beginTransaction();

            $proposal = $this->b2bRepository->findProposalById($proposalId);

            if (! $proposal) {
                return false;
            }

            // Send proposal via email
            $this->sendProposalEmail($proposal);

            $this->b2bRepository->updateProposal($proposalId, [
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('Proposal sent', ['proposal_id' => $proposalId]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send proposal', [
                'proposal_id' => $proposalId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getPartnerAnalytics($partnerId)
    {
        $partner = $this->b2bRepository->findPartnerById($partnerId);

        if (! $partner) {
            return null;
        }

        return [
            'partner' => $partner,
            'total_students' => $this->getTotalStudents($partnerId),
            'active_contracts' => $this->getActiveContracts($partnerId),
            'revenue' => $this->calculatePartnerRevenue($partnerId),
            'engagement_rate' => $this->calculateEngagementRate($partnerId),
            'satisfaction_score' => $this->getPartnerSatisfactionScore($partnerId),
        ];
    }

    public function renewContract($contractId, array $data)
    {
        try {
            DB::beginTransaction();

            $contract = $this->b2bRepository->findContractById($contractId);

            if (! $contract) {
                return null;
            }

            // Create renewal record
            $renewal = $this->b2bRepository->createContractRenewal([
                'original_contract_id' => $contractId,
                'new_end_date' => $data['new_end_date'],
                'terms' => json_encode($data['terms'] ?? []),
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            Log::info('Contract renewal initiated', [
                'contract_id' => $contractId,
                'renewal_id' => $renewal->id,
            ]);

            DB::commit();

            return $renewal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew contract', [
                'contract_id' => $contractId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function createContactRecord($partner, $data)
    {
        return $this->b2bRepository->createContact([
            'partner_id' => $partner->id,
            'name' => $data['contact_person'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_primary' => true,
        ]);
    }

    protected function enrollStudent($partnerId, $studentData, $programId)
    {
        // Create student account and enroll in program
        return $this->b2bRepository->enrollStudent([
            'partner_id' => $partnerId,
            'name' => $studentData['name'],
            'email' => $studentData['email'],
            'program_id' => $programId,
        ]);
    }

    protected function generateProposalNumber()
    {
        $prefix = 'PROP';
        $date = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    protected function sendProposalEmail($proposal)
    {
        // Placeholder for email sending
        Log::info('Sending proposal email', ['proposal_id' => $proposal->id]);
    }

    protected function getContractSignatures($contractId)
    {
        return []; // Placeholder
    }

    protected function getContractHistory($contractId)
    {
        return []; // Placeholder
    }

    protected function checkRenewalStatus($contract)
    {
        $daysUntilExpiry = now()->diffInDays($contract->end_date, false);

        return [
            'days_until_expiry' => $daysUntilExpiry,
            'renewal_recommended' => $daysUntilExpiry <= 90,
            'status' => $daysUntilExpiry <= 30 ? 'urgent' : ($daysUntilExpiry <= 90 ? 'upcoming' : 'normal'),
        ];
    }

    protected function getTotalStudents($partnerId)
    {
        return 0; // Placeholder
    }

    protected function getActiveContracts($partnerId)
    {
        return 0; // Placeholder
    }

    protected function calculatePartnerRevenue($partnerId)
    {
        return 0; // Placeholder
    }

    protected function calculateEngagementRate($partnerId)
    {
        return 0; // Placeholder
    }

    protected function getPartnerSatisfactionScore($partnerId)
    {
        return 0; // Placeholder
    }
}
