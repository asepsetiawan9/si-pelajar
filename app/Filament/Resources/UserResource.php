<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $navigationLabel = 'Pengguna & Akun';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Daftar Pengguna';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun & Akses')
                    ->description('Kelola data profil, peran sistem, dan unit organisasi yang diampu.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap & Gelar')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nip')
                            ->label('NIP (Nomor Induk Pegawai)')
                            ->helperText('Kosongkan jika bukan ASN atau belum memiliki NIP')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email Resmi')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Biarkan kosong jika tidak ingin mengubah password.' : 'Minimal 8 karakter.')
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Peran Pengguna (Role)')
                            ->options([
                                'superadmin' => 'Superadmin (Pengelola IT)',
                                'admin_kecamatan' => 'Admin Kecamatan (Sekmat / Verifikator)',
                                'kasi' => 'Kasi / Kasubag (Pengisi Kinerja Unit)',
                                'camat' => 'Camat (Pimpinan / Approver Final)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state !== 'kasi') {
                                    $set('unit_organisasi_id', null);
                                }
                            }),

                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan Resmi')
                            ->placeholder('Contoh: Sekretaris Camat, Kepala Seksi Pelayanan')
                            ->maxLength(255),

                        Forms\Components\Select::make('unit_organisasi_id')
                            ->label('Unit Organisasi yang Diampu')
                            ->relationship('unitOrganisasi', 'nama_unit')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('role') === 'kasi')
                            ->required(fn (Get $get): bool => $get('role') === 'kasi')
                            ->helperText('Wajib dipilih khusus untuk pejabat Kepala Seksi / Kasubag.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Akun Aktif')
                            ->helperText('Jika dinonaktifkan, pengguna tidak dapat masuk ke sistem.')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Peran')
                    ->colors([
                        'danger' => 'superadmin',
                        'warning' => 'admin_kecamatan',
                        'success' => 'camat',
                        'info' => 'kasi',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'superadmin' => 'Superadmin',
                        'admin_kecamatan' => 'Admin Kecamatan',
                        'camat' => 'Camat',
                        'kasi' => 'Kasi / Kasubag',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('unitOrganisasi.nama_unit')
                    ->label('Unit Pengampu')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Peran')
                    ->options([
                        'superadmin' => 'Superadmin',
                        'admin_kecamatan' => 'Admin Kecamatan',
                        'kasi' => 'Kasi / Kasubag',
                        'camat' => 'Camat',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
