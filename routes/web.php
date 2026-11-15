<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleExceptionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/google/connect/{professional}', [GoogleCalendarController::class, 'redirect'])->name('google.connect');
    Route::get('/google/callback', [GoogleCalendarController::class, 'callback'])->name('google.callback');
});

Route::get('/agenda', [AgendaController::class, 'index'])
    ->middleware('auth')
    ->name('agenda.index');

Route::middleware('auth')->group(function () {
    Route::get('/professionals', [ProfessionalController::class, 'index'])
        ->name('professionals.index');
    Route::post('/professionals', [ProfessionalController::class, 'store'])
        ->name('professionals.store');
    Route::patch('/professionals/{professional}', [ProfessionalController::class, 'update'])
        ->name('professionals.update');
    Route::patch('/professionals/{professional}/toggle', [ProfessionalController::class, 'toggle'])
        ->name('professionals.toggle');

    Route::get('/businesses/{business}/services', [ServiceController::class, 'index'])
        ->name('services.index');
    Route::post('/businesses/{business}/services', [ServiceController::class, 'store'])
        ->name('services.store');
    Route::patch('/services/{service}', [ServiceController::class, 'update'])
        ->name('services.update');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])
        ->name('services.toggle');

    Route::get('/professionals/{professional}/schedules', [WorkScheduleController::class, 'index'])
        ->name('schedules.index');
    Route::post('/professionals/{professional}/schedules', [WorkScheduleController::class, 'store'])
        ->name('schedules.store');
    Route::delete('/schedules/{schedule}', [WorkScheduleController::class, 'destroy'])
        ->name('schedules.destroy');

    Route::get('/professionals/{professional}/exceptions', [ScheduleExceptionController::class, 'index'])
        ->name('exceptions.index');
    Route::post('/professionals/{professional}/exceptions', [ScheduleExceptionController::class, 'store'])
        ->name('exceptions.store');
    Route::delete('/exceptions/{exception}', [ScheduleExceptionController::class, 'destroy'])
        ->name('exceptions.destroy');
});
