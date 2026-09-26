<?php

namespace App\Http\Controllers;

use App\Exports\LaporanKinerjaExport;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanExportController extends Controller
{
    /**
     * Download Excel rekap bulanan kecamatan.
     */
    public function downloadKecamatanExcel(Request $request, Laporan $laporan): BinaryFileResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Akses ditolak: Akun tidak aktif.');
        }

        // Only Superadmin, Sekmat, Camat can download kecamatan rekap
        if ($user->isKasi()) {
            abort(403, 'Akses ditolak: Kepala Seksi hanya dapat mengunduh laporan unit sendiri.');
        }

        $date = Carbon::parse($laporan->bulan_pelaporan);
        $filename = sprintf(
            'SI-PELAJAR-Kecamatan-Malangbong-%s-%s.xlsx',
            $date->translatedFormat('F'),
            $laporan->tahun
        );

        return Excel::download(new LaporanKinerjaExport($laporan), $filename);
    }

    /**
     * Download Excel laporan kinerja unit tunggal.
     */
    public function downloadDetailExcel(Request $request, LaporanDetail $detail): BinaryFileResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Akses ditolak: Akun tidak aktif.');
        }

        // Row-Level Security: Kasi can only download their own unit
        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $detail->unit_organisasi_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki akses ke laporan unit lain.');
        }

        $date = Carbon::parse($detail->laporan->bulan_pelaporan);
        $unitCode = $detail->unitOrganisasi?->kode_unit ?? 'UNIT';
        $filename = sprintf(
            'SI-PELAJAR-%s-%s-%s.xlsx',
            $unitCode,
            $date->translatedFormat('F'),
            $detail->laporan->tahun
        );

        return Excel::download(new LaporanKinerjaExport(null, $detail), $filename);
    }
}
