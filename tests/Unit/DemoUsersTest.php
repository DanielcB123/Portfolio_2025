<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Support\DemoUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_login_returns_empty_array_when_config_has_no_users(): void
    {
        config(['demo_users.users' => []]);

        $this->assertSame([], DemoUsers::forLogin());
    }

    public function test_for_login_maps_config_users_to_login_safe_payload(): void
    {
        Team::create([
            'name'  => 'MediaHaus Squad',
            'slug'  => 'mediahaus-squad',
            'color' => '#2563eb',
        ]);

        config([
            'demo_users.users' => [
                [
                    'name'      => 'Media User 1',
                    'email'     => 'media1@example.com',
                    'team_slug' => 'mediahaus-squad',
                ],
            ],
        ]);

        $users = DemoUsers::forLogin();

        $this->assertCount(1, $users);
        $this->assertSame([
            'name'  => 'Media User 1',
            'email' => 'media1@example.com',
            'team'  => 'MediaHaus Squad',
        ], $users[0]);
    }

    public function test_for_login_falls_back_when_team_is_missing(): void
    {
        config([
            'demo_users.users' => [
                [
                    'name'      => 'Orphan User',
                    'email'     => 'orphan@example.com',
                    'team_slug' => 'missing-team',
                ],
            ],
        ]);

        $users = DemoUsers::forLogin();

        $this->assertSame('Demo team', $users[0]['team']);
    }
}
