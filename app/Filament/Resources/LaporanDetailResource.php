<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanDetailResource\Pages;
use App\Models\Laporan;
use App\Models\LaporanDetail;
use App\Models\LaporanDetailIndikator;
use App\Models\LaporanDetailLayanan;
use App\Models\RencanaAksi;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LaporanDetailResource extends Resource
{
    protected static ?string $model = LaporanDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Pelaporan Kinerja';

    protected static ?string $navigationLabel = 'Laporan Kinerja Unit';

    protected static ?string $modelLabel = 'Laporan Kinerja Unit';

    protected static ?string $pluralModelLabel = 'Laporan Kinerja Unit';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['laporan', 'unitOrganisasi', 'user', 'indikators.rencanaAksi.sasaranStrategis']);

        $user = auth()->user();

        // Row-Level Security: Kasi hanya dapat melihat laporan unitnya sendiri
        if ($user && $user->isKasi()) {
            $query->where('unit_organisasi_id', $user->unit_organisasi_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('LaporanDetailTabs')
                    ->tabs([
                        // TAB 1: INFORMASI UMUM
                        Forms\Components\Tabs\Tab::make('Informasi Umum')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Identitas Laporan & Unit')
                                    ->description('Informasi periode pelaporan dan unit organisasi pengampu.')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('bulan_pelaporan')
                                            ->label('Bulan Pelaporan')
                                            ->displayFormat('F Y')
                                            ->format('Y-m-d')
                                            ->default(fn () => now()->subMonth()->startOfMonth()->toDateString())
                                            ->required()
                                            ->native(false)
                                            ->disabled(fn (?LaporanDetail $record) => $record !== null)
                                            ->helperText('Batas penginputan tanggal 10 bulan berikutnya.'),

                                        Forms\Components\Select::make('unit_organisasi_id')
                                            ->label('Unit Organisasi')
                                            ->options(UnitOrganisasi::orderBy('urutan')->pluck('nama_unit', 'id'))
                                            ->default(fn () => auth()->user()?->unit_organisasi_id)
                                            ->disabled(fn () => auth()->user()?->isKasi() || $form->getRecord() !== null)
                                            ->dehydrated()
                                            ->required()
                                            ->live()
                                            ->helperText(fn () => auth()->user()?->isKasi()
                                                ? 'Otomatis terkunci sesuai unit kerja Anda.'
                                                : 'Pilih unit yang akan dilaporkan (Delegated Input).'
                                            ),

                                        Forms\Components\Placeholder::make('status_badge')
                                            ->label('Status Laporan')
                                            ->content(function (?LaporanDetail $record): string {
                                                if (! $record) {
                                                    return 'Draft Baru';
                                                }

                                                $status = match ($record->status) {
                                                    'draft' => 'Draft',
                                                    'diajukan' => 'Diajukan ke Sekmat',
                                                    'disetujui' => 'Disetujui',
                                                    'ditolak' => 'Dikembalikan / Ditolak',
                                                    default => $record->status,
                                                };

                                                if ($record->is_late) {
                                                    $status .= ' (Terlambat - Pasca Cut-Off)';
                                                }

                                                if ($record->is_dispensasi) {
                                                    $status .= ' [Dispensasi Aktif]';
                                                }

                                                return $status;
                                            }),

                                        Forms\Components\Placeholder::make('user_pengisi')
                                            ->label('Pejabat Pengisi')
                                            ->content(fn (?LaporanDetail $record) => $record?->user?->name ?? auth()->user()?->name ?? '-'),
                                    ]),

                                Forms\Components\Section::make('Catatan Penolakan / Revisi dari Sekmat')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->schema([
                                        Forms\Components\Placeholder::make('catatan_sekmat')
                                            ->label('Catatan Verifikator')
                                            ->content(fn (?LaporanDetail $record) => $record?->catatan_verifikasi_sekmat ?? '-'),
                                    ])
                                    ->visible(fn (?LaporanDetail $record) => $record?->status === 'ditolak'),

                                Forms\Components\Section::make('Dasar Hukum & Narasi Pelaporan')
                                    ->schema([
                                        Forms\Components\Textarea::make('latar_belakang')
                                            ->label('Latar Belakang & Dasar Hukum')
                                            ->default(fn () => LaporanDetail::defaultLatarBelakang())
                                            ->rows(4)
                                            ->required()
                                            ->helperText('Template default Permenpan-RB No. 53/2014 & No. 22/2024.'),

                                        Forms\Components\Textarea::make('keterangan_keterkaitan')
                                            ->label('Keterkaitan Rencana Aksi / Program Kerja')
                                            ->rows(3)
                                            ->placeholder('Uraikan keterkaitan rencana aksi unit dengan target Perjanjian Kinerja Camat...'),
                                    ]),
                            ]),

                        // TAB 2: CAPAIAN KINERJA & REALISASI ANGGARAN
                        Forms\Components\Tabs\Tab::make('Capaian Kinerja & Anggaran')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Actions::make([
                                    FormAction::make('loadRencanaAksiUnit')
                                        ->label('⚡ Muat Seluruh Rencana Aksi Unit Ini')
                                        ->icon('heroicon-o-bolt')
                                        ->color('primary')
                                        ->action(function (Get $get, Set $set) {
                                            $unitId = $get('unit_organisasi_id') ?? auth()->user()?->unit_organisasi_id;
                                            if (! $unitId) {
                                                Notification::make()
                                                    ->title('Unit Belum Dipilih')
                                                    ->body('Silakan pilih unit organisasi terlebih dahulu di Tab 1 (Informasi Umum).')
                                                    ->warning()
                                                    ->send();

                                                return;
                                            }

                                            $rencanaAksis = RencanaAksi::where('unit_organisasi_id', $unitId)->get();
                                            if ($rencanaAksis->isEmpty()) {
                                                Notification::make()
                                                    ->title('Tidak Ada Rencana Aksi')
                                                    ->body('Belum ada rencana aksi yang terdaftar untuk unit organisasi ini.')
                                                    ->warning()
                                                    ->send();

                                                return;
                                            }

                                            $items = [];
                                            foreach ($rencanaAksis as $ra) {
                                                $target = (float) ($ra->target_default ?? 1);
                                                $items[] = [
                                                    'rencana_aksi_id' => $ra->id,
                                                    'target_kinerja' => $target,
                                                    'realisasi_kinerja' => 0,
                                                    'persentase_kinerja' => 0.0,
                                                    'predikat_efektivitas' => 'tidak_efektif',
                                                    'anggaran_pagu' => 0,
                                                    'realisasi_anggaran' => 0,
                                                    'persentase_anggaran' => 0.0,
                                                    'predikat_efisiensi' => 'sangat_efisien',
                                                ];
                                            }

                                            $set('indikators', $items);

                                            Notification::make()
                                                ->title('Rencana Aksi Berhasil Dimuat')
                                                ->body(count($items).' indikator rencana aksi unit berhasil dimuat secara otomatis.')
                                                ->success()
                                                ->send();
                                        }),
                                ]),

                                Forms\Components\Repeater::make('indikators')
                                    ->relationship('indikators')
                                    ->label('Daftar Indikator Rencana Aksi')
                                    ->itemLabel(function (array $state): ?string {
                                        if (empty($state['rencana_aksi_id'])) {
                                            return 'Indikator Baru';
                                        }
                                        $aksi = RencanaAksi::find($state['rencana_aksi_id']);

                                        return $aksi ? $aksi->uraian_rencana_aksi : 'Indikator';
                                    })
                                    ->schema([
                                        Forms\Components\Select::make('rencana_aksi_id')
                                            ->label('Rencana Aksi Unit')
                                            ->options(function (Get $get) {
                                                $unitId = $get('../../unit_organisasi_id') ?? auth()->user()?->unit_organisasi_id;
                                                if (! $unitId) {
                                                    return RencanaAksi::pluck('uraian_rencana_aksi', 'id');
                                                }

                                                return RencanaAksi::where('unit_organisasi_id', $unitId)
                                                    ->pluck('uraian_rencana_aksi', 'id');
                                            })
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state) {
                                                    $ra = RencanaAksi::find($state);
                                                    if ($ra) {
                                                        $set('target_kinerja', $ra->target_default);
                                                        $target = (float) $ra->target_default;
                                                        $realisasi = 0.0;
                                                        $persen = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                                                        $set('persentase_kinerja', $persen);
                                                        $set('predikat_efektivitas', LaporanDetailIndikator::calculatePredikatEfektivitas($persen));
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('target_kinerja')
                                                    ->label('Target Kinerja')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $target = (float) ($get('target_kinerja') ?? 0);
                                                        $realisasi = (float) ($get('realisasi_kinerja') ?? 0);
                                                        $persen = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                                                        $set('persentase_kinerja', $persen);
                                                        $set('predikat_efektivitas', LaporanDetailIndikator::calculatePredikatEfektivitas($persen));
                                                    }),

                                                Forms\Components\TextInput::make('realisasi_kinerja')
                                                    ->label('Realisasi Fisik')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $target = (float) ($get('target_kinerja') ?? 0);
                                                        $realisasi = (float) ($get('realisasi_kinerja') ?? 0);
                                                        $persen = LaporanDetailIndikator::calculatePersentaseKinerja($target, $realisasi);
                                                        $set('persentase_kinerja', $persen);
                                                        $set('predikat_efektivitas', LaporanDetailIndikator::calculatePredikatEfektivitas($persen));
                                                    }),

                                                Forms\Components\TextInput::make('persentase_kinerja')
                                                    ->label('Capaian (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->default(0),

                                                Forms\Components\Select::make('predikat_efektivitas')
                                                    ->label('Predikat Efektivitas')
                                                    ->options([
                                                        'sangat_efektif' => 'Sangat Efektif (> 100%)',
                                                        'efektif' => 'Efektif (90% - 100%)',
                                                        'cukup_efektif' => 'Cukup Efektif (60% - 89%)',
                                                        'tidak_efektif' => 'Tidak Efektif (< 60%)',
                                                    ])
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->default('tidak_efektif'),
                                            ]),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('anggaran_pagu')
                                                    ->label('Pagu Anggaran (Rp)')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->default(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $pagu = (float) ($get('anggaran_pagu') ?? 0);
                                                        $realisasi = (float) ($get('realisasi_anggaran') ?? 0);
                                                        $persen = LaporanDetailIndikator::calculatePersentaseAnggaran($pagu, $realisasi);
                                                        $set('persentase_anggaran', $persen);
                                                        $set('predikat_efisiensi', LaporanDetailIndikator::calculatePredikatEfisiensi($persen));
                                                    }),

                                                Forms\Components\TextInput::make('realisasi_anggaran')
                                                    ->label('Realisasi Belanja (Rp)')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->default(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $pagu = (float) ($get('anggaran_pagu') ?? 0);
                                                        $realisasi = (float) ($get('realisasi_anggaran') ?? 0);
                                                        $persen = LaporanDetailIndikator::calculatePersentaseAnggaran($pagu, $realisasi);
                                                        $set('persentase_anggaran', $persen);
                                                        $set('predikat_efisiensi', LaporanDetailIndikator::calculatePredikatEfisiensi($persen));
                                                    }),

                                                Forms\Components\TextInput::make('persentase_anggaran')
                                                    ->label('Serapan (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->default(0),

                                                Forms\Components\Select::make('predikat_efisiensi')
                                                    ->label('Predikat Efisiensi')
                                                    ->options([
                                                        'sangat_efisien' => 'Sangat Efisien (< 60%)',
                                                        'efisien' => 'Efisien (60% - 90%)',
                                                        'cukup_efisien' => 'Cukup Efisien (91% - 100%)',
                                                        'tidak_efisien' => 'Tidak Efisien (> 100%)',
                                                    ])
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->default('sangat_efisien'),
                                            ]),
                                    ])
                                    ->addActionLabel('+ Tambah Indikator Rencana Aksi')
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->defaultItems(1),
                            ]),

                        // TAB 3: RINCIAN LAYANAN DINAMIS (PATEN)
                        Forms\Components\Tabs\Tab::make('Rincian Layanan / Kegiatan')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Forms\Components\Actions::make([
                                    FormAction::make('loadPatenPresets')
                                        ->label('⚡ Muat 10 Preset Layanan PATEN (Seksi Pelayanan)')
                                        ->icon('heroicon-o-sparkles')
                                        ->color('primary')
                                        ->action(function (Set $set) {
                                            $presets = LaporanDetailLayanan::defaultPresetsPaten();
                                            $set('layanans', $presets);
                                            Notification::make()
                                                ->title('Preset Berhasil Dimuat')
                                                ->body('10 item layanan PATEN telah ditambahkan ke sub-tabel rincian.')
                                                ->success()
                                                ->send();
                                        }),
                                ]),

                                Forms\Components\Repeater::make('layanans')
                                    ->relationship('layanans')
                                    ->label('Sub-Tabel Rincian Pemohon / Kegiatan Layanan')
                                    ->defaultItems(0)
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_layanan')
                                            ->label('Nama Layanan / Kegiatan')
                                            ->required()
                                            ->placeholder('Contoh: Surat Keterangan Tidak Mampu (SKTM)')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah Pemohon')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),

                                        Forms\Components\TextInput::make('satuan')
                                            ->label('Satuan')
                                            ->default('Pemohon')
                                            ->required(),

                                        Forms\Components\TextInput::make('keterangan')
                                            ->label('Keterangan Tambahan')
                                            ->placeholder('Catatan atau keterangan...')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->addActionLabel('+ Tambah Rincian Layanan')
                                    ->collapsible(),
                            ]),

                        // TAB 4: EVALUASI & HAMBATAN
                        Forms\Components\Tabs\Tab::make('Evaluasi & Hambatan')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Forms\Components\Textarea::make('keluhan_masyarakat')
                                    ->label('Keluhan / Pengaduan Masyarakat')
                                    ->rows(3)
                                    ->placeholder('Uraikan keluhan masyarakat atau catatan helpdesk selama bulan pelaporan...'),

                                Forms\Components\Textarea::make('hambatan')
                                    ->label('Hambatan / Kendala Pelaksanaan')
                                    ->rows(3)
                                    ->placeholder('Uraikan hambatan operasional, sarana prasarana, atau kendala regulasi...'),

                                Forms\Components\Textarea::make('simpulan')
                                    ->label('Simpulan Naratif Evaluasi Kinerja')
                                    ->rows(4)
                                    ->placeholder('Simpulan capaian kinerja unit secara keseluruhan...'),
                            ]),

                        // TAB 5: BUKTI DUKUNG
                        Forms\Components\Tabs\Tab::make('Bukti Dukung')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                Forms\Components\Repeater::make('dokumens')
                                    ->relationship('dokumens')
                                    ->label('Lampiran Berkas Pendukung (PDF / Foto Kegiatan)')
                                    ->defaultItems(0)
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_dokumen')
                                            ->label('Nama Dokumen / Keterangan Berkas')
                                            ->required()
                                            ->placeholder('Contoh: Rekap Fisik Pemohon PATEN Bulan Ini'),

                                        Forms\Components\FileUpload::make('file_path')
                                            ->label('Berkas Lampiran')
                                            ->directory('spko-dokumen')
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                            ->maxSize(10240) // 10MB
                                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                                $cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());

                                                return (string) str(now()->format('Ymd_His').'_'.$cleanName);
                                            })
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('+ Unggah Bukti Dukung')
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('laporan.bulan_pelaporan')
                    ->label('Bulan Pelaporan')
                    ->date('F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unitOrganisasi.nama_unit')
                    ->label('Unit Organisasi')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pejabat Pengisi')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('indikators_count')
                    ->counts('indikators')
                    ->label('Jml Indikator')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan ke Sekmat',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Dikembalikan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'info',
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
                    ->falseColor('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => Laporan::distinct()->pluck('tahun', 'tahun')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => ! empty($data['value']) ? $query->whereHas('laporan', fn ($q) => $q->where('tahun', $data['value'])) : $query),

                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => ! empty($data['value']) ? $query->whereHas('laporan', fn ($q) => $q->whereMonth('bulan_pelaporan', $data['value'])) : $query),

                Tables\Filters\SelectFilter::make('unit_organisasi_id')
                    ->label('Unit Organisasi')
                    ->options(UnitOrganisasi::pluck('nama_unit', 'id'))
                    ->visible(fn () => ! auth()->user()?->isKasi()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Dikembalikan',
                    ]),

                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Keterlambatan (Cut-off)'),
            ])
            ->actions([
                // Action Ajukan Laporan Unit ke Sekmat
                Tables\Actions\Action::make('ajukan')
                    ->label('Ajukan ke Sekmat')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(function (LaporanDetail $record): bool {
                        $user = auth()->user();
                        if (! $user) {
                            return false;
                        }

                        // Hanya bisa diajukan jika status draft atau ditolak
                        if (! in_array($record->status, ['draft', 'ditolak'], true)) {
                            return false;
                        }

                        // Hak akses: Superadmin, Sekmat, atau Kasi pemilik unit
                        return $user->isSuperAdmin() || $user->isAdminKecamatan() || $user->unit_organisasi_id === $record->unit_organisasi_id;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Pengesahan Pengajuan Laporan Kinerja')
                    ->modalDescription('Apakah Anda yakin ingin mengajukan laporan kinerja unit ini ke Sekretaris Camat? Formulir akan dikunci sementara untuk proses verifikasi.')
                    ->modalSubmitActionLabel('Ya, Ajukan Laporan')
                    ->action(function (LaporanDetail $record) {
                        if ($record->indikators()->count() === 0) {
                            Notification::make()
                                ->title('Pengajuan Gagal')
                                ->body('Harap isi minimal 1 indikator rencana aksi sebelum mengajukan laporan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Cek keterlambatan cut-off
                        $isLate = LaporanDetail::isPastCutoff($record->laporan->bulan_pelaporan);
                        if ($isLate && ! $record->hasActiveDispensasi()) {
                            $record->is_late = true;
                        }

                        $record->status = 'diajukan';
                        $record->submitted_at = now();
                        $record->save();

                        // Kirim notifikasi database Filament ke seluruh Admin Kecamatan (Sekmat) dan Superadmin
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
                                    ->url(LaporanDetailResource::getUrl('edit', ['record' => $record])),
                            ])
                            ->sendToDatabase($recipients);

                        Notification::make()
                            ->title('Laporan Berhasil Diajukan')
                            ->body('Laporan kinerja telah dikirimkan ke Sekretaris Camat untuk diverifikasi.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('unduhPdf')
                    ->label('PDF')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('info')
                    ->url(fn (LaporanDetail $record): string => route('spko.laporan-detail.pdf', ['detail' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('unduhExcel')
                    ->label('Excel')
                    ->icon('heroicon-m-table-cells')
                    ->color('success')
                    ->url(fn (LaporanDetail $record): string => route('spko.laporan-detail.excel', ['detail' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (LaporanDetail $record): bool => ! $record->isLocked() || auth()->user()?->isSuperAdmin() || auth()->user()?->isAdminKecamatan()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isSuperAdmin()),
                ]),
            ])
            ->emptyStateHeading(fn () => auth()->user()?->isCamat() ? 'Belum Ada Laporan Unit' : 'Belum Ada Laporan Kinerja')
            ->emptyStateDescription(fn () => auth()->user()?->isCamat()
                ? 'Sebagai Camat / Pengesah, Anda meninjau dan mengesahkan kompilasi seluruh unit di menu "Kompilasi & Pengesahan Camat".'
                : 'Silakan gunakan tombol "Buat Laporan Kinerja Baru" di atas untuk memulai penginputan capaian kinerja unit.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && ! $user->isCamat();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdminKecamatan()) {
            return true;
        }

        /** @var LaporanDetail $record */
        return $record->unit_organisasi_id === $user->unit_organisasi_id && ! $record->isLocked();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        /** @var LaporanDetail $record */
        return $user->isAdminKecamatan() && $record->status === 'draft';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanDetails::route('/'),
            'create' => Pages\CreateLaporanDetail::route('/create'),
            'view' => Pages\ViewLaporanDetail::route('/{record}'),
            'edit' => Pages\EditLaporanDetail::route('/{record}/edit'),
        ];
    }
}
