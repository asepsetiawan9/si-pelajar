<?php

namespace App\Filament\Resources\UnitOrganisasiResource\Pages;

use App\Filament\Resources\UnitOrganisasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitOrganisasis extends ListRecords
{
    protected static string $resource = UnitOrganisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
