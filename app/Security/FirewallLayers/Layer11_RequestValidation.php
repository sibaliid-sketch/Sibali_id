<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer11_RequestValidation
{
    protected $maxPayloadSize = 10485760; // 10MB

    public function check(Request $request): array
    {
        // Check payload size
        $contentLength = $request->header('Content-Length', 0);

        if ($contentLength > $this->maxPayloadSize) {
            return [
                'allowed' => false,
                'layer' => 'Layer11_RequestValidation',
                'reason' => 'payload_too_large',
                'message' => 'Request payload too large',
                'status_code' => 413,
            ];
        }

        // Validate JSON for API requests
        if ($request->isJson()) {
            $content = $request->getContent();

            if (! empty($content)) {
                json_decode($content);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [
                        'allowed' => false,
                        'layer' => 'Layer11_RequestValidation',
                        'reason' => 'invalid_json',
                        'message' => 'Invalid JSON payload',
                        'status_code' => 400,
                    ];
                }
            }
        }

        // Check for null bytes
        if ($this->containsNullBytes($request)) {
            return [
                'allowed' => false,
                'layer' => 'Layer11_RequestValidation',
                'reason' => 'null_bytes_detected',
                'message' => 'Invalid characters in request',
                'status_code' => 400,
            ];
        }

        return [
            'allowed' => true,
            'layer' => 'Layer11_RequestValidation',
        ];
    }

    protected function containsNullBytes(Request $request): bool
    {
        $inputs = $request->all();

        foreach ($inputs as $value) {
            if (is_string($value) && strpos($value, "\0") !== false) {
                return true;
            }
        }

        return false;
    }
}
