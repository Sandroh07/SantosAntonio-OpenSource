<?php
declare(strict_types=1);
require_once __DIR__.'/Support.php';
env_load(dirname(__DIR__).'/.env');
date_default_timezone_set(config('APP_TIMEZONE','Africa/Luanda'));
if(PHP_SAPI!=='cli') {
    ini_set('session.use_strict_mode','1');
    session_name('angolapayroll_session');
    session_set_cookie_params(['httponly'=>true,'secure'=>filter_var(config('SESSION_SECURE','1'),FILTER_VALIDATE_BOOL),'samesite'=>'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, max-age=0');
}
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Payroll.php';
