<?php

namespace Tests\Feature;

use App\Filament\Widgets\KecamatanPerformanceChartWidget;
use App\Models\Laporan;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\LaporanKinerjaV2Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DummyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(DummyDataSeeder::class);
    }

    public function test_dummy_data_seeder_creates_three_periods_with_complete_structure(): void
    {
        // 1. Tiga periode laporan harus terdaftar
        $this->assertEquals(3, Laporan::count());

        $laporanAgustus = Laporan::whereDate('bulan_pelaporan', '2026-08-01')->first();
        $this->assertNotNull($laporanAgustus);
        $this->assertEquals('disetujui', $laporanAgustus->status);
        $this->assertNotNull($laporanAgustus->disetujui_pada);

        // 2. Seluruh 7 unit terisi untuk Agustus 2026 dan berstatus disetujui
        $this->assertEquals(7, $laporanAgustus->details()->where('status', 'disetujui')->count());

        // 3. Layanan PATEN Seksi Pelayanan terisi 88 pemohon
        $detailPelayanan = $laporanAgustus->details()->where('unit_organisasi_id', 7)->first();
        $this->assertNotNull($detailPelayanan);
        $totalPemohon = $detailPelayanan->layanans()->sum('jumlah');
        $this->assertEquals(88, $totalPemohon);

        // 4. September 2026 memiliki variasi status real-time
        $laporanSeptember = Laporan::whereDate('bulan_pelaporan', '2026-09-01')->first();
        $this->assertNotNull($laporanSeptember);
        $this->assertEquals('menunggu_verifikasi', $laporanSeptember->status);
        $this->assertEquals(2, $laporanSeptember->details()->where('status', 'disetujui')->count());
        $this->assertEquals(1, $laporanSeptember->details()->where('status', 'diajukan')->count());
        $this->assertEquals(1, $laporanSeptember->details()->where('status', 'ditolak')->count());
        $this->assertEquals(3, $laporanSeptember->details()->where('status', 'draft')->count());
    }

    public function test_chart_widget_renders_data_correctly_with_dummy_data(): void
    {
        $this->seed(LaporanKinerjaV2Seeder::class);

        $widget = new KecamatanPerformanceChartWidget;
        $widget->filters = ['tahun' => 2026, 'bulan' => 8];

        $ref = new ReflectionMethod($widget, 'getData');
        $ref->setAccessible(true);
        $data = $ref->invoke($widget);

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(7, $data['labels']);
        $this->assertCount(2, $data['datasets']);

        // Data berkas bukti dukung 7 unit
        $berkasDataset = $data['datasets'][0];
        $this->assertEquals('Berkas Bukti Dukung (Dokumen)', $berkasDataset['label']);
        foreach ($berkasDataset['data'] as $val) {
            $this->assertGreaterThan(0, $val);
        }

        // Data laporan/agenda terkirim 7 unit
        $laporanDataset = $data['datasets'][1];
        $this->assertEquals('Laporan / Agenda Terkirim', $laporanDataset['label']);
        foreach ($laporanDataset['data'] as $val) {
            $this->assertEquals(1, $val);
        }
    }
}
