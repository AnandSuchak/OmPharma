<?php

namespace App\Repositories;

use App\Interfaces\AuthRepositoryInterface;
use App\Models\User;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
