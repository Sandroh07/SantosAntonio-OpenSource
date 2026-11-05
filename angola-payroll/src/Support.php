<?php
declare(strict_types=1);

function env_load(string $path): void {
    if (!is_readable($path)) { return; }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) { continue; }
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) { putenv(trim($key).'='.trim(trim($value), "\"'")); }
    }
}
function config(string $key, string $fallback=''): string { return (string)(getenv($key) !== false ? getenv($key) : $fallback); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $page, string $message=''): never {
    if ($message !== '') { $_SESSION['flash'] = $message; }
    header('Location: ?page='.rawurlencode($page), true, 303); exit;
}
function csrf_token(): string {
    if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419); throw new RuntimeException('Expired or invalid security token.');
    }
}
function qty_to_milli(string $value, bool $allowNegative=false): int {
    $v = trim($value);
    if (!preg_match('/^(-?)(\d{1,9})(?:\.(\d{1,3}))?$/D', $v, $m)) { throw new InvalidArgumentException('Quantity requires at most three decimal places (e.g. 12.500).'); }
    if ($m[1] === '-' && !$allowNegative) { throw new InvalidArgumentException('Quantity cannot be negative.'); }
    $num = (int)$m[2] * 1000 + (int)str_pad($m[3] ?? '',3,'0');
    return $m[1] === '-' ? -$num : $num;
}
function milli_to_qty(int $n): string { return ($n < 0 ? '-' : '').intdiv(abs($n),1000).'.'.str_pad((string)(abs($n)%1000),3,'0',STR_PAD_LEFT); }
function valid_date(?string $value): ?string {
    if ($value === null || $value === '') return null;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    if (!$d || $d->format('Y-m-d') !== $value) throw new InvalidArgumentException('Use a valid ISO date YYYY-MM-DD.');
    return $value;
}
function post_string(string $name,int $max=160): string {
    $value=trim((string)($_POST[$name] ?? ''));
    if (strlen($value)>$max) throw new InvalidArgumentException('Field too long: '.$name);
    return $value;
}
