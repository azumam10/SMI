<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LeaveDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class LeaveDocumentController extends Controller
{
    public function download(LeaveDocument $leaveDocument)
    {
        $user = Auth::user();

        $leave = $leaveDocument->leaveRequest;

        if (
            $user->hasRole('super_admin') ||
            $user->hasRole('hrd')
        ) {
            //
        } elseif (
            $user->hasRole('kepala_bagian')
        ) {

            if (
                $leave->supervisor_id !== $user->employee?->id
            ) {
                abort(403);
            }

        } elseif (
            $user->hasRole('karyawan')
        ) {

            if (
                $leave->employee_id !== $user->employee?->id
            ) {
                abort(403);
            }

        } else {

            abort(403);

        }

        return Storage::disk($leaveDocument->disk)
            ->download(
                $leaveDocument->path,
                $leaveDocument->original_name
            );
    }
}
