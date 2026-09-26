<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan seluruh Spatie Role terdaftar
        $roles = [
            'superadmin',
            'admin_kecamatan',
            'kasi',
            'camat',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Daftarkan permissions dasar sistem
        $basePermissions = [
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'restore_user',
            'restore_any_user',
            'replicate_user',
            'reorder_user',
            'delete_user',
            'delete_any_user',
            'force_delete_user',
            'force_delete_any_user',
        ];

        foreach ($basePermissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        // Berikan seluruh hak akses ke superadmin
        $superadminRole = Role::where('name', 'superadmin')->first();
        if ($superadminRole) {
            $allPermissions = Permission::all();
            $superadminRole->syncPermissions($allPermissions);
        }

        // Berikan akses kelola sasaran strategis & rencana aksi ke admin_kecamatan (Sekmat)
        $adminKecamatanRole = Role::where('name', 'admin_kecamatan')->first();
        if ($adminKecamatanRole) {
            $adminKecamatanPermissions = Permission::where(function ($query) {
                $query->where('name', 'like', '%sasaran%')
                    ->orWhere('name', 'like', '%rencana%');
            })->get();
            $adminKecamatanRole->syncPermissions($adminKecamatanPermissions);
        }

        // 2. Daftar akun resmi Kecamatan Malangbong
        $users = [
            [
                'name' => 'Superadmin Pengelola IT',
                'nip' => null,
                'email' => 'superadmin@malangbong.go.id',
                'role' => 'superadmin',
                'jabatan' => 'Pengelola IT / Administrator Sistem',
                'unit_organisasi_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Sekretaris Camat Malangbong',
                'nip' => null,
                'email' => 'sekmat@malangbong.go.id',
                'role' => 'admin_kecamatan',
                'jabatan' => 'Sekretaris Camat',
                'unit_organisasi_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'H. Robiul Awaludin, S.Sos., A.Kp., MM',
                'nip' => '19700523199303 1 005',
                'email' => 'camat@malangbong.go.id',
                'role' => 'camat',
                'jabatan' => 'Plt. Camat Malangbong',
                'unit_organisasi_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Kepala Seksi Pelayanan',
                'nip' => '19801214201410 2 002',
                'email' => 'kasi.pelayanan@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kepala Seksi Pelayanan',
                'unit_organisasi_id' => 7, // Seksi Pelayanan
                'is_active' => true,
            ],
            [
                'name' => 'Kepala Seksi Pemerintahan',
                'nip' => '19750810199903 1 004',
                'email' => 'kasi.pemerintahan@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kepala Seksi Pemerintahan',
                'unit_organisasi_id' => 3, // Seksi Pemerintahan
                'is_active' => true,
            ],
            [
                'name' => 'Asep Saepuloh, S.IP',
                'nip' => '19790615200801 1 008',
                'email' => 'kasubag.umum@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kasubag Umum, Perencanaan Evaluasi dan Pelaporan',
                'unit_organisasi_id' => 1, // Subbag Umum
                'is_active' => true,
            ],
            [
                'name' => 'Dewi Sartika, SE',
                'nip' => '19830422201001 2 015',
                'email' => 'kasubag.keuangan@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kasubag Keuangan dan BMD',
                'unit_organisasi_id' => 2, // Subbag Keuangan
                'is_active' => true,
            ],
            [
                'name' => 'H. Dedi Supriyadi, S.Ag',
                'nip' => '19740312200212 1 003',
                'email' => 'kasi.kesra@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kepala Seksi Kesejahteraan Masyarakat',
                'unit_organisasi_id' => 4, // Seksi Kesra
                'is_active' => true,
            ],
            [
                'name' => 'Iwan Setiawan, S.Sos',
                'nip' => '19810517200902 1 005',
                'email' => 'kasi.pmd@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kepala Seksi Pemberdayaan Masyarakat dan Desa',
                'unit_organisasi_id' => 5, // Seksi PMD
                'is_active' => true,
            ],
            [
                'name' => 'Agus Ridwan, S.IP',
                'nip' => '19781104200701 1 009',
                'email' => 'kasi.trantib@malangbong.go.id',
                'role' => 'kasi',
                'jabatan' => 'Kepala Seksi Ketentraman dan Ketertiban',
                'unit_organisasi_id' => 6, // Seksi Trantib
                'is_active' => true,
            ],
        ];

        $defaultPassword = Hash::make('Password123!');

        foreach ($users as $userData) {
            $role = $userData['role'];
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => $defaultPassword])
            );

            // Sinkronkan peran Spatie Permission
            $user->syncRoles([$role]);
        }
    }
}
