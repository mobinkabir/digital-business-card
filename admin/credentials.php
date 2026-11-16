<?php

declare(strict_types=1);

/**
 * Admin credentials live in auth_hash.php as
 * ['username' => ..., 'hash' => ..., 'display_name' => ...].
 *
 * Kept out of config.php on purpose: that file is safe to inspect or ship to a
 * build script, while this one holds the password hash. The file is generated
 * and rewritten at runtime by the login page / "change password" form, so the
 * hash never has to be produced by hand on the server.
 */
function auth_file(): string
{
    return __DIR__ . '/auth_hash.php';
}

/** @return array{username?: string, hash?: string, display_name?: string} */
function load_auth(): array
{
    $file = auth_file();
    if (!is_file($file)) {
        return [];
    }
    $auth = @include $file;
    return is_array($auth) ? $auth : [];
}

/**
 * Writes the credential fields atomically. atomic_write() is the same
 * tmp+rename helper the rest of the panel uses, so a half-written file can
 * never lock the admin out.
 *
 * $displayName is kept optional so older callers keep working; when omitted
 * the name already on disk is preserved by auth_set_password() and login.php.
 */
function persist_auth(string $username, string $hash, string $displayName = ''): bool
{
    $data = ['username' => $username, 'hash' => $hash];
    if ($displayName !== '') {
        $data['display_name'] = $displayName;
    }
    $php = "<?php\n// Runtime-generated admin credentials — do not edit by hand.\n"
         . 'return ' . var_export($data, true) . ";\n";
    return atomic_write(auth_file(), $php);
}

/** The name shown in the panel / login greeting. */
function auth_display_name(): string
{
    return trim((string)(load_auth()['display_name'] ?? ''));
}

/**
 * Verifies a password against the stored hash.
 *
 * The argument is typed mixed because $_POST values can be arrays
 * (`password[]=x`); password_verify() declares string, and passing an array
 * would be an uncaught TypeError (a 500) instead of a failed check.
 */
function auth_verify(mixed $password): bool
{
    $hash = (string)(load_auth()['hash'] ?? '');
    if ($hash === '' || !is_string($password)) {
        return false;
    }
    return password_verify($password, $hash);
}

/** Re-hashes and stores a new password, keeping username and display name. */
function auth_set_password(string $password): bool
{
    $auth = load_auth();
    $username = (string)($auth['username'] ?? '');
    if ($username === '') {
        return false;
    }
    return persist_auth(
        $username,
        password_hash($password, PASSWORD_BCRYPT),
        (string)($auth['display_name'] ?? '')
    );
}

/** Stores a new display name without changing the password. */
function auth_set_display_name(string $name): bool
{
    $auth = load_auth();
    $username = (string)($auth['username'] ?? '');
    $hash = (string)($auth['hash'] ?? '');
    if ($username === '' || $hash === '') {
        return false;
    }
    return persist_auth($username, $hash, trim($name));
}
