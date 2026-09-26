<?php

namespace App\Filament\Resources\LaporanKecamatanResource\Pages;

use App\Filament\Resources\LaporanKecamatanResource;
use App\Models\Laporan;
use App\Services\LaporanApprovalService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanKecamatan extends ViewRecord
{
    protected static string $resource = LaporanKecamatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action Ajukan ke Camat (Sekmat)
            Action::make('ajukanKeCamat')
                ->label('Ajukan ke Camat')
                ->icon('heroicon-m-paper-airplane')
                ->color('primary')
                ->visible(function (): bool {
                    $user = auth()->user();
                    if (! $user || (! $user->isAdminKecamatan() && ! $user->isSuperAdmin())) {
                        return false;
                    }

                    return in_array($this->record->status, ['draft', 'menunggu_verifikasi', 'ditolak'], true)
                        && $this->record->isAllMandatoryUnitsApproved();
                })
                ->requiresConfirmation()
                ->modalHeading('Ajukan Laporan Gabungan ke Camat')
                ->modalDescription('Seluruh 7 unit operasional telah disetujui. Apakah Anda yakin ingin mengajukan laporan kinerja kecamatan ke Plt. Camat Malangbong untuk disahkan?')
                ->action(function (LaporanApprovalService $service) {
                    $sekmat = auth()->user();
                    $service->ajukanKeCamat($this->record, $sekmat);

                    Notification::make()
                        ->title('Laporan Diajukan ke Camat')
                        ->body('Laporan kompilasi 7 unit berhasil diajukan ke Camat Malangbong.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'diajukan_oleh']);
                }),

            // Action Sahkan Laporan (Camat)
            Action::make('sahkanLaporan')
                ->label('Sahkan Laporan Kinerja')
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->visible(function (): bool {
                    $user = auth()->user();
                    if (! $user || (! $user->isCamat() && ! $user->isSuperAdmin())) {
                        return false;
                    }

                    return $this->record->status === 'diajukan_ke_camat';
                })
                ->requiresConfirmation()
                ->modalHeading('Pengesahan Resmi Laporan Kinerja')
                ->modalDescription('Dengan ini Anda menyatakan telah menelaah dan mengesahkan Laporan Kinerja Kecamatan Malangbong. Dokumen PDF resmi ber-barcode verifikasi digital akan otomatis di-generate.')
                ->action(function (LaporanApprovalService $service) {
                    $camat = auth()->user();
                    $service->sahkanLaporan($this->record, $camat);

                    Notification::make()
                        ->title('Laporan Kinerja Resmi Disahkan')
                        ->body('Laporan kinerja kecamatan telah disahkan dan dokumen PDF resmi berhasil dibuat.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'disetujui_oleh', 'disetujui_pada', 'dokumen_rekap_pdf_path']);
                }),

            // Action Kembalikan ke Sekmat (Camat)
            Action::make('kembalikanKeSekmat')
                ->label('Kembalikan ke Sekmat')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('danger')
                ->visible(function (): bool {
                    $user = auth()->user();
                    if (! $user || (! $user->isCamat() && ! $user->isSuperAdmin())) {
                        return false;
                    }

                    return $this->record->status === 'diajukan_ke_camat';
                })
                ->form([
                    Forms\Components\Textarea::make('catatan_camat')
                        ->label('Catatan / Arahan Perbaikan Camat (Wajib Diisi)')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, LaporanApprovalService $service) {
                    $camat = auth()->user();
                    $service->kembalikanKeSekmat($this->record, $camat, $data['catatan_camat']);

                    Notification::make()
                        ->title('Laporan Dikembalikan ke Sekmat')
                        ->body('Laporan dikembalikan ke Sekmat beserta catatan arahan perbaikan.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'catatan_camat']);
                }),

            // Action Unduh PDF
            Action::make('unduhPdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('info')
                ->url(fn (): string => route('spko.laporan.pdf', ['laporan' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }
}
