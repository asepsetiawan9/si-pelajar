<?php

namespace Tests\Feature;

use App\Exports\LaporanKinerjaExport;
use App\Exports\Sheets\IndikatorKinerjaSheet;
use App\Exports\Sheets\RekapitulasiUnitSheet;
use App\Exports\Sheets\RincianLayananSheet;
use App\Filament\Widgets\CamatSummaryWidget;
use App\Filament\Widgets\DashboardHeroWidget;
use App\Filament\Widgets\KasiRecentReportsWidget;
use App\Filament\Widgets\KasiStatusWidget;
use App\Filament\Widgets\KasiSubmissionTrackerWidget;
use App\Filament\Widgets\KecamatanPerformanceChartWidget;
use App\Filament\Widgets\KecamatanStatusDonutChartWidget;
use App\Filament\Widgets\SekmatProgressWidget;
use App\Filament\Widgets\SekmatUnitStatusTableWidget;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\LaporanDetailLayanan;
use App\Models\LaporanDokumen;
use App\Models\RencanaAksi;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\LaporanApprovalService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DashboardAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_renders_and_kasi_sees_kasi_status_widget(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->actingAs($kasi);

        $response = $this->get('/admin');
        $response->assertSuccessful();

        // Kasi can view KasiStatusWidget
        Livewire::test(KasiStatusWidget::class)
            ->assertSuccessful();

        // Kasi cannot view Sekmat-only widgets
        $this->assertFalse(SekmatProgressWidget::canView());
        $this->assertFalse(SekmatUnitStatusTableWidget::canView());
        $this->assertFalse(CamatSummaryWidget::canView());
        $this->assertFalse(KecamatanPerformanceChartWidget::canView());
    }

    public function test_sekmat_and_camat_dashboard_widgets_visibility(): void
    {
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $this->actingAs($sekmat);

        $response = $this->get('/admin');
        $response->assertSuccessful();

        $this->assertTrue(SekmatProgressWidget::canView());
        $this->assertTrue(SekmatUnitStatusTableWidget::canView());
        $this->assertTrue(KecamatanPerformanceChartWidget::canView());
        $this->assertFalse(KasiStatusWidget::canView());
        $this->assertFalse(CamatSummaryWidget::canView());

        Livewire::test(SekmatProgressWidget::class)->assertSuccessful();
        Livewire::test(SekmatUnitStatusTableWidget::class)->assertSuccessful();
        Livewire::test(KecamatanPerformanceChartWidget::class)->assertSuccessful();

        // Switch to Camat
        $camat = User::where('email', 'camat@malangbong.go.id')->first();
        $this->actingAs($camat);

        $this->assertTrue(CamatSummaryWidget::canView());
        $this->assertTrue(KecamatanPerformanceChartWidget::canView());
        $this->assertFalse(KasiStatusWidget::canView());
        $this->assertFalse(SekmatProgressWidget::canView());

        Livewire::test(CamatSummaryWidget::class)->assertSuccessful();
    }

    public function test_dashboard_hero_widget_renders_for_all_roles(): void
    {
        $roles = [
            'superadmin@malangbong.go.id',
            'sekmat@malangbong.go.id',
            'camat@malangbong.go.id',
            'kasi.pelayanan@malangbong.go.id',
        ];

        foreach ($roles as $email) {
            $user = User::where('email', $email)->first();
            $this->actingAs($user);

            Livewire::test(DashboardHeroWidget::class)
                ->assertSuccessful()
                ->assertSee($user->name);
        }
    }

    public function test_kasi_recent_reports_widget_renders_for_kasi(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->actingAs($kasi);
        $this->assertTrue(KasiRecentReportsWidget::canView());

        Livewire::test(KasiRecentReportsWidget::class)
            ->assertSuccessful();

        // Sekmat cannot view KasiRecentReportsWidget
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $this->actingAs($sekmat);
        $this->assertFalse(KasiRecentReportsWidget::canView());
    }

    public function test_new_modern_widgets_render_successfully_with_rbac(): void
    {
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $camat = User::where('email', 'camat@malangbong.go.id')->first();
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();

        // 1. Sekmat: Can view Donut, cannot view KasiSubmissionTracker
        $this->actingAs($sekmat);
        $this->assertTrue(KecamatanStatusDonutChartWidget::canView());
        $this->assertFalse(KasiSubmissionTrackerWidget::canView());

        Livewire::test(KecamatanStatusDonutChartWidget::class)->assertSuccessful();

        // 2. Camat: Can view Donut
        $this->actingAs($camat);
        $this->assertTrue(KecamatanStatusDonutChartWidget::canView());
        $this->assertFalse(KasiSubmissionTrackerWidget::canView());

        Livewire::test(KecamatanStatusDonutChartWidget::class)->assertSuccessful();

        // 3. Kasi: Can view KasiSubmissionTracker, cannot view Donut
        $this->actingAs($kasi);
        $this->assertTrue(KasiSubmissionTrackerWidget::canView());
        $this->assertFalse(KecamatanStatusDonutChartWidget::canView());

        Livewire::test(KasiSubmissionTrackerWidget::class)->assertSuccessful();
    }

    public function test_spko_check_deadline_command_and_notifications(): void
    {
        // Jalankan artisan command dengan --force
        $exitCode = Artisan::call('spko:check-deadline', ['--force' => true]);
        $this->assertEquals(0, $exitCode);

        // Kasi Pelayanan must have received a database notification
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $this->assertGreaterThan(0, $kasi->notifications()->count());

        // Sekmat must have received a summary notification
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $this->assertGreaterThan(0, $sekmat->notifications()->count());
    }

    public function test_excel_export_structure_and_sheets(): void
    {
        $bulanDate = Carbon::create(2026, 8, 1);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanDate->toDateString(),
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $seksiPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->first();
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $ra = RencanaAksi::where('unit_organisasi_id', $seksiPelayanan->id)->first();

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $seksiPelayanan->id,
            'user_id' => $kasi->id,
            'status' => 'disetujui',
        ]);

        LaporanDetailIndikator::create([
            'laporan_detail_id' => $detail->id,
            'rencana_aksi_id' => $ra->id,
            'target_kinerja' => 1,
            'realisasi_kinerja' => 1,
            'persentase_kinerja' => 100,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 10000000,
            'realisasi_anggaran' => 8500000,
            'persentase_anggaran' => 85,
            'predikat_efisiensi' => 'efisien',
        ]);

        LaporanDetailLayanan::create([
            'laporan_detail_id' => $detail->id,
            'nama_layanan' => 'Surat Keterangan Tidak Mampu (SKTM)',
            'jumlah' => 45,
            'satuan' => 'Pemohon',
            'keterangan' => 'Pelayanan reguler masyarakat',
        ]);

        // Test LaporanKinerjaExport structure
        $export = new LaporanKinerjaExport($laporan);
        $sheets = $export->sheets();

        $this->assertCount(3, $sheets);
        $this->assertInstanceOf(IndikatorKinerjaSheet::class, $sheets[0]);
        $this->assertInstanceOf(RincianLayananSheet::class, $sheets[1]);
        $this->assertInstanceOf(RekapitulasiUnitSheet::class, $sheets[2]);

        $indCollection = $sheets[0]->collection();
        $this->assertCount(1, $indCollection);
        $this->assertEquals('SEKSI-PELAYANAN', $seksiPelayanan->kode_unit);
        $this->assertEquals(8500000, $indCollection->first()['realisasi_anggaran']);

        $layananCollection = $sheets[1]->collection();
        $this->assertCount(1, $layananCollection);
        $this->assertEquals(45, $layananCollection->first()['jumlah']);
    }

    public function test_excel_export_controllers_authorization(): void
    {
        $bulanDate = Carbon::create(2026, 8, 1);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanDate->toDateString(),
            'tahun' => 2026,
            'status' => 'disetujui',
        ]);

        $seksiPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->first();
        $seksiPemerintahan = UnitOrganisasi::where('kode_unit', 'SEKSI-PEMERINTAHAN')->first();

        $kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $kasiPemerintahan = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $camat = User::where('email', 'camat@malangbong.go.id')->first();

        $detailPelayanan = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $seksiPelayanan->id,
            'user_id' => $kasiPelayanan->id,
            'status' => 'disetujui',
        ]);

        // Kasi Pelayanan downloading single unit Excel for own unit -> 200
        $response = $this->actingAs($kasiPelayanan)->get("/laporan-detail/{$detailPelayanan->id}/excel");
        $response->assertSuccessful();

        // Kasi Pemerintahan downloading Pelayanan Excel -> 403 Forbidden
        $response = $this->actingAs($kasiPemerintahan)->get("/laporan-detail/{$detailPelayanan->id}/excel");
        $response->assertForbidden();

        // Kasi Pelayanan downloading Kecamatan rekap Excel -> 403 Forbidden
        $response = $this->actingAs($kasiPelayanan)->get("/laporan/{$laporan->id}/excel");
        $response->assertForbidden();

        // Sekmat and Camat downloading Kecamatan rekap Excel -> 200
        $response = $this->actingAs($sekmat)->get("/laporan/{$laporan->id}/excel");
        $response->assertSuccessful();

        $response = $this->actingAs($camat)->get("/laporan/{$laporan->id}/excel");
        $response->assertSuccessful();
    }

    public function test_attachment_download_authorization_and_sanitization(): void
    {
        Storage::fake('public');

        $kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $kasiPemerintahan = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();

        $laporan = Laporan::create([
            'bulan_pelaporan' => '2026-08-01',
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasiPelayanan->unit_organisasi_id,
            'user_id' => $kasiPelayanan->id,
            'status' => 'draft',
        ]);

        $fakeFile = UploadedFile::fake()->create('bukti_paten.pdf', 100, 'application/pdf');
        $storedPath = $fakeFile->store('spko-dokumen', 'public');

        $dokumen = LaporanDokumen::create([
            'laporan_detail_id' => $detail->id,
            'nama_dokumen' => 'Bukti Fisik Pelayanan PATEN 2026',
            'file_path' => $storedPath,
            'file_type' => 'application/pdf',
            'file_size' => 102400,
        ]);

        // Kasi Pelayanan can download
        $response = $this->actingAs($kasiPelayanan)->get("/laporan-dokumen/{$dokumen->id}/download");
        $response->assertSuccessful();

        // Kasi Pemerintahan cannot download (403 Forbidden)
        $response = $this->actingAs($kasiPemerintahan)->get("/laporan-dokumen/{$dokumen->id}/download");
        $response->assertForbidden();

        // Sekmat can download
        $response = $this->actingAs($sekmat)->get("/laporan-dokumen/{$dokumen->id}/download");
        $response->assertSuccessful();
    }

    public function test_row_level_security_laporan_detail_policy(): void
    {
        $kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $kasiPemerintahan = User::where('email', 'kasi.pemerintahan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();

        $laporan = Laporan::create([
            'bulan_pelaporan' => now()->startOfMonth()->toDateString(),
            'tahun' => now()->year,
            'status' => 'draft',
        ]);

        $detailPelayanan = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $kasiPelayanan->unit_organisasi_id,
            'user_id' => $kasiPelayanan->id,
            'status' => 'draft',
        ]);

        // Policy view check
        $this->assertTrue($kasiPelayanan->can('view', $detailPelayanan));
        $this->assertFalse($kasiPemerintahan->can('view', $detailPelayanan));
        $this->assertTrue($sekmat->can('view', $detailPelayanan));

        // Policy update check
        $this->assertTrue($kasiPelayanan->can('update', $detailPelayanan));
        $this->assertFalse($kasiPemerintahan->can('update', $detailPelayanan));
        $this->assertTrue($sekmat->can('update', $detailPelayanan));

        // When submitted (locked), Kasi cannot update anymore
        $detailPelayanan->update(['status' => 'diajukan', 'submitted_at' => now()]);
        $this->assertFalse($kasiPelayanan->can('update', $detailPelayanan));
        $this->assertTrue($sekmat->can('update', $detailPelayanan)); // Sekmat still can
    }

    public function test_full_end_to_end_flow_with_88_pemohon_to_final_approval(): void
    {
        Storage::fake('public');

        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $camat = User::where('email', 'camat@malangbong.go.id')->first();

        $bulanDate = Carbon::create(2026, 8, 1);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanDate->toDateString(),
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $seksiPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->first();
        $detailPelayanan = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $seksiPelayanan->id,
            'user_id' => $kasi->id,
            'status' => 'draft',
            'latar_belakang' => 'Pelaksanaan tugas PATEN',
        ]);

        // Input 88 pemohon across standard services
        $layananData = [
            ['nama' => 'Surat Keterangan Tidak Mampu (SKTM)', 'jumlah' => 30],
            ['nama' => 'Rekomendasi Surat Ijin Keramaian', 'jumlah' => 15],
            ['nama' => 'Rekomendasi Permohonan Kredit', 'jumlah' => 12],
            ['nama' => 'Surat Keterangan Ahli Waris', 'jumlah' => 10],
            ['nama' => 'Dispensasi Nikah', 'jumlah' => 8],
            ['nama' => 'Perekaman KTP-el', 'jumlah' => 13],
        ];

        $totalPemohon = 0;
        foreach ($layananData as $item) {
            LaporanDetailLayanan::create([
                'laporan_detail_id' => $detailPelayanan->id,
                'nama_layanan' => $item['nama'],
                'jumlah' => $item['jumlah'],
                'satuan' => 'Pemohon',
            ]);
            $totalPemohon += $item['jumlah'];
        }

        $this->assertEquals(88, $totalPemohon);

        $ra = RencanaAksi::where('unit_organisasi_id', $seksiPelayanan->id)->first();
        LaporanDetailIndikator::create([
            'laporan_detail_id' => $detailPelayanan->id,
            'rencana_aksi_id' => $ra->id,
            'target_kinerja' => 1,
            'realisasi_kinerja' => 1,
            'persentase_kinerja' => 100,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 25000000,
            'realisasi_anggaran' => 24500000,
            'persentase_anggaran' => 98,
            'predikat_efisiensi' => 'cukup_efisien',
        ]);

        // 1. Submit by Kasi
        $detailPelayanan->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);
        $this->assertEquals('diajukan', $detailPelayanan->fresh()->status);

        // 2. Verify by Sekmat
        $approvalService = app(LaporanApprovalService::class);
        $approvalService->setujuiLaporanDetail($detailPelayanan, $sekmat);
        $this->assertEquals('disetujui', $detailPelayanan->fresh()->status);

        // Populate other 6 units to simulate complete 7 units ready for Camat
        $otherUnits = UnitOrganisasi::where('id', '!=', $seksiPelayanan->id)->get();
        foreach ($otherUnits as $u) {
            $det = LaporanDetail::create([
                'laporan_id' => $laporan->id,
                'unit_organisasi_id' => $u->id,
                'user_id' => $sekmat->id,
                'status' => 'disetujui',
                'verified_at' => now(),
                'verified_by' => $sekmat->id,
            ]);
            $r = RencanaAksi::where('unit_organisasi_id', $u->id)->first();
            if ($r) {
                LaporanDetailIndikator::create([
                    'laporan_detail_id' => $det->id,
                    'rencana_aksi_id' => $r->id,
                    'target_kinerja' => 1,
                    'realisasi_kinerja' => 1,
                    'persentase_kinerja' => 100,
                    'predikat_efektivitas' => 'efektif',
                    'anggaran_pagu' => 10000000,
                    'realisasi_anggaran' => 9000000,
                    'persentase_anggaran' => 90,
                    'predikat_efisiensi' => 'efisien',
                ]);
            }
        }

        $this->assertTrue($laporan->fresh()->isAllMandatoryUnitsApproved());

        // 3. Sekmat submits to Camat
        $approvalService->ajukanKeCamat($laporan, $sekmat);
        $this->assertEquals('diajukan_ke_camat', $laporan->fresh()->status);

        // 4. Camat endorses report (Sahkan Laporan)
        $approvalService->sahkanLaporan($laporan, $camat);
        $this->assertEquals('disetujui', $laporan->fresh()->status);
        $this->assertNotNull($laporan->fresh()->dokumen_rekap_pdf_path);
        Storage::disk('public')->assertExists($laporan->fresh()->dokumen_rekap_pdf_path);

        // 5. Excel export can be successfully downloaded
        $excelResponse = $this->actingAs($camat)->get("/laporan/{$laporan->id}/excel");
        $excelResponse->assertSuccessful();
    }

    public function test_widgets_render_successfully_with_active_report_data(): void
    {
        $bulanDate = Carbon::create(2026, 8, 1);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanDate->toDateString(),
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $seksiPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->first();
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $ra = RencanaAksi::where('unit_organisasi_id', $seksiPelayanan->id)->first();

        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $seksiPelayanan->id,
            'user_id' => $kasi->id,
            'status' => 'disetujui',
        ]);

        LaporanDetailIndikator::create([
            'laporan_detail_id' => $detail->id,
            'rencana_aksi_id' => $ra->id,
            'target_kinerja' => 10,
            'realisasi_kinerja' => 9,
            'persentase_kinerja' => 90.0,
            'predikat_efektivitas' => 'efektif',
            'anggaran_pagu' => 20000000,
            'realisasi_anggaran' => 16000000,
            'persentase_anggaran' => 80.0,
            'predikat_efisiensi' => 'efisien',
        ]);

        // 1. Camat Summary Widget with active report data
        $camat = User::where('email', 'camat@malangbong.go.id')->first();
        $this->actingAs($camat);

        Livewire::withQueryParams(['tahun' => 2026, 'bulan' => 8])
            ->test(CamatSummaryWidget::class)
            ->assertSuccessful();

        // 2. Sekmat Progress Widget with active report data
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->first();
        $this->actingAs($sekmat);

        Livewire::withQueryParams(['tahun' => 2026, 'bulan' => 8])
            ->test(SekmatProgressWidget::class)
            ->assertSuccessful();

        // 3. Sekmat Monitoring Table Widget with active report data
        Livewire::withQueryParams(['tahun' => 2026, 'bulan' => 8])
            ->test(SekmatUnitStatusTableWidget::class)
            ->assertSuccessful();

        // 4. Chart Widget with active report data
        Livewire::withQueryParams(['tahun' => 2026, 'bulan' => 8])
            ->test(KecamatanPerformanceChartWidget::class)
            ->assertSuccessful();
    }

    public function test_dispensasi_without_expiration_is_not_active(): void
    {
        $bulanDate = Carbon::create(2026, 8, 1);
        $laporan = Laporan::create([
            'bulan_pelaporan' => $bulanDate->toDateString(),
            'tahun' => 2026,
            'status' => 'draft',
        ]);

        $seksiPelayanan = UnitOrganisasi::where('kode_unit', 'SEKSI-PELAYANAN')->first();
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();

        // Dispensasi true but dispensasi_sampai is null
        $detail = LaporanDetail::create([
            'laporan_id' => $laporan->id,
            'unit_organisasi_id' => $seksiPelayanan->id,
            'user_id' => $kasi->id,
            'status' => 'draft',
            'is_dispensasi' => true,
            'dispensasi_sampai' => null,
        ]);

        $this->assertFalse($detail->hasActiveDispensasi());

        // Now set valid future date
        $detail->update([
            'dispensasi_sampai' => now()->addDays(2),
        ]);

        $this->assertTrue($detail->fresh()->hasActiveDispensasi());
    }
}
