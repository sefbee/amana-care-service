<?php

namespace App\Admin\Controllers;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\User;
use App\Admin\Services\ExportWatermark;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveReportController
{
    public function index(Request $request, CrmRecordAccess $access)
    {
        [$start, $end] = $this->period($request);
        $this->authorizeAndAudit($request, $access, 'view', $start, $end);

        return view('admin.reports.executive', [
            'breadcrumb' => [
                ['title' => 'Executive Reports', 'url' => route('executive.reports.index')],
            ],
            'startMonth' => $start->format('Y-m'),
            'endMonth' => $end->format('Y-m'),
            'months' => $this->aggregate($start, $end),
        ]);
    }

    public function export(Request $request, CrmRecordAccess $access, ExportWatermark $watermark): StreamedResponse
    {
        $reportId = (string) Str::uuid();
        [$start, $end] = $this->period($request);
        $this->authorizeAndAudit($request, $access, 'export', $start, $end, $reportId);
        $rows = $this->aggregate($start, $end);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Anonymized Executive and Auditor Report')
            ->setSubject('Aggregated operational and reconciled finance metrics')
            ->setKeywords('Amanah, anonymized, aggregated, executive report');

        $data = $spreadsheet->getActiveSheet();
        $data->setTitle('Aggregate Report');
        $data->fromArray([
            ['Month', 'Leads Created', 'Households Created', 'Placements Activated', 'Invoices Issued', 'Invoice Total (BDT)', 'Reconciled Payments', 'Reconciled Amount (BDT)'],
        ], null, 'A1');
        $rowNumber = 2;
        foreach ($rows as $row) {
            $data->fromArray([[
                $row['month'], $row['leads'], $row['households'], $row['placements'],
                $row['invoices'], $row['invoice_total'], $row['payments'], $row['payment_total'],
            ]], null, 'A'.$rowNumber++);
        }
        $data->fromArray([['TOTAL', '='.$this->sumFormula('B', $rowNumber), '='.$this->sumFormula('C', $rowNumber), '='.$this->sumFormula('D', $rowNumber), '='.$this->sumFormula('E', $rowNumber), '='.$this->sumFormula('F', $rowNumber), '='.$this->sumFormula('G', $rowNumber), '='.$this->sumFormula('H', $rowNumber)]], null, 'A'.$rowNumber);
        $data->freezePane('A2');
        $data->getStyle('A1:H1')->getFont()->setBold(true);
        $data->getStyle('A'.$rowNumber.':H'.$rowNumber)->getFont()->setBold(true);
        foreach (range('A', 'H') as $column) {
            $data->getColumnDimension($column)->setAutoSize(true);
        }
        $data->getStyle('F2:F'.$rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');
        $data->getStyle('H2:H'.$rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');

        $watermark->apply($spreadsheet, $request, $request->user(), $reportId, 'Anonymized aggregate report');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'amanah-executive-report-'.$start->format('Y-m').'-to-'.$end->format('Y-m').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function period(Request $request): array
    {
        $validated = $request->validate([
            'start_month' => ['nullable', 'date_format:Y-m'],
            'end_month' => ['nullable', 'date_format:Y-m'],
        ]);

        $end = isset($validated['end_month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['end_month'])->endOfMonth()
            : CarbonImmutable::now()->endOfMonth();
        $start = isset($validated['start_month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['start_month'])->startOfMonth()
            : $end->startOfMonth()->subMonths(11);

        if ($start->greaterThan($end) || $start->diffInMonths($end) > 23) {
            abort(422, 'Choose a valid reporting period of at most 24 months.');
        }

        return [$start, $end];
    }

    private function authorizeAndAudit(Request $request, CrmRecordAccess $access, string $action, CarbonImmutable $start, CarbonImmutable $end, ?string $reportId = null): void
    {
        /** @var User $user */
        $user = $request->user();
        $role = $access->roleSlug($user);
        abort_unless(in_array($role, ['ex', 'platform-superuser'], true), 403);

        activity($user->isPlatformSuperuser() ? 'platform_superuser_break_glass' : 'executive_report_access')
            ->causedBy($user)
            ->withProperties([
                'action' => $action,
                'start_month' => $start->format('Y-m'),
                'end_month' => $end->format('Y-m'),
                'source_ip' => (string) $request->ip(),
                'report' => 'anonymized_aggregate',
                'report_id' => $reportId,
            ])
            ->log($action === 'export' ? 'Anonymized executive report export requested' : 'Anonymized executive report viewed');
    }

    /** @return array<int, array<string, int|string>> */
    private function aggregate(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $months = [];
        for ($cursor = $start->startOfMonth(); $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $months[$key] = [
                'month' => $cursor->format('M Y'), 'leads' => 0, 'households' => 0,
                'placements' => 0, 'invoices' => 0, 'invoice_total' => '0.00',
                'payments' => 0, 'payment_total' => '0.00',
            ];
        }

        $this->mergeAggregate($months, 'leads', 'created_at', 'leads', $start, $end);
        $this->mergeAggregate($months, 'households', 'created_at', 'households', $start, $end);
        $this->mergeAggregate($months, 'placements', 'activated_at', 'placements', $start, $end);
        $this->mergeAggregate($months, 'invoices', 'issued_at', 'finance_invoices', $start, $end, ['status' => ['Issued', 'Partially Paid', 'Paid', 'Overdue']], 'total', 'invoice_total');
        $this->mergeAggregate($months, 'payments', 'paid_at', 'finance_payments', $start, $end, ['status' => ['Reconciled']], 'amount', 'payment_total');

        return array_values($months);
    }

    private function mergeAggregate(array &$months, string $countKey, string $dateColumn, string $table, CarbonImmutable $start, CarbonImmutable $end, array $filters = [], ?string $amountColumn = null, ?string $amountKey = null): void
    {
        $query = DB::table($table)->whereBetween($dateColumn, [$start->startOfDay(), $end->endOfDay()]);
        if (in_array($table, ['leads', 'households'], true)) {
            $query->whereNull($table.'.deleted_at');
        }
        foreach ($filters as $column => $values) {
            $query->whereIn($column, $values);
        }
        $periodExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$dateColumn})",
            'pgsql' => "TO_CHAR({$dateColumn}, 'YYYY-MM')",
            default => "DATE_FORMAT({$dateColumn}, '%Y-%m')",
        };
        $select = "{$periodExpression} as period, COUNT(*) as aggregate_count";
        if ($amountColumn !== null) {
            $select .= ", SUM({$amountColumn}) as aggregate_amount";
        }
        $groups = $query->selectRaw($select)->groupByRaw($periodExpression)->get();

        foreach ($groups as $group) {
            if (! isset($months[$group->period])) {
                continue;
            }
            $months[$group->period][$countKey] = (int) $group->aggregate_count;
            if ($amountColumn !== null && $amountKey !== null) {
                $months[$group->period][$amountKey] = number_format((float) $group->aggregate_amount, 2, '.', '');
            }
        }
    }

    private function sumFormula(string $column, int $lastRow): string
    {
        return "SUM({$column}2:{$column}".max(2, $lastRow - 1).')';
    }
}
