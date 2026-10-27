<?php

namespace App\Admin\Services;

use App\Admin\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportWatermark
{
    public function apply(Spreadsheet $spreadsheet, Request $request, User $user, string $reportId, string $classification): void
    {
        $exportedAt = now()->utc()->toIso8601String();
        $ip = (string) $request->ip();

        $spreadsheet->getProperties()
            ->setCreator('Amanah Operations')
            ->setDescription("{$classification}. Exporter user ID: {$user->getKey()}; exported at: {$exportedAt}; source IP: {$ip}; report ID: {$reportId}.")
            ->setCustomProperty('Exporter User ID', (string) $user->getKey())
            ->setCustomProperty('Exported At UTC', $exportedAt)
            ->setCustomProperty('Source IP', $ip)
            ->setCustomProperty('Report ID', $reportId)
            ->setCustomProperty('Classification', $classification);

        $audit = $spreadsheet->createSheet();
        $audit->setTitle('Export Audit');
        $audit->fromArray([
            ['Field', 'Value'],
            ['Exporter User ID', (string) $user->getKey()],
            ['Exported At UTC', $exportedAt],
            ['Source IP', $ip],
            ['Report ID', $reportId],
            ['Classification', $classification],
        ], null, 'A1');
        $audit->getStyle('A1:B1')->getFont()->setBold(true);
        $audit->getColumnDimension('A')->setAutoSize(true);
        $audit->getColumnDimension('B')->setAutoSize(true);
        $audit->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }
}
