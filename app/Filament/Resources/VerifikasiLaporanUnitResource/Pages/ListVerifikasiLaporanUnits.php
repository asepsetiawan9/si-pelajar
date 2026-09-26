<?php

namespace App\Filament\Resources\VerifikasiLaporanUnitResource\Pages;

use App\Filament\Resources\VerifikasiLaporanUnitResource;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasiLaporanUnits extends ListRecords
{
    protected static string $resource = VerifikasiLaporanUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
