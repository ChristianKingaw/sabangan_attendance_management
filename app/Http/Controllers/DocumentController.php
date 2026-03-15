<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use ZipArchive;

class DocumentController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function upload(Request $request)
    {
        if (!DB::getSchemaBuilder()->hasTable('file')) {
            return back()->withErrors(['xls_file' => 'Database table "file" does not exist.'])->withInput();
        }

        $request->validate([
            'xls_file' => ['required', 'file', 'mimes:xls'],
        ]);

        if (!class_exists(\ZipArchive::class)) {
            return back()->withErrors(['xls_file' => 'PHP Zip extension is required to generate DOCX files.'])->withInput();
        }

        $adminId = (int) $request->session()->get('admin_id', 0);
        if ($adminId <= 0) {
            return back()->withErrors(['xls_file' => 'Please log in again.'])->withInput();
        }

        $upload = $request->file('xls_file');
        if (strtolower($upload->getClientOriginalExtension()) !== 'xls') {
            return back()->withErrors(['xls_file' => 'Only .xls files are allowed.'])->withInput();
        }

        $spreadsheet = SpreadsheetIOFactory::load($upload->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $report = $this->extractReportData($sheet->toArray(null, true, true, false));

        if (count($report['employees']) === 0) {
            return back()->withErrors(['xls_file' => 'No employee records found in the XLS file.'])->withInput();
        }

        try {
            $templatePath = $this->ensureTemplate();
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['xls_file' => $exception->getMessage()])->withInput();
        }
        $documentsDir = storage_path('app' . DIRECTORY_SEPARATOR . 'attendance');
        if (!File::exists($documentsDir)) {
            File::makeDirectory($documentsDir, 0755, true);
        }

        $nextId = (int) (DB::table('file')->max('id') ?? 0);
        $generatedDocumentCount = 0;

        $periodDate = $this->extractPeriodDate($report['period']);
        $periodSlug = $this->buildPeriodSlug($report['period'], $periodDate);

        foreach ($report['employees'] as $employee) {
            $filename = $this->buildFilename($employee, $periodSlug);
            $relativePath = 'attendance/' . $filename;
            $fullPath = $documentsDir . DIRECTORY_SEPARATOR . $filename;

            try {
                $this->generateDocx($templatePath, $fullPath, $employee, $report['period']);
            } catch (\Throwable $exception) {
                return back()->withErrors([
                    'xls_file' => 'Failed to generate DOCX: ' . $exception->getMessage(),
                ])->withInput();
            }

            if (DB::getSchemaBuilder()->hasTable('file')) {
                $existingFile = DB::table('file')
                    ->where('adminID', $adminId)
                    ->where('filename', $filename)
                    ->first();

                if ($existingFile) {
                    DB::table('file')
                        ->where('id', $existingFile->id)
                        ->update([
                            'date' => (int) now()->format('Ymd'),
                            'path' => $relativePath,
                        ]);
                } else {
                    $nextId++;
                    DB::table('file')->insert([
                        'id' => $nextId,
                        'date' => (int) now()->format('Ymd'),
                        'adminID' => $adminId,
                        'filename' => $filename,
                        'path' => $relativePath,
                    ]);
                }
            }

            if (DB::getSchemaBuilder()->hasTable('attendance_records')) {
                $employeeId = $employee['id'] ?: null;
                $employeeName = $employee['name'] ?: null;
                $periodRaw = $report['period'] ?: null;

                $query = DB::table('attendance_records')->where('admin_id', $adminId);
                if (!empty($employeeId)) {
                    $query->where('employee_id', $employeeId);
                } else {
                    $query->where('employee_name', $employeeName);
                }

                $query->where(function ($subQuery) use ($periodDate, $periodRaw) {
                    if ($periodDate) {
                        $subQuery->whereDate('period_date', $periodDate->toDateString());
                    } else {
                        $subQuery->whereNull('period_date')->where('period_raw', $periodRaw);
                    }
                });

                $existingRecord = $query->first();

                $payload = [
                    'admin_id' => $adminId,
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'department' => $employee['dept'] ?: null,
                    'period_raw' => $periodRaw,
                    'period_date' => $periodDate ? $periodDate->toDateString() : null,
                    'attendance' => json_encode($employee['attendance'] ?? []),
                    'document_path' => $relativePath,
                    'updated_at' => now(),
                ];

                if ($existingRecord) {
                    DB::table('attendance_records')
                        ->where('id', $existingRecord->id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('attendance_records')->insert($payload);
                }
            }

            $generatedDocumentCount++;
        }

        return back()->with('status', "Generated {$generatedDocumentCount} document(s).");
    }

    public function show(int $id)
    {
        $record = DB::table('file')->where('id', $id)->first();
        if (!$record || empty($record->path)) {
            abort(404);
        }

        $path = (string) $record->path;
        if (Str::startsWith($path, 'attendance/')) {
            $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $path);
        } else {
            $fullPath = public_path($path);
        }

        if (!File::exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }

    public function destroy(int $id)
    {
        $record = DB::table('file')->where('id', $id)->first();
        if (!$record) {
            return back()->withErrors(['doc' => 'Document not found.']);
        }

        if (!empty($record->path)) {
            $path = (string) $record->path;
            if (Str::startsWith($path, 'attendance/')) {
                $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $path);
            } else {
                $fullPath = public_path($path);
            }
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }

        DB::table('file')->where('id', $id)->delete();

        return back()->with('status', 'Document deleted.');
    }

    private function ensureTemplate(): string
    {
        $templatePath = base_path('template' . DIRECTORY_SEPARATOR . 'DTR.docx');
        if (!File::exists($templatePath)) {
            throw new \RuntimeException('Template file template/DTR.docx was not found.');
        }

        if ((int) File::size($templatePath) <= 0) {
            throw new \RuntimeException('Template file template/DTR.docx is empty or corrupted.');
        }

        $zip = new ZipArchive();
        $zipOpenResult = $zip->open($templatePath);
        if ($zipOpenResult !== true) {
            throw new \RuntimeException('Template file template/DTR.docx is not a valid DOCX file.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($documentXml === false) {
            throw new \RuntimeException('Template file template/DTR.docx is missing word/document.xml.');
        }

        return $templatePath;
    }

    private function generateDocx(string $templatePath, string $outputPath, array $employee, string $period): void
    {
        $tempPath = storage_path('app' . DIRECTORY_SEPARATOR . 'dtr_' . Str::uuid() . '.docx');
        File::copy($templatePath, $tempPath);

        $zip = new ZipArchive();
        $zipOpenResult = $zip->open($tempPath);
        if ($zipOpenResult !== true) {
            File::delete($tempPath);
            throw new \RuntimeException('Unable to open DOCX template archive.');
        }

        $documentXml = $zip->getFromName('word/document.xml');

        if ($documentXml === false) {
            $zip->close();
            File::delete($tempPath);
            throw new \RuntimeException('Invalid DOCX template.');
        }

        [$month, $year] = $this->extractMonthYear($period);
        $employeeName = $employee['name'] ?: 'N/A';
        $employeeNameUpper = Str::upper((string) $employeeName);
        $monthUpper = Str::upper((string) $month);
        $yearUpper = Str::upper((string) $year);
        $placeholders = [
            '{employeeName}' => $employeeNameUpper,
            '{name}' => $employeeNameUpper,
            '{month}' => $monthUpper,
            '{year}' => $yearUpper,
        ];

        $documentXml = $this->replacePlaceholdersInWordXml($documentXml, $placeholders);
        $this->replacePlaceholdersInZip($zip, $placeholders);

        $documentXml = $this->fillAttendanceTables($documentXml, $employee['attendance']);

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        if (File::exists($outputPath)) {
            File::delete($outputPath);
        }
        File::move($tempPath, $outputPath);
    }

    private function buildFilename(array $employee, string $periodSlug): string
    {
        $identity = $employee['id'] ?: ($employee['name'] ?: 'unknown');
        $identitySlug = Str::slug($identity);
        if ($identitySlug === '') {
            $identitySlug = 'unknown';
        }

        $base = 'DTR_' . $identitySlug;
        if ($periodSlug !== '') {
            $base .= '_' . $periodSlug;
        }

        return $base . '.docx';
    }

    private function extractReportData(array $rows): array
    {
        $period = '';
        $dayRowIndex = null;
        $dayColumns = [];

        foreach ($rows as $index => $row) {
            if ($period === '' && $this->rowContainsLabel($row, 'Att. Time')) {
                $period = $this->valueAfterLabel($row, 'Att. Time');
            }

            $candidateDayColumns = [];
            foreach ($row as $colIndex => $cell) {
                if (is_numeric($cell)) {
                    $day = (int) $cell;
                    if ($day >= 1 && $day <= 31) {
                        $candidateDayColumns[$colIndex] = $day;
                    }
                }
            }

            if ($dayRowIndex === null && count($candidateDayColumns) >= 10 && in_array(1, $candidateDayColumns, true)) {
                $dayRowIndex = $index;
                $dayColumns = $candidateDayColumns;
            }
        }

        if ($dayRowIndex === null) {
            return [
                'period' => $period,
                'employees' => [],
            ];
        }

        $employees = [];
        $rowCount = count($rows);
        for ($rowIndex = $dayRowIndex + 1; $rowIndex < $rowCount; $rowIndex++) {
            $row = $rows[$rowIndex];
            if (!$this->rowContainsLabel($row, 'ID:')) {
                continue;
            }

            $employeeId = $this->valueAfterLabel($row, 'ID:');
            $employeeName = $this->valueAfterLabel($row, 'Name:');
            $employeeDept = $this->valueAfterLabel($row, 'Dept.:');
            if ($employeeDept === '') {
                $employeeDept = $this->valueAfterLabel($row, 'Dept:');
            }

            $attendanceDataRow = null;
            for ($scanRowIndex = $rowIndex + 1; $scanRowIndex < $rowCount; $scanRowIndex++) {
                $scannedRow = $rows[$scanRowIndex];
                if ($this->rowContainsLabel($scannedRow, 'ID:')) {
                    break;
                }
                if ($this->rowHasDataForColumns($scannedRow, array_keys($dayColumns))) {
                    $attendanceDataRow = $scannedRow;
                    $rowIndex = $scanRowIndex;
                    break;
                }
            }

            $attendance = [];
            if ($attendanceDataRow !== null) {
                foreach ($dayColumns as $colIndex => $dayNumber) {
                    $raw = $attendanceDataRow[$colIndex] ?? '';
                    $attendance[$dayNumber] = $this->parseAttendanceCell($raw);
                }
            }

            $employees[] = [
                'id' => trim((string) $employeeId),
                'name' => trim((string) $employeeName),
                'dept' => trim((string) $employeeDept),
                'attendance' => $attendance,
            ];
        }

        return [
            'period' => $period,
            'employees' => $employees,
        ];
    }

    private function rowContainsLabel(array $row, string $label): bool
    {
        $label = strtolower(trim($label));
        foreach ($row as $cell) {
            if (strtolower(trim((string) $cell)) === $label) {
                return true;
            }
        }

        return false;
    }

    private function valueAfterLabel(array $row, string $label): string
    {
        $label = strtolower(trim($label));
        $found = false;
        foreach ($row as $cell) {
            $cellValue = trim((string) $cell);
            if ($found && $cellValue !== '') {
                return $cellValue;
            }
            if (strtolower($cellValue) === $label) {
                $found = true;
            }
        }

        return '';
    }

    private function rowHasDataForColumns(array $row, array $columns): bool
    {
        foreach ($columns as $colIndex) {
            $value = $row[$colIndex] ?? '';
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function parseAttendanceCell($value): array
    {
        $raw = trim((string) $value);
        $emptySlots = [
            'am_in' => '',
            'am_out' => '',
            'pm_in' => '',
            'pm_out' => '',
        ];

        if ($raw === '') {
            return [
                'slots' => $emptySlots,
                'totalMinutes' => null,
                'note' => '',
            ];
        }

        $special = $this->mapSpecialCode($raw);
        if ($special !== null) {
            return [
                'slots' => [
                    'am_in' => $special,
                    'am_out' => '',
                    'pm_in' => '',
                    'pm_out' => '',
                ],
                'totalMinutes' => null,
                'note' => $special,
            ];
        }

        $times = $this->extractTimes($raw);
        if (count($times) === 0) {
            return [
                'slots' => [
                    'am_in' => $raw,
                    'am_out' => '',
                    'pm_in' => '',
                    'pm_out' => '',
                ],
                'totalMinutes' => null,
                'note' => $raw,
            ];
        }

        $slots = $this->mapTimesToSlots($times);

        return [
            'slots' => $slots,
            'totalMinutes' => $this->calculateTotalMinutes($times),
            'note' => '',
        ];
    }

    private function mapSpecialCode(string $value): ?string
    {
        $normalized = strtoupper(trim($value));
        $map = [
            '25' => 'Leave',
            '26' => 'Out',
            'LEAVE' => 'Leave',
            'OUT' => 'Out',
            'HOLIDAY' => 'Holiday',
            'HOL' => 'Holiday',
            'OFF' => 'Off',
        ];

        return $map[$normalized] ?? null;
    }

    private function extractTimes(string $raw): array
    {
        $raw = preg_replace('/\s+/', '', $raw);
        if ($raw === '') {
            return [];
        }

        if (!preg_match_all('/\d{2}:\d{2}/', $raw, $matches)) {
            return [];
        }

        $times = $matches[0];
        $deduplicatedTimes = [];
        foreach ($times as $time) {
            if (end($deduplicatedTimes) !== $time) {
                $deduplicatedTimes[] = $time;
            }
        }

        return $deduplicatedTimes;
    }

    private function mapTimesToSlots(array $times): array
    {
        $slots = [
            'am_in' => '',
            'am_out' => '',
            'pm_in' => '',
            'pm_out' => '',
        ];

        $times = array_values($times);
        if (isset($times[0])) {
            $slots['am_in'] = $times[0];
        }
        if (isset($times[1])) {
            $slots['am_out'] = $times[1];
        }
        if (isset($times[2])) {
            $slots['pm_in'] = $times[2];
        }
        if (isset($times[3])) {
            $slots['pm_out'] = $times[3];
        }

        return $slots;
    }

    private function calculateTotalMinutes(array $times): ?int
    {
        $times = array_values($times);
        if (count($times) < 2) {
            return null;
        }

        $totalMinutes = 0;
        for ($i = 0; $i + 1 < count($times) && $i < 4; $i += 2) {
            $start = $this->timeToMinutes($times[$i]);
            $end = $this->timeToMinutes($times[$i + 1]);
            if ($start === null || $end === null) {
                continue;
            }
            $minuteDifference = $end - $start;
            if ($minuteDifference > 0) {
                $totalMinutes += $minuteDifference;
            }
        }

        return $totalMinutes > 0 ? $totalMinutes : null;
    }

    private function timeToMinutes(string $time): ?int
    {
        if (!preg_match('/^(\\d{2}):(\\d{2})$/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    private function extractMonthYear(string $period): array
    {
        if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $period, $matches) && count($matches[0]) > 0) {
            $date = Carbon::createFromFormat('Y-m-d', $matches[0][0]);
            return [$date->format('F'), $date->format('Y')];
        }

        $date = Carbon::now();
        return [$date->format('F'), $date->format('Y')];
    }

    private function extractPeriodDate(string $period): ?Carbon
    {
        if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $period, $matches) && count($matches[0]) > 0) {
            return Carbon::createFromFormat('Y-m-d', $matches[0][0]);
        }

        return null;
    }

    private function buildPeriodSlug(string $periodRaw, ?Carbon $periodDate): string
    {
        if ($periodDate) {
            return $periodDate->format('Y-m');
        }

        $periodRaw = trim($periodRaw);
        if ($periodRaw !== '') {
            return Str::slug($periodRaw);
        }

        [$month, $year] = $this->extractMonthYear($periodRaw);
        return Str::slug(trim($month . ' ' . $year));
    }

    private function formatPeriodLabel(string $periodRaw, ?string $periodDate): string
    {
        if (!empty($periodDate)) {
            return Carbon::parse($periodDate)->format('F Y');
        }

        [$month, $year] = $this->extractMonthYear($periodRaw);
        return trim($month . ' ' . $year);
    }

    private function replacePlaceholdersInZip(ZipArchive $zip, array $placeholders): void
    {
        $xmlPartNames = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }

            if (preg_match('/^word\/(header|footer)\d+\.xml$/', $name)) {
                $xmlPartNames[] = $name;
            }
        }

        foreach ($xmlPartNames as $xmlPartName) {
            $xml = $zip->getFromName($xmlPartName);
            if ($xml === false) {
                continue;
            }

            $updated = $this->replacePlaceholdersInWordXml($xml, $placeholders);
            $zip->addFromString($xmlPartName, $updated);
        }
    }

    private function replacePlaceholdersInWordXml(string $xml, array $placeholders): string
    {
        // Fast path for placeholders that are already contiguous text.
        $updated = str_replace(array_keys($placeholders), array_values($placeholders), $xml);

        foreach ($placeholders as $tag => $value) {
            $chars = preg_split('//u', $tag, -1, PREG_SPLIT_NO_EMPTY);
            if ($chars === false || count($chars) === 0) {
                continue;
            }

            $escapedChars = array_map(static function (string $char): string {
                return preg_quote($char, '/');
            }, $chars);

            // Word can split a placeholder across multiple XML runs/tags.
            $pattern = '/' . implode('(?:\\s|<[^>]+>)*', $escapedChars) . '/u';
            $replacement = htmlspecialchars((string) $value, ENT_XML1);
            $updated = preg_replace($pattern, $replacement, $updated) ?? $updated;
        }

        return $updated;
    }

    private function fillAttendanceTables(string $documentXml, array $attendance): string
    {
        $domDocument = new \DOMDocument();
        $domDocument->preserveWhiteSpace = true;
        $domDocument->formatOutput = false;
        $domDocument->loadXML($documentXml);
        $xpath = new \DOMXPath($domDocument);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xpath->query('//w:tbl');
        foreach ($tables as $table) {
            $rows = $xpath->query('.//w:tr', $table);
            foreach ($rows as $row) {
                $cells = $xpath->query('.//w:tc', $row);
                if ($cells->length < 3) {
                    continue;
                }

                $dayText = $this->getCellText($xpath, $cells->item(0));
                if (!is_numeric($dayText)) {
                    continue;
                }

                $day = (int) $dayText;
                if (!isset($attendance[$day])) {
                    continue;
                }

                $attendanceEntry = $attendance[$day] ?? [];
                $slots = $attendanceEntry['slots'] ?? [];
                $amIn = (string) ($slots['am_in'] ?? '');
                $amOut = (string) ($slots['am_out'] ?? '');
                $pmIn = (string) ($slots['pm_in'] ?? '');
                $pmOut = (string) ($slots['pm_out'] ?? '');

                if ($cells->length >= 5) {
                    $this->setCellText($domDocument, $xpath, $cells->item(1), $amIn, $row);
                    $this->setCellText($domDocument, $xpath, $cells->item(2), $amOut, $row);
                    $this->setCellText($domDocument, $xpath, $cells->item(3), $pmIn, $row);
                    $this->setCellText($domDocument, $xpath, $cells->item(4), $pmOut, $row);
                } else {
                    $arrival = $amIn !== '' ? $amIn : $pmIn;
                    $departure = $pmOut !== '' ? $pmOut : $amOut;
                    $this->setCellText($domDocument, $xpath, $cells->item(1), $arrival, $row);
                    $this->setCellText($domDocument, $xpath, $cells->item(2), $departure, $row);
                }

            }
        }

        return $domDocument->saveXML();
    }

    private function getCellText(\DOMXPath $xpath, \DOMElement $cell): string
    {
        $texts = $xpath->query('.//w:t', $cell);
        $value = '';
        foreach ($texts as $text) {
            $value .= $text->nodeValue;
        }
        return trim($value);
    }

    private function setCellText(\DOMDocument $domDocument, \DOMXPath $xpath, \DOMElement $cell, string $value, \DOMElement $row): void
    {
        $value = trim($value);
        $texts = $xpath->query('.//w:t', $cell);
        if ($texts->length > 0) {
            $texts->item(0)->nodeValue = $value;
            if ($value !== '' && preg_match('/\s/', $value)) {
                $texts->item(0)->setAttribute('xml:space', 'preserve');
            }
            for ($i = 1; $i < $texts->length; $i++) {
                $texts->item($i)->nodeValue = '';
            }
            return;
        }

        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $p = $xpath->query('.//w:p', $cell)->item(0);
        if (!$p) {
            $p = $domDocument->createElementNS($ns, 'w:p');
            $cell->appendChild($p);
        }

        // Avoid adding extra paragraphs that can stretch the row height.
        $r = $domDocument->createElementNS($ns, 'w:r');
        $referenceRunProps = $this->findReferenceRunProps($xpath, $cell, $row);
        if ($referenceRunProps) {
            $r->appendChild($domDocument->importNode($referenceRunProps, true));
        }

        $t = $domDocument->createElementNS($ns, 'w:t', $value);
        if ($value !== '' && preg_match('/\s/', $value)) {
            $t->setAttribute('xml:space', 'preserve');
        }
        $r->appendChild($t);
        $p->appendChild($r);
    }

    private function findReferenceRunProps(\DOMXPath $xpath, \DOMElement $cell, \DOMElement $row): ?\DOMElement
    {
        $runProps = $xpath->query('.//w:rPr', $cell);
        if ($runProps->length > 0) {
            return $runProps->item(0);
        }

        $rowRunProps = $xpath->query('.//w:rPr', $row);
        if ($rowRunProps->length > 0) {
            return $rowRunProps->item(0);
        }

        return null;
    }
}
