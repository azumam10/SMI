<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\LeaveDocumentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::middleware(['auth'])->group(function () {

    // Route Download & Delete File Dokumen Karyawan
    Route::get('/admin/document-file/{file}/download', [DocumentFileController::class, 'download'])
        ->name('filament.document-file.download');

    Route::delete('/admin/document-file/{id}', [DocumentFileController::class, 'destroy'])
        ->name('filament.document-file.destroy');

    // Route Download Cuti
    Route::get(
        '/leave-documents/{leaveDocument}/download',
        [LeaveDocumentController::class, 'download']
    )->name('leave-document.download');

});
