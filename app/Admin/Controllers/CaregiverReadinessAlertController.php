<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\CaregiverReadinessAlert;
use App\Admin\Models\Caregiver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CaregiverReadinessAlertController extends Controller
{
    public function index(Request $request, CrmRecordAccess $access)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['rs', 'om', 'platform-superuser'], true), 403);
        $visibleCaregivers = $access->caregivers(Caregiver::query(), $request->user())->select('caregivers.id');
        $query = CaregiverReadinessAlert::query()->whereIn('caregiver_id', $visibleCaregivers)->with(['caregiver', 'check'])->latest();

        if ($request->wantsJson()) {
            activity('caregiver_readiness_alert')->causedBy($request->user())
                ->withProperties(['alert_ids' => (clone $query)->pluck('id')->all()])
                ->log('Caregiver readiness alert records accessed');
            return DataTables::of($query)
                ->addColumn('candidate', fn ($row) => e($row->caregiver?->name ?? 'Caregiver record unavailable'))
                ->addColumn('check_type', fn ($row) => e($row->check?->type ?? 'Police'))
                ->editColumn('valid_until', fn ($row) => $row->valid_until->format('d M Y'))
                ->editColumn('alert_type', fn ($row) => $row->alert_type === 'Expired' ? '<span class="badge badge-light-danger">Expired</span>' : '<span class="badge badge-light-warning">Expires within 30 days</span>')
                ->addColumn('status', fn ($row) => $row->read_at ? 'Acknowledged' : 'Unread')
                ->addColumn('action', fn ($row) => $row->read_at ? '—' : '<form method="POST" action="'.e(route('caregiver-readiness-alerts.read', $row)).'"><input type="hidden" name="_token" value="'.e(csrf_token()).'"><button class="btn btn-sm btn-light-primary">Acknowledge</button></form>')
                ->rawColumns(['alert_type', 'action'])
                ->make(true);
        }

        activity('caregiver_readiness_alert')->causedBy($request->user())
            ->withProperties(['unread_count' => (clone $query)->whereNull('read_at')->count()])
            ->log('Caregiver readiness alert queue viewed');

        return view('admin.workforce.caregivers.alerts', ['breadcrumb' => [
            ['title' => 'Caregiver Recruitment', 'url' => route('caregivers.index')],
            ['title' => 'Readiness Alerts'],
        ]]);
    }

    public function acknowledge(Request $request, CaregiverReadinessAlert $alert, CrmRecordAccess $access)
    {
        $role = $access->roleSlug($request->user());
        abort_unless(in_array($role, ['rs', 'om', 'platform-superuser'], true), 403);

        DB::transaction(function () use ($request, $alert, $access): void {
            $locked = CaregiverReadinessAlert::query()->lockForUpdate()->findOrFail($alert->id);
            $caregiver = Caregiver::query()->findOrFail($locked->caregiver_id);
            $access->assertCaregiver($request->user(), $caregiver);
            abort_if($locked->read_at, 422, 'This alert has already been acknowledged.');
            $locked->forceFill(['read_at' => now(), 'read_by' => $request->user()->id])->save();
            activity('caregiver_readiness_alert')->causedBy($request->user())->performedOn($locked)
                ->withProperties(['caregiver_id' => $locked->caregiver_id, 'alert_type' => $locked->alert_type])
                ->log('Caregiver readiness alert acknowledged');
        });

        return redirect()->route('caregiver-readiness-alerts.index')->with('success', 'Alert acknowledged.');
    }
}
