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
}
