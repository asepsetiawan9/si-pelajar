<?php

namespace App\Filament\Widgets;

use App\Models\LaporanKinerjaV2;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class KasiRecentReportsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->isKasi();
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $unitId = $user?->unit_organisasi_id ?? 0;

        return $table
            ->heading('Riwayat Pelaporan Kinerja Unit Anda (Versi 2)')
            ->description('Daftar berkas laporan kinerja unit kerja yang telah disusun beserta catatan telaah Sekmat')
            ->query(
                LaporanKinerjaV2::query()
                    ->where('unit_organisasi_id', $unitId)
                    ->latest('tanggal_pelaporan')
            )
            ->columns([
                Tables\Columns\TextColumn::make('judul_pelaporan')
                    ->label('Judul Pelaporan')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-document-text')
                    ->iconColor('primary')
                    ->description(fn (LaporanKinerjaV2 $record): string => "Tanggal: {$record->tanggal_pelaporan?->format('d/m/Y')} • Periode: {$record->periode_bulan}/{$record->periode_tahun}"),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'diajukan' => 'Perlu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'diajukan' => 'info',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'disetujui' => 'heroicon-m-check-badge',
                        'diajukan' => 'heroicon-m-arrow-path',
                        'ditolak' => 'heroicon-m-exclamation-triangle',
                        'draft' => 'heroicon-m-pencil-square',
                        default => 'heroicon-m-clock',
                    }),

                Tables\Columns\TextColumn::make('bukti_dukung')
                    ->label('Bukti Dukung')
                    ->state(fn (LaporanKinerjaV2 $record): string => (is_array($record->bukti_dukung) ? count($record->bukti_dukung) : 0).' Berkas')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('catatan_verifikasi')
                    ->label('Catatan Telaah Sekmat')
                    ->placeholder('Belum ada catatan')
                    ->limit(45)
                    ->tooltip(fn (LaporanKinerjaV2 $record): ?string => $record->catatan_verifikasi)
                    ->color(fn (LaporanKinerjaV2 $record): string => $record->isDitolak() ? 'danger' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaLaporan')
                    ->label(fn (LaporanKinerjaV2 $record): string => $record->isDraft() || $record->isDitolak() ? 'Lanjutkan' : 'Buka')
                    ->icon(fn (LaporanKinerjaV2 $record): string => $record->isDraft() || $record->isDitolak() ? 'heroicon-m-pencil-square' : 'heroicon-m-eye')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->url(fn (LaporanKinerjaV2 $record): string => "/admin/laporan-kinerja-v2s/{$record->id}/edit"),
            ])
            ->emptyStateHeading('Belum Ada Laporan Unit')
            ->emptyStateDescription('Mulai buat laporan kinerja unit Anda dengan menekan tombol "+ Buat Laporan (V2)" di atas.')
            ->emptyStateIcon('heroicon-o-document-plus')
            ->paginated(false);
    }
}
