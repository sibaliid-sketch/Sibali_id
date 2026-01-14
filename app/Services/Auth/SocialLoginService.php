<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserSocialAccount;
use Laravel\Socialite\Contracts\User as SocialUser;

class SocialLoginService
{
    public function findOrCreateUser(SocialUser $socialUser, string $provider): User
    {
        $socialAccount = UserSocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        // Check if user exists with this email
        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(), // Social logins are pre-verified
                'password' => bcrypt(str_random(16)), // Random password
                'user_type' => 'student', // Default, can be changed later
            ]);
        }

        // Link social account
        $this->linkProvider($user, $provider, $socialUser->getId());

        return $user;
    }

    public function linkProvider(User $user, string $provider, string $providerId): void
    {
        UserSocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_id' => $providerId,
                'avatar' => null, // Could be set from social user
            ]
        );
    }

    public function unlinkProvider(User $user, string $provider): void
    {
        UserSocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();
    }

    public function isEmailReused(string $email, string $provider): bool
    {
        // Check if email is used by another provider
        $existingAccount = UserSocialAccount::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->where('provider', '!=', $provider)->first();

        return $existingAccount !== null;
    }

    public function getLinkedProviders(User $user): array
    {
        return $user->socialAccounts->pluck('provider')->toArray();
    }
}
