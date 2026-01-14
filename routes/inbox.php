<?php

use App\Http\Controllers\InboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inbox')->group(function () {
    // Main inbox page
    Route::get('/', [InboxController::class, 'index'])->name('inbox');

    // Individual item actions
    Route::post('/{inboxItem}/approve', [InboxController::class, 'approve'])->name('inbox.approve');
    Route::post('/{inboxItem}/reject', [InboxController::class, 'reject'])->name('inbox.reject');
    Route::post('/{inboxItem}/defer', [InboxController::class, 'defer'])->name('inbox.defer');
    Route::delete('/{inboxItem}', [InboxController::class, 'archive'])->name('inbox.archive');

    // Bulk actions
    Route::post('/bulk', [InboxController::class, 'bulkAction'])->name('inbox.bulk');
});
