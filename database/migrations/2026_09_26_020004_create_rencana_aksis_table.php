<?php

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
        Schema::create('rencana_aksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_organisasi_id')
                ->constrained('unit_organisasis')
                ->cascadeOnDelete();
            $table->foreignId('sasaran_strategis_id')
                ->constrained('sasaran_strategis')
                ->cascadeOnDelete();
            $table->text('uraian_rencana_aksi');
            $table->string('indikator_kinerja');
            $table->decimal('target_default', 12, 2)->default(1);
            $table->string('satuan_target', 50)->default('Laporan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_aksis');
    }
};
