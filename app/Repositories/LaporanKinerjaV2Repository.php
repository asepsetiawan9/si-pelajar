<?php

namespace App\Repositories;

use App\Models\LaporanKinerjaV2;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LaporanKinerjaV2Repository
{
    public function getBaseQuery(?User $user = null): Builder
    {
        $query = LaporanKinerjaV2::query()
            ->with(['unitOrganisasi', 'user', 'verifier'])
            ->latest('tanggal_pelaporan')
            ->latest('id');

        if ($user && $user->isKasi()) {
            $query->where('unit_organisasi_id', $user->unit_organisasi_id);
        }

        return $query;
    }

    public function findById(int $id): ?LaporanKinerjaV2
    {
        return LaporanKinerjaV2::with(['unitOrganisasi', 'user', 'verifier'])->find($id);
    }

    public function create(array $data): LaporanKinerjaV2
    {
        return LaporanKinerjaV2::create($data);
    }

    public function update(LaporanKinerjaV2 $laporan, array $data): LaporanKinerjaV2
    {
        $laporan->update($data);

        return $laporan->fresh(['unitOrganisasi', 'user', 'verifier']);
    }

    public function delete(LaporanKinerjaV2 $laporan): bool
    {
        return (bool) $laporan->delete();
    }
}
