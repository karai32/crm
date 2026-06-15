<?php

class AuthService
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function completeLogin(array $user): void
    {
        $this->users->updateLastLogin((int) $user['id']);
        Auth::login($user);
    }

}
