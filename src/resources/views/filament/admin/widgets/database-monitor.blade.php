@php($data = $this->getData())

<x-filament::section>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-server-stack class="h-5 w-5 text-gray-500 dark:text-gray-400" />
            <span>Database Monitoring</span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- MASTER NODE --}}
        <div class="flex flex-col justify-between rounded-xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-900/50 dark:ring-white/10">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <x-heroicon-o-circle-stack class="h-5 w-5 text-primary-500 shrink-0" />
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white truncate">
                        Master Node
                    </h3>
                </div>

                <div class="shrink-0">
                    @if ($data['master']['online'])
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">Online</x-filament::badge>
                    @else
                        <x-filament::badge color="danger" icon="heroicon-m-x-circle">Offline</x-filament::badge>
                    @endif
                </div>
            </div>

            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-2 border-b border-gray-200 pb-2.5 dark:border-white/10">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Hostname</span>
                    <span class="font-medium text-gray-950 dark:text-white truncate max-w-[180px]" title="{{ $data['master']['hostname'] }}">
                        {{ $data['master']['hostname'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Server ID</span>
                    <span class="font-mono font-medium text-gray-950 dark:text-white">
                        {{ $data['master']['server_id'] ?? '-' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- REPLICA NODE --}}
        <div class="flex flex-col justify-between rounded-xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-900/50 dark:ring-white/10">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <x-heroicon-o-server class="h-5 w-5 text-info-500 shrink-0" />
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white truncate">
                        Replica Node
                    </h3>
                </div>

                <div class="shrink-0">
                    @if ($data['replica']['online'])
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">Online</x-filament::badge>
                    @else
                        <x-filament::badge color="danger" icon="heroicon-m-x-circle">Offline</x-filament::badge>
                    @endif
                </div>
            </div>

            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-2 border-b border-gray-200 pb-2.5 dark:border-white/10">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Hostname</span>
                    <span class="font-medium text-gray-950 dark:text-white truncate max-w-[180px]" title="{{ $data['replica']['hostname'] }}">
                        {{ $data['replica']['hostname'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Server ID</span>
                    <span class="font-mono font-medium text-gray-950 dark:text-white">
                        {{ $data['replica']['server_id'] ?? '-' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- REPLICATION --}}
        <div class="flex flex-col justify-between rounded-xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-900/50 dark:ring-white/10">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <x-heroicon-o-arrow-path class="h-5 w-5 text-warning-500 shrink-0" />
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white truncate">
                        Replication
                    </h3>
                </div>

                <div class="shrink-0">
                    @if ($data['replication']['running'])
                        <x-filament::badge color="success" icon="heroicon-m-play">Running</x-filament::badge>
                    @else
                        <x-filament::badge color="danger" icon="heroicon-m-stop">Stopped</x-filament::badge>
                    @endif
                </div>
            </div>

            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-2 border-b border-gray-200 pb-2.5 dark:border-white/10">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Delay</span>
                    <div class="flex items-center gap-1 font-mono font-medium text-gray-950 dark:text-white">
                        <span>{{ $data['replication']['delay'] ?? '0' }}</span>
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">sec</span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-500 dark:text-gray-400 shrink-0">Health</span>
                    @if (isset($data['replication']['delay']) && $data['replication']['delay'] == 0 && $data['replication']['running'])
                        <span class="flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-m-check class="h-4 w-4" />
                            Synced
                        </span>
                    @else
                        <span class="flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400">
                            <x-heroicon-m-exclamation-triangle class="h-4 w-4" />
                            Syncing / Lag
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament::section>