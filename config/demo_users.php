<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared demo password
    |--------------------------------------------------------------------------
    |
    | All seeded portfolio demo accounts share this password so visitors can
    | explore TaskFlow and Incident Command without creating an account.
    |
    */

    'password' => 'password',

    /*
    |--------------------------------------------------------------------------
    | Demo users
    |--------------------------------------------------------------------------
    |
    | Teams are seeded by TeamSeeder. Each user references an existing slug.
    |
    */

    'users' => [
        [
            'name'         => 'Media User 1',
            'email'        => 'media1@example.com',
            'team_slug'    => 'mediahaus-squad',
            'avatar_color' => '#0ea5e9',
        ],
        [
            'name'         => 'Media User 2',
            'email'        => 'media2@example.com',
            'team_slug'    => 'mediahaus-squad',
            'avatar_color' => '#22c55e',
        ],
        [
            'name'         => 'Media User 3',
            'email'        => 'media3@example.com',
            'team_slug'    => 'mediahaus-squad',
            'avatar_color' => '#f59e0b',
        ],
        [
            'name'         => 'Design User 1',
            'email'        => 'design1@example.com',
            'team_slug'    => 'design-team',
            'avatar_color' => '#ec4899',
        ],
        [
            'name'         => 'Design User 2',
            'email'        => 'design2@example.com',
            'team_slug'    => 'design-team',
            'avatar_color' => '#a855f7',
        ],
    ],

];
