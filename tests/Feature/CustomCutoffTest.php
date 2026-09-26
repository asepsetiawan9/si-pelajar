<?php

namespace Tests\Feature;

use App\Filament\Resources\JadwalCutoffResource;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\LaporanApprovalService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomCutoffTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $sekmat;

    protected User $kasiPelayanan;

    protected User $camat;

    protected UnitOrganisasi $unitPelayanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superadmin = User::where('role', 'superadmin')->firstOrFail();
        $this->sekmat = User::where('role', 'admin_kecamatan')->firstOrFail();
        $this->kasiPelayanan = User::where('role', 'kasi')->where('email', 'kasi.pelayanan@malangbong.go.id')->firstOrFail();
        $this->camat = User::where('role', 'camat')->firstOrFail();
        $this->unitPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->firstOrFail();
    }

    public function test_custom_cutoff_date_overrides_default_day_10(): void
    {
        $bulanPelaporan = '2026-08-01';

        // Buat header laporan dengan custom cut-off: 20 September 2026 pukul 20:00 WIB
        $customCutoff = Carbon::create(2026, 9, 20, 20, 0, 0);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanPelaporan,
            'tahun' => 2026,
            'status' => 'draft',
            'cutoff_status' => 'otomatis',
            'custom_cutoff_at' => $customCutoff,
        ]);

        $calculated = LaporanDetail::calculateCutoffDate($bulanPelaporan);
        $this->assertEquals('2026-09-20 20:00:00', $calculated->toDateTimeString());

        // Uji pada tanggal 15 September (lewat tanggal 10 default, tapi sebelum custom cut-off tgl 20)
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0));
        $this->assertFalse(LaporanDetail::isPastCutoff($bulanPelaporan));
        $this->assertTrue($laporan->isCutoffOpen());
        $this->assertFalse($laporan->isCutoffClosed());

        // Uji pada tanggal 21 September (lewat cut-off custom)
        Carbon::setTestNow(Carbon::create(2026, 9, 21, 10, 0, 0));
        $this->assertTrue(LaporanDetail::isPastCutoff($bulanPelaporan));
        $this->assertFalse($laporan->isCutoffOpen());
        $this->assertTrue($laporan->isCutoffClosed());

        Carbon::setTestNow();
    }

    public function test_sekmat_can_force_open_cutoff_anytime(): void
    {
        $bulanPelaporan = '2026-08-01';
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanPelaporan,
            'tahun' => 2026,
            'status' => 'draft',
            'cutoff_status' => 'terbuka',
            'catatan_cutoff' => 'Dibuka khusus oleh Sekmat untuk koordinasi lintas seksi.',
        ]);

        // Detail laporan
        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $this->unitPelayanan->id,
            'user_id' => $this->kasiPelayanan->id,
            'status' => 'draft',
        ]);

        // Set waktu jauh lewat cut-off (Oktober 2026)
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 10, 0, 0));

        // Karena status 'terbuka', pengisian TIDAK dianggap lewat cut-off dan formulir TIDAK terkunci
        $this->assertFalse(LaporanDetail::isPastCutoff($bulanPelaporan));
        $this->assertFalse($detail->isLocked());
        $this->assertTrue($laporan->isCutoffOpen());

        Carbon::setTestNow();
    }

    public function test_sekmat_can_force_close_cutoff_anytime(): void
    {
        $bulanPelaporan = '2026-08-01';
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanPelaporan,
            'tahun' => 2026,
            'status' => 'draft',
            'cutoff_status' => 'tertutup',
            'catatan_cutoff' => 'Pengisian ditutup sementara untuk konsolidasi data.',
        ]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $this->unitPelayanan->id,
            'user_id' => $this->kasiPelayanan->id,
            'status' => 'draft',
        ]);

        // Set waktu masih awal bulan (misal 5 September - sebelum tgl 10)
        Carbon::setTestNow(Carbon::create(2026, 9, 5, 10, 0, 0));

        // Karena status 'tertutup', pengisian langsung dianggap terkunci
        $this->assertTrue(LaporanDetail::isPastCutoff($bulanPelaporan));
        $this->assertTrue($detail->isLocked());
        $this->assertTrue($laporan->isCutoffClosed());

        Carbon::setTestNow();
    }

    public function test_laporan_approval_service_manages_cutoff_actions_and_logs(): void
    {
        $service = app(LaporanApprovalService::class);
        $bulanPelaporan = '2026-08-01';
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanPelaporan,
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        // 1. Buka Akses Pengisian
        $service->bukaAksesPengisian($laporan, 'Buka pengisian unit pelayanan.', $this->sekmat, true);
        $laporan->refresh();
        $this->assertEquals('terbuka', $laporan->cutoff_status);
        $this->assertEquals($this->sekmat->id, $laporan->cutoff_updated_by);
        $this->assertNotNull($laporan->cutoff_updated_at);

        // Notifikasi harus terkirim ke Kasi
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->kasiPelayanan->id,
        ]);

        // 2. Kunci Akses Pengisian
        $service->tutupAksesPengisian($laporan, 'Kunci pengisian.', $this->sekmat, true);
        $laporan->refresh();
        $this->assertEquals('tertutup', $laporan->cutoff_status);

        // 3. Set Custom Cutoff Date
        $customDate = Carbon::create(2026, 9, 25, 23, 59, 0);
        $service->aturCutoffPeriode(
            laporan: $laporan,
            status: 'otomatis',
            customCutoffAt: $customDate,
            catatan: 'Diperpanjang sampai tgl 25.',
            actor: $this->sekmat,
            notify: true
        );
        $laporan->refresh();
        $this->assertEquals('otomatis', $laporan->cutoff_status);
        $this->assertEquals($customDate->toDateTimeString(), $laporan->custom_cutoff_at->toDateTimeString());

        // 4. Reset ke Otomatis
        $service->resetCutoffOtomatis($laporan, $this->sekmat);
        $laporan->refresh();
        $this->assertEquals('otomatis', $laporan->cutoff_status);
        $this->assertNull($laporan->custom_cutoff_at);

        // Verifikasi Spatie Activity Log
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'cutoff_management',
            'causer_id' => $this->sekmat->id,
        ]);
    }

    public function test_jadwal_cutoff_resource_rbac_and_rendering(): void
    {
        // 1. Superadmin & Sekmat can view
        $this->actingAs($this->sekmat);
        $this->assertTrue(JadwalCutoffResource::canViewAny());

        $this->actingAs($this->superadmin);
        $this->assertTrue(JadwalCutoffResource::canViewAny());

        // 2. Kasi & Camat cannot view
        $this->actingAs($this->kasiPelayanan);
        $this->assertFalse(JadwalCutoffResource::canViewAny());

        $this->actingAs($this->camat);
        $this->assertFalse(JadwalCutoffResource::canViewAny());

        // 3. Render List Jadwal Cutoff Page
        $this->actingAs($this->sekmat);
        Livewire::test(JadwalCutoffResource\Pages\ListJadwalCutoffs::class)
            ->assertSuccessful();
    }
}
