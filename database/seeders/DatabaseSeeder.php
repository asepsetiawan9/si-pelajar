<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitOrganisasiSeeder::class,
            UserSeeder::class,
            SasaranStrategisSeeder::class,
            RencanaAksiSeeder::class,
        ]);

        if (! app()->runningUnitTests()) {
            $this->call(DummyDataSeeder::class);
        }
    }
}
