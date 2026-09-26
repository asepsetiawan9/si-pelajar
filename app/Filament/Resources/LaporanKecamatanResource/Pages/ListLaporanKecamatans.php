<?php

namespace App\Filament\Resources\LaporanKecamatanResource\Pages;

use App\Filament\Resources\LaporanKecamatanResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanKecamatans extends ListRecords
{
    protected static string $resource = LaporanKecamatanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
