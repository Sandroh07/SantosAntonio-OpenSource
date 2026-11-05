<?php
declare(strict_types=1);
function db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    if (!extension_loaded('pdo_mysql')) throw new RuntimeException('The PHP pdo_mysql extension is required.');
    $host = env('DB_HOST', '127.0.0.1');
    $database = env('DB_NAME', 'universalpos');
    $port = env('DB_PORT', '3306');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $db = new PDO($dsn, env('DB_USER', ''), env('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $db;
}
function one(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->fetch() ?: null;
}
function all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->fetchAll();
}
function run(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->rowCount();
}
