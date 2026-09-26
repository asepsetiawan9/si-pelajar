<?php

namespace Tests\Feature;

use App\Filament\Resources\LaporanDetailResource;
use App\Filament\Resources\LaporanKinerjaV2Resource;
use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\LaporanKinerjaV2Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanKinerjaV2Test extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $sekmat;

    protected User $kasiPelayanan;

    protected User $kasiPemerintahan;

    protected UnitOrganisasi $unitPelayanan;

    protected UnitOrganisasi $unitPemerintahan;

    protected LaporanKinerjaV2Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitPelayanan = UnitOrganisasi::create([
            'nama_unit' => 'Seksi Pelayanan Umum',
            'kode_unit' => 'YANUM',
            'urutan' => 7,
            'wajib_dilaporkan' => true,
        ]);

        $this->unitPemerintahan = UnitOrganisasi::create([
            'nama_unit' => 'Seksi Pemerintahan',
            'kode_unit' => 'PEM',
            'urutan' => 3,
            'wajib_dilaporkan' => true,
        ]);

        $this->superadmin = User::factory()->create([
            'name' => 'Superadmin IT',
            'email' => 'superadmin@test.com',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->sekmat = User::factory()->create([
            'name' => 'Sekretaris Camat',
            'email' => 'sekmat@test.com',
            'role' => 'admin_kecamatan',
            'is_active' => true,
        ]);

        $this->kasiPelayanan = User::factory()->create([
            'name' => 'Kasi Pelayanan',
            'nip' => '198001012005011001',
            'jabatan' => 'Kepala Seksi Pelayanan Umum',
            'email' => 'kasi.pelayanan@test.com',
            'role' => 'kasi',
            'unit_organisasi_id' => $this->unitPelayanan->id,
            'is_active' => true,
        ]);

        $this->kasiPemerintahan = User::factory()->create([
            'name' => 'Kasi Pemerintahan',
            'nip' => '198202022006021002',
            'jabatan' => 'Kepala Seksi Pemerintahan',
            'email' => 'kasi.pem@test.com',
            'role' => 'kasi',
            'unit_organisasi_id' => $this->unitPemerintahan->id,
            'is_active' => true,
        ]);

        $this->service = app(LaporanKinerjaV2Service::class);
    }

    public function test_v1_resource_is_hidden_from_navigation(): void
    {
        $this->assertFalse(LaporanDetailResource::shouldRegisterNavigation());
        $this->assertTrue(LaporanKinerjaV2Resource::shouldRegisterNavigation());
    }

    public function test_kasi_can_create_laporan_v2_with_draft_status(): void
    {
        Storage::fake('public');

        $laporan = LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Kinerja Unit Seksi Pelayanan September 2026',
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'tanggal_pelaporan' => now()->toDateString(),
            'unit_organisasi_id' => $this->kasiPelayanan->unit_organisasi_id,
            'user_id' => $this->kasiPelayanan->id,
            'nama_pejabat' => $this->kasiPelayanan->name,
            'nip_pejabat' => $this->kasiPelayanan->nip,
            'jabatan_pejabat' => $this->kasiPelayanan->jabatan,
            'status' => 'draft',
            'bukti_dukung' => ['bukti-dukung-v2/dokumen_kegiatan.pdf'],
        ]);

        $this->assertDatabaseHas('laporan_kinerja_v2', [
            'id' => $laporan->id,
            'judul_pelaporan' => 'Laporan Kinerja Unit Seksi Pelayanan September 2026',
            'status' => 'draft',
            'nama_pejabat' => 'Kasi Pelayanan',
        ]);

        $this->assertTrue($laporan->isDraft());
        $this->assertEquals('Draft (Belum Dikirim)', $laporan->status_label);
    }

    public function test_kasi_can_submit_laporan_v2_for_verification(): void
    {
        $laporan = LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Pelayanan Siap Verifikasi',
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'tanggal_pelaporan' => now()->toDateString(),
            'unit_organisasi_id' => $this->kasiPelayanan->unit_organisasi_id,
            'user_id' => $this->kasiPelayanan->id,
            'nama_pejabat' => $this->kasiPelayanan->name,
            'nip_pejabat' => $this->kasiPelayanan->nip,
            'jabatan_pejabat' => $this->kasiPelayanan->jabatan,
            'status' => 'draft',
        ]);

        $updated = $this->service->kirimLaporan($laporan, $this->kasiPelayanan);

        $this->assertEquals('diajukan', $updated->status);
        $this->assertNotNull($updated->submitted_at);
        $this->assertEquals('Perlu Verifikasi', $updated->status_label);

        // Verifier (Sekmat) menerima database notifikasi
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->sekmat->id,
        ]);
    }

    public function test_sekmat_can_approve_laporan_v2(): void
    {
        $laporan = LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Pelayanan Siap Setujui',
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'tanggal_pelaporan' => now()->toDateString(),
            'unit_organisasi_id' => $this->kasiPelayanan->unit_organisasi_id,
            'user_id' => $this->kasiPelayanan->id,
            'nama_pejabat' => $this->kasiPelayanan->name,
            'nip_pejabat' => $this->kasiPelayanan->nip,
            'jabatan_pejabat' => $this->kasiPelayanan->jabatan,
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        $approved = $this->service->setujuiLaporan($laporan, $this->sekmat, 'Dokumen lengkap dan valid.');

        $this->assertEquals('disetujui', $approved->status);
        $this->assertEquals($this->sekmat->id, $approved->verified_by);
        $this->assertNotNull($approved->verified_at);
        $this->assertEquals('Dokumen lengkap dan valid.', $approved->catatan_verifikasi);
        $this->assertEquals('Disetujui / Terverifikasi', $approved->status_label);

        // Author (Kasi Pelayanan) menerima notifikasi persetujuan
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->kasiPelayanan->id,
        ]);
    }

    public function test_sekmat_can_reject_laporan_v2_with_revision_notes(): void
    {
        $laporan = LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Kurang Berkas',
            'periode_bulan' => 9,
            'periode_tahun' => 2026,
            'tanggal_pelaporan' => now()->toDateString(),
            'unit_organisasi_id' => $this->kasiPelayanan->unit_organisasi_id,
            'user_id' => $this->kasiPelayanan->id,
            'nama_pejabat' => $this->kasiPelayanan->name,
            'nip_pejabat' => $this->kasiPelayanan->nip,
            'jabatan_pejabat' => $this->kasiPelayanan->jabatan,
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        $rejected = $this->service->kembalikanLaporan($laporan, $this->sekmat, 'Mohon lampirkan SPTJM bermaterai.');

        $this->assertEquals('ditolak', $rejected->status);
        $this->assertEquals('Mohon lampirkan SPTJM bermaterai.', $rejected->catatan_verifikasi);
        $this->assertEquals('Perlu Revisi', $rejected->status_label);

        // Author (Kasi Pelayanan) menerima notifikasi revisi
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->kasiPelayanan->id,
        ]);
    }

    public function test_row_level_security_kasi_only_sees_own_unit_reports(): void
    {
        LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Pelayanan 1',
            'unit_organisasi_id' => $this->unitPelayanan->id,
            'user_id' => $this->kasiPelayanan->id,
            'nama_pejabat' => $this->kasiPelayanan->name,
            'nip_pejabat' => $this->kasiPelayanan->nip,
            'jabatan_pejabat' => $this->kasiPelayanan->jabatan,
            'status' => 'draft',
        ]);

        LaporanKinerjaV2::create([
            'judul_pelaporan' => 'Laporan Pemerintahan 1',
            'unit_organisasi_id' => $this->unitPemerintahan->id,
            'user_id' => $this->kasiPemerintahan->id,
            'nama_pejabat' => $this->kasiPemerintahan->name,
            'nip_pejabat' => $this->kasiPemerintahan->nip,
            'jabatan_pejabat' => $this->kasiPemerintahan->jabatan,
            'status' => 'draft',
        ]);

        $this->actingAs($this->kasiPelayanan);
        $pelayananQuery = LaporanKinerjaV2Resource::getEloquentQuery()->get();

        $this->assertCount(1, $pelayananQuery);
        $this->assertEquals('Laporan Pelayanan 1', $pelayananQuery->first()->judul_pelaporan);

        $this->actingAs($this->sekmat);
        $sekmatQuery = LaporanKinerjaV2Resource::getEloquentQuery()->get();
        $this->assertCount(2, $sekmatQuery);
    }

    public function test_filament_resource_pages_can_be_rendered(): void
    {
        $this->actingAs($this->superadmin);

        $this->get(LaporanKinerjaV2Resource::getUrl('index'))
            ->assertSuccessful();

        $this->get(LaporanKinerjaV2Resource::getUrl('create'))
            ->assertSuccessful();
    }
}
