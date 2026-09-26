<?php

namespace App\Filament\Resources\LaporanKinerjaV2Resource\Pages;

use App\Filament\Resources\LaporanKinerjaV2Resource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLaporanKinerjaV2s extends ListRecords
{
    protected static string $resource = LaporanKinerjaV2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Pelaporan Baru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();
        if ($user && ($user->isAdminKecamatan() || $user->isSuperAdmin())) {
            return 'Daftar pelaporan kinerja unit kerja operasional Kecamatan Malangbong (Versi 2). Lakukan verifikasi pada laporan yang berstatus Perlu Verifikasi.';
        }

        return 'Kelola dan kirim berkas laporan kinerja unit kerja Anda. Laporan yang dikirim akan diverifikasi oleh Sekretaris Camat / Admin.';
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Laporan'),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'draft')->count())
                ->badgeColor('gray'),
            'diajukan' => Tab::make('Perlu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'diajukan'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'diajukan')->count())
                ->badgeColor('warning'),
            'disetujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'disetujui'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'disetujui')->count())
                ->badgeColor('success'),
            'ditolak' => Tab::make('Perlu Revisi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ditolak'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'ditolak')->count())
                ->badgeColor('danger'),
        ];
    }
}
