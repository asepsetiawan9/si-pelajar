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
        Schema::create('laporan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->foreignId('unit_organisasi_id')->constrained('unit_organisasis')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_verifikasi_sekmat')->nullable();
            $table->text('latar_belakang')->nullable();
            $table->text('keterangan_keterkaitan')->nullable();
            $table->text('keluhan_masyarakat')->nullable();
            $table->text('hambatan')->nullable();
            $table->text('simpulan')->nullable();
            $table->boolean('is_late')->default(false);
            $table->boolean('is_dispensasi')->default(false);
            $table->timestamp('dispensasi_sampai')->nullable();
            $table->text('alasan_dispensasi')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['laporan_id', 'unit_organisasi_id'], 'uk_detail_laporan_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_details');
    }
};
