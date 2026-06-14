<?php

namespace App\Support;

use App\Models\Team;

class DemoUsers
{
    /**
     * Demo accounts safe to expose on login pages.
     *
     * @return list<array{name: string, email: string, team: string}>
     */
    public static function forLogin(): array
    {
        $config = config('demo_users', []);
        $users = $config['users'] ?? [];

        if ($users === []) {
            return [];
        }

        $teamNames = Team::query()
            ->whereIn('slug', collect($users)->pluck('team_slug')->unique())
            ->pluck('name', 'slug');

        return collect($users)
            ->map(function (array $user) use ($teamNames) {
                return [
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'team'  => $teamNames->get($user['team_slug'], 'Demo team'),
                ];
            })
            ->values()
            ->all();
    }
}
