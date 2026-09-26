<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SasaranStrategisResource\Pages;
use App\Models\SasaranStrategis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SasaranStrategisResource extends Resource
{
    protected static ?string $model = SasaranStrategis::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Sasaran Strategis';

    protected static ?string $modelLabel = 'Sasaran Strategis';

    protected static ?string $pluralModelLabel = 'Sasaran Strategis Camat';

    protected static ?int $navigationSort = 2;

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
                Forms\Components\Section::make('Instrumen Sasaran Strategis Camat')
                    ->description('Berdasarkan dokumen Perjanjian Kinerja (PK) Camat Malangbong.')
                    ->schema([
                        Forms\Components\TextInput::make('tahun')
                            ->label('Tahun Anggaran')
                            ->numeric()
                            ->default(fn () => (int) date('Y'))
                            ->required(),

                        Forms\Components\TextInput::make('indikator_kinerja')
                            ->label('Indikator Kinerja Sasaran')
                            ->placeholder('Contoh: Nilai Sinergitas Kinerja Kecamatan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('uraian_sasaran')
                            ->label('Uraian Sasaran Strategis')
                            ->placeholder('Contoh: Meningkatnya kinerja penyelenggaraan pelayanan publik dan pemerintahan di kewilayahan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('target_angka')
                            ->label('Target Angka')
                            ->numeric()
                            ->step('0.01')
                            ->required()
                            ->placeholder('Contoh: 84.00'),

                        Forms\Components\TextInput::make('satuan')
                            ->label('Satuan Pengukuran')
                            ->default('Nilai')
                            ->required()
                            ->placeholder('Contoh: Nilai, Indeks, %'),

                        Forms\Components\Textarea::make('program_penunjang')
                            ->label('Program Penunjang')
                            ->placeholder('Contoh: Program Penyelenggaraan Pemerintahan dan Pelayanan Publik')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tahun', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('uraian_sasaran')
                    ->label('Uraian Sasaran')
                    ->searchable()
                    ->wrap()
                    ->weight('bold')
                    ->description(fn (SasaranStrategis $record): string => "Program: {$record->program_penunjang}"),

                Tables\Columns\TextColumn::make('indikator_kinerja')
                    ->label('Indikator Kinerja')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('target_angka')
                    ->label('Target')
                    ->formatStateUsing(fn (SasaranStrategis $record): string => number_format((float) $record->target_angka, 2).' '.$record->satuan)
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rencana_aksis_count')
                    ->label('Rencana Aksi')
                    ->counts('rencanaAksis')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(function () {
                        return SasaranStrategis::query()
                            ->distinct()
                            ->pluck('tahun', 'tahun')
                            ->toArray();
                    }),
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
            'index' => Pages\ListSasaranStrategis::route('/'),
            'create' => Pages\CreateSasaranStrategis::route('/create'),
            'edit' => Pages\EditSasaranStrategis::route('/{record}/edit'),
        ];
    }
}
