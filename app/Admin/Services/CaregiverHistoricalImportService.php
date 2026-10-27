<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaregiverHistoricalImportService
{
    private const MAX_ROWS = 500;

    public const HEADERS = [
        'name', 'dob', 'gender', 'phone', 'national_id', 'live_in_preferred', 'available_from',
        'zone_preferences', 'skills',
        'identity_outcome', 'identity_checked_at', 'identity_valid_until',
        'police_outcome', 'police_checked_at', 'police_valid_until',
        'psych_outcome', 'psych_checked_at', 'psych_valid_until',
    ];

    public function __construct(private CaregiverService $caregivers) {}

    /** @return array{imported:int, errors:array<int, string>} */
    public function import(UploadedFile $file, int $actorId): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be read.']);
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '\\');
            if (! is_array($headers)) {
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);
            if (count($headers) !== count(array_unique($headers)) || array_diff($headers, self::HEADERS) !== []
                || ! in_array('name', $headers, true) || ! in_array('phone', $headers, true) || ! in_array('national_id', $headers, true)) {
                throw ValidationException::withMessages(['file' => 'Use the supplied CSV template with unique, recognized column names.']);
            }

            $imported = 0;
            $errors = [];
            $rowNumber = 1;
            while (($values = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                $rowNumber++;
                if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                    continue;
                }
                if ($rowNumber > self::MAX_ROWS + 1) {
                    $errors[] = 'Import stopped: the file exceeds the 500-row limit.';
                    break;
                }
                if (count($values) !== count($headers)) {
                    $errors[] = "Row {$rowNumber}: column count does not match the template.";
                    continue;
                }

                $row = array_combine($headers, array_map(fn ($value) => trim((string) $value), $values));
                $row += array_fill_keys(self::HEADERS, '');
                $skills = $this->parseList($row['skills']);
                $zones = $this->parseList($row['zone_preferences']);
                $liveInInput = strtolower($row['live_in_preferred']);
                $liveIn = match ($liveInInput) {
                    '', '0', 'false', 'no' => 0,
                    '1', 'true', 'yes' => 1,
                    default => $row['live_in_preferred'],
                };
                $validated = Validator::make([
                    'name' => $row['name'],
                    'dob' => $row['dob'],
                    'gender' => $row['gender'] ?: null,
                    'phone' => $row['phone'],
                    'national_id' => $row['national_id'],
                    'live_in_preferred' => $liveIn,
                    'available_from' => $row['available_from'] ?: null,
                    'zone_preferences' => $zones,
                    'skills' => array_fill_keys($skills, true),
                ], [
                    'name' => ['required', 'string', 'max:150'],
                    'dob' => ['required', 'date', 'before:today'],
                    'gender' => ['nullable', 'string', 'max:30'],
                    'phone' => ['required', 'string', 'max:20'],
                    'national_id' => ['required', 'string', 'max:100'],
                    'live_in_preferred' => ['boolean'],
                    'available_from' => ['nullable', 'date'],
                    'zone_preferences' => ['array'],
                    'zone_preferences.*' => ['string', 'max:80'],
                    'skills' => ['array'],
                ]);

                if ($validated->fails()) {
                    $fields = array_keys($validated->errors()->messages());
                    $errors[] = "Row {$rowNumber}: invalid ".implode(', ', $fields).'.';
                    continue;
                }

                $normalized = $validated->validated();
                $checks = [];
                foreach (['identity' => 'Identity', 'police' => 'Police', 'psych' => 'Psych'] as $prefix => $type) {
                    if (empty($row["{$prefix}_outcome"]) && empty($row["{$prefix}_checked_at"]) && empty($row["{$prefix}_valid_until"])) {
                        continue;
                    }
                    $check = [
                        'type' => $type,
                        'outcome' => $row["{$prefix}_outcome"] ?: 'Pending',
                        'checked_at' => $row["{$prefix}_checked_at"] ?: null,
                        'valid_until' => $row["{$prefix}_valid_until"] ?: null,
                        'verification_status' => 'Needs Verification',
                        'record_source' => 'Historical Import',
                    ];
                    $checkValidation = Validator::make($check, [
                        'outcome' => [Rule::in(['Pass', 'Fail', 'Adverse', 'Pending'])],
                        'checked_at' => ['nullable', 'date', 'before_or_equal:today'],
                        'valid_until' => ['nullable', 'date'],
                    ]);
                    if ($checkValidation->fails()) {
                        $fields = array_keys($checkValidation->errors()->messages());
                        $errors[] = "Row {$rowNumber}: invalid {$type} check ".implode(', ', $fields).'.';
                        continue 2;
                    }
                    $checks[] = $check;
                }

                try {
                    DB::transaction(function () use ($normalized, $checks, $actorId): void {
                        $caregiver = $this->caregivers->create(
                            $normalized,
                            $normalized['skills'],
                            $actorId,
                            'Historical Import',
                        );
                        foreach ($checks as $check) {
                            $caregiver->screeningChecks()->create($check);
                        }
                    });
                    $imported++;
                } catch (ValidationException $exception) {
                    $errors[] = "Row {$rowNumber}: rejected under duplicate or data-integrity rules (".implode(', ', array_keys($exception->errors())).').';
                } catch (\Throwable) {
                    $errors[] = "Row {$rowNumber}: could not be imported because of a data-integrity error.";
                }
            }

            activity('caregiver_recruitment')->causedBy($actorId)->withProperties([
                'imported_count' => $imported,
                'error_count' => count($errors),
            ])->log('Historical caregiver import processed');

            return ['imported' => $imported, 'errors' => $errors];
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int, string> */
    private function parseList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode('|', $value)))));
    }
}
