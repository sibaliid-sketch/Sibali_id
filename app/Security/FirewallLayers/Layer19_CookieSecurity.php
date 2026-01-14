<?php

namespace App\Security\FirewallLayers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class Layer19_CookieSecurity
{
    public function check(Request $request): array
    {
        return [
            'allowed' => true,
            'layer' => 'Layer19_CookieSecurity',
        ];
    }

    public function secureCookies(Response $response): Response
    {
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            $response->headers->removeCookie($cookie->getName());

            $response->headers->setCookie(new Cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $this->shouldBeSecure(),
                true, // HttpOnly
                false,
                $this->getSameSite()
            ));
        }

        return $response;
    }

    protected function shouldBeSecure(): bool
    {
        return ! app()->environment('local');
    }

    protected function getSameSite(): string
    {
        return config('session.same_site', 'lax');
    }
}
