<?php

namespace App\Filament\Widgets;

use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KecamatanPerformanceChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Grafik Aktivitas Pelaporan & Kelengkapan Bukti Dukung 7 Unit';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '360px';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isCamat() || $user->isAdminKecamatan() || $user->isSuperAdmin());
    }

    public function getDescription(): ?string
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $namaBulan = $bulanDate->translatedFormat('F Y');

        $count = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->count();

        if ($count === 0) {
            return "Belum ada rekaman laporan kinerja untuk periode {$namaBulan}. Grafik akan terisi otomatis saat unit kerja memasukkan laporan.";
        }

        return "Perbandingan volume laporan dan dokumen bukti dukung fisik terunggah seluruh seksi/subbag periode {$namaBulan}.";
    }

    protected function getData(): array
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);

        $units = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();

        // Prefetch Laporan V2 untuk periode ini
        $v2Map = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get()
            ->groupBy('unit_organisasi_id');

        $labels = [];
        $berkasData = [];
        $laporanData = [];

        foreach ($units as $unit) {
            $shortName = match ($unit->kode_unit) {
                'SUBBAG-UMUM' => 'Subbag Umum',
                'SUBBAG-KEUANGAN' => 'Subbag Keuangan',
                'SEKSI-PEMERINTAHAN' => 'Seksi Pemerintahan',
                'SEKSI-KESRA' => 'Seksi Kesra',
                'SEKSI-PMD' => 'Seksi PMD',
                'SEKSI-TRANTIB' => 'Seksi Trantib',
                'SEKSI-PELAYANAN' => 'Seksi Pelayanan',
                default => $unit->nama_unit,
            };

            $labels[] = $shortName;

            $reports = $v2Map->get($unit->id);

            if (! $reports || $reports->isEmpty()) {
                $berkasData[] = 0;
                $laporanData[] = 0;
            } else {
                $totalFiles = 0;
                foreach ($reports as $r) {
                    $totalFiles += $r->bukti_dukung_count;
                }

                $berkasData[] = $totalFiles;
                $laporanData[] = $reports->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Berkas Bukti Dukung (Dokumen)',
                    'data' => $berkasData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)', // Emerald-500
                    'borderColor' => '#059669',
                    'borderWidth' => 1.5,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Laporan / Agenda Terkirim',
                    'data' => $laporanData,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.85)', // Indigo-500
                    'borderColor' => '#4f46e5',
                    'borderWidth' => 1.5,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
