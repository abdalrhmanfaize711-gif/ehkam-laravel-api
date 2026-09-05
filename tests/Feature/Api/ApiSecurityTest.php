<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    public function test_every_api_route_has_expected_authentication_posture(): void
    {
        $public = [
            'api/login',
            'api/register',
            'api/loginStudent',
        ];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (in_array($uri, $public, true)) {
                $this->assertTrue(
                    !in_array('auth:sanctum', $middleware, true),
                    "Public endpoint unexpectedly protected: {$uri}"
                );
                continue;
            }

            $hasAuth = collect($middleware)->contains(
                fn ($m) => in_array($m, ['auth:sanctum', 'auth:api'], true)
                    || str_starts_with((string) $m, 'auth:')
            );

            $this->assertTrue($hasAuth, "Unprotected API endpoint: {$uri}");
        }
    }

    public function test_there_are_no_duplicate_route_signatures(): void
    {
        $seen = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $methods = array_diff($route->methods(), ['HEAD']);
            foreach ($methods as $method) {
                $key = $method . ' ' . $uri;
                $seen[$key] = ($seen[$key] ?? 0) + 1;
            }
        }

        $duplicates = array_keys(array_filter($seen, fn ($count) => $count > 1));

        $this->assertSame([], $duplicates, 'Duplicate API route signatures found: '.implode(', ', $duplicates));
    }
}
