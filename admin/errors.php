<?php

declare(strict_types=1);

/**
 * Error visibility for the admin panel.
 *
 * session.php sets display_errors=0 so a notice can never leak an absolute
 * path or a key into a page served to the public. The cost of that is that a
 * genuine bug in the panel shows up as a blank white page with nothing in it,
 * which is close to undiagnosable without server log access.
 *
 * This closes that gap without reopening the leak: every error is logged to a
 * file the web server refuses to serve, and the ones that happen during an
 * authenticated request are also rendered into the panel, where the one person
 * who is allowed to read them is already logged in.
 *
 * Required once from session.php, before anything else can fail.
 */

/** How many errors to keep for display in one page load. */
const PANEL_ERROR_DISPLAY_LIMIT = 20;

/** Truncate the log once it passes this, so it cannot fill the disk. */
const PANEL_ERROR_LOG_MAX_BYTES = 1048576;

$GLOBALS['__panel_errors'] = [];
$GLOBALS['__panel_error_log'] = null;

/**
 * Notices: conditions the panel handled, but that the operator should know
 * about. Kept apart from errors on purpose.
 *
 * The error box says "خطای PHP در این صفحه" and it is right about that. A
 * MySQL server that is not running is not a PHP error on the page — it is the
 * documented fallback this project ships with, where storage.php keeps using
 * the JSON files. Filing it as an error put a red "PHP error" box on a panel
 * that was working perfectly, which trains the operator to ignore the box
 * that would have mattered.
 *
 * A notice is the same severity of information in a different key: the panel
 * is running, but it is running in a degraded or unexpected way.
 */
$GLOBALS['__panel_notices'] = [];

/**
 * Installs the handlers. Safe to call more than once.
 */
