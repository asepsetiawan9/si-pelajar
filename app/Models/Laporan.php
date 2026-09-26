<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laporan extends Model
{
    use HasFactory;

    protected $table = 'laporans';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bulan_pelaporan',
        'tahun',
        'status',
        'cutoff_status',
        'custom_cutoff_at',
        'catatan_cutoff',
        'cutoff_updated_by',
        'cutoff_updated_at',
        'catatan_camat',
        'diajukan_oleh',
        'disetujui_oleh',
        'disetujui_pada',
        'dokumen_rekap_pdf_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bulan_pelaporan' => 'date',
            'tahun' => 'integer',
            'custom_cutoff_at' => 'datetime',
            'cutoff_updated_at' => 'datetime',
            'disetujui_pada' => 'datetime',
        ];
    }

    /**
     * Detail pelaporan dari masing-masing unit organisasi operasional.
     */
    public function laporanDetails(): HasMany
    {
        return $this->hasMany(LaporanDetail::class, 'laporan_id');
    }

    public function details(): HasMany
    {
        return $this->laporanDetails();
    }

    /**
     * Admin Kecamatan / Sekmat yang mengajukan laporan gabungan ke Camat.
     */
    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    /**
     * Camat Malangbong yang mengesahkan laporan kinerja.
     */
    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * User (Sekmat / Superadmin) yang memperbarui konfigurasi cut-off.
     */
    public function cutoffUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cutoff_updated_by');
    }

    /**
     * Hitung tanggal batas cut-off efektif untuk laporan ini.
     * Menggunakan custom_cutoff_at jika ditentukan, atau fallback ke default tgl 10.
     */
    public function getEffectiveCutoffDate(): Carbon
    {
        if ($this->custom_cutoff_at !== null) {
            return $this->custom_cutoff_at->copy();
        }

        $bulan = $this->bulan_pelaporan instanceof Carbon
            ? $this->bulan_pelaporan->copy()
            : Carbon::parse($this->bulan_pelaporan);

        $cutoffDay = (int) config('spko.cutoff_day', 10);

        return $bulan->startOfMonth()->addMonth()->day($cutoffDay)->endOfDay();
    }

    /**
     * Cek apakah akses pengisian laporan periode ini saat ini sedang dibuka.
     * - 'terbuka': Akses dibuka manual oleh Sekmat (bisa diisi kapan saja).
     * - 'tertutup': Akses ditutup manual oleh Sekmat (terkunci kapan saja).
     * - 'otomatis': Mengikuti tanggal batas cut-off efektif.
     */
    public function isCutoffOpen(): bool
    {
        $status = $this->cutoff_status ?? 'otomatis';

        if ($status === 'terbuka') {
            return true;
        }

        if ($status === 'tertutup') {
            return false;
        }

        return now()->lessThanOrEqualTo($this->getEffectiveCutoffDate());
    }

    /**
     * Cek apakah akses pengisian laporan periode ini saat ini ditutup / terkunci.
     */
    public function isCutoffClosed(): bool
    {
        return ! $this->isCutoffOpen();
    }

    /**
     * Memeriksa apakah seluruh 7 unit organisasi operasional wajib telah disetujui Sekmat.
     */
    public function isAllMandatoryUnitsApproved(): bool
    {
        $mandatoryUnitIds = UnitOrganisasi::where('wajib_dilaporkan', true)->pluck('id');

        if ($mandatoryUnitIds->isEmpty()) {
            return false;
        }

        $approvedCount = $this->laporanDetails()
            ->whereIn('unit_organisasi_id', $mandatoryUnitIds)
            ->where('status', 'disetujui')
            ->count();

        return $approvedCount >= $mandatoryUnitIds->count();
    }
}
