<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKinerjaV2Resource\Pages;
use App\Models\LaporanKinerjaV2;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\LaporanKinerjaV2Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class LaporanKinerjaV2Resource extends Resource
{
    protected static ?string $model = LaporanKinerjaV2::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Pelaporan Kinerja';

    protected static ?string $navigationLabel = 'Laporan Kinerja Unit';

    protected static ?string $modelLabel = 'Laporan Kinerja Unit';

    protected static ?string $pluralModelLabel = 'Laporan Kinerja Unit';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['unitOrganisasi', 'user', 'verifier']);

        $user = auth()->user();

        // Row-Level Security: Kasi hanya melihat laporan dari unit kerjanya sendiri
        if ($user && $user->isKasi()) {
            $query->where('unit_organisasi_id', $user->unit_organisasi_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isKasi = $user?->isKasi();
        $isAdmin = $user?->isSuperAdmin() || $user?->isAdminKecamatan();

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Section 1: Buat Pelaporan
                        Forms\Components\Section::make('Informasi Pelaporan')
                            ->description('Silakan lengkapi judul dan periode pelaporan kinerja unit')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\TextInput::make('judul_pelaporan')
                                    ->label('Buat Pelaporan / Judul Laporan')
                                    ->placeholder('Contoh: Laporan Kinerja Seksi Pelayanan Bulan September 2026')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),

                                Forms\Components\DatePicker::make('tanggal_pelaporan')
                                    ->label('Tanggal Pelaporan')
                                    ->default(now()->toDateString())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),

                                Forms\Components\Select::make('periode_bulan')
                                    ->label('Bulan Pelaporan')
                                    ->options([
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                    ])
                                    ->default(now()->month)
                                    ->required()
                                    ->native(false)
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),

                                Forms\Components\TextInput::make('periode_tahun')
                                    ->label('Tahun')
                                    ->default(now()->year)
                                    ->numeric()
                                    ->required()
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),
                            ])
                            ->columns(3),

                        // Section 2: Unit Organisasi & Pejabat Pengisi
                        Forms\Components\Section::make('Unit Organisasi & Pejabat Pengisi')
                            ->description('Data pejabat pengisi (Nama, NIP, Jabatan) lengkap')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Pilih Pejabat Pengisi')
                                    ->options(function () {
                                        return User::query()
                                            ->where('is_active', true)
                                            ->with('unitOrganisasi')
                                            ->get()
                                            ->mapWithKeys(function ($u) {
                                                $unit = $u->unitOrganisasi?->nama_unit ?? 'Non-Unit';

                                                return [$u->id => "{$u->name} ({$u->jabatan}) - {$unit}"];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->visible($isAdmin)
                                    ->default($user?->id)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (! $state) {
                                            return;
                                        }
                                        $selectedUser = User::with('unitOrganisasi')->find($state);
                                        if ($selectedUser) {
                                            $set('nama_pejabat', $selectedUser->name);
                                            $set('nip_pejabat', $selectedUser->nip ?? '-');
                                            $set('jabatan_pejabat', $selectedUser->jabatan ?? '-');
                                            if ($selectedUser->unit_organisasi_id) {
                                                $set('unit_organisasi_id', $selectedUser->unit_organisasi_id);
                                            }
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('unit_organisasi_id')
                                    ->label('Unit Organisasi')
                                    ->options(UnitOrganisasi::query()->pluck('nama_unit', 'id'))
                                    ->default($user?->unit_organisasi_id)
                                    ->disabled($isKasi)
                                    ->dehydrated()
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('nama_pejabat')
                                    ->label('Nama Lengkap Pejabat')
                                    ->default($user?->name)
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),

                                Forms\Components\TextInput::make('nip_pejabat')
                                    ->label('NIP Pejabat')
                                    ->default($user?->nip ?? '-')
                                    ->required()
                                    ->maxLength(50)
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),

                                Forms\Components\TextInput::make('jabatan_pejabat')
                                    ->label('Jabatan Resmi')
                                    ->default($user?->jabatan ?? '-')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),
                            ])
                            ->columns(3),

                        // Section 3: Upload Bukti Dukung
                        Forms\Components\Section::make('Upload Bukti Dukung')
                            ->description('Unggah berkas bukti dukung kinerja (PDF, DOCX, XLSX, JPG, PNG, atau ZIP)')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Forms\Components\FileUpload::make('bukti_dukung')
                                    ->label('Berkas Bukti Dukung')
                                    ->disk('public')
                                    ->directory('bukti-dukung-v2')
                                    ->multiple()
                                    ->reorderable()
                                    ->openable()
                                    ->downloadable()
                                    ->previewable()
                                    ->maxSize(20480) // 20 MB
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'image/jpeg',
                                        'image/png',
                                        'application/zip',
                                        'application/x-zip-compressed',
                                    ])
                                    ->helperText('Dapat mengunggah beberapa berkas sekaligus. Maksimal 20MB per berkas.')
                                    ->columnSpanFull()
                                    ->disabled(fn (?Model $record) => static::isFormLockedForUser($record, $user)),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // Section 4: Sidebar Status & Catatan
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status Pelaporan')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Placeholder::make('status_badge')
                                    ->label('Status Saat Ini')
                                    ->content(function (?Model $record) {
                                        if (! $record) {
                                            return new HtmlString('<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">Draft (Baru)</span>');
                                        }

                                        $colors = [
                                            'draft' => 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-800 dark:text-gray-200',
                                            'diajukan' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-200',
                                            'disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-200',
                                            'ditolak' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/40 dark:text-rose-200',
                                        ];

                                        $labels = [
                                            'draft' => 'Draft (Belum Dikirim)',
                                            'diajukan' => 'Perlu Verifikasi',
                                            'disetujui' => 'Disetujui / Terverifikasi',
                                            'ditolak' => 'Perlu Revisi',
                                        ];

                                        $color = $colors[$record->status] ?? $colors['draft'];
                                        $label = $labels[$record->status] ?? ucfirst((string) $record->status);

                                        return new HtmlString("<span class=\"inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border {$color}\">{$label}</span>");
                                    }),

                                Forms\Components\Placeholder::make('info_alur')
                                    ->label('Petunjuk Pengiriman')
                                    ->content(function (?Model $record) {
                                        if (! $record || $record->status === 'draft') {
                                            return new HtmlString('<p class="text-xs text-gray-500">Laporan berstatus <strong>Draft</strong> belum dikirim ke Sekmat. Gunakan tombol <strong>Kirim Laporan</strong> setelah data tersimpan.</p>');
                                        }

                                        if ($record->status === 'diajukan') {
                                            return new HtmlString('<p class="text-xs text-amber-600 font-medium">Laporan telah dikirim dan sedang menunggu verifikasi dari Sekretaris Camat / Admin.</p>');
                                        }

                                        if ($record->status === 'disetujui') {
                                            return new HtmlString('<p class="text-xs text-emerald-600 font-medium">Laporan telah diverifikasi dan disetujui resmi.</p>');
                                        }

                                        return new HtmlString('<p class="text-xs text-rose-600 font-medium">Laporan dikembalikan untuk perbaikan. Silakan periksa catatan revisi di bawah ini.</p>');
                                    }),

                                Forms\Components\Placeholder::make('catatan_verifikasi_info')
                                    ->label('Catatan Verifikator')
                                    ->content(fn (?Model $record) => $record?->catatan_verifikasi ? new HtmlString("<div class=\"p-3 text-xs rounded-lg bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-200\">{$record->catatan_verifikasi}</div>") : '-')
                                    ->visible(fn (?Model $record) => filled($record?->catatan_verifikasi)),

                                Forms\Components\Placeholder::make('waktu_pengiriman')
                                    ->label('Waktu Pengiriman')
                                    ->content(fn (?Model $record) => $record?->submitted_at ? $record->submitted_at->format('d/m/Y H:i').' WIB' : '-')
                                    ->visible(fn (?Model $record) => filled($record?->submitted_at)),

                                Forms\Components\Placeholder::make('diverifikasi_oleh')
                                    ->label('Diverifikasi Oleh')
                                    ->content(function (?Model $record) {
                                        if (! $record?->verified_by) {
                                            return '-';
                                        }
                                        $verifier = $record->verifier?->name ?? 'Admin';
                                        $time = $record->verified_at ? ' ('.$record->verified_at->format('d/m/Y H:i').' WIB)' : '';

                                        return "{$verifier}{$time}";
                                    })
                                    ->visible(fn (?Model $record) => filled($record?->verified_by)),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isVerifikator = $user?->isAdminKecamatan() || $user?->isSuperAdmin();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul_pelaporan')
                    ->label('Pelaporan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (LaporanKinerjaV2 $record) => "Periode: {$record->periode_bulan}/{$record->periode_tahun} • Tgl: {$record->tanggal_pelaporan->format('d/m/Y')}"),

                Tables\Columns\TextColumn::make('unitOrganisasi.nama_unit')
                    ->label('Unit Organisasi')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_pejabat')
                    ->label('Pejabat Pengisi')
                    ->searchable()
                    ->description(fn (LaporanKinerjaV2 $record) => "NIP: {$record->nip_pejabat} • {$record->jabatan_pejabat}"),

                Tables\Columns\TextColumn::make('bukti_dukung')
                    ->label('Bukti Dukung')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Tidak ada berkas';
                        }
                        $count = is_array($state) ? count($state) : 1;

                        return "{$count} Berkas";
                    })
                    ->badge()
                    ->color(fn ($state) => empty($state) ? 'gray' : 'primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft (Belum Dikirim)',
                        'diajukan' => 'Perlu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'secondary',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Laporan')
                    ->options([
                        'draft' => 'Draft (Belum Dikirim)',
                        'diajukan' => 'Perlu Verifikasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Perlu Revisi',
                    ]),

                Tables\Filters\SelectFilter::make('unit_organisasi_id')
                    ->label('Unit Organisasi')
                    ->relationship('unitOrganisasi', 'nama_unit')
                    ->visible(! $user?->isKasi()),

                Tables\Filters\SelectFilter::make('periode_tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $years = LaporanKinerjaV2::query()
                            ->select('periode_tahun')
                            ->distinct()
                            ->pluck('periode_tahun', 'periode_tahun')
                            ->toArray();

                        if (empty($years)) {
                            $years = [now()->year => now()->year];
                        }

                        return $years;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (LaporanKinerjaV2 $record) => $isVerifikator || in_array($record->status, ['draft', 'ditolak'])),

                // Aksi Kirim Laporan (Untuk Pembuat / Kasi)
                Tables\Actions\Action::make('kirim')
                    ->label('Kirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Laporan Kinerja')
                    ->modalDescription('Apakah Anda yakin ingin mengirim laporan ini untuk diverifikasi oleh Sekretaris Camat / Admin?')
                    ->modalSubmitActionLabel('Ya, Kirim Laporan')
                    ->visible(fn (LaporanKinerjaV2 $record) => in_array($record->status, ['draft', 'ditolak']))
                    ->action(function (LaporanKinerjaV2 $record, LaporanKinerjaV2Service $service) {
                        $service->kirimLaporan($record, auth()->user());
                        Notification::make()
                            ->title('Laporan Berhasil Dikirim')
                            ->body('Status berubah menjadi Perlu Verifikasi.')
                            ->success()
                            ->send();
                    }),

                // Aksi Setujui (Untuk Verifikator: Sekmat / Superadmin)
                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LaporanKinerjaV2 $record) => $isVerifikator && $record->status === 'diajukan')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Verifikasi (Opsional)')
                            ->placeholder('Tambahkan catatan jika diperlukan...'),
                    ])
                    ->modalHeading('Setujui Laporan Kinerja')
                    ->modalSubmitActionLabel('Setujui Laporan')
                    ->action(function (LaporanKinerjaV2 $record, array $data, LaporanKinerjaV2Service $service) {
                        $service->setujuiLaporan($record, auth()->user(), $data['catatan'] ?? null);
                        Notification::make()
                            ->title('Laporan Disetujui')
                            ->body("Laporan unit {$record->unitOrganisasi?->nama_unit} telah disetujui.")
                            ->success()
                            ->send();
                    }),

                // Aksi Kembalikan / Perlu Revisi (Untuk Verifikator: Sekmat / Superadmin)
                Tables\Actions\Action::make('kembalikan')
                    ->label('Revisi')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (LaporanKinerjaV2 $record) => $isVerifikator && $record->status === 'diajukan')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Revisi (Wajib Diisi)')
                            ->required()
                            ->placeholder('Jelaskan poin perbaikan yang harus dilakukan oleh unit...'),
                    ])
                    ->modalHeading('Kembalikan Laporan untuk Revisi')
                    ->modalSubmitActionLabel('Kembalikan Laporan')
                    ->action(function (LaporanKinerjaV2 $record, array $data, LaporanKinerjaV2Service $service) {
                        $service->kembalikanLaporan($record, auth()->user(), $data['catatan']);
                        Notification::make()
                            ->title('Laporan Dikembalikan')
                            ->body("Laporan unit {$record->unitOrganisasi?->nama_unit} dikembalikan untuk direvisi.")
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (LaporanKinerjaV2 $record) => $isVerifikator || ($record->isDraft() && auth()->user()?->unit_organisasi_id === $record->unit_organisasi_id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible($isVerifikator),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKinerjaV2s::route('/'),
            'create' => Pages\CreateLaporanKinerjaV2::route('/create'),
            'view' => Pages\ViewLaporanKinerjaV2::route('/{record}'),
            'edit' => Pages\EditLaporanKinerjaV2::route('/{record}/edit'),
        ];
    }

    protected static function isFormLockedForUser(?Model $record, ?User $user): bool
    {
        if (! $record || ! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdminKecamatan()) {
            return false;
        }

        return in_array($record->status, ['diajukan', 'disetujui']);
    }
}
