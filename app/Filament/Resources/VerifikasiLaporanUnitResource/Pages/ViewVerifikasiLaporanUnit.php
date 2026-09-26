<?php

namespace App\Filament\Resources\VerifikasiLaporanUnitResource\Pages;

use App\Filament\Resources\VerifikasiLaporanUnitResource;
use App\Services\LaporanApprovalService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVerifikasiLaporanUnit extends ViewRecord
{
    protected static string $resource = VerifikasiLaporanUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setujui')
                ->label('Setujui Laporan')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['diajukan', 'ditolak'], true))
                ->requiresConfirmation()
                ->modalHeading('Verifikasi & Setujui Laporan Unit')
                ->modalDescription("Apakah Anda yakin ingin menyetujui laporan kinerja dari {$this->record->unitOrganisasi?->nama_unit}?")
                ->action(function (LaporanApprovalService $service) {
                    $sekmat = auth()->user();
                    $service->setujuiLaporanDetail($this->record, $sekmat);

                    Notification::make()
                        ->title('Laporan Unit Disetujui')
                        ->body("Laporan kinerja {$this->record->unitOrganisasi?->nama_unit} berhasil disetujui.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'verified_at', 'verified_by']);
                }),

            Action::make('kembalikan')
                ->label('Kembalikan untuk Revisi')
                ->icon('heroicon-m-arrow-path')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->record->status, ['diajukan', 'disetujui'], true))
                ->form([
                    Forms\Components\Textarea::make('catatan_verifikasi_sekmat')
                        ->label('Catatan / Alasan Pengembalian (Wajib Diisi)')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, LaporanApprovalService $service) {
                    $sekmat = auth()->user();
                    $service->kembalikanLaporanDetail($this->record, $sekmat, $data['catatan_verifikasi_sekmat']);

                    Notification::make()
                        ->title('Laporan Dikembalikan')
                        ->body("Laporan unit {$this->record->unitOrganisasi?->nama_unit} dikembalikan ke Kasi untuk direvisi.")
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'catatan_verifikasi_sekmat']);
                }),

            Action::make('bukaDispensasi')
                ->label('Buka Dispensasi')
                ->icon('heroicon-m-key')
                ->color('warning')
                ->form([
                    Forms\Components\DateTimePicker::make('dispensasi_sampai')
                        ->label('Batas Akhir Dispensasi')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addDays(3)->endOfDay()),
                    Forms\Components\Textarea::make('alasan_dispensasi')
                        ->label('Alasan Dispensasi Keterlambatan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data, LaporanApprovalService $service) {
                    $sekmat = auth()->user();
                    $service->bukaDispensasi($this->record, $sekmat, $data['dispensasi_sampai'], $data['alasan_dispensasi']);

                    Notification::make()
                        ->title('Dispensasi Keterlambatan Dibuka')
                        ->body("Dispensasi pelaporan aktif untuk {$this->record->unitOrganisasi?->nama_unit}.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['is_dispensasi', 'dispensasi_sampai', 'alasan_dispensasi']);
                }),

            Action::make('unduhPdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-m-document-arrow-down')
                ->color('primary')
                ->url(fn (): string => route('spko.laporan-detail.pdf', ['detail' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }
}
