<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Idle session timeout (sliding): auto-logout after 30 minutes of inactivity.
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Ensure a CSRF token exists (it also may have been created at login).
csrf_token();

// Shared gate for every form POST. session.php's csrf_token() is reused before
// checking so a tampered/expired token can never be compared against null.
function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!secure_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('درخواست نامعتبر است (CSRF). صفحه را دوباره بارگذاری کنید.');
    }
}