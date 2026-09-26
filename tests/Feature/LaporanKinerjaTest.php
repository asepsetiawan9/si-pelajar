<?php

namespace Tests\Feature;

use App\Filament\Resources\LaporanDetailResource;
use App\Filament\Resources\LaporanDetailResource\Pages\CreateLaporanDetail;
use App\Filament\Resources\LaporanDetailResource\Pages\ListLaporanDetails;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\LaporanDetailLayanan;
use App\Models\RencanaAksi;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanKinerjaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_concurrency_safe_header_auto_creation_and_detail_creation(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $unit = $kasi->unitOrganisasi;

        $targetBulan = Carbon::create(2026, 8, 1);

        $laporan = Laporan::firstOrCreate(
            ['bulan_pelaporan' => $targetBulan->toDateString()],
            ['tahun' => 2026, 'status' => 'draft']
        );

        $this->assertEquals('2026-08-01', $laporan->bulan_pelaporan->format('Y-m-d'));
        $this->assertDatabaseHas('laporans', [
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $unit->id,
            'user_id' => $kasi->id,
            'status' => 'draft',
            'latar_belakang' => LaporanDetail::defaultLatarBelakang(),
            'keterangan_keterkaitan' => 'Keterkaitan dengan Sasaran Camat Malangbong',
        ]);

        $this->assertDatabaseHas('laporan_details', [
            'id' => $detail->id,
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $unit->id,
            'status' => 'draft',
        ]);

        $this->assertEquals($laporan->id, $detail->laporan->id);
        $this->assertEquals($unit->id, $detail->unitOrganisasi->id);
    }

    public function test_automatic_efektivitas_and_efisiensi_calculations(): void
    {
        // 1. Sangat Efektif (> 100%)
        $p1 = LaporanDetailIndikator::calculatePersentaseKinerja(10, 12);
        $this->assertEquals(120.0, $p1);
        $this->assertEquals('sangat_efektif', LaporanDetailIndikator::calculatePredikatEfektivitas($p1));

        // 2. Efektif (90% - 100%)
        $p2 = LaporanDetailIndikator::calculatePersentaseKinerja(100, 95);
        $this->assertEquals(95.0, $p2);
        $this->assertEquals('efektif', LaporanDetailIndikator::calculatePredikatEfektivitas($p2));

        // 3. Cukup Efektif (60% - 89%)
        $p3 = LaporanDetailIndikator::calculatePersentaseKinerja(100, 75);
        $this->assertEquals(75.0, $p3);
        $this->assertEquals('cukup_efektif', LaporanDetailIndikator::calculatePredikatEfektivitas($p3));

        // 4. Tidak Efektif (< 60%)
        $p4 = LaporanDetailIndikator::calculatePersentaseKinerja(100, 45);
        $this->assertEquals(45.0, $p4);
        $this->assertEquals('tidak_efektif', LaporanDetailIndikator::calculatePredikatEfektivitas($p4));

        // 5. Sangat Efisien (< 60%)
        $a1 = LaporanDetailIndikator::calculatePersentaseAnggaran(10000000, 5000000);
        $this->assertEquals(50.0, $a1);
        $this->assertEquals('sangat_efisien', LaporanDetailIndikator::calculatePredikatEfisiensi($a1));

        // 6. Efisien (60% - 90%)
        $a2 = LaporanDetailIndikator::calculatePersentaseAnggaran(10000000, 8000000);
        $this->assertEquals(80.0, $a2);
        $this->assertEquals('efisien', LaporanDetailIndikator::calculatePredikatEfisiensi($a2));

        // 7. Cukup Efisien (91% - 100%)
        $a3 = LaporanDetailIndikator::calculatePersentaseAnggaran(10000000, 9500000);
        $this->assertEquals(95.0, $a3);
        $this->assertEquals('cukup_efisien', LaporanDetailIndikator::calculatePredikatEfisiensi($a3));

        // 8. Tidak Efisien (> 100%)
        $a4 = LaporanDetailIndikator::calculatePersentaseAnggaran(10000000, 11000000);
        $this->assertEquals(110.0, $a4);
        $this->assertEquals('tidak_efisien', LaporanDetailIndikator::calculatePredikatEfisiensi($a4));
    }

    public function test_paten_preset_services_and_dynamic_layanan_creation(): void
    {
        $presets = LaporanDetailLayanan::defaultPresetsPaten();
        $this->assertCount(10, $presets);
        $this->assertEquals('Surat Keterangan Tidak Mampu (SKTM)', $presets[0]['nama_layanan']);

        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $laporan = Laporan::create(['bulan_pelaporan' => '2026-08-01', 'tahun' => 2026]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasi->unit_organisasi_id,
            'user_id' => $kasi->id,
            'status' => 'draft',
        ]);

        foreach ($presets as $item) {
            $detail->layanans()->create($item);
        }

        $this->assertEquals(10, $detail->layanans()->count());
    }

    public function test_cutoff_calculation_and_late_status_logic(): void
    {
        // Bulan pelaporan: Agustus 2026 (2026-08-01)
        // Cut-off: 10 September 2026 pukul 23:59:59
        $bulanPelaporan = Carbon::create(2026, 8, 1);
        $cutoffDate = LaporanDetail::calculateCutoffDate($bulanPelaporan);

        $this->assertEquals(9, $cutoffDate->month);
        $this->assertEquals(10, $cutoffDate->day);
        $this->assertEquals(23, $cutoffDate->hour);
        $this->assertEquals(59, $cutoffDate->minute);
        $this->assertEquals(59, $cutoffDate->second);

        // Sebelum cut-off: 5 September 2026
        Carbon::setTestNow(Carbon::create(2026, 9, 5, 10, 0, 0));
        $this->assertFalse(LaporanDetail::isPastCutoff($bulanPelaporan));

        // Setelah cut-off: 11 September 2026
        Carbon::setTestNow(Carbon::create(2026, 9, 11, 8, 0, 0));
        $this->assertTrue(LaporanDetail::isPastCutoff($bulanPelaporan));

        Carbon::setTestNow(); // Reset time mock
    }

    public function test_dispensasi_mechanism_allows_editing_past_cutoff(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $laporan = Laporan::create(['bulan_pelaporan' => '2026-08-01', 'tahun' => 2026]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasi->unit_organisasi_id,
            'user_id' => $kasi->id,
            'status' => 'draft',
            'is_dispensasi' => false,
        ]);

        // Mock waktu setelah cut-off
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0));

        // Tanpa dispensasi: isLocked = true
        $this->assertTrue($detail->isLocked());

        // Berikan dispensasi oleh Sekmat
        $detail->update([
            'is_dispensasi' => true,
            'dispensasi_sampai' => Carbon::create(2026, 9, 20, 23, 59, 59),
            'alasan_dispensasi' => 'Gangguan jaringan internet di kantor kecamatan',
        ]);

        $this->assertTrue($detail->hasActiveDispensasi());
        $this->assertFalse($detail->isLocked());

        Carbon::setTestNow();
    }

    public function test_submit_report_locks_form_and_dispatches_database_notification(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $laporan = Laporan::create(['bulan_pelaporan' => '2026-08-01', 'tahun' => 2026]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasi->unit_organisasi_id,
            'user_id' => $kasi->id,
            'status' => 'draft',
        ]);

        $rencanaAksi = RencanaAksi::where('unit_organisasi_id', $kasi->unit_organisasi_id)->first();

        // Tambah indikator
        $detail->indikators()->create([
            'rencana_aksi_id' => $rencanaAksi->id,
            'target_kinerja' => 1,
            'realisasi_kinerja' => 1,
            'persentase_kinerja' => 100,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 5000000,
            'realisasi_anggaran' => 4500000,
            'persentase_anggaran' => 90,
            'predikat_efisiensi' => 'efisien',
        ]);

        $this->actingAs($kasi);

        // Simulasi submit report
        $detail->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        // Form sekarang harus terkunci
        $this->assertTrue($detail->isLocked());

        // Kirim notifikasi database ke Sekmat
        Notification::make()
            ->title('Laporan Kinerja Unit Diajukan')
            ->body("Unit {$detail->unitOrganisasi->nama_unit} telah mengajukan laporan.")
            ->sendToDatabase($sekmat);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $sekmat->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_row_level_security_kasi_only_sees_own_unit_reports(): void
    {
        $kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $kasiPemerintahan = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();

        $laporan = Laporan::create(['bulan_pelaporan' => '2026-08-01', 'tahun' => 2026]);

        $detailPelayanan = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasiPelayanan->unit_organisasi_id,
            'user_id' => $kasiPelayanan->id,
            'status' => 'draft',
        ]);

        $detailPemerintahan = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasiPemerintahan->unit_organisasi_id,
            'user_id' => $kasiPemerintahan->id,
            'status' => 'draft',
        ]);

        // Kasi Pelayanan query
        $this->actingAs($kasiPelayanan);
        $pelayananQuery = LaporanDetailResource::getEloquentQuery()->pluck('id');
        $this->assertContains($detailPelayanan->id, $pelayananQuery);
        $this->assertNotContains($detailPemerintahan->id, $pelayananQuery);

        // Sekmat query sees all
        $this->actingAs($sekmat);
        $sekmatQuery = LaporanDetailResource::getEloquentQuery()->pluck('id');
        $this->assertContains($detailPelayanan->id, $sekmatQuery);
        $this->assertContains($detailPemerintahan->id, $sekmatQuery);
    }

    public function test_filament_laporan_detail_pages_render_successfully(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->actingAs($kasi);

        Livewire::test(ListLaporanDetails::class)
            ->assertSuccessful();

        Livewire::test(CreateLaporanDetail::class)
            ->assertSuccessful();
    }

    public function test_kasi_can_create_laporan_detail_via_filament_form(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->actingAs($kasi);

        $rencanaAksi = RencanaAksi::where('unit_organisasi_id', $kasi->unit_organisasi_id)->first();

        // Set time sebelum cutoff: 5 September 2026 untuk laporan Agustus 2026
        Carbon::setTestNow(Carbon::create(2026, 9, 5, 10, 0, 0));

        Livewire::test(CreateLaporanDetail::class)
            ->fillForm([
                'bulan_pelaporan' => '2026-08-01',
                'unit_organisasi_id' => $kasi->unit_organisasi_id,
                'latar_belakang' => LaporanDetail::defaultLatarBelakang(),
                'keterangan_keterkaitan' => 'Program pelayanan publik terpadu kecamatan',
                'indikators' => [
                    [
                        'rencana_aksi_id' => $rencanaAksi->id,
                        'target_kinerja' => 1,
                        'realisasi_kinerja' => 1,
                        'persentase_kinerja' => 100,
                        'predikat_efektivitas' => 'efektif',
                        'anggaran_pagu' => 1000000,
                        'realisasi_anggaran' => 800000,
                        'persentase_anggaran' => 80,
                        'predikat_efisiensi' => 'efisien',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('laporan_details', [
            'unit_organisasi_id' => $kasi->unit_organisasi_id,
            'user_id' => $kasi->id,
            'status' => 'draft',
            'is_late' => false,
        ]);

        $detail = LaporanDetail::where('unit_organisasi_id', $kasi->unit_organisasi_id)->first();
        $this->assertEquals(1, $detail->indikators()->count());
        $this->assertEquals(100.0, (float) $detail->indikators->first()->persentase_kinerja);

        Carbon::setTestNow();
    }

    public function test_kasi_can_submit_laporan_via_table_action(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->actingAs($kasi);

        $laporan = Laporan::create(['bulan_pelaporan' => '2026-08-01', 'tahun' => 2026]);
        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasi->unit_organisasi_id,
            'user_id' => $kasi->id,
            'status' => 'draft',
        ]);

        $rencanaAksi = RencanaAksi::where('unit_organisasi_id', $kasi->unit_organisasi_id)->first();
        $detail->indikators()->create([
            'rencana_aksi_id' => $rencanaAksi->id,
            'target_kinerja' => 1,
            'realisasi_kinerja' => 1,
            'persentase_kinerja' => 100,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 1000000,
            'realisasi_anggaran' => 800000,
            'persentase_anggaran' => 80,
            'predikat_efisiensi' => 'efisien',
        ]);

        Livewire::test(ListLaporanDetails::class)
            ->callTableAction('ajukan', $detail)
            ->assertHasNoTableActionErrors();

        $detail->refresh();
        $this->assertEquals('diajukan', $detail->status);
        $this->assertNotNull($detail->submitted_at);
    }
}
