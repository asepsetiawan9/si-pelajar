<?php

use Database\Seeders\UnitOrganisasiSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan master 7 unit telah terisi sebelum constraint FK dipasang
        $seeder = new UnitOrganisasiSeeder;
        $seeder->run();

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('unit_organisasi_id')
                ->references('id')
                ->on('unit_organisasis')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_organisasi_id']);
        });
    }
};
