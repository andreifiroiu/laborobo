<?php

use App\Http\Controllers\Directory\ContactController;
use App\Http\Controllers\Directory\PartyController;
use App\Http\Controllers\Directory\TeamMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Directory Routes
|--------------------------------------------------------------------------
|
| API routes for managing parties, contacts, and team members in the
| directory section. All routes require authentication and verification.
|
*/

Route::middleware(['auth', 'verified'])->prefix('directory')->name('directory.')->group(function () {
    // Party management routes
    Route::post('parties', [PartyController::class, 'store'])->name('parties.store');
    Route::patch('parties/{party}', [PartyController::class, 'update'])->name('parties.update');
    Route::delete('parties/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

    // Contact management routes
    Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::patch('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Team member management routes
    Route::patch('team/{user}/skills', [TeamMemberController::class, 'updateSkills'])->name('team.skills.update');
    Route::patch('team/{user}/capacity', [TeamMemberController::class, 'updateCapacity'])->name('team.capacity.update');
});
