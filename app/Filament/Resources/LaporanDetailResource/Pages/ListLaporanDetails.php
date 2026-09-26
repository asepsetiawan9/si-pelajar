<?php

namespace App\Filament\Resources\LaporanDetailResource\Pages;

use App\Filament\Resources\LaporanDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanDetails extends ListRecords
{
    protected static string $resource = LaporanDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Laporan Kinerja Baru')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();
        if ($user && $user->isCamat()) {
            return 'Pimpinan (Camat) memantau pengisian laporan kinerja 7 unit kerja. Pengesahan final dokumen gabungan dilakukan melalui menu Kompilasi & Pengesahan Camat.';
        }

        return null;
    }
}
