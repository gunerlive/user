<?php

namespace ESign;

use PDO;

class Auth
{
    public function __construct(private PDO $db)
    {
    }

    public function attempt(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            Helpers::redirect('login.php');
        }
    }

    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function userName(): string
    {
        return $_SESSION['user_name'] ?? '';
    }

    public static function hasAnyUser(PDO $db): bool
    {
        return (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    }

    public function listUsers(): array
    {
        return $this->db->query('SELECT id, full_name, email, is_active, created_at FROM users ORDER BY created_at ASC')->fetchAll();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createUser(string $fullName, string $email, string $password): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)');
        $stmt->execute([
            'full_name'     => $fullName,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function setUserActive(int $userId, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $userId]);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return false;
        }

        $update = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $update->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
        return true;
    }
}
