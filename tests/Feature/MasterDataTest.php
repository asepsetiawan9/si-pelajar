<?php

namespace Tests\Feature;

use App\Filament\Resources\RencanaAksiResource;
use App\Filament\Resources\SasaranStrategisResource;
use App\Filament\Resources\UnitOrganisasiResource;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_7_operational_units_are_seeded_correctly(): void
    {
        $this->assertDatabaseCount('unit_organisasis', 7);

        $expectedUnits = [
            1 => 'Sub Bag Umum, Perencanaan Evaluasi dan Pelaporan',
            2 => 'Sub Bagian Keuangan dan BMD',
            3 => 'Seksi Pemerintahan',
            4 => 'Seksi Kesejahteraan Masyarakat',
            5 => 'Seksi Pemberdayaan Masyarakat dan Desa',
            6 => 'Seksi Ketentraman dan Ketertiban',
            7 => 'Seksi Pelayanan',
        ];

        foreach ($expectedUnits as $urutan => $namaUnit) {
            $this->assertDatabaseHas('unit_organisasis', [
                'urutan' => $urutan,
                'nama_unit' => $namaUnit,
                'wajib_dilaporkan' => true,
            ]);
        }
    }

    public function test_user_belongs_to_unit_organisasi_relationship(): void
    {
        $kasiPelayanan = User::where('email', 'kasi.pelayanan@malangbong.go.id')->firstOrFail();

        $this->assertNotNull($kasiPelayanan->unitOrganisasi);
        $this->assertEquals('Seksi Pelayanan', $kasiPelayanan->unitOrganisasi->nama_unit);
        $this->assertEquals('SEKSI-PELAYANAN', $kasiPelayanan->unitOrganisasi->kode_unit);
    }

    public function test_sasaran_strategis_camat_2026_seeded_correctly(): void
    {
        $this->assertDatabaseHas('sasaran_strategis', [
            'tahun' => 2026,
            'indikator_kinerja' => 'Nilai Sinergitas Kinerja Kecamatan',
            'target_angka' => 84.00,
        ]);

        $this->assertDatabaseHas('sasaran_strategis', [
            'tahun' => 2026,
            'indikator_kinerja' => 'Indeks Reformasi Birokrasi Perangkat Daerah',
            'target_angka' => 82.63,
        ]);
    }

    public function test_rencana_aksi_for_seksi_pelayanan_is_linked(): void
    {
        $pelayanan = UnitOrganisasi::where('nama_unit', 'Seksi Pelayanan')->firstOrFail();

        $this->assertTrue(
            $pelayanan->rencanaAksis()
                ->where('uraian_rencana_aksi', 'like', '%PATEN%')
                ->exists()
        );

        $this->assertTrue(
            $pelayanan->rencanaAksis()
                ->where('uraian_rencana_aksi', 'like', '%survey kepuasan masyarakat%')
                ->exists()
        );
    }

    public function test_cutoff_day_configuration_is_set_to_10(): void
    {
        $this->assertEquals(10, config('spko.cutoff_day'));
    }

    public function test_superadmin_can_access_all_master_data_resources(): void
    {
        $superadmin = User::where('email', 'superadmin@malangbong.go.id')->firstOrFail();

        $this->actingAs($superadmin);

        $this->get(UnitOrganisasiResource::getUrl('index'))->assertSuccessful();
        $this->get(SasaranStrategisResource::getUrl('index'))->assertSuccessful();
        $this->get(RencanaAksiResource::getUrl('index'))->assertSuccessful();
    }

    public function test_sekmat_can_access_sasaran_and_rencana_aksi_but_not_unit_organisasi(): void
    {
        $sekmat = User::where('email', 'sekmat@malangbong.go.id')->firstOrFail();

        $this->actingAs($sekmat);

        // Sekmat can access Sasaran Strategis and Rencana Aksi
        $this->get(SasaranStrategisResource::getUrl('index'))->assertSuccessful();
        $this->get(RencanaAksiResource::getUrl('index'))->assertSuccessful();

        // Sekmat cannot access UnitOrganisasi (Superadmin only)
        $this->get(UnitOrganisasiResource::getUrl('index'))->assertForbidden();
    }

    public function test_kasi_cannot_access_master_data_resources(): void
    {
        $kasi = User::where('email', 'kasi.pelayanan@malangbong.go.id')->firstOrFail();

        $this->actingAs($kasi);

        $this->get(UnitOrganisasiResource::getUrl('index'))->assertForbidden();
        $this->get(SasaranStrategisResource::getUrl('index'))->assertForbidden();
        $this->get(RencanaAksiResource::getUrl('index'))->assertForbidden();
    }
}
