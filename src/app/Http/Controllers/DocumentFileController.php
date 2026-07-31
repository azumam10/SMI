<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentFileController extends Controller
{
    /**
     * Download file dokumen.
     */
    public function download(DocumentFile $file): StreamedResponse
    {
        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name
        );
    }

    /**
     * Hapus file dokumen.
     */
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