function panel_errors_init(?string $logFile = null): void
{
    if ($GLOBALS['__panel_error_log'] !== null) { return; }
    $GLOBALS['__panel_error_log'] = $logFile ?? dirname(__DIR__) . '/data/.panel-errors.log';

    // Collected for display, still logged — display is a convenience, the
    // log is the record that survives the request.
    set_error_handler(static function (int $no, string $str, string $file = '', int $line = 0): bool {
        // Respect @-suppression and the error_reporting mask rather than
        // surfacing noise the code deliberately ignored.
        if (!(error_reporting() & $no)) { return true; }
        $isFatal = in_array($no, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
        panel_error_record(
            panel_error_label($no) . ': ' . $str,
            $file,
            $line,
            $isFatal
        );
        // Always handled: returning false would let PHP's own handler print it
        // too, which is exactly the display_errors=0 leak this file avoids.
        return true;
    });

    set_exception_handler(static function (Throwable $e): void {
        panel_error_record(
            'Uncaught ' . get_class($e) . ': ' . $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            true,
            $e->getTraceAsString()
        );
        panel_errors_respond();
    });

    // Catches what set_exception_handler cannot: E_ERROR, memory exhaustion,
    // a failed require. Runs after normal shutdown.
    register_shutdown_function(static function (): void {
        $e = error_get_last();
        if ($e === null) { return; }
        if (!in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) { return; }
        panel_error_record(
            'Fatal: ' . $e['message'],
            $e['file'],
            $e['line'],
            true
        );
        panel_errors_respond(true);
    });
}

function panel_error_label(int $no): string
{
    static $map = [
        E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parse error',
        E_NOTICE => 'Notice', E_CORE_ERROR => 'Core error', E_CORE_WARNING => 'Core warning',
        E_COMPILE_ERROR => 'Compile error', E_COMPILE_WARNING => 'Compile warning',
        E_USER_ERROR => 'User error', E_USER_WARNING => 'User warning', E_USER_NOTICE => 'User notice',
        E_RECOVERABLE_ERROR => 'Recoverable error', E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'Deprecated',
    ];
    return $map[$no] ?? ('E' . $no);
}

function panel_error_record(string $message, string $file = '', int $line = 0, bool $fatal = false, ?string $trace = null): void
{
    $entry = [
        'message' => $message,
        'file'    => $file,
        'line'    => $line,
        'fatal'   => $fatal,
        'trace'   => $trace,
        'time'    => time(),
        'shown'   => false,
    ];
    $GLOBALS['__panel_errors'][] = $entry;
    panel_error_log_write($entry);
}

function panel_error_log_write(array $entry): void
{
    $file = $GLOBALS['__panel_error_log'];
    if ($file === null) { return; }

    $rel = panel_error_relativize((string)$entry['file']);
    $line = sprintf(
        "[%s] %s%s in %s:%d\n",
        date('Y-m-d H:i:s'),
        $entry['fatal'] ? 'FATAL ' : '',
        $entry['message'],
        $rel,
        (int)$entry['line']
    );
    if (!empty($entry['trace'])) { $line .= "  trace: " . $entry['trace'] . "\n"; }

    // Serialised: two requests logging at once must not interleave halves of
    // a line. Append mode plus LOCK_EX is enough here — the file is a tail.
    // 'c+' not 'a': append mode is write-only, so the rotation below could not
    // read the existing tail back. Writes are still serialised by the lock, and
    // the file is opened with LOCK_EX below, so there is no interleaving.
    $fh = @fopen($file, 'c+');
    if ($fh === false) { return; }
    @flock($fh, LOCK_EX);
    // Re-stat under the lock: another request may have rotated since we checked.
    clearstatcache(true, $file);
    if (@filesize($file) > PANEL_ERROR_LOG_MAX_BYTES) {
        $keep = (string)stream_get_contents($fh);
        $half = (int)(strlen($keep) / 2);
        ftruncate($fh, 0);
        fseek($fh, 0);
        fwrite($fh, substr($keep, $half));
    }
    fseek($fh, 0, SEEK_END);
    fwrite($fh, $line);
    @flock($fh, LOCK_UN);
    fclose($fh);
    @chmod($file, 0600);
}

/**
 * Absolute paths are noise in a log the admin reads; the part that identifies
 * the file is what matters.
 */
function panel_error_relativize(string $file): string
{
    $root = dirname(__DIR__);
    $real = @realpath($file);
    if ($real !== false && strncmp($real, $root, strlen($root)) === 0) {
        return str_replace('\\', '/', substr($real, strlen($root)));
    }
    return str_replace('\\', '/', $file);
}

/**
 * Errors collected so far, newest last, capped.
 *
 * @return list<array>
 */
function panel_errors(): array
{
    return array_slice($GLOBALS['__panel_errors'], -PANEL_ERROR_DISPLAY_LIMIT);
}

function panel_errors_pending(): int
{
    return count($GLOBALS['__panel_errors']);
}

/**
 * Renders the collected errors, or '' if there is nothing to say.
 *
 * The login check lives here rather than at the call site on purpose: this
 * function prints absolute file paths, and a gate that every caller has to
 * remember is exactly the kind of gate that gets forgotten the first time
 * someone adds a new page to the panel.
 */
function panel_error_box(): string
{
    if (empty($_SESSION['admin_logged_in'])) { return ''; }
    $errs = panel_errors();
    if ($errs === []) { return ''; }

    $h = '<div class="panel-errors" role="alert">'
       . '<details' . (count($errs) > 1 ? '' : ' open') . '>'
       . '<summary>خطای PHP در این صفحه ('
       . count($GLOBALS['__panel_errors']) . ') — برای رفع مشکل باز کنید</summary>'
       . '<ul>';
    foreach ($errs as $e) {
        $h .= '<li><span class="pe-kind' . ($e['fatal'] ? ' pe-fatal' : '') . '">'
            . htmlspecialchars($e['fatal'] ? 'Fatal' : 'Error') . '</span> '
            . htmlspecialchars($e['message'])
            . ($e['file'] !== ''
                ? ' <a class="pe-src" href="?#">' . htmlspecialchars(panel_error_relativize($e['file'])) . ':' . (int)$e['line'] . '</a>'
                : '')
            . '</li>';
    }
    $h .= '</ul>'
       . '<p class="pe-foot">همین موارد در <code>data/.panel-errors.log</code> هم ثبت شده‌اند.</p>'
       . '</details></div>';
    return $h;
}

/* -------------------------------------------------------------------------
 * Notices: handled conditions the operator should still see.
 * ---------------------------------------------------------------------- */

/**
 * Records a notice.
 *
 * Same shape as an error, different channel: the condition did not break the
 * request, but the operator is running in a mode they may not have chosen
 * (MySQL down and the panel keeping the JSON files, for instance).
 *
 * Still written to the log file, so nothing that was visible before is lost —
 * a notice that is only on screen disappears the moment the page reloads.
 */
function panel_notice_record(string $message, string $file = '', int $line = 0): void
{
    $entry = [
        'message' => $message,
        'file'    => $file,
        'line'    => $line,
        'time'    => time(),
        'shown'   => false,
    ];
    $GLOBALS['__panel_notices'][] = $entry;

    // The log format is shared with errors, so the prefix is what tells a
    // reader of data/.panel-errors.log which channel a line came from.
    $logEntry = $entry;
    $logEntry['fatal'] = false;
    $logEntry['trace'] = null;
    $logEntry['message'] = 'NOTICE ' . $message;
    panel_error_log_write($logEntry);
}

/**
 * Notices collected so far, newest last, capped.
 *
 * @return list<array>
 */
function panel_notices(): array
{
    return array_slice($GLOBALS['__panel_notices'], -PANEL_ERROR_DISPLAY_LIMIT);
}

/**
 * Renders the collected notices, or '' if there is nothing to say.
 *
 * Deliberately not styled as an error: no red, no "PHP error" wording. The
 * point of the separate channel is that the operator learns to read the red
 * box when it appears, which only works if red means red.
 *
 * The technical line is still shown, just under a plain sentence that says
 * what the panel is actually doing. "connection failed (PDOException)" is a
 * fact about a socket; it says nothing about whether the panel is working.
 */
function panel_notice_box(): string
{
    if (empty($_SESSION['admin_logged_in'])) { return ''; }
    $notices = panel_notices();
    if ($notices === []) { return ''; }

    // Every notice so far comes from the storage layer, which means the panel
    // is running on the JSON files rather than on the database it was
    // configured for. Checked rather than assumed, so a notice from another
    // part of the panel does not get a sentence about MySQL.
    $storageOnly = true;
    foreach ($notices as $n) {
        if (strncmp($n['message'], 'db: ', 4) !== 0) { $storageOnly = false; break; }
    }

    $h = '<div class="panel-notices" role="status">';
    if ($storageOnly) {
        $h .= '<p class="pn-head">وضعیت ذخیره‌سازی</p>'
            . '<p class="pn-lead">ذخیره‌سازی روی فایل انجام می‌شود — پایگاه داده MySQL در دسترس نیست. '
            . 'سایت و پنل بدون مشکل کار می‌کنند و همه‌چیز در <code>data/content.json</code> ذخیره می‌شود.</p>';
    } else {
        $h .= '<p class="pn-head">اعلان‌ها</p>';
    }
    $h .= '<ul>';
    foreach ($notices as $n) {
        $h .= '<li>' . htmlspecialchars($n['message'])
            . ($n['file'] !== ''
                ? ' <span class="pn-src">' . htmlspecialchars(panel_error_relativize($n['file'])) . ':' . (int)$n['line'] . '</span>'
                : '')
            . '</li>';
    }
    $h .= '</ul></div>';
    return $h;
}

/**
 * Last-resort response for an error that killed the request.
 *
 * Only produces output if nothing has been sent yet; otherwise appending HTML
 * to a half-rendered page would corrupt it and the browser would show a
 * confusing mix. Never echoes the message to a visitor who is not logged in.
 */
function panel_errors_respond(bool $fromShutdown = false): void
{
    if (headers_sent()) { return; }
    if (!panel_errors_respond_should_render()) {
        // Unknown visitor: acknowledge without explaining.
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "خطای داخلی سرور. لطفاً بعداً تلاش کنید.\n";
        return;
    }

    $errs = panel_errors();
    $last = $errs === [] ? null : $errs[count($errs) - 1];
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
       . '<title>خطای داخلی</title>'
       // The panel's own typeface, so this page matches the rest of the panel
       // instead of falling back to the browser default. This page is reached
       // when something is badly broken, so it cannot rely on index.php having
       // loaded anything — it links the one stylesheet it needs itself.
       . '<link rel="stylesheet" href="../css/fonts.css"></head>'
       . '<body style="font-family:Dana,system-ui,sans-serif;max-width:60rem;margin:2rem auto;padding:0 1rem">'
       . '<h1>خطای داخلی در پنل مدیریت</h1>';
    if ($last !== null) {
        echo '<p><strong>' . htmlspecialchars($last['message']) . '</strong></p>';
        echo '<p style="color:#666;font-size:.9rem">'
           . htmlspecialchars(panel_error_relativize((string)$last['file'])) . ':' . (int)$last['line'] . '</p>';
    }
    if ($fromShutdown && $last !== null && $last['trace'] !== null) {
        echo '<pre style="overflow:auto;background:#f6f6f6;padding:1rem;font-size:.8rem">'
           . htmlspecialchars((string)$last['trace']) . '</pre>';
    }
    echo '<p>جزئیات کامل در <code>data/.panel-errors.log</code> ثبت شده است.</p>'
       . '</body></html>';
}

function panel_errors_respond_should_render(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}
