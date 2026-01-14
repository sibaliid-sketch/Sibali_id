<?php

return [
    'cms_rules' => [
        'content_types' => [
            'article' => ['title', 'body', 'tags', 'categories'],
            'video' => ['title', 'url', 'description', 'thumbnail'],
            'quiz' => ['title', 'questions', 'answers', 'explanation'],
            'ebook' => ['title', 'file', 'description', 'preview'],
        ],
        'workflows' => [
            'draft' => 'author_only',
            'review' => 'editor_approval',
            'publish' => 'admin_approval',
            'archive' => 'auto_after_1_year',
        ],
        'versioning' => [
            'enabled' => true,
            'max_versions' => 10,
            'auto_save' => true,
        ],
    ],
    'moderation' => [
        'auto_moderation' => [
            'spam_filter' => true,
            'profanity_check' => true,
            'duplicate_detection' => true,
        ],
        'manual_review' => [
            'threshold_score' => 0.7,
            'review_queue' => true,
            'escalation_rules' => ['high_traffic', 'controversial_topics'],
        ],
        'reporting' => [
            'user_reports' => true,
            'moderator_logs' => true,
            'content_flags' => ['inappropriate', 'copyright', 'misinformation'],
        ],
    ],
    'seo_optimization' => [
        'meta_tags' => [
            'title' => true,
            'description' => true,
            'keywords' => true,
            'open_graph' => true,
        ],
        'url_structure' => 'slug_based',
        'sitemap_generation' => true,
        'robots_txt' => true,
    ],
    'multimedia_handling' => [
        'image_formats' => ['jpg', 'png', 'webp', 'svg'],
        'video_formats' => ['mp4', 'webm', 'avi'],
        'max_file_size' => '50MB',
        'compression' => true,
        'watermarking' => false,
    ],
];
