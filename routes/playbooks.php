<?php

use App\Http\Controllers\Playbooks\PlaybookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('playbooks')->group(function () {
    // CRUD operations
    Route::post('/', [PlaybookController::class, 'store'])->name('playbooks.store');
    Route::patch('/{playbook}', [PlaybookController::class, 'update'])->name('playbooks.update');
    Route::delete('/{playbook}', [PlaybookController::class, 'destroy'])->name('playbooks.destroy');

    // Additional operations
    Route::post('/{playbook}/duplicate', [PlaybookController::class, 'duplicate'])->name('playbooks.duplicate');
    Route::post('/{playbook}/attach', [PlaybookController::class, 'attachToWorkOrders'])->name('playbooks.attach');
});
