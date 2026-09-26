<?php

namespace App\Filament\Widgets;

use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class SekmatUnitStatusTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdminKecamatan() || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        $tahun = (int) ($this->filters['tahun'] ?? now()->year);
        $bulan = (int) ($this->filters['bulan'] ?? now()->month);
        $bulanDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();

        // Prefetch Laporan V2 untuk periode ini
        $v2Map = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get()
            ->keyBy('unit_organisasi_id');

        return $table
            ->heading('Monitoring Laporan Kinerja 7 Unit Operasional — Periode '.$bulanDate->translatedFormat('F Y'))
            ->description('Matriks kendali agenda pelaporan, status verifikasi, dan kelengkapan bukti dukung fisik per seksi/subbag')
            ->query(
                UnitOrganisasi::query()
                    ->where('wajib_dilaporkan', true)
                    ->orderBy('urutan')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_unit')
                    ->label('Unit Organisasi')
                    ->description(fn (UnitOrganisasi $record): string => "Kode: {$record->kode_unit}")
                    ->icon(fn (UnitOrganisasi $record): string => str_contains($record->kode_unit, 'SUBBAG') ? 'heroicon-m-folder' : 'heroicon-m-building-office-2')
                    ->iconColor('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('agenda_pelaporan')
                    ->label('Agenda / Judul Pelaporan')
                    ->state(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);

                        return $v2 ? $v2->judul_pelaporan : 'Belum membuat laporan kinerja';
                    })
                    ->wrap()
                    ->color(fn (string $state): string => $state === 'Belum membuat laporan kinerja' ? 'gray' : 'default')
                    ->weight(fn (string $state): string => $state === 'Belum membuat laporan kinerja' ? 'normal' : 'medium'),

                Tables\Columns\TextColumn::make('pejabat_pengisi')
                    ->label('Pejabat Pengisi')
                    ->state(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);

                        return $v2 ? $v2->nama_pejabat : '-';
                    })
                    ->description(function (UnitOrganisasi $record) use ($v2Map): ?string {
                        $v2 = $v2Map->get($record->id);

                        return $v2 ? "NIP: {$v2->nip_pejabat}" : null;
                    }),

                Tables\Columns\TextColumn::make('status_laporan')
                    ->label('Status')
                    ->state(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);

                        return $v2 ? $v2->status : 'belum_dibuat';
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'Belum Lapor',
                        'draft' => 'Draft',
                        'diajukan' => 'Perlu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'gray',
                        'draft' => 'warning',
                        'diajukan' => 'info',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'disetujui' => 'heroicon-m-check-badge',
                        'diajukan' => 'heroicon-m-clock',
                        'ditolak' => 'heroicon-m-exclamation-triangle',
                        'draft' => 'heroicon-m-pencil-square',
                        default => 'heroicon-m-x-circle',
                    }),

                Tables\Columns\TextColumn::make('bukti_dukung')
                    ->label('Bukti Dukung')
                    ->state(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);
                        if (! $v2) {
                            return 'Nihil';
                        }
                        $count = $v2->bukti_dukung_count;

                        return $count > 0 ? "{$count} Berkas" : 'Tanpa Berkas';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Berkas') => 'primary',
                        default => 'gray',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Waktu Pelaporan')
                    ->state(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);
                        if (! $v2) {
                            return '-';
                        }

                        return $v2->tanggal_pelaporan ? $v2->tanggal_pelaporan->format('d/m/Y') : '-';
                    })
                    ->description(function (UnitOrganisasi $record) use ($v2Map): ?string {
                        $v2 = $v2Map->get($record->id);
                        if (! $v2 || ! $v2->verified_at) {
                            return null;
                        }

                        return 'Verif: '.$v2->verified_at->format('d/m/Y');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('telaah')
                    ->label('Telaah')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->visible(fn (UnitOrganisasi $record): bool => $v2Map->has($record->id))
                    ->url(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);

                        return "/admin/laporan-kinerja-v2s/{$v2->id}";
                    }),

                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-m-printer')
                    ->button()
                    ->size('xs')
                    ->color('success')
                    ->visible(fn (UnitOrganisasi $record): bool => $v2Map->has($record->id))
                    ->url(function (UnitOrganisasi $record) use ($v2Map): string {
                        $v2 = $v2Map->get($record->id);

                        return route('spko.laporan-v2.pdf', $v2);
                    })
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Tidak Ada Unit Kerja')
            ->emptyStateDescription('Unit organisasi belum dikonfigurasi.')
            ->paginated(false);
    }
}
