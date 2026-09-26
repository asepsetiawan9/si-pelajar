<?php

namespace Tests\Feature;

use App\Filament\Resources\LaporanKecamatanResource;
use App\Filament\Resources\VerifikasiLaporanUnitResource;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\LaporanDetailLayanan;
use App\Models\RencanaAksi;
use App\Models\SasaranStrategis;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\LaporanApprovalService;
use App\Services\LaporanPdfService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LaporanApprovalAndPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $sekmat;

    protected User $camat;

    protected User $kasiPelayanan;

    protected User $kasiPemerintahan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->superadmin = User::where('email', 'superadmin@malangbong.go.id')->firstOrFail();
        $this->sekmat = User::where('email', 'sekmat@malangbong.go.id')->firstOrFail();
        $this->camat = User::where('email', 'camat@malangbong.go.id')->firstOrFail();
        $this->kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->firstOrFail();
        $this->kasiPemerintahan = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->firstOrFail();

        Storage::fake('public');
    }

    /**
     * Helper membuat laporan lengkap untuk sebuah unit.
     */
    protected function createLaporanUnit(UnitOrganisasi $unit, User $user, string $status = 'diajukan', ?Laporan $laporan = null): LaporanDetail
    {
        $bulan = Carbon::create(2026, 8, 1);

        $laporan = $laporan ?? Laporan::firstOrCreate(
            ['bulan_pelaporan' => $bulan->toDateString()],
            ['tahun' => 2026, 'status' => 'draft']
        );

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $unit->id,
            'user_id' => $user->id,
            'status' => $status,
            'latar_belakang' => LaporanDetail::defaultLatarBelakang(),
            'submitted_at' => now(),
        ]);

        $rencanaAksi = RencanaAksi::where('unit_organisasi_id', $unit->id)->first();
        if (! $rencanaAksi) {
            $sasaran = SasaranStrategis::first();
            $rencanaAksi = RencanaAksi::create([
                'unit_organisasi_id' => $unit->id,
                'sasaran_strategis_id' => $sasaran->id,
                'uraian_rencana_aksi' => 'Aksi '.$unit->nama_unit,
                'indikator_kinerja' => 'Output '.$unit->nama_unit,
                'target_default' => 10,
                'satuan_target' => 'Laporan',
            ]);
        }

        LaporanDetailIndikator::create([
            'laporan_detail_id' => $detail->id,
            'rencana_aksi_id' => $rencanaAksi->id,
            'target_kinerja' => 10,
            'realisasi_kinerja' => 10,
            'persentase_kinerja' => 100,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 5000000,
            'realisasi_anggaran' => 4000000,
            'persentase_anggaran' => 80,
            'predikat_efisiensi' => 'efisien',
        ]);

        LaporanDetailLayanan::create([
            'laporan_detail_id' => $detail->id,
            'nama_layanan' => 'Layanan Rutin '.$unit->nama_unit,
            'jumlah' => 25,
            'satuan' => 'Berkas',
        ]);

        return $detail;
    }

    public function test_sekmat_can_approve_unit_report(): void
    {
        $unit = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detail = $this->createLaporanUnit($unit, $this->kasiPelayanan, 'diajukan');

        $service = app(LaporanApprovalService::class);
        $updatedDetail = $service->setujuiLaporanDetail($detail, $this->sekmat);

        $this->assertEquals('disetujui', $updatedDetail->status);
        $this->assertEquals($this->sekmat->id, $updatedDetail->verified_by);
        $this->assertNotNull($updatedDetail->verified_at);

        // Verifikasi Activity Log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'verifikasi_unit',
            'subject_id' => $detail->id,
            'causer_id' => $this->sekmat->id,
        ]);

        // Verifikasi Notifikasi Database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->kasiPelayanan->id,
        ]);
    }

    public function test_sekmat_can_reject_unit_report_with_notes(): void
    {
        $unit = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detail = $this->createLaporanUnit($unit, $this->kasiPelayanan, 'diajukan');

        $service = app(LaporanApprovalService::class);
        $catatan = 'Mohon perbaiki rincian anggaran belanja pada indikator kedua.';
        $updatedDetail = $service->kembalikanLaporanDetail($detail, $this->sekmat, $catatan);

        $this->assertEquals('ditolak', $updatedDetail->status);
        $this->assertEquals($catatan, $updatedDetail->catatan_verifikasi_sekmat);
        $this->assertEquals($this->sekmat->id, $updatedDetail->verified_by);

        // Verifikasi Activity Log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'verifikasi_unit',
            'subject_id' => $detail->id,
        ]);

        // Uji penolakan tanpa catatan harus gagal
        $this->expectException(InvalidArgumentException::class);
        $service->kembalikanLaporanDetail($detail, $this->sekmat, '   ');
    }

    public function test_sekmat_can_grant_dispensasi(): void
    {
        $unit = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detail = $this->createLaporanUnit($unit, $this->kasiPelayanan, 'draft');

        $service = app(LaporanApprovalService::class);
        $sampai = now()->addDays(2);
        $alasan = 'Gangguan jaringan listrik dan transisi pegawai.';

        $updatedDetail = $service->bukaDispensasi($detail, $this->sekmat, $sampai, $alasan);

        $this->assertTrue($updatedDetail->is_dispensasi);
        $this->assertEquals($alasan, $updatedDetail->alasan_dispensasi);
        $this->assertNotNull($updatedDetail->dispensasi_sampai);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'dispensasi_cutoff',
            'subject_id' => $detail->id,
            'causer_id' => $this->sekmat->id,
        ]);
    }

    public function test_ajukan_ke_camat_requires_all_7_mandatory_units_approved(): void
    {
        $mandatoryUnits = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();
        $this->assertCount(7, $mandatoryUnits);

        $service = app(LaporanApprovalService::class);

        // Buat laporan header
        $laporan = Laporan::firstOrCreate(
            ['bulan_pelaporan' => '2026-08-01'],
            ['tahun' => 2026, 'status' => 'draft']
        );

        // Baru 6 unit yang disetujui
        foreach ($mandatoryUnits->take(6) as $unit) {
            $this->createLaporanUnit($unit, $this->kasiPelayanan, 'disetujui', $laporan);
        }

        $this->assertFalse($laporan->fresh()->isAllMandatoryUnitsApproved());

        // Mencoba mengajukan harus melempar exception
        try {
            $service->ajukanKeCamat($laporan, $this->sekmat);
            $this->fail('Pengajuan seharusnya ditolak jika belum 7 unit disetujui.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('7 unit organisasi operasional wajib berstatus "disetujui"', $e->getMessage());
        }

        // Setujui unit ke-7
        $unit7 = $mandatoryUnits->last();
        $this->createLaporanUnit($unit7, $this->kasiPelayanan, 'disetujui', $laporan);

        $this->assertTrue($laporan->fresh()->isAllMandatoryUnitsApproved());

        // Pengajuan sukses
        $updatedLaporan = $service->ajukanKeCamat($laporan->fresh(), $this->sekmat);
        $this->assertEquals('diajukan_ke_camat', $updatedLaporan->status);
        $this->assertEquals($this->sekmat->id, $updatedLaporan->diajukan_oleh);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'pengajuan_kecamatan',
            'subject_id' => $laporan->id,
        ]);

        // Verifikasi notifikasi ke Camat
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->camat->id,
        ]);
    }

    public function test_camat_can_approve_and_generate_final_pdf(): void
    {
        $mandatoryUnits = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();

        $laporan = Laporan::firstOrCreate(
            ['bulan_pelaporan' => '2026-08-01'],
            ['tahun' => 2026, 'status' => 'diajukan_ke_camat', 'diajukan_oleh' => $this->sekmat->id]
        );
        $laporan->update(['status' => 'diajukan_ke_camat', 'diajukan_oleh' => $this->sekmat->id]);

        foreach ($mandatoryUnits as $unit) {
            $this->createLaporanUnit($unit, $this->kasiPelayanan, 'disetujui', $laporan);
        }

        $service = app(LaporanApprovalService::class);
        $approvedLaporan = $service->sahkanLaporan($laporan->fresh(), $this->camat);

        $this->assertEquals('disetujui', $approvedLaporan->status);
        $this->assertEquals($this->camat->id, $approvedLaporan->disetujui_oleh);
        $this->assertNotNull($approvedLaporan->disetujui_pada);
        $this->assertNotNull($approvedLaporan->dokumen_rekap_pdf_path);

        // Verifikasi PDF tersimpan di disk public
        Storage::disk('public')->assertExists($approvedLaporan->dokumen_rekap_pdf_path);

        // Verifikasi Activity Log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'pengesahan_camat',
            'subject_id' => $laporan->id,
            'causer_id' => $this->camat->id,
        ]);
    }

    public function test_camat_can_reject_report_back_to_sekmat(): void
    {
        $laporan = Laporan::firstOrCreate(
            ['bulan_pelaporan' => '2026-08-01'],
            ['tahun' => 2026, 'status' => 'diajukan_ke_camat', 'diajukan_oleh' => $this->sekmat->id]
        );
        $laporan->update(['status' => 'diajukan_ke_camat', 'diajukan_oleh' => $this->sekmat->id]);

        $service = app(LaporanApprovalService::class);
        $catatan = 'Mohon koordinasikan kembali evaluasi serapan anggaran triwulan III.';
        $rejectedLaporan = $service->kembalikanKeSekmat($laporan, $this->camat, $catatan);

        $this->assertEquals('ditolak', $rejectedLaporan->status);
        $this->assertEquals($catatan, $rejectedLaporan->catatan_camat);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'pengesahan_camat',
            'subject_id' => $laporan->id,
        ]);

        // Verifikasi Notifikasi ke Sekmat
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->sekmat->id,
        ]);
    }

    public function test_pdf_generation_for_single_unit(): void
    {
        $unit = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detail = $this->createLaporanUnit($unit, $this->kasiPelayanan, 'disetujui');

        $pdfService = app(LaporanPdfService::class);
        $filePath = $pdfService->generateLaporanUnitPdf($detail);

        Storage::disk('public')->assertExists($filePath);
        $this->assertStringEndsWith('.pdf', $filePath);
    }

    public function test_pdf_controller_routes_authorization(): void
    {
        $unitPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detailPelayanan = $this->createLaporanUnit($unitPelayanan, $this->kasiPelayanan, 'disetujui');

        // Kasi Pelayanan dapat unduh PDF unitnya
        $response = $this->actingAs($this->kasiPelayanan)
            ->get(route('spko.laporan-detail.pdf', ['detail' => $detailPelayanan->id]));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        // Kasi Pemerintahan dilarang (403) mengunduh laporan unit Seksi Pelayanan
        $forbiddenResponse = $this->actingAs($this->kasiPemerintahan)
            ->get(route('spko.laporan-detail.pdf', ['detail' => $detailPelayanan->id]));
        $forbiddenResponse->assertStatus(403);

        // Sekmat & Camat berhak mengunduh seluruh laporan
        $sekmatResponse = $this->actingAs($this->sekmat)
            ->get(route('spko.laporan-detail.pdf', ['detail' => $detailPelayanan->id]));
        $sekmatResponse->assertStatus(200);

        $camatResponse = $this->actingAs($this->camat)
            ->get(route('spko.laporan-detail.pdf', ['detail' => $detailPelayanan->id]));
        $camatResponse->assertStatus(200);
    }

    public function test_verifikasi_and_laporan_kecamatan_resource_rbac(): void
    {
        // Sekmat can access Verifikasi and LaporanKecamatan
        $this->actingAs($this->sekmat)
            ->get(VerifikasiLaporanUnitResource::getUrl('index'))
            ->assertStatus(200);

        $this->actingAs($this->sekmat)
            ->get(LaporanKecamatanResource::getUrl('index'))
            ->assertStatus(200);

        // Camat can access LaporanKecamatan but NOT VerifikasiLaporanUnit
        $this->actingAs($this->camat)
            ->get(LaporanKecamatanResource::getUrl('index'))
            ->assertStatus(200);

        $this->actingAs($this->camat)
            ->get(VerifikasiLaporanUnitResource::getUrl('index'))
            ->assertStatus(403);

        // Kasi cannot access either Verifikasi or LaporanKecamatan
        $this->actingAs($this->kasiPelayanan)
            ->get(VerifikasiLaporanUnitResource::getUrl('index'))
            ->assertStatus(403);

        $this->actingAs($this->kasiPelayanan)
            ->get(LaporanKecamatanResource::getUrl('index'))
            ->assertStatus(403);
    }

    public function test_livewire_sekmat_verifikasi_table_actions(): void
    {
        $unit = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
        $detail = $this->createLaporanUnit($unit, $this->kasiPelayanan, 'diajukan');

        $this->actingAs($this->sekmat);

        // Uji action setujui dari tabel verifikasi
        Livewire::test(VerifikasiLaporanUnitResource\Pages\ListVerifikasiLaporanUnits::class)
            ->callTableAction('setujui', $detail);

        $this->assertEquals('disetujui', $detail->fresh()->status);
        $this->assertEquals($this->sekmat->id, $detail->fresh()->verified_by);

        // Uji action kembalikan dari tabel verifikasi
        Livewire::test(VerifikasiLaporanUnitResource\Pages\ListVerifikasiLaporanUnits::class)
            ->callTableAction('kembalikan', $detail, data: [
                'catatan_verifikasi_sekmat' => 'Format salah, mohon sesuaikan.',
            ]);

        $this->assertEquals('ditolak', $detail->fresh()->status);
        $this->assertEquals('Format salah, mohon sesuaikan.', $detail->fresh()->catatan_verifikasi_sekmat);
    }
}
