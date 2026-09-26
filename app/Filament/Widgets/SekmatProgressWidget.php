<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SekmatProgressWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?int $columns = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdminKecamatan() || $user->isSuperAdmin());
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

        $approvedCount = $v2Reports->where('status', 'disetujui')->pluck('unit_organisasi_id')->unique()->count();
        $submittedCount = $v2Reports->where('status', 'diajukan')->count();
        $allApproved = ($mandatoryTotal > 0 && $approvedCount >= $mandatoryTotal);

        // 1. Stat Progress Verifikasi 7 Unit Kerja
        $progressText = "{$approvedCount} / {$mandatoryTotal} Unit Disetujui";
        $progressDesc = $allApproved
            ? 'Seluruh 7 unit kerja telah tuntas diverifikasi resmi'
            : ($v2Reports->isNotEmpty()
                ? 'Menunggu penyelesaian verifikasi '.($mandatoryTotal - $approvedCount).' unit lagi'
                : "Belum ada laporan unit masuk untuk {$namaBulan}");

        $progressStat = Stat::make('Progres Verifikasi Unit', $progressText)
            ->description($progressDesc)
            ->descriptionIcon($allApproved ? 'heroicon-m-check-badge' : 'heroicon-m-arrow-path')
            ->color($allApproved ? 'success' : ($approvedCount > 0 ? 'warning' : 'gray'))
            ->chart([1, 2, 3, 4, 5, max(1, $approvedCount)]);

        // 2. Stat Antrean Menunggu Verifikasi
        $totalAntrean = LaporanKinerjaV2::where('status', 'diajukan')->count();
        $queueDesc = $totalAntrean > 0
            ? "{$totalAntrean} berkas laporan unit perlu ditelaah Sekmat"
            : 'Tidak ada antrean telaah saat ini (Selesai)';

        $queueStat = Stat::make('Antrean Verifikasi Sekmat', "{$totalAntrean} Berkas")
            ->description($queueDesc)
            ->descriptionIcon('heroicon-m-document-magnifying-glass')
            ->color($totalAntrean > 0 ? 'info' : 'gray')
            ->chart([5, 4, 3, 2, $totalAntrean]);

        // 3. Stat Batas Waktu Cut-Off (Dinamis & Custom)
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $now = now();
        $isPast = $now->isAfter($cutoffDate);
        $cutoffStatus = $laporanHeader?->cutoff_status ?? 'otomatis';

        if ($cutoffStatus === 'terbuka') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Dibuka Bebas')
                ->description('Akses pengisian dibuka manual untuk semua unit')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success')
                ->chart([4, 6, 7, 8, 9, 10]);
        } elseif ($cutoffStatus === 'tertutup') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Ditutup Manual')
                ->description('Akses pengisian dikunci manual oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger')
                ->chart([10, 8, 6, 4, 2, 0]);
        } elseif ($isPast) {
            $cutoffStat = Stat::make('Status Cut-Off', 'Sudah Berakhir')
                ->description('Batas waktu pelaporan tanggal 10 telah lewat')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([6, 5, 4, 3, 2, 0]);
        } else {
            $diffInSeconds = max(0, (int) $now->diffInSeconds($cutoffDate, false));
            $days = (int) floor($diffInSeconds / 86400);
            $hours = (int) floor(($diffInSeconds % 86400) / 3600);
            $minutes = (int) floor(($diffInSeconds % 3600) / 60);

            if ($days > 0) {
                $descTime = $hours > 0 ? "Tersisa {$days} hari {$hours} jam" : "Tersisa {$days} hari";
            } elseif ($hours > 0) {
                $descTime = $minutes > 0 ? "Tersisa {$hours} jam {$minutes} menit" : "Tersisa {$hours} jam";
            } else {
                $descTime = "Tersisa {$minutes} menit";
            }

            $jamWib = $cutoffDate->format('H:i').' WIB';
            $cutoffStat = Stat::make('Batas Waktu Cut-Off', $cutoffDate->translatedFormat('d M Y'))
                ->description("{$descTime} menuju batas akhir (Pukul {$jamWib})")
                ->descriptionIcon('heroicon-m-clock')
                ->color($days <= 3 ? 'warning' : 'success')
                ->chart([12, 10, 8, 6, 4, max(1, $days)]);
        }

        // 4. Stat Berkas Bukti Dukung Masuk (Menggantikan Serapan Anggaran)
        $totalBerkas = 0;
        foreach ($v2Reports as $report) {
            $totalBerkas += $report->bukti_dukung_count;
        }

        $berkasStat = Stat::make('Bukti Dukung Terkumpul', "{$totalBerkas} Berkas Dokumen")
            ->description("Arsip pertanggungjawaban fisik kegiatan {$namaBulan}")
            ->descriptionIcon('heroicon-m-folder-arrow-down')
            ->color($totalBerkas > 0 ? 'success' : 'gray')
            ->chart([3, 5, 8, 10, 12, max(1, $totalBerkas)]);

        return [
            $progressStat,
            $queueStat,
            $cutoffStat,
            $berkasStat,
        ];
    }
}
