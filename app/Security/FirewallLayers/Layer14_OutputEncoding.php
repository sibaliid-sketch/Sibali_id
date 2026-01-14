<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;

class Layer14_OutputEncoding
{
    public function check(Request $request): array
    {
        // This layer doesn't block requests
        // It provides encoding utilities for output

        return [
            'allowed' => true,
            'layer' => 'Layer14_OutputEncoding'
        ];
    }

    /**
     * Encode for HTML context
     */
    public function encodeHTML(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Encode for JavaScript context
     */
    public function encodeJS(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Encode for URL context
     */
    public function encodeURL(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * Encode for CSS context
     */
    public function encodeCSS(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
    }
}
