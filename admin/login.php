<?php
// Debug mode is off — if you ever see a blank white page or a generic
// server error here, check your host's PHP error log (cPanel -> Metrics
// -> Errors) rather than re-enabling display_errors long-term.
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/credentials.php';
require_once __DIR__ . '/secrets_store.php';
// Per-session CSRF token for the login form itself (login CSRF hardening).
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
csrf_token();

$error = '';

// ---- optional secret-key gate ----
// If a secret key has been set from the panel's security settings, this
// page requires ?key=... to even load. Anyone (or any bot) hitting
// login.php without the right key sees a plain 404, as if the page didn't
// exist — the same effect as renaming wp-admin on WordPress.
//
// This deliberately does NOT remember your browser across visits — every
// request (including the login form's own POST) must carry the current
// key, either as ?key=... or as a hidden form field. An earlier "remember
// this browser for 30 days" cookie was removed because it let an
// already-rotated old key keep working.
$gateKey = secrets_get('login_gate');
if ($gateKey !== '') {
    $providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
    // secure_equals, not hash_equals((string)$x): `?key[]=x` parses to an array,
    // and casting one emits an "Array to string conversion" warning on this
    // pre-auth path instead of simply failing the comparison.
    if (!secure_equals($gateKey, $providedKey)) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:80px 20px;color:#555;"><h1>404</h1><p>Not Found</p></body></html>';
        exit;
    }
}
$keyParam = $gateKey !== '' ? ('?key=' . urlencode($gateKey)) : '';

// ---- brute-force protection, keyed by IP address (not by session) ----
// A session-based counter is trivially bypassed by any automated tool that
// simply doesn't send cookies between attempts. Tracking by IP in a file
// closes that gap. (IPs are stored hashed, not in plain text.)

/**
 * Best-effort client IP.
 *
 * Under Cloudflare / a cPanel or nginx front end, REMOTE_ADDR is the proxy, not
 * the visitor — keying the counter on it pools every visitor on the site into
 * one bucket, so five typos by a passer-by would lock the real admin out. The
 * forwarded headers are only consulted when the direct address is a known
 * proxy, otherwise any client could spoof X-Forwarded-For and evade the limit
 * entirely by rotating a fake value.
 */
function client_ip(): string
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $trusted = ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];
    if ($remote !== '' && !ip_is_trusted($remote, $trusted)) {
        return $remote;
    }
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $header) {
        $raw = (string)($_SERVER[$header] ?? '');
        if ($raw === '') { continue; }
        // X-Forwarded-For is a chain: "client, proxy1, proxy2".
        $first = trim(explode(',', $raw)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) { return $first; }
    }
    return $remote !== '' ? $remote : 'unknown';
}

function ip_is_trusted(string $ip, array $cidrs): bool
{
    foreach ($cidrs as $cidr) {
        if (strpos($cidr, '/') === false) {
            if ($ip === $cidr) { return true; }
            continue;
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) { continue; }
        $mask = -1 << (32 - (int)$bits);
        if (($ipLong & $mask) === ($subnetLong & $mask)) { return true; }
    }
    return false;
}

