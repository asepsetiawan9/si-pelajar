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
        Schema::create('sasaran_strategis', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun')->index();
            $table->text('uraian_sasaran');
            $table->string('indikator_kinerja');
            $table->decimal('target_angka', 8, 2);
            $table->string('satuan', 50)->default('Nilai');
            $table->text('program_penunjang');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sasaran_strategis');
    }
};
