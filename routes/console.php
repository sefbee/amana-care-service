<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('placements:activate-due', function (\App\Admin\Services\CaregiverReadinessEvaluator $readiness, \App\Admin\Services\PlacementFollowUpScheduler $followUps): void {
    $dueIds = \App\Admin\Models\Placement::query()
        ->where('status', 'Scheduled')
        ->whereDate('start_date', '<=', today())
        ->orderBy('id')
        ->pluck('id');
    $activated = 0;
    $held = 0;

    foreach ($dueIds as $placementId) {
        \Illuminate\Support\Facades\DB::transaction(function () use ($placementId, $readiness, $followUps, &$activated, &$held): void {
            $snapshot = \App\Admin\Models\Placement::query()->find($placementId);
            if (! $snapshot) return;
            $caregiver = \App\Admin\Models\Caregiver::withTrashed()->lockForUpdate()->find($snapshot->caregiver_id);
            $placement = \App\Admin\Models\Placement::query()->lockForUpdate()->find($placementId);
            if (! $caregiver || ! $placement || $placement->status !== 'Scheduled' || $placement->start_date->gt(today())) return;
            if ($caregiver->trashed()) {
                $held++;
                activity('placement')->performedOn($placement)
                    ->withProperties(['caregiver_id' => $caregiver->id, 'reason' => 'caregiver_inactive'])
                    ->log('Due placement remained Scheduled because caregiver is inactive');
                return;
            }

            $mandatoryIds = \App\Admin\Models\TrainingModule::query()->where('is_active', true)->where('is_mandatory', true)->pluck('id');
            if (! $readiness->refresh($caregiver, $mandatoryIds)) {
                $held++;
                activity('placement')->performedOn($placement)
                    ->withProperties(['caregiver_id' => $caregiver->id, 'reason' => 'readiness_not_current'])
                    ->log('Due placement remained Scheduled because caregiver readiness was not current');
                return;
            }

            $placement->forceFill(['status' => 'Active', 'activated_at' => now()])->save();
            $followUps->schedule($placement);
            \App\Admin\Models\PlacementEvent::query()->create([
                'placement_id' => $placement->id,
                'event_type' => 'ActivatedFromSchedule',
                'rationale' => 'Scheduled start date reached and caregiver readiness revalidated.',
                'occurred_at' => now(),
            ]);
            activity('placement')->performedOn($placement)
                ->withProperties(['caregiver_id' => $caregiver->id, 'start_date' => $placement->start_date->toDateString()])
                ->log('Scheduled placement activated after readiness revalidation');
            $activated++;
        });
    }

    $this->info("Activated {$activated} due placements; {$held} remain scheduled pending readiness.");
})->purpose('Activate due placements only after current caregiver readiness is revalidated');

Schedule::command('placements:activate-due')
    ->dailyAt('00:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('caregivers:readiness-expiry', function (\App\Admin\Services\CaregiverReadinessExpiryService $service): void {
    $summary = $service->run();
    $this->info("Created {$summary['alerts_created']} readiness alerts; withdrew {$summary['proposals_withdrawn']} draft proposals for {$summary['expired_caregivers']} expired caregivers.");
})->purpose('Alert RS about police-check expiry and revoke readiness/proposals after expiry');

Schedule::command('caregivers:readiness-expiry')
    ->dailyAt('00:01')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('placements:schedule-follow-ups', function (\App\Admin\Services\PlacementFollowUpScheduler $scheduler): void {
    $created = 0;
    \App\Admin\Models\Placement::query()->where('status', 'Active')->orderBy('id')->chunkById(100, function ($placements) use ($scheduler, &$created): void {
        foreach ($placements as $placement) {
            $created += $scheduler->schedule($placement);
        }
    });

    $this->info("Created {$created} missing placement follow-up tasks.");
})->purpose('Ensure each active placement has Day 7, Day 30, and Day 60 quality follow-up tasks');

Schedule::command('placements:schedule-follow-ups')
    ->dailyAt('03:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('finance:generate-drafts', function (\App\Admin\Services\InvoiceDraftGenerator $generator): void {
    $summary = $generator->generateUpcoming();
    if (isset($summary['skipped']['billing_cycle_not_approved']) || isset($summary['skipped']['recurring_amount_basis_not_approved'])) {
        $this->warn('No invoices generated: billing-cycle and recurring quote-override policies must be approved and configured.');
        return;
    }

    $this->info("Created {$summary['created']} draft invoices; {$summary['existing']} already existed; ".array_sum($summary['skipped']).' placements skipped.');
    if ($summary['skipped'] !== []) {
        $this->line(json_encode($summary['skipped'], JSON_UNESCAPED_SLASHES));
    }
})->purpose('Generate idempotent draft invoices from active placement price snapshots when billing policy is configured');

Schedule::command('finance:generate-drafts')
    ->dailyAt('01:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('finance:mark-overdue', function (\App\Admin\Services\FinanceLedgerService $ledger): void {
    $count = $ledger->markOverdue();
    $this->info("Marked {$count} unpaid invoices overdue.");
})->purpose('Mark issued invoices overdue after their recorded due date');

Schedule::command('finance:mark-overdue')
    ->dailyAt('02:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('safeguarding:dispatch-alerts', function (\App\Admin\Services\SafeguardingAlertDispatcher $dispatcher): void {
    $result = $dispatcher->run();
    $this->info("Delivered {$result['delivered']} alerts; {$result['pending']} scheduled for retry; {$result['dead_lettered']} moved to dead letter; {$result['sla_breaches']} acknowledgement SLA breaches recorded.");
})->purpose('Deliver high-priority safeguarding alerts, retry failed channels, and record unacknowledged 15-minute SLA breaches');

Schedule::command('safeguarding:dispatch-alerts')
    ->everyMinute()
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Artisan::command('notifications:dispatch', function (\App\Admin\Services\OperationalNotificationDispatcher $dispatcher): void {
    $result = $dispatcher->run();
    $this->info("Delivered {$result['delivered']}; retrying {$result['pending']}; cancelled {$result['cancelled']}; dead-lettered {$result['dead_lettered']}; quiet-hour deferrals {$result['quiet_deferred']}; quiet-hour policy unconfigured {$result['quiet_policy_unconfigured']}.");
})->purpose('Deliver queued operational email/SMS notifications, enforce consent and quiet hours, and retry or dead-letter failures');

Schedule::command('notifications:dispatch')
    ->everyMinute()
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
