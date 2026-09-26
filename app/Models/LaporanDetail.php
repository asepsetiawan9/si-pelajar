<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanDetail extends Model
{
    use HasFactory;

    protected $table = 'laporan_details';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_id',
        'unit_organisasi_id',
        'user_id',
        'status',
        'catatan_verifikasi_sekmat',
        'latar_belakang',
        'keterangan_keterkaitan',
        'keluhan_masyarakat',
        'hambatan',
        'simpulan',
        'is_late',
        'is_dispensasi',
        'dispensasi_sampai',
        'alasan_dispensasi',
        'submitted_at',
        'verified_at',
        'verified_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_late' => 'boolean',
            'is_dispensasi' => 'boolean',
            'dispensasi_sampai' => 'datetime',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_organisasi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->verifiedBy();
    }

    public function indikators(): HasMany
    {
        return $this->hasMany(LaporanDetailIndikator::class, 'laporan_detail_id');
    }

    public function layanans(): HasMany
    {
        return $this->hasMany(LaporanDetailLayanan::class, 'laporan_detail_id');
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(LaporanDokumen::class, 'laporan_detail_id');
    }

    /**
     * Hitung tanggal cut-off pelaporan.
     * Prioritas:
     * 1. custom_cutoff_at pada Laporan periode terkait (jika disetel).
     * 2. Default tanggal 10 bulan berikutnya pukul 23:59:59.
     */
    public static function calculateCutoffDate(Carbon|string $bulanPelaporan): Carbon
    {
        $bulan = $bulanPelaporan instanceof Carbon ? $bulanPelaporan->copy() : Carbon::parse($bulanPelaporan);
        $bulanDate = $bulan->startOfMonth()->toDateString();

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate)->first();
        if ($laporan && $laporan->custom_cutoff_at !== null) {
            return $laporan->custom_cutoff_at->copy();
        }

        $cutoffDay = (int) config('spko.cutoff_day', 10);

        return $bulan->startOfMonth()->addMonth()->day($cutoffDay)->endOfDay();
    }

    /**
     * Cek apakah pengisian pelaporan ditutup / lewat batas cut-off.
     * - 'terbuka': Akses dibuka kapan saja oleh Sekmat (mengembalikan false).
     * - 'tertutup': Akses dikunci/ditutup kapan saja oleh Sekmat (mengembalikan true).
     * - 'otomatis': Mengikuti perbandingan tanggal batas waktu efektif.
     */
    public static function isPastCutoff(Carbon|string $bulanPelaporan): bool
    {
        $bulan = $bulanPelaporan instanceof Carbon ? $bulanPelaporan->copy() : Carbon::parse($bulanPelaporan);
        $bulanDate = $bulan->startOfMonth()->toDateString();

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate)->first();
        if ($laporan) {
            $status = $laporan->cutoff_status ?? 'otomatis';
            if ($status === 'terbuka') {
                return false;
            }
            if ($status === 'tertutup') {
                return true;
            }
        }

        return Carbon::now()->greaterThan(static::calculateCutoffDate($bulanPelaporan));
    }

    /**
     * Memeriksa apakah unit memiliki dispensasi aktif dari Admin Kecamatan/Sekmat.
     */
    public function hasActiveDispensasi(): bool
    {
        if (! $this->is_dispensasi) {
            return false;
        }

        if ($this->dispensasi_sampai === null) {
            return false;
        }

        return $this->dispensasi_sampai->isFuture();
    }

    /**
     * Status formulir terkunci (read-only): jika sudah diajukan/disetujui,
     * atau telah lewat batas cut-off tanpa dispensasi aktif.
     */
    public function isLocked(): bool
    {
        if (in_array($this->status, ['diajukan', 'disetujui'], true)) {
            return true;
        }

        $bulan = $this->laporan?->bulan_pelaporan;
        if ($bulan && static::isPastCutoff($bulan) && ! $this->hasActiveDispensasi()) {
            return true;
        }

        return false;
    }

    /**
     * Teks template narasi dasar hukum latar belakang default.
     */
    public static function defaultLatarBelakang(): string
    {
        return 'Berdasarkan Peraturan Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor 53 Tahun 2014 tentang Petunjuk Teknis Perjanjian Kinerja, Pelaporan Kinerja dan Tata Cara Reviu atas Laporan Kinerja Instansi Pemerintah serta Permenpan-RB Nomor 22 Tahun 2024, penyusunan laporan capaian kinerja ini merupakan wujud akuntabilitas pelaksanaan program, kegiatan, dan penyelenggaraan tugas pokok dan fungsi pada unit kerja di lingkungan Kecamatan Malangbong.';
    }
}
