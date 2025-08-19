<?php

namespace App\Interfaces;

use App\Models\User;

interface AuthRepositoryInterface
{
    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User;
}
