<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitOrganisasiResource\Pages;
use App\Models\UnitOrganisasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UnitOrganisasiResource extends Resource
{
    protected static ?string $model = UnitOrganisasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Unit Organisasi';

    protected static ?string $modelLabel = 'Unit Organisasi';

    protected static ?string $pluralModelLabel = 'Daftar Unit Organisasi';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Unit Organisasi')
                    ->description('Kelola struktur unit kerja operasional pelapor di lingkungan Kecamatan Malangbong.')
                    ->schema([
                        Forms\Components\TextInput::make('nama_unit')
                            ->label('Nama Unit Organisasi')
                            ->placeholder('Contoh: Seksi Pelayanan, Sub Bagian Keuangan dan BMD')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('kode_unit')
                            ->label('Kode Unit / Singkatan')
                            ->placeholder('Contoh: SEKSI-PELAYANAN, SUBBAG-KEUANGAN')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('urutan')
                            ->label('Nomor Urut Tampilan')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->helperText('Urutan unit saat disajikan di dalam kompilasi laporan bulanan.'),

                        Forms\Components\Toggle::make('wajib_dilaporkan')
                            ->label('Wajib Melaporkan Kinerja')
                            ->helperText('Jika aktif, unit ini wajib menyusun capaian rencana aksi setiap bulan.')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('urutan')
                    ->label('No. Urut')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('nama_unit')
                    ->label('Nama Unit Kerja')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('kode_unit')
                    ->label('Kode Unit')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Pejabat / Staf')
                    ->counts('users')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('rencana_aksis_count')
                    ->label('Rencana Aksi')
                    ->counts('rencanaAksis')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('wajib_dilaporkan')
                    ->label('Wajib Lapor')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('wajib_dilaporkan')
                    ->label('Status Wajib Lapor'),
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
            'index' => Pages\ListUnitOrganisasis::route('/'),
            'create' => Pages\CreateUnitOrganisasi::route('/create'),
            'edit' => Pages\EditUnitOrganisasi::route('/{record}/edit'),
        ];
    }
}
