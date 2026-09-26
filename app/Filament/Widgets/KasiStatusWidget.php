<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KasiStatusWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->isKasi();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->unit_organisasi_id) {
            return [];
        }

        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $namaBulan = $bulanDate->translatedFormat('F Y');

        // Cari header dan detail
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $detail = null;
        if ($laporanHeader) {
            $detail = LaporanDetail::where('laporan_id', $laporanHeader->id)
                ->where('unit_organisasi_id', $user->unit_organisasi_id)
                ->first();
        }

        // 1. Stat Status Laporan
        if (! $detail) {
            $statusStat = Stat::make('Status Laporan Unit', 'Belum Dibuat')
                ->description("Periode {$namaBulan} belum memiliki draf")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger');
        } else {
            $statusLabel = match ($detail->status) {
                'draft' => 'Draft Pengisian',
                'diajukan' => 'Menunggu Verifikasi',
                'disetujui' => 'Telah Disetujui',
                'ditolak' => 'Perlu Perbaikan / Revisi',
                default => strtoupper($detail->status),
            };

            $statusColor = match ($detail->status) {
                'draft' => 'warning',
                'diajukan' => 'info',
                'disetujui' => 'success',
                'ditolak' => 'danger',
                default => 'gray',
            };

            $desc = match ($detail->status) {
                'draft' => 'Formulir belum diajukan ke Sekmat',
                'diajukan' => 'Terkunci: sedang ditelaah Sekmat',
                'disetujui' => 'Terverifikasi resmi oleh Sekmat',
                'ditolak' => 'Catatan revisi tersedia di formulir',
                default => '',
            };

            $statusStat = Stat::make('Status Laporan Unit', $statusLabel)
                ->description($desc)
                ->descriptionIcon($detail->status === 'disetujui' ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                ->color($statusColor);
        }

        // 2. Stat Batas Waktu Cut-Off (Dinamis & Custom)
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $now = now();
        $isPast = $now->isAfter($cutoffDate);
        $cutoffStatus = $laporan?->cutoff_status ?? 'otomatis';

        if ($detail && $detail->hasActiveDispensasi()) {
            $cutoffStat = Stat::make('Batas Cut-Off (Dispensasi)', 'Dibuka Khusus')
                ->description("Dispensasi s.d {$detail->dispensasi_sampai?->translatedFormat('d M Y H:i')}")
                ->descriptionIcon('heroicon-m-key')
                ->color('warning');
        } elseif ($cutoffStatus === 'terbuka') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Dibuka Bebas')
                ->description('Akses pengisian dibuka manual oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success');
        } elseif ($cutoffStatus === 'tertutup') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Ditutup Manual')
                ->description('Akses pengisian dikunci oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger');
        } elseif ($isPast) {
            $diffText = $cutoffDate->diffForHumans($now);
            $cutoffStat = Stat::make('Batas Cut-Off', 'Sudah Berakhir')
                ->description("Batas akhir lewat {$diffText} (Terkunci)")
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger');
        } else {
            $diffInSeconds = max(0, (int) $now->diffInSeconds($cutoffDate, false));
            $days = (int) floor($diffInSeconds / 86400);
            $hours = (int) floor(($diffInSeconds % 86400) / 3600);
            $minutes = (int) floor(($diffInSeconds % 3600) / 60);

            if ($days > 0) {
                $descTime = $hours > 0 ? "Tersisa {$days} hari {$hours} jam lagi" : "Tersisa {$days} hari lagi";
            } elseif ($hours > 0) {
                $descTime = $minutes > 0 ? "Tersisa {$hours} jam {$minutes} menit lagi" : "Tersisa {$hours} jam lagi";
            } else {
                $descTime = "Tersisa {$minutes} menit lagi";
            }

            $jamWib = $cutoffDate->format('H:i').' WIB';
            $cutoffStat = Stat::make('Batas Waktu Pengisian', $cutoffDate->translatedFormat('d F Y'))
                ->description("Pukul {$jamWib} ({$descTime})")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($days <= 3 ? 'warning' : 'success');
        }

        // 3. Stat Indikator & Serapan Anggaran
        if ($detail) {
            $indCount = $detail->indikators()->count();
            $avgFisik = $indCount > 0 ? round($detail->indikators()->avg('persentase_kinerja') ?? 0, 1) : 0;
            $sumBelanja = $detail->indikators()->sum('realisasi_anggaran') ?? 0;

            $indikatorStat = Stat::make('Kinerja & Serapan Belanja', "{$avgFisik}% Fisik")
                ->description("{$indCount} Indikator | Realisasi: Rp ".number_format($sumBelanja, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgFisik >= 90 ? 'success' : ($avgFisik >= 60 ? 'info' : 'warning'));
        } else {
            $indikatorStat = Stat::make('Rencana Aksi Unit', '0 Indikator')
                ->description('Belum ada capaian fisik/anggaran yang diisi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray');
        }

        return [
            $statusStat,
            $cutoffStat,
            $indikatorStat,
        ];
    }
}
