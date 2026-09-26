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
        Schema::table('laporan_kinerja_v2', function (Blueprint $table) {
            $table->text('ringkasan_kegiatan')->nullable()->after('jabatan_pejabat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_kinerja_v2', function (Blueprint $table) {
            $table->dropColumn('ringkasan_kegiatan');
        });
    }
};
