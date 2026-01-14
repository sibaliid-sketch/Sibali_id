<?php

namespace App\Repositories;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\ContentAsset;
use App\Models\Marketing\ContentIdea;

class MarketingRepository
{
    public function getAllCampaigns(array $filters = [])
    {
        $query = Campaign::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        return $query->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createCampaign(array $data)
    {
        return Campaign::create($data);
    }

    public function findCampaignById($id)
    {
        return Campaign::with(['creator', 'assets'])->find($id);
    }

    public function updateCampaign($id, array $data)
    {
        $campaign = $this->findCampaignById($id);

        if (! $campaign) {
            return null;
        }

        $campaign->update($data);

        return $campaign->fresh();
    }

    public function createAsset(array $data)
    {
        return ContentAsset::create($data);
    }

    public function getContentIdeas(array $filters = [])
    {
        $query = ContentIdea::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function createContentIdea(array $data)
    {
        return ContentIdea::create($data);
    }

    public function findProposalById($proposalId)
    {
        return DB::table('proposals')->where('id', $proposalId)->first();
    }

    public function updateProposal($proposalId, array $data)
    {
        return DB::table('proposals')
            ->where('id', $proposalId)
            ->update(array_merge($data, ['updated_at' => now()]));
    }
}
