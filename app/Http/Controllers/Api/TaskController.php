<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskTag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Task::with(['assignedUser', 'creator', 'tags'])
            ->where('team_id', $user->team_id)
            ->with(['assignedUser', 'tags']) 
            ->orderBy('position');

        if ($assigned = $request->input('assigned_to')) {
            $query->where('assigned_to', $assigned);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'tasks'   => $tasks,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user(); // resolved by ApiKeyAuth

        \Log::info('TaskController@store called', [
            'user_id' => optional($user)->id,
            'user_team_id' => $user->team_id ?? null,
        ]);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'in:todo,doing,done'], // we may want to sync this with in_progress later
            'priority'    => ['required', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'team_id'     => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        // Prefer explicit team_id from request, otherwise fall back to user's team
        $teamId = $validated['team_id'] ?? null;

        if (! $teamId) {
            if (! empty($user->current_team_id)) {
                $teamId = $user->current_team_id;
            } elseif (! empty($user->team_id)) {
                $teamId = $user->team_id;
            } else {
                \Log::warning('TaskController@store: no team resolved', [
                    'user_id' => optional($user)->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No team could be resolved for this user. Please create or select a team first.',
                ], 422);
            }
        }

        $task = Task::create([
            'team_id'      => $teamId,
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'status'       => $validated['status'],
            'priority'     => $validated['priority'],
            'assigned_to'  => $validated['assigned_to'] ?? null,
            'created_by'   => $user->id,
            'position'     => 1,
            'completed_at' => null,
        ]);

        // load relations so UI gets a rich object
        $task->load(['assignedUser', 'creator', 'tags']);

        \Log::info('TaskController@store: task created', [
            'task_id' => $task->id,
            'team_id' => $task->team_id,
        ]);

        return response()->json([
            'success' => true,
            'task'    => $task,
        ], 201);
    }

    public function update(Request $request, Task $task)
    {
        $user = $request->user();
    
        abort_unless($task->team_id === $user->team_id, 403);
    
        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority'    => ['sometimes', 'in:low,medium,high'],
            'status'      => ['sometimes', 'in:todo,in_progress,done'],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'position'    => ['sometimes', 'integer'],
            'tags'        => ['sometimes', 'array'],
            'tags.*.name' => ['required_with:tags', 'string', 'max:50'],
            'tags.*.color'=> ['nullable', 'string', 'max:20'],
        ]);
    
        $originalStatus = $task->status;
    
        // Remove tags from the fill data so we do not treat them as a normal attribute
        $tagsPayload = array_key_exists('tags', $data) ? $data['tags'] : null;
        unset($data['tags']);
    
        // Fill scalar fields
        if (!empty($data)) {
            $task->fill($data);
        }
    
        // Handle completed_at when status changes
        if (array_key_exists('status', $data)) {
            if ($data['status'] === 'done' && !$task->completed_at) {
                $task->completed_at = now();
            } elseif ($data['status'] !== 'done') {
                $task->completed_at = null;
            }
        }
    
        $task->save();
    
        // If tags were sent, overwrite all tags for this task
        if ($tagsPayload !== null) {
            $task->tags()->delete();
    
            foreach ($tagsPayload as $tag) {
                if (!isset($tag['name']) || $tag['name'] === '') {
                    continue;
                }
    
                $task->tags()->create([
                    'name'  => $tag['name'],
                    'color' => $tag['color'] ?? '#0ea5e9',
                ]);
            }
        }
    
        // Make sure we return the task with all relations, including tags
        $task->load(['assignedUser', 'creator', 'tags']);
    
        return response()->json([
            'success'           => true,
            'task'              => $task,
            'status_changed_to' => $originalStatus !== $task->status
                ? $task->status
                : null,
        ]);
    }
    


    public function move(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot move tasks from another team.',
            ], 200);
        }

        $data = $request->validate([
            'status'   => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $oldStatus = $task->status;

        $task->status = $data['status'];

        // Shift positions in new column
        Task::where('team_id', $task->team_id)
            ->where('status', $data['status'])
            ->where('id', '!=', $task->id)
            ->where('position', '>=', $data['position'])
            ->increment('position');

        $task->position = $data['position'];

        if ($data['status'] === 'done' && !$task->completed_at) {
            $task->completed_at = Carbon::now();
        }
        if ($data['status'] !== 'done') {
            $task->completed_at = null;
        }

        $task->save();

        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success'           => true,
            'message'           => 'Task moved successfully',
            'task'              => $task,
            'status_changed_to' => $task->status !== $oldStatus ? $task->status : null,
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot delete tasks from another team.',
            ], 200);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted with a smooth goodbye.',
        ]);
    }

    public function assign(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot assign tasks from another team.',
            ], 200);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (!empty($data['user_id'])) {
            $assignee = User::where('team_id', $task->team_id)
                ->where('id', $data['user_id'])
                ->first();

            if (!$assignee) {
                return response()->json([
                    'success' => false,
                    'error'   => 'User must belong to the same team.',
                ], 200);
            }

            $task->assigned_to = $assignee->id;
        } else {
            // Unassign
            $task->assigned_to = null;
        }

        $task->save();
        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success' => true,
            'message' => 'Task assignment updated.',
            'task'    => $task,
        ]);
    }
}
