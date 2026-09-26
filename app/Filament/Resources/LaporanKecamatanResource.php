<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKecamatanResource\Pages;
use App\Models\Laporan;
use App\Models\UnitOrganisasi;
use App\Services\LaporanApprovalService;
use Carbon\Carbon;
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

class LaporanKecamatanResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static ?string $slug = 'laporan-kecamatan';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Pelaporan Kinerja';

    protected static ?string $navigationLabel = 'Laporan Kompilasi Kecamatan';

    protected static ?string $modelLabel = 'Laporan Kompilasi Kecamatan';

    protected static ?string $pluralModelLabel = 'Kompilasi Laporan Kecamatan';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdminKecamatan() || $user->isCamat() || $user->isSuperAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'diajukanOleh',
                'disetujuiOleh',
                'laporanDetails.unitOrganisasi',
                'laporanDetails.user',
                'laporanDetails.verifiedBy',
                'laporanDetails.indikators',
            ])
            ->latest('bulan_pelaporan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('bulan_pelaporan')
                    ->label('Bulan Pelaporan')
                    ->required()
                    ->native(false)
                    ->displayFormat('F Y'),
                Forms\Components\TextInput::make('tahun')
                    ->label('Tahun')
                    ->required()
                    ->numeric()
                    ->default(now()->year),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Umum & Status Pengesahan Kecamatan')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('bulan_pelaporan')
                            ->label('Periode Pelaporan')
                            ->date('F Y')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('tahun')
                            ->label('Tahun Anggaran'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Laporan Kompilasi')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'menunggu_verifikasi' => 'info',
                                'diajukan_ke_camat' => 'warning',
                                'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'menunggu_verifikasi' => 'Menunggu Verifikasi Sekmat',
                                'diajukan_ke_camat' => 'Menunggu Pengesahan Camat',
                                'disetujui' => 'Resmi Disahkan Camat',
                                'ditolak' => 'Dikembalikan ke Sekmat',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('diajukanOleh.name')
                            ->label('Diajukan Oleh (Sekmat)')
                            ->placeholder('Belum diajukan'),
                        Infolists\Components\TextEntry::make('disetujuiOleh.name')
                            ->label('Disahkan Oleh (Camat)')
                            ->placeholder('Belum disahkan'),
                        Infolists\Components\TextEntry::make('disetujui_pada')
                            ->label('Tanggal Pengesahan Camat')
                            ->dateTime('d F Y H:i WIB')
                            ->placeholder('Belum disahkan'),
                        Infolists\Components\TextEntry::make('catatan_camat')
                            ->label('Catatan Penolakan Camat')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan penolakan')
                            ->color('danger'),
                    ]),

                Infolists\Components\Section::make('Konfigurasi Batas Waktu & Cut-Off Pengisian')
                    ->description('Parameter kendali akses pengisian laporan kinerja untuk seluruh unit kerja.')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('cutoff_status')
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
                            ->formatStateUsing(function (string $state, Laporan $record): string {
                                if ($state === 'terbuka') {
                                    return 'Dibuka Bebas (Manual)';
                                }
                                if ($state === 'tertutup') {
                                    return 'Ditutup / Dikunci Manual';
                                }

                                return $record->isCutoffOpen() ? 'Otomatis (Aktif)' : 'Otomatis (Berakhir)';
                            }),
                        Infolists\Components\TextEntry::make('effective_cutoff')
                            ->label('Batas Waktu Efektif')
                            ->state(fn (Laporan $record): string => $record->getEffectiveCutoffDate()->translatedFormat('d F Y H:i').' WIB')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('cutoffUpdatedBy.name')
                            ->label('Diatur Oleh')
                            ->placeholder('Sistem Default'),
                        Infolists\Components\TextEntry::make('cutoff_updated_at')
                            ->label('Waktu Pengaturan')
                            ->dateTime('d/m/Y H:i WIB')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('catatan_cutoff')
                            ->label('Catatan / Alasan Cut-Off')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan khusus')
                            ->color('info'),
                    ]),

                Infolists\Components\Section::make('Kepatuhan & Status 7 Unit Organisasi Operasional')
                    ->description('Seluruh 7 unit wajib berstatus Disetujui sebelum diajukan ke Camat.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('laporanDetails')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('unitOrganisasi.nama_unit')
                                    ->label('Unit Kerja')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Penginput'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status Unit')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'diajukan' => 'warning',
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('verified_at')
                                    ->label('Waktu Disetujui Sekmat')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bulan_pelaporan')
                    ->label('Bulan Pelaporan')
                    ->date('F Y')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                // Kolom Progres 7 Unit Operasional dengan Visual Progress Bar
                Tables\Columns\TextColumn::make('unit_progress')
                    ->label('Progres 7 Unit')
                    ->html()
                    ->state(function (Laporan $record): string {
                        $mandatoryTotal = UnitOrganisasi::where('wajib_dilaporkan', true)->count();
                        $approvedCount = $record->laporanDetails()
                            ->whereHas('unitOrganisasi', fn ($q) => $q->where('wajib_dilaporkan', true))
                            ->where('status', 'disetujui')
                            ->count();
                        $percent = $mandatoryTotal > 0 ? (int) round(($approvedCount / $mandatoryTotal) * 100) : 0;
                        $colorClass = $percent >= 100 ? 'bg-emerald-500' : 'bg-amber-500';
                        $badgeBg = $percent >= 100 ? 'background-color: #ecfdf5; color: #047857;' : 'background-color: #fffbeb; color: #b45309;';

                        return <<<HTML
                        <div style="min-width: 140px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 11px; font-weight: 600;">
                                <span style="display: inline-block; padding: 2px 6px; border-radius: 9999px; {$badgeBg}">{$approvedCount}/{$mandatoryTotal} Disetujui</span>
                                <span style="color: #6b7280;">{$percent}%</span>
                            </div>
                            <div style="width: 100%; height: 6px; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden;">
                                <div class="{$colorClass}" style="width: {$percent}%; height: 100%; border-radius: 9999px;"></div>
                            </div>
                        </div>
                        HTML;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Laporan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'menunggu_verifikasi' => 'info',
                        'diajukan_ke_camat' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'menunggu_verifikasi' => 'Verifikasi Unit',
                        'diajukan_ke_camat' => 'Menunggu Camat',
                        'disetujui' => 'Disahkan Camat',
                        'ditolak' => 'Ditolak Camat',
                        default => $state,
                    }),

                // Kolom Status Cut-Off & Batas Waktu Custom
                Tables\Columns\TextColumn::make('cutoff_status')
                    ->label('Status Cut-Off')
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

                        $effectiveDate = $record->getEffectiveCutoffDate();
                        $isOpen = $record->isCutoffOpen();

                        return $isOpen
                            ? 'Otomatis: s.d '.$effectiveDate->translatedFormat('d M H:i')
                            : 'Berakhir (Terkunci)';
                    })
                    ->description(function (Laporan $record): ?string {
                        if ($record->custom_cutoff_at) {
                            return 'Batas Custom: '.$record->custom_cutoff_at->translatedFormat('d M Y H:i');
                        }

                        return 'Default: Tgl 10';
                    }),

                Tables\Columns\TextColumn::make('diajukanOleh.name')
                    ->label('Diajukan Oleh')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('disetujuiOleh.name')
                    ->label('Disahkan Camat')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('disetujui_pada')
                    ->label('Waktu Sah')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->options(fn () => Laporan::distinct()->pluck('tahun', 'tahun')->toArray()),
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan Pelaporan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => ! empty($data['value']) ? $query->whereMonth('bulan_pelaporan', $data['value']) : $query),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'diajukan_ke_camat' => 'Menunggu Pengesahan Camat',
                        'disetujui' => 'Resmi Disahkan Camat',
                        'ditolak' => 'Ditolak Camat',
                        'draft' => 'Draft',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Telaah'),

                // Action "Ajukan ke Camat" (Sekmat & Superadmin)
                Action::make('ajukanKeCamat')
                    ->label('Ajukan ke Camat')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->visible(function (Laporan $record): bool {
                        $user = auth()->user();
                        if (! $user || (! $user->isAdminKecamatan() && ! $user->isSuperAdmin())) {
                            return false;
                        }

                        return in_array($record->status, ['draft', 'menunggu_verifikasi', 'ditolak'], true)
                            && $record->isAllMandatoryUnitsApproved();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Laporan Gabungan ke Camat')
                    ->modalDescription('Seluruh 7 unit operasional telah disetujui. Apakah Anda yakin ingin mengajukan laporan kinerja kecamatan ke Plt. Camat Malangbong untuk disahkan?')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $sekmat = auth()->user();
                        $service->ajukanKeCamat($record, $sekmat);

                        Notification::make()
                            ->title('Laporan Diajukan ke Camat')
                            ->body('Laporan kompilasi 7 unit berhasil diajukan ke Camat Malangbong.')
                            ->success()
                            ->send();
                    }),

                // Action "Sahkan Laporan Kinerja" (Camat & Superadmin)
                Action::make('sahkanLaporan')
                    ->label('Sahkan Laporan')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(function (Laporan $record): bool {
                        $user = auth()->user();
                        if (! $user || (! $user->isCamat() && ! $user->isSuperAdmin())) {
                            return false;
                        }

                        return $record->status === 'diajukan_ke_camat';
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Pengesahan Resmi Laporan Kinerja')
                    ->modalDescription('Dengan ini Anda menyatakan telah menelaah dan mengesahkan Laporan Kinerja Kecamatan Malangbong. Dokumen PDF resmi ber-barcode verifikasi digital akan otomatis di-generate.')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $camat = auth()->user();
                        $service->sahkanLaporan($record, $camat);

                        Notification::make()
                            ->title('Laporan Kinerja Resmi Disahkan')
                            ->body('Laporan kinerja kecamatan telah disahkan dan dokumen PDF resmi berhasil dibuat.')
                            ->success()
                            ->send();
                    }),

                // Action "Kembalikan ke Sekmat" (Camat & Superadmin)
                Action::make('kembalikanKeSekmat')
                    ->label('Kembalikan')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->visible(function (Laporan $record): bool {
                        $user = auth()->user();
                        if (! $user || (! $user->isCamat() && ! $user->isSuperAdmin())) {
                            return false;
                        }

                        return $record->status === 'diajukan_ke_camat';
                    })
                    ->form([
                        Forms\Components\Textarea::make('catatan_camat')
                            ->label('Catatan / Arahan Perbaikan Camat (Wajib Diisi)')
                            ->required()
                            ->rows(4)
                            ->placeholder('Contoh: Mohon seksi PMD mengonfirmasi kembali angka realisasi serapan dana transfer desa sebelum finalisasi.'),
                    ])
                    ->action(function (Laporan $record, array $data, LaporanApprovalService $service) {
                        $camat = auth()->user();
                        $service->kembalikanKeSekmat($record, $camat, $data['catatan_camat']);

                        Notification::make()
                            ->title('Laporan Dikembalikan ke Sekmat')
                            ->body('Laporan dikembalikan ke Sekmat beserta catatan arahan perbaikan.')
                            ->warning()
                            ->send();
                    }),

                // Action "Pengaturan Cut-Off" (Sekmat & Superadmin)
                Action::make('aturCutoff')
                    ->label('Atur Cut-Off')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->isAdminKecamatan() || auth()->user()?->isSuperAdmin())
                    ->modalHeading(fn (Laporan $record): string => 'Pengaturan Cut-Off: Periode '.($record->bulan_pelaporan?->translatedFormat('F Y') ?? "Tahun {$record->tahun}"))
                    ->modalDescription('Atur batas waktu pengisian, buka atau tutup akses kapan saja secara fleksibel.')
                    ->modalSubmitActionLabel('Simpan Pengaturan Cut-Off')
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
                            ->placeholder('Contoh: Perpanjangan pengisian hingga tanggal 15 karena rekonsiliasi data.')
                            ->default($record->catatan_cutoff)
                            ->rows(3),
                        Forms\Components\Toggle::make('notify_kasi')
                            ->label('Kirim Notifikasi Database ke Seluruh Kasi / Kasubag')
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

                // Quick Action "Buka Akses" (Jika saat ini tertutup)
                Action::make('bukaAksesCepat')
                    ->label('Buka Akses')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->visible(fn (Laporan $record): bool => (auth()->user()?->isAdminKecamatan() || auth()->user()?->isSuperAdmin()) && $record->isCutoffClosed())
                    ->requiresConfirmation()
                    ->modalHeading('Buka Akses Pengisian Laporan')
                    ->modalDescription('Apakah Anda yakin ingin membuka akses pengisian laporan periode ini untuk seluruh unit kerja?')
                    ->modalSubmitActionLabel('Ya, Buka Akses')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $service->bukaAksesPengisian($record, 'Akses dibuka cepat oleh Sekretaris Camat.', auth()->user(), true);

                        Notification::make()
                            ->title('Akses Pengisian Berhasil Dibuka')
                            ->body('Seluruh unit kerja kini dapat mengisi dan mengedit laporan periode ini.')
                            ->success()
                            ->send();
                    }),

                // Quick Action "Kunci Akses" (Jika saat ini terbuka)
                Action::make('kunciAksesCepat')
                    ->label('Kunci Akses')
                    ->icon('heroicon-m-lock-closed')
                    ->color('danger')
                    ->visible(fn (Laporan $record): bool => (auth()->user()?->isAdminKecamatan() || auth()->user()?->isSuperAdmin()) && $record->isCutoffOpen())
                    ->requiresConfirmation()
                    ->modalHeading('Kunci / Tutup Akses Pengisian Laporan')
                    ->modalDescription('Apakah Anda yakin ingin mengunci akses pengisian laporan periode ini? Seluruh form yang belum diajukan akan menjadi terkunci.')
                    ->modalSubmitActionLabel('Ya, Kunci Akses')
                    ->action(function (Laporan $record, LaporanApprovalService $service) {
                        $service->tutupAksesPengisian($record, 'Akses dikunci cepat oleh Sekretaris Camat.', auth()->user(), true);

                        Notification::make()
                            ->title('Akses Pengisian Berhasil Dikunci')
                            ->body('Pengisian laporan periode ini telah ditutup.')
                            ->warning()
                            ->send();
                    }),

                // Action "Unduh PDF Rekap Resmi"
                Action::make('unduhPdf')
                    ->label('PDF Resmi')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->url(fn (Laporan $record) => route('spko.laporan.pdf', ['laporan' => $record->id]))
                    ->openUrlInNewTab(),

                // Action "Unduh Excel Rekap"
                Action::make('unduhExcel')
                    ->label('Excel (XLSX)')
                    ->icon('heroicon-m-table-cells')
                    ->color('success')
                    ->url(fn (Laporan $record) => route('spko.laporan.excel', ['laporan' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Belum Ada Kompilasi Laporan Kecamatan')
            ->emptyStateDescription('Draf laporan kecamatan akan otomatis terbuat saat unit organisasi pertama kali mengisi laporan bulanan.')
            ->emptyStateIcon('heroicon-o-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKecamatans::route('/'),
            'view' => Pages\ViewLaporanKecamatan::route('/{record}'),
        ];
    }
}
