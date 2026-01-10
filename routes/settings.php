<?php

use App\Http\Controllers\Settings\LanguageController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TeamController;
use App\Http\Controllers\Settings\TeamMemberController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/language', [LanguageController::class, 'edit'])->name('language.edit');
    Route::patch('settings/language', [LanguageController::class, 'update'])->name('language.update');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // Team management
    Route::get('settings/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::patch('settings/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

    // Team members
    Route::get('settings/teams/{team}/members', [TeamMemberController::class, 'index'])->name('teams.members.index');
    Route::post('settings/teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::delete('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
});
