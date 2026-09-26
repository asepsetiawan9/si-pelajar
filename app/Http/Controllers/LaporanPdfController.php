<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Services\LaporanPdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LaporanPdfController extends Controller
{
    public function __construct(
        protected LaporanPdfService $pdfService
    ) {}

    /**
     * Download / Stream PDF resmi Laporan Detail per unit.
     */
    public function downloadDetailPdf(LaporanDetail $detail): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Kasi hanya bisa unduh laporan unitnya sendiri (strict int comparison)
        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $detail->unit_organisasi_id) {
            abort(403, 'Anda tidak memiliki hak akses mengunduh laporan unit lain.');
        }

        return $this->pdfService->downloadLaporanUnitPdf($detail);
    }

    /**
     * Download / Stream PDF resmi Rekapitulasi Kompilasi Kecamatan Malangbong.
     */
    public function downloadKecamatanPdf(Laporan $laporan): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Hanya Sekmat, Camat, dan Superadmin yang berhak mengunduh rekapitulasi kecamatan
        if ($user->isKasi()) {
            abort(403, 'Akses ditolak: Hanya Sekretaris Camat, Camat, dan Superadmin yang dapat mengunduh rekapitulasi kecamatan.');
        }

        return $this->pdfService->downloadLaporanKecamatanPdf($laporan);
    }
}
