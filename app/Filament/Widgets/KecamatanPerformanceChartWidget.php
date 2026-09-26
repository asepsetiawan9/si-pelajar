<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KecamatanPerformanceChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Grafik Analisis Capaian Kinerja Fisik & Serapan Belanja 7 Unit';

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

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        if (! $laporan) {
            return "Belum ada rekaman laporan kinerja untuk periode {$namaBulan}. Grafik akan terisi otomatis saat unit organisasi memasukkan data.";
        }

        return "Perbandingan akuntabilitas kinerja fisik (%) dan serapan belanja (%) seluruh seksi/subbag periode {$namaBulan}.";
    }

    protected function getData(): array
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $laporanId = $laporan ? $laporan->id : 0;

        $units = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();

        // Prefetch semua detail + indikators dalam 1-2 query (eliminasi N+1)
        $detailsMap = $laporanId > 0
            ? LaporanDetail::where('laporan_id', $laporanId)
                ->with(['indikators'])
                ->get()
                ->keyBy('unit_organisasi_id')
            : collect();

        $labels = [];
        $kinerjaData = [];
        $anggaranData = [];

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

            $detail = $detailsMap->get($unit->id);

            if (! $detail || $detail->indikators->isEmpty()) {
                $kinerjaData[] = 0;
                $anggaranData[] = 0;
            } else {
                $avgKinerja = round($detail->indikators->avg('persentase_kinerja') ?? 0, 1);
                $paguTotal = $detail->indikators->sum('anggaran_pagu') ?? 0;
                $realisasiTotal = $detail->indikators->sum('realisasi_anggaran') ?? 0;
                $serapan = $paguTotal > 0 ? round(($realisasiTotal / $paguTotal) * 100, 1) : 0;

                $kinerjaData[] = $avgKinerja;
                $anggaranData[] = $serapan;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Capaian Kinerja Fisik (%)',
                    'data' => $kinerjaData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)', // Emerald-500
                    'borderColor' => '#059669',
                    'borderWidth' => 1.5,
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Serapan Belanja Anggaran (%)',
                    'data' => $anggaranData,
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
