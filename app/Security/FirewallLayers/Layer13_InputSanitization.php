<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer13_InputSanitization
{
    public function check(Request $request): array
    {
        // Sanitize all inputs
        $sanitized = $this->sanitizeInputs($request->all());
        $request->merge($sanitized);

        return [
            'allowed' => true,
            'layer' => 'Layer13_InputSanitization'
        ];
    }

    protected function sanitizeInputs(array $inputs): array
    {
        $sanitized = [];

        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInputs($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        // Trim
        $value = trim($value);

        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        return $value;
    }
}
