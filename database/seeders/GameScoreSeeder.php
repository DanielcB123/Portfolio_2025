<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameScore;

class GameScoreSeeder extends Seeder
{
    public function run(): void
    {
        $scores = [
            // Orbital Dodge - your main game
            [
                'game_key' => 'orbital_dodge',
                'name'     => 'Nova',
                'score'    => 4200,
            ],
            [
                'game_key' => 'orbital_dodge',
                'name'     => 'Comet',
                'score'    => 3650,
            ],
            [
                'game_key' => 'orbital_dodge',
                'name'     => 'Pulsar',
                'score'    => 3100,
            ],
            [
                'game_key' => 'orbital_dodge',
                'name'     => 'Meteor',
                'score'    => 2750,
            ],
            [
                'game_key' => 'orbital_dodge',
                'name'     => 'Echo',
                'score'    => 2300,
            ],

            [
                'game_key' => 'incident_sim',
                'name'     => 'OnCallOps',
                'score'    => 1800,
            ],
            [
                'game_key' => 'incident_sim',
                'name'     => 'PagerDutyHero',
                'score'    => 1550,
            ],
        ];

        foreach ($scores as $data) {
            GameScore::updateOrCreate(
                [
                    'game_key' => $data['game_key'],
                    'name'     => $data['name'],
                ],
                ['score' => $data['score']]
            );
        }
    }
}
