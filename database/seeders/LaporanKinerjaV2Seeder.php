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
        $units = UnitOrganisasi::all()->keyBy('kode_unit');

        // 1. Seksi Pelayanan Umum - Status: Perlu Verifikasi (Diajukan)
        $yanumUser = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        if ($yanumUser && isset($units['YANUM'])) {
            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => 'Laporan Kinerja Seksi Pelayanan Umum - September 2026',
                    'unit_organisasi_id' => $units['YANUM']->id,
                ],
                [
                    'periode_bulan' => 9,
                    'periode_tahun' => 2026,
                    'tanggal_pelaporan' => Carbon::create(2026, 9, 20),
                    'user_id' => $yanumUser->id,
                    'nama_pejabat' => $yanumUser->name,
                    'nip_pejabat' => $yanumUser->nip ?? '197906152008011005',
                    'jabatan_pejabat' => $yanumUser->jabatan ?? 'Kepala Seksi Pelayanan Umum',
                    'status' => 'diajukan',
                    'bukti_dukung' => ['bukti-dukung-v2/rekapitulasi_layanan_paten_september.pdf'],
                    'submitted_at' => Carbon::create(2026, 9, 20, 14, 30),
                ]
            );
        }

        // 2. Seksi Ketenteraman & Ketertiban - Status: Disetujui
        $trantibUser = User::where('email', 'kasi.trantib@malangbong.go.id')->first();
        if ($trantibUser && isset($units['TRANTIB'])) {
            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => 'Laporan Kinerja Seksi Trantib - September 2026',
                    'unit_organisasi_id' => $units['TRANTIB']->id,
                ],
                [
                    'periode_bulan' => 9,
                    'periode_tahun' => 2026,
                    'tanggal_pelaporan' => Carbon::create(2026, 9, 18),
                    'user_id' => $trantibUser->id,
                    'nama_pejabat' => $trantibUser->name,
                    'nip_pejabat' => $trantibUser->nip ?? '197805122007011004',
                    'jabatan_pejabat' => $trantibUser->jabatan ?? 'Kepala Seksi Ketenteraman dan Ketertiban',
                    'status' => 'disetujui',
                    'bukti_dukung' => ['bukti-dukung-v2/patroli_wilayah_pasar_malangbong.pdf'],
                    'catatan_verifikasi' => 'Data patroli dan penertiban PKL lengkap dan terverifikasi.',
                    'verified_by' => $sekmat?->id,
                    'verified_at' => Carbon::create(2026, 9, 19, 10, 15),
                    'submitted_at' => Carbon::create(2026, 9, 18, 16, 0),
                ]
            );
        }

        // 3. Seksi PMD - Status: Ditolak (Perlu Revisi)
        $pmdUser = User::where('email', 'kasi.pmd@malangbong.go.id')->first();
        if ($pmdUser && isset($units['PMD'])) {
            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => 'Laporan Kinerja Seksi PMD - September 2026',
                    'unit_organisasi_id' => $units['PMD']->id,
                ],
                [
                    'periode_bulan' => 9,
                    'periode_tahun' => 2026,
                    'tanggal_pelaporan' => Carbon::create(2026, 9, 17),
                    'user_id' => $pmdUser->id,
                    'nama_pejabat' => $pmdUser->name,
                    'nip_pejabat' => $pmdUser->nip ?? '198203102009021003',
                    'jabatan_pejabat' => $pmdUser->jabatan ?? 'Kepala Seksi Perekonomian dan PMD',
                    'status' => 'ditolak',
                    'bukti_dukung' => ['bukti-dukung-v2/monev_dana_desa_draft.docx'],
                    'catatan_verifikasi' => 'Mohon lampirkan dokumentasi foto kegiatan monitoring dana desa di Desa Cihaurkuning dan Girimakmur.',
                    'verified_by' => $sekmat?->id,
                    'verified_at' => Carbon::create(2026, 9, 18, 9, 0),
                    'submitted_at' => Carbon::create(2026, 9, 17, 15, 30),
                ]
            );
        }

        // 4. Seksi Pemerintahan - Status: Draft
        $pemUser = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->first();
        if ($pemUser && isset($units['PEM'])) {
            LaporanKinerjaV2::updateOrCreate(
                [
                    'judul_pelaporan' => 'Laporan Kinerja Seksi Pemerintahan - September 2026',
                    'unit_organisasi_id' => $units['PEM']->id,
                ],
                [
                    'periode_bulan' => 9,
                    'periode_tahun' => 2026,
                    'tanggal_pelaporan' => Carbon::create(2026, 9, 25),
                    'user_id' => $pemUser->id,
                    'nama_pejabat' => $pemUser->name,
                    'nip_pejabat' => $pemUser->nip ?? '198104112008011002',
                    'jabatan_pejabat' => $pemUser->jabatan ?? 'Kepala Seksi Pemerintahan',
                    'status' => 'draft',
                    'bukti_dukung' => null,
                ]
            );
        }
    }
}
