<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $judul_pelaporan
 * @property int $periode_bulan
 * @property int $periode_tahun
 * @property \Carbon\Carbon|null $tanggal_pelaporan
 * @property int $unit_organisasi_id
 * @property int $user_id
 * @property string $nama_pejabat
 * @property string $nip_pejabat
 * @property string $jabatan_pejabat
 * @property string|null $ringkasan_kegiatan
 * @property string $status
 * @property array|null $bukti_dukung
 * @property string|null $catatan_verifikasi
 * @property int|null $verified_by
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
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
        'ringkasan_kegiatan',
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

    public function getNamaBulanAttribute(): string
    {
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulanMap[$this->periode_bulan] ?? "Bulan {$this->periode_bulan}";
    }

    public function getBuktiDukungCountAttribute(): int
    {
        if (empty($this->bukti_dukung) || ! is_array($this->bukti_dukung)) {
            return 0;
        }

        return count($this->bukti_dukung);
    }

    /**
     * @return array<int, array{path: string, name: string, ext: string, is_image: bool, is_pdf: bool, url: string}>
     */
    public function getBuktiDukungDetailsAttribute(): array
    {
        if (empty($this->bukti_dukung) || ! is_array($this->bukti_dukung)) {
            return [];
        }

        $details = [];
        foreach ($this->bukti_dukung as $path) {
            $filename = basename((string) $path);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            $isPdf = $ext === 'pdf';

            $details[] = [
                'path' => (string) $path,
                'name' => $filename,
                'ext' => strtoupper($ext),
                'is_image' => $isImage,
                'is_pdf' => $isPdf,
                'url' => asset('storage/'.$path),
            ];
        }

        return $details;
    }
}
