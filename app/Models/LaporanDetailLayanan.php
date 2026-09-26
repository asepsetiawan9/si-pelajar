<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanDetailLayanan extends Model
{
    use HasFactory;

    protected $table = 'laporan_detail_layanans';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'laporan_detail_id',
        'nama_layanan',
        'jumlah',
        'satuan',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function laporanDetail(): BelongsTo
    {
        return $this->belongsTo(LaporanDetail::class, 'laporan_detail_id');
    }

    /**
     * Preset 10 daftar layanan administrasi terpadu (PATEN) untuk Seksi Pelayanan.
     * Sesuai dokumen fisik riil Kecamatan Malangbong.
     */
    public static function defaultPresetsPaten(): array
    {
        return [
            ['nama_layanan' => 'Surat Keterangan Tidak Mampu (SKTM)', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Layanan Kesejahteraan Sosial'],
            ['nama_layanan' => 'Surat Izin Keramaian', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Rekomendasi Acara Warga'],
            ['nama_layanan' => 'Surat Rekomendasi Kredit', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Fasilitasi Usaha Ekonomi'],
            ['nama_layanan' => 'Surat Keterangan Hak Waris', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Pencatatan Keperdataan'],
            ['nama_layanan' => 'Surat Rekomendasi Dispensasi Nikah', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'KUA Kecamatan Malangbong'],
            ['nama_layanan' => 'Pengantar / Rekomendasi Akta Kelahiran', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Administrasi Kependudukan'],
            ['nama_layanan' => 'Layanan Legalisasi Dokumen', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Verifikasi Dokumen Umum'],
            ['nama_layanan' => 'Rekomendasi Perekaman / Cetak KTP-el', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Sinergi Disdukcapil'],
            ['nama_layanan' => 'Rekomendasi Kartu Identitas Anak (KIA)', 'jumlah' => 0, 'satuan' => 'Pemohon', 'keterangan' => 'Identitas Kependudukan Anak'],
            ['nama_layanan' => 'Layanan Pengaduan & Informasi PATEN', 'jumlah' => 0, 'satuan' => 'Layanan', 'keterangan' => 'Helpdesk Pelayanan Publik'],
        ];
    }
}
