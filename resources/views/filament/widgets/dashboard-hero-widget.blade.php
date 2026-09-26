<x-filament-widgets::widget>
    @php
        $user = $this->getViewData()['user'];
        $salam = $this->getViewData()['salam'];
        $tanggalHariIni = $this->getViewData()['tanggalHariIni'];
        $periodeLabel = $this->getViewData()['periodeLabel'];
        $cutoffDate = $this->getViewData()['cutoffDate'];
        $cutoffStatus = $this->getViewData()['cutoffStatus'];
        $isPastCutoff = $this->getViewData()['isPastCutoff'];
        $sisaWaktuText = $this->getViewData()['sisaWaktuText'];
        $kasiLaporanV2 = $this->getViewData()['kasiLaporanV2'];
        $kasiStatusBadge = $this->getViewData()['kasiStatusBadge'];
        $mandatoryTotal = $this->getViewData()['mandatoryTotal'];
        $approvedUnits = $this->getViewData()['approvedUnits'];
        $totalPendingSekmat = $this->getViewData()['totalPendingSekmat'];
        $camatStatusLabel = $this->getViewData()['camatStatusLabel'];
        $camatStatusColor = $this->getViewData()['camatStatusColor'];

        $isCamat = $user?->isCamat();
        $isSekmat = $user?->isAdminKecamatan();
        $isKasi = $user?->isKasi();
        $isSuperadmin = $user?->isSuperAdmin();

        $roleLabel = match (true) {
            $isCamat => 'Plt. Camat Malangbong',
            $isSekmat => 'Sekretaris Camat / Verifikator',
            $isKasi => $user?->jabatan ?? 'Kepala Seksi / Kasubag',
            $isSuperadmin => 'Superadmin IT',
            default => 'Aparatur Kecamatan',
        };

        $roleBadgeGradient = match (true) {
            $isCamat => 'from-amber-400 via-amber-500 to-yellow-600',
            $isSekmat => 'from-blue-500 via-indigo-600 to-indigo-700',
            $isKasi => 'from-emerald-500 via-teal-600 to-emerald-700',
            $isSuperadmin => 'from-purple-500 via-violet-600 to-indigo-700',
            default => 'from-slate-600 to-slate-700',
        };

        $userInitials = collect(explode(' ', $user?->name ?? 'User'))
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
    @endphp

    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-6 sm:p-7 text-white shadow-xl shadow-slate-900/10 border border-slate-700/60 dark:border-slate-800">
        <!-- Ambient Background Glows -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-16 h-48 w-48 rounded-full bg-teal-500/10 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <!-- Left Column: User Profile & Greeting -->
            <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                <!-- User Avatar / Emblem -->
                <div class="relative flex-shrink-0">
                    <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-gradient-to-tr {{ $roleBadgeGradient }} p-0.5 shadow-lg">
                        <div class="flex h-full w-full items-center justify-center rounded-[14px] bg-slate-900/90 text-white font-bold text-lg sm:text-xl">
                            {{ $userInitials }}
                        </div>
                    </div>
                    <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-slate-900"></span>
                    </span>
                </div>

                <!-- Text Info -->
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gradient-to-r {{ $roleBadgeGradient }} text-white shadow-sm">
                            @if($isCamat)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            @elseif($isSekmat)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            @elseif($isKasi)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            @endif
                            {{ $roleLabel }}
                        </span>

                        <span class="text-xs text-slate-400 font-medium">
                            • {{ $tanggalHariIni }}
                        </span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                        {{ $salam }}, <span class="text-emerald-400">{{ $user?->name }}</span>
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-300 flex flex-wrap items-center gap-x-2 gap-y-1">
                        @if($user?->nip)
                            <span class="text-slate-400 font-mono text-xs">NIP. {{ $user->nip }}</span>
                        @endif
                        @if($user?->unitOrganisasi)
                            <span class="inline-flex items-center gap-1 text-emerald-300 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                {{ $user->unitOrganisasi->nama_unit }}
                            </span>
                        @else
                            <span class="text-slate-400">Pemerintah Kecamatan Malangbong</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Right Column: Status Badges & Quick Action Buttons -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Status Chip Contextual -->
                <div class="flex items-center justify-between sm:justify-start gap-2 bg-slate-800/80 backdrop-blur-md px-3.5 py-2 rounded-xl border border-slate-700/80">
                    <div class="text-left">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Periode Aktif</p>
                        <p class="text-xs font-bold text-white flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            {{ $periodeLabel }}
                        </p>
                    </div>

                    <div class="h-7 w-[1px] bg-slate-700 mx-1"></div>

                    <div class="text-left">
                        @if($isKasi)
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Status Laporan Unit</p>
                            <span class="inline-flex items-center text-xs font-bold text-{{ $kasiStatusBadge['color'] ?? 'emerald' }}-400">
                                {{ $kasiStatusBadge['label'] ?? 'Siap Dilaporkan' }}
                            </span>
                        @elseif($isSekmat || $isSuperadmin)
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Antrean Verifikasi</p>
                            <span class="inline-flex items-center gap-1 text-xs font-bold {{ $totalPendingSekmat > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                                @if($totalPendingSekmat > 0)
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </span>
                                @endif
                                {{ $totalPendingSekmat }} Laporan Masuk
                            </span>
                        @elseif($isCamat)
                            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kompilasi Kecamatan</p>
                            <span class="inline-flex items-center text-xs font-bold text-{{ $camatStatusColor }}-400">
                                {{ $camatStatusLabel }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-2">
                    @if($isKasi)
                        <a href="/admin/laporan-kinerja-v2s/create"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 shadow-md shadow-emerald-900/40 hover:-translate-y-0.5 active:scale-95 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Buat Laporan (V2)
                        </a>

                        <a href="/admin/laporan-kinerja-v2s"
                           class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl font-medium text-xs sm:text-sm text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 hover:border-slate-600 transition-all">
                            Riwayat
                        </a>
                    @elseif($isSekmat)
                        <a href="/admin/verifikasi-laporan-unit"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-md shadow-indigo-900/40 hover:-translate-y-0.5 active:scale-95 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Telaah Pengajuan
                        </a>

                        <a href="/admin/jadwal-cutoffs"
                           class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl font-medium text-xs sm:text-sm text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 hover:border-slate-600 transition-all">
                            Jadwal Cut-Off
                        </a>
                    @elseif($isCamat)
                        <a href="/admin/laporan-kecamatans"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-white bg-gradient-to-r from-amber-500 to-yellow-600 hover:from-amber-400 hover:to-yellow-500 shadow-md shadow-amber-900/40 hover:-translate-y-0.5 active:scale-95 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Pengesahan Camat
                        </a>
                    @else
                        <a href="/admin/users"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-md shadow-purple-900/40 hover:-translate-y-0.5 active:scale-95 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Kelola User
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
