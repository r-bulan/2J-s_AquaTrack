<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Two J's AquaTrack
|--------------------------------------------------------------------------
*/

// Root redirect based on authenticated role
Route::get('/', [AuthController::class, 'root'])->name('root');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token?}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:6,1')->name('verification.send');

    // Self-profile management (All roles)
    Route::put('/profile/name', [ProfileController::class, 'updateName'])->name('profile.update-name');
});

// Admin / Owner Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/assign-rider', [OrderController::class, 'assignRider'])->name('orders.assign');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/adjust-jugs', [CustomerController::class, 'adjustJugs'])->name('customers.adjust-jugs');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/{item}/restock', [InventoryController::class, 'restock'])->name('inventory.restock');

    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance', [FinanceController::class, 'store'])->name('finance.store');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{maintenance}/serviced', [MaintenanceController::class, 'markServiced'])->name('maintenance.serviced');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/export-sales-csv', [ReportController::class, 'exportSalesCsv'])->name('reports.sales-csv');
    Route::get('/reports/export-expense-csv', [ReportController::class, 'exportExpenseCsv'])->name('reports.expense-csv');

    Route::get('/riders', [RiderController::class, 'index'])->name('riders.index');
    Route::post('/riders', [RiderController::class, 'store'])->name('riders.store');
    Route::put('/riders/{rider}', [RiderController::class, 'update'])->name('riders.update');
    Route::post('/riders/{rider}/toggle-status', [RiderController::class, 'toggleStatus'])->name('riders.toggle-status');
    Route::post('/riders/{rider}/cash-advance', [RiderController::class, 'adjustCashAdvance'])->name('riders.cash-advance');

    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('feedback.status');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});

// Deliveries Management (Admin has full access, Rider accesses assigned deliveries)
Route::middleware(['auth', 'role:admin,rider'])->group(function () {
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('/deliveries/{delivery}/start', [DeliveryController::class, 'start'])->name('deliveries.start');
    Route::post('/deliveries/{delivery}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
    Route::post('/deliveries/{delivery}/fail', [DeliveryController::class, 'fail'])->name('deliveries.fail');
});

// Customer Portal Routes
Route::middleware(['auth', 'role:customer'])->prefix('portal')->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('portal.index');
    Route::post('/orders', [PortalController::class, 'placeOrder'])->middleware('verified')->name('portal.orders.store');
    Route::post('/recurring', [PortalController::class, 'storeRecurringOrder'])->middleware('verified')->name('portal.recurring.store');
    Route::post('/recurring/{recurringOrder}/pause', [PortalController::class, 'pauseRecurringOrder'])->name('portal.recurring.pause');
    Route::post('/recurring/{recurringOrder}/resume', [PortalController::class, 'resumeRecurringOrder'])->name('portal.recurring.resume');
    Route::post('/recurring/{recurringOrder}/cancel', [PortalController::class, 'cancelRecurringOrder'])->name('portal.recurring.cancel');
    Route::post('/feedback', [PortalController::class, 'submitFeedback'])->name('portal.feedback.store');
});

// Fallback 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
