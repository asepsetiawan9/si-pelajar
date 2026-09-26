<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LaporanApprovalService
{
    public function __construct(
        protected LaporanPdfService $pdfService
    ) {}

    /**
     * Sekmat menyetujui laporan kinerja dari unit kerja.
     */
    public function setujuiLaporanDetail(LaporanDetail $detail, User $sekmat): LaporanDetail
    {
        return DB::transaction(function () use ($detail, $sekmat) {
            $detail->update([
                'status' => 'disetujui',
                'verified_at' => Carbon::now(),
                'verified_by' => $sekmat->id,
            ]);

            activity('verifikasi_unit')
                ->performedOn($detail)
                ->causedBy($sekmat)
                ->withProperties([
                    'status' => 'disetujui',
                    'unit' => $detail->unitOrganisasi?->nama_unit,
                    'bulan' => $detail->laporan?->bulan_pelaporan?->format('F Y'),
                ])
                ->log("Laporan kinerja unit {$detail->unitOrganisasi?->nama_unit} telah diverifikasi dan disetujui oleh Sekmat ({$sekmat->name}).");

            // Kirim notifikasi database ke Kasi pembuat
            if ($detail->user) {
                Notification::make()
                    ->title('Laporan Kinerja Disetujui Sekmat')
                    ->body("Laporan kinerja unit {$detail->unitOrganisasi?->nama_unit} telah diverifikasi dan disetujui.")
                    ->success()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->button()
                            ->label('Lihat Laporan')
                            ->url(route('filament.admin.resources.laporan-details.view', ['record' => $detail->id])),
                    ])
                    ->sendToDatabase($detail->user);
            }

            return $detail;
        });
    }

    /**
     * Sekmat mengembalikan / menolak laporan kinerja unit dengan catatan perbaikan.
     */
    public function kembalikanLaporanDetail(LaporanDetail $detail, User $sekmat, string $catatan): LaporanDetail
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new InvalidArgumentException('Catatan verifikasi revisi wajib diisi oleh Sekmat.');
        }

        return DB::transaction(function () use ($detail, $sekmat, $catatan) {
            $detail->update([
                'status' => 'ditolak',
                'catatan_verifikasi_sekmat' => $catatan,
                'verified_at' => Carbon::now(),
                'verified_by' => $sekmat->id,
            ]);

            activity('verifikasi_unit')
                ->performedOn($detail)
                ->causedBy($sekmat)
                ->withProperties([
                    'status' => 'ditolak',
                    'catatan' => $catatan,
                    'unit' => $detail->unitOrganisasi?->nama_unit,
                ])
                ->log("Laporan unit {$detail->unitOrganisasi?->nama_unit} dikembalikan oleh Sekmat dengan catatan: {$catatan}");

            // Kirim notifikasi ke Kasi
            if ($detail->user) {
                Notification::make()
                    ->title('Laporan Kinerja Memerlukan Revisi')
                    ->body("Sekmat mengembalikan laporan unit {$detail->unitOrganisasi?->nama_unit}: \"{$catatan}\"")
                    ->warning()
                    ->actions([
                        NotificationAction::make('perbaiki')
                            ->button()
                            ->label('Perbaiki Sekarang')
                            ->url(route('filament.admin.resources.laporan-details.edit', ['record' => $detail->id])),
                    ])
                    ->sendToDatabase($detail->user);
            }

            return $detail;
        });
    }

    /**
     * Sekmat / Admin Kecamatan membuka dispensasi keterlambatan pasca cut-off tanggal 10.
     */
    public function bukaDispensasi(LaporanDetail $detail, User $sekmat, Carbon|string $sampai, string $alasan): LaporanDetail
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new InvalidArgumentException('Alasan dispensasi keterlambatan wajib diisi.');
        }

        $sampaiCarbon = $sampai instanceof Carbon ? $sampai : Carbon::parse($sampai);

        return DB::transaction(function () use ($detail, $sekmat, $sampaiCarbon, $alasan) {
            $detail->update([
                'is_dispensasi' => true,
                'dispensasi_sampai' => $sampaiCarbon,
                'alasan_dispensasi' => $alasan,
            ]);

            activity('dispensasi_cutoff')
                ->performedOn($detail)
                ->causedBy($sekmat)
                ->withProperties([
                    'dispensasi_sampai' => $sampaiCarbon->toDateTimeString(),
                    'alasan' => $alasan,
                    'unit' => $detail->unitOrganisasi?->nama_unit,
                ])
                ->log("Dispensasi keterlambatan diberikan untuk {$detail->unitOrganisasi?->nama_unit} hingga {$sampaiCarbon->format('d/m/Y H:i')} WIB. Alasan: {$alasan}");

            // Notifikasi ke Kasi
            if ($detail->user) {
                Notification::make()
                    ->title('Dispensasi Pelaporan Kinerja Dibuka')
                    ->body("Dispensasi pengisian/perbaikan laporan unit {$detail->unitOrganisasi?->nama_unit} aktif sampai {$sampaiCarbon->translatedFormat('d F Y H:i')} WIB.")
                    ->info()
                    ->actions([
                        NotificationAction::make('edit')
                            ->button()
                            ->label('Buka Formulir')
                            ->url(route('filament.admin.resources.laporan-details.edit', ['record' => $detail->id])),
                    ])
                    ->sendToDatabase($detail->user);
            }

            return $detail;
        });
    }

    /**
     * Sekmat mengajukan laporan kompilasi 7 unit kecamatan ke Camat.
     */
    public function ajukanKeCamat(Laporan $laporan, User $sekmat): Laporan
    {
        if (! $laporan->isAllMandatoryUnitsApproved()) {
            throw new InvalidArgumentException('Tidak dapat mengajukan ke Camat. Seluruh 7 unit organisasi operasional wajib berstatus "disetujui" terlebih dahulu.');
        }

        return DB::transaction(function () use ($laporan, $sekmat) {
            $laporan->update([
                'status' => 'diajukan_ke_camat',
                'diajukan_oleh' => $sekmat->id,
            ]);

            $bulanStr = $laporan->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$laporan->tahun}";

            activity('pengajuan_kecamatan')
                ->performedOn($laporan)
                ->causedBy($sekmat)
                ->withProperties([
                    'status' => 'diajukan_ke_camat',
                    'bulan' => $bulanStr,
                ])
                ->log("Laporan kompilasi 7 unit Kecamatan Malangbong periode {$bulanStr} diajukan ke Camat oleh {$sekmat->name}.");

            // Notifikasi ke semua pejabat Camat
            $camatUsers = User::where('role', 'camat')->where('is_active', true)->get();
            foreach ($camatUsers as $camatUser) {
                Notification::make()
                    ->title('Laporan Kinerja Kecamatan Siap Disahkan')
                    ->body("Sekmat ({$sekmat->name}) telah mengajukan kompilasi Laporan Kinerja Kecamatan Malangbong periode {$bulanStr} untuk ditelaah dan disahkan.")
                    ->info()
                    ->actions([
                        NotificationAction::make('tinjau')
                            ->button()
                            ->label('Telaah Sekarang')
                            ->url(route('filament.admin.resources.laporan-kecamatan.view', ['record' => $laporan->id])),
                    ])
                    ->sendToDatabase($camatUser);
            }

            return $laporan;
        });
    }

    /**
     * Camat mengesahkan laporan kinerja kecamatan dan men-generate dokumen PDF resmi.
     */
    public function sahkanLaporan(Laporan $laporan, User $camat): Laporan
    {
        return DB::transaction(function () use ($laporan, $camat) {
            // Generate PDF Resmi final
            $pdfPath = $this->pdfService->generateLaporanKecamatanPdf($laporan);

            $laporan->update([
                'status' => 'disetujui',
                'disetujui_oleh' => $camat->id,
                'disetujui_pada' => Carbon::now(),
                'dokumen_rekap_pdf_path' => $pdfPath,
            ]);

            $bulanStr = $laporan->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$laporan->tahun}";

            activity('pengesahan_camat')
                ->performedOn($laporan)
                ->causedBy($camat)
                ->withProperties([
                    'status' => 'disetujui',
                    'disetujui_pada' => Carbon::now()->toDateTimeString(),
                    'dokumen_pdf' => $pdfPath,
                ])
                ->log("Laporan Kinerja Kecamatan Malangbong periode {$bulanStr} telah resmi disahkan oleh Camat ({$camat->name}).");

            // Broadcast Notifikasi ke Sekmat & seluruh Kasi
            $recipients = User::whereIn('role', ['admin_kecamatan', 'kasi', 'superadmin'])
                ->where('is_active', true)
                ->get();

            foreach ($recipients as $recipient) {
                Notification::make()
                    ->title('Laporan Kinerja Resmi Disahkan Camat')
                    ->body("Laporan Kinerja Kecamatan Malangbong periode {$bulanStr} telah resmi disahkan oleh Camat. Dokumen PDF resmi siap diunduh.")
                    ->success()
                    ->actions([
                        NotificationAction::make('unduh')
                            ->button()
                            ->label('Unduh Dokumen PDF')
                            ->url(route('spko.laporan.pdf', ['laporan' => $laporan->id])),
                    ])
                    ->sendToDatabase($recipient);
            }

            return $laporan;
        });
    }

    /**
     * Camat mengembalikan laporan gabungan ke Sekmat dengan catatan penolakan.
     */
    public function kembalikanKeSekmat(Laporan $laporan, User $camat, string $catatan): Laporan
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new InvalidArgumentException('Catatan penolakan / arahan Camat wajib diisi.');
        }

        return DB::transaction(function () use ($laporan, $camat, $catatan) {
            $laporan->update([
                'status' => 'ditolak',
                'catatan_camat' => $catatan,
            ]);

            $bulanStr = $laporan->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$laporan->tahun}";

            activity('pengesahan_camat')
                ->performedOn($laporan)
                ->causedBy($camat)
                ->withProperties([
                    'status' => 'ditolak',
                    'catatan_camat' => $catatan,
                ])
                ->log("Laporan Kinerja Kecamatan Malangbong periode {$bulanStr} dikembalikan oleh Camat ke Sekmat. Catatan: {$catatan}");

            // Notifikasi ke Sekmat / Admin Kecamatan
            $adminUsers = User::where('role', 'admin_kecamatan')->where('is_active', true)->get();
            foreach ($adminUsers as $adminUser) {
                Notification::make()
                    ->title('Laporan Dikembalikan oleh Camat')
                    ->body("Camat mengembalikan Laporan Kinerja periode {$bulanStr}: \"{$catatan}\"")
                    ->warning()
                    ->actions([
                        NotificationAction::make('periksa')
                            ->button()
                            ->label('Periksa Laporan')
                            ->url(route('filament.admin.resources.laporan-kecamatan.view', ['record' => $laporan->id])),
                    ])
                    ->sendToDatabase($adminUser);
            }

            return $laporan;
        });
    }

    /**
     * Mengatur konfigurasi cut-off periode (Status, Tanggal Custom, dan Catatan).
     */
    public function aturCutoffPeriode(
        Laporan $laporan,
        string $status,
        ?Carbon $customCutoffAt = null,
        ?string $catatan = null,
        ?User $actor = null,
        bool $notify = true
    ): Laporan {
        if (! in_array($status, ['otomatis', 'terbuka', 'tertutup'], true)) {
            throw new InvalidArgumentException("Status cut-off tidak valid: {$status}");
        }

        return DB::transaction(function () use ($laporan, $status, $customCutoffAt, $catatan, $actor, $notify) {
            $user = $actor ?? auth()->user();
            $bulanStr = $laporan->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$laporan->tahun}";

            $laporan->update([
                'cutoff_status' => $status,
                'custom_cutoff_at' => $customCutoffAt,
                'catatan_cutoff' => $catatan,
                'cutoff_updated_by' => $user?->id,
                'cutoff_updated_at' => Carbon::now(),
            ]);

            $statusText = match ($status) {
                'terbuka' => 'DIBUKA BEBAS (Bisa diisi kapan saja)',
                'tertutup' => 'DITUTUP MANUAL (Akses dikunci)',
                'otomatis' => 'OTOMATIS (Mengikuti batas tanggal)',
            };

            $tglStr = $customCutoffAt ? $customCutoffAt->translatedFormat('d F Y H:i').' WIB' : 'Default Tanggal 10';

            activity('cutoff_management')
                ->performedOn($laporan)
                ->causedBy($user)
                ->withProperties([
                    'status' => $status,
                    'custom_cutoff_at' => $customCutoffAt?->toDateTimeString(),
                    'catatan' => $catatan,
                    'actor' => $user?->name,
                ])
                ->log("Konfigurasi Cut-Off periode {$bulanStr} diatur: Status [{$statusText}], Batas [{$tglStr}].".($catatan ? " Catatan: {$catatan}" : ''));

            if ($notify) {
                $this->kirimNotifikasiCutoffKeKasi($laporan, $status, $customCutoffAt, $catatan);
            }

            return $laporan;
        });
    }

    /**
     * Buka akses pengisian laporan periode kapan saja secara instan.
     */
    public function bukaAksesPengisian(Laporan $laporan, ?string $catatan = null, ?User $actor = null, bool $notify = true): Laporan
    {
        return $this->aturCutoffPeriode(
            laporan: $laporan,
            status: 'terbuka',
            customCutoffAt: $laporan->custom_cutoff_at,
            catatan: $catatan ?? 'Akses pengisian dibuka manual oleh Sekretaris Camat.',
            actor: $actor,
            notify: $notify
        );
    }

    /**
     * Kunci / Tutup akses pengisian laporan periode kapan saja secara instan.
     */
    public function tutupAksesPengisian(Laporan $laporan, ?string $catatan = null, ?User $actor = null, bool $notify = true): Laporan
    {
        return $this->aturCutoffPeriode(
            laporan: $laporan,
            status: 'tertutup',
            customCutoffAt: $laporan->custom_cutoff_at,
            catatan: $catatan ?? 'Akses pengisian ditutup / dikunci oleh Sekretaris Camat.',
            actor: $actor,
            notify: $notify
        );
    }

    /**
     * Kembalikan cut-off periode ke mode otomatis terjadwal.
     */
    public function resetCutoffOtomatis(Laporan $laporan, ?User $actor = null): Laporan
    {
        return $this->aturCutoffPeriode(
            laporan: $laporan,
            status: 'otomatis',
            customCutoffAt: null,
            catatan: 'Konfigurasi cut-off dikembalikan ke mode otomatis default.',
            actor: $actor,
            notify: false
        );
    }

    /**
     * Kirim notifikasi database Filament ke seluruh Kasi terkait perubahan cut-off.
     */
    protected function kirimNotifikasiCutoffKeKasi(
        Laporan $laporan,
        string $status,
        ?Carbon $customCutoffAt,
        ?string $catatan
    ): void {
        $kasiUsers = User::where('role', 'kasi')->where('is_active', true)->get();
        if ($kasiUsers->isEmpty()) {
            return;
        }

        $bulanStr = $laporan->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$laporan->tahun}";

        if ($status === 'terbuka') {
            $title = "🔓 Akses Pengisian Dibuka: Periode {$bulanStr}";
            $body = 'Sekretaris Camat telah membuka akses pengisian laporan kinerja periode '.$bulanStr.'. Silakan lengkapi dan ajukan laporan unit Anda.';
            $icon = 'heroicon-o-lock-open';
            $color = 'success';
        } elseif ($status === 'tertutup') {
            $title = "🔒 Akses Pengisian Ditutup: Periode {$bulanStr}";
            $body = 'Pengisian laporan kinerja periode '.$bulanStr.' telah ditutup / dikunci oleh Sekretaris Camat.'.($catatan ? " Alasan: {$catatan}" : '');
            $icon = 'heroicon-o-lock-closed';
            $color = 'danger';
        } else {
            $tglStr = $customCutoffAt ? $customCutoffAt->translatedFormat('d F Y H:i').' WIB' : 'Tanggal 10 Pukul 23:59 WIB';
            $title = "📅 Batas Waktu Cut-Off Disesuaikan: Periode {$bulanStr}";
            $body = "Batas waktu pengisian laporan periode {$bulanStr} disetel menjadi {$tglStr}.".($catatan ? " Keterangan: {$catatan}" : '');
            $icon = 'heroicon-o-calendar-days';
            $color = 'warning';
        }

        foreach ($kasiUsers as $kasi) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->color($color)
                ->actions([
                    NotificationAction::make('bukaLaporan')
                        ->button()
                        ->label('Buka Meja Pelaporan')
                        ->url(route('filament.admin.resources.laporan-details.index')),
                ])
                ->sendToDatabase($kasi);
        }
    }
}
