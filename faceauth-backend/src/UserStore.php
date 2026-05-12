<?php

declare(strict_types=1);

final class UserStore
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
    }

    public function isInstalled(): bool
    {
        return count($this->all()) > 0;
    }

    public function all(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $raw = file_get_contents($this->path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['users']) || !is_array($decoded['users'])) {
            return [];
        }

        return $decoded['users'];
    }

    public function verify(string $username, string $password): ?array
    {
        foreach ($this->all() as $user) {
            if (($user['username'] ?? '') !== $username) {
                continue;
            }

            $hash = $user['password_hash'] ?? '';
            if (is_string($hash) && password_verify($password, $hash)) {
                return $user;
            }
        }

        return null;
    }

    public function createInitialUsers(string $superadminUser, string $superadminPassword, string $universityAdminUser, string $universityAdminPassword): void
    {
        if ($this->isInstalled()) {
            throw new RuntimeException('User store is already initialized');
        }

        if (trim($superadminUser) === trim($universityAdminUser)) {
            throw new RuntimeException('Superadmin and university admin usernames must be different');
        }

        $this->saveUsers([
            $this->buildUser($superadminUser, $superadminPassword, 'superadmin'),
            $this->buildUser($universityAdminUser, $universityAdminPassword, 'admin'),
        ]);
    }

    public function addUser(string $username, string $password, string $role): void
    {
        $users = $this->all();
        foreach ($users as $user) {
            if (($user['username'] ?? '') === $username) {
                throw new RuntimeException('User already exists');
            }
        }

        $users[] = $this->buildUser($username, $password, $role);
        $this->saveUsers($users);
    }

    private function buildUser(string $username, string $password, string $role): array
    {
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('Username is required');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('Password must contain at least 8 characters');
        }

        if (!in_array($role, ['superadmin', 'admin', 'viewer'], true)) {
            throw new RuntimeException('Invalid role');
        }

        return [
            'username' => $username,
            'role' => $role,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => gmdate('c'),
        ];
    }

    private function saveUsers(array $users): void
    {
        $payload = json_encode(['users' => array_values($users)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            throw new RuntimeException('Could not encode users');
        }

        file_put_contents($this->path, $payload . PHP_EOL, LOCK_EX);
        chmod($this->path, 0660);
    }
}
