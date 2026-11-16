<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

// Logout is a state change, so it must be a POST carrying a valid CSRF token —
// never a plain GET link (a cross-site <img> could otherwise force a logout).
// CSRF is validated inline (instead of via auth.php) so session.php is never
// double-included within the same request.
csrf_token();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}
if (!secure_equals(csrf_token(), $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('درخواست نامعتبر است (CSRF). صفحه را دوباره بارگذاری کنید.');
}

// Full teardown: clear the in-memory session, expire the cookie with the same
// parameters login.php issued, then destroy the server-side session record.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: login.php');
exit;