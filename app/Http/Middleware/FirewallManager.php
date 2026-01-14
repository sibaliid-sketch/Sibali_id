<?php

namespace App\Http\Middleware;

use App\Services\Security\FirewallService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirewallManager
{
    protected $firewallService;

    protected $layers = [];

    public function __construct(FirewallService $firewallService)
    {
        $this->firewallService = $firewallService;
        $this->loadLayers();
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Execute firewall layers in sequence
        foreach ($this->layers as $layer) {
            if (! $layer['enabled']) {
                continue;
            }

            $result = $this->executeLayer($layer['class'], $request);

            if (! $result['allowed']) {
                return $this->blockRequest($result, $request);
            }
        }

        $response = $next($request);

        // Log successful request
        $this->firewallService->logRequest($request, 'allowed');

        return $response;
    }

    protected function loadLayers(): void
    {
        $config = config('firewall.layers', []);

        $this->layers = [
            ['name' => 'Layer1_IPFilter', 'class' => \App\Security\FirewallLayers\Layer1_IPFilter::class, 'enabled' => $config['ip_filter'] ?? true],
            ['name' => 'Layer2_RateLimiter', 'class' => \App\Security\FirewallLayers\Layer2_RateLimiter::class, 'enabled' => $config['rate_limiter'] ?? true],
            ['name' => 'Layer3_UserAgentCheck', 'class' => \App\Security\FirewallLayers\Layer3_UserAgentCheck::class, 'enabled' => $config['ua_check'] ?? true],
            ['name' => 'Layer4_GeoBlocking', 'class' => \App\Security\FirewallLayers\Layer4_GeoBlocking::class, 'enabled' => $config['geo_blocking'] ?? false],
            ['name' => 'Layer5_BotDetection', 'class' => \App\Security\FirewallLayers\Layer5_BotDetection::class, 'enabled' => $config['bot_detection'] ?? true],
            ['name' => 'Layer6_SQLInjectionFilter', 'class' => \App\Security\FirewallLayers\Layer6_SQLInjectionFilter::class, 'enabled' => $config['sql_injection'] ?? true],
            ['name' => 'Layer7_XSSFilter', 'class' => \App\Security\FirewallLayers\Layer7_XSSFilter::class, 'enabled' => $config['xss_filter'] ?? true],
            ['name' => 'Layer8_CSRFProtection', 'class' => \App\Security\FirewallLayers\Layer8_CSRFProtection::class, 'enabled' => $config['csrf_protection'] ?? true],
            ['name' => 'Layer9_SessionSecurity', 'class' => \App\Security\FirewallLayers\Layer9_SessionSecurity::class, 'enabled' => $config['session_security'] ?? true],
            ['name' => 'Layer10_TwoFactorAuth', 'class' => \App\Security\FirewallLayers\Layer10_TwoFactorAuth::class, 'enabled' => $config['two_factor'] ?? false],
            ['name' => 'Layer11_RequestValidation', 'class' => \App\Security\FirewallLayers\Layer11_RequestValidation::class, 'enabled' => $config['request_validation'] ?? true],
            ['name' => 'Layer12_FileUploadSecurity', 'class' => \App\Security\FirewallLayers\Layer12_FileUploadSecurity::class, 'enabled' => $config['file_upload'] ?? true],
            ['name' => 'Layer13_InputSanitization', 'class' => \App\Security\FirewallLayers\Layer13_InputSanitization::class, 'enabled' => $config['input_sanitization'] ?? true],
            ['name' => 'Layer14_OutputEncoding', 'class' => \App\Security\FirewallLayers\Layer14_OutputEncoding::class, 'enabled' => $config['output_encoding'] ?? true],
            ['name' => 'Layer15_CORSProtection', 'class' => \App\Security\FirewallLayers\Layer15_CORSProtection::class, 'enabled' => $config['cors_protection'] ?? true],
            ['name' => 'Layer16_HTTPSEnforcement', 'class' => \App\Security\FirewallLayers\Layer16_HTTPSEnforcement::class, 'enabled' => $config['https_enforcement'] ?? true],
            ['name' => 'Layer17_ContentSecurityPolicy', 'class' => \App\Security\FirewallLayers\Layer17_ContentSecurityPolicy::class, 'enabled' => $config['csp'] ?? true],
            ['name' => 'Layer18_HTTPHeadersSecurity', 'class' => \App\Security\FirewallLayers\Layer18_HTTPHeadersSecurity::class, 'enabled' => $config['http_headers'] ?? true],
            ['name' => 'Layer19_CookieSecurity', 'class' => \App\Security\FirewallLayers\Layer19_CookieSecurity::class, 'enabled' => $config['cookie_security'] ?? true],
            ['name' => 'Layer20_DatabaseInjectionProtection', 'class' => \App\Security\FirewallLayers\Layer20_DatabaseInjectionProtection::class, 'enabled' => $config['db_injection'] ?? true],
        ];
    }

    protected function executeLayer(string $layerClass, Request $request): array
    {
        try {
            if (! class_exists($layerClass)) {
                \Log::warning("Firewall layer class not found: {$layerClass}");

                return ['allowed' => true];
            }

            $layer = app($layerClass);

            return $layer->check($request);
        } catch (\Exception $e) {
            \Log::error("Firewall layer execution failed: {$layerClass}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fail-open for non-critical layers, fail-closed for critical
            $criticalLayers = ['Layer6_SQLInjectionFilter', 'Layer7_XSSFilter', 'Layer8_CSRFProtection'];
            $isCritical = in_array(class_basename($layerClass), $criticalLayers);

            return ['allowed' => ! $isCritical, 'reason' => 'layer_error'];
        }
    }

    protected function blockRequest(array $result, Request $request): Response
    {
        $this->firewallService->logRequest($request, 'blocked', $result);

        $statusCode = $result['status_code'] ?? 403;
        $message = $result['message'] ?? 'Access denied by security policy';

        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
            'code' => 'FIREWALL_BLOCKED',
        ], $statusCode);
    }
}
