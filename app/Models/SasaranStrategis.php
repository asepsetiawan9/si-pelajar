<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SasaranStrategis extends Model
{
    use HasFactory;

    protected $table = 'sasaran_strategis';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'uraian_sasaran',
        'indikator_kinerja',
        'target_angka',
        'satuan',
        'program_penunjang',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'target_angka' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke rencana aksi turunan sasaran strategis ini.
     */
    public function rencanaAksis(): HasMany
    {
        return $this->hasMany(RencanaAksi::class, 'sasaran_strategis_id');
    }
}
