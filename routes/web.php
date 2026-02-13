<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InvoiceChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InventoryController;


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


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{email}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{email}', [ClientController::class, 'destroy'])->name('clients.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Inventory Management Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{name}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{name}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
});
require __DIR__.'/auth.php';
