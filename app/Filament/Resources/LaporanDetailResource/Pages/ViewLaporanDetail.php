<?php

namespace App\Filament\Resources\LaporanDetailResource\Pages;

use App\Filament\Resources\LaporanDetailResource;
use App\Models\LaporanDetail;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanDetail extends ViewRecord
{
    protected static string $resource = LaporanDetailResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var LaporanDetail $record */
        $record = $this->getRecord();

        $data['bulan_pelaporan'] = $record->laporan?->bulan_pelaporan?->toDateString();
        $data['unit_organisasi_id'] = $record->unit_organisasi_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->isLocked() || auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminKecamatan()),

            Actions\Action::make('ajukan')
                ->label('Ajukan ke Sekmat')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(function (): bool {
                    /** @var LaporanDetail $record */
                    $record = $this->getRecord();
                    $user = auth()->user();

                    if (! $user) {
                        return false;
                    }

                    if (! in_array($record->status, ['draft', 'ditolak'], true)) {
                        return false;
                    }

                    return $user->isSuperAdmin() || $user->isAdminKecamatan() || $user->unit_organisasi_id === $record->unit_organisasi_id;
                })
                ->requiresConfirmation()
                ->modalHeading('Pengesahan Pengajuan Laporan Kinerja')
                ->modalDescription('Apakah Anda yakin ingin mengajukan laporan kinerja unit ini ke Sekretaris Camat? Formulir akan dikunci sementara untuk proses verifikasi.')
                ->modalSubmitActionLabel('Ya, Ajukan Laporan')
                ->action(function () {
                    /** @var LaporanDetail $record */
                    $record = $this->getRecord();

                    if ($record->indikators()->count() === 0) {
                        Notification::make()
                            ->title('Pengajuan Gagal')
                            ->body('Harap isi minimal 1 indikator rencana aksi sebelum mengajukan laporan.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $isLate = LaporanDetail::isPastCutoff($record->laporan->bulan_pelaporan);
                    if ($isLate && ! $record->hasActiveDispensasi()) {
                        $record->is_late = true;
                    }

                    $record->status = 'diajukan';
                    $record->submitted_at = now();
                    $record->save();

                    // Notifikasi Database ke Admin Kecamatan (Sekmat)
                    $recipients = User::whereIn('role', ['admin_kecamatan', 'superadmin'])
                        ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['admin_kecamatan', 'superadmin']))
                        ->get();

                    Notification::make()
                        ->title('Laporan Kinerja Unit Diajukan')
                        ->icon('heroicon-o-document-check')
                        ->iconColor('success')
                        ->body("Unit {$record->unitOrganisasi->nama_unit} telah mengajukan laporan kinerja periode ".Carbon::parse($record->laporan->bulan_pelaporan)->translatedFormat('F Y').'.')
                        ->actions([
                            NotificationAction::make('review')
                                ->button()
                                ->label('Tinjau Laporan')
                                ->url(LaporanDetailResource::getUrl('view', ['record' => $record])),
                        ])
                        ->sendToDatabase($recipients);

                    Notification::make()
                        ->title('Laporan Berhasil Diajukan')
                        ->body('Laporan kinerja telah dikirimkan ke Sekretaris Camat untuk diverifikasi.')
                        ->success()
                        ->send();

                    $this->redirect(LaporanDetailResource::getUrl('index'));
                }),

            Actions\Action::make('unduhPdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-m-document-arrow-down')
                ->color('info')
                ->url(fn (): string => route('spko.laporan-detail.pdf', ['detail' => $this->getRecord()->id]))
                ->openUrlInNewTab(),
        ];
    }
}
