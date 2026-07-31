@if (auth()->check() &&auth()->user()->hasRole('super_admin'))
    @php
        $replica = \App\Support\DatabaseConnection::read();
    @endphp

    <div
        class="mt-6 flex items-center justify-center gap-2 border-t border-gray-200/50 py-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400"
    >
        @if ($replica['online'])
            {{-- Indikator Titik Hijau Berkedip (Online) --}}
            <span class="relative flex h-2 w-2">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>

            <span>
                Load DB:
                <strong class="font-semibold text-gray-700 dark:text-gray-200">{{ $replica['label'] }}</strong>
            </span>
            <span class="font-mono text-gray-400 dark:text-gray-500">
                ({{ $replica['hostname'] }} &bull; ID: {{ $replica['server_id'] }})
            </span>
        @else
            {{-- Indikator Titik Merah (Offline) --}}
            <span class="relative flex h-2 w-2">
                <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
            </span>

            <span class="font-medium text-rose-500">Load DB: {{ $replica['label'] }} (Disconnected)</span>
        @endif
    </div>
@endif