function client_ip_hash(?string $ip = null): string {
    return hash('sha256', $ip ?? client_ip());
}
// Attempts live in a JSON file (login_attempts.json), read/written with the
// shared atomic_write() helper. Unlike the old PHP-export trick, nothing is
// ever executed from this file, and load_attempts() can never run arbitrary
// code even if the file bytes were ever corrupted.
function load_attempts(string $file): array {
    $data = [];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) { $data = $decoded; }
    } else {
        // Legacy migration from the previous PHP-export format is deliberately
        // NOT executed — nothing is ever include()d or require()d here, so the
        // file's bytes can never run code. If its body happens to be valid JSON
        // it is adopted; otherwise the next save simply writes JSON going forward.
        $legacy = dirname($file) . '/login_attempts.php';
        if (is_file($legacy)) {
            $decoded = json_decode((string)@file_get_contents($legacy), true);
            if (is_array($decoded)) { $data = $decoded; }
        }
    }
    return $data;
}
function save_attempts(string $file, array $data, array $deleted = []): void {
    // Prune entries older than 1 hour so the file never grows unbounded.
    $cutoff = time() - 3600;
    // Start from disk, then apply this request's array on top: the caller's
    // snapshot is the newer of the two, so a plain merge of the other way round
    // let a stale read overwrite a fresher count and run the brute-force
    // counter backwards.
    $merged = array_merge(load_attempts($file), $data);
    // ...and honour deletions. A bare merge resurrected them, which made the
    // unset() on a successful login — whose whole job is to clear the
    // counters — a no-op, so the admin's own success kept their lock count up.
    foreach ($deleted as $k) { unset($merged[$k]); }
    $merged = array_filter($merged, fn($v) => ($v['last'] ?? 0) > $cutoff);
    atomic_write($file, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$attemptsFile = __DIR__ . '/login_attempts.json';
$ipKey = client_ip_hash();
$attempts = load_attempts($attemptsFile);
$myAttempts = $attempts[$ipKey] ?? ['count' => 0, 'locked_until' => 0, 'last' => 0];

// Password check: bcrypt only. Credentials never live in config.php;
// credentials.php owns reading/writing auth_hash.php ({username, hash}).
// No weaker fallback exists.
$auth = load_auth();
$authHash = (string)($auth['hash'] ?? '');
$authUser = (string)($auth['username'] ?? '');
$authName = trim((string)($auth['display_name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login-CSRF guard: the initial GET created a per-session token; every
    // POST must carry it back. Combined with SameSite=Strict, this blocks
    // cross-site forged logins.
    if (!secure_equals((string)($_SESSION['csrf_token'] ?? ''), $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('درخواست نامعتبر است (CSRF).');
    }
    // Second, coarser bucket keyed on the username being tried. The per-IP
    // counter alone cannot slow down a botnet walking one IP at a time, but
    // it cannot lock the admin out either, because the username is the same
    // from everywhere. The threshold is higher and the window longer, since
    // this one is shared across all addresses.
    $rawUser = $_POST['username'] ?? '';
    $userKey = 'user:' . hash('sha256', strtolower(trim(is_scalar($rawUser) ? (string)$rawUser : '')));
    $userAttempts = $attempts[$userKey] ?? ['count' => 0, 'locked_until' => 0, 'last' => 0];
    $userLocked = time() < $userAttempts['locked_until'];

    if ($userLocked) {
        $error = 'به‌دلیل تلاش‌های ناموفق زیاد برای این نام کاربری، ۱۵ دقیقه صبر کنید.';
    } elseif (time() < $myAttempts['locked_until']) {
        $error = 'به‌دلیل تلاش‌های ناموفق زیاد، چند دقیقه صبر کنید.';
    } else {
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';
        // Both can be arrays (`password[]=x`), and both hash_equals() and
        // password_verify() declare `string` — an uncaught TypeError, i.e. a
        // 500 instead of a failed login. secure_equals() rejects non-scalars;
        // the password is cast because an array password is simply wrong, not
        // a reason to crash.
        $userOk = $authUser !== '' && secure_equals($authUser, $u);
        $passOk = $userOk && $authHash !== ''
            && !is_array($p) && !is_object($p)
            && password_verify((string)$p, $authHash);

        if ($userOk && $passOk) {
            // Persist an optional display name from the login form together
            // with a hash rehash, if one is due, in a single atomic write.
            $hashToStore = $authHash;
            if (password_needs_rehash($authHash, PASSWORD_BCRYPT)) {
                $hashToStore = password_hash((string)$p, PASSWORD_BCRYPT);
            }
            $nameToStore = $authName;
            $postedName = $_POST['display_name'] ?? '';
            if (is_string($postedName)) {
                $candidate = trim((string)preg_replace('/\s+/u', ' ', $postedName));
                if ($candidate !== '' && mb_strlen($candidate) <= 60) {
                    $nameToStore = $candidate;
                }
            }
            if ($hashToStore !== $authHash || $nameToStore !== $authName) {
                persist_auth($authUser, $hashToStore, $nameToStore);
            }
            unset($attempts[$ipKey], $attempts[$userKey]);
            save_attempts($attemptsFile, $attempts, [$ipKey, $userKey]);
            session_regenerate_id(true);
            // Fresh CSRF token for the privileged session. The pre-login token
            // was handed out to an unauthenticated visitor and has by now been
            // through one round-trip; carrying it forward would keep a token
            // that existed before authentication valid for every form after it.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['admin_logged_in'] = true;
    header('Location: index.php');
            exit;
        } else {
            $myAttempts['count']++;
            $myAttempts['last'] = time();
            if ($myAttempts['count'] >= 5) {
                $myAttempts['locked_until'] = time() + 300;
                $myAttempts['count'] = 0;
                $error = 'به‌دلیل تلاش‌های ناموفق زیاد، ۵ دقیقه صبر کنید.';
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است.';
            }
            $attempts[$ipKey] = $myAttempts;

            $userAttempts['count']++;
            $userAttempts['last'] = time();
            if ($userAttempts['count'] >= 12) {
                $userAttempts['locked_until'] = time() + 900;
                $userAttempts['count'] = 0;
            }
            $attempts[$userKey] = $userAttempts;

            save_attempts($attemptsFile, $attempts);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود به پنل مدیریت | <?= htmlspecialchars($authName !== '' ? $authName : 'مدیر سایت') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="icon" href="../image/favicon.ico" sizes="any">
  <style>
    :root {
      color-scheme: light;
      --bg: #f8fafc;
      --panel: #ffffff;
      --field: #ffffff;
      --line: #e5e7eb;
      --sig: #2563eb;
      --ink: #111827;
      --muted: #4b5563;
      --muted-2: #6b7280;
      --danger: #dc2626;
      --danger-bg: #fef2f2;
      --danger-line: #fecaca;
      --ring: rgba(37, 99, 235, 0.12);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      overflow-x: hidden;
    }

    body {
      min-height: 100vh;
      min-height: 100dvh;
      display: grid;
      place-items: center;
      padding: 16px;
      color: var(--ink);
      font-family: "Dana", system-ui, sans-serif;
      line-height: 1.7;
      background: var(--bg);
    }

    .login-card {
      position: relative;
      width: 100%;
      max-width: 340px;
      padding: 32px 28px;
      border-radius: 12px;
      background: var(--panel);
      border: 1px solid var(--line);
    }

    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 8px;
      margin-bottom: 24px;
    }

    .brand h1 {
      font-size: 17px;
      font-weight: 700;
    }

    .brand .who {
      color: var(--muted);
      font-size: 12px;
    }

    .form-body {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .error {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 12.5px;
      line-height: 1.6;
      color: var(--danger);
      background: var(--danger-bg);
      border: 1px solid var(--danger-line);
    }

    .error svg {
      flex: none;
      margin-top: 3px;
    }

    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .field-label {
      font-size: 11.5px;
      color: var(--muted);
    }

    .field-hint {
      font-size: 11px;
      line-height: 1.6;
      color: var(--muted-2);
    }

    .input-shell {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-shell > svg {
      position: absolute;
      inset-inline-start: 10px;
      color: var(--muted-2);
      pointer-events: none;
    }

    .input-shell input {
      width: 100%;
      border-radius: 8px;
      border: 1px solid var(--line);
      background: var(--field);
      color: var(--ink);
      font-family: inherit;
      font-size: 14px;
      padding: 9px 12px;
      padding-inline-start: 34px;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .input-shell.no-icon input {
      padding-inline-start: 12px;
    }

    .field.has-toggle .input-shell input {
      padding-inline-end: 34px;
    }

    .input-shell input:focus {
      outline: none;
      border-color: var(--sig);
      box-shadow: 0 0 0 2px var(--ring);
    }

    .pw-toggle {
      position: absolute;
      inset-inline-end: 6px;
      width: 26px;
      height: 26px;
      display: grid;
      place-items: center;
      border: none;
      background: transparent;
      color: var(--muted-2);
      cursor: pointer;
    }

    .pw-toggle svg.off {
      display: none;
    }

    .pw-toggle.hide .off {
      display: block;
    }

    .pw-toggle.hide .on {
      display: none;
    }

    .actions {
      margin-top: 6px;
    }

    .btn {
      width: 100%;
      border: none;
      border-radius: 8px;
      padding: 10px 14px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      color: #fff;
      background: var(--sig);
      cursor: pointer;
    }

    .btn:hover {
      filter: brightness(1.05);
    }

    .btn:focus-visible {
      outline: none;
      box-shadow: 0 0 0 2px var(--ring);
    }

    .foot {
      margin-top: 18px;
      text-align: center;
      color: var(--muted-2);
      font-size: 11px;
    }

    .foot .domain {
      direction: ltr;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
  </style>
</head>
<body>
  <form class="login-card" method="post" action="login.php<?= htmlspecialchars($keyParam) ?>" autocomplete="off">
    <div class="brand">
      <h1>ورود به پنل مدیریت</h1>
      <p class="who" id="who"><?= htmlspecialchars($authName !== '' ? $authName : 'مدیر سایت') ?></p>
    </div>

    <div class="form-body">
      <?php if ($error): ?>
        <div class="error" role="alert">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <div class="field-label">
          <label for="u">نام کاربری</label>
        </div>
        <div class="input-shell">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <input type="text" id="u" name="username" required autofocus autocomplete="username" spellcheck="false" autocapitalize="none" autocorrect="off">
        </div>
      </div>

      <div class="field has-toggle">
        <div class="field-label">
          <label for="p">رمز عبور</label>
        </div>
        <div class="input-shell">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" id="p" name="password" required autocomplete="current-password">
          <button type="button" class="pw-toggle" aria-label="نمایش رمز عبور" aria-pressed="false">
            <svg class="on" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M17.9 17.9A10.8 10.8 0 0 1 12 19.9C4.2 19.9.7 12 .7 12a19.3 19.3 0 0 1 4.6-5.7"/>
              <path d="M9.9 4.6a9.5 9.5 0 0 1 2.1-.3c7.8 0 11.3 7.7 11.3 7.7a19 19 0 0 1-2.3 3.2"/>
              <path d="M3 3l18 18"/>
            </svg>
          </button>
        </div>
      </div>

      <?php if ($authName === ''): ?>
      <div class="field">
        <div class="field-label">
          <label for="n">اسم شما چیست؟</label>
        </div>
        <div class="input-shell no-icon">
          <input type="text" id="n" name="display_name" value="" maxlength="60" placeholder="مثلاً نام مدیر" autocomplete="name" dir="auto">
        </div>
        <p class="field-hint">هر اسمی که اینجا بنویسی، همان زیر عنوان ورود نمایش داده میشود.</p>
      </div>
      <?php endif; ?>

      <div class="actions">
        <button class="btn" type="submit">ورود</button>
      </div>
    </div>

    <div class="foot">
      <span class="domain">Digital Business Card</span>
    </div>

    <?php if ($gateKey !== ''): ?><input type="hidden" name="key" value="<?= htmlspecialchars($gateKey) ?>"><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
  </form>

  <script>
    (function () {
      var toggle = document.querySelector('.pw-toggle');
      var input = document.getElementById('p');
      if (toggle && input) {
        toggle.addEventListener('click', function () {
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          toggle.classList.toggle('hide', show);
          toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
          toggle.setAttribute('aria-label', show ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور');
        });
      }

      // The name field edits the greeting live, so the admin sees exactly what
      // will appear under the title before signing in.
      var nameInput = document.getElementById('n');
      var who = document.getElementById('who');
      if (nameInput && who) {
        nameInput.addEventListener('input', function () {
          var value = nameInput.value.trim();
          who.textContent = value !== '' ? value : 'مدیر سایت';
        });
      }
    })();
  </script>
</body>
</html>
