<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class AttendanceController extends Controller
{
    public function index()
    {
        $adminId = (int) session('admin_id', 0);
        $records = collect();

        if (DB::getSchemaBuilder()->hasTable('attendance_records')) {
            $records = DB::table('attendance_records')
                ->where('admin_id', $adminId)
                ->orderBy('department')
                ->orderBy('employee_name')
                ->orderByDesc('period_date')
                ->get();
        }

        $departmentGroups = $records
            ->map(function ($record) {
                $record->period_label = $this->formatPeriodLabel(
                    $record->period_raw ?? '',
                    $record->period_date ?? null
                );
                return $record;
            })
            ->groupBy(function ($record) {
                $dept = trim((string) ($record->department ?? ''));
                return $dept !== '' ? $dept : '__none__';
            })
            ->map(function ($deptRecords, $deptKey) {
                $departmentValue = $deptKey === '__none__' ? '' : $deptKey;
                $departmentLabel = $deptKey === '__none__' ? 'No Department' : $deptKey;

                $employees = $deptRecords
                    ->groupBy(function ($record) {
                        $employeeId = trim((string) ($record->employee_id ?? ''));
                        if ($employeeId !== '') {
                            return 'id:' . $employeeId;
                        }
                        $employeeName = trim((string) ($record->employee_name ?? ''));
                        return 'name:' . ($employeeName !== '' ? $employeeName : 'unknown');
                    })
                    ->map(function ($employeeRecords) {
                        $first = $employeeRecords->first();
                        $employeeId = trim((string) ($first->employee_id ?? ''));
                        $employeeName = trim((string) ($first->employee_name ?? ''));

                        return [
                            'employee_id' => $employeeId,
                            'employee_name' => $employeeName !== '' ? $employeeName : 'Unnamed',
                            'records' => $employeeRecords->map(function ($record) {
                                return [
                                    'id' => $record->id,
                                    'period_label' => $record->period_label,
                                    'document_url' => $record->document_path ? route('attendance.document', $record->id) : null,
                                ];
                            })->values(),
                        ];
                    })
                    ->values();

                return [
                    'department_label' => $departmentLabel,
                    'department_value' => $departmentValue,
                    'employees' => $employees,
                ];
            })
            ->values();

        return view('attendance', [
            'departmentGroups' => $departmentGroups,
            'attendancePayload' => $departmentGroups->all(),
        ]);
    }

    public function downloadEmployeeZip(Request $request)
    {
        if (!class_exists(\ZipArchive::class)) {
            return back()->withErrors(['attendance' => 'PHP Zip extension is required to create archives.']);
        }

        $adminId = (int) $request->session()->get('admin_id', 0);
        $department = trim((string) $request->query('department', ''));
        $employeeId = trim((string) $request->query('employee_id', ''));
        $employeeName = trim((string) $request->query('employee_name', ''));

        if ($department === '__none__') {
            $department = '';
        }

        if ($employeeId === '' && $employeeName === '') {
            return back()->withErrors(['attendance' => 'Employee is required to download attendance.']);
        }

        $query = DB::table('attendance_records')->where('admin_id', $adminId);

        $query->where(function ($q) use ($department) {
            if ($department === '') {
                $q->whereNull('department')->orWhere('department', '');
            } else {
                $q->where('department', $department);
            }
        });

        if ($employeeId !== '') {
            $query->where('employee_id', $employeeId);
        } else {
            $query->where('employee_name', $employeeName);
        }

        $records = $query->orderByDesc('period_date')->get();
        if ($records->isEmpty()) {
            return back()->withErrors(['attendance' => 'No attendance documents found for that employee.']);
        }

        $safeDepartment = Str::slug($department !== '' ? $department : 'no-department');
        $safeEmployee = Str::slug($employeeName !== '' ? $employeeName : ($employeeId !== '' ? $employeeId : 'employee'));
        $zipName = 'attendance_' . $safeDepartment . '_' . $safeEmployee . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app' . DIRECTORY_SEPARATOR . $zipName);

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            return back()->withErrors(['attendance' => 'Unable to create zip archive.']);
        }

        $usedNames = [];
        foreach ($records as $record) {
            if (empty($record->document_path)) {
                continue;
            }
            $path = (string) $record->document_path;
            if (Str::startsWith($path, 'attendance/')) {
                $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $path);
            } else {
                $fullPath = public_path($path);
            }
            if (!File::exists($fullPath)) {
                continue;
            }

            $periodLabel = $this->formatPeriodLabel($record->period_raw ?? '', $record->period_date ?? null);
            $prefix = Str::slug($periodLabel !== '' ? $periodLabel : 'period');
            $baseName = $prefix . '_' . basename($fullPath);
            $archiveName = $baseName;
            $counter = 1;
            while (isset($usedNames[$archiveName])) {
                $archiveName = $prefix . '_' . $counter . '_' . basename($fullPath);
                $counter++;
            }
            $usedNames[$archiveName] = true;

            $zip->addFile($fullPath, $archiveName);
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function showDocument(int $id)
    {
        $adminId = (int) session('admin_id', 0);
        $record = DB::table('attendance_records')
            ->where('id', $id)
            ->where('admin_id', $adminId)
            ->first();

        if (!$record || empty($record->document_path)) {
            abort(404);
        }

        $path = (string) $record->document_path;
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

    private function formatPeriodLabel(string $periodRaw, ?string $periodDate): string
    {
        if (!empty($periodDate)) {
            return Carbon::parse($periodDate)->format('F Y');
        }

        if (preg_match_all('/\d{4}-\d{2}-\d{2}/', $periodRaw, $matches) && count($matches[0]) > 0) {
            $date = Carbon::createFromFormat('Y-m-d', $matches[0][0]);
            return $date->format('F Y');
        }

        return '';
    }
}
