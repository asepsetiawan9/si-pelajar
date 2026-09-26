<?php

namespace Database\Seeders;

use App\Models\SasaranStrategis;
use Illuminate\Database\Seeder;

class SasaranStrategisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sasaranList = [
            [
                'id' => 1,
                'tahun' => 2026,
                'uraian_sasaran' => 'Meningkatnya kinerja penyelenggaraan pelayanan publik dan pemerintahan di kewilayahan',
                'indikator_kinerja' => 'Nilai Sinergitas Kinerja Kecamatan',
                'target_angka' => 84.00,
                'satuan' => 'Nilai',
                'program_penunjang' => 'Program Penyelenggaraan Pemerintahan dan Pelayanan Publik',
            ],
            [
                'id' => 2,
                'tahun' => 2026,
                'uraian_sasaran' => 'Terwujudnya birokrasi yang bersih, efektif dan efisien',
                'indikator_kinerja' => 'Indeks Reformasi Birokrasi Perangkat Daerah',
                'target_angka' => 82.63,
                'satuan' => 'Indeks',
                'program_penunjang' => 'Program Penunjang Urusan Pemerintahan Daerah Kabupaten/Kota',
            ],
        ];

        foreach ($sasaranList as $sasaran) {
            SasaranStrategis::updateOrCreate(
                ['id' => $sasaran['id']],
                $sasaran
            );
        }
    }
}
