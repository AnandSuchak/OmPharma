<?php

namespace App\Services;

use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Attempt to authenticate a user and log them in.
     *
     * @throws ValidationException
     */
    public function login(array $credentials): bool
    {
        $user = $this->authRepository->findUserByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Throw a validation exception if login fails.
            // This is the standard Laravel way to handle failed logins.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Use Laravel's global auth() helper to log the user in.
        auth()->login($user, $credentials['remember'] ?? false);

        return true;
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        auth()->logout();
    }
}
