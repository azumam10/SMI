<?php

namespace App\Http\Controllers;

use App\Models\DocumentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentFileController extends Controller
{
    public function destroy(Request $request, $id)
    {
        $file = DocumentFile::findOrFail($id);

        // Hapus file fisik dari storage
        Storage::disk($file->disk)->delete($file->path);

        // Hapus record dari database
        $file->delete();

        // Redirect back dengan notifikasi
        return back()->with('success', 'File berhasil dihapus.');
    }
}