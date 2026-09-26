<?php

namespace Database\Seeders;

use App\Models\UnitOrganisasi;
use Illuminate\Database\Seeder;

class UnitOrganisasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'id' => 1,
                'nama_unit' => 'Sub Bag Umum, Perencanaan Evaluasi dan Pelaporan',
                'kode_unit' => 'SUBBAG-UMUM',
                'urutan' => 1,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 2,
                'nama_unit' => 'Sub Bagian Keuangan dan BMD',
                'kode_unit' => 'SUBBAG-KEUANGAN',
                'urutan' => 2,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 3,
                'nama_unit' => 'Seksi Pemerintahan',
                'kode_unit' => 'SEKSI-PEMERINTAHAN',
                'urutan' => 3,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 4,
                'nama_unit' => 'Seksi Kesejahteraan Masyarakat',
                'kode_unit' => 'SEKSI-KESRA',
                'urutan' => 4,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 5,
                'nama_unit' => 'Seksi Pemberdayaan Masyarakat dan Desa',
                'kode_unit' => 'SEKSI-PMD',
                'urutan' => 5,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 6,
                'nama_unit' => 'Seksi Ketentraman dan Ketertiban',
                'kode_unit' => 'SEKSI-TRANTIB',
                'urutan' => 6,
                'wajib_dilaporkan' => true,
            ],
            [
                'id' => 7,
                'nama_unit' => 'Seksi Pelayanan',
                'kode_unit' => 'SEKSI-PELAYANAN',
                'urutan' => 7,
                'wajib_dilaporkan' => true,
            ],
        ];

        foreach ($units as $unit) {
            UnitOrganisasi::updateOrCreate(
                ['id' => $unit['id']],
                $unit
            );
        }
    }
}
