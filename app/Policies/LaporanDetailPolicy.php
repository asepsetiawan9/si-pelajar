<?php

namespace App\Policies;

use App\Models\LaporanDetail;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaporanDetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, LaporanDetail $laporanDetail): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdminKecamatan() || $user->isCamat()) {
            return true;
        }

        // Kasi can only view their own unit's report
        return (int) $user->unit_organisasi_id === (int) $laporanDetail->unit_organisasi_id;
    }

    public function create(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // Superadmin and Admin Kecamatan (Sekmat) can create for any unit (delegated input)
        // Kasi can create for their assigned unit
        return $user->isSuperAdmin() || $user->isAdminKecamatan() || ($user->isKasi() && ! empty($user->unit_organisasi_id));
    }

    public function update(User $user, LaporanDetail $laporanDetail): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // Superadmin and Admin Kecamatan can update (delegated input or correction)
        if ($user->isSuperAdmin() || $user->isAdminKecamatan()) {
            return true;
        }

        // Kasi can only update their own unit
        if ((int) $user->unit_organisasi_id !== (int) $laporanDetail->unit_organisasi_id) {
            return false;
        }

        // Must not be locked
        return ! $laporanDetail->isLocked();
    }

    public function delete(User $user, LaporanDetail $laporanDetail): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdminKecamatan()) {
            return true;
        }

        // Kasi can only delete their own draft
        return (int) $user->unit_organisasi_id === (int) $laporanDetail->unit_organisasi_id
            && $laporanDetail->status === 'draft';
    }
}
