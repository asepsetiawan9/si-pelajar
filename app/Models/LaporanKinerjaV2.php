<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKinerjaV2 extends Model
{
    use HasFactory;

    protected $table = 'laporan_kinerja_v2';

    protected $fillable = [
        'judul_pelaporan',
        'periode_bulan',
        'periode_tahun',
        'tanggal_pelaporan',
        'unit_organisasi_id',
        'user_id',
        'nama_pejabat',
        'nip_pejabat',
        'jabatan_pejabat',
        'status',
        'bukti_dukung',
        'catatan_verifikasi',
        'verified_by',
        'verified_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'tanggal_pelaporan' => 'date',
            'bukti_dukung' => 'array',
            'verified_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_organisasi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDiajukan(): bool
    {
        return $this->status === 'diajukan';
    }

    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft (Belum Dikirim)',
            'diajukan' => 'Perlu Verifikasi',
            'disetujui' => 'Disetujui / Terverifikasi',
            'ditolak' => 'Perlu Revisi',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'diajukan' => 'warning',
            'disetujui' => 'success',
            'ditolak' => 'danger',
            default => 'primary',
        };
    }
}
