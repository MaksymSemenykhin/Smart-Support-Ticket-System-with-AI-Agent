<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function create(string $name, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
