<?php

declare(strict_types=1);

// Never leak PHP warnings/notices to the browser — log them server-side instead,
// and show them to the logged-in admin instead. See errors.php for why this is
// not simply display_errors=1.
//
// require_once, not require: errors.php declares functions, and the panel's
// bootstrap chain (index.php -> auth.php -> session.php) can be entered more
// than once from the same request. A second plain require is a fatal
// "cannot redeclare", which is a spectacularly unhelpful way to lose a page.
require_once __DIR__ . '/errors.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
panel_errors_init();

// Declared floor, checked rather than assumed. The panel itself is written to
// PHP 8.0, but the shipped tooling (scripts/backup-messages.php) uses 8.1-only
// syntax, and DEPLOY.md requires 8.1 — refusing an older host here with a
// readable message beats a parse error in a backup task later. The array_is_list
// call below is still guarded so the panel keeps working on 8.0 if the check is
// ever lowered back.
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("این پنل به PHP 8.1 یا بالاتر نیاز دارد. نسخهٔ فعلی سرور: " . PHP_VERSION . "\n");
}

// Shared session bootstrap for the whole panel (login.php, auth.php, logout.php).
// Hard-coding the admin folder path breaks the session the moment it sits one
// level deeper, so both the cookie path and this helper derive it from
// SCRIPT_NAME: /admin/ in production, /site/admin/ in local dev — same code.
function admin_cookie_path(): string
{
    // Always scope the session cookie to the site root. Scoping it to the
    // admin subfolder (especially one whose name contains spaces or other
    // reserved characters) caused browsers to silently drop the cookie on the
    // POST that follows the login form, which surfaced as a CSRF failure.
    return '/';
}

if (session_status() === PHP_SESSION_NONE) {
    // Ensure a writable, absolute session save path. A relative path
    // (e.g. "\xampp\tmp") resolves against the process working directory,
    // which differs between Apache and the PHP built-in server and can
    // silently break session persistence.
    $savePath = ini_get('session.save_path');
    if ($savePath === '' || !is_dir($savePath)) {
        $savePath = sys_get_temp_dir();
    }
    session_save_path($savePath);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // Behind a reverse proxy (cPanel/nginx), HTTPS is signalled via this header
    // rather than $_SERVER['HTTPS']; trust it only for the secure-cookie flag so
    // the session cookie is never sent in clear text on production.
    if (!$isHttps && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $isHttps = stripos(trim($_SERVER['HTTP_X_FORWARDED_PROTO']), 'https') === 0;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => admin_cookie_path(),
        'secure'   => $isHttps,  // true on production (HTTPS-only), false when testing over plain http locally
        'httponly' => true,     // not readable from JS — blocks session-stealing via XSS
        'samesite' => 'Lax', // Lax keeps CSRF protection while allowing the login form POST to carry the cookie reliably
    ]);
    session_start();
}

// One CSRF token per session, reused across all forms on a page load.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

// Constant-time compare that cannot be made to fatal.
//
// hash_equals() declares `string $user_string`, so a POST of
// `csrf_token[]=x` — which PHP parses as an ARRAY — raised an uncaught
// TypeError: a 500 with a stack trace instead of the intended 403. On
// login.php's pre-authentication guard that was reachable by any anonymous
// visitor, and on auth.php's require_valid_csrf() it guards every
// actions.php branch. Scalars are cast, anything else is rejected outright.
function secure_equals(string $known, mixed $given): bool
{
    if (is_array($given) || is_object($given) || is_null($given) || is_bool($given)) {
        return false;
    }
    return hash_equals($known, (string)$given);
}