<?php

namespace App\Http\Controllers\Incident;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentFollowUp;
use Illuminate\Http\Request;

class IncidentFollowUpController extends Controller
{
    public function store(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'owner' => 'nullable|string|max:255',
        ]);

        $actor = optional($request->user())->name ?: 'System';

        // create follow up, default status is open
        $followUp = $incident->followUps()->create([
            'label'  => $data['label'],
            'owner'  => $data['owner'] ?? null,
            'status' => 'open',
        ]);

        // bump incident last_updated_at
        $incident->update([
            'last_updated_at' => now(),
        ]);

        // add timeline event
        $incident->events()->create([
            'occurred_at' => now(),
            'type'        => 'follow_up',
            'actor'       => $actor,
            'label'       => 'Follow up created: ' . $followUp->label,
            'detail'      => "New follow up created by {$actor}.",
        ]);

        return redirect()
            ->route('incident.dashboard')
            ->with('status', 'Follow up created.');
    }

    public function update(Request $request, IncidentFollowUp $followUp)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,done',
        ]);

        $oldStatus = $followUp->status;
        $newStatus = $data['status'];
        $actor     = optional($request->user())->name ?: 'System';

        $followUp->update([
            'status' => $newStatus,
        ]);

        if ($followUp->incident) {
            $incident = $followUp->incident;

            $incident->update([
                'last_updated_at' => now(),
            ]);

            $incident->events()->create([
                'occurred_at' => now(),
                'type'        => 'follow_up',
                'actor'       => $actor,
                'label'       => 'Follow up updated: ' . $followUp->label,
                'detail'      => "Follow up status changed from {$oldStatus} to {$newStatus} by {$actor}.",
            ]);
        }

        return redirect()
            ->route('incident.dashboard')
            ->with('status', 'Follow up updated.');
    }
}
