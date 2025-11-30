<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\TaskTag;

class TaskTagSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::with('team')->get();

        if ($tasks->isEmpty()) {
            $this->command?->warn('No tasks found. Run TaskSeeder before TaskTagSeeder.');
            return;
        }

        foreach ($tasks as $task) {
            // Avoid double seeding
            if ($task->tags()->count() > 0) {
                continue;
            }

            $tags = [];

            // Basic classification by status
            switch ($task->status) {
                case 'todo':
                    $tags[] = ['name' => 'Backlog', 'color' => '#64748b'];
                    break;

                case 'in_progress':
                    $tags[] = ['name' => 'In flight', 'color' => '#0ea5e9'];
                    break;

                case 'done':
                    $tags[] = ['name' => 'Shipped', 'color' => '#22c55e'];
                    break;
            }

            // Extra tag for priority
            if ($task->priority === 'high') {
                $tags[] = ['name' => 'High impact', 'color' => '#ef4444'];
            } elseif ($task->priority === 'low') {
                $tags[] = ['name' => 'Nice to have', 'color' => '#a855f7'];
            }

            // Optional team flavored tag
            if ($task->team && $task->team->slug === 'design-team') {
                $tags[] = ['name' => 'Design', 'color' => '#ec4899'];
            } elseif ($task->team && $task->team->slug === 'mediahaus-squad') {
                $tags[] = ['name' => 'MediaHaus', 'color' => '#2563eb'];
            }

            foreach ($tags as $tagData) {
                TaskTag::create([
                    'task_id' => $task->id,
                    'name'    => $tagData['name'],
                    'color'   => $tagData['color'],
                ]);
            }
        }
    }
}
