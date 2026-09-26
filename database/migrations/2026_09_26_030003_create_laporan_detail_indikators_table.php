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
        Schema::create('laporan_detail_indikators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_detail_id')->constrained('laporan_details')->cascadeOnDelete();
            $table->foreignId('rencana_aksi_id')->constrained('rencana_aksis')->restrictOnDelete();
            $table->decimal('target_kinerja', 12, 2);
            $table->decimal('realisasi_kinerja', 12, 2);
            $table->decimal('persentase_kinerja', 8, 2);
            $table->enum('predikat_efektivitas', [
                'sangat_efektif',
                'efektif',
                'cukup_efektif',
                'tidak_efektif',
            ]);
            $table->decimal('anggaran_pagu', 15, 2)->default(0);
            $table->decimal('realisasi_anggaran', 15, 2)->default(0);
            $table->decimal('persentase_anggaran', 8, 2)->default(0);
            $table->enum('predikat_efisiensi', [
                'sangat_efisien',
                'efisien',
                'cukup_efisien',
                'tidak_efisien',
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_detail_indikators');
    }
};
