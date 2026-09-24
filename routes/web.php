<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/dashboard', [AttendanceController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('attendance.index');

Route::prefix('master-data')->group(function (): void {
    Route::get('/', [MasterDataController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('master.index');
    Route::post('/students', [MasterDataController::class, 'storeStudent'])->name('master.students.store');
    Route::post('/courses', [MasterDataController::class, 'storeCourse'])->name('master.courses.store');
    Route::post('/schedules', [MasterDataController::class, 'storeSchedule'])->name('master.schedules.store');
    Route::post('/courses/{course}/toggle', [MasterDataController::class, 'toggleCourse'])->name('master.courses.toggle');
    Route::post('/students/{student}/toggle', [MasterDataController::class, 'toggleStudent'])->name('master.students.toggle');
    Route::post('/schedules/{schedule}/toggle', [MasterDataController::class, 'toggleSchedule'])->name('master.schedules.toggle');
});

Route::get('/schedules/{schedule}/qr', [MasterDataController::class, 'scheduleQr'])
    ->middleware('throttle:60,1')
    ->name('schedules.qr');

Route::prefix('api')->group(function (): void {
    Route::get('/students/check', [AttendanceController::class, 'checkStudent'])
        ->middleware('throttle:180,1')
        ->name('students.check');

    Route::get('/schedules/lookup', [AttendanceController::class, 'lookupSchedule'])
        ->middleware('throttle:180,1')
        ->name('schedules.lookup');

    Route::get('/attendances', [AttendanceController::class, 'data'])
        ->middleware('throttle:120,1')
        ->name('attendance.data');

    Route::post('/attendances/scan', [AttendanceController::class, 'scan'])
        ->middleware('throttle:120,1')
        ->name('attendance.scan');

    // kept as an alias for clients using the previous endpoint
    Route::post('/attendances', [AttendanceController::class, 'scan'])
        ->middleware('throttle:120,1')
        ->name('attendance.store');
});
