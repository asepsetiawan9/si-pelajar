@php
    $record = $getRecord();
    $files = $record ? $record->bukti_dukung_details : [];
@endphp

<div class="space-y-3">
    @if (!empty($files) && count($files) > 0)
        <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-700">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                Terlampir {{ count($files) }} berkas dokumen bukti dukung
            </span>
            @if (count($files) > 1)
                <a href="{{ route('spko.laporan-v2.files-zip', $record) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Unduh Semua (ZIP)
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
            @foreach ($files as $idx => $file)
                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-100/70 transition dark:border-gray-800 dark:bg-gray-900/40 dark:hover:bg-gray-800/60">
                    <div class="flex items-center gap-3 min-w-0 pr-2">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xs shrink-0
                            {{ $file['is_pdf'] ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400' :
                               ($file['is_image'] ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400') }}">
                            {{ $file['ext'] ?: 'DOC' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $file['name'] }}">
                                {{ $file['name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Berkas #{{ $idx + 1 }} • {{ $file['ext'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="{{ $file['url'] }}"
                           target="_blank"
                           class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-md border border-gray-300 shadow-sm transition dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">
                            Buka
                        </a>
                        <a href="{{ $file['url'] }}"
                           download="{{ $file['name'] }}"
                           class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-md border border-emerald-200 transition dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800">
                            Unduh
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-6 text-center rounded-xl border border-dashed border-gray-300 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-900/20">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Belum ada dokumen bukti dukung yang dilampirkan pada laporan ini.
            </p>
        </div>
    @endif
</div>
