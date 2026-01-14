<?php

namespace App\Repositories;

use App\Models\Content;
use Illuminate\Support\Facades\DB;

class ContentRepository
{
    public function getAll(array $filters = [])
    {
        $query = Content::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('body', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with('author')
                    ->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function findById($id)
    {
        return Content::with('author')->find($id);
    }

    public function create(array $data)
    {
        return Content::create($data);
    }

    public function update($id, array $data)
    {
        $content = $this->findById($id);

        if (!$content) {
            return null;
        }

        $content->update($data);
        return $content->fresh();
    }

    public function delete($id)
    {
        $content = $this->findById($id);

        if (!$content) {
            return false;
        }

        return $content->delete();
    }

    public function getRevisions($contentId)
    {
        return DB::table('content_revisions')
            ->where('content_id', $contentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRevisionById($revisionId)
    {
        return DB::table('content_revisions')
            ->where('id', $revisionId)
            ->first();
    }

    public function createRevision(array $data)
    {
        return DB::table('content_revisions')->insertGetId($data);
    }
}
