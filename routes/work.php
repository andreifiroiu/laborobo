<?php

use App\Http\Controllers\Work\CommunicationController;
use App\Http\Controllers\Work\DeliverableController;
use App\Http\Controllers\Work\PartyController;
use App\Http\Controllers\Work\ProjectController;
use App\Http\Controllers\Work\TaskController;
use App\Http\Controllers\Work\TimeEntryController;
use App\Http\Controllers\Work\WorkController;
use App\Http\Controllers\Work\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('work')->group(function () {
    // Main work page
    Route::get('/', [WorkController::class, 'index'])->name('work');
    Route::patch('/preferences', [WorkController::class, 'updatePreference'])->name('work.preferences');

    // Projects
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

    // Work Orders
    Route::post('/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::patch('/work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update');
    Route::delete('/work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])->name('work-orders.destroy');
    Route::patch('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.status');

    // Tasks
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::patch('/tasks/{task}/checklist/{itemId}', [TaskController::class, 'toggleChecklist'])->name('tasks.checklist');

    // Time Entries
    Route::post('/time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
    Route::post('/tasks/{task}/timer/start', [TimeEntryController::class, 'startTimer'])->name('tasks.timer.start');
    Route::post('/tasks/{task}/timer/stop', [TimeEntryController::class, 'stopTimer'])->name('tasks.timer.stop');

    // Deliverables
    Route::post('/deliverables', [DeliverableController::class, 'store'])->name('deliverables.store');
    Route::get('/deliverables/{deliverable}', [DeliverableController::class, 'show'])->name('deliverables.show');
    Route::patch('/deliverables/{deliverable}', [DeliverableController::class, 'update'])->name('deliverables.update');
    Route::delete('/deliverables/{deliverable}', [DeliverableController::class, 'destroy'])->name('deliverables.destroy');

    // Communications (polymorphic: projects/{id}/communications or work-orders/{id}/communications)
    Route::get('/{type}/{id}/communications', [CommunicationController::class, 'show'])->name('communications.show');
    Route::post('/{type}/{id}/communications', [CommunicationController::class, 'store'])->name('communications.store');

    // Parties
    Route::get('/parties', [PartyController::class, 'index'])->name('parties.index');
    Route::post('/parties', [PartyController::class, 'store'])->name('parties.store');
    Route::patch('/parties/{party}', [PartyController::class, 'update'])->name('parties.update');
    Route::delete('/parties/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');
});
