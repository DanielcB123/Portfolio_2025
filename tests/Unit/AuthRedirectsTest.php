<?php

namespace Tests\Unit;

use App\Support\AuthRedirects;
use Tests\TestCase;

class AuthRedirectsTest extends TestCase
{
    public function test_resolve_returns_default_when_redirect_is_null(): void
    {
        $default = route('dashboard');

        $this->assertSame($default, AuthRedirects::resolve(null, $default));
    }

    public function test_resolve_allows_dashboard_path(): void
    {
        $default = route('dashboard');
        $redirect = route('dashboard', absolute: false);

        $this->assertSame($redirect, AuthRedirects::resolve($redirect, $default));
    }

    public function test_resolve_allows_incident_dashboard_path(): void
    {
        $default = route('dashboard');
        $redirect = route('incident.dashboard', absolute: false);

        $this->assertSame($redirect, AuthRedirects::resolve($redirect, $default));
    }

    public function test_resolve_rejects_external_redirect(): void
    {
        $default = route('dashboard');

        $this->assertSame(
            $default,
            AuthRedirects::resolve('https://evil.example/phish', $default)
        );
    }

    public function test_resolve_rejects_unknown_internal_path(): void
    {
        $default = route('dashboard');

        $this->assertSame($default, AuthRedirects::resolve('/admin', $default));
    }
}
