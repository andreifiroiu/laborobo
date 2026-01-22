<?php

use App\Http\Controllers\Documents\DocumentAnnotationController;
use App\Http\Controllers\Documents\DocumentCommentController;
use App\Http\Controllers\Documents\DocumentShareLinkController;
use App\Http\Controllers\Documents\FolderController;
use App\Http\Controllers\Documents\SharedDocumentController;
use Illuminate\Support\Facades\Route;

// Public shared document access (no auth required)
Route::get('shared/{token}', [SharedDocumentController::class, 'show'])->name('shared.document');
Route::post('shared/{token}/verify', [SharedDocumentController::class, 'verify'])->name('shared.verify');
Route::get('shared/{token}/download', [SharedDocumentController::class, 'download'])->name('shared.download');

// Authenticated routes for document management
Route::middleware(['auth', 'verified'])->group(function () {
    // Folder routes
    Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::get('folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    // Document comments (thread-level)
    Route::get('documents/{document}/comments', [DocumentCommentController::class, 'index'])->name('documents.comments.index');
    Route::post('documents/{document}/comments', [DocumentCommentController::class, 'store'])->name('documents.comments.store');
    Route::patch('documents/{document}/comments/{comment}', [DocumentCommentController::class, 'update'])->name('documents.comments.update');
    Route::delete('documents/{document}/comments/{comment}', [DocumentCommentController::class, 'destroy'])->name('documents.comments.destroy');

    // Document annotations (positional comments)
    Route::get('documents/{document}/annotations', [DocumentAnnotationController::class, 'index'])->name('documents.annotations.index');
    Route::post('documents/{document}/annotations', [DocumentAnnotationController::class, 'store'])->name('documents.annotations.store');
    Route::get('documents/{document}/annotations/{annotation}', [DocumentAnnotationController::class, 'show'])->name('documents.annotations.show');
    Route::patch('documents/{document}/annotations/{annotation}', [DocumentAnnotationController::class, 'update'])->name('documents.annotations.update');
    Route::delete('documents/{document}/annotations/{annotation}', [DocumentAnnotationController::class, 'destroy'])->name('documents.annotations.destroy');
    Route::post('documents/{document}/annotations/{annotation}/reply', [DocumentAnnotationController::class, 'addReply'])->name('documents.annotations.reply');

    // Document share links
    Route::get('documents/{document}/share-links', [DocumentShareLinkController::class, 'index'])->name('documents.share-links.index');
    Route::post('documents/{document}/share-links', [DocumentShareLinkController::class, 'store'])->name('documents.share-links.store');
    Route::get('documents/{document}/share-links/{share_link}', [DocumentShareLinkController::class, 'show'])->name('documents.share-links.show');
    Route::patch('documents/{document}/share-links/{share_link}', [DocumentShareLinkController::class, 'update'])->name('documents.share-links.update');
    Route::delete('documents/{document}/share-links/{share_link}', [DocumentShareLinkController::class, 'destroy'])->name('documents.share-links.destroy');
});
