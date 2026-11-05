<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if ($argc < 3) { fwrite(STDERR, "Usage: php scripts/create-admin.php admin@example.com 'Full Name'\nPassword is requested interactively.\n"); exit(1); }
$email = strtolower(trim($argv[1])); $name = trim($argv[2]);
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $name==='') exit("Invalid email/name\n");
if (!function_exists('shell_exec')) { fwrite(STDERR,"Password must be entered using a non-echoing terminal.\n"); exit(1); }
if (stripos(PHP_OS,'WIN')!==0) {
    fwrite(STDOUT,'Admin password: ');
    shell_exec('stty -echo');
    $password = trim(fgets(STDIN) ?: '');
    shell_exec('stty echo'); fwrite(STDOUT,"\n");
} else { fwrite(STDOUT,'Admin password: '); $password = trim(fgets(STDIN) ?: ''); }
if (strlen($password)<12) exit("Password must have at least 12 characters.\n");
if (one('SELECT id FROM users WHERE email=?',[$email])) exit("User already exists.\n");
run('INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);
echo "Admin created.\n";
