<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $unit = $data['unit'] ?? null;
        $tahun = $data['tahun'] ?? now()->year;
        $matrix = $data['matrix'] ?? [];
        $totalApproved = $data['totalApproved'] ?? 0;
        $checklist = $data['checklist'] ?? [];
        $activeReport = $data['activeReport'] ?? null;
    @endphp

    <div class="relative overflow-hidden rounded-2xl bg-white/95 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm backdrop-blur-md">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 dark:border-slate-800/80 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Kesiapan & Rekam Jejak Unit</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Monitoring ketertiban pelaporan TA {{ $tahun }}</p>
                </div>
            </div>

            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">
                {{ $totalApproved }}/12 Sah
            </span>
        </div>

        <!-- 12-Month Matrix Track -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Matriks Bulan (12 Bulan)</span>
                <span class="text-[10px] text-slate-400">Klik bulan aktif untuk detail</span>
            </div>

            <div class="grid grid-cols-4 sm:grid-cols-6 gap-1.5">
                @foreach($matrix as $m)
                    @php
                        $dotBg = match($m['status']) {
                            'disetujui' => '#10b981',
                            'diajukan' => '#0ea5e9',
                            'ditolak' => '#f43f5e',
                            'draft' => '#f59e0b',
                            default => '#cbd5e1',
                        };

                        $borderClass = $m['is_current']
                            ? 'ring-2 ring-emerald-500 dark:ring-emerald-400 shadow-sm'
                            : 'border border-slate-200/70 dark:border-slate-700/60';
                    @endphp

                    <div class="flex flex-col items-center justify-center p-1.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/40 {{ $borderClass }} text-center transition-all hover:scale-105">
                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">{{ $m['name'] }}</span>
                        <span class="mt-1 h-2 w-2 rounded-full" style="background-color: {{ $dotBg }} !important;"></span>
                    </div>
                @endforeach
            </div>

            <!-- Legend Dots -->
            <div class="flex flex-wrap items-center justify-between gap-1 mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Disetujui</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span> Telaah</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Draft</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Revisi</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span> Belum</span>
            </div>
        </div>

        <!-- Checklist Kesiapan -->
        <div class="space-y-2">
            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block mb-1">Checklist Pengisian Periode Ini</span>

            @foreach($checklist as $item)
                <div class="flex items-start gap-2.5 p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/50">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($item['passed'])
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-white">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        @else
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-400">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-none">{{ $item['title'] }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 truncate">{{ $item['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Quick Action Footer -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
            @if(! $activeReport)
                <a href="/admin/laporan-kinerja-v2s/create"
                   class="flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 shadow-sm transition-all hover:scale-[1.01]">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Buat Laporan Periode Ini
                </a>
            @elseif($activeReport->isDraft() || $activeReport->isDitolak())
                <a href="/admin/laporan-kinerja-v2s/{{ $activeReport->id }}/edit"
                   class="flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 shadow-sm transition-all hover:scale-[1.01]">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Lengkapi & Ajukan Laporan
                </a>
            @else
                <a href="/admin/laporan-kinerja-v2s/{{ $activeReport->id }}"
                   class="flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Lihat Berkas Laporan Resmi
                </a>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
