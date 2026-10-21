<?php

namespace App\Services;

use App\Enums\Roles;
use App\Enums\Status;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Interfaces\SocialAuthInterface;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService implements SocialAuthInterface
{
    public function authenticate(string $token): User
    {
        $googleUser = Socialite::driver('google')->userFromToken($token);

        return DB::transaction(function () use ($googleUser) {

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->firstOr(function () use ($googleUser) {
                    // **Este código só roda se o usuário NÃO EXISTE**
                    $newUser = User::create([
                        'google_id' => $googleUser->getId(),
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'status_id' => Status::ACTIVE->value,
                        'password' => null,
                    ]);

                    $newUser->roles()->attach(Roles::USER->value);
                    Log::info("Novo usuário registrado via Google e permissão 'usuario' atribuída.", ['user_id' => $newUser->id]);

                    return $newUser;
                });

            if (!$user->wasRecentlyCreated) {
                $user->update([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                ]);
            }
            Log::info("Usuário logado via Google.", ['user_name' => $user->name]);
            return $user;
        });
    }
}
