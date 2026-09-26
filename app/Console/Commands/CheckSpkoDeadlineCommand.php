<?php

namespace App\Console\Commands;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSpkoDeadlineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spko:check-deadline {--force : Jalankan pengingat tanpa memeriksa tanggal 7 atau 9}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pemeriksaan batas waktu pelaporan kinerja (cut-off tgl 10) dan pengiriman notifikasi pengingat otomatis H-3 dan H-1 ke Kasi dan Sekmat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $day = $now->day;
        $isForce = (bool) $this->option('force');

        $isH3 = ($day === 7);
        $isH1 = ($day === 9);

        if (! $isForce && ! $isH3 && ! $isH1) {
            $this->info("Hari ini tanggal {$day}. Notifikasi cut-off otomatis hanya dikirim pada H-3 (tgl 7) dan H-1 (tgl 9). Gunakan --force untuk menjalankan manual.");

            return Command::SUCCESS;
        }

        $reminderType = $isH1 ? 'H-1' : ($isH3 ? 'H-3' : 'Manual/Force');
        $this->warn("Menjalankan pemeriksaan deadline SPKO ({$reminderType})...");

        // Periode yang harus dilaporkan adalah bulan sebelumnya
        $targetMonthDate = $now->copy()->subMonth()->startOfMonth();
        $targetMonthName = $targetMonthDate->translatedFormat('F Y');
        $cutoffDate = LaporanDetail::calculateCutoffDate($targetMonthDate);
        $deadlineString = "{$cutoffDate->translatedFormat('d F Y')} pukul {$cutoffDate->format('H:i')} WIB";

        $mandatoryUnits = UnitOrganisasi::where('wajib_dilaporkan', true)->orderBy('urutan')->get();
        $laporanHeader = Laporan::whereDate('bulan_pelaporan', $targetMonthDate->toDateString())->first();

        $delinquentUnits = [];

        foreach ($mandatoryUnits as $unit) {
            $detail = null;
            if ($laporanHeader) {
                $detail = LaporanDetail::where('laporan_id', $laporanHeader->id)
                    ->where('unit_organisasi_id', $unit->id)
                    ->first();
            }

            // Jika belum ada detail atau status masih draft/ditolak
            if (! $detail || in_array($detail->status, ['draft', 'ditolak'], true)) {
                $delinquentUnits[] = [
                    'unit' => $unit,
                    'status' => $detail ? $detail->status : 'belum_dibuat',
                ];
            }
        }

        $unsubmittedCount = count($delinquentUnits);
        $this->info("Ditemukan {$unsubmittedCount} dari {$mandatoryUnits->count()} unit operasional belum mengajukan laporan periode {$targetMonthName}.");

        $notifiedKasiCount = 0;

        foreach ($delinquentUnits as $item) {
            /** @var UnitOrganisasi $unit */
            $unit = $item['unit'];
            $status = $item['status'];

            // Cari user Kasi untuk unit ini
            $kasiUsers = User::where('role', 'kasi')
                ->where('unit_organisasi_id', $unit->id)
                ->where('is_active', true)
                ->get();

            $statusText = match ($status) {
                'belum_dibuat' => 'belum dibuat sama sekali',
                'draft' => 'masih berstatus draft',
                'ditolak' => 'perlu revisi dan belum diajukan ulang',
                default => $status,
            };

            $urgencyTitle = $isH1
                ? "⚠️ PERINGATAN H-1: Batas Waktu Pelaporan SI-PELAJAR ({$unit->nama_unit})"
                : "⏳ PENGINGAT H-3: Batas Waktu Pelaporan SI-PELAJAR ({$unit->nama_unit})";

            $urgencyBody = "Laporan kinerja unit Anda untuk periode {$targetMonthName} {$statusText}. Batas pengajuan adalah {$deadlineString}. Mohon segera lengkapi dan ajukan.";

            foreach ($kasiUsers as $kasi) {
                Notification::make()
                    ->title($urgencyTitle)
                    ->body($urgencyBody)
                    ->icon($isH1 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock')
                    ->color($isH1 ? 'danger' : 'warning')
                    ->actions([
                        Action::make('bukaForm')
                            ->label('Buka Formulir Laporan')
                            ->url('/admin/laporan-details')
                            ->button(),
                    ])
                    ->sendToDatabase($kasi);

                $notifiedKasiCount++;
            }
        }

        // Kirim rekap ringkasan ke Sekmat (admin_kecamatan) & Superadmin
        $adminUsers = User::whereIn('role', ['admin_kecamatan', 'superadmin'])
            ->where('is_active', true)
            ->get();

        $adminTitle = "📋 Pengingat Cut-Off Dikirim: {$unsubmittedCount} Unit Belum Mengajukan";
        $adminBody = "Pemberitahuan {$reminderType} telah dikirim ke {$notifiedKasiCount} pejabat seksi terkait batas pelaporan periode {$targetMonthName}.";

        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title($adminTitle)
                ->body($adminBody)
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->actions([
                    Action::make('cekVerifikasi')
                        ->label('Meja Verifikasi Sekmat')
                        ->url('/admin/verifikasi-laporan-unit')
                        ->button(),
                ])
                ->sendToDatabase($admin);
        }

        // Catat ke Spatie Activity Log
        if (function_exists('activity')) {
            activity('spko_scheduler')
                ->withProperties([
                    'periode' => $targetMonthDate->toDateString(),
                    'reminder_type' => $reminderType,
                    'unsubmitted_units_count' => $unsubmittedCount,
                    'kasi_notified' => $notifiedKasiCount,
                ])
                ->log("Pemeriksaan scheduler SPKO deadline cut-off berhasil dijalankan ({$reminderType}).");
        }

        Log::info("SPKO check-deadline ({$reminderType}): {$unsubmittedCount} unit belum mengajukan, {$notifiedKasiCount} Kasi ternotifikasi.");
        $this->table(
            ['Unit Organisasi', 'Status Terkini'],
            array_map(fn ($item) => [$item['unit']->nama_unit, strtoupper($item['status'])], $delinquentUnits)
        );

        $this->info("Selesai. {$notifiedKasiCount} notifikasi telah dikirim ke Kasi dan ".count($adminUsers).' admin.');

        return Command::SUCCESS;
    }
}
