<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\LeaveDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::delete('/admin/document-file/{id}', [DocumentFileController::class, 'destroy'])
    ->name('filament.document-file.destroy')
    ->middleware(['auth']);

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/leave-documents/{leaveDocument}/download',
        [LeaveDocumentController::class, 'download']
    )->name('leave-document.download');

});
