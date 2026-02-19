<?php

namespace App\Actions\Auth;

use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    /**
     * @return array{0:\App\Models\User,1:string}
     */
    public function execute(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('api.auth.invalid_credentials'),
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [$user, $token];
    }
}
