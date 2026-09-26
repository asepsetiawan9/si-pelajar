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
        Schema::table('laporans', function (Blueprint $table) {
            $table->enum('cutoff_status', ['otomatis', 'terbuka', 'tertutup'])
                ->default('otomatis')
                ->after('status');
            $table->timestamp('custom_cutoff_at')->nullable()->after('cutoff_status');
            $table->text('catatan_cutoff')->nullable()->after('custom_cutoff_at');
            $table->foreignId('cutoff_updated_by')->nullable()->after('catatan_cutoff')->constrained('users')->nullOnDelete();
            $table->timestamp('cutoff_updated_at')->nullable()->after('cutoff_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropForeign(['cutoff_updated_by']);
            $table->dropColumn([
                'cutoff_status',
                'custom_cutoff_at',
                'catatan_cutoff',
                'cutoff_updated_by',
                'cutoff_updated_at',
            ]);
        });
    }
};
