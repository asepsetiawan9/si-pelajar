<?php

namespace Database\Seeders;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\LaporanDetailLayanan;
use App\Models\LaporanDokumen;
use App\Models\RencanaAksi;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan UserSeeder dijalankan terlebih dahulu agar semua akun tersedia
        $this->call(UserSeeder::class);

        $sekmat = User::where('role', 'admin_kecamatan')->first();
        $camat = User::where('role', 'camat')->first();

        $usersByUnit = User::where('role', 'kasi')
            ->whereNotNull('unit_organisasi_id')
            ->get()
            ->keyBy('unit_organisasi_id');

        // =========================================================================
        // 1. PERIODE AGUSTUS 2026 (Periode Lengkap & Disahkan Camat - Tampilan Default Dashboard)
        // =========================================================================
        $this->seedAgustus2026($sekmat, $camat, $usersByUnit);

        // =========================================================================
        // 2. PERIODE SEPTEMBER 2026 (Periode Berjalan / Aktif Real-Time)
        // =========================================================================
        $this->seedSeptember2026($sekmat, $camat, $usersByUnit);

        // =========================================================================
        // 3. PERIODE JULI 2026 (Periode Historis Sebelumnya)
        // =========================================================================
        $this->seedJuli2026($sekmat, $camat, $usersByUnit);
    }

    /**
     * Seed data bulan Agustus 2026 (Status: Telah Disahkan oleh Camat, 7 Unit Lengkap).
     */
    protected function seedAgustus2026(User $sekmat, User $camat, $usersByUnit): void
    {
        $bulanDate = Carbon::create(2026, 8, 1)->startOfMonth();

        $laporan = Laporan::updateOrCreate(
            ['bulan_pelaporan' => $bulanDate->toDateString()],
            [
                'tahun' => 2026,
                'status' => 'disetujui',
                'cutoff_status' => 'otomatis',
                'catatan_camat' => 'Laporan Kinerja Bulanan Kecamatan Malangbong periode Agustus 2026 telah diteliti dan diverifikasi secara komprehensif. Capaian indikator kinerja seluruh 7 unit operasional dinilai sangat memuaskan, dan serapan anggaran berjalan proporsional serta akuntabel. Laporan ini resmi disahkan sebagai dokumen akuntabilitas publik.',
                'diajukan_oleh' => $sekmat->id,
                'disetujui_oleh' => $camat->id,
                'disetujui_pada' => Carbon::create(2026, 8, 15, 10, 30, 0),
            ]
        );

        $unitConfigs = [
            1 => [ // Subbag Umum
                'latar_belakang' => 'Penyelenggaraan tata kelola administrasi kepegawaian, surat-menyurat, serta evaluasi pelaporan kinerja Kecamatan Malangbong pada bulan Agustus 2026.',
                'keluhan_masyarakat' => 'Tidak terdapat keluhan terkait layanan administrasi internal aparatur.',
                'hambatan' => 'Sebagian data dukung kegiatan dari beberapa seksi memerlukan waktu konfirmasi tambahan.',
                'simpulan' => 'Seluruh agenda pengelolaan administrasi umum dan penyusunan pelaporan akuntabilitas kinerja kecamatan terlaksana tepat waktu.',
                'submitted_at' => Carbon::create(2026, 8, 8, 14, 20, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 10, 0, 0),
                'catatan_sekmat' => 'Dokumen perencanaan dan evaluasi kinerja lengkap dan sesuai format Permenpan-RB. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 1,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 12500000,
                        'realisasi_anggaran' => 11250000,
                    ],
                ],
            ],
            2 => [ // Subbag Keuangan
                'latar_belakang' => 'Pelaksanaan penatausahaan keuangan daerah, rekonsiliasi belanja langsung dan belanja pegawai, serta pengelolaan Barang Milik Daerah (BMD) periode Agustus 2026.',
                'keluhan_masyarakat' => 'Nihil keluhan terkait layanan administrasi keuangan.',
                'hambatan' => 'Penyesuaian kode rekening belanja pada sistem SIPD memerlukan koordinasi teknis dengan BPKAD.',
                'simpulan' => 'Realisasi anggaran belanja berjalan tertib, penatausahaan aset BMD tercatat rapi tanpa kendala administrasi.',
                'submitted_at' => Carbon::create(2026, 8, 7, 16, 30, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 11, 15, 0),
                'catatan_sekmat' => 'Laporan realisasi belanja dan penatausahaan BMD klir, SPJ lengkap. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 2,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 18000000,
                        'realisasi_anggaran' => 16500000,
                    ],
                ],
            ],
            3 => [ // Seksi Pemerintahan
                'latar_belakang' => 'Pembinaan dan pengawasan tata kelola pemerintahan desa, administrasi aparatur desa, dan penataan batas wilayah di wilayah kerja Kecamatan Malangbong.',
                'keluhan_masyarakat' => 'Permohonan klarifikasi batas administrasi tanah desa di dua desa telah difasilitasi musyawarah mufakat.',
                'hambatan' => 'Jadwal monitoring berbenturan dengan agenda musrenbang tingkat desa.',
                'simpulan' => 'Pelaksanaan pembinaan administrasi pemerintahan desa berjalan kondusif dengan tingkat kepatuhan desa mencapai 96%.',
                'submitted_at' => Carbon::create(2026, 8, 8, 9, 45, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 13, 40, 0),
                'catatan_sekmat' => 'Monitoring pembinaan desa di 24 desa berjalan lancar. Rekomendasi pembinaan telah ditindaklanjuti. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 3,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 22000000,
                        'realisasi_anggaran' => 19800000,
                    ],
                ],
            ],
            4 => [ // Seksi Kesra
                'latar_belakang' => 'Fasilitasi penanganan kesejahteraan sosial masyarakat, penanggulangan kemiskinan ekstrem, dan pembinaan keagamaan di Kecamatan Malangbong.',
                'keluhan_masyarakat' => 'Pertanyaan warga mengenai sinkronisasi data penerima bansos DTKS di tingkat desa telah diakomodasi.',
                'hambatan' => 'Verifikasi faktual calon penerima bantuan sosial memerlukan waktu tempuh ke daerah pelosok desa.',
                'simpulan' => 'Penyaluran bantuan sosial tepat sasaran dan koordinasi lintas sektor dalam penanganan PMKS berjalan optimal.',
                'submitted_at' => Carbon::create(2026, 8, 9, 11, 10, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 15, 20, 0),
                'catatan_sekmat' => 'Penyaluran bantuan sosial dan penanganan masalah kesejahteraan sosial terdokumentasi baik. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 4,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 25000000,
                        'realisasi_anggaran' => 21750000,
                    ],
                ],
            ],
            5 => [ // Seksi PMD
                'latar_belakang' => 'Pendampingan kelembagaan masyarakat desa, pembinaan BUMDes, Posyandu, Lembaga Kemasyarakatan Desa (LKD), serta partisipasi gotong royong warga.',
                'keluhan_masyarakat' => 'Kebutuhan pelatihan pembukuan akuntansi bagi pengurus unit usaha BUMDes baru.',
                'hambatan' => 'Keterbatasan waktu pelatihan teknis pengurus BUMDes tingkat kecamatan.',
                'simpulan' => 'Penguatan kapasitas kelembagaan desa menunjukkan peningkatan keaktifan ekonomi produktif dan Posyandu.',
                'submitted_at' => Carbon::create(2026, 8, 8, 15, 0, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 16, 10, 0),
                'catatan_sekmat' => 'Fasilitasi BUMDes dan pembinaan PKK/Posyandu terlaksana komprehensif. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 5,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 20000000,
                        'realisasi_anggaran' => 17600000,
                    ],
                ],
            ],
            6 => [ // Seksi Trantib
                'latar_belakang' => 'Penyelenggaraan ketertiban umum, ketentraman masyarakat, perlindungan masyarakat (Linmas), dan penegakan Peraturan Daerah di wilayah Malangbong.',
                'keluhan_masyarakat' => 'Kepadatan arus lalu lintas dan penataan parkir pasar tumpah pada akhir pekan.',
                'hambatan' => 'Keterbatasan personil patroli gabungan pada jam sibuk aktivitas pasar.',
                'simpulan' => 'Stabilitas ketertiban umum aman terkendali, gangguan trantibum tertangani dengan respons cepat di bawah 30 menit.',
                'submitted_at' => Carbon::create(2026, 8, 9, 8, 30, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 17, 0, 0),
                'catatan_sekmat' => 'Operasi patroli wilayah tertib dan kondusifitas pasar Malangbong terjaga. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 6,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 18500000,
                        'realisasi_anggaran' => 15900000,
                    ],
                ],
            ],
            7 => [ // Seksi Pelayanan
                'latar_belakang' => 'Penyelenggaraan Pelayanan Administrasi Terpadu Kecamatan (PATEN) dan pelaksanaan survei kepuasan masyarakat terhadap mutu layanan prima.',
                'keluhan_masyarakat' => 'Jaringan internet server adminduk sempat mengalami pelambatan selama 2 jam pada tanggal 14 Agustus.',
                'hambatan' => 'Fluktuasi bandwidth internet saat jam sibuk pengajuan perekaman KTP-el.',
                'simpulan' => 'Penyelenggaraan PATEN berjalan sangat efektif melayani 88 pemohon dengan indeks kepuasan masyarakat (IKM) 88.5 (Kategori A / Sangat Baik).',
                'submitted_at' => Carbon::create(2026, 8, 9, 14, 0, 0),
                'verified_at' => Carbon::create(2026, 8, 9, 17, 30, 0),
                'catatan_sekmat' => 'Layanan PATEN mencapai 88 pemohon tanpa komplain. Indeks SKM tercapai memuaskan. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 7, // PATEN
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 28000000,
                        'realisasi_anggaran' => 24500000,
                    ],
                    [
                        'rencana_aksi_id' => 8, // SKM
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 10000000,
                        'realisasi_anggaran' => 8800000,
                    ],
                ],
                'layanans' => [
                    ['nama_layanan' => 'Surat Keterangan Tidak Mampu (SKTM)', 'jumlah' => 36, 'satuan' => 'Pemohon', 'keterangan' => 'Layanan Kesejahteraan Sosial'],
                    ['nama_layanan' => 'Surat Izin Keramaian', 'jumlah' => 14, 'satuan' => 'Pemohon', 'keterangan' => 'Rekomendasi Acara Warga'],
                    ['nama_layanan' => 'Surat Rekomendasi Kredit', 'jumlah' => 8, 'satuan' => 'Pemohon', 'keterangan' => 'Fasilitasi Usaha Ekonomi'],
                    ['nama_layanan' => 'Surat Keterangan Hak Waris', 'jumlah' => 4, 'satuan' => 'Pemohon', 'keterangan' => 'Pencatatan Keperdataan'],
                    ['nama_layanan' => 'Surat Rekomendasi Dispensasi Nikah', 'jumlah' => 7, 'satuan' => 'Pemohon', 'keterangan' => 'KUA Kecamatan Malangbong'],
                    ['nama_layanan' => 'Pengantar / Rekomendasi Akta Kelahiran', 'jumlah' => 9, 'satuan' => 'Pemohon', 'keterangan' => 'Administrasi Kependudukan'],
                    ['nama_layanan' => 'Layanan Legalisasi Dokumen', 'jumlah' => 5, 'satuan' => 'Pemohon', 'keterangan' => 'Verifikasi Dokumen Umum'],
                    ['nama_layanan' => 'Rekomendasi Perekaman / Cetak KTP-el', 'jumlah' => 3, 'satuan' => 'Pemohon', 'keterangan' => 'Sinergi Disdukcapil'],
                    ['nama_layanan' => 'Rekomendasi Kartu Identitas Anak (KIA)', 'jumlah' => 2, 'satuan' => 'Pemohon', 'keterangan' => 'Identitas Kependudukan Anak'],
                    ['nama_layanan' => 'Layanan Pengaduan & Informasi PATEN', 'jumlah' => 0, 'satuan' => 'Layanan', 'keterangan' => 'Helpdesk Pelayanan Publik'],
                ],
            ],
        ];

        foreach ($unitConfigs as $unitId => $config) {
            $user = $usersByUnit->get($unitId) ?? $sekmat;

            $detail = LaporanDetail::updateOrCreate(
                [
                    'laporan_id' => $laporan->id,
                    'unit_organisasi_id' => $unitId,
                ],
                [
                    'user_id' => $user->id,
                    'status' => 'disetujui',
                    'catatan_verifikasi_sekmat' => $config['catatan_sekmat'],
                    'latar_belakang' => $config['latar_belakang'],
                    'keterangan_keterkaitan' => 'Mendukung Sasaran Strategis Perjanjian Kinerja Camat Malangbong Tahun Anggaran 2026.',
                    'keluhan_masyarakat' => $config['keluhan_masyarakat'] ?? 'Nihil keluhan masyarakat.',
                    'hambatan' => $config['hambatan'] ?? 'Kegiatan operasional berjalan sesuai jadwal.',
                    'simpulan' => $config['simpulan'],
                    'is_late' => false,
                    'is_dispensasi' => false,
                    'submitted_at' => $config['submitted_at'],
                    'verified_at' => $config['verified_at'],
                    'verified_by' => $sekmat->id,
                ]
            );

            // Simpan Indikators
            foreach ($config['indikators'] as $ind) {
                $target = (float) $ind['target'];
                $realisasi = (float) $ind['realisasi'];
                $pagu = (float) $ind['pagu'];
                $realisasiAnggaran = (float) $ind['realisasi_anggaran'];

                $persenKinerja = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                $predikatEfektivitas = LaporanDetailIndikator::calculatePredikatEfektivitas($persenKinerja);
                $persenAnggaran = LaporanDetailIndikator::calculatePersentaseAnggaran($pagu, $realisasiAnggaran);
                $predikatEfisiensi = LaporanDetailIndikator::calculatePredikatEfisiensi($persenAnggaran);

                LaporanDetailIndikator::updateOrCreate(
                    [
                        'laporan_detail_id' => $detail->id,
                        'rencana_aksi_id' => $ind['rencana_aksi_id'],
                    ],
                    [
                        'target_kinerja' => $target,
                        'realisasi_kinerja' => $realisasi,
                        'persentase_kinerja' => $persenKinerja,
                        'predikat_efektivitas' => $predikatEfektivitas,
                        'anggaran_pagu' => $pagu,
                        'realisasi_anggaran' => $realisasiAnggaran,
                        'persentase_anggaran' => $persenAnggaran,
                        'predikat_efisiensi' => $predikatEfisiensi,
                    ]
                );
            }

            // Simpan Layanan PATEN (khusus Seksi Pelayanan)
            if (! empty($config['layanans'])) {
                foreach ($config['layanans'] as $layanan) {
                    LaporanDetailLayanan::updateOrCreate(
                        [
                            'laporan_detail_id' => $detail->id,
                            'nama_layanan' => $layanan['nama_layanan'],
                        ],
                        [
                            'jumlah' => $layanan['jumlah'],
                            'satuan' => $layanan['satuan'],
                            'keterangan' => $layanan['keterangan'],
                        ]
                    );
                }
            }

            // Lampiran Dokumen Bukti Fisik
            LaporanDokumen::updateOrCreate(
                [
                    'laporan_detail_id' => $detail->id,
                    'nama_dokumen' => 'Dokumentasi_Kinerja_Unit_'.$unitId.'_Agustus_2026.pdf',
                ],
                [
                    'file_path' => 'laporan-dokumen/sample_unit_'.$unitId.'_agustus.pdf',
                    'file_type' => 'application/pdf',
                    'file_size' => 204800,
                ]
            );
        }
    }

    /**
     * Seed data bulan September 2026 (Periode Berjalan / Real-Time).
     * Mensimulasikan kondisi riil: 2 disetujui, 1 diajukan, 1 ditolak, 3 draft.
     */
    protected function seedSeptember2026(User $sekmat, User $camat, $usersByUnit): void
    {
        $bulanDate = Carbon::create(2026, 9, 1)->startOfMonth();

        $laporan = Laporan::updateOrCreate(
            ['bulan_pelaporan' => $bulanDate->toDateString()],
            [
                'tahun' => 2026,
                'status' => 'menunggu_verifikasi',
                'cutoff_status' => 'terbuka',
                'catatan_cutoff' => 'Akses pengisian dibuka fleksibel untuk penyusunan laporan berjalan triwulan III.',
                'cutoff_updated_by' => $sekmat->id,
                'cutoff_updated_at' => Carbon::create(2026, 9, 20, 9, 0, 0),
                'diajukan_oleh' => null,
                'disetujui_oleh' => null,
                'disetujui_pada' => null,
            ]
        );

        $unitConfigs = [
            // 1. Seksi Pelayanan -> Disetujui Sekmat
            7 => [
                'status' => 'disetujui',
                'latar_belakang' => 'Pelaksanaan PATEN dan survei kepuasan masyarakat periode September 2026.',
                'keluhan_masyarakat' => 'Nihil komplain, antrean tertata dengan tiket antrean digital.',
                'hambatan' => 'Tidak terdapat kendala sistematis.',
                'simpulan' => 'Pelayanan PATEN berjalan sangat efektif melayani 68 pemohon.',
                'submitted_at' => Carbon::create(2026, 9, 22, 10, 0, 0),
                'verified_at' => Carbon::create(2026, 9, 23, 14, 0, 0),
                'verified_by' => $sekmat->id,
                'catatan_sekmat' => 'Data PATEN September terverifikasi akurat dan lengkap. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 7,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 28000000,
                        'realisasi_anggaran' => 22400000,
                    ],
                    [
                        'rencana_aksi_id' => 8,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 10000000,
                        'realisasi_anggaran' => 8200000,
                    ],
                ],
                'layanans' => [
                    ['nama_layanan' => 'Surat Keterangan Tidak Mampu (SKTM)', 'jumlah' => 28, 'satuan' => 'Pemohon', 'keterangan' => 'Layanan Kesejahteraan Sosial'],
                    ['nama_layanan' => 'Surat Izin Keramaian', 'jumlah' => 10, 'satuan' => 'Pemohon', 'keterangan' => 'Rekomendasi Acara Warga'],
                    ['nama_layanan' => 'Surat Rekomendasi Kredit', 'jumlah' => 6, 'satuan' => 'Pemohon', 'keterangan' => 'Fasilitasi Usaha Ekonomi'],
                    ['nama_layanan' => 'Surat Keterangan Hak Waris', 'jumlah' => 3, 'satuan' => 'Pemohon', 'keterangan' => 'Pencatatan Keperdataan'],
                    ['nama_layanan' => 'Surat Rekomendasi Dispensasi Nikah', 'jumlah' => 5, 'satuan' => 'Pemohon', 'keterangan' => 'KUA Kecamatan Malangbong'],
                    ['nama_layanan' => 'Pengantar / Rekomendasi Akta Kelahiran', 'jumlah' => 7, 'satuan' => 'Pemohon', 'keterangan' => 'Administrasi Kependudukan'],
                    ['nama_layanan' => 'Layanan Legalisasi Dokumen', 'jumlah' => 4, 'satuan' => 'Pemohon', 'keterangan' => 'Verifikasi Dokumen Umum'],
                    ['nama_layanan' => 'Rekomendasi Perekaman / Cetak KTP-el', 'jumlah' => 3, 'satuan' => 'Pemohon', 'keterangan' => 'Sinergi Disdukcapil'],
                    ['nama_layanan' => 'Rekomendasi Kartu Identitas Anak (KIA)', 'jumlah' => 2, 'satuan' => 'Pemohon', 'keterangan' => 'Identitas Kependudukan Anak'],
                    ['nama_layanan' => 'Layanan Pengaduan & Informasi PATEN', 'jumlah' => 0, 'satuan' => 'Layanan', 'keterangan' => 'Helpdesk Pelayanan Publik'],
                ],
            ],

            // 2. Seksi Pemerintahan -> Disetujui Sekmat
            3 => [
                'status' => 'disetujui',
                'latar_belakang' => 'Monitoring tertib administrasi desa dan fasilitasi aparatur desa triwulan III.',
                'keluhan_masyarakat' => 'Nihil.',
                'hambatan' => 'Akses jalan beberapa dusun desa terhambat perbaikan jalan kewilayahan.',
                'simpulan' => 'Pembinaan administrasi pemerintahan desa berjalan tepat sasaran.',
                'submitted_at' => Carbon::create(2026, 9, 23, 11, 30, 0),
                'verified_at' => Carbon::create(2026, 9, 24, 9, 15, 0),
                'verified_by' => $sekmat->id,
                'catatan_sekmat' => 'Laporan pembinaan desa September lengkap dan valid. Disetujui.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 3,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 22000000,
                        'realisasi_anggaran' => 18500000,
                    ],
                ],
            ],

            // 3. Seksi Kesra -> Menunggu Verifikasi Sekmat (Diajukan)
            4 => [
                'status' => 'diajukan',
                'latar_belakang' => 'Fasilitasi penyaluran bantuan sosial kemiskinan dan penanganan stunting desa.',
                'keluhan_masyarakat' => 'Permintaan penambahan kuota PMT balita di Posyandu dusun.',
                'hambatan' => 'Sinkronisasi data balita stunting dengan Puskesmas Malangbong masih difinalisasi.',
                'simpulan' => 'Program bansos dan pencegahan stunting terlaksana sesuai rencana kerja.',
                'submitted_at' => Carbon::create(2026, 9, 25, 14, 0, 0),
                'verified_at' => null,
                'verified_by' => null,
                'catatan_sekmat' => null,
                'indikators' => [
                    [
                        'rencana_aksi_id' => 4,
                        'target' => 1,
                        'realisasi' => 1,
                        'pagu' => 25000000,
                        'realisasi_anggaran' => 20500000,
                    ],
                ],
            ],

            // 4. Seksi PMD -> Ditolak / Dikembalikan untuk Revisi
            5 => [
                'status' => 'ditolak',
                'latar_belakang' => 'Pendampingan kelembagaan BUMDes dan program pemberdayaan masyarakat desa.',
                'keluhan_masyarakat' => 'Keterlambatan penyampaian LPJ kegiatan bimtek kelembagaan.',
                'hambatan' => 'Bukti dukung daftar hadir bimbingan teknis belum terunggah lengkap.',
                'simpulan' => 'Kegiatan pembinaan telah dilaksanakan namun kelengkapan administrasi SPJ masih diverifikasi ulang.',
                'submitted_at' => Carbon::create(2026, 9, 24, 16, 0, 0),
                'verified_at' => Carbon::create(2026, 9, 25, 10, 0, 0),
                'verified_by' => $sekmat->id,
                'catatan_sekmat' => 'Mohon lengkapi rincian realisasi belanja pelatihan kelembagaan BUMDes dan lampirkan daftar hadir peserta serta dokumentasi foto kegiatan.',
                'indikators' => [
                    [
                        'rencana_aksi_id' => 5,
                        'target' => 1,
                        'realisasi' => 0.8,
                        'pagu' => 20000000,
                        'realisasi_anggaran' => 14000000,
                    ],
                ],
            ],

            // 5. Subbag Umum -> Status Draf
            1 => [
                'status' => 'draft',
                'latar_belakang' => 'Penyusunan berkas administrasi umum, arsip persuratan, dan rekap evaluasi bulanan.',
                'keluhan_masyarakat' => 'Nihil.',
                'hambatan' => 'Proses verifikasi nota dinas masih berlangsung.',
                'simpulan' => 'Penginputan draft laporan kinerja Subbag Umum masih dalam penyelesaian.',
                'submitted_at' => null,
                'verified_at' => null,
                'verified_by' => null,
                'catatan_sekmat' => null,
                'indikators' => [
                    [
                        'rencana_aksi_id' => 1,
                        'target' => 1,
                        'realisasi' => 0.85,
                        'pagu' => 12500000,
                        'realisasi_anggaran' => 9500000,
                    ],
                ],
            ],

            // 6. Subbag Keuangan -> Status Draf
            2 => [
                'status' => 'draft',
                'latar_belakang' => 'Rekonsiliasi belanja kegiatan dan penatausahaan aset BMD bulan September 2026.',
                'keluhan_masyarakat' => 'Nihil.',
                'hambatan' => 'Menunggu pencairan SP2D termin akhir bulan.',
                'simpulan' => 'Penatausahaan keuangan berjalan sesuai prosedur perbendaharaan daerah.',
                'submitted_at' => null,
                'verified_at' => null,
                'verified_by' => null,
                'catatan_sekmat' => null,
                'indikators' => [
                    [
                        'rencana_aksi_id' => 2,
                        'target' => 1,
                        'realisasi' => 0.9,
                        'pagu' => 18000000,
                        'realisasi_anggaran' => 14800000,
                    ],
                ],
            ],

            // 7. Seksi Trantib -> Status Draf
            6 => [
                'status' => 'draft',
                'latar_belakang' => 'Pelaksanaan patroli ketentraman wilayah dan penertiban pedagang kaki lima pasar Malangbong.',
                'keluhan_masyarakat' => 'Laporan warga mengenai kebisingan sound system pada acara perayaan warga.',
                'hambatan' => 'Penertiban memerlukan koordinasi berkala dengan aparat Babinsa dan Bhabinkamtibmas.',
                'simpulan' => 'Operasi ketertiban rutin terlaksana dengan situasi wilayah tetap kondusif.',
                'submitted_at' => null,
                'verified_at' => null,
                'verified_by' => null,
                'catatan_sekmat' => null,
                'indikators' => [
                    [
                        'rencana_aksi_id' => 6,
                        'target' => 1,
                        'realisasi' => 0.95,
                        'pagu' => 18500000,
                        'realisasi_anggaran' => 15200000,
                    ],
                ],
            ],
        ];

        foreach ($unitConfigs as $unitId => $config) {
            $user = $usersByUnit->get($unitId) ?? $sekmat;

            $detail = LaporanDetail::updateOrCreate(
                [
                    'laporan_id' => $laporan->id,
                    'unit_organisasi_id' => $unitId,
                ],
                [
                    'user_id' => $user->id,
                    'status' => $config['status'],
                    'catatan_verifikasi_sekmat' => $config['catatan_sekmat'],
                    'latar_belakang' => $config['latar_belakang'],
                    'keterangan_keterkaitan' => 'Mendukung Sasaran Strategis Perjanjian Kinerja Camat Malangbong Tahun Anggaran 2026.',
                    'keluhan_masyarakat' => $config['keluhan_masyarakat'] ?? 'Nihil.',
                    'hambatan' => $config['hambatan'] ?? 'Nihil.',
                    'simpulan' => $config['simpulan'],
                    'is_late' => false,
                    'is_dispensasi' => false,
                    'submitted_at' => $config['submitted_at'],
                    'verified_at' => $config['verified_at'],
                    'verified_by' => $config['verified_by'],
                ]
            );

            // Simpan Indikators
            foreach ($config['indikators'] as $ind) {
                $target = (float) $ind['target'];
                $realisasi = (float) $ind['realisasi'];
                $pagu = (float) $ind['pagu'];
                $realisasiAnggaran = (float) $ind['realisasi_anggaran'];

                $persenKinerja = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                $predikatEfektivitas = LaporanDetailIndikator::calculatePredikatEfektivitas($persenKinerja);
                $persenAnggaran = LaporanDetailIndikator::calculatePersentaseAnggaran($pagu, $realisasiAnggaran);
                $predikatEfisiensi = LaporanDetailIndikator::calculatePredikatEfisiensi($persenAnggaran);

                LaporanDetailIndikator::updateOrCreate(
                    [
                        'laporan_detail_id' => $detail->id,
                        'rencana_aksi_id' => $ind['rencana_aksi_id'],
                    ],
                    [
                        'target_kinerja' => $target,
                        'realisasi_kinerja' => $realisasi,
                        'persentase_kinerja' => $persenKinerja,
                        'predikat_efektivitas' => $predikatEfektivitas,
                        'anggaran_pagu' => $pagu,
                        'realisasi_anggaran' => $realisasiAnggaran,
                        'persentase_anggaran' => $persenAnggaran,
                        'predikat_efisiensi' => $predikatEfisiensi,
                    ]
                );
            }

            // Simpan Layanan PATEN jika ada
            if (! empty($config['layanans'])) {
                foreach ($config['layanans'] as $layanan) {
                    LaporanDetailLayanan::updateOrCreate(
                        [
                            'laporan_detail_id' => $detail->id,
                            'nama_layanan' => $layanan['nama_layanan'],
                        ],
                        [
                            'jumlah' => $layanan['jumlah'],
                            'satuan' => $layanan['satuan'],
                            'keterangan' => $layanan['keterangan'],
                        ]
                    );
                }
            }
        }
    }

    /**
     * Seed data bulan Juli 2026 (Historis Lengkap & Disahkan Camat).
     */
    protected function seedJuli2026(User $sekmat, User $camat, $usersByUnit): void
    {
        $bulanDate = Carbon::create(2026, 7, 1)->startOfMonth();

        $laporan = Laporan::updateOrCreate(
            ['bulan_pelaporan' => $bulanDate->toDateString()],
            [
                'tahun' => 2026,
                'status' => 'disetujui',
                'cutoff_status' => 'otomatis',
                'catatan_camat' => 'Laporan Kinerja Bulanan Kecamatan Malangbong periode Juli 2026 telah diteliti dan disahkan. Realisasi program triwulan kedua dan awal semester II berjalan sesuai target.',
                'diajukan_oleh' => $sekmat->id,
                'disetujui_oleh' => $camat->id,
                'disetujui_pada' => Carbon::create(2026, 7, 14, 11, 0, 0),
            ]
        );

        $units = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();

        foreach ($units as $unit) {
            $user = $usersByUnit->get($unit->id) ?? $sekmat;

            $detail = LaporanDetail::updateOrCreate(
                [
                    'laporan_id' => $laporan->id,
                    'unit_organisasi_id' => $unit->id,
                ],
                [
                    'user_id' => $user->id,
                    'status' => 'disetujui',
                    'catatan_verifikasi_sekmat' => 'Laporan kinerja periode Juli 2026 terverifikasi lengkap dan sesuai target. Disetujui.',
                    'latar_belakang' => 'Pelaksanaan program dan kegiatan operasional '.$unit->nama_unit.' periode Juli 2026.',
                    'keterangan_keterkaitan' => 'Mendukung Sasaran Strategis Perjanjian Kinerja Camat Malangbong Tahun Anggaran 2026.',
                    'keluhan_masyarakat' => 'Nihil keluhan masyarakat.',
                    'hambatan' => 'Kegiatan berjalan tertib dan lancar.',
                    'simpulan' => 'Capaian kinerja fisik dan penyerapan belanja unit '.$unit->nama_unit.' terlaksana 100%.',
                    'is_late' => false,
                    'is_dispensasi' => false,
                    'submitted_at' => Carbon::create(2026, 7, 8, 11, 0, 0),
                    'verified_at' => Carbon::create(2026, 7, 9, 14, 0, 0),
                    'verified_by' => $sekmat->id,
                ]
            );

            // Ambil rencana aksi untuk unit ini
            $rencanaAksis = RencanaAksi::where('unit_organisasi_id', $unit->id)->get();
            foreach ($rencanaAksis as $rencana) {
                $pagu = 15000000.0;
                $realisasiAnggaran = 13200000.0;
                $target = 1.0;
                $realisasi = 1.0;

                $persenKinerja = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                $predikatEfektivitas = LaporanDetailIndikator::calculatePredikatEfektivitas($persenKinerja);
                $persenAnggaran = LaporanDetailIndikator::calculatePersentaseAnggaran($pagu, $realisasiAnggaran);
                $predikatEfisiensi = LaporanDetailIndikator::calculatePredikatEfisiensi($persenAnggaran);

                LaporanDetailIndikator::updateOrCreate(
                    [
                        'laporan_detail_id' => $detail->id,
                        'rencana_aksi_id' => $rencana->id,
                    ],
                    [
                        'target_kinerja' => $target,
                        'realisasi_kinerja' => $realisasi,
                        'persentase_kinerja' => $persenKinerja,
                        'predikat_efektivitas' => $predikatEfektivitas,
                        'anggaran_pagu' => $pagu,
                        'realisasi_anggaran' => $realisasiAnggaran,
                        'persentase_anggaran' => $persenAnggaran,
                        'predikat_efisiensi' => $predikatEfisiensi,
                    ]
                );
            }

            // Jika Seksi Pelayanan, tambahkan layanan PATEN Juli
            if ($unit->kode_unit === 'SEKSI-PELAYANAN') {
                $presets = [
                    ['nama_layanan' => 'Surat Keterangan Tidak Mampu (SKTM)', 'jumlah' => 40, 'satuan' => 'Pemohon', 'keterangan' => 'Layanan Kesejahteraan Sosial'],
                    ['nama_layanan' => 'Surat Izin Keramaian', 'jumlah' => 18, 'satuan' => 'Pemohon', 'keterangan' => 'Rekomendasi Acara Warga'],
                    ['nama_layanan' => 'Surat Rekomendasi Kredit', 'jumlah' => 10, 'satuan' => 'Pemohon', 'keterangan' => 'Fasilitasi Usaha Ekonomi'],
                    ['nama_layanan' => 'Surat Keterangan Hak Waris', 'jumlah' => 5, 'satuan' => 'Pemohon', 'keterangan' => 'Pencatatan Keperdataan'],
                    ['nama_layanan' => 'Surat Rekomendasi Dispensasi Nikah', 'jumlah' => 8, 'satuan' => 'Pemohon', 'keterangan' => 'KUA Kecamatan Malangbong'],
                    ['nama_layanan' => 'Pengantar / Rekomendasi Akta Kelahiran', 'jumlah' => 12, 'satuan' => 'Pemohon', 'keterangan' => 'Administrasi Kependudukan'],
                    ['nama_layanan' => 'Layanan Legalisasi Dokumen', 'jumlah' => 6, 'satuan' => 'Pemohon', 'keterangan' => 'Verifikasi Dokumen Umum'],
                    ['nama_layanan' => 'Rekomendasi Perekaman / Cetak KTP-el', 'jumlah' => 4, 'satuan' => 'Pemohon', 'keterangan' => 'Sinergi Disdukcapil'],
                    ['nama_layanan' => 'Rekomendasi Kartu Identitas Anak (KIA)', 'jumlah' => 3, 'satuan' => 'Pemohon', 'keterangan' => 'Identitas Kependudukan Anak'],
                    ['nama_layanan' => 'Layanan Pengaduan & Informasi PATEN', 'jumlah' => 0, 'satuan' => 'Layanan', 'keterangan' => 'Helpdesk Pelayanan Publik'],
                ];

                foreach ($presets as $p) {
                    LaporanDetailLayanan::updateOrCreate(
                        [
                            'laporan_detail_id' => $detail->id,
                            'nama_layanan' => $p['nama_layanan'],
                        ],
                        [
                            'jumlah' => $p['jumlah'],
                            'satuan' => $p['satuan'],
                            'keterangan' => $p['keterangan'],
                        ]
                    );
                }
            }
        }
    }
}
