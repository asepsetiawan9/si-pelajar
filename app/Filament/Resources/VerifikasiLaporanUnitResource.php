<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifikasiLaporanUnitResource\Pages;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Services\LaporanApprovalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VerifikasiLaporanUnitResource extends Resource
{
    protected static ?string $model = LaporanDetail::class;

    protected static ?string $slug = 'verifikasi-laporan-unit';

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Pelaporan Kinerja';

    protected static ?string $navigationLabel = 'Verifikasi Laporan Sekmat';

    protected static ?string $modelLabel = 'Verifikasi Laporan Unit';

    protected static ?string $pluralModelLabel = 'Meja Kerja Verifikasi Sekmat';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdminKecamatan() || $user->isSuperAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['laporan', 'unitOrganisasi', 'user', 'verifiedBy', 'indikators.rencanaAksi.sasaranStrategis', 'layanans', 'dokumens'])
            ->latest('submitted_at');
    }

    public static function form(Form $form): Form
    {
        // Meja verifikasi adalah antarmuka reviu & aksi persetujuan
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Laporan & Kepatuhan Waktu')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('laporan.bulan_pelaporan')
                            ->label('Bulan Pelaporan')
                            ->date('F Y')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('unitOrganisasi.nama_unit')
                            ->label('Unit Kerja Operasional')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Pengajuan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'diajukan' => 'warning',
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Pejabat Penginput')
                            ->icon('heroicon-m-user'),
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Waktu Diajukan')
                            ->dateTime('d M Y H:i WIB')
                            ->placeholder('Belum diajukan'),
                        Infolists\Components\IconEntry::make('is_late')
                            ->label('Status Keterlambatan')
                            ->boolean()
                            ->trueIcon('heroicon-o-exclamation-triangle')
                            ->falseIcon('heroicon-o-check-circle')
                            ->trueColor('danger')
                            ->falseColor('success'),
                    ]),

                Infolists\Components\Section::make('Dasar Hukum & Narasi Latar Belakang')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('latar_belakang')
                            ->label('')
                            ->markdown(),
                    ]),

                Infolists\Components\Section::make('Capaian Target Fisik & Realisasi Anggaran Belanja')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('indikators')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('rencanaAksi.uraian_rencana_aksi')
                                    ->label('Rencana Aksi')
                                    ->columnSpan(2)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('target_kinerja')
                                    ->label('Target Kinerja')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('realisasi_kinerja')
                                    ->label('Realisasi Fisik')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('persentase_kinerja')
                                    ->label('Capaian Fisik (%)')
                                    ->suffix('%')
                                    ->badge()
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('predikat_efektivitas')
                                    ->label('Predikat Efektivitas')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'sangat_efektif', 'efektif' => 'success',
                                        'cukup_efektif' => 'warning',
                                        default => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('anggaran_pagu')
                                    ->label('Pagu Anggaran')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('realisasi_anggaran')
                                    ->label('Realisasi Belanja')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('persentase_anggaran')
                                    ->label('Serapan Belanja (%)')
                                    ->suffix('%'),
                                Infolists\Components\TextEntry::make('predikat_efisiensi')
                                    ->label('Predikat Efisiensi')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'sangat_efisien', 'efisien' => 'success',
                                        'cukup_efisien' => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Rincian Pemohon Layanan / Kegiatan (PATEN)')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('layanans')
                            ->label('')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('nama_layanan')
                                    ->label('Nama Layanan / Kegiatan'),
                                Infolists\Components\TextEntry::make('jumlah')
                                    ->label('Jumlah Pemohon')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('keterangan')
                                    ->label('Keterangan')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Evaluasi, Kendala & Catatan Verifikasi')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('keluhan_masyarakat')
                            ->label('Keluhan Masyarakat')
                            ->placeholder('Tidak ada keluhan'),
                        Infolists\Components\TextEntry::make('hambatan')
                            ->label('Hambatan / Kendala')
                            ->placeholder('Tidak ada hambatan'),
                        Infolists\Components\TextEntry::make('simpulan')
                            ->label('Simpulan Unit')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('catatan_verifikasi_sekmat')
                            ->label('Catatan Verifikasi Sekmat Terakhir')
                            ->columnSpanFull()
                            ->placeholder('Belum ada catatan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('laporan.bulan_pelaporan')
                    ->label('Bulan Pelaporan')
                    ->date('F Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('unitOrganisasi.nama_unit')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Penginput')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_late')
                    ->label('Terlambat')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('is_dispensasi')
                    ->label('Dispensasi')
                    ->boolean()
                    ->trueColor('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Diajukan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('verifiedBy.name')
                    ->label('Diverifikasi Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(fn () => Laporan::distinct()->pluck('tahun', 'tahun')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => ! empty($data['value']) ? $query->whereHas('laporan', fn ($q) => $q->where('tahun', $data['value'])) : $query),

                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Filter Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => ! empty($data['value']) ? $query->whereHas('laporan', fn ($q) => $q->whereMonth('bulan_pelaporan', $data['value'])) : $query),

                Tables\Filters\SelectFilter::make('unit_organisasi_id')
                    ->label('Filter Unit')
                    ->relationship('unitOrganisasi', 'nama_unit'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'diajukan' => 'Diajukan (Perlu Verifikasi)',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak (Perlu Revisi)',
                        'draft' => 'Draft',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Telaah'),

                // Action "Setujui"
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (LaporanDetail $record): bool => in_array($record->status, ['diajukan', 'ditolak'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi & Setujui Laporan Unit')
                    ->modalDescription(fn (LaporanDetail $record) => "Apakah Anda yakin ingin menyetujui laporan kinerja dari {$record->unitOrganisasi?->nama_unit}?")
                    ->action(function (LaporanDetail $record, LaporanApprovalService $service) {
                        $sekmat = auth()->user();
                        $service->setujuiLaporanDetail($record, $sekmat);

                        Notification::make()
                            ->title('Laporan Unit Disetujui')
                            ->body("Laporan kinerja {$record->unitOrganisasi?->nama_unit} berhasil diverifikasi dan disetujui.")
                            ->success()
                            ->send();
                    }),

                // Action "Kembalikan / Tolak"
                Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->icon('heroicon-m-arrow-path')
                    ->color('danger')
                    ->visible(fn (LaporanDetail $record): bool => in_array($record->status, ['diajukan', 'disetujui'], true))
                    ->form([
                        Forms\Components\Textarea::make('catatan_verifikasi_sekmat')
                            ->label('Catatan / Alasan Pengembalian (Wajib Diisi)')
                            ->required()
                            ->rows(4)
                            ->placeholder('Contoh: Data realisasi belanja pada kegiatan PATEN belum sinkron dengan BKU bendahara.'),
                    ])
                    ->action(function (LaporanDetail $record, array $data, LaporanApprovalService $service) {
                        $sekmat = auth()->user();
                        $service->kembalikanLaporanDetail($record, $sekmat, $data['catatan_verifikasi_sekmat']);

                        Notification::make()
                            ->title('Laporan Dikembalikan')
                            ->body("Laporan unit {$record->unitOrganisasi?->nama_unit} dikembalikan ke Kasi terkait untuk direvisi.")
                            ->warning()
                            ->send();
                    }),

                // Action "Buka Dispensasi"
                Action::make('bukaDispensasi')
                    ->label('Dispensasi')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\DateTimePicker::make('dispensasi_sampai')
                            ->label('Batas Akhir Dispensasi Pengisian')
                            ->required()
                            ->minDate(now())
                            ->default(now()->addDays(3)->endOfDay()),
                        Forms\Components\Textarea::make('alasan_dispensasi')
                            ->label('Alasan Dispensasi Keterlambatan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Adanya transisi pejabat Kepala Seksi dan gangguan jaringan internet di kantor kecamatan.'),
                    ])
                    ->action(function (LaporanDetail $record, array $data, LaporanApprovalService $service) {
                        $sekmat = auth()->user();
                        $service->bukaDispensasi($record, $sekmat, $data['dispensasi_sampai'], $data['alasan_dispensasi']);

                        Notification::make()
                            ->title('Dispensasi Keterlambatan Dibuka')
                            ->body("Dispensasi pelaporan aktif untuk {$record->unitOrganisasi?->nama_unit}.")
                            ->success()
                            ->send();
                    }),

                // Action "Unduh PDF"
                Action::make('unduhPdf')
                    ->label('PDF')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('primary')
                    ->url(fn (LaporanDetail $record) => route('spko.laporan-detail.pdf', ['detail' => $record->id]))
                    ->openUrlInNewTab(),

                // Action "Unduh Excel"
                Action::make('unduhExcel')
                    ->label('Excel')
                    ->icon('heroicon-m-table-cells')
                    ->color('success')
                    ->url(fn (LaporanDetail $record) => route('spko.laporan-detail.excel', ['detail' => $record->id]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasiLaporanUnits::route('/'),
            'view' => Pages\ViewVerifikasiLaporanUnit::route('/{record}'),
        ];
    }
}
