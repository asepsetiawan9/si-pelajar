<?php

namespace App\Services;

use App\Models\LaporanKinerjaV2;
use App\Models\User;
use App\Repositories\LaporanKinerjaV2Repository;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LaporanKinerjaV2Service
{
    public function __construct(
        protected LaporanKinerjaV2Repository $repository
    ) {}

    public function kirimLaporan(LaporanKinerjaV2 $laporan, User $user): LaporanKinerjaV2
    {
        if (! in_array($laporan->status, ['draft', 'ditolak'])) {
            throw new InvalidArgumentException('Hanya laporan berstatus draft atau ditolak yang dapat dikirim.');
        }

        return DB::transaction(function () use ($laporan) {
            $updated = $this->repository->update($laporan, [
                'status' => 'diajukan',
                'submitted_at' => Carbon::now(),
            ]);

            $this->notifyVerifiersAboutSubmission($updated);

            return $updated;
        });
    }

    public function setujuiLaporan(LaporanKinerjaV2 $laporan, User $verifier, ?string $catatan = null): LaporanKinerjaV2
    {
        if ($laporan->status !== 'diajukan') {
            throw new InvalidArgumentException('Hanya laporan berstatus diajukan yang dapat disetujui.');
        }

        return DB::transaction(function () use ($laporan, $verifier, $catatan) {
            $updated = $this->repository->update($laporan, [
                'status' => 'disetujui',
                'verified_by' => $verifier->id,
                'verified_at' => Carbon::now(),
                'catatan_verifikasi' => $catatan,
            ]);

            $this->notifyAuthorAboutStatus($updated, 'disetujui');

            return $updated;
        });
    }

    public function kembalikanLaporan(LaporanKinerjaV2 $laporan, User $verifier, string $catatan): LaporanKinerjaV2
    {
        if ($laporan->status !== 'diajukan') {
            throw new InvalidArgumentException('Hanya laporan berstatus diajukan yang dapat dikembalikan.');
        }

        return DB::transaction(function () use ($laporan, $verifier, $catatan) {
            $updated = $this->repository->update($laporan, [
                'status' => 'ditolak',
                'verified_by' => $verifier->id,
                'verified_at' => Carbon::now(),
                'catatan_verifikasi' => $catatan,
            ]);

            $this->notifyAuthorAboutStatus($updated, 'ditolak', $catatan);

            return $updated;
        });
    }

    protected function notifyVerifiersAboutSubmission(LaporanKinerjaV2 $laporan): void
    {
        $verifiers = User::query()
            ->whereIn('role', ['admin_kecamatan', 'superadmin'])
            ->where('is_active', true)
            ->get();

        $unitName = $laporan->unitOrganisasi?->nama_unit ?? 'Unit';

        foreach ($verifiers as $verifier) {
            Notification::make()
                ->title('Laporan Kinerja Unit Masuk')
                ->body("Laporan {$unitName} telah dikirim oleh {$laporan->nama_pejabat} dan menunggu verifikasi.")
                ->info()
                ->sendToDatabase($verifier);
        }
    }

    protected function notifyAuthorAboutStatus(LaporanKinerjaV2 $laporan, string $status, ?string $catatan = null): void
    {
        $author = $laporan->user;
        if (! $author) {
            return;
        }

        $isApproved = $status === 'disetujui';
        $title = $isApproved ? 'Laporan Kinerja Disetujui' : 'Laporan Kinerja Perlu Revisi';
        $body = $isApproved
            ? "Laporan kinerja {$laporan->judul_pelaporan} telah disetujui."
            : "Laporan kinerja {$laporan->judul_pelaporan} dikembalikan untuk direvisi: {$catatan}";

        $notification = Notification::make()->title($title)->body($body);

        if ($isApproved) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->sendToDatabase($author);
    }
}
