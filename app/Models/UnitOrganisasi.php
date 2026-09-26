<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOrganisasi extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_unit',
        'kode_unit',
        'urutan',
        'wajib_dilaporkan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'wajib_dilaporkan' => 'boolean',
        ];
    }

    /**
     * Relasi ke seluruh pejabat/staf di unit ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_organisasi_id');
    }

    /**
     * Relasi ke rencana aksi yang menjadi tupoksi unit ini.
     */
    public function rencanaAksis(): HasMany
    {
        return $this->hasMany(RencanaAksi::class, 'unit_organisasi_id');
    }

    /**
     * Relasi ke seluruh laporan detail unit ini.
     */
    public function laporanDetails(): HasMany
    {
        return $this->hasMany(LaporanDetail::class, 'unit_organisasi_id');
    }

    public function laporanKinerjaV2s(): HasMany
    {
        return $this->hasMany(LaporanKinerjaV2::class, 'unit_organisasi_id');
    }
}
