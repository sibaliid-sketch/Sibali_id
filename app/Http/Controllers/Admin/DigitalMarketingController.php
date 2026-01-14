<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MarketingService;
use Illuminate\Http\Request;

class DigitalMarketingController extends Controller
{
    protected $marketingService;

    public function __construct(MarketingService $marketingService)
    {
        $this->marketingService = $marketingService;
        $this->middleware(['auth', 'role:admin|marketing_manager']);
    }

    public function campaigns(Request $request)
    {
        $campaigns = $this->marketingService->getCampaigns($request->all());

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    public function createCampaign(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,social,sms,mixed',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'required|numeric|min:0',
            'channels' => 'required|array',
            'target_audience' => 'required|array',
        ]);

        $campaign = $this->marketingService->createCampaign($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully',
            'data' => $campaign,
        ], 201);
    }

    public function scheduleCampaign(Request $request, $id)
    {
        $request->validate([
            'schedule_at' => 'required|date|after:now',
        ]);

        $campaign = $this->marketingService->scheduleCampaign($id, $request->schedule_at);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign scheduled successfully',
            'data' => $campaign,
        ]);
    }

    public function previewCampaign($id)
    {
        $preview = $this->marketingService->generateCampaignPreview($id);

        if (! $preview) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    public function pushCampaign($id)
    {
        $result = $this->marketingService->pushCampaign($id);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to push campaign',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign pushed successfully',
            'data' => $result,
        ]);
    }

    public function pauseCampaign($id)
    {
        $campaign = $this->marketingService->pauseCampaign($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign paused successfully',
            'data' => $campaign,
        ]);
    }

    public function resumeCampaign($id)
    {
        $campaign = $this->marketingService->resumeCampaign($id);

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campaign resumed successfully',
            'data' => $campaign,
        ]);
    }

    public function campaignAnalytics($id)
    {
        $analytics = $this->marketingService->getCampaignAnalytics($id);

        if (! $analytics) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    public function uploadAsset(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'type' => 'required|in:image,video,document',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $asset = $this->marketingService->uploadMarketingAsset($request->file('file'), $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Asset uploaded successfully',
            'data' => $asset,
        ], 201);
    }

    public function contentIdeas(Request $request)
    {
        $ideas = $this->marketingService->getContentIdeas($request->all());

        return response()->json([
            'success' => true,
            'data' => $ideas,
        ]);
    }

    public function generateContentIdea(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
            'audience' => 'required|string',
            'channel' => 'required|in:social,email,blog,video',
        ]);

        $idea = $this->marketingService->generateContentIdea($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Content idea generated',
            'data' => $idea,
        ], 201);
    }
}
