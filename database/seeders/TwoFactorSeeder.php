<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Services\Security\TwoFactorAuthService;

class TwoFactorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $twoFactorService = app(TwoFactorAuthService::class);

        // Enable 2FA for admin users
        $adminUsers = User::where('user_type', 'admin')->get();
        foreach ($adminUsers as $user) {
            if (!$twoFactorService->isEnabled($user)) {
                // Use a standard test secret for local development
                $twoFactorService->enableWithSecret($user, 'JBSWY3DPEHPK3PXP');
            }
        }

        // Enable 2FA for teacher users (optional)
        $teacherUsers = User::where('user_type', 'teacher')->get();
        foreach ($teacherUsers as $user) {
            if (!$twoFactorService->isEnabled($user)) {
                $twoFactorService->enableWithSecret($user);
            }
        }

        $this->command->info('2FA has been enabled for admin and teacher users.');
        $this->command->info('Test 2FA code: 123456 (any 6-digit code works in local environment)');
    }
}
