<?php

namespace App\Policies;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaporanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdminKecamatan() || $user->isCamat();
    }

    public function view(User $user, Laporan $laporan): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdminKecamatan() || $user->isCamat();
    }

    public function create(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdminKecamatan();
    }

    public function update(User $user, Laporan $laporan): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // Camat can update (sahkan / kembalikan), Sekmat/Superadmin can update
        return $user->isSuperAdmin() || $user->isAdminKecamatan() || $user->isCamat();
    }

    public function delete(User $user, Laporan $laporan): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isSuperAdmin() && $laporan->status === 'draft';
    }
}
