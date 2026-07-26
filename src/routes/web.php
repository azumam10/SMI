<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentFileController;
use App\Http\Controllers\LeaveDocumentController;
use Illuminate\Support\Facades\Route;
use App\Exports\EmployeeTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

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

     Route::get('/employee-template', function () {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        return Excel::download(new EmployeeTemplateExport(), 'Template_Import_Karyawan.xlsx');
    })->name('employee.template');

});

