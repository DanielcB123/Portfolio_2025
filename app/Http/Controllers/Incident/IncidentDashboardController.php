<?php

namespace App\Http\Controllers\Incident;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\IncidentEvent;

class IncidentDashboardController extends Controller
{
    public function index()
    {
        $incidents = Incident::query()
            ->with(['events', 'followUps'])
            ->orderByDesc('started_at')
            ->limit(50)
            ->get()
            ->map(function (Incident $incident) {
                return [
                    'id'             => $incident->id,
                    'key'            => $incident->key,
                    'title'          => $incident->title,
                    'severity'       => $incident->severity,
                    'status'         => $incident->status,
                    'system'         => $incident->system,
                    'startedAt'      => optional($incident->started_at)->toIso8601String(),
                    'lastUpdatedAt'  => optional($incident->last_updated_at)->toIso8601String(),
                    'impactedRegions'=> $incident->impacted_regions ?? [],
                    'impactedUsers'  => $incident->impacted_users,
                    'owner'          => $incident->owner,
                    'summary'        => $incident->summary,
                    'tags'           => $incident->tags ?? [],

                    'timeline' => $incident->events
                        ->sortBy('occurred_at')
                        ->map(function ($event) {
                            return [
                                'id'     => $event->id,
                                'at'     => optional($event->occurred_at)->toIso8601String(),
                                'type'   => $event->type,
                                'actor'  => $event->actor,
                                'label'  => $event->label,
                                'detail' => $event->detail,
                            ];
                        })
                        ->values()
                        ->all(),

                    'followUps' => $incident->followUps
                        ->map(function ($f) {
                            return [
                                'id'     => $f->id,
                                'owner'  => $f->owner,
                                'label'  => $f->label,
                                'status' => $f->status,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            });

        return Inertia::render('Incident/Dashboard', [
            'incidents' => $incidents,
        ]);
    }

    public function update(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'status'       => 'required|in:investigating,mitigating,monitoring,resolved',
            'status_actor' => 'nullable|string|max:255',
            'status_note'  => 'nullable|string',
        ]);

        $oldStatus = $incident->status;
        $newStatus = $data['status'];

        // Prefer explicit actor from modal, then logged in user, then System
        $actor = $data['status_actor']
            ?? optional($request->user())->name
            ?? 'System';

        // Optional note coming from the modal
        $note = $data['status_note'] ?? null;

        // Update main incident record
        $incident->update([
            'status'          => $newStatus,
            'last_updated_at' => now(),
        ]);

        // Timeline event that will show up in your Vue timeline
        $incident->events()->create([
            'occurred_at' => now(),
            'type'        => $this->statusToEventType($newStatus),
            'actor'       => $actor,
            'label'       => 'Status changed to ' . ucfirst($newStatus),
            'detail'      => $note ?: "Status changed from {$oldStatus} to {$newStatus} by {$actor}.",
        ]);

        return redirect()
            ->route('incident.dashboard')
            ->with('status', 'Incident updated');
    }


    /**
     * Map incident status to a timeline event type (used for colors).
     */
    protected function statusToEventType(string $status): string
    {
        return match ($status) {
            'investigating' => 'triage',
            'mitigating'    => 'mitigation',
            'monitoring'    => 'monitoring',
            'resolved'      => 'resolved',
            default         => 'update',
        };
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'system'         => 'required|string|max:255',
            'severity'       => 'required|in:SEV1,SEV2,SEV3',
            'summary'        => 'nullable|string',
            'impacted_users' => 'nullable|string|max:255', // you can make this int if your column is int
            'impacted_regions' => 'nullable|array',
            'tags'             => 'nullable|array',
        ]);

        $actor = optional($request->user())->name ?: 'System';

        // Generate a simple key, tweak however you like
        $key = 'INC-' . now()->format('Ymd-His');

        $incident = Incident::create([
            'key'             => $key,
            'title'           => $data['title'],
            'severity'        => $data['severity'],
            'status'          => 'investigating',
            'system'          => $data['system'],
            'started_at'      => now(),
            'last_updated_at' => now(),
            'impacted_regions'=> $data['impacted_regions'] ?? [],
            'impacted_users'  => $data['impacted_users'] ?? null,
            'owner'           => $actor,
            'summary'         => $data['summary'] ?? '',
            'tags'            => $data['tags'] ?? [],
        ]);

        // Initial timeline event: detected / reported
        $incident->events()->create([
            'occurred_at' => now(),
            'type'        => 'detected',
            'actor'       => $actor,
            'label'       => 'Incident opened',
            'detail'      => "Incident created and set to investigating by {$actor}.",
        ]);

        return redirect()
            ->route('incident.dashboard')
            ->with('status', 'Incident created.');
    }
}
