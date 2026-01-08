<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Redirect home to today
Route::redirect('/', '/today')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Main navigation routes
    Route::get('today', function () {
        return Inertia::render('today');
    })->name('today');

    Route::get('work', function () {
        return Inertia::render('work/index');
    })->name('work');

    Route::get('inbox', function () {
        return Inertia::render('inbox/index');
    })->name('inbox');

    Route::get('playbooks', function () {
        return Inertia::render('playbooks/index');
    })->name('playbooks');

    Route::get('directory', function () {
        return Inertia::render('directory/index');
    })->name('directory');

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
