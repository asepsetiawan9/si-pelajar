<?php

namespace App\Filament\Widgets;

use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class KasiSubmissionTrackerWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.kasi-submission-tracker-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'xl' => 4,
    ];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->isKasi();
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->unit_organisasi_id) {
            return [];
        }

        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();

        // Ambil semua laporan V2 unit ini untuk tahun berjalan
        $yearlyReports = LaporanKinerjaV2::where('unit_organisasi_id', $user->unit_organisasi_id)
            ->where('periode_tahun', $tahun)
            ->get()
            ->keyBy('periode_bulan');

        $activeReport = $yearlyReports->get($bulan);

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $matrix = [];
        $totalApproved = 0;
        $totalSubmitted = 0;

        foreach ($monthNames as $m => $name) {
            $rep = $yearlyReports->get($m);
            $status = $rep ? $rep->status : 'belum';

            if ($status === 'disetujui') {
                $totalApproved++;
            }
            if (in_array($status, ['diajukan', 'disetujui'])) {
                $totalSubmitted++;
            }

            $matrix[] = [
                'month' => $m,
                'name' => $name,
                'is_current' => $m === $bulan,
                'status' => $status,
                'color' => match ($status) {
                    'disetujui' => 'emerald',
                    'diajukan' => 'sky',
                    'ditolak' => 'rose',
                    'draft' => 'amber',
                    default => 'slate',
                },
                'report_id' => $rep?->id,
            ];
        }

        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
        $cutoffSafe = now()->isBefore($cutoffDate);

        $checklist = [
            [
                'title' => 'Profil & Identitas Pejabat Lengkap',
                'desc' => "NIP: {$user->nip} • {$user->jabatan}",
                'passed' => ! empty($user->nip) && ! empty($user->jabatan),
            ],
            [
                'title' => 'Laporan Periode Terpilih Telah Dibuat',
                'desc' => $activeReport ? $activeReport->judul_pelaporan : 'Belum membuat draf laporan',
                'passed' => $activeReport !== null,
            ],
            [
                'title' => 'Berkas Bukti Dukung Digital Terlampir',
                'desc' => $activeReport && $activeReport->bukti_dukung_count > 0 ? "{$activeReport->bukti_dukung_count} Berkas terunggah" : 'Belum ada bukti dukung fisik',
                'passed' => $activeReport && $activeReport->bukti_dukung_count > 0,
            ],
            [
                'title' => 'Status Batas Waktu Pengisian Aman',
                'desc' => $cutoffSafe ? 'Masih dalam tenggat waktu pengisian' : 'Telah melewati batas tanggal 10',
                'passed' => $cutoffSafe,
            ],
        ];

        return [
            'unit' => $user->unitOrganisasi,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'matrix' => $matrix,
            'totalApproved' => $totalApproved,
            'totalSubmitted' => $totalSubmitted,
            'checklist' => $checklist,
            'activeReport' => $activeReport,
        ];
    }
}
