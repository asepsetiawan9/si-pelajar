<?php

namespace App\Filament\Resources\SasaranStrategisResource\Pages;

use App\Filament\Resources\SasaranStrategisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSasaranStrategis extends CreateRecord
{
    protected static string $resource = SasaranStrategisResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
