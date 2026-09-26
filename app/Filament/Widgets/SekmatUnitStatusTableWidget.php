<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
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

        return $table
            ->heading('Monitoring Kepatuhan 7 Unit Operasional — Periode '.$bulanDate->translatedFormat('F Y'))
            ->query(
                UnitOrganisasi::query()
                    ->where('wajib_dilaporkan', true)
                    ->orderBy('urutan')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_unit')
                    ->label('Unit Organisasi (Seksi / Subbag)')
                    ->description(fn (UnitOrganisasi $record): string => "Kode: {$record->kode_unit}")
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status_laporan')
                    ->label('Status Laporan')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap): string {
                        $detail = $detailsMap->get($record->id);

                        return $detail ? $detail->status : 'belum_dibuat';
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'Belum Dibuat',
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui Sekmat',
                        'ditolak' => 'Dikembalikan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'belum_dibuat' => 'danger',
                        'draft' => 'warning',
                        'diajukan' => 'info',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('capaian_kinerja')
                    ->label('Rata-rata Fisik')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap): string {
                        $detail = $detailsMap->get($record->id);

                        if (! $detail || $detail->indikators->isEmpty()) {
                            return '-';
                        }

                        $avg = round($detail->indikators->avg('persentase_kinerja') ?? 0, 1);

                        return "{$avg}%";
                    })
                    ->badge()
                    ->color('info')
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
                    }),

                Tables\Columns\TextColumn::make('kepatuhan')
                    ->label('Kepatuhan Cut-Off')
                    ->state(function (UnitOrganisasi $record) use ($detailsMap): string {
                        $detail = $detailsMap->get($record->id);

                        if (! $detail) {
                            return 'Belum Mengisi';
                        }

                        if ($detail->hasActiveDispensasi()) {
                            return 'Dispensasi Aktif';
                        }

                        return $detail->is_late ? 'Terlambat (> Tgl 10)' : 'Tepat Waktu';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Dispensasi Aktif' => 'warning',
                        'Terlambat (> Tgl 10)' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('bukaVerifikasi')
                    ->label('Telaah')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->visible(fn (UnitOrganisasi $record): bool => $detailsMap->has($record->id))
                    ->url(function (UnitOrganisasi $record) use ($detailsMap): string {
                        $detail = $detailsMap->get($record->id);

                        return $detail ? "/admin/verifikasi-laporan-unit/{$detail->id}" : '/admin/verifikasi-laporan-unit';
                    }),
            ])
            ->paginated(false);
    }
}
