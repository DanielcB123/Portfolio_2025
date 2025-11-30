<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\IncidentFollowUp;
use Carbon\Carbon;

class IncidentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $incidents = [
            [
                'base' => [
                    'key'              => 'IC-2471',
                    'title'            => 'Checkout failures for credit card payments',
                    'severity'         => 'SEV1',
                    'status'           => 'investigating',
                    'system'           => 'Payments',
                    'impacted_regions' => ['US-East', 'US-West'],
                    'impacted_users'   => '~32 percent of active sessions',
                    'owner'            => 'On call payments',
                    'summary'          => 'Elevated failure rate on credit card charges. PayPal and ACH remain healthy.',
                    'tags'             => ['payments', 'checkout', 'stripe-gateway', 'revenue-impact'],
                    'started_at'       => $now->copy()->subMinutes(45),
                    'last_updated_at'  => $now->copy()->subMinutes(5),
                ],
                'events' => [
                    [
                        'offset' => -45,
                        'type'   => 'detected',
                        'actor'  => 'Alert: payments_error_rate',
                        'label'  => 'Alert fired for spike in 5xx from payment service',
                        'detail' => 'Error rate went from 0.3 percent to 7.8 percent over 5 minutes.',
                    ],
                    [
                        'offset' => -40,
                        'type'   => 'triage',
                        'actor'  => 'On call payments',
                        'label'  => 'Initial triage and dashboard review',
                        'detail' => 'Confirmed spike in credit card failures. Other payment methods look healthy.',
                    ],
                    [
                        'offset' => -25,
                        'type'   => 'mitigation',
                        'actor'  => 'Payments engineer',
                        'label'  => 'Traffic shifted away from degraded gateway',
                        'detail' => 'Routing 80 percent of traffic to secondary processor while investigating primary.',
                    ],
                ],
                'followUps' => [
                    [
                        'owner'  => 'Payments team',
                        'label'  => 'Add synthetic monitoring for credit card only path',
                        'status' => 'open',
                    ],
                    [
                        'owner'  => 'Data team',
                        'label'  => 'Quantify revenue impact and add dashboard view',
                        'status' => 'open',
                    ],
                ],
            ],

            [
                'base' => [
                    'key'              => 'IC-2472',
                    'title'            => 'Delayed incident timeline updates',
                    'severity'         => 'SEV3',
                    'status'           => 'monitoring',
                    'system'           => 'Incident Command Center',
                    'impacted_regions' => ['US-East'],
                    'impacted_users'   => 'On call and commanders',
                    'owner'            => 'Platform team',
                    'summary'          => 'Incident event stream was lagging behind by 3 to 5 minutes for some users.',
                    'tags'             => ['incidents', 'realtime', 'websockets'],
                    'started_at'       => $now->copy()->subHours(3),
                    'last_updated_at'  => $now->copy()->subHours(1),
                ],
                'events' => [
                    [
                        'offset' => -180,
                        'type'   => 'detected',
                        'actor'  => 'Synthetic monitor',
                        'label'  => 'Timeline updates delayed by more than 3 minutes',
                        'detail' => 'Websocket clients still connected but not receiving fresh events.',
                    ],
                    [
                        'offset' => -170,
                        'type'   => 'triage',
                        'actor'  => 'Platform on call',
                        'label'  => 'Rolled logs for websocket fanout service',
                        'detail' => 'Found backlog building on one node after deployment.',
                    ],
                    [
                        'offset' => -160,
                        'type'   => 'mitigation',
                        'actor'  => 'Platform on call',
                        'label'  => 'Drained traffic from unhealthy node',
                        'detail' => 'Restarted pod and drained connections while monitoring error rate.',
                    ],
                    [
                        'offset' => -70,
                        'type'   => 'communication',
                        'actor'  => 'Comms lead',
                        'label'  => 'Posted internal status update',
                        'detail' => 'Explained partial impact and expected recovery time to responders.',
                    ],
                ],
                'followUps' => [
                    [
                        'owner'  => 'Platform team',
                        'label'  => 'Add alert on websocket backlog depth',
                        'status' => 'in_progress',
                    ],
                ],
            ],

            [
                'base' => [
                    'key'              => 'IC-2473',
                    'title'            => 'Login latency spike for EU users',
                    'severity'         => 'SEV2',
                    'status'           => 'resolved',
                    'system'           => 'Auth',
                    'impacted_regions' => ['EU-Central', 'EU-West'],
                    'impacted_users'   => 'Up to 18 percent of login attempts in EU',
                    'owner'            => 'Auth team',
                    'summary'          => 'Increased login latency due to misconfigured rate limiting on EU edge.',
                    'tags'             => ['auth', 'latency', 'eu-region'],
                    'started_at'       => $now->copy()->subHours(7),
                    'last_updated_at'  => $now->copy()->subHours(5),
                ],
                'events' => [
                    [
                        'offset' => -420,
                        'type'   => 'detected',
                        'actor'  => 'Alert: login_p95_latency',
                        'label'  => 'Login p95 latency above 2.5 seconds in EU',
                        'detail' => 'Correlated with rollout of updated rate limiting policy.',
                    ],
                    [
                        'offset' => -400,
                        'type'   => 'triage',
                        'actor'  => 'Auth on call',
                        'label'  => 'Identified EU edge node as shared factor',
                        'detail' => 'US and APAC not impacted. EU-only config change suspected.',
                    ],
                    [
                        'offset' => -390,
                        'type'   => 'mitigation',
                        'actor'  => 'Auth engineer',
                        'label'  => 'Rolled back rate limit policy',
                        'detail' => 'Reverted to previous configuration, latency trending down.',
                    ],
                    [
                        'offset' => -360,
                        'type'   => 'resolved',
                        'actor'  => 'Incident commander',
                        'label'  => 'Declared incident resolved',
                        'detail' => 'p95 latency back within normal range for 30 minutes.',
                    ],
                ],
                'followUps' => [
                    [
                        'owner'  => 'Auth team',
                        'label'  => 'Add safe rollout guardrails for rate limits',
                        'status' => 'open',
                    ],
                    [
                        'owner'  => 'SRE',
                        'label'  => 'Codify regional config checks in preflight',
                        'status' => 'open',
                    ],
                ],
            ],
        ];

        foreach ($incidents as $data) {
            $incident = Incident::updateOrCreate(
                ['key' => $data['base']['key']],
                $data['base']
            );

            // Events
            foreach ($data['events'] as $index => $event) {
                IncidentEvent::create([
                    'incident_id' => $incident->id,
                    'occurred_at' => $now->copy()->addMinutes($event['offset']),
                    'type'        => $event['type'],
                    'actor'       => $event['actor'],
                    'label'       => $event['label'],
                    'detail'      => $event['detail'],
                    'sort_order'  => $index + 1,
                ]);
            }

            // Follow ups
            foreach ($data['followUps'] as $fu) {
                IncidentFollowUp::create([
                    'incident_id' => $incident->id,
                    'owner'       => $fu['owner'],
                    'label'       => $fu['label'],
                    'status'      => $fu['status'],
                ]);
            }
        }
    }
}
