<?php

namespace App\Filament\Resources\SasaranStrategisResource\Pages;

use App\Filament\Resources\SasaranStrategisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSasaranStrategis extends ListRecords
{
    protected static string $resource = SasaranStrategisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
