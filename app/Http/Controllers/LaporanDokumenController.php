<?php

namespace App\Http\Controllers;

use App\Models\LaporanDokumen;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanDokumenController extends Controller
{
    /**
     * Download secure attachment for Laporan Detail.
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
}
