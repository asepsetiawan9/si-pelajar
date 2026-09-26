<?php

namespace App\Filament\Widgets;

use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CamatSummaryWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isCamat() || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $namaBulan = $bulanDate->translatedFormat('F Y');

        $mandatoryTotal = UnitOrganisasi::where('wajib_dilaporkan', true)->count();

        // Ambil data Laporan V2 untuk periode ini
        $v2Reports = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get();

        $totalLaporan = $v2Reports->count();
        $approvedCount = $v2Reports->where('status', 'disetujui')->pluck('unit_organisasi_id')->unique()->count();
        $pendingCount = $v2Reports->where('status', 'diajukan')->count();
        $allApproved = ($mandatoryTotal > 0 && $approvedCount >= $mandatoryTotal);

        // Hitung total berkas bukti dukung
        $totalBerkas = 0;
        foreach ($v2Reports as $report) {
            $totalBerkas += $report->bukti_dukung_count;
        }

        // 1. Stat Kepatuhan Pelaporan 7 Unit
        $persenKepatuhan = $mandatoryTotal > 0 ? round(($approvedCount / $mandatoryTotal) * 100) : 0;
        $kepatuhanStat = Stat::make('Kepatuhan Pelaporan Unit', "{$approvedCount} / {$mandatoryTotal} Unit")
            ->description($allApproved ? '100% Seluruh unit telah tuntas diverifikasi' : "Tingkat kepatuhan: {$persenKepatuhan}% ({$namaBulan})")
            ->descriptionIcon($allApproved ? 'heroicon-m-check-badge' : 'heroicon-m-building-office-2')
            ->color($allApproved ? 'success' : ($approvedCount > 0 ? 'info' : 'warning'))
            ->chart([20, 40, 60, 80, 90, max(10, (int) $persenKepatuhan)]);

        // 2. Stat Total Agenda / Laporan Kinerja Masuk
        $laporanStat = Stat::make('Laporan Kinerja Masuk', "{$totalLaporan} Agenda")
            ->description("{$approvedCount} Disetujui • {$pendingCount} Menunggu Telaah")
            ->descriptionIcon('heroicon-m-document-check')
            ->color($totalLaporan > 0 ? 'info' : 'gray')
            ->chart([1, 2, 4, 5, 6, max(1, $totalLaporan)]);

        // 3. Stat Berkas Bukti Dukung Terkumpul
        $berkasStat = Stat::make('Arsip Bukti Dukung Digital', "{$totalBerkas} Berkas")
            ->description($totalBerkas > 0 ? "Dokumen pertanggungjawaban fisik {$namaBulan}" : 'Belum ada dokumen yang dilampirkan')
            ->descriptionIcon('heroicon-m-paper-clip')
            ->color($totalBerkas > 0 ? 'success' : 'gray')
            ->chart([2, 5, 8, 10, 14, max(1, $totalBerkas)]);

        // 4. Stat Status Verifikasi Kecamatan
        if ($allApproved) {
            $statusSah = 'Tuntas Diverifikasi';
            $descSah = 'Seluruh seksi tuntas diverifikasi oleh Sekmat';
            $colorSah = 'success';
            $iconSah = 'heroicon-m-check-badge';
            $chartSah = [4, 6, 8, 10, 12, 14];
        } elseif ($totalLaporan > 0) {
            $statusSah = 'Proses Telaah';
            $descSah = 'Sekmat sedang menelaah laporan seksi/subbag';
            $colorSah = 'warning';
            $iconSah = 'heroicon-m-clock';
            $chartSah = [2, 3, 4, 6, 7, 8];
        } else {
            $statusSah = 'Belum Ada Laporan';
            $descSah = "Menunggu penyampaian laporan periode {$namaBulan}";
            $colorSah = 'gray';
            $iconSah = 'heroicon-m-x-circle';
            $chartSah = [0, 0, 0, 0, 0, 0];
        }

        $verifikasiStat = Stat::make('Status Verifikasi Kecamatan', $statusSah)
            ->description($descSah)
            ->descriptionIcon($iconSah)
            ->color($colorSah)
            ->chart($chartSah);

        return [
            $kepatuhanStat,
            $laporanStat,
            $berkasStat,
            $verifikasiStat,
        ];
    }
}
