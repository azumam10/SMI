<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentFile;
use Illuminate\Http\Request;

final class DocumentFileController extends Controller
{
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $file = DocumentFile::findOrFail($id);

        // File fisik otomatis dihapus oleh event Model (booted)
        $file->delete();

        return back()->with(
            'success',
            'File berhasil dihapus.'
        );
    }
}
