<?php

namespace App\Filament\Resources\RencanaAksiResource\Pages;

use App\Filament\Resources\RencanaAksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRencanaAksis extends ListRecords
{
    protected static string $resource = RencanaAksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
