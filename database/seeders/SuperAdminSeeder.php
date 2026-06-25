<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminSeeder extends Seeder {
    public function run(): void {
        User::updateOrCreate(
            ['email' => 'superadmin@clothonic.com'],
            [
                'name'              => 'Super Admin',
                'password'          => bcrypt('SuperAdmin@2026'),
                'is_super_admin'    => true,
                'tenant_id'         => null,
                'email_verified_at' => now(),
                'role'              => 'admin',
            ]
        );
        $this->command->info('Super Admin created: superadmin@clothonic.com / SuperAdmin@2026');
    }
}
