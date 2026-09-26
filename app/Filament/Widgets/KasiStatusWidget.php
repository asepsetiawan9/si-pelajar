<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

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

        // Cari Laporan V2 untuk unit ini pada periode yang dipilih
        $laporanV2 = LaporanKinerjaV2::where('unit_organisasi_id', $user->unit_organisasi_id)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->latest()
            ->first();

        // 1. Stat Status Laporan Unit
        if ($laporanV2) {
            $statusLabel = $laporanV2->status_label;
            $statusColor = $laporanV2->status_color;
            $statusDesc = match ($laporanV2->status) {
                'draft' => 'Draft belum dikirim ke Sekmat',
                'diajukan' => 'Terkunci: sedang ditelaah verifikator',
                'disetujui' => 'Laporan kinerja resmi disetujui',
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
                ->descriptionIcon($laporanV2->isDisetujui() ? 'heroicon-m-check-badge' : ($laporanV2->isDiajukan() ? 'heroicon-m-clock' : 'heroicon-m-document-text'))
                ->color($statusColor)
                ->chart($chartData);
        } else {
            $statusStat = Stat::make('Status Laporan Unit', 'Belum Dibuat')
                ->description("Belum ada laporan untuk {$namaBulan}")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([0, 0, 0, 0, 0, 0]);
        }

        // 2. Stat Batas Waktu Pengisian (Cut-Off Dinamis)
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $cutoffStatus = $laporanHeader?->cutoff_status ?? 'otomatis';
        $now = now();
        $isPast = $now->isAfter($cutoffDate);

        if ($cutoffStatus === 'terbuka') {
            $cutoffStat = Stat::make('Batas Pengisian', 'Dibuka Bebas')
                ->description('Akses pengisian dibuka manual oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-open')
                ->color('success')
                ->chart([3, 5, 6, 8, 9, 10]);
        } elseif ($cutoffStatus === 'tertutup') {
            $cutoffStat = Stat::make('Batas Pengisian', 'Ditutup Manual')
                ->description('Akses pengisian dikunci sementara oleh Sekmat')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger')
                ->chart([10, 8, 6, 4, 2, 0]);
        } elseif ($isPast) {
            $cutoffStat = Stat::make('Batas Pengisian', 'Telah Berakhir')
                ->description('Batas waktu tanggal 10 telah terlewati')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
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

        // 3. Stat Agenda / Laporan Terkirim (Menggantikan Realisasi)
        if ($laporanV2) {
            $judulSingkat = Str::limit($laporanV2->judul_pelaporan, 32);
            $tgl = $laporanV2->tanggal_pelaporan ? $laporanV2->tanggal_pelaporan->format('d/m/Y') : '-';

            $agendaStat = Stat::make('Agenda Dilaporkan', $judulSingkat)
                ->description("Tanggal: {$tgl} • Oleh: {$laporanV2->nama_pejabat}")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->chart([2, 4, 5, 7, 8, 10]);
        } else {
            $agendaStat = Stat::make('Agenda Pelaporan', 'Belum Ada Laporan')
                ->description('Silakan klik + Buat Laporan Kinerja untuk periode ini')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0]);
        }

        // 4. Stat Berkas Bukti Dukung Terlampir
        $filesCount = $laporanV2 ? $laporanV2->bukti_dukung_count : 0;

        $dokumenStat = Stat::make('Bukti Dukung & Lampiran', "{$filesCount} Berkas Terunggah")
            ->description($filesCount > 0 ? 'Berkas pertanggungjawaban fisik digital aman' : 'Belum mengunggah berkas bukti dukung')
            ->descriptionIcon('heroicon-m-paper-clip')
            ->color($filesCount > 0 ? 'success' : 'gray')
            ->chart([0, 1, 2, 2, 3, max(0, $filesCount)]);

        return [
            $statusStat,
            $cutoffStat,
            $agendaStat,
            $dokumenStat,
        ];
    }
}
