<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class DashboardHeroWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.dashboard-hero-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();
        $now = now();

        // Salam dinamis berbasis jam
        $hour = (int) $now->format('H');
        $salam = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 18 => 'Selamat Sore',
            default => 'Selamat Malam',
        };

        // Periode dari filter dashboard atau default
        $tahun = (int) ($this->filters['tahun'] ?? $now->year);
        $bulan = (int) ($this->filters['bulan'] ?? $now->subMonth()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $periodeLabel = $bulanDate->translatedFormat('F Y');

        // Header laporan kompilasi periode ini
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $cutoffStatus = $laporanHeader?->cutoff_status ?? 'otomatis';

        // Hitung sisa waktu cut-off
        $diffInSeconds = max(0, (int) $now->diffInSeconds($cutoffDate, false));
        $days = (int) floor($diffInSeconds / 86400);
        $hours = (int) floor(($diffInSeconds % 86400) / 3600);
        $isPastCutoff = $now->isAfter($cutoffDate);

        $sisaWaktuText = $days > 0
            ? "Tersisa {$days} hari {$hours} jam lagi"
            : ($hours > 0 ? "Tersisa {$hours} jam lagi" : 'Hari ini batas akhir');

        // Status untuk Kasi
        $kasiLaporanV2 = null;
        $kasiLaporanV1 = null;
        $kasiStatusBadge = null;

        if ($user && $user->isKasi() && $user->unit_organisasi_id) {
            $kasiLaporanV2 = LaporanKinerjaV2::where('unit_organisasi_id', $user->unit_organisasi_id)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $tahun)
                ->latest()
                ->first();

            if ($laporanHeader) {
                $kasiLaporanV1 = LaporanDetail::where('laporan_id', $laporanHeader->id)
                    ->where('unit_organisasi_id', $user->unit_organisasi_id)
                    ->first();
            }

            // Tentukan status aktif untuk Kasi
            if ($kasiLaporanV2) {
                $kasiStatusBadge = [
                    'label' => $kasiLaporanV2->status_label,
                    'color' => $kasiLaporanV2->status_color,
                    'is_v2' => true,
                ];
            } elseif ($kasiLaporanV1) {
                $statusMap = [
                    'draft' => ['label' => 'Draft Pengisian', 'color' => 'warning'],
                    'diajukan' => ['label' => 'Menunggu Verifikasi Sekmat', 'color' => 'info'],
                    'disetujui' => ['label' => 'Telah Disetujui', 'color' => 'success'],
                    'ditolak' => ['label' => 'Perlu Perbaikan / Revisi', 'color' => 'danger'],
                ];
                $kasiStatusBadge = $statusMap[$kasiLaporanV1->status] ?? ['label' => ucfirst($kasiLaporanV1->status), 'color' => 'gray'];
                $kasiStatusBadge['is_v2'] = false;
            } else {
                $kasiStatusBadge = [
                    'label' => 'Belum Ada Laporan',
                    'color' => 'danger',
                    'is_v2' => false,
                ];
            }
        }

        // Metrik untuk Sekmat & Superadmin
        $mandatoryTotal = UnitOrganisasi::where('wajib_dilaporkan', true)->count();
        $v2ApprovedCount = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status', 'disetujui')
            ->pluck('unit_organisasi_id')
            ->unique()
            ->count();
        $approvedUnits = $v2ApprovedCount ?: ($laporanHeader ? $laporanHeader->details()->where('status', 'disetujui')->count() : 0);
        $totalPendingSekmat = LaporanKinerjaV2::where('status', 'diajukan')->count();

        // Metrik untuk Camat
        if ($approvedUnits >= $mandatoryTotal) {
            $camatStatusLabel = 'Seluruh Unit Disetujui';
            $camatStatusColor = 'success';
        } elseif ($totalPendingSekmat > 0) {
            $camatStatusLabel = 'Verifikasi Sekmat';
            $camatStatusColor = 'warning';
        } else {
            $camatStatusLabel = $approvedUnits > 0 ? "{$approvedUnits} Unit Siap" : 'Belum Ada Laporan';
            $camatStatusColor = $approvedUnits > 0 ? 'info' : 'gray';
        }

        $totalDokumen = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get()
            ->sum('bukti_dukung_count');

        $progressPercentage = $mandatoryTotal > 0 ? round(($approvedUnits / $mandatoryTotal) * 100) : 0;

        return [
            'user' => $user,
            'salam' => $salam,
            'tanggalHariIni' => $now->translatedFormat('l, d F Y'),
            'periodeLabel' => $periodeLabel,
            'cutoffDate' => $cutoffDate,
            'cutoffStatus' => $cutoffStatus,
            'isPastCutoff' => $isPastCutoff,
            'sisaWaktuText' => $sisaWaktuText,
            'kasiLaporanV2' => $kasiLaporanV2,
            'kasiStatusBadge' => $kasiStatusBadge,
            'mandatoryTotal' => $mandatoryTotal,
            'approvedUnits' => $approvedUnits,
            'totalPendingSekmat' => $totalPendingSekmat,
            'camatStatusLabel' => $camatStatusLabel,
            'camatStatusColor' => $camatStatusColor,
            'totalDokumen' => $totalDokumen,
            'progressPercentage' => $progressPercentage,
        ];
    }
}
