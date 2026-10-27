<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

class AuditLogController extends Controller
{
    public function index(Request $request, CrmRecordAccess $access)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['sa', 'om', 'sl', 'platform-superuser'], true), 403);

        $query = Activity::query()->select([
            'id', 'log_name', 'description', 'subject_type', 'subject_id',
            'causer_type', 'causer_id', 'created_at',
        ])->latest('created_at');

        if ($role === 'sa') {
            $piiSubjects = [
                \App\Admin\Models\User::class,
                \App\Admin\Models\Lead::class,
                \App\Admin\Models\Household::class,
                \App\Admin\Models\HouseholdContact::class,
                \App\Admin\Models\CareRecipient::class,
                \App\Admin\Models\Caregiver::class,
                \App\Admin\Models\CaregiverDocument::class,
                \App\Admin\Models\CaregiverScreeningCheck::class,
                \App\Admin\Models\CaregiverTrainingRecord::class,
                \App\Admin\Models\CaregiverReadinessApproval::class,
                \App\Admin\Models\Placement::class,
                \App\Admin\Models\SafeguardingIncident::class,
            ];
            $query->where(fn ($records) => $records->whereNull('subject_type')->orWhereNotIn('subject_type', $piiSubjects))
                ->where(fn ($records) => $records->whereNull('subject_type')->orWhereRaw('LOWER(subject_type) NOT LIKE ?', ['%safeguard%']))
                ->where(fn ($records) => $records->whereNull('log_name')->orWhereRaw('LOWER(log_name) NOT LIKE ?', ['%safeguard%']))
                ->where(fn ($records) => $records->whereNull('description')->orWhereRaw('LOWER(description) NOT LIKE ?', ['%safeguard%']));
        }

        activity('audit_access')->causedBy($request->user())
            ->withProperties(['role' => $role, 'view' => 'system_audit_logs'])
            ->log('System audit log viewed');

        if ($request->wantsJson()) {
            return DataTables::of($query)
                ->addColumn('actor', fn (Activity $activity) => $activity->causer_id ? 'Staff #'.$activity->causer_id : 'System')
                ->addColumn('target', fn (Activity $activity) => $this->targetLabel($activity, $role))
                ->editColumn('log_name', fn (Activity $activity) => $this->categoryLabel($activity, $role))
                ->editColumn('description', fn (Activity $activity) => $this->descriptionLabel($activity, $role))
                ->editColumn('created_at', fn (Activity $activity) => $activity->created_at?->utc()->format('Y-m-d H:i:s').' UTC')
                ->make(true);
        }

        return view('admin.identity.audit-logs.index');
    }

    private function targetLabel(Activity $activity, string $role): string
    {
        if ($this->isSafeguardingActivity($activity) && ! in_array($role, ['sl', 'platform-superuser'], true)) {
            return 'Restricted record';
        }

        $type = $activity->subject_type ? class_basename($activity->subject_type) : 'System';

        return $activity->subject_id ? $type.' #'.$activity->subject_id : $type;
    }

    private function categoryLabel(Activity $activity, string $role): string
    {
        if ($this->isSafeguardingActivity($activity) && ! in_array($role, ['sl', 'platform-superuser'], true)) {
            return 'Restricted activity';
        }

        return (string) ($activity->log_name ?: 'default');
    }

    private function descriptionLabel(Activity $activity, string $role): string
    {
        if ($this->isSafeguardingActivity($activity) && ! in_array($role, ['sl', 'platform-superuser'], true)) {
            return 'Restricted case audit event';
        }

        return (string) $activity->description;
    }

    private function isSafeguardingActivity(Activity $activity): bool
    {
        return str_contains(strtolower((string) $activity->subject_type), 'safeguarding')
            || str_contains(strtolower((string) $activity->log_name), 'safeguarding')
            || str_contains(strtolower((string) $activity->description), 'safeguarding');
    }
}
