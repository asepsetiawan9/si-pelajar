<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\UnitOrganisasi;
use App\Repositories\LaporanRepository;
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

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $mandatoryTotal = UnitOrganisasi::where('wajib_dilaporkan', true)->count();

        $repo = app(LaporanRepository::class);
        $stats = $laporan ? $repo->getStatistikKecamatan($laporan) : [
            'rata_rata_efektivitas' => 0,
            'predikat_efektivitas' => 'belum_ada',
            'total_pagu' => 0,
            'total_realisasi' => 0,
            'persentase_serapan' => 0,
            'predikat_efisiensi' => 'belum_ada',
            'total_unit_disetujui' => 0,
            'total_unit_wajib' => $mandatoryTotal,
        ];

        // 1. Stat Efektivitas Kinerja Kecamatan
        $efektivitasVal = $stats['rata_rata_efektivitas'];
        $predikatEfektivitas = $laporan ? match ($stats['predikat_efektivitas']) {
            'sangat_efektif' => 'Sangat Efektif (Istimewa)',
            'efektif' => 'Efektif (Baik)',
            'cukup_efektif' => 'Cukup Efektif',
            'tidak_efektif' => 'Tidak Efektif',
            default => 'Belum Ada Data',
        } : "Menunggu penginputan laporan periode {$namaBulan}";

        $efektivitasStat = Stat::make('Efektivitas Kinerja Kecamatan', "{$efektivitasVal}%")
            ->description($predikatEfektivitas)
            ->descriptionIcon('heroicon-m-sparkles')
            ->color($laporan ? ($efektivitasVal >= 90 ? 'success' : ($efektivitasVal >= 60 ? 'info' : 'warning')) : 'gray');

        // 2. Stat Serapan Belanja Kecamatan
        $serapanPersen = $stats['persentase_serapan'];
        $realisasiRp = 'Rp '.number_format($stats['total_realisasi'], 0, ',', '.');
        $paguRp = 'Rp '.number_format($stats['total_pagu'], 0, ',', '.');
        $predikatEfisiensi = match ($stats['predikat_efisiensi']) {
            'sangat_efisien' => 'Sangat Efisien (< 60%)',
            'efisien' => 'Efisien (60-90%)',
            'cukup_efisien' => 'Cukup Efisien (91-100%)',
            'tidak_efisien' => 'Tidak Efisien (> 100%)',
            default => 'Belum Ada Data',
        };

        $belanjaStat = Stat::make('Realisasi Belanja Kecamatan', $realisasiRp)
            ->description($laporan ? "{$serapanPersen}% Serapan (Pagu: {$paguRp}) — {$predikatEfisiensi}" : "Belum ada data belanja untuk {$namaBulan}")
            ->descriptionIcon('heroicon-m-banknotes')
            ->color($laporan && $serapanPersen > 0 && $serapanPersen <= 100 ? 'success' : 'gray');

        // 3. Stat Status Pengesahan Kompilasi
        if (! $laporan) {
            $statusSah = 'Belum Diproses';
            $descSah = 'Belum ada draf kompilasi kecamatan untuk bulan ini';
            $colorSah = 'gray';
            $iconSah = 'heroicon-m-x-circle';
        } else {
            $statusSah = match ($laporan->status) {
                'draft' => 'Draft Awal',
                'menunggu_verifikasi' => 'Verifikasi Sekmat',
                'diajukan_ke_camat' => 'Menunggu Pengesahan Anda',
                'disetujui' => 'Telah Resmi Disahkan',
                'ditolak' => 'Dikembalikan ke Sekmat',
                default => strtoupper($laporan->status),
            };

            $descSah = match ($laporan->status) {
                'diajukan_ke_camat' => 'Seluruh seksi lengkap. Siap disahkan Camat.',
                'disetujui' => 'Disahkan pada '.($laporan->disetujui_pada ? $laporan->disetujui_pada->translatedFormat('d/m/Y H:i') : '-'),
                'menunggu_verifikasi' => 'Sekmat sedang memverifikasi kontribusi seksi',
                default => 'Dalam penyusunan internal',
            };

            $colorSah = match ($laporan->status) {
                'diajukan_ke_camat' => 'warning',
                'disetujui' => 'success',
                'ditolak' => 'danger',
                default => 'info',
            };

            $iconSah = match ($laporan->status) {
                'disetujui' => 'heroicon-m-check-badge',
                'diajukan_ke_camat' => 'heroicon-m-exclamation-circle',
                default => 'heroicon-m-clock',
            };
        }

        $pengesahanStat = Stat::make('Status Pengesahan Camat', $statusSah)
            ->description($descSah)
            ->descriptionIcon($iconSah)
            ->color($colorSah);

        // 4. Stat Kepatuhan Seksi
        $approvedTotal = $stats['total_unit_disetujui'];
        $kepatuhanStat = Stat::make('Kepatuhan 7 Unit Operasional', "{$approvedTotal} / {$mandatoryTotal} Unit")
            ->description($approvedTotal >= $mandatoryTotal ? '100% Seluruh seksi telah terverifikasi' : 'Belum seluruh seksi selesai diverifikasi')
            ->descriptionIcon('heroicon-m-building-office-2')
            ->color($approvedTotal >= $mandatoryTotal ? 'success' : 'warning');

        return [
            $efektivitasStat,
            $belanjaStat,
            $pengesahanStat,
            $kepatuhanStat,
        ];
    }
}
