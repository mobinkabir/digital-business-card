<?php

declare(strict_types=1);

/**
 * One place for the panel's secrets.
 *
 * There used to be two files, both shaped ['key' => '...'], meaning completely
 * different things:
 *
 *   admin/secret.php     the login gate key. Managed from the Security tab.
 *   admin/deepl_key.php  the DeepL API key. Had to be created by hand-editing
 *                        a PHP file on the server, because the panel had no
 *                        field for it.
 *
 * Identical shape, unrelated meaning, and one of the two unreachable from the
 * UI — that is how a key ends up configured in the wrong place, or in two
 * places at once, with the wrong one winning.
 *
 * Now there is one managed file, admin/secrets.php, with one key per named
 * secret. Both legacy files are still read (never written) so an existing
 * install keeps working on upgrade, and the values are folded into the unified
 * file the first time the panel saves something.
 *
 * Precedence for each secret:
 *   1. the DEEPL_API_KEY environment variable, where one exists
 *   2. admin/secrets.php
 *   3. the legacy file, as a read-only fallback
 */

/** The managed file. A .php file, never .json, on purpose: if .htaccess */
const SECRETS_FILE = __DIR__ . '/secrets.php';

/** Named secrets and where each came from before this file existed. */
const SECRETS_LEGACY = [
    'login_gate'   => __DIR__ . '/secret.php',
    'deepl_api_key' => __DIR__ . '/deepl_key.php',
];

/** The names this store knows about. Anything else is not persisted. */
const SECRETS_NAMES = ['login_gate', 'deepl_api_key'];

/**
 * Cached per request, keyed by directory. The panel asks for the gate key on
 * every page render and for the API key on every translation; re-reading and
 * re-parsing per call would be wasteful, and caching also means a mid-request
 * write cannot make the same page disagree with itself.
 */
$GLOBALS['__secrets_cache'] = [];

/** Default directory: the admin folder this file lives in. */
/**
 * Atomic file write: write to a temp file then rename, so readers never see
 * a half-written file and concurrent writers cannot corrupt each other.
 */
function atomic_write(string $file, string $data): bool
{
    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $data, LOCK_EX) === false) {
        return false;
    }
    return @rename($tmp, $file);
}

function secrets_dir(?string $dir = null): string
{
    return $dir ?? __DIR__;
}

function secrets_path(?string $dir = null): string
{
    return secrets_dir($dir) . '/secrets.php';
}

function secrets_legacy_path(string $name, ?string $dir = null): string
{
    // A switch rather than `match`, which is PHP 8.0+. This function loads the
    // gate key, so on an 8.0 host the match expression was a parse-time fatal
    // on the very path that decides whether the panel is reachable.
    $file = '';
    if ($name === 'login_gate') { $file = 'secret.php'; }
    elseif ($name === 'deepl_api_key') { $file = 'deepl_key.php'; }
    return secrets_dir($dir) . '/' . $file;
}

/** Anything this small cannot be one of our files; refuse before parsing. */
const SECRETS_MAX_BYTES = 65536;

/**
 * Reads one of the `return [...]` PHP files without ever letting a broken file
 * take the page down.
 *
 * The obvious implementation is `@include`. That is not safe, and the failure
 * mode is nasty:
 *
 *   - `@` does not suppress a parse error, and a parse error inside an include
 *     is a *fatal* error that try/catch cannot intercept. So a mangled
 *     secrets.php turns the whole panel into a white screen.
 *   - the message contains the server's absolute path, so a corrupt file also
 *     leaks the filesystem layout to whoever loaded the page.
 *   - and a file that merely *echoes* would print straight into the response.
 *
 * So the file is parsed without being executed, and only included afterwards,
 * once it is known to be a single `return` of an array literal. TOKEN_PARSE
 * gives a real parse that reports problems as a catchable ParseError, and the
 * token walk then rejects anything that is not our own writer's output, so the
 * include can never run a call, touch a variable, or emit HTML.
 *
 * @return array<string,string>|null null on missing, unreadable, or rejected
 */
