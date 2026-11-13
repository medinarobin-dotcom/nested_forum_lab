<?php
declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    /** Create a PDO connection with safe defaults. */
    public static function connect(array $db): PDO
    {
        $pdo = new PDO($db['dsn'], $db['user'] ?? null, $db['password'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ]);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            // Store and read every timestamp in UTC.
            $pdo->exec("SET time_zone = '+00:00'");
        }

        return $pdo;
    }
}
