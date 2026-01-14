<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\PlaybooksController;
use App\Http\Controllers\TodayController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Redirect home to today
Route::redirect('/', '/today')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Main navigation routes
    Route::get('today', [TodayController::class, 'index'])->name('today');

    // Work routes are in routes/work.php
    // Inbox routes are in routes/inbox.php

    Route::get('playbooks', [PlaybooksController::class, 'index'])->name('playbooks');

    Route::get('directory', [DirectoryController::class, 'index'])->name('directory');

    Route::get('reports', function () {
        return Inertia::render('reports/index');
    })->name('reports');

    Route::get('settings', function () {
        return Inertia::render('settings/index');
    })->name('settings.index');

    // Keep dashboard for now (can remove later)
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/work.php';
require __DIR__.'/inbox.php';
require __DIR__.'/directory.php';
require __DIR__.'/playbooks.php';
