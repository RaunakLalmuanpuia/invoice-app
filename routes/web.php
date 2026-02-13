<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InvoiceChatController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Invoice Routes
Route::middleware(['auth'])->group(function () {
    // Create Invoice Page
    Route::get('/invoices/create', [InvoiceChatController::class, 'create'])->name('invoices.create');

    // Chat API Endpoint
    Route::post('/invoices/chat', [InvoiceChatController::class, 'chat'])->name('invoices.chat');

    // Download Invoice PDF
    Route::get('/invoices/download/{filename}', [InvoiceChatController::class, 'download'])->name('invoices.download');
});

require __DIR__.'/auth.php';
