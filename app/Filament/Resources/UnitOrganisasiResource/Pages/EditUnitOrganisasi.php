<?php

namespace App\Filament\Resources\UnitOrganisasiResource\Pages;

use App\Filament\Resources\UnitOrganisasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitOrganisasi extends EditRecord
{
    protected static string $resource = UnitOrganisasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
