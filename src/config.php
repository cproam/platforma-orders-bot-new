<?php
// config.php
$env = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_TYPED) ?: [];

define('BOT_TOKEN', (string)($env['BOT_TOKEN'] ?? ''));
define('ACCOUNTANT_ID', (int)($env['ACCOUNTANT_ID'] ?? 0));
define('DIRECTOR_ID', (int)($env['DIRECTOR_ID'] ?? 0));
define('ROP_ID', (int)($env['ROP_ID'] ?? 0));
define('DB_PATH', dirname(__DIR__) . '/database.sqlite');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');

// Разбор списка разрешённых менеджеров
$allowedRaw = (string)($env['ALLOWED_MANAGER_IDS'] ?? '');
$allowed = array_values(array_filter(array_map(function ($v) {
    $v = trim($v);
    return ctype_digit($v) ? (int)$v : null;
}, explode(',', $allowedRaw)), fn($v) => !is_null($v)));

define('ALLOWED_MANAGERS', $allowed);

/** Утилита: проверка доступа менеджера */
function is_manager_allowed(int $managerId): bool
{
    return in_array($managerId, ALLOWED_MANAGERS, true);
}
