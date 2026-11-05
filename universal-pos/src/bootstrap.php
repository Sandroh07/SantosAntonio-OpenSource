<?php
declare(strict_types=1);
const APP_ROOT = __DIR__ . '/..';
$dotenv = APP_ROOT . '/.env';
if (is_readable($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $k)) continue;
        if (getenv($k) === false) putenv($k . '=' . trim(trim($v), "\"'"));
    }
}
function env(string $key, string $default = ''): string { $x = getenv($key); return $x === false ? $default : $x; }
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Luanda'));
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || env('APP_HTTPS') === '1';
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    session_name('UNIPOSSESSID');
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
}
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/Pos.php';
