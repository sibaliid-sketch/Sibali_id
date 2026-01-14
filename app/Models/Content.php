<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'body',
        'excerpt',
        'featured_image',
        'status',
        'published_at',
        'publish_at',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'meta',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'publish_at' => 'datetime',
        'meta' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('publish_at', '>', now());
    }

    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate excerpt from body
        return substr(strip_tags($this->body), 0, 200).'...';
    }

    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->body));
        $minutes = ceil($wordCount / 200); // Average reading speed

        return $minutes;
    }
}
