<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
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

        $laporan = Laporan::whereDate('bulan_pelaporan', $bulanDate->toDateString())->first();
        $laporanId = $laporan ? $laporan->id : 0;

        // Prefetch semua detail + indikators dalam 1-2 query (eliminasi N+1)
        $detailsMap = $laporanId > 0
            ? LaporanDetail::where('laporan_id', $laporanId)
                ->with(['indikators'])
                ->get()
                ->keyBy('unit_organisasi_id')
            : collect();

        // Prefetch Laporan V2 untuk periode ini
        $v2Map = LaporanKinerjaV2::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->get()
            ->keyBy('unit_organisasi_id');

        return $table
            ->heading('Monitoring Kepatuhan 7 Unit Operasional — Periode '.$bulanDate->translatedFormat('F Y'))
            ->description('Matriks kendali progres pelaporan, evaluasi fisik, dan serapan belanja anggaran per seksi')
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

                Tables\Columns\TextColumn::make('status_laporan')
                    ->label('Status Laporan')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap, $v2Map): string {
                        $detail = $detailsMap->get($record->id);
                        if ($detail) {
                            return $detail->status;
                        }

                        $v2 = $v2Map->get($record->id);
                        if ($v2) {
                            return $v2->status;
                        }

                        return 'belum_dibuat';
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'Belum Dibuat',
                        'draft' => 'Draft',
                        'diajukan' => 'Perlu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'danger',
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
                        default => 'heroicon-m-x-circle',
                    }),

                Tables\Columns\TextColumn::make('capaian_kinerja')
                    ->label('Rata-rata Fisik')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap, $v2Map): string {
                        $detail = $detailsMap->get($record->id);

                        if ($detail && $detail->indikators->isNotEmpty()) {
                            $avg = round($detail->indikators->avg('persentase_kinerja') ?? 0, 1);

                            return "{$avg}%";
                        }

                        $v2 = $v2Map->get($record->id);
                        if ($v2) {
                            $filesCount = is_array($v2->bukti_dukung) ? count($v2->bukti_dukung) : 0;

                            return "{$filesCount} Berkas (V2)";
                        }

                        return '-';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, '-') => 'gray',
                        str_contains($state, 'V2') => 'info',
                        (float) str_replace('%', '', $state) >= 90 => 'success',
                        (float) str_replace('%', '', $state) >= 70 => 'info',
                        default => 'warning',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('realisasi_belanja')
                    ->label('Realisasi Belanja')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap): string {
                        $detail = $detailsMap->get($record->id);

                        if (! $detail) {
                            return '-';
                        }

                        $sum = $detail->indikators->sum('realisasi_anggaran') ?? 0;

                        return 'Rp '.number_format($sum, 0, ',', '.');
                    })
                    ->weight('medium')
                    ->color(fn (string $state): string => $state === '-' ? 'gray' : 'primary'),

                Tables\Columns\TextColumn::make('kepatuhan')
                    ->label('Kepatuhan Cut-Off')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap, $v2Map): string {
                        $detail = $detailsMap->get($record->id);

                        if ($detail) {
                            if ($detail->hasActiveDispensasi()) {
                                return 'Dispensasi Aktif';
                            }

                            return $detail->is_late ? 'Terlambat (> Tgl 10)' : 'Tepat Waktu';
                        }

                        $v2 = $v2Map->get($record->id);
                        if ($v2) {
                            return 'Tepat Waktu';
                        }

                        return 'Belum Mengisi';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Dispensasi Aktif' => 'warning',
                        'Terlambat (> Tgl 10)' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Tepat Waktu' => 'heroicon-m-shield-check',
                        'Dispensasi Aktif' => 'heroicon-m-key',
                        'Terlambat (> Tgl 10)' => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-clock',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaVerifikasi')
                    ->label('Telaah')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->visible(fn (UnitOrganisasi $record): bool => $detailsMap->has($record->id) || $v2Map->has($record->id))
                    ->url(function (UnitOrganisasi $record) use ($detailsMap, $v2Map): string {
                        $detail = $detailsMap->get($record->id);
                        if ($detail) {
                            return "/admin/verifikasi-laporan-unit/{$detail->id}";
                        }

                        $v2 = $v2Map->get($record->id);
                        if ($v2) {
                            return "/admin/laporan-kinerja-v2s/{$v2->id}/edit";
                        }

                        return '/admin/verifikasi-laporan-unit';
                    }),
            ])
            ->emptyStateHeading('Tidak Ada Unit Kerja')
            ->emptyStateDescription('Unit organisasi wajib dilaporkan belum dikonfigurasi.')
            ->paginated(false);
    }
}
