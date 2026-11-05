<?php
declare(strict_types=1);
function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function moneyToMinor(string $str): int {
    $s = trim(str_replace(' ', '', $str));
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $s)) throw new InvalidArgumentException('Enter a valid non-negative amount, e.g. 12.50.');
    [$whole, $frac] = array_pad(explode('.', $s, 2), 2, '');
    return (int)$whole * 100 + (int)str_pad($frac, 2, '0');
}
function minorToMoney(int $cents): string { return number_format($cents / 100, 2, '.', ','); }
function setting(): array {
    static $settings = null;
    if ($settings === null) $settings = one('SELECT * FROM settings WHERE id = 1') ?? [];
    return $settings;
}
function ccy(int $cents): string { return h(setting()['currency'] ?? 'AOA') . ' ' . minorToMoney($cents); }
function token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Security token expired. Refresh the page and try again.');
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function requireUser(): void { if (!user()) { header('Location: ?p=login'); exit; } }
function requireAdmin(): void { requireUser(); if (user()['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); } }
function go(string $page): never { header('Location: ?p=' . rawurlencode($page)); exit; }
function flash(string $message, string $type = 'info'): void { $_SESSION['flash'] = [$message,$type]; }
function loginUser(string $email, string $password): bool {
    $since = time() - 900;
    $_SESSION['attempts'] = array_values(array_filter($_SESSION['attempts'] ?? [], fn($t) => $t > $since));
    if (count($_SESSION['attempts']) >= 6) throw new RuntimeException('Too many attempts. Try again later.');
    $u = one('SELECT id,name,email,role,password_hash FROM users WHERE email=? AND active=1', [$email]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION['attempts'][] = time(); return false;
    }
    session_regenerate_id(true);
    unset($u['password_hash']); $_SESSION['user'] = $u; $_SESSION['attempts'] = [];
    return true;
}
function saleTax(int $taxableMinor, int $rateBasisPoints): int {
    if ($taxableMinor < 0 || $rateBasisPoints < 0 || $rateBasisPoints > 10000) throw new InvalidArgumentException('Invalid tax parameters.');
    return intdiv($taxableMinor * $rateBasisPoints + 5000, 10000);
}
function safeCsv(string $value): string {
    if (preg_match('/^[\s]*[=+@\-]/u', $value)) return "'" . $value;
    return $value;
}
function pageTop(string $title, string $current = ''): void {
    $biz = setting(); $name = h($biz['business_name'] ?? 'UniversalPOS');
    $loggedIn = user();
    $titleSafe = h($title);
    echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$titleSafe} | UniversalPOS</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/app.css"></head><body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark"><div class="container-fluid"><a class="navbar-brand fw-bold" href="?p=dashboard">◈ UniversalPOS <span class="small text-info">{$name}</span></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
<div id="nav" class="collapse navbar-collapse"><ul class="navbar-nav ms-auto gap-lg-2">
HTML;
    if ($loggedIn) {
        $links = ['dashboard'=>'Overview','pos'=>'Point of sale','products'=>'Products','sales'=>'Sales','customers'=>'Customers'];
        if ($loggedIn['role'] === 'admin') $links += ['users'=>'Team','settings'=>'Business setup'];
        foreach($links as $slug => $label) {
            $active = $current === $slug ? 'active' : '';
            echo '<li class="nav-item"><a class="nav-link ' . $active . '" href="?p=' . h($slug) . '">' . h($label) . '</a></li>';
        }
        echo '<li class="nav-item"><form method="post"><input type="hidden" name="csrf" value="'. h(token()) .'"><button name="action" value="logout" class="btn btn-sm btn-outline-light mt-1">Logout</button></form></li>';
    } else echo '<li class="nav-item"><a class="nav-link" href="?p=login">Sign in</a></li>';
    echo '</ul></div></div></nav><main class="container py-4">';
    if (!empty($_SESSION['flash'])) {
        [$message,$type] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-' . h(in_array($type,['success','danger','warning','info'])?$type:'info') . '">' . h($message) . '</div>';
    }
}
function pageBottom(): void {
    echo '</main><footer class="py-4 border-top container small text-secondary d-flex flex-wrap justify-content-between gap-2"><span>UniversalPOS · Open source under MIT · Not a certified tax-invoicing system</span><span>Voluntary support: RedotPay <b>1612817131</b> · Airtm <b>santosantonio.eng@gmail.com</b> · <a href="https://santosantonio.it.ao/" target="_blank" rel="noopener noreferrer">santosantonio.it.ao</a></span></footer>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}
