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
        Schema::create('laporan_detail_layanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_detail_id')->constrained('laporan_details')->cascadeOnDelete();
            $table->string('nama_layanan');
            $table->integer('jumlah')->default(0);
            $table->string('satuan', 50)->default('Pemohon');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_detail_layanans');
    }
};
