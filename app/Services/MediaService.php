<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaService
{
    protected $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    protected $maxSize = 10240; // 10MB in KB

    public function upload($file, $directory = 'media')
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file);

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            // Create media record
            $media = $this->createMediaRecord($file, $path);

            Log::info('Media uploaded', [
                'filename' => $filename,
                'path' => $path
            ]);

            return $media;
        } catch (\Exception $e) {
            Log::error('Failed to upload media', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function validateFile($file)
    {
        // Check if file exists
        if (!$file || !$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > ($this->maxSize * 1024)) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimes)) {
            throw new \Exception('File type not allowed');
        }
    }

    protected function generateFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $timestamp = time();
        $random = Str::random(8);

        return "{$basename}-{$timestamp}-{$random}.{$extension}";
    }

    protected function createMediaRecord($file, $path)
    {
        return [
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now(),
        ];
    }

    public function delete($path)
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('Media deleted', ['path' => $path]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete media', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUrl($path)
    {
        return Storage::url($path);
    }
}
