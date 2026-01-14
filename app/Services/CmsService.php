<?php

namespace App\Services;

use App\Repositories\ContentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CmsService
{
    protected $contentRepository;

    protected $mediaService;

    public function __construct(ContentRepository $contentRepository, MediaService $mediaService)
    {
        $this->contentRepository = $contentRepository;
        $this->mediaService = $mediaService;
    }

    public function getContents(array $filters = [])
    {
        return $this->contentRepository->getAll($filters);
    }

    public function getContentById($id)
    {
        return $this->contentRepository->findById($id);
    }

    public function createContent(array $data)
    {
        try {
            DB::beginTransaction();

            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Sanitize HTML content
            if (isset($data['body'])) {
                $data['body'] = $this->sanitizeHtml($data['body']);
            }

            // Set author
            $data['author_id'] = auth()->id();

            // Set default status
            if (! isset($data['status'])) {
                $data['status'] = 'draft';
            }

            $content = $this->contentRepository->create($data);

            // Create initial revision
            $this->createRevision($content);

            Log::info('Content created', ['id' => $content->id, 'title' => $content->title]);

            DB::commit();

            return $content;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create content', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateContent($id, array $data)
    {
        try {
            DB::beginTransaction();

            $content = $this->contentRepository->findById($id);

            if (! $content) {
                return null;
            }

            // Create revision before update
            $this->createRevision($content);

            // Sanitize HTML content
            if (isset($data['body'])) {
                $data['body'] = $this->sanitizeHtml($data['body']);
            }

            $content = $this->contentRepository->update($id, $data);

            Log::info('Content updated', ['id' => $id]);

            DB::commit();

            return $content;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update content', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteContent($id)
    {
        try {
            $result = $this->contentRepository->delete($id);

            if ($result) {
                Log::info('Content deleted', ['id' => $id]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to delete content', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publishContent($id)
    {
        return $this->updateContent($id, [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function unpublishContent($id)
    {
        return $this->updateContent($id, [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function schedulePublish($id, $publishAt)
    {
        return $this->updateContent($id, [
            'status' => 'scheduled',
            'publish_at' => $publishAt,
        ]);
    }

    public function uploadMedia($file)
    {
        return $this->mediaService->upload($file);
    }

    public function getContentRevisions($id)
    {
        return $this->contentRepository->getRevisions($id);
    }

    public function rollbackToRevision($id, $revisionId)
    {
        try {
            DB::beginTransaction();

            $revision = $this->contentRepository->getRevisionById($revisionId);

            if (! $revision || $revision->content_id != $id) {
                return null;
            }

            $content = $this->updateContent($id, json_decode($revision->data, true));

            Log::info('Content rolled back', ['id' => $id, 'revision_id' => $revisionId]);

            DB::commit();

            return $content;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to rollback content', [
                'id' => $id,
                'revision_id' => $revisionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function createRevision($content)
    {
        return $this->contentRepository->createRevision([
            'content_id' => $content->id,
            'data' => json_encode($content->toArray()),
            'created_by' => auth()->id(),
        ]);
    }

    protected function sanitizeHtml($html)
    {
        // Basic HTML sanitization - in production use a library like HTMLPurifier
        $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';

        return strip_tags($html, $allowed_tags);
    }
}
