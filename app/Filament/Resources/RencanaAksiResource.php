<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RencanaAksiResource\Pages;
use App\Models\RencanaAksi;
use App\Models\SasaranStrategis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RencanaAksiResource extends Resource
{
    protected static ?string $model = RencanaAksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Rencana Aksi';

    protected static ?string $modelLabel = 'Rencana Aksi';

    protected static ?string $pluralModelLabel = 'Rencana Aksi Unit';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdminKecamatan());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rencana Aksi Unit Kerja')
                    ->description('Rencana aksi operasional tahunan unit yang mendukung pencapaian sasaran strategis Camat.')
                    ->schema([
                        Forms\Components\Select::make('unit_organisasi_id')
                            ->label('Unit Organisasi Pengampu')
                            ->relationship('unitOrganisasi', 'nama_unit')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('sasaran_strategis_id')
                            ->label('Sasaran Strategis Camat')
                            ->relationship('sasaranStrategis', 'uraian_sasaran')
                            ->getOptionLabelFromRecordUsing(fn (SasaranStrategis $record) => "[{$record->tahun}] {$record->uraian_sasaran}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Sasaran strategis Camat yang menjadi acuan aksi ini.'),

                        Forms\Components\Textarea::make('uraian_rencana_aksi')
                            ->label('Uraian Rencana Aksi')
                            ->placeholder('Contoh: Menyelenggarakan pelayanan PATEN Kecamatan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('indikator_kinerja')
                            ->label('Indikator Kinerja Aksi')
                            ->placeholder('Contoh: Jumlah layanan perizinan dan non-perizinan PATEN yang diselesaikan sesuai SOP')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('target_default')
                            ->label('Target Kinerja Default')
                            ->numeric()
                            ->step('0.01')
                            ->default(1)
                            ->required()
                            ->helperText('Nilai target acuan bulanan saat pengisian formulir kinerja.'),

                        Forms\Components\TextInput::make('satuan_target')
                            ->label('Satuan Target')
                            ->default('Laporan')
                            ->placeholder('Contoh: Laporan/Bulan, Dokumen Laporan, Berkas')
                            ->required()
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('unit_organisasi_id')
            ->columns([
                Tables\Columns\TextColumn::make('unitOrganisasi.nama_unit')
                    ->label('Unit Pengampu')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->wrap(),

                Tables\Columns\TextColumn::make('uraian_rencana_aksi')
                    ->label('Uraian Rencana Aksi')
                    ->searchable()
                    ->wrap()
                    ->weight('bold')
                    ->description(fn (RencanaAksi $record): string => "Indikator: {$record->indikator_kinerja}"),

                Tables\Columns\TextColumn::make('target_default')
                    ->label('Target Default')
                    ->formatStateUsing(fn (RencanaAksi $record): string => number_format((float) $record->target_default, 0).' '.$record->satuan_target)
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sasaranStrategis.uraian_sasaran')
                    ->label('Sasaran Camat')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_organisasi_id')
                    ->label('Filter Unit')
                    ->relationship('unitOrganisasi', 'nama_unit'),

                Tables\Filters\SelectFilter::make('sasaran_strategis_id')
                    ->label('Filter Sasaran Strategis')
                    ->relationship('sasaranStrategis', 'uraian_sasaran'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRencanaAksis::route('/'),
            'create' => Pages\CreateRencanaAksi::route('/create'),
            'edit' => Pages\EditRencanaAksi::route('/{record}/edit'),
        ];
    }
}
