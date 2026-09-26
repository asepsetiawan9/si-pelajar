<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');
        $response->assertSuccessful();
    }

    public function test_superadmin_can_access_dashboard(): void
    {
        $user = User::where('email', 'superadmin@malangbong.go.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertSuccessful();
    }

    public function test_all_official_roles_can_access_dashboard(): void
    {
        $emails = [
            'superadmin@malangbong.go.id',
            'sekmat@malangbong.go.id',
            'camat@malangbong.go.id',
            'kasi.pelayanan@malangbong.go.id',
            'kasi.pemerintahan@malangbong.go.id',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "User {$email} must exist");

            $response = $this->actingAs($user)->get('/admin');
            $this->assertEquals(
                200,
                $response->status(),
                "User {$email} with role {$user->role} should be able to access /admin"
            );
        }
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::where('email', 'kasi.pelayanan@malangbong.go.id')->first();
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertForbidden();
    }

    public function test_superadmin_can_view_user_management(): void
    {
        $superadmin = User::where('email', 'superadmin@malangbong.go.id')->first();
        $response = $this->actingAs($superadmin)->get('/admin/users');
        $response->assertSuccessful();
    }
}
