<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private PDO $pdo) {}

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function exists(string $username, string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute(['username' => $username, 'email' => $email]);
        return (bool) $stmt->fetchColumn();
    }

    /** Returns the new user's ID. $passwordHash must come from password_hash(). */
    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
        );
        $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }
}
