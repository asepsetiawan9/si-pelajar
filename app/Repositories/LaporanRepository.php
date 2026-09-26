<?php

namespace App\Repositories;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class LaporanRepository
{
    /**
     * Dapatkan Laporan header berdasarkan ID beserta relasi lengkap.
     */
    public function findWithDetails(int $id): ?Laporan
    {
        return Laporan::with([
            'diajukanOleh',
            'disetujuiOleh',
            'laporanDetails.unitOrganisasi',
            'laporanDetails.user',
            'laporanDetails.verifiedBy',
            'laporanDetails.indikators.rencanaAksi.sasaranStrategis',
            'laporanDetails.layanans',
            'laporanDetails.dokumens',
        ])->find($id);
    }

    /**
     * Dapatkan LaporanDetail berdasarkan ID beserta relasi lengkap.
     */
    public function findDetailWithRelations(int $id): ?LaporanDetail
    {
        return LaporanDetail::with([
            'laporan',
            'unitOrganisasi',
            'user',
            'verifiedBy',
            'indikators.rencanaAksi.sasaranStrategis',
            'layanans',
            'dokumens',
        ])->find($id);
    }

    /**
     * Dapatkan Laporan berdasarkan bulan pelaporan.
     */
    public function findByBulan(Carbon|string $bulan): ?Laporan
    {
        $bulanDate = $bulan instanceof Carbon ? $bulan->startOfMonth()->toDateString() : Carbon::parse($bulan)->startOfMonth()->toDateString();

        return Laporan::where('bulan_pelaporan', $bulanDate)->first();
    }

    /**
     * Query Builder untuk daftar verifikasi Sekmat (LaporanDetail).
     */
    public function getVerifikasiQuery(): Builder
    {
        return LaporanDetail::with([
            'laporan',
            'unitOrganisasi',
            'user',
            'verifiedBy',
            'indikators',
        ]);
    }

    /**
     * Dapatkan status progress 7 unit organisasi wajib untuk sebuah Laporan.
     *
     * @return array<int, array{unit: UnitOrganisasi, status: string, detail: ?LaporanDetail}>
     */
    public function getMandatoryUnitsProgress(Laporan $laporan): array
    {
        $mandatoryUnits = UnitOrganisasi::where('wajib_dilaporkan', true)
            ->orderBy('urutan')
            ->get();

        $details = $laporan->laporanDetails()
            ->with(['unitOrganisasi', 'user', 'verifiedBy'])
            ->get()
            ->keyBy('unit_organisasi_id');

        $progress = [];

        foreach ($mandatoryUnits as $unit) {
            $detail = $details->get($unit->id);
            $progress[] = [
                'unit' => $unit,
                'status' => $detail ? $detail->status : 'belum_dibuat',
                'detail' => $detail,
            ];
        }

        return $progress;
    }

    /**
     * Dapatkan ringkasan statistik efektivitas dan serapan anggaran seluruh seksi untuk sebuah Laporan.
     *
     * @return array{
     *     total_target_kinerja: float,
     *     total_realisasi_kinerja: float,
     *     rata_persentase_kinerja: float,
     *     total_anggaran_pagu: float,
     *     total_realisasi_anggaran: float,
     *     persentase_serapan_anggaran: float,
     *     predikat_efektivitas_umum: string,
     *     predikat_efisiensi_umum: string
     * }
     */
    public function getStatistikKecamatan(Laporan $laporan): array
    {
        $indikators = LaporanDetailIndikator::whereHas('laporanDetail', function ($query) use ($laporan) {
            $query->where('laporan_id', $laporan->id);
        })->get();

        $totalPagu = (float) $indikators->sum('anggaran_pagu');
        $totalBelanja = (float) $indikators->sum('realisasi_anggaran');
        $persenBelanja = $totalPagu > 0 ? round(($totalBelanja / $totalPagu) * 100, 2) : 0.0;

        $rataKinerja = $indikators->isNotEmpty() ? round($indikators->avg('persentase_kinerja'), 2) : 0.0;

        // Predikat Efektivitas (lowercase snake_case untuk konsumsi widget)
        $predikatEfektivitasKey = match (true) {
            $rataKinerja > 100 => 'sangat_efektif',
            $rataKinerja >= 90 => 'efektif',
            $rataKinerja >= 60 => 'cukup_efektif',
            default => 'tidak_efektif',
        };

        // Predikat Efektivitas (human readable untuk display/PDF)
        $predikatEfektivitasLabel = match ($predikatEfektivitasKey) {
            'sangat_efektif' => 'Sangat Efektif',
            'efektif' => 'Efektif',
            'cukup_efektif' => 'Cukup Efektif',
            default => 'Tidak Efektif',
        };

        // Predikat Efisiensi (lowercase snake_case untuk konsumsi widget)
        $predikatEfisiensiKey = match (true) {
            $persenBelanja < 60 => 'sangat_efisien',
            $persenBelanja <= 90 => 'efisien',
            $persenBelanja <= 100 => 'cukup_efisien',
            default => 'tidak_efisien',
        };

        // Predikat Efisiensi (human readable untuk display/PDF)
        $predikatEfisiensiLabel = match ($predikatEfisiensiKey) {
            'sangat_efisien' => 'Sangat Efisien',
            'efisien' => 'Efisien',
            'cukup_efisien' => 'Cukup Efisien',
            default => 'Tidak Efisien',
        };

        // Statistik kepatuhan unit
        $totalUnitDisetujui = (int) $laporan->details()->where('status', 'disetujui')->count();
        $totalUnitWajib = (int) UnitOrganisasi::where('wajib_dilaporkan', true)->count();

        return [
            // Key canonical (verbose)
            'total_target_kinerja' => (float) $indikators->sum('target_kinerja'),
            'total_realisasi_kinerja' => (float) $indikators->sum('realisasi_kinerja'),
            'rata_persentase_kinerja' => $rataKinerja,
            'total_anggaran_pagu' => $totalPagu,
            'total_realisasi_anggaran' => $totalBelanja,
            'persentase_serapan_anggaran' => $persenBelanja,
            'predikat_efektivitas_umum' => $predikatEfektivitasLabel,
            'predikat_efisiensi_umum' => $predikatEfisiensiLabel,

            // Key alias (dipakai CamatSummaryWidget & SekmatProgressWidget)
            'rata_rata_efektivitas' => $rataKinerja,
            'total_pagu' => $totalPagu,
            'total_realisasi' => $totalBelanja,
            'persentase_serapan' => $persenBelanja,
            'predikat_efektivitas' => $predikatEfektivitasKey,
            'predikat_efisiensi' => $predikatEfisiensiKey,
            'total_unit_disetujui' => $totalUnitDisetujui,
            'total_unit_wajib' => $totalUnitWajib,
        ];
    }
}
