<?php

namespace App\Filament\Resources\LaporanDetailResource\Pages;

use App\Filament\Resources\LaporanDetailResource;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLaporanDetail extends CreateRecord
{
    protected static string $resource = LaporanDetailResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Penanganan pembuatan record yang aman dari race condition (concurrency-safe header)
     * serta validasi otomatis cut-off tanggal 10.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $bulanRaw = $data['bulan_pelaporan'] ?? now()->subMonth()->startOfMonth()->toDateString();
            $bulanDate = Carbon::parse($bulanRaw)->startOfMonth()->toDateString();
            $tahun = (int) Carbon::parse($bulanDate)->year;

            // 1. Concurrency-Safe Auto-Create Header Kompilasi Laporan Kecamatan
            $laporan = Laporan::firstOrCreate(
                ['bulan_pelaporan' => $bulanDate],
                [
                    'tahun' => $tahun,
                    'status' => 'draft',
                ]
            );

            $unitId = $data['unit_organisasi_id'] ?? auth()->user()?->unit_organisasi_id;

            if (! $unitId) {
                throw ValidationException::withMessages([
                    'data.unit_organisasi_id' => 'Unit organisasi pelapor wajib ditentukan.',
                ]);
            }

            // 2. Proteksi Duplikasi Laporan Unit pada Bulan yang Sama
            $existing = LaporanDetail::where('laporan_id', $laporan->id)
                ->where('unit_organisasi_id', $unitId)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'data.bulan_pelaporan' => 'Laporan kinerja untuk unit ini pada periode '.Carbon::parse($bulanDate)->translatedFormat('F Y').' sudah terdaftar. Silakan buka draf laporan tersebut untuk mengedit.',
                ]);
            }

            // 3. Validasi Cut-Off Pelaporan (Dinamis / Custom)
            $isPastCutoff = LaporanDetail::isPastCutoff($bulanDate);
            $user = auth()->user();
            $isAdmin = $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());

            if ($isPastCutoff && ! $isAdmin) {
                $cutoffDate = LaporanDetail::calculateCutoffDate($bulanDate);
                $isClosedManual = $laporan->cutoff_status === 'tertutup';
                $msg = $isClosedManual
                    ? 'Akses pengisian laporan kinerja periode ini sedang ditutup oleh Sekretaris Camat.'
                    : 'Batas waktu pelaporan bulanan (cut-off: '.$cutoffDate->translatedFormat('d F Y H:i').' WIB) untuk periode ini telah lewat. Hubungi Sekretaris Camat untuk mengajukan dispensasi.';

                throw ValidationException::withMessages([
                    'data.bulan_pelaporan' => $msg,
                ]);
            }

            $data['laporan_id'] = $laporan->id;
            $data['unit_organisasi_id'] = $unitId;
            $data['user_id'] = auth()->id();
            $data['is_late'] = $isPastCutoff;
            $data['status'] = 'draft';

            unset($data['bulan_pelaporan']);

            /** @var LaporanDetail $record */
            $record = static::getModel()::create($data);

            return $record;
        });
    }
}
