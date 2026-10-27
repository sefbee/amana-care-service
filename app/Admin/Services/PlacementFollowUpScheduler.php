<?php

namespace App\Admin\Services;

use App\Admin\Models\Placement;
use App\Admin\Models\PlacementFollowUpTask;
use Illuminate\Support\Carbon;

class PlacementFollowUpScheduler
{
    public function schedule(Placement $placement): int
    {
        if ($placement->status !== 'Active') {
            return 0;
        }

        $activatedAt = $placement->activated_at ?? now();
        $created = 0;

        foreach ([7, 30, 60] as $milestoneDay) {
            $task = PlacementFollowUpTask::query()->firstOrCreate(
                ['placement_id' => $placement->id, 'milestone_day' => $milestoneDay],
                ['due_at' => Carbon::parse($activatedAt)->addDays($milestoneDay), 'status' => 'Open']
            );
            $created += (int) $task->wasRecentlyCreated;
        }

        return $created;
    }
}
