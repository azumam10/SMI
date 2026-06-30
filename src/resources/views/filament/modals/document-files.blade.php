{{-- resources/views/filament/modals/document-files.blade.php --}}
{{-- Dipanggil dari DocumentsRelationManager action 'lihat_file' --}}

<div class="space-y-3 py-2">
    @forelse ($document->files as $file)
        <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            {{-- Icon berdasarkan tipe file --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex-shrink-0">
                    @if ($file->isImage())
                        <x-heroicon-o-photo class="w-8 h-8 text-blue-500"/>
                    @elseif ($file->isPdf())
                        <x-heroicon-o-document class="w-8 h-8 text-red-500"/>
                    @else
                        <x-heroicon-o-document-text class="w-8 h-8 text-gray-400"/>
                    @endif
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $file->original_name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $file->formatted_size }}
                        @if ($file->uploaded_by)
                            · Diupload oleh {{ $file->uploaded_by }}
                        @endif
                        · {{ $file->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            {{-- Aksi: preview (gambar) atau download --}}
            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                @if ($file->isImage())
                    <a href="{{ Storage::disk($file->disk)->url($file->path) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        <x-heroicon-m-eye class="w-4 h-4"/>
                        Lihat
                    </a>
                @endif

                <a href="{{ Storage::disk($file->disk)->url($file->path) }}"
                   download="{{ $file->original_name }}"
                   class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4"/>
                    Unduh
                </a>

                {{-- Hapus file individual --}}
                <form method="POST"
                      action="{{ route('filament.document-file.destroy', $file->id) }}"
                      onsubmit="return confirm('Hapus file ini?')"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700 border border-red-300 dark:border-red-700 rounded px-2 py-1">
                        <x-heroicon-m-trash class="w-4 h-4"/>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="text-center py-8">
            <x-heroicon-o-document class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"/>
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada file yang diupload</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Tutup modal ini dan klik Edit untuk menambah file</p>
        </div>
    @endforelse

    @if ($document->files->count() > 0)
        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ $document->files->count() }} / 5 file terpakai
            </p>
        </div>
    @endif
</div>