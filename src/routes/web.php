<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::delete('/admin/document-file/{id}', [App\Http\Controllers\DocumentFileController::class, 'destroy'])
    ->name('filament.document-file.destroy')
    ->middleware(['auth']);
