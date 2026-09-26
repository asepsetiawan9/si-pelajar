<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalCutoffResource\Pages;
use App\Models\Laporan;
use App\Models\UnitOrganisasi;
use App\Services\LaporanApprovalService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JadwalCutoffResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static ?string $slug = 'jadwal-cutoff';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Pelaporan Kinerja';

    protected static ?string $navigationLabel = 'Jadwal & Batas Cut-Off';

    protected static ?string $modelLabel = 'Jadwal Cut-Off';

    protected static ?string $pluralModelLabel = 'Jadwal & Batas Cut-Off';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['cutoffUpdatedBy', 'laporanDetails'])
            ->latest('bulan_pelaporan');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bulan_pelaporan')
                    ->label('Periode Pelaporan')
                    ->date('F Y')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cutoff_status')
                    ->label('Status Akses')
                    ->badge()
                    ->color(function (Laporan $record): string {
                        if ($record->cutoff_status === 'terbuka') {
                            return 'success';
                        }
                        if ($record->cutoff_status === 'tertutup') {
                            return 'danger';
                        }

                        return $record->isCutoffOpen() ? 'warning' : 'danger';
                    })
                    ->icon(function (Laporan $record): string {
                        if ($record->cutoff_status === 'terbuka') {
                            return 'heroicon-m-lock-open';
                        }
                        if ($record->cutoff_status === 'tertutup') {
                            return 'heroicon-m-lock-closed';
                        }

                        return $record->isCutoffOpen() ? 'heroicon-m-clock' : 'heroicon-m-lock-closed';
                    })
                    ->formatStateUsing(function (string $state, Laporan $record): string {
                        if ($state === 'terbuka') {
                            return 'Dibuka Bebas';
                        }
                        if ($state === 'tertutup') {
                            return 'Ditutup Manual';
                        }

                        return $record->isCutoffOpen() ? 'Otomatis (Aktif)' : 'Berakhir (Terkunci)';
                    })
                    ->description(function (Laporan $record): string {
                        if ($record->cutoff_status === 'terbuka') {
                            return 'Dapat diisi kapan saja oleh semua unit';
                        }
                        if ($record->cutoff_status === 'tertutup') {
                            return 'Akses pengisian dikunci oleh Sekmat';
                        }

                        $effectiveDate = $record->getEffectiveCutoffDate();
                        $now = now();
                        if ($now->isAfter($effectiveDate)) {
                            return 'Batas waktu telah berakhir';
                        }

                        $diffInSeconds = max(0, (int) $now->diffInSeconds($effectiveDate, false));
                        $days = (int) floor($diffInSeconds / 86400);
                        $hours = (int) floor(($diffInSeconds % 86400) / 3600);

                        return $days > 0 ? "Tersisa {$days} hari {$hours} jam lagi" : "Tersisa {$hours} jam lagi";
                    }),

                Tables\Columns\TextColumn::make('effective_cutoff')
                    ->label('Batas Akhir Cut-Off')
                    ->state(fn (Laporan $record): string => $record->getEffectiveCutoffDate()->translatedFormat('d F Y H:i').' WIB')
                    ->weight('medium')
                    ->description(fn (Laporan $record): string => $record->custom_cutoff_at ? '⚡ Tanggal Custom' : 'Default Tanggal 10'),

                Tables\Columns\TextColumn::make('catatan_cutoff')
                    ->label('Catatan / Alasan')
                    ->limit(35)
                    ->placeholder('-')
                    ->tooltip(fn (Laporan $record): ?string => $record->catatan_cutoff),

                Tables\Columns\TextColumn::make('unit_progress')
                    ->label('Partisipasi Unit')
                    ->badge()
                    ->color('info')
                    ->state(function (Laporan $record): string {
                        $mandatoryCount = UnitOrganisasi::where('wajib_dilaporkan', true)->count();
                        $submitted = $record->laporanDetails()->count();

                        return "{$submitted} / {$mandatoryCount} Unit";
                    }),

                Tables\Columns\TextColumn::make('cutoffUpdatedBy.name')
                    ->label('Diatur Oleh')
                    ->placeholder('Sistem Default')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cutoff_updated_at')
                    ->label('Waktu Atur')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->options(fn () => Laporan::distinct()->pluck('tahun', 'tahun')->toArray()),
                Tables\Filters\SelectFilter::make('cutoff_status')
                    ->label('Status Akses')
                    ->options([
                        'otomatis' => 'Otomatis (Jadwal Tanggal)',
                        'terbuka' => 'Dibuka Bebas',
                        'tertutup' => 'Ditutup Manual',
                    ]),
            ])
            ->headerActions([
                Action::make('tambahPeriodeCutoff')
                    ->label('+ Atur Cut-Off Periode')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->modalHeading('Atur Batas Waktu & Cut-Off Periode Pelaporan')
                    ->modalDescription('Pilih periode bulan dan tentukan status akses atau tanggal batas waktu cut-off.')
                    ->modalSubmitActionLabel('Simpan Jadwal Cut-Off')
                    ->form([
                        Forms\Components\DatePicker::make('bulan_pelaporan')
                            ->label('Periode Bulan Pelaporan')
                            ->required()
                            ->native(false)
                            ->displayFormat('F Y')
                            ->default(now()->startOfMonth()->toDateString()),
                        Forms\Components\Select::make('cutoff_status')
                            ->label('Status Akses Pengisian')
                            ->options([
                                'otomatis' => '⏱️ Otomatis (Mengikuti Batas Tanggal)',
                                'terbuka' => '🔓 Buka Akses Pengisian (Bisa diisi kapan saja)',
                                'tertutup' => '🔒 Kunci / Tutup Akses Pengisian (Tidak bisa diisi/diedit)',
                            ])
                            ->default('otomatis')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('custom_cutoff_at')
                            ->label('Batas Tanggal & Waktu Cut-Off (Custom)')
                            ->helperText('Kosongkan untuk menggunakan default (tanggal 10 pukul 23:59 WIB bulan berikutnya).')
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\Textarea::make('catatan_cutoff')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Pembukaan akses periode pelaporan baru.')
                            ->rows(3),
                        Forms\Components\Toggle::make('notify_kasi')
                            ->label('Kirim Notifikasi Database ke Seluruh Pejabat Seksi / Kasubag')
                            ->default(true),
                    ])
                    ->action(function (array $data, LaporanApprovalService $service) {
                        $bulanDate = Carbon::parse($data['bulan_pelaporan'])->startOfMonth()->toDateString();
                        $tahun = (int) Carbon::parse($bulanDate)->year;

                        $laporan = Laporan::firstOrCreate(
                            ['bulan_pelaporan' => $bulanDate],
                            [
                                'tahun' => $tahun,
                                'status' => 'draft',
                            ]
                        );

                        $customAt = ! empty($data['custom_cutoff_at']) ? Carbon::parse($data['custom_cutoff_at']) : null;
                        $service->aturCutoffPeriode(
                            laporan: $laporan,
                            status: $data['cutoff_status'],
                            customCutoffAt: $customAt,
                            catatan: $data['catatan_cutoff'] ?? null,
                            actor: auth()->user(),
                            notify: (bool) ($data['notify_kasi'] ?? true)
                        );

                        Notification::make()
                            ->title('Jadwal Cut-Off Berhasil Disimpan')
                            ->body('Konfigurasi cut-off untuk periode '.Carbon::parse($bulanDate)->translatedFormat('F Y').' telah aktif.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // Quick Action: Buka Akses Kapan Saja
                Action::make('bukaAkses')
                    ->label('Buka Akses')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->visible(fn (Laporan $record): bool => $record->isCutoffClosed())
                    ->requiresConfirmation()
                    ->modalHeading('Buka Akses Pengisian Laporan')
                    ->modalDescription('Apakah Anda yakin ingin membuka akses pengisian periode ini untuk seluruh unit kerja?')
                    ->modalSubmitActionLabel('Ya, Buka Akses')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $service->bukaAksesPengisian($record, 'Akses dibuka oleh Sekretaris Camat.', auth()->user(), true);

                        Notification::make()
                            ->title('Akses Pengisian Berhasil Dibuka')
                            ->body('Seluruh unit kerja kini dapat mengisi dan mengedit laporan periode ini.')
                            ->success()
                            ->send();
                    }),

                // Quick Action: Kunci Akses Kapan Saja
                Action::make('kunciAkses')
                    ->label('Kunci Akses')
                    ->icon('heroicon-m-lock-closed')
                    ->color('danger')
                    ->visible(fn (Laporan $record): bool => $record->isCutoffOpen())
                    ->requiresConfirmation()
                    ->modalHeading('Kunci / Tutup Akses Pengisian Laporan')
                    ->modalDescription('Apakah Anda yakin ingin mengunci akses pengisian laporan periode ini? Seluruh form yang belum diajukan akan menjadi terkunci.')
                    ->modalSubmitActionLabel('Ya, Kunci Akses')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $service->tutupAksesPengisian($record, 'Akses dikunci oleh Sekretaris Camat.', auth()->user(), true);

                        Notification::make()
                            ->title('Akses Pengisian Berhasil Dikunci')
                            ->body('Pengisian laporan periode ini telah ditutup.')
                            ->warning()
                            ->send();
                    }),

                // Modal Action: Ubah Cut-Off
                Action::make('ubahCutoff')
                    ->label('Ubah Cut-Off')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->modalHeading(fn (Laporan $record): string => 'Ubah Cut-Off Periode '.($record->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$record->tahun}"))
                    ->modalDescription('Kustomisasi tanggal batas waktu dan status pengisian.')
                    ->modalSubmitActionLabel('Simpan Perubahan')
                    ->form(fn (Laporan $record): array => [
                        Forms\Components\Select::make('cutoff_status')
                            ->label('Status Akses Pengisian')
                            ->options([
                                'otomatis' => '⏱️ Otomatis (Mengikuti Batas Tanggal)',
                                'terbuka' => '🔓 Buka Akses Pengisian (Bisa diisi kapan saja)',
                                'tertutup' => '🔒 Kunci / Tutup Akses Pengisian (Tidak bisa diisi/diedit)',
                            ])
                            ->default($record->cutoff_status ?? 'otomatis')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('custom_cutoff_at')
                            ->label('Batas Tanggal & Waktu Cut-Off (Custom)')
                            ->helperText('Kosongkan untuk menggunakan default (tanggal 10 pukul 23:59 WIB bulan berikutnya).')
                            ->default($record->custom_cutoff_at ?? $record->getEffectiveCutoffDate())
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\Textarea::make('catatan_cutoff')
                            ->label('Catatan / Alasan')
                            ->placeholder('Contoh: Penyesuaian jadwal cut-off.')
                            ->default($record->catatan_cutoff)
                            ->rows(3),
                        Forms\Components\Toggle::make('notify_kasi')
                            ->label('Kirim Notifikasi Database ke Seluruh Pejabat Seksi / Kasubag')
                            ->default(true),
                    ])
                    ->action(function (Laporan $record, array $data, LaporanApprovalService $service) {
                        $customAt = ! empty($data['custom_cutoff_at']) ? Carbon::parse($data['custom_cutoff_at']) : null;
                        $service->aturCutoffPeriode(
                            laporan: $record,
                            status: $data['cutoff_status'],
                            customCutoffAt: $customAt,
                            catatan: $data['catatan_cutoff'] ?? null,
                            actor: auth()->user(),
                            notify: (bool) ($data['notify_kasi'] ?? true)
                        );

                        Notification::make()
                            ->title('Pengaturan Cut-Off Berhasil Disimpan')
                            ->body('Status cut-off dan batas waktu pengisian telah diperbarui.')
                            ->success()
                            ->send();
                    }),

                // Reset Action: Kembalikan ke Otomatis
                Action::make('resetOtomatis')
                    ->label('Reset Otomatis')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->visible(fn (Laporan $record): bool => $record->cutoff_status !== 'otomatis' || $record->custom_cutoff_at !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan ke Jadwal Standar')
                    ->modalDescription('Apakah Anda yakin ingin mengembalikan cut-off periode ini ke mode otomatis standar (tanggal 10 pukul 23:59 WIB)?')
                    ->modalSubmitActionLabel('Ya, Kembalikan Standar')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $service->resetCutoffOtomatis($record, auth()->user());

                        Notification::make()
                            ->title('Cut-Off Dikembalikan ke Standar')
                            ->body('Mode cut-off telah disetel ke otomatis default tanggal 10.')
                            ->info()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Belum Ada Jadwal Cut-Off')
            ->emptyStateDescription('Klik tombol "+ Atur Cut-Off Periode" untuk menentukan jadwal dan batas cut-off pelaporan.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwalCutoffs::route('/'),
        ];
    }
}
