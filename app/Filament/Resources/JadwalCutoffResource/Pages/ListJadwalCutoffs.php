<?php

namespace App\Filament\Resources\JadwalCutoffResource\Pages;

use App\Filament\Resources\JadwalCutoffResource;
use Filament\Resources\Pages\ListRecords;

class ListJadwalCutoffs extends ListRecords
{
    protected static string $resource = JadwalCutoffResource::class;

    public function getSubheading(): ?string
    {
        return 'Pusat kendali batas waktu (cut-off) pelaporan bulanan. Anda dapat membuka atau menutup akses kapan saja, serta mengkustomisasi tanggal batas waktu per periode pelaporan.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
