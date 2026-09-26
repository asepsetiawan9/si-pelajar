<?php

namespace App\Filament\Resources\RencanaAksiResource\Pages;

use App\Filament\Resources\RencanaAksiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRencanaAksi extends CreateRecord
{
    protected static string $resource = RencanaAksiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
