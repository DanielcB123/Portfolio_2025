<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = config('demo_users.users', []);
        $teamsBySlug = Team::query()
            ->whereIn('slug', collect($users)->pluck('team_slug')->unique())
            ->get()
            ->keyBy('slug');

        foreach ($users as $user) {
            $team = $teamsBySlug->get($user['team_slug']);

            if (! $team) {
                throw new RuntimeException(
                    "Demo user {$user['email']} references unknown team slug [{$user['team_slug']}]. Run TeamSeeder first."
                );
            }

            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'         => $user['name'],
                    'password'     => Hash::make(config('demo_users.password')),
                    'team_id'      => $team->id,
                    'avatar_color' => $user['avatar_color'],
                ]
            );
        }
    }
}
