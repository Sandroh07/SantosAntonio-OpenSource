<?php
declare(strict_types=1);
require_once __DIR__.'/Support.php';
env_load(dirname(__DIR__).'/.env');
if (PHP_SAPI!=='cli') {
    date_default_timezone_set(config('APP_TIMEZONE','Africa/Luanda'));
    ini_set('session.use_strict_mode','1');
    session_name('smartinventory_session');
    session_set_cookie_params(['httponly'=>true,'secure'=>filter_var(config('SESSION_SECURE','0'),FILTER_VALIDATE_BOOL),'samesite'=>'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Inventory.php';
