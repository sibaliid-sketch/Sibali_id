<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CmsService;
use App\Http\Requests\Admin\ContentStoreRequest;

class ContentManagementController extends Controller
{
    protected $cmsService;

    public function __construct(CmsService $cmsService)
    {
        $this->cmsService = $cmsService;
        $this->middleware(['auth', 'role:admin|editor']);
    }

    public function index(Request $request)
    {
        $contents = $this->cmsService->getContents($request->all());

        return response()->json([
            'success' => true,
            'data' => $contents
        ]);
    }

    public function store(ContentStoreRequest $request)
    {
        $content = $this->cmsService->createContent($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Content created successfully',
            'data' => $content
        ], 201);
    }

    public function show($id)
    {
        $content = $this->cmsService->getContentById($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    public function update(ContentStoreRequest $request, $id)
    {
        $content = $this->cmsService->updateContent($id, $request->validated());

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content updated successfully',
            'data' => $content
        ]);
    }

    public function destroy($id)
    {
        $deleted = $this->cmsService->deleteContent($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content deleted successfully'
        ]);
    }

    public function publish($id)
    {
        $content = $this->cmsService->publishContent($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content published successfully',
            'data' => $content
        ]);
    }

    public function unpublish($id)
    {
        $content = $this->cmsService->unpublishContent($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content unpublished successfully',
            'data' => $content
        ]);
    }

    public function schedulePublish(Request $request, $id)
    {
        $request->validate([
            'publish_at' => 'required|date|after:now'
        ]);

        $content = $this->cmsService->schedulePublish($id, $request->publish_at);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content scheduled for publishing',
            'data' => $content
        ]);
    }

    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx'
        ]);

        $media = $this->cmsService->uploadMedia($request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully',
            'data' => $media
        ], 201);
    }

    public function getRevisions($id)
    {
        $revisions = $this->cmsService->getContentRevisions($id);

        return response()->json([
            'success' => true,
            'data' => $revisions
        ]);
    }

    public function rollback($id, $revisionId)
    {
        $content = $this->cmsService->rollbackToRevision($id, $revisionId);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content or revision not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content rolled back successfully',
            'data' => $content
        ]);
    }
}
