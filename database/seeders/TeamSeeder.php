<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'slug'  => 'mediahaus-squad',
                'name'  => 'MediaHaus Squad',
                'color' => '#2563eb',
            ],
            [
                'slug'  => 'design-team',
                'name'  => 'Design Team',
                'color' => '#ec4899',
            ],
            [
                'slug'  => 'sre-guild',
                'name'  => 'SRE Guild',
                'color' => '#22c55e',
            ],
            [
                'slug'  => 'incident-command',
                'name'  => 'Incident Command',
                'color' => '#0ea5e9',
            ],
        ];

        foreach ($teams as $data) {
            Team::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'color' => $data['color']]
            );
        }
    }
}
