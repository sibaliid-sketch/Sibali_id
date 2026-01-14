<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class Layer12_FileUploadSecurity
{
    protected $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    protected $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7',
        'exe', 'bat', 'cmd', 'com', 'pif',
        'sh', 'bash',
        'js', 'vbs', 'jar',
    ];

    protected $maxFileSize = 52428800; // 50MB

    public function check(Request $request): array
    {
        if (! $request->hasFile()) {
            return [
                'allowed' => true,
                'layer' => 'Layer12_FileUploadSecurity',
            ];
        }

        $files = $request->allFiles();

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $validation = $this->validateFile($file);

                if (! $validation['allowed']) {
                    return array_merge($validation, [
                        'layer' => 'Layer12_FileUploadSecurity',
                    ]);
                }
            }
        }

        return [
            'allowed' => true,
            'layer' => 'Layer12_FileUploadSecurity',
        ];
    }

    protected function validateFile(UploadedFile $file): array
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'allowed' => false,
                'reason' => 'file_too_large',
                'message' => 'File size exceeds maximum allowed',
                'status_code' => 413,
            ];
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, $this->dangerousExtensions)) {
            \Log::warning('Dangerous file upload attempt', [
                'filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'ip' => request()->ip(),
            ]);

            return [
                'allowed' => false,
                'reason' => 'dangerous_extension',
                'message' => 'File type not allowed',
                'status_code' => 400,
            ];
        }

        // Check MIME type
        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'allowed' => false,
                'reason' => 'invalid_mime_type',
                'message' => 'File type not supported',
                'status_code' => 400,
            ];
        }

        // Check for double extensions
        if ($this->hasDoubleExtension($file->getClientOriginalName())) {
            return [
                'allowed' => false,
                'reason' => 'double_extension',
                'message' => 'Invalid filename',
                'status_code' => 400,
            ];
        }

        return ['allowed' => true];
    }

    protected function hasDoubleExtension(string $filename): bool
    {
        $parts = explode('.', $filename);

        if (count($parts) > 2) {
            $secondToLast = strtolower($parts[count($parts) - 2]);

            return in_array($secondToLast, $this->dangerousExtensions);
        }

        return false;
    }
}
