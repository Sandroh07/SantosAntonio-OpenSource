<?php
declare(strict_types=1);
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_user(): array { if (!current_user()) redirect('login'); return current_user(); }
function require_role(array $roles): array {
    $u = require_user(); if (!in_array($u['role'], $roles, true)) { http_response_code(403); throw new RuntimeException('Access denied.'); } return $u;
}
function login(PDO $db, string $email, string $password): bool {
    $s=$db->prepare('SELECT id,name,email,password_hash,role,active FROM users WHERE email=? LIMIT 1');
    $s->execute([$email]); $u=$s->fetch();
    if (!$u || !(int)$u['active'] || !password_verify($password, $u['password_hash'])) {
        password_verify($password,'$2y$10$Zl0EcmnFW.TQ8SIAEOT5SezVvm82HKLJ1C1nQJHLZu3M9Zv.Oe0QK');
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
    return true;
}
function logout(): void { $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',time()-3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }
