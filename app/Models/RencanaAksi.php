<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaAksi extends Model
{
    use HasFactory;

    protected $table = 'rencana_aksis';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'unit_organisasi_id',
        'sasaran_strategis_id',
        'uraian_rencana_aksi',
        'indikator_kinerja',
        'target_default',
        'satuan_target',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_default' => 'decimal:2',
        ];
    }

    /**
     * Unit organisasi pengampu aksi ini.
     */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_organisasi_id');
    }

    /**
     * Sasaran strategis induk dari aksi ini.
     */
    public function sasaranStrategis(): BelongsTo
    {
        return $this->belongsTo(SasaranStrategis::class, 'sasaran_strategis_id');
    }

    /**
     * Relasi ke entri capaian indikator pada laporan detail bulanan.
     */
    public function laporanDetailIndikators(): HasMany
    {
        return $this->hasMany(LaporanDetailIndikator::class, 'rencana_aksi_id');
    }
}
