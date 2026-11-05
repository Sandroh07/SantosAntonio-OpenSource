<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require dirname(__DIR__).'/src/bootstrap.php';
if ($argc!==4){fwrite(STDERR,"Usage: php scripts/create-admin.php 'Name' email@example.com 'StrongPassword'
");exit(1);}
[$script,$name,$email,$password]=$argv;
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12){fwrite(STDERR,"Use valid email and password of at least 12 chars.
");exit(1);}
$s=Database::connection()->prepare("INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,'admin')");
$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);echo "Admin created. Remove the password from shell history if needed.
";
