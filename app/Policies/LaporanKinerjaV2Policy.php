<?php

namespace App\Policies;

use App\Models\LaporanKinerjaV2;
use App\Models\User;

class LaporanKinerjaV2Policy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, LaporanKinerjaV2 $laporan): bool
    {
        if ($user->isAdminKecamatan() || $user->isCamat()) {
            return true;
        }

        if ($user->isKasi()) {
            return $user->unit_organisasi_id === $laporan->unit_organisasi_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdminKecamatan() || $user->isKasi();
    }

    public function update(User $user, LaporanKinerjaV2 $laporan): bool
    {
        if ($user->isAdminKecamatan()) {
            return true;
        }

        if ($user->isKasi()) {
            if ($user->unit_organisasi_id !== $laporan->unit_organisasi_id) {
                return false;
            }

            // Kasi hanya bisa mengedit jika masih draft atau ditolak (perlu revisi)
            return in_array($laporan->status, ['draft', 'ditolak']);
        }

        return false;
    }

    public function delete(User $user, LaporanKinerjaV2 $laporan): bool
    {
        if ($user->isAdminKecamatan()) {
            return true;
        }

        if ($user->isKasi()) {
            return $user->unit_organisasi_id === $laporan->unit_organisasi_id && $laporan->isDraft();
        }

        return false;
    }

    public function verifikasi(User $user): bool
    {
        return $user->isAdminKecamatan() || $user->isSuperAdmin();
    }
}
