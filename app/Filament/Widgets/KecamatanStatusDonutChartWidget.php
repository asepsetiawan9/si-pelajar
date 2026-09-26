<?php

namespace App\Filament\Widgets;

use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KecamatanStatusDonutChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Distribusi Status Pelaporan Unit';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'xl' => 5,
    ];

    protected static ?string $maxHeight = '320px';

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

        return "Komposisi status akuntabilitas pelaporan 7 unit kerja pada periode {$namaBulan}.";
    }

    protected function getData(): array
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);

        $units = UnitOrganisasi::where('wajib_dilaporkan', true)->get();
        $totalUnits = $units->count();

        $reports = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get()
            ->keyBy('unit_organisasi_id');

        $disetujui = 0;
        $diajukan = 0;
        $draft = 0;
        $ditolak = 0;
        $belumLapor = 0;

        foreach ($units as $unit) {
            $rep = $reports->get($unit->id);
            if (! $rep) {
                $belumLapor++;
            } else {
                match ($rep->status) {
                    'disetujui' => $disetujui++,
                    'diajukan' => $diajukan++,
                    'ditolak' => $ditolak++,
                    default => $draft++,
                };
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Unit',
                    'data' => [$disetujui, $diajukan, $ditolak, $draft, $belumLapor],
                    'backgroundColor' => [
                        '#10b981', // Disetujui (Emerald)
                        '#0ea5e9', // Diajukan (Sky)
                        '#f43f5e', // Ditolak / Revisi (Rose)
                        '#f59e0b', // Draft (Amber)
                        '#94a3b8', // Belum Lapor (Slate)
                    ],
                    'hoverBackgroundColor' => [
                        '#059669',
                        '#0284c7',
                        '#e11d48',
                        '#d97706',
                        '#64748b',
                    ],
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 6,
                ],
            ],
            'labels' => [
                "Disetujui ({$disetujui})",
                "Perlu Verifikasi ({$diajukan})",
                "Perlu Revisi ({$ditolak})",
                "Draft ({$draft})",
                "Belum Lapor ({$belumLapor})",
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 14,
                        'font' => [
                            'family' => 'Plus Jakarta Sans',
                            'weight' => '600',
                            'size' => 11,
                        ],
                    ],
                ],
                'tooltip' => [
                    'padding' => 10,
                    'cornerRadius' => 8,
                    'boxPadding' => 4,
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'cutout' => '68%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
