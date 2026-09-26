<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanDokumen extends Model
{
    use HasFactory;

    protected $table = 'laporan_dokumens';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_detail_id',
        'nama_dokumen',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function laporanDetail(): BelongsTo
    {
        return $this->belongsTo(LaporanDetail::class, 'laporan_detail_id');
    }
}
