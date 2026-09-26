<?php

namespace App\Filament\Resources\SasaranStrategisResource\Pages;

use App\Filament\Resources\SasaranStrategisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSasaranStrategis extends EditRecord
{
    protected static string $resource = SasaranStrategisResource::class;

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
