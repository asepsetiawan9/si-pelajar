<?php

namespace App\Filament\Resources\LaporanKinerjaV2Resource\Pages;

use App\Filament\Resources\LaporanKinerjaV2Resource;
use App\Services\LaporanKinerjaV2Service;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanKinerjaV2 extends ViewRecord
{
    protected static string $resource = LaporanKinerjaV2Resource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isVerifikator = $user?->isAdminKecamatan() || $user?->isSuperAdmin();

        return [
            Actions\EditAction::make()
                ->visible(fn () => $isVerifikator || in_array($this->record->status, ['draft', 'ditolak'])),

            // Aksi Kirim (Author)
            Actions\Action::make('kirim')
                ->label('Kirim Laporan')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kirim Laporan Kinerja')
                ->modalDescription('Kirim berkas laporan ini untuk diverifikasi oleh Sekretaris Camat / Admin?')
                ->visible(fn () => in_array($this->record->status, ['draft', 'ditolak']))
                ->action(function (LaporanKinerjaV2Service $service) {
                    $service->kirimLaporan($this->record, auth()->user());
                    Notification::make()
                        ->title('Laporan Berhasil Dikirim')
                        ->body('Status berubah menjadi Perlu Verifikasi.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'submitted_at']);
                }),

            // Aksi Setujui (Verifikator)
            Actions\Action::make('setujui')
                ->label('Setujui Laporan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $isVerifikator && $this->record->status === 'diajukan')
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan Verifikasi (Opsional)')
                        ->placeholder('Tambahkan catatan jika diperlukan...'),
                ])
                ->action(function (array $data, LaporanKinerjaV2Service $service) {
                    $service->setujuiLaporan($this->record, auth()->user(), $data['catatan'] ?? null);
                    Notification::make()
                        ->title('Laporan Disetujui')
                        ->body("Laporan unit {$this->record->unitOrganisasi?->nama_unit} telah disetujui.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'verified_by', 'verified_at', 'catatan_verifikasi']);
                }),

            // Aksi Kembalikan / Revisi (Verifikator)
            Actions\Action::make('kembalikan')
                ->label('Kembalikan / Revisi')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn () => $isVerifikator && $this->record->status === 'diajukan')
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan Revisi (Wajib Diisi)')
                        ->required()
                        ->placeholder('Tuliskan rincian perbaikan yang diperlukan...'),
                ])
                ->action(function (array $data, LaporanKinerjaV2Service $service) {
                    $service->kembalikanLaporan($this->record, auth()->user(), $data['catatan']);
                    Notification::make()
                        ->title('Laporan Dikembalikan')
                        ->body("Laporan unit {$this->record->unitOrganisasi?->nama_unit} dikembalikan untuk direvisi.")
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'verified_by', 'verified_at', 'catatan_verifikasi']);
                }),
        ];
    }
}
