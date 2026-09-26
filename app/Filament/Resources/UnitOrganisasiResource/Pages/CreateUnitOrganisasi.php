<?php

namespace App\Filament\Resources\UnitOrganisasiResource\Pages;

use App\Filament\Resources\UnitOrganisasiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitOrganisasi extends CreateRecord
{
    protected static string $resource = UnitOrganisasiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
