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
        Schema::create('laporan_kinerja_v2', function (Blueprint $table) {
            $table->id();
            $table->string('judul_pelaporan')->comment('Buat Pelaporan / Nama Laporan');
            $table->unsignedTinyInteger('periode_bulan')->default(now()->month);
            $table->unsignedSmallInteger('periode_tahun')->default(now()->year);
            $table->date('tanggal_pelaporan')->default(now()->toDateString());
            $table->foreignId('unit_organisasi_id')->constrained('unit_organisasis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_pejabat');
            $table->string('nip_pejabat');
            $table->string('jabatan_pejabat');
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('draft');
            $table->json('bukti_dukung')->nullable()->comment('Daftar berkas bukti dukung');
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['unit_organisasi_id', 'status']);
            $table->index(['periode_tahun', 'periode_bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_kinerja_v2');
    }
};
