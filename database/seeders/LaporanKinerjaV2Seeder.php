<?php

namespace Database\Seeders;

use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LaporanKinerjaV2Seeder extends Seeder
{
    public function run(): void
    {
        $sekmat = User::where('role', 'admin_kecamatan')->first();

        // 7 Unit Kerja Kasi / Kasubag
        $unitConfigs = [
            1 => [ // Subbag Umum
                'email' => 'kasubag.umum@malangbong.go.id',
                'judul' => 'Laporan Pengelolaan Administrasi Kepegawaian & Evaluasi Kinerja Organisasi',
                'ringkasan' => 'Pengelolaan tertib administrasi surat masuk/keluar, penataan arsip kepegawaian aparatur sipil negara, serta penyusunan rekapitulasi kehadiran dan pelaporan kinerja organisasi bulanan.',
                'status' => 'disetujui',
                'catatan' => 'Laporan administrasi kepegawaian dan kearsipan sangat tertib, berkas rekap kehadiran terlampir lengkap.',
                'files' => ['bukti-dukung-v2/rekap_presensi_kepegawaian.pdf', 'bukti-dukung-v2/laporan_tata_kelola_persuratan.pdf'],
                'day' => 8,
            ],
            2 => [ // Subbag Keuangan
                'email' => 'kasubag.keuangan@malangbong.go.id',
                'judul' => 'Laporan Penatausahaan Keuangan Daerah & Rekonsiliasi Aset BMD',
                'ringkasan' => 'Pelaksanaan penatausahaan belanja langsung, verifikasi bukti pertanggungjawaban (SPJ) kegiatan operasional kecamatan, serta rekonsiliasi berkala daftar Barang Milik Daerah (BMD).',
                'status' => 'disetujui',
                'catatan' => 'Buku Kas Umum (BKU) dan SPJ belanja operasional telah diverifikasi sesuai SIPD.',
                'files' => ['bukti-dukung-v2/bku_pengeluaran_agustus.pdf', 'bukti-dukung-v2/berita_acara_rekonsiliasi_bmd.pdf'],
                'day' => 7,
            ],
            3 => [ // Seksi Pemerintahan
                'email' => 'kasi.pemerintahan@malangbong.go.id',
                'judul' => 'Laporan Pembinaan Administrasi Desa & Fasilitasi Kewilayahan',
                'ringkasan' => 'Fasilitasi penyusunan Peraturan Desa, pembinaan batas wilayah dan aset desa, koordinasi pengisian perangkat desa, serta rekapitulasi pelaporan penyelenggaraan pemerintahan 24 desa.',
                'status' => 'disetujui',
                'catatan' => 'Hasil pembinaan administrasi 24 desa telah direkapitulasi secara komprehensif.',
                'files' => ['bukti-dukung-v2/hasil_pembinaan_adm_desa.pdf', 'bukti-dukung-v2/dokumentasi_rakor_kades.jpg'],
                'day' => 9,
            ],
            4 => [ // Seksi Kesra
                'email' => 'kasi.kesra@malangbong.go.id',
                'judul' => 'Laporan Fasilitasi Program Sosial, Keagamaan & Kesejahteraan Masyarakat',
                'ringkasan' => 'Penyaluran dan verifikasi bantuan sosial kemasyarakatan, koordinasi program penanganan kemiskinan ekstrem, pembinaan kelembagaan keagamaan, serta penanganan respon cepat warga pra-sejahtera.',
                'status' => 'diajukan',
                'catatan' => null,
                'files' => ['bukti-dukung-v2/rekap_penerima_bansos_kecamatan.xlsx', 'bukti-dukung-v2/dokumentasi_santunan_sosial.jpg'],
                'day' => 15,
            ],
            5 => [ // Seksi PMD
                'email' => 'kasi.pmd@malangbong.go.id',
                'judul' => 'Laporan Monitoring & Evaluasi Penyaluran Dana Desa Tahap II',
                'ringkasan' => 'Pelaksanaan monitoring dan evaluasi penyerapan Dana Desa (DD) dan Alokasi Dana Desa (ADD), pembinaan tata kelola BUMDesa, serta fasilitasi musyawarah perencanaan pembangunan desa.',
                'status' => 'ditolak',
                'catatan' => 'Mohon lengkapi dokumentasi foto uji petik pembangunan fisik jalan desa di Desa Cihaurkuning dan Desa Girimakmur sebelum disahkan.',
                'files' => ['bukti-dukung-v2/monev_dana_desa_draft.pdf'],
                'day' => 16,
            ],
            6 => [ // Seksi Trantib
                'email' => 'kasi.trantib@malangbong.go.id',
                'judul' => 'Laporan Penyelenggaraan Ketentraman, Ketertiban Umum & Penegakan Perda',
                'ringkasan' => 'Patroli ketertiban umum di kawasan Pasar Malangbong dan jalur protokol, penertiban pedagang kaki lima di bahu jalan, penanganan potensi gangguan kamtibmas, serta siaga bencana alam kewilayahan.',
                'status' => 'disetujui',
                'catatan' => 'Patroli pasar dan penertiban PKL berjalan tertib dan humanis. Disetujui.',
                'files' => ['bukti-dukung-v2/laporan_patroli_trantib_wilayah.pdf', 'bukti-dukung-v2/dokumentasi_penertiban_pkl.jpg'],
                'day' => 10,
            ],
            7 => [ // Seksi Pelayanan
                'email' => 'kasi.pelayanan@malangbong.go.id',
                'judul' => 'Laporan Kinerja Penyelenggaraan Pelayanan Administrasi Terpadu Kecamatan (PATEN)',
                'ringkasan' => 'Penyelenggaraan loket PATEN mencakup penerbitan rekomendasi perizinan, dispensasi nikah, legalisasi surat keterangan, fasilitasi KTP-el dan KIA, serta pengelolaan indeks kepuasan masyarakat (IKM).',
                'status' => 'diajukan',
                'catatan' => null,
                'files' => ['bukti-dukung-v2/rekapitulasi_88_layanan_paten.pdf', 'bukti-dukung-v2/survei_kepuasan_masyarakat_ikm.pdf'],
                'day' => 20,
            ],
        ];

        // Seeding Periode September 2026
        foreach ($unitConfigs as $unitId => $cfg) {
            $user = User::where('email', $cfg['email'])->first();
            if (! $user) {
                continue;
            }

            $unit = UnitOrganisasi::find($unitId);
            $submittedAt = Carbon::create(2026, 9, $cfg['day'], 14, 0, 0);
            $verifiedAt = in_array($cfg['status'], ['disetujui', 'ditolak'])
                ? Carbon::create(2026, 9, min(28, $cfg['day'] + 1), 10, 30, 0)
                : null;

            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => $cfg['judul'].' - September 2026',
                    'unit_organisasi_id' => $unitId,
                    'periode_bulan' => 9,
                    'periode_tahun' => 2026,
                ],
                [
                    'tanggal_pelaporan' => Carbon::create(2026, 9, $cfg['day']),
                    'user_id' => $user->id,
                    'nama_pejabat' => $user->name,
                    'nip_pejabat' => $user->nip ?? '198001012005011001',
                    'jabatan_pejabat' => $user->jabatan ?? ($unit?->nama_unit ?? 'Kepala Seksi'),
                    'ringkasan_kegiatan' => $cfg['ringkasan'],
                    'status' => $cfg['status'],
                    'bukti_dukung' => $cfg['files'],
                    'catatan_verifikasi' => $cfg['catatan'],
                    'verified_by' => in_array($cfg['status'], ['disetujui', 'ditolak']) ? $sekmat?->id : null,
                    'verified_at' => $verifiedAt,
                    'submitted_at' => $submittedAt,
                ]
            );
        }

        // Seeding Periode Agustus 2026 (Semua 7 Unit Disetujui 100%)
        foreach ($unitConfigs as $unitId => $cfg) {
            $user = User::where('email', $cfg['email'])->first();
            if (! $user) {
                continue;
            }

            $unit = UnitOrganisasi::find($unitId);

            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => $cfg['judul'].' - Agustus 2026',
                    'unit_organisasi_id' => $unitId,
                    'periode_bulan' => 8,
                    'periode_tahun' => 2026,
                ],
                [
                    'tanggal_pelaporan' => Carbon::create(2026, 8, min(25, $cfg['day'])),
                    'user_id' => $user->id,
                    'nama_pejabat' => $user->name,
                    'nip_pejabat' => $user->nip ?? '198001012005011001',
                    'jabatan_pejabat' => $user->jabatan ?? ($unit?->nama_unit ?? 'Kepala Seksi'),
                    'ringkasan_kegiatan' => $cfg['ringkasan'],
                    'status' => 'disetujui',
                    'bukti_dukung' => $cfg['files'],
                    'catatan_verifikasi' => 'Laporan kinerja dan kelengkapan bukti dukung fisik periode Agustus 2026 telah diverifikasi tuntas.',
                    'verified_by' => $sekmat?->id,
                    'verified_at' => Carbon::create(2026, 8, 12, 11, 0, 0),
                    'submitted_at' => Carbon::create(2026, 8, min(10, $cfg['day']), 15, 0, 0),
                ]
            );
        }
    }
}