function secrets_read_php_array(string $path, string $what): ?array
{
    if (!is_file($path)) { return null; }

    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') { return null; }

    if (strlen($raw) > SECRETS_MAX_BYTES) {
        secrets_log("$what is implausibly large (" . strlen($raw) . ' bytes); ignored.', $path);
        return null;
    }

    try {
        $tokens = @token_get_all($raw, TOKEN_PARSE);
    } catch (\Throwable $e) {
        secrets_log("$what does not parse as PHP (" . $e->getMessage() . '); ignored.', $path);
        return null;
    }

    $count = count($tokens);
    $i = 0;
    $skipTrivia = static function () use ($tokens, $count, &$i): void {
        while ($i < $count) {
            $t = $tokens[$i];
            if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }
            return;
        }
    };

    // Must open with a real PHP tag: no leading HTML, no short-echo block.
    if (!isset($tokens[0]) || !is_array($tokens[0]) || $tokens[0][0] !== T_OPEN_TAG) {
        secrets_log("$what does not start with <?php; ignored.", $path);
        return null;
    }
    if (stripos((string)$tokens[0][1], '<?=') === 0) {
        secrets_log("$what uses a short-echo tag; ignored.", $path);
        return null;
    }
    $i = 1;

    $skipTrivia();
    if (!isset($tokens[$i]) || !is_array($tokens[$i]) || $tokens[$i][0] !== T_RETURN) {
        secrets_log("$what does not start with a return statement; ignored.", $path);
        return null;
    }
    $i++;
    $skipTrivia();

    // The expression must be an array literal: array( ... ) or [ ... ].
    $literalOk = false;
    if (isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] === T_ARRAY) {
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) { $j++; }
        $literalOk = isset($tokens[$j]) && $tokens[$j] === '(';
    } elseif (isset($tokens[$i]) && $tokens[$i] === '[') {
        $literalOk = true;
    }
    if (!$literalOk) {
        secrets_log("$what does not return an array literal; ignored.", $path);
        return null;
    }

    // Walk the literal, counting bracket depth. Only quoted strings, the =>
    // arrow, commas, brackets and trivia may appear inside it. That rules out
    // calls, variables, interpolation and nested expressions.
    $depth = 0;
    $closed = false;
    for (; $i < $count && !$closed; $i++) {
        $t = $tokens[$i];
        $text = is_array($t) ? $t[1] : $t;

        if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { continue; }
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) { continue; }
        if (is_array($t) && $t[0] === T_DOUBLE_ARROW) { continue; }
        // The `array` keyword carries no brackets; the ( after it is counted on
        // the next iteration.
        if (is_array($t) && $t[0] === T_ARRAY) { continue; }
        if ($text === ',') { continue; }
        if ($text === '(' || $text === '[' || $text === '{') { $depth++; continue; }
        if ($text === ')' || $text === ']' || $text === '}') {
            $depth--;
            if ($depth === 0) { $closed = true; }
            continue;
        }

        secrets_log(
            $what . ' contains ' . (is_array($t) ? token_name($t[0]) : var_export($text, true))
            . ', which is not part of a plain array literal; ignored.',
            $path
        );
        return null;
    }
    if (!$closed || $depth !== 0) {
        secrets_log("$what has an unterminated array literal; ignored.", $path);
        return null;
    }
    // No $i++ here: the loop's own increment already moved past the closing
    // bracket, so $i now points at the semicolon.

    // Nothing may follow the expression except `;` and trailing trivia.
    $skipTrivia();
    if (!isset($tokens[$i]) || $tokens[$i] !== ';') {
        secrets_log("$what has more than a single return statement; ignored.", $path);
        return null;
    }
    $i++;
    $skipTrivia();
    if ($i !== $count) {
        secrets_log("$what has trailing content after the return; ignored.", $path);
        return null;
    }

    // Validated: parsing cannot fail and execution can only return the array.
    ob_start();
    try {
        $value = include $path;
    } catch (\Throwable $e) {
        $value = null;
    }
    ob_end_clean();

    if (!is_array($value)) {
        secrets_log("$what did not evaluate to an array; ignored.", $path);
        return null;
    }

    $out = [];
    foreach ($value as $k => $v) {
        if (is_scalar($v)) { $out[(string)$k] = (string)$v; }
    }
    return $out;
}

/**
 * Records a problem with a secret file. Guarded because config.php is also read
 * by scripts/build-public-content.php, which does not boot the panel and so has
 * no error collector.
 */
function secrets_log(string $message, string $path): void
{
    if (function_exists('panel_error_record')) {
        panel_error_record($message, $path, 0);
    }
}

/**
 * All managed secrets, with legacy values folded in.
 *
 * @return array<string,string>
 */
