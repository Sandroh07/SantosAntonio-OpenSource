<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
echo "UniversalPOS diagnostic\nPHP: ".PHP_VERSION."\n";
foreach (['pdo_mysql','mbstring','openssl','json'] as $ext) echo $ext.': '.(extension_loaded($ext)?'OK':'MISSING')."\n";
echo 'Environment file: '.(is_file(APP_ROOT.'/.env')?'present':'MISSING')."\n";
try {
    $ok=one('SELECT VERSION() server_version');
    echo 'DB connection: OK ('.($ok['server_version']??'unknown').")\n";
    echo 'Schema: '.(one('SELECT COUNT(*) n FROM settings')!==null?'OK':'MISSING')."\n";
} catch (Throwable $e) {
    echo "DB connection or schema: FAILED\n";
    exit(1);
}
