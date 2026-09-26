<?php

namespace Database\Seeders;

use App\Models\RencanaAksi;
use Illuminate\Database\Seeder;

class RencanaAksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rencanaAksiList = [
            // 1. Sub Bag Umum, Perencanaan Evaluasi dan Pelaporan
            [
                'id' => 1,
                'unit_organisasi_id' => 1,
                'sasaran_strategis_id' => 2,
                'uraian_rencana_aksi' => 'Penyusunan dokumen perencanaan, evaluasi dan pelaporan kinerja kecamatan',
                'indikator_kinerja' => 'Tersedianya dokumen pelaporan kinerja dan akuntabilitas kecamatan tepat waktu',
                'target_default' => 1,
                'satuan_target' => 'Dokumen Laporan',
            ],
            // 2. Sub Bagian Keuangan dan BMD
            [
                'id' => 2,
                'unit_organisasi_id' => 2,
                'sasaran_strategis_id' => 2,
                'uraian_rencana_aksi' => 'Pengelolaan administrasi keuangan dan penatausahaan BMD kecamatan',
                'indikator_kinerja' => 'Persentase ketepatan pertanggungjawaban keuangan dan inventarisasi BMD',
                'target_default' => 1,
                'satuan_target' => 'Laporan Keuangan',
            ],
            // 3. Seksi Pemerintahan
            [
                'id' => 3,
                'unit_organisasi_id' => 3,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Pembinaan dan pengawasan penyelenggaraan administrasi pemerintahan desa',
                'indikator_kinerja' => 'Jumlah kegiatan monitoring evaluasi administrasi pemerintahan desa yang terlaksana',
                'target_default' => 1,
                'satuan_target' => 'Laporan Pembinaan',
            ],
            // 4. Seksi Kesejahteraan Masyarakat
            [
                'id' => 4,
                'unit_organisasi_id' => 4,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Fasilitasi dan pemantauan program penanggulangan kemiskinan dan kesejahteraan sosial',
                'indikator_kinerja' => 'Terlaksananya penyaluran bantuan sosial dan penanganan masalah kesejahteraan masyarakat',
                'target_default' => 1,
                'satuan_target' => 'Laporan Kegiatan',
            ],
            // 5. Seksi Pemberdayaan Masyarakat dan Desa (PMD)
            [
                'id' => 5,
                'unit_organisasi_id' => 5,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Pendampingan dan pembinaan kelembagaan desa serta partisipasi masyarakat',
                'indikator_kinerja' => 'Terlaksananya evaluasi perkembangan desa dan partisipasi swadaya masyarakat',
                'target_default' => 1,
                'satuan_target' => 'Laporan Pendampingan',
            ],
            // 6. Seksi Ketentraman dan Ketertiban (Trantib)
            [
                'id' => 6,
                'unit_organisasi_id' => 6,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Patroli dan penegakan ketentraman dan ketertiban umum di wilayah kecamatan',
                'indikator_kinerja' => 'Tingkat kondusifitas wilayah dan penanganan gangguan trantibum',
                'target_default' => 1,
                'satuan_target' => 'Laporan Penertiban',
            ],
            // 7. Seksi Pelayanan (PATEN & SKM)
            [
                'id' => 7,
                'unit_organisasi_id' => 7,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Menyelenggarakan pelayanan PATEN Kecamatan',
                'indikator_kinerja' => 'Jumlah layanan perizinan dan non-perizinan PATEN yang diselesaikan sesuai SOP',
                'target_default' => 1,
                'satuan_target' => 'Laporan/Bulan',
            ],
            [
                'id' => 8,
                'unit_organisasi_id' => 7,
                'sasaran_strategis_id' => 1,
                'uraian_rencana_aksi' => 'Menyelenggarakan survey kepuasan masyarakat',
                'indikator_kinerja' => 'Laporan hasil Survey Kepuasan Masyarakat (SKM) per periode',
                'target_default' => 1,
                'satuan_target' => 'Dokumen Laporan',
            ],
        ];

        foreach ($rencanaAksiList as $rencana) {
            RencanaAksi::updateOrCreate(
                ['id' => $rencana['id']],
                $rencana
            );
        }
    }
}
