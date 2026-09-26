<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
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

        // Cari header dan detail V1
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $detail = null;
        if ($laporanHeader) {
            $detail = LaporanDetail::where('laporan_id', $laporanHeader->id)
                ->where('unit_organisasi_id', $user->unit_organisasi_id)
                ->first();
        }

        // Cari Laporan V2 untuk unit ini pada periode yang dipilih
        $laporanV2 = LaporanKinerjaV2::where('unit_organisasi_id', $user->unit_organisasi_id)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->latest()
            ->first();

        // 1. Stat Status Laporan Unit (Harmonisasi V2 & V1)
        if ($laporanV2) {
            $statusLabel = $laporanV2->status_label;
            $statusColor = $laporanV2->status_color;
            $statusDesc = match ($laporanV2->status) {
                'draft' => 'Draft V2 belum diajukan ke Sekmat',
                'diajukan' => 'Terkunci: sedang ditelaah verifikator',
                'disetujui' => 'Telah disetujui & disahkan',
                'ditolak' => 'Perlu revisi: cek catatan perbaikan',
                default => 'Laporan kinerja unit',
            };
            $chartData = match ($laporanV2->status) {
                'disetujui' => [2, 4, 6, 8, 10, 12],
                'diajukan' => [3, 5, 4, 6, 7, 8],
                'ditolak' => [6, 5, 4, 3, 2, 1],
                default => [1, 2, 2, 3, 3, 4],
            };

            $statusStat = Stat::make('Status Laporan Unit', $statusLabel)
                ->description($statusDesc)
                ->descriptionIcon($laporanV2->isDisetujui() ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                ->color($statusColor)
                ->chart($chartData);
        } elseif ($detail) {
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

            $chartData = match ($detail->status) {
                'disetujui' => [2, 4, 6, 8, 10, 12],
                'diajukan' => [3, 5, 4, 6, 7, 8],
                'ditolak' => [6, 5, 4, 3, 2, 1],
                default => [1, 2, 2, 3, 3, 4],
            };

            $statusStat = Stat::make('Status Laporan Unit', $statusLabel)
                ->description($desc)
                ->descriptionIcon($detail->status === 'disetujui' ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                ->color($statusColor)
                ->chart($chartData);
        } else {
            $statusStat = Stat::make('Status Laporan Unit', 'Belum Dibuat')
                ->description("Periode {$namaBulan} belum memiliki draf")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([0, 0, 0, 0, 0, 0]);
        }

        // 2. Stat Batas Waktu Cut-Off (Dinamis & Custom)
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $now = now();
        $isPast = $now->isAfter($cutoffDate);
        $cutoffStatus = $laporanHeader?->cutoff_status ?? 'otomatis';

        if ($detail && $detail->hasActiveDispensasi()) {
            $cutoffStat = Stat::make('Batas Cut-Off (Dispensasi)', 'Dibuka Khusus')
                ->description("Dispensasi s.d {$detail->dispensasi_sampai?->translatedFormat('d M Y H:i')}")
                ->descriptionIcon('heroicon-m-key')
                ->color('warning')
                ->chart([2, 5, 3, 6, 4, 8]);
        } elseif ($cutoffStatus === 'terbuka') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Dibuka Bebas')
                ->description('Akses pengisian dibuka manual oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success')
                ->chart([5, 6, 7, 8, 9, 10]);
        } elseif ($cutoffStatus === 'tertutup') {
            $cutoffStat = Stat::make('Status Cut-Off', 'Ditutup Manual')
                ->description('Akses pengisian dikunci oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger')
                ->chart([10, 8, 6, 4, 2, 0]);
        } elseif ($isPast) {
            $diffText = $cutoffDate->diffForHumans($now);
            $cutoffStat = Stat::make('Batas Cut-Off', 'Sudah Berakhir')
                ->description("Batas akhir lewat {$diffText} (Terkunci)")
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger')
                ->chart([5, 4, 3, 2, 1, 0]);
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
                ->color($days <= 3 ? 'warning' : 'success')
                ->chart([10, 8, 7, 6, 4, max(1, $days)]);
        }

        // 3. Stat Indikator & Serapan Anggaran / Kelengkapan
        if ($detail) {
            $indCount = $detail->indikators()->count();
            $avgFisik = $indCount > 0 ? round($detail->indikators()->avg('persentase_kinerja') ?? 0, 1) : 0;
            $sumBelanja = $detail->indikators()->sum('realisasi_anggaran') ?? 0;

            $indikatorStat = Stat::make('Kinerja & Serapan Belanja', "{$avgFisik}% Fisik")
                ->description("{$indCount} Indikator | Realisasi: Rp ".number_format($sumBelanja, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgFisik >= 90 ? 'success' : ($avgFisik >= 60 ? 'info' : 'warning'))
                ->chart([50, 60, 72, 80, 88, (int) $avgFisik]);
        } elseif ($laporanV2) {
            $filesCount = is_array($laporanV2->bukti_dukung) ? count($laporanV2->bukti_dukung) : 0;
            $indikatorStat = Stat::make('Pelaporan Versi 2', "{$filesCount} Berkas")
                ->description("Tanggal: {$laporanV2->tanggal_pelaporan?->format('d/m/Y')} • Terlampir")
                ->descriptionIcon('heroicon-m-paper-clip')
                ->color('info')
                ->chart([1, 2, 2, 3, 3, max(1, $filesCount)]);
        } else {
            $indikatorStat = Stat::make('Rencana Aksi Unit', '0 Indikator')
                ->description('Belum ada capaian fisik/anggaran yang diisi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0]);
        }

        // 4. Stat Bukti Dukung & Arsip Digital Unit
        $v1DocsCount = $detail ? $detail->dokumens()->count() : 0;
        $v2DocsCount = ($laporanV2 && is_array($laporanV2->bukti_dukung)) ? count($laporanV2->bukti_dukung) : 0;
        $totalBerkas = $v1DocsCount + $v2DocsCount;

        $dokumenStat = Stat::make('Bukti Dukung & Lampiran', "{$totalBerkas} Dokumen")
            ->description($totalBerkas > 0 ? 'Berkas bukti dukung fisik terunggah aman' : 'Belum mengunggah berkas pendukung')
            ->descriptionIcon('heroicon-m-folder-arrow-down')
            ->color($totalBerkas > 0 ? 'success' : 'gray')
            ->chart([0, 1, 1, 2, 2, max(0, $totalBerkas)]);

        return [
            $statusStat,
            $cutoffStat,
            $indikatorStat,
            $dokumenStat,
        ];
    }
}
