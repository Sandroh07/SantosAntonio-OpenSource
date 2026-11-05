<?php
declare(strict_types=1);
final class Database {
    private static ?PDO $pdo = null;
    public static function connection(): PDO {
        if (self::$pdo) return self::$pdo;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', config('DB_HOST','127.0.0.1'), (int)config('DB_PORT','3306'), config('DB_NAME','smart_inventory'));
        return self::$pdo = new PDO($dsn, config('DB_USER'), config('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
