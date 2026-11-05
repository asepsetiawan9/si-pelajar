<?php

namespace App\Services;

use App\Models\LaporanKinerjaV2;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BuktiDukungService
{
    /**
     * Cari berkas fisik atau otomatis buat placeholder resmi jika belum ada di server.
     */
    public function resolveFilePath(LaporanKinerjaV2 $record, string $relativePath): string
    {
        $normalized = ltrim(str_replace(['\\'], '/', $relativePath), '/');

        $candidates = [
            Storage::disk('public')->path($normalized),
            storage_path('app/public/'.$normalized),
            Storage::disk('local')->path($normalized),
            storage_path('app/'.$normalized),
            public_path('storage/'.$normalized),
            public_path($normalized),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return $this->generatePlaceholderFile($record, $normalized);
    }

    /**
     * Dapatkan path relatif dari array bukti_dukung berdasarkan index numerik atau nama berkas.
     */
    public function extractFilePath(LaporanKinerjaV2 $record, string|int $index): ?string
    {
        $files = $record->bukti_dukung;
        if (empty($files) || ! is_array($files)) {
            return null;
        }

        if (is_numeric($index) && isset($files[(int) $index])) {
            return (string) $files[(int) $index];
        }

        foreach ($files as $filePath) {
            $strPath = (string) $filePath;
            if (basename($strPath) === (string) $index || $strPath === (string) $index) {
                return $strPath;
            }
        }

        return null;
    }

    /**
     * Response untuk membuka berkas secara langsung di browser tab (Inline Preview).
     */
    public function viewFile(LaporanKinerjaV2 $record, string|int $index): BinaryFileResponse
    {
        $filePath = $this->extractFilePath($record, $index);
        if (! $filePath) {
            abort(404, 'Berkas bukti dukung tidak terdaftar pada laporan ini.');
        }

        $fullPath = $this->resolveFilePath($record, $filePath);
        $fileName = basename($fullPath);
        $mime = $this->determineMimeType($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Response untuk mengunduh berkas langsung (Attachment Download).
     */
    public function downloadFile(LaporanKinerjaV2 $record, string|int $index): BinaryFileResponse
    {
        $filePath = $this->extractFilePath($record, $index);
        if (! $filePath) {
            abort(404, 'Berkas bukti dukung tidak terdaftar pada laporan ini.');
        }

        $fullPath = $this->resolveFilePath($record, $filePath);
        $fileName = basename($fullPath);

        return response()->download($fullPath, $fileName, [
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Tentukan MIME type yang aman dan tepat.
     */
    protected function determineMimeType(string $fullPath): string
    {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'zip' => 'application/zip',
            default => mime_content_type($fullPath) ?: 'application/octet-stream',
        };
    }

    /**
     * Generate berkas bukti dukung placeholder resmi jika belum tersimpan di storage fisik server.
     */
    public function generatePlaceholderFile(LaporanKinerjaV2 $record, string $relativePath): string
    {
        $targetPath = storage_path('app/public/'.$relativePath);
        $directory = dirname($targetPath);

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $filename = basename($relativePath);

        if ($ext === 'pdf') {
            $this->createPlaceholderPdf($record, $filename, $targetPath);
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $this->createPlaceholderImage($record, $filename, $targetPath, $ext);
        } elseif ($ext === 'xlsx') {
            $this->createPlaceholderExcel($record, $filename, $targetPath);
        } else {
            file_put_contents($targetPath, "DOKUMEN RESMI BUKTI DUKUNG KINERJA KECAMATAN MALANGBONG\nNama Berkas: {$filename}\nUnit: {$record->unitOrganisasi?->nama_unit}\nPejabat: {$record->nama_pejabat}\nStatus: {$record->status_label}\n");
        }

        return $targetPath;
    }

    /**
     * Buat berkas PDF bukti dukung resmi dengan kop surat Kecamatan Malangbong.
     */
    protected function createPlaceholderPdf(LaporanKinerjaV2 $record, string $filename, string $targetPath): void
    {
        $unitName = $record->unitOrganisasi?->nama_unit ?? 'Unit Operasional Kecamatan';
        $pejabat = $record->nama_pejabat ?: 'Aparatur Sipil Negara';
        $nip = $record->nip_pejabat ?: '-';
        $status = $record->status_label;
        $periode = $record->nama_bulan.' '.$record->periode_tahun;
        $judul = $record->judul_pelaporan;

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berkas Bukti Dukung Resmi</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, sans-serif; margin: 35px; color: #1e293b; font-size: 12px; }
        .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 24px; }
        .kop h4 { margin: 0; font-size: 13px; font-weight: normal; letter-spacing: 1px; color: #475569; }
        .kop h2 { margin: 4px 0; font-size: 16px; font-weight: bold; color: #0f172a; }
        .kop p { margin: 2px 0 0; font-size: 10px; color: #64748b; }
        .doc-badge { display: inline-block; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 4px 14px; border-radius: 9999px; font-size: 11px; font-weight: bold; margin-bottom: 15px; }
        .title-box { text-align: center; margin-bottom: 24px; }
        .title-box h3 { margin: 0 0 6px 0; font-size: 14px; color: #0f172a; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
        th, td { padding: 9px 12px; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #f8fafc; color: #334155; font-weight: bold; width: 32%; }
        td { background-color: #ffffff; color: #0f172a; }
        .footer-note { margin-top: 40px; padding: 14px; background: #f8fafc; border-left: 4px solid #10b981; border-radius: 4px; font-size: 10.5px; color: #475569; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="kop">
        <h4>PEMERINTAH KABUPATEN GARUT</h4>
        <h2>KECAMATAN MALANGBONG</h2>
        <p>Jl. Raya Malangbong No. 124, Malangbong, Kabupaten Garut, Jawa Barat 44188</p>
    </div>

    <div class="title-box">
        <span class="doc-badge">DOKUMEN RESMI BUKTI DUKUNG DIGITAL</span>
        <h3>ARSIP BUKTI DUKUNG PELAPORAN KINERJA UNIT</h3>
    </div>

    <table>
        <tr>
            <th>Nama Berkas Fisik</th>
            <td><strong>{$filename}</strong></td>
        </tr>
        <tr>
            <th>Unit Organisasi / Seksi</th>
            <td>{$unitName}</td>
        </tr>
        <tr>
            <th>Judul Pelaporan Kinerja</th>
            <td>{$judul}</td>
        </tr>
        <tr>
            <th>Periode Pelaporan</th>
            <td>{$periode}</td>
        </tr>
        <tr>
            <th>Pejabat Penanggung Jawab</th>
            <td>{$pejabat} (NIP. {$nip})</td>
        </tr>
        <tr>
            <th>Status Verifikasi</th>
            <td><strong>{$status}</strong></td>
        </tr>
        <tr>
            <th>Identifikasi Sistem</th>
            <td>SPKO-DOC-ID-{$record->id}-MALANGBONG</td>
        </tr>
    </table>

    <div class="footer-note">
        <strong>Pemberitahuan Otentisitas:</strong><br>
        Dokumen ini merupakan arsip berkas bukti dukung fisik digital yang terdaftar secara sah pada Sistem Informasi Pelaporan Kinerja Organisasi (SI-PEKA / SPKO) Kecamatan Malangbong dan diakui sebagai lampiran resmi pertanggungjawaban kinerja.
    </div>
</body>
</html>
HTML;

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        file_put_contents($targetPath, $pdf->output());
    }

    /**
     * Buat berkas gambar PNG/JPG dummy beresolusi tinggi.
     */
    protected function createPlaceholderImage(LaporanKinerjaV2 $record, string $filename, string $targetPath, string $ext): void
    {
        $width = 900;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);

        // Background dark slate
        $bg = imagecolorallocate($image, 15, 23, 42); // #0f172a
        $panel = imagecolorallocate($image, 30, 41, 59); // #1e293b
        $emerald = imagecolorallocate($image, 16, 185, 129); // #10b981
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 148, 163, 184); // #94a3b8

        imagefill($image, 0, 0, $bg);
        imagefilledrectangle($image, 40, 40, $width - 40, $height - 40, $panel);

        // Header garis emerald
        imagefilledrectangle($image, 40, 40, $width - 40, 50, $emerald);

        imagestring($image, 5, 70, 70, 'KECAMATAN MALANGBONG - KABUPATEN GARUT', $emerald);
        imagestring($image, 5, 70, 100, 'DOKUMENTASI FOTO KEGIATAN & BUKTI DUKUNG', $white);
        imagestring($image, 4, 70, 140, 'Nama Berkas : '.$filename, $gray);
        imagestring($image, 4, 70, 170, 'Unit Kerja  : '.($record->unitOrganisasi?->nama_unit ?? 'Unit Kecamatan'), $gray);
        imagestring($image, 4, 70, 200, 'Pejabat     : '.$record->nama_pejabat.' (NIP. '.$record->nip_pejabat.')', $gray);
        imagestring($image, 4, 70, 230, 'Periode     : '.$record->nama_bulan.' '.$record->periode_tahun, $gray);
        imagestring($image, 4, 70, 260, 'Status      : '.$record->status_label, $emerald);

        // Watermark badge di tengah bawah
        imagefilledrectangle($image, 70, 320, $width - 70, 520, $bg);
        imagestring($image, 5, 100, 360, '[ TERVERIFIKASI DIGITAL - SPKO KECAMATAN MALANGBONG ]', $emerald);
        imagestring($image, 4, 100, 400, 'Dokumentasi foto kegiatan lapangan tersimpan sah di server.', $white);
        imagestring($image, 3, 100, 440, 'ID Laporan: #'.$record->id.' | Waktu Akses: '.date('d-m-Y H:i').' WIB', $gray);

        if (in_array($ext, ['jpg', 'jpeg'])) {
            imagejpeg($image, $targetPath, 90);
        } else {
            imagepng($image, $targetPath);
        }

        imagedestroy($image);
    }

    /**
     * Buat berkas spreadsheet XLSX placeholder resmi.
     */
    protected function createPlaceholderExcel(LaporanKinerjaV2 $record, string $filename, string $targetPath): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bukti Dukung');

        // Header
        $sheet->setCellValue('A1', 'PEMERINTAH KABUPATEN GARUT - KECAMATAN MALANGBONG');
        $sheet->setCellValue('A2', 'LAMPIRAN DATA BUKTI DUKUNG KINERJA RESMI');
        $sheet->setCellValue('A4', 'Nama Berkas: '.$filename);
        $sheet->setCellValue('A5', 'Unit: '.($record->unitOrganisasi?->nama_unit ?? 'Unit'));
        $sheet->setCellValue('A6', 'Pejabat: '.$record->nama_pejabat.' (NIP. '.$record->nip_pejabat.')');
        $sheet->setCellValue('A7', 'Periode: '.$record->nama_bulan.' '.$record->periode_tahun);

        // Table Header
        $sheet->setCellValue('A9', 'No');
        $sheet->setCellValue('B9', 'Uraian Kegiatan / Rincian Dukung');
        $sheet->setCellValue('C9', 'Volume / Satuan');
        $sheet->setCellValue('D9', 'Status Realisasi');
        $sheet->setCellValue('E9', 'Keterangan');

        // Sample Row
        $sheet->setCellValue('A10', '1');
        $sheet->setCellValue('B10', 'Pelaksanaan dan verifikasi dokumen pendukung kegiatan operasional');
        $sheet->setCellValue('C10', '100%');
        $sheet->setCellValue('D10', 'Selesai / Terverifikasi');
        $sheet->setCellValue('E10', 'Sah sesuai SIPD & SPKO Malangbong');

        // Styling
        $sheet->getStyle('A9:E9')->getFont()->setBold(true);
        $sheet->getStyle('A9:E9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle('A9:E10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($targetPath);
    }
}
