<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\UnitOrganisasi;
use App\Repositories\LaporanRepository;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SekmatProgressWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

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
        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();

        $approvedCount = 0;
        $submittedCount = 0;
        $draftCount = 0;
        $rejectedCount = 0;

        if ($laporan) {
            $approvedCount = $laporan->details()->where('status', 'disetujui')->count();
            $submittedCount = $laporan->details()->where('status', 'diajukan')->count();
            $draftCount = $laporan->details()->where('status', 'draft')->count();
            $rejectedCount = $laporan->details()->where('status', 'ditolak')->count();
        }

        $allApproved = ($mandatoryTotal > 0 && $approvedCount >= $mandatoryTotal);

        // 1. Stat Progress 7 Unit Wajib
        $progressText = "{$approvedCount} / {$mandatoryTotal} Unit";
        $progressDesc = $laporan
            ? ($allApproved
                ? 'Seluruh 7 unit telah disetujui (Siap diajukan ke Camat)'
                : 'Menunggu kelengkapan '.($mandatoryTotal - $approvedCount).' unit lagi')
            : "Belum ada laporan unit masuk pada periode {$namaBulan}";

        $progressStat = Stat::make('Progres Verifikasi Unit', $progressText)
            ->description($progressDesc)
            ->descriptionIcon($allApproved ? 'heroicon-m-check-badge' : 'heroicon-m-arrow-path')
            ->color($laporan ? ($allApproved ? 'success' : 'warning') : 'gray');

        // 2. Stat Antrean Menunggu Verifikasi
        $queueDesc = $submittedCount > 0
            ? "{$submittedCount} laporan unit perlu verifikasi Sekmat"
            : 'Tidak ada antrean telaah saat ini';

        $queueStat = Stat::make('Antrean Verifikasi Sekmat', "{$submittedCount} Unit")
            ->description($queueDesc)
            ->descriptionIcon('heroicon-m-document-magnifying-glass')
            ->color($submittedCount > 0 ? 'info' : 'gray');

        // 3. Stat Batas Waktu Cut-Off (Dinamis & Custom)
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $now = now();
        $isPast = $now->isAfter($cutoffDate);
        $cutoffStatus = $laporan?->cutoff_status ?? 'otomatis';

        if ($cutoffStatus === 'terbuka') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Dibuka Bebas')
                ->description('Akses pengisian dibuka manual untuk semua unit')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success');
        } elseif ($cutoffStatus === 'tertutup') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Ditutup Manual')
                ->description('Akses pengisian dikunci manual oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger');
        } elseif ($isPast) {
            $lateUnits = $laporan ? $laporan->details()->where('is_late', true)->count() : 0;
            $cutoffStat = Stat::make('Status Cut-Off', 'Sudah Berakhir')
                ->description("Batas akhir terlewati ({$lateUnits} unit tercatat terlambat)")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
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
                ->color($days <= 3 ? 'warning' : 'success');
        }

        // 4. Stat Total Serapan Anggaran Kecamatan
        $repo = app(LaporanRepository::class);
        $summary = $laporan ? $repo->getStatistikKecamatan($laporan) : ['total_pagu' => 0, 'total_realisasi' => 0, 'persentase_serapan' => 0];

        $serapanFormatted = 'Rp '.number_format($summary['total_realisasi'], 0, ',', '.');
        $serapanPersen = $summary['persentase_serapan'];

        $anggaranStat = Stat::make('Serapan Belanja Periode Ini', $serapanFormatted)
            ->description("{$serapanPersen}% dari Pagu Rp ".number_format($summary['total_pagu'], 0, ',', '.'))
            ->descriptionIcon('heroicon-m-banknotes')
            ->color($serapanPersen >= 60 && $serapanPersen <= 100 ? 'success' : 'info');

        return [
            $progressStat,
            $queueStat,
            $cutoffStat,
            $anggaranStat,
        ];
    }
}
