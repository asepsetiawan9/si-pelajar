<?php

namespace App\Filament\Resources\LaporanKinerjaV2Resource\Pages;

use App\Filament\Resources\LaporanKinerjaV2Resource;
use App\Services\LaporanKinerjaV2Service;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLaporanKinerjaV2 extends EditRecord
{
    protected static string $resource = LaporanKinerjaV2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('kirim')
                ->label('Kirim Laporan')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kirim Laporan Kinerja')
                ->modalDescription('Apakah Anda yakin ingin mengirim laporan ini untuk diverifikasi?')
                ->visible(fn () => in_array($this->record->status, ['draft', 'ditolak']))
                ->action(function (LaporanKinerjaV2Service $service) {
                    $service->kirimLaporan($this->record, auth()->user());
                    Notification::make()
                        ->title('Laporan Berhasil Dikirim')
                        ->body('Status berubah menjadi Perlu Verifikasi.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
