<?php

namespace App\Filament\Resources\LaporanKinerjaV2Resource\Pages;

use App\Filament\Resources\LaporanKinerjaV2Resource;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanKinerjaV2 extends CreateRecord
{
    protected static string $resource = LaporanKinerjaV2Resource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika bukan admin, pastikan user_id dan unit_organisasi_id terikat ke user yang login
        if ($user && $user->isKasi()) {
            $data['user_id'] = $user->id;
            $data['unit_organisasi_id'] = $user->unit_organisasi_id;
        } elseif (empty($data['user_id'])) {
            $data['user_id'] = $user?->id;
        }

        $data['status'] = 'draft';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
