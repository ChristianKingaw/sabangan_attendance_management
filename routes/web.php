<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::middleware('admin.auth')->group(function () {
    Route::get('/', [DocumentController::class, 'index']);
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/settings', [AttendanceController::class, 'settings'])->name('settings.index');
    Route::delete('/settings/attendance', [AttendanceController::class, 'destroyAllAttendance'])->name('settings.attendance.destroyAll');
    Route::get('/attendance/documents/{id}', [AttendanceController::class, 'showDocument'])->name('attendance.document');
    Route::get('/attendance/employee-zip', [AttendanceController::class, 'downloadEmployeeZip'])->name('attendance.employee.zip');
    Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
