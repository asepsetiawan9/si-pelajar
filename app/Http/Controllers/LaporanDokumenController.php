<?php

namespace App\Http\Controllers;

use App\Models\LaporanDokumen;
use App\Models\LaporanKinerjaV2;
use App\Services\BuktiDukungService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanDokumenController extends Controller
{
    public function __construct(
        protected BuktiDukungService $buktiDukungService
    ) {}

    /**
     * Download secure attachment for Laporan Detail (V1).
     */
    public function download(Request $request, LaporanDokumen $dokumen): StreamedResponse|Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Akses ditolak: Akun tidak aktif atau belum terotentikasi.');
        }

        $detail = $dokumen->laporanDetail;
        if (! $detail) {
            abort(404, 'Data laporan pendukung tidak ditemukan.');
        }

        // Row-Level Security: Kasi can only access their own unit's attachments
        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $detail->unit_organisasi_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mengunduh lampiran unit lain.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($dokumen->file_path)) {
            // Also check default storage disk in case stored in private
            if (Storage::exists($dokumen->file_path)) {
                $disk = Storage::disk(config('filesystems.default'));
            } else {
                abort(404, 'Berkas lampiran fisik tidak ditemukan di penyimpanan server.');
            }
        }

        // Sanitize download filename
        $extension = pathinfo($dokumen->file_path, PATHINFO_EXTENSION);
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dokumen->nama_dokumen ?: 'lampiran');
        $downloadName = $safeBase.($extension ? '.'.$extension : '');

        return $disk->download($dokumen->file_path, $downloadName);
    }

    /**
     * Buka berkas bukti dukung Laporan Kinerja Unit V2 di tab browser baru (Inline View).
     */
    public function viewBuktiDukungV2(Request $request, LaporanKinerjaV2 $record, string|int $index): BinaryFileResponse
    {
        $this->authorizeAccess($request, $record);

        return $this->buktiDukungService->viewFile($record, $index);
    }

    /**
     * Unduh berkas bukti dukung Laporan Kinerja Unit V2 secara langsung (Download Attachment).
     */
    public function downloadBuktiDukungV2(Request $request, LaporanKinerjaV2 $record, string|int $index): BinaryFileResponse
    {
        $this->authorizeAccess($request, $record);

        return $this->buktiDukungService->downloadFile($record, $index);
    }

    /**
     * Validasi otorisasi akun dan Row-Level Security (RLS).
     */
    protected function authorizeAccess(Request $request, LaporanKinerjaV2 $record): void
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Akses ditolak: Akun belum terotentikasi atau dinonaktifkan.');
        }

        if ($user->isKasi() && (int) $user->unit_organisasi_id !== (int) $record->unit_organisasi_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mengakses berkas bukti dukung unit lain.');
        }
    }
}
