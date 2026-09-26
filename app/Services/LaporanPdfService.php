<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanKinerjaV2;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LaporanPdfService
{
    /**
     * Generate dan simpan file PDF untuk laporan kinerja unit kerja.
     */
    public function generateLaporanUnitPdf(LaporanDetail $detail): string
    {
        $detail->loadMissing([
            'laporan',
            'unitOrganisasi',
            'user',
            'verifiedBy',
            'indikators.rencanaAksi.sasaranStrategis',
            'layanans',
        ]);

        $data = $this->prepareUnitPdfData($detail);
        $pdf = Pdf::loadView('pdf.laporan-kinerja-resmi', $data)
            ->setPaper('a4', 'portrait');

        $fileName = 'laporan/unit_'.$detail->id.'_'.date('Ymd_His').'.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }

    /**
     * Generate dan simpan file PDF kompilasi resmi Kecamatan Malangbong.
     */
    public function generateLaporanKecamatanPdf(Laporan $laporan): string
    {
        $laporan->loadMissing([
            'diajukanOleh',
            'disetujuiOleh',
            'laporanDetails.unitOrganisasi',
            'laporanDetails.user',
            'laporanDetails.verifiedBy',
            'laporanDetails.indikators.rencanaAksi.sasaranStrategis',
            'laporanDetails.layanans',
        ]);

        $data = $this->prepareKecamatanPdfData($laporan);
        $pdf = Pdf::loadView('pdf.laporan-kinerja-resmi', $data)
            ->setPaper('a4', 'portrait');

        $fileName = 'laporan/kecamatan_'.$laporan->id.'_'.date('Ymd_His').'.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        $laporan->update([
            'dokumen_rekap_pdf_path' => $fileName,
        ]);

        return $fileName;
    }

    /**
     * Download langsung PDF Laporan Unit.
     */
    public function downloadLaporanUnitPdf(LaporanDetail $detail): Response
    {
        $detail->loadMissing([
            'laporan',
            'unitOrganisasi',
            'user',
            'verifiedBy',
            'indikators.rencanaAksi.sasaranStrategis',
            'layanans',
        ]);

        $data = $this->prepareUnitPdfData($detail);
        $pdf = Pdf::loadView('pdf.laporan-kinerja-resmi', $data)
            ->setPaper('a4', 'portrait');

        $safeUnitName = str_replace(' ', '_', $detail->unitOrganisasi?->nama_unit ?? 'Unit');
        $fileName = 'Laporan_Kinerja_'.$safeUnitName.'_'.($detail->laporan?->bulan_pelaporan?->format('Y_m') ?? date('Y_m')).'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Download langsung PDF Laporan Kompilasi Kecamatan.
     */
    public function downloadLaporanKecamatanPdf(Laporan $laporan): Response
    {
        $laporan->loadMissing([
            'diajukanOleh',
            'disetujuiOleh',
            'laporanDetails.unitOrganisasi',
            'laporanDetails.user',
            'laporanDetails.verifiedBy',
            'laporanDetails.indikators.rencanaAksi.sasaranStrategis',
            'laporanDetails.layanans',
        ]);

        $data = $this->prepareKecamatanPdfData($laporan);
        $pdf = Pdf::loadView('pdf.laporan-kinerja-resmi', $data)
            ->setPaper('a4', 'portrait');

        $fileName = 'Laporan_Kinerja_Kecamatan_Malangbong_'.($laporan->bulan_pelaporan?->format('Y_m') ?? date('Y_m')).'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Susun payload data Blade view untuk Laporan Detail unit.
     *
     * @return array<string, mixed>
     */
    protected function prepareUnitPdfData(LaporanDetail $detail): array
    {
        $bulanDate = $detail->laporan?->bulan_pelaporan ?? Carbon::now();
        $periodeBulan = $bulanDate->translatedFormat('F Y');
        $namaUnit = $detail->unitOrganisasi?->nama_unit ?? 'Unit Kerja';

        // Daftar Rencana Aksi & Sasaran
        $daftarRencanaAksi = [];
        $indikatorList = [];

        foreach ($detail->indikators as $ind) {
            $aksi = $ind->rencanaAksi;
            $sasaran = $aksi?->sasaranStrategis;

            $daftarRencanaAksi[] = [
                'sasaran' => $sasaran?->uraian_sasaran ?? 'Peningkatan Pelayanan Publik',
                'sasaran_indikator' => $sasaran?->indikator_kinerja ?? '-',
                'rencana_aksi' => $aksi?->uraian_rencana_aksi ?? 'Pelaksanaan Kegiatan Operasional',
                'indikator_kinerja' => $ind->rencanaAksi?->indikator_kinerja ?? 'Volume Kegiatan',
            ];

            $indikatorList[] = [
                'rencana_aksi' => $aksi?->uraian_rencana_aksi ?? 'Pelaksanaan Kegiatan',
                'indikator_kinerja' => $aksi?->indikator_kinerja ?? 'Capaian Output',
                'satuan' => $aksi?->satuan_target ?? 'Kegiatan',
                'target_kinerja' => (float) $ind->target_kinerja,
                'realisasi_kinerja' => (float) $ind->realisasi_kinerja,
                'persentase_kinerja' => (float) $ind->persentase_kinerja,
                'predikat_efektivitas' => $ind->predikat_efektivitas,
                'anggaran_pagu' => (float) $ind->anggaran_pagu,
                'realisasi_anggaran' => (float) $ind->realisasi_anggaran,
                'persentase_anggaran' => (float) $ind->persentase_anggaran,
                'predikat_efisiensi' => $ind->predikat_efisiensi,
            ];
        }

        // Sub-tabel rincian layanan (misal PATEN)
        $layananList = [];
        foreach ($detail->layanans as $lay) {
            $layananList[] = [
                'nama_layanan' => $lay->nama_layanan,
                'jumlah' => $lay->jumlah,
                'satuan' => $lay->satuan,
                'keterangan' => $lay->keterangan,
            ];
        }

        // Sekmat info
        $sekmatUser = $detail->verifiedBy ?? User::where('role', 'admin_kecamatan')->first();
        $namaSekmat = $sekmatUser?->name ?? 'Sekretaris Camat Malangbong';
        $nipSekmat = $sekmatUser?->nip ?? '19750810200003 1 002';

        // QR Code Payload
        $hashSekmat = hash('sha256', "SPKO_SEKMAT_{$detail->id}_{$detail->verified_at}");
        $qrPayloadSekmat = "SPKO KECAMATAN MALANGBONG\nVerifikasi: SEKRETARIS CAMAT\nUnit: {$namaUnit}\nPeriode: {$periodeBulan}\nVerifikator: {$namaSekmat}\nTanggal: ".($detail->verified_at?->format('d/m/Y H:i') ?? 'N/A')."\nToken: ".substr($hashSekmat, 0, 16);
        $qrCodeSekmat = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadSekmat));

        $hashCamat = hash('sha256', "SPKO_CAMAT_{$detail->id}_{$detail->laporan?->disetujui_pada}");
        $camatNama = config('spko.instansi.camat_nama');
        $qrPayloadCamat = "SPKO KECAMATAN MALANGBONG\nPengesahan: CAMAT MALANGBONG\nPejabat: {$camatNama}\nUnit: {$namaUnit}\nPeriode: {$periodeBulan}\nTanggal: ".($detail->laporan?->disetujui_pada?->format('d/m/Y H:i') ?? date('d/m/Y H:i'))."\nToken: ".substr($hashCamat, 0, 16);
        $qrCodeCamat = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadCamat));

        $tanggalPengesahan = $detail->laporan?->disetujui_pada
            ? $detail->laporan->disetujui_pada->translatedFormat('d F Y')
            : Carbon::now()->translatedFormat('d F Y');

        return [
            'judulDokumen' => "Laporan Kinerja Bulanan {$namaUnit} - {$periodeBulan}",
            'namaUnitKerja' => $namaUnit,
            'periodeBulan' => $periodeBulan,
            'tahun' => $detail->laporan?->tahun ?? $bulanDate->year,
            'latarBelakang' => $detail->latar_belakang ?: LaporanDetail::defaultLatarBelakang(),
            'daftarRencanaAksi' => $daftarRencanaAksi,
            'indikatorList' => $indikatorList,
            'layananList' => $layananList,
            'keluhanMasyarakat' => $detail->keluhan_masyarakat,
            'hambatan' => $detail->hambatan,
            'simpulan' => $detail->simpulan,
            'namaSekmat' => $namaSekmat,
            'nipSekmat' => $nipSekmat,
            'qrCodeSekmat' => $qrCodeSekmat,
            'qrCodeCamat' => $qrCodeCamat,
            'tanggalPengesahan' => $tanggalPengesahan,
            'showCover' => true,
            'logoBase64' => $this->getLogoBase64(),
        ];
    }

    /**
     * Susun payload data Blade view untuk Laporan Kompilasi Kecamatan (seluruh unit).
     *
     * @return array<string, mixed>
     */
    protected function prepareKecamatanPdfData(Laporan $laporan): array
    {
        $bulanDate = $laporan->bulan_pelaporan ?? Carbon::now();
        $periodeBulan = $bulanDate->translatedFormat('F Y');
        $namaUnit = 'Pemerintah Kecamatan Malangbong (Kompilasi 7 Unit Kerja)';

        $daftarRencanaAksi = [];
        $indikatorList = [];
        $layananList = [];

        foreach ($laporan->laporanDetails as $detail) {
            $unitName = $detail->unitOrganisasi?->nama_unit ?? 'Unit';

            foreach ($detail->indikators as $ind) {
                $aksi = $ind->rencanaAksi;
                $sasaran = $aksi?->sasaranStrategis;

                $daftarRencanaAksi[] = [
                    'sasaran' => $sasaran?->uraian_sasaran ?? 'Penyelenggaraan Pemerintahan & Pelayanan',
                    'sasaran_indikator' => $sasaran?->indikator_kinerja ?? '-',
                    'rencana_aksi' => "[{$unitName}] ".($aksi?->uraian_rencana_aksi ?? 'Kegiatan Operasional'),
                    'indikator_kinerja' => $ind->rencanaAksi?->indikator_kinerja ?? 'Output',
                ];

                $indikatorList[] = [
                    'rencana_aksi' => "[{$unitName}] ".($aksi?->uraian_rencana_aksi ?? 'Pelaksanaan Kegiatan'),
                    'indikator_kinerja' => $aksi?->indikator_kinerja ?? 'Capaian Output',
                    'satuan' => $aksi?->satuan_target ?? 'Kegiatan',
                    'target_kinerja' => (float) $ind->target_kinerja,
                    'realisasi_kinerja' => (float) $ind->realisasi_kinerja,
                    'persentase_kinerja' => (float) $ind->persentase_kinerja,
                    'predikat_efektivitas' => $ind->predikat_efektivitas,
                    'anggaran_pagu' => (float) $ind->anggaran_pagu,
                    'realisasi_anggaran' => (float) $ind->realisasi_anggaran,
                    'persentase_anggaran' => (float) $ind->persentase_anggaran,
                    'predikat_efisiensi' => $ind->predikat_efisiensi,
                ];
            }

            foreach ($detail->layanans as $lay) {
                $layananList[] = [
                    'nama_layanan' => "[{$unitName}] ".$lay->nama_layanan,
                    'jumlah' => $lay->jumlah,
                    'satuan' => $lay->satuan,
                    'keterangan' => $lay->keterangan,
                ];
            }
        }

        $sekmatUser = $laporan->diajukanOleh ?? User::where('role', 'admin_kecamatan')->first();
        $namaSekmat = $sekmatUser?->name ?? 'Sekretaris Camat Malangbong';
        $nipSekmat = $sekmatUser?->nip ?? '19750810200003 1 002';

        $hashSekmat = hash('sha256', "SPKO_KECAMATAN_SEKMAT_{$laporan->id}_{$laporan->updated_at}");
        $qrPayloadSekmat = "SPKO KECAMATAN MALANGBONG\nPengajuan: SEKRETARIS CAMAT\nLingkup: 7 Unit Kerja Operasional\nPeriode: {$periodeBulan}\nVerifikator: {$namaSekmat}\nToken: ".substr($hashSekmat, 0, 16);
        $qrCodeSekmat = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadSekmat));

        $hashCamat = hash('sha256', "SPKO_KECAMATAN_CAMAT_{$laporan->id}_{$laporan->disetujui_pada}");
        $camatNama = config('spko.instansi.camat_nama');
        $qrPayloadCamat = "SPKO KECAMATAN MALANGBONG\nPengesahan: CAMAT MALANGBONG\nPejabat: {$camatNama}\nLingkup: 7 Unit Kerja Operasional\nPeriode: {$periodeBulan}\nTanggal: ".($laporan->disetujui_pada?->format('d/m/Y H:i') ?? date('d/m/Y H:i'))."\nToken: ".substr($hashCamat, 0, 16);
        $qrCodeCamat = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadCamat));

        $tanggalPengesahan = $laporan->disetujui_pada
            ? $laporan->disetujui_pada->translatedFormat('d F Y')
            : Carbon::now()->translatedFormat('d F Y');

        return [
            'judulDokumen' => "Laporan Kinerja Kompilasi Kecamatan Malangbong - {$periodeBulan}",
            'namaUnitKerja' => $namaUnit,
            'periodeBulan' => $periodeBulan,
            'tahun' => $laporan->tahun,
            'latarBelakang' => LaporanDetail::defaultLatarBelakang(),
            'daftarRencanaAksi' => $daftarRencanaAksi,
            'indikatorList' => $indikatorList,
            'layananList' => $layananList,
            'keluhanMasyarakat' => 'Aduan dan keluhan pelayanan terkelola secara terpadu melalui loket PATEN dan kanal koordinasi kewilayahan.',
            'hambatan' => 'Sebagian hambatan operasional dan administrasi anggaran dapat ditanggulangi melalui optimalisasi sumber daya kecamatan.',
            'simpulan' => "Kompilasi capaian kinerja seluruh 7 unit kerja operasional Kecamatan Malangbong pada periode {$periodeBulan} telah terlaksana dengan efektif dan akuntabel.",
            'namaSekmat' => $namaSekmat,
            'nipSekmat' => $nipSekmat,
            'qrCodeSekmat' => $qrCodeSekmat,
            'qrCodeCamat' => $qrCodeCamat,
            'tanggalPengesahan' => $tanggalPengesahan,
            'showCover' => true,
            'logoBase64' => $this->getLogoBase64(),
        ];
    }

    /**
     * Download / stream file PDF resmi Laporan Kinerja Unit V2.
     */
    public function downloadLaporanV2Pdf(LaporanKinerjaV2 $record): Response
    {
        $record->loadMissing(['unitOrganisasi', 'user', 'verifier']);
        $data = $this->prepareLaporanV2PdfData($record);

        $pdf = Pdf::loadView('pdf.laporan-kinerja-v2-resmi', $data)
            ->setPaper('a4', 'portrait');

        $unitSlug = Str::slug($record->unitOrganisasi?->nama_unit ?? 'unit');
        $filename = "Laporan_Kinerja_V2_{$unitSlug}_{$record->periode_tahun}_{$record->periode_bulan}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Prepare view data untuk PDF Laporan Kinerja Unit V2.
     */
    protected function prepareLaporanV2PdfData(LaporanKinerjaV2 $record): array
    {
        $sekmatUser = User::where('role', 'admin_kecamatan')->first();
        $namaSekmat = $sekmatUser?->name ?? 'Sekretaris Camat Malangbong';
        $nipSekmat = $sekmatUser?->nip ?? '19750810200003 1 002';

        $hashSekmat = hash('sha256', "SPKO_V2_SEKMAT_{$record->id}_{$record->verified_at}");
        $qrPayloadSekmat = "SPKO KECAMATAN MALANGBONG\nVerifikasi: SEKRETARIS CAMAT\nUnit: {$record->unitOrganisasi?->nama_unit}\nAgenda: {$record->judul_pelaporan}\nPeriode: {$record->nama_bulan} {$record->periode_tahun}\nToken: ".substr($hashSekmat, 0, 16);
        $qrCodeSekmat = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadSekmat));

        $hashPelapor = hash('sha256', "SPKO_V2_PELAPOR_{$record->id}_{$record->submitted_at}");
        $qrPayloadPelapor = "SPKO KECAMATAN MALANGBONG\nPejabat Pelapor: {$record->nama_pejabat}\nNIP: {$record->nip_pejabat}\nUnit: {$record->unitOrganisasi?->nama_unit}\nTanggal: ".($record->tanggal_pelaporan?->format('d/m/Y') ?? date('d/m/Y'))."\nToken: ".substr($hashPelapor, 0, 16);
        $qrCodePelapor = base64_encode(QrCode::format('svg')->size(100)->generate($qrPayloadPelapor));

        $tanggalPengesahan = $record->verified_at
            ? $record->verified_at->translatedFormat('d F Y')
            : Carbon::now()->translatedFormat('d F Y');

        return [
            'judulDokumen' => "Laporan Kinerja - {$record->unitOrganisasi?->nama_unit} ({$record->nama_bulan} {$record->periode_tahun})",
            'record' => $record,
            'namaSekmat' => $namaSekmat,
            'nipSekmat' => $nipSekmat,
            'qrCodeSekmat' => $qrCodeSekmat,
            'qrCodePelapor' => $qrCodePelapor,
            'tanggalPengesahan' => $tanggalPengesahan,
            'logoBase64' => $this->getLogoBase64(),
        ];
    }

    /**
     * Helper untuk mengambil data base64 logo resmi Garut untuk DomPDF.
     */
    protected function getLogoBase64(): ?string
    {
        $logoPath = public_path('logo.png');
        if (file_exists($logoPath)) {
            return 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        }

        return null;
    }
}
