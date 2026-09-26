<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use App\Services\LaporanPdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class LaporanPdfController extends Controller
{
    public function __construct(
        protected LaporanPdfService $pdfService
    ) {}

    /**
     * Download / Stream PDF resmi Laporan Kinerja Unit V2.
     */
    public function downloadLaporanV2Pdf(LaporanKinerjaV2 $record): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Kasi hanya bisa unduh laporan unitnya sendiri
        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $record->unit_organisasi_id) {
            abort(403, 'Anda tidak memiliki hak akses mengunduh laporan unit lain.');
        }

        return $this->pdfService->downloadLaporanV2Pdf($record);
    }

    /**
     * Download seluruh berkas bukti dukung yang di-upload dalam arsip ZIP.
     */
    public function downloadBuktiDukungZip(LaporanKinerjaV2 $record): BinaryFileResponse|Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $record->unit_organisasi_id) {
            abort(403, 'Akses ditolak.');
        }

        $files = $record->bukti_dukung;
        if (empty($files) || ! is_array($files)) {
            abort(404, 'Tidak ada berkas bukti dukung yang dilampirkan.');
        }

        $zipFileName = 'bukti_dukung_'.$record->id.'_'.date('Ymd_His').'.zip';
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir.'/'.$zipFileName;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $filePath) {
                $fullPath = Storage::disk('public')->path($filePath);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, basename($filePath));
                }
            }
            $zip->close();
        }

        if (! file_exists($zipPath)) {
            abort(404, 'Gagal mengompresi berkas bukti dukung.');
        }

        return response()->download($zipPath, "Bukti_Dukung_Unit_{$record->unit_organisasi_id}_{$record->periode_bulan}_{$record->periode_tahun}.zip")->deleteFileAfterSend(true);
    }

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