function secrets_load(?string $dir = null): array
{
    $dir = secrets_dir($dir);
    if (isset($GLOBALS['__secrets_cache'][$dir])) {
        return $GLOBALS['__secrets_cache'][$dir];
    }

    $managedPath = $dir . '/secrets.php';
    $out = [];
    foreach (SECRETS_NAMES as $name) {
        $out[$name] = '';
    }

    // Legacy first, so the managed file below can override it.
    foreach (SECRETS_NAMES as $name) {
        $legacyFile = secrets_legacy_path($name, $dir);
        if ($legacyFile === '') { continue; }
        $legacy = secrets_read_php_array($legacyFile, 'Legacy file ' . basename($legacyFile));
        if ($legacy === null) { continue; }
        // Both legacy files used the same key name for different secrets.
        $out[$name] = $legacy['key'] ?? '';
    }

    $managed = secrets_read_php_array($managedPath, 'secrets.php');
    if ($managed !== null) {
        foreach ($managed as $name => $value) {
            $out[(string)$name] = $value;
        }
    }

    $GLOBALS['__secrets_cache'][$dir] = $out;
    return $out;
}

/**
 * One secret by name. Unknown names come back as '' rather than raising, so a
 * typo in a caller cannot take a page down.
 */
function secrets_get(string $name, ?string $dir = null): string
{
    return (string)(secrets_load($dir)[$name] ?? '');
}

function secrets_has(string $name, ?string $dir = null): bool
{
    return secrets_get($name, $dir) !== '';
}

/**
 * Writes one secret, leaving the others untouched.
 *
 * The whole file is rewritten rather than patched: it holds two short strings,
 * and a read-modify-write over the whole set is far easier to reason about than
 * a merge that has to survive a concurrent write to a *different* key.
 */
function secrets_set(string $name, string $value, ?string $dir = null): bool
{
    $all = secrets_load($dir);
    $all[$name] = $value;
    return secrets_write_all($all, $dir);
}

function secrets_write_all(array $all, ?string $dir = null): bool
{
    // Only ever persist known names, so a caller cannot smuggle arbitrary keys
    // into a file that is generated from user input.
    $clean = [];
    foreach (SECRETS_NAMES as $name) {
        $clean[$name] = (string)($all[$name] ?? '');
    }

    $php = "<?php\n"
         . "// Generated by the admin panel (Security tab). Do not edit by hand.\n"
         . "//\n"
         . "// A .php file rather than .json on purpose: if .htaccess protection ever\n"
         . "// fails on a host, requesting this file directly executes it and returns\n"
         . "// nothing, instead of printing the secrets.\n"
         . 'return ' . var_export($clean, true) . ";\n";

    $path = secrets_path($dir);
    $ok = atomic_write($path, $php);
    if ($ok) { $GLOBALS['__secrets_cache'][secrets_dir($dir)] = $clean; }
    return $ok;
}

/**
 * Names whose value still exists only in a legacy file.
 *
 * Reads the managed file directly rather than going through secrets_load():
 * load() merges the legacy values in, so every secret would look "already
 * present" and this would always answer "nothing to migrate" — which is the one
 * answer that must never be wrong.
 */
function secrets_has_legacy_only(?string $dir = null): array
{
    $dir = secrets_dir($dir);
    $managedPath = $dir . '/secrets.php';
    $managed = is_file($managedPath)
        ? secrets_read_php_array($managedPath, 'secrets.php')
        : null;

    $out = [];
    foreach (SECRETS_NAMES as $name) {
        if ((string)($managed[$name] ?? '') !== '') { continue; }
        $legacyFile = secrets_legacy_path($name, $dir);
        if ($legacyFile === '' || !is_file($legacyFile)) { continue; }
        $legacy = secrets_read_php_array($legacyFile, 'Legacy file ' . basename($legacyFile));
        if ($legacy !== null && (string)($legacy['key'] ?? '') !== '') {
            $out[] = $name;
        }
    }
    return $out;
}

/**
 * Copies any legacy-only values into the unified file. Explicit, never
 * automatic: writing a secret file as a side effect of rendering a page is how
 * a read-only GET ends up needing a writable directory.
 */
function secrets_migrate_legacy(?string $dir = null): bool
{
    $pending = secrets_has_legacy_only($dir);
    if ($pending === []) { return true; }
    $all = secrets_load($dir);
    foreach ($pending as $name) { $all[$name] = secrets_get($name, $dir); }
    return secrets_write_all($all, $dir);
}
