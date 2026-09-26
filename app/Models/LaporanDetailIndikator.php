<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanDetailIndikator extends Model
{
    use HasFactory;

    protected $table = 'laporan_detail_indikators';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_detail_id',
        'rencana_aksi_id',
        'target_kinerja',
        'realisasi_kinerja',
        'persentase_kinerja',
        'predikat_efektivitas',
        'anggaran_pagu',
        'realisasi_anggaran',
        'persentase_anggaran',
        'predikat_efisiensi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_kinerja' => 'decimal:2',
            'realisasi_kinerja' => 'decimal:2',
            'persentase_kinerja' => 'decimal:2',
            'anggaran_pagu' => 'decimal:2',
            'realisasi_anggaran' => 'decimal:2',
            'persentase_anggaran' => 'decimal:2',
        ];
    }

    public function laporanDetail(): BelongsTo
    {
        return $this->belongsTo(LaporanDetail::class, 'laporan_detail_id');
    }

    public function rencanaAksi(): BelongsTo
    {
        return $this->belongsTo(RencanaAksi::class, 'rencana_aksi_id');
    }

    /**
     * Hitung persentase realisasi kinerja: (Realisasi / Target) * 100.
     */
    public static function calculatePersentaseKinerja(float|int|string|null $target, float|int|string|null $realisasi): float
    {
        $targetFloat = (float) ($target ?? 0);
        $realisasiFloat = (float) ($realisasi ?? 0);

        if ($targetFloat <= 0) {
            return $realisasiFloat > 0 ? 100.00 : 0.00;
        }

        return round(($realisasiFloat / $targetFloat) * 100, 2);
    }

    /**
     * Tentukan predikat efektivitas kinerja sesuai standar Permenpan-RB:
     * > 100%       : Sangat Efektif
     * 90% s.d 100% : Efektif
     * 60% s.d 89%  : Cukup Efektif
     * < 60%        : Tidak Efektif
     */
    public static function calculatePredikatEfektivitas(float $persentase): string
    {
        if ($persentase > 100) {
            return 'sangat_efektif';
        }

        if ($persentase >= 90) {
            return 'efektif';
        }

        if ($persentase >= 60) {
            return 'cukup_efektif';
        }

        return 'tidak_efektif';
    }

    /**
     * Hitung persentase serapan belanja: (Realisasi / Pagu) * 100.
     */
    public static function calculatePersentaseAnggaran(float|int|string|null $pagu, float|int|string|null $realisasi): float
    {
        $paguFloat = (float) ($pagu ?? 0);
        $realisasiFloat = (float) ($realisasi ?? 0);

        if ($paguFloat <= 0) {
            return 0.00;
        }

        return round(($realisasiFloat / $paguFloat) * 100, 2);
    }

    /**
     * Tentukan predikat efisiensi anggaran sesuai standar Permenpan-RB:
     * < 60%        : Sangat Efisien
     * 60% s.d 90%  : Efisien
     * 91% s.d 100% : Cukup Efisien
     * > 100%       : Tidak Efisien (Over-budget)
     */
    public static function calculatePredikatEfisiensi(float $persentase): string
    {
        if ($persentase < 60) {
            return 'sangat_efisien';
        }

        if ($persentase <= 90) {
            return 'efisien';
        }

        if ($persentase <= 100) {
            return 'cukup_efisien';
        }

        return 'tidak_efisien';
    }

    public function getEfektivitasLabelAttribute(): string
    {
        return match ($this->predikat_efektivitas) {
            'sangat_efektif' => 'Sangat Efektif',
            'efektif' => 'Efektif',
            'cukup_efektif' => 'Cukup Efektif',
            'tidak_efektif' => 'Tidak Efektif',
            default => '-',
        };
    }

    public function getEfisiensiLabelAttribute(): string
    {
        return match ($this->predikat_efisiensi) {
            'sangat_efisien' => 'Sangat Efisien',
            'efisien' => 'Efisien',
            'cukup_efisien' => 'Cukup Efisien',
            'tidak_efisien' => 'Tidak Efisien',
            default => '-',
        };
    }
}
