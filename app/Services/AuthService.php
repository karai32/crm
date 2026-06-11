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

    public function createFirstAdmin(): string
    {
        $email = 'artemtepliakov02@gmail.com';

        if ($this->users->adminExists()) {
            return 'Admin user already exists.';
        }

        $role = $this->users->findRoleByName('admin');

        if ($role === null) {
            return 'Admin role was not found. Please import the database schema first.';
        }

        $this->users->create([
            'role_id' => $role['id'],
            'name' => 'Admin',
            'email' => $email,
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'is_active' => 1,
        ]);

        return 'Admin user created. You can now log in.';
    }
}
