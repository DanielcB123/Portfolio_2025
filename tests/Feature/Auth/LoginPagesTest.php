<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create([
            'name'  => 'MediaHaus Squad',
            'slug'  => 'mediahaus-squad',
            'color' => '#2563eb',
        ]);

        Team::create([
            'name'  => 'Design Team',
            'slug'  => 'design-team',
            'color' => '#ec4899',
        ]);
    }

    public function test_taskflow_login_page_includes_demo_users(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Login')
            ->has('demoUsers', 5)
            ->where('demoPassword', 'password')
        );
    }

    public function test_incident_login_page_includes_demo_users(): void
    {
        $response = $this->get(route('incident.login'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Incident/Login')
            ->has('demoUsers', 5)
            ->where('demoPassword', 'password')
        );
    }

    public function test_incident_register_page_renders(): void
    {
        $response = $this->get(route('incident.register'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Incident/Register')
        );
    }
}
