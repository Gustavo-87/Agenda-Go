<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
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

    Route::get('/businesses/{business}/holidays', [HolidayController::class, 'index'])
        ->name('holidays.index');
    Route::post('/businesses/{business}/holidays', [HolidayController::class, 'store'])
        ->name('holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])
        ->name('holidays.destroy');

    Route::patch('/businesses/{business}/booking-policy', [BusinessController::class, 'updateBookingPolicy'])
        ->name('businesses.booking-policy');

    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])
        ->name('appointments.confirm');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])
        ->name('appointments.reschedule');
    Route::post('/appointments/{appointment}/attend', [AppointmentController::class, 'attend'])
        ->name('appointments.attend');
    Route::post('/appointments/{appointment}/absent', [AppointmentController::class, 'absent'])
        ->name('appointments.absent');

    Route::get('/businesses/{business}/customers', [CustomerController::class, 'index'])
        ->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->name('customers.show');
    Route::post('/customers/{customer}/consent', [CustomerController::class, 'consent'])
        ->name('customers.consent');

    Route::post('/businesses/{business}/payments', [PaymentController::class, 'store'])
        ->name('payments.store');
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])
        ->name('payments.verify');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->name('payments.receipt');

    Route::get('/businesses/{business}/reports/summary', [ReportController::class, 'summary'])
        ->name('reports.summary');
    Route::get('/businesses/{business}/reports/appointments.csv', [ReportController::class, 'appointmentsCsv'])
        ->name('reports.appointments');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/businesses', [AdminController::class, 'businesses'])->name('businesses');
        Route::post('/businesses', [AdminController::class, 'storeBusiness'])->name('businesses.store');
        Route::post('/businesses/{business}/activate', [AdminController::class, 'activate'])->name('businesses.activate');
        Route::post('/businesses/{business}/suspend', [AdminController::class, 'suspend'])->name('businesses.suspend');
        Route::post('/businesses/{business}/memberships', [AdminController::class, 'registerMembership'])->name('memberships.store');
        Route::post('/memberships/{membership}/renew', [AdminController::class, 'renew'])->name('memberships.renew');
        Route::post('/membership-payments/{payment}/verify', [AdminController::class, 'verifyPayment'])->name('payments.verify');
    });
});
