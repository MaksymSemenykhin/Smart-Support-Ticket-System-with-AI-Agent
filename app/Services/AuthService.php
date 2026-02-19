<?php

namespace App\Services;

use App\Actions\Auth\LoginUserAction;

class AuthService
{
    public function __construct(
        private readonly LoginUserAction $loginUser,
        private readonly UserService $users,
    ) {}

    /**
     * @return array{0:\App\Models\User,1:string}
     */
    public function register(string $name, string $email, string $password): array
    {
        $user = $this->users->create(name: $name, email: $email, password: $password);

        $token = $user->createToken('api-token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * @return array{0:\App\Models\User,1:string}
     */
    public function login(string $email, string $password): array
    {
        return $this->loginUser->execute(email: $email, password: $password);
    }
}
