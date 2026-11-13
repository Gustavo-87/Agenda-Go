<?php

use App\Http\Controllers\ProfileController;
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
    Route::get('/google/connect/{professional}', [\App\Http\Controllers\GoogleCalendarController::class, 'redirect'])->name('google.connect');
    Route::get('/google/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.callback');
});

Route::get('/agenda', [\App\Http\Controllers\AgendaController::class, 'index'])
    ->middleware('auth')
    ->name('agenda.index');
