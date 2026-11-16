<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/credentials.php';
require_once __DIR__ . '/secrets_store.php';
// Prevent caching of the panel page so section switching and active state are always fresh.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/**
 * Country choices for the card. The panel only ever asks for a name; the ISO
 * code comes from this map so a hand-typed code can never mismatch the flag.
 * Codes with a flag in image/flags/ get a flag; the rest fall back to a badge.
 */
$countryOptions = [
    'ir' => 'ایران',
    'ae' => 'امارات متحدهٔ عربی',
    'sa' => 'عربستان سعودی',
    'qa' => 'قطر',
    'kw' => 'کویت',
    'bh' => 'بحرین',
    'om' => 'عمان',
    'iq' => 'عراق',
    'ca' => 'کانادا',
    'tr' => 'ترکیه',
    'de' => 'آلمان',
    'gb' => 'بریتانیا',
    'fr' => 'فرانسه',
    'nl' => 'هلند',
    'se' => 'سوئد',
    'us' => 'آمریکا',
    'au' => 'استرالیا',
    'my' => 'مالزی',
    'cn' => 'چین',
    'in' => 'هند',
    'jp' => 'ژاپن',
    'kr' => 'کرهٔ جنوبی',
    'ru' => 'روسیه',
    'az' => 'آذربایجان',
    'am' => 'ارمنستان',
    'ge' => 'گرجستان',
    'tm' => 'ترکمنستان',
    'uz' => 'ازبکستان',
    'kz' => 'قزاقستان',
    'pk' => 'پاکستان',
    'af' => 'افغانستان',
    'eg' => 'مصر',
    'jo' => 'اردن',
    'lb' => 'لبنان',
    'za' => 'آفریقای جنوبی',
    'br' => 'برزیل',
];

// Only codes that actually have a PNG in image/flags/ ask for an <img>; every
// other country gets a compact code badge instead of a broken image.
$flagCodes = [];
foreach (glob(__DIR__ . '/../image/flags/*.png') ?: [] as $flagPath) {
    $flagCodes[] = strtolower(pathinfo($flagPath, PATHINFO_FILENAME));
}
sort($flagCodes);

/**
 * The country select is grouped by region so 36 names stay easy to scan.
 * $countryOptions remains the single source of truth for names; a code that is
 * missing here simply never renders, so adding a new name above is enough.
 */
$countryGroups = [
    'خاورمیانه' => ['ir', 'tr', 'ae', 'sa', 'qa', 'kw', 'bh', 'om', 'iq', 'jo', 'lb', 'af', 'pk'],
    'قفقاز و آسیای مرکزی' => ['az', 'am', 'ge', 'tm', 'uz', 'kz'],
    'اروپا' => ['de', 'gb', 'fr', 'nl', 'se', 'ru'],
    'آسیا و اقیانوسیه' => ['in', 'cn', 'jp', 'kr', 'my', 'au'],
    'آمریکا' => ['us', 'ca', 'br'],
    'آفریقا' => ['eg', 'za'],
];

/**
 * Apps offered in «شبکه‌های اجتماعی» و «راه‌های ارتباطی». The key is the short
 * code stored in data/card.json (`socials[].icon`, `contacts[].icon`), the
 * brand colour tints the whole admin row and the public card, and the inline
 * logo means the picker never waits for another image request.
 */
$appCatalog = [
    'wa' => [
        'label' => 'واتساپ',
        'color' => '#25D366',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>',
    ],
    'tg' => [
        'label' => 'تلگرام',
        'color' => '#26A5E4',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
    ],
    'ig' => [
        'label' => 'اینستاگرام',
        'color' => '#E1306C',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7m11 2a1 1 0 0 1 1 1 1 1 0 0 1-1 1 1 1 0 0 1-1-1 1 1 0 0 1 1-1m-7 2a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg>',
    ],
    'li' => [
        'label' => 'لینکدین',
        'color' => '#0A66C2',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>',
    ],
    'gh' => [
        'label' => 'گیت‌هاب',
        'color' => '#24292F',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2A10 10 0 0 0 2 12c0 4.42 2.87 8.17 6.84 9.5.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.87 1.52 2.34 1.07 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.92 0-1.11.38-2 1.03-2.71-.1-.25-.45-1.29.1-2.64 0 0 .84-.27 2.75 1.02.79-.22 1.65-.33 2.5-.33.85 0 1.71.11 2.5.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.35.2 2.39.1 2.64.65.71 1.03 1.6 1.03 2.71 0 3.82-2.34 4.66-4.57 4.91.36.31.69.92.69 1.85V21c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0 0 12 2z"/></svg>',
    ],
    'x' => [
        'label' => 'X (توییتر)',
        'color' => '#0F1419',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    ],
    'yt' => [
        'label' => 'یوتیوب',
        'color' => '#FF0000',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.29.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.29-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>',
    ],
    'fb' => [
        'label' => 'فیسبوک',
        'color' => '#1877F2',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
    ],
    'tt' => [
        'label' => 'تیک‌تاک',
        'color' => '#FE2C55',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
    ],
    'call' => [
        'label' => 'تماس تلفنی',
        'color' => '#4F46E5',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg>',
    ],
    'mail' => [
        'label' => 'ایمیل',
        'color' => '#EA580C',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
    ],
    'web' => [
        'label' => 'وب‌سایت',
        'color' => '#7C3AED',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/></svg>',
    ],
];

/** Old / hand-typed icon names still resolve to a catalog key. */
$appAliases = [    'instagram' => 'ig', 'insta' => 'ig', 'اینستاگرام' => 'ig',
    'linkedin' => 'li', 'لینکدین' => 'li',
    'github' => 'gh', 'گیت‌هاب' => 'gh', 'گیت هاب' => 'gh',
    'twitter' => 'x', 'توییتر' => 'x',
    'youtube' => 'yt', 'یوتیوب' => 'yt',
    'telegram' => 'tg', 'تلگرام' => 'tg',
    'whatsapp' => 'wa', 'whats app' => 'wa', 'واتساپ' => 'wa', 'واتس‌اپ' => 'wa',
    'facebook' => 'fb', 'fb' => 'fb', 'فیسبوک' => 'fb', 'فیس بوک' => 'fb',
    'tiktok' => 'tt', 'تیک‌تاک' => 'tt', 'تیک تاک' => 'tt',
    'phone' => 'call', 'tel' => 'call', 'telephone' => 'call', 'تماس' => 'call', 'تماس تلفنی' => 'call',
    'email' => 'mail', 'e-mail' => 'mail', 'mail' => 'mail', 'ایمیل' => 'mail',
    'web' => 'web', 'website' => 'web', 'link' => 'web', 'url' => 'web', 'وب‌سایت' => 'web', 'وبسایت' => 'web',
];

/** Map a stored icon (old or new) to a catalog key, or '' when unknown. */
function app_lookup(string $icon, string $label, array $catalog, array $aliases): string
{
    $icon = strtolower(trim($icon));
    if (isset($catalog[$icon])) return $icon;
    if (isset($aliases[$icon])) return $aliases[$icon];
    $label = strtolower(trim($label));
    if (isset($catalog[$label])) return $label;
    if (isset($aliases[$label])) return $aliases[$label];
    return '';
}

/** Hex colour → rgba() so row tints work without CSS color-mix support. */
function app_hex_rgba(string $hex, float $alpha): string
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return 'rgba(100,116,139,' . $alpha . ')';
    }
    return sprintf(
        'rgba(%d,%d,%d,%s)',
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
        $alpha
    );
}

/* ---------- Jalali (Shamsi) calendar helpers ---------- */

/** Truncated (C-like) modulo, matching the algorithm's JS reference. */
function jalali_mod(int $a, int $b): int
{
    return $a - intdiv($a, $b) * $b;
}

/** Core Jalali year info: leap flag and the Gregorian date of 1 Farvardin. */
function jalali_cal(int $jy): array
{
    static $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
    $bl = count($breaks);
    $gy = $jy + 621;
    $leapJ = -14;
    $jp = $breaks[0];
    $jump = 0;
    if ($jy < $jp || $jy >= $breaks[$bl - 1]) return [];
    for ($i = 1; $i < $bl; $i++) {
        $jm = $breaks[$i];
        $jump = $jm - $jp;
        if ($jy < $jm) break;
        $leapJ += intdiv($jump, 33) * 8 + intdiv(jalali_mod($jump, 33), 4);
        $jp = $jm;
    }
    $n = $jy - $jp;
    $leapJ += intdiv($n, 33) * 8 + intdiv(jalali_mod($n, 33) + 3, 4);
    if (jalali_mod($jump, 33) === 4 && $jump - $n === 4) $leapJ += 1;
    $leapG = intdiv($gy, 4) - intdiv((intdiv($gy, 100) + 1) * 3, 4) - 150;
    $march = 20 + $leapJ - $leapG;
    if ($jump - $n < 6) $n = $n - $jump + intdiv($jump + 4, 33) * 33;
    $leap = jalali_mod(jalali_mod($n + 1, 33) - 1, 4);
    if ($leap === -1) $leap = 4;
    return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
}

function jalali_month_length(int $jy, int $jm): int
{
    if ($jm <= 6) return 31;
    if ($jm <= 11) return 30;
    $r = jalali_cal($jy);
    return ($r && $r['leap'] === 0) ? 30 : 29;
}

function gregorian_day_number(int $gy, int $gm, int $gd): int
{
    $d = intdiv(($gy + intdiv($gm - 8, 6) + 100100) * 1461, 4)
        + intdiv(153 * jalali_mod($gm + 9, 12) + 2, 5)
        + $gd - 34840408;
    return $d - intdiv(intdiv($gy + 100100 + intdiv($gm - 8, 6), 100) * 3, 4) + 752;
}

function day_number_to_gregorian(int $jdn): array
{
    $j = 4 * $jdn + 139361631;
    $j = $j + intdiv(intdiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    $i = intdiv(jalali_mod($j, 1461), 4) * 5 + 308;
    $gd = intdiv(jalali_mod($i, 153), 5) + 1;
    $gm = jalali_mod(intdiv($i, 153), 12) + 1;
    $gy = intdiv($j, 1461) - 100100 + intdiv(8 - $gm, 6);
    return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
}

function jalali_day_number(int $jy, int $jm, int $jd): ?int
{
    $r = jalali_cal($jy);
    if (!$r) return null;
    return gregorian_day_number($r['gy'], 3, $r['march'])
        + ($jm - 1) * 31 - intdiv($jm, 7) * ($jm - 7) + $jd - 1;
}

function jalali_to_gregorian(int $jy, int $jm, int $jd): ?array
{
    $jdn = jalali_day_number($jy, $jm, $jd);
    return $jdn === null ? null : day_number_to_gregorian($jdn);
}

/** Parse the "1403-04-15" value posted by the calendar. */
function parse_jalali_value($raw): ?array
{
    if (!is_string($raw) || !preg_match('/^\s*(\d{4})-(\d{1,2})-(\d{1,2})\s*$/', $raw, $m)) return null;
    $jy = (int)$m[1];
    $jm = (int)$m[2];
    $jd = (int)$m[3];
    if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12) return null;
    if ($jd < 1 || $jd > jalali_month_length($jy, $jm)) return null;
    return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
}

/**
 * Shape guard for a Jalali date stored in card.json. Hand-edited data can hold
 * strings, arrays or out-of-range months; every consumer gets a real date or
 * null, never a warning followed by "Array" printed into the page.
 */
function clean_jalali_date($date): ?array
{
    if (!is_array($date)) return null;
    $jy = filter_var($date['jy'] ?? null, FILTER_VALIDATE_INT);
    $jm = filter_var($date['jm'] ?? null, FILTER_VALIDATE_INT);
    $jd = filter_var($date['jd'] ?? null, FILTER_VALIDATE_INT);
    if ($jy === false || $jm === false || $jd === false) return null;
    return parse_jalali_value(sprintf('%04d-%02d-%02d', $jy, $jm, $jd));
}

/** Persian month name for a Jalali month number (1..12). */
function jalali_month_name(int $jm): string
{
    static $names = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    return $names[$jm - 1] ?? '';
}

/** Convert Latin digits in a string to Persian digits (for display labels). */
function fa_digits(string $value): string
{
    return strtr($value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

/** Format a parsed Jalali date as "۱۵ شهریور ۱۴۰۲". */
function format_jalali_date(?array $date): string
{
    if (!$date) return '';
    return fa_digits($date['jd'] . ' ' . jalali_month_name((int)$date['jm']) . ' ' . $date['jy']);
}

/** <option> list shared by every app picker (server + JS mirror). */
function app_picker_options(array $catalog, string $selected): string
{
    $html = '<option value="">انتخاب کنید…</option>';
    foreach ($catalog as $key => $app) {
        $html .= '<option value="' . htmlspecialchars($key) . '"'
            . ($key === $selected ? ' selected' : '') . '>'
            . htmlspecialchars($app['label']) . '</option>';
    }
    return $html;
}

/**
 * Sections that already exist in index.html. Any other key in sections[] is a
 * custom section created from the panel: its title and text live in the same
 * entry and js/card.js builds the markup on the public card.
 */
$knownSectionKeys = ['countries', 'about', 'contact', 'socials', 'experience', 'skills', 'education', 'certs', 'gallery', 'portfolio', 'resume', 'map'];

/**
 * Card data stores upload paths relative to the site root (`data/uploads/x.jpg`),
 * but this page lives in /admin/. Serving that path as-is made every preview
 * request /admin/data/uploads/... and 404, so saved images looked deleted.
 * Prefix relative paths with ../ and leave absolute/remote URLs alone.
 */
function admin_preview_url(string $path): string
{
    $path = trim($path);
    if ($path === '') return '';
    if (preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $path) || $path[0] === '/') return $path;
    return '../' . ltrim($path, './');
}

/**
 * Browser-tab name, description and logo are editable from the panel. Empty
 * values fall back to the card name/role so a fresh install keeps the original
 * metadata without forcing the admin to fill three fields first.
 */
function brand_meta(array $card): array
{
    $name = trim((string)($card['name'] ?? ''));
    $role = trim((string)($card['role'] ?? ''));
    $title = trim((string)($card['site_title'] ?? ''));
    if ($title === '') {
        $title = ($name !== '' ? $name : 'Your Name') . ' — Digital Business Card';
    }
    $description = trim((string)($card['site_description'] ?? ''));
    if ($description === '') {
        $description = $name !== ''
            ? trim($name . ($role !== '' ? ' — ' . $role : ''))
            : 'Digital Business Card';
    }
    $logo = trim((string)($card['site_logo'] ?? ''));
    if ($logo !== '' && !preg_match('#^(?:data/uploads|image)/[A-Za-z0-9_-][A-Za-z0-9._-]*$#', $logo)) {
        $logo = '';
    }
    return [
        'title' => $title,
        'description' => $description,
        'logo' => $logo,
        'name' => $name !== '' ? $name : 'Your Name',
    ];
}

/**
 * index.html carries the social-preview tags inline so crawlers that never run
 * js/card.js still see the current title/description/logo. The block between
 * the brand markers is regenerated after every successful save; if the markers
 * were removed the page is left untouched.
 */
function sync_site_head(string $indexFile, array $card): bool
{
    if (!is_file($indexFile)) return false;
    $html = (string)file_get_contents($indexFile);
    if ($html === '' || strpos($html, '<!--brand:start-->') === false || strpos($html, '<!--brand:end-->') === false) {
        return false;
    }
    $brand = brand_meta($card);
    $e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $title = $e($brand['title']);
    $description = $e($brand['description']);
    $siteName = $e($brand['name']);
    if ($brand['logo'] !== '') {
        $icons = '  <link rel="icon" href="' . $e($brand['logo']) . '">' . "\n"
            . '  <link rel="apple-touch-icon" href="' . $e($brand['logo']) . '">';
    } else {
        $icons = '  <link rel="icon" href="image/favicon.ico" sizes="any">' . "\n"
            . '  <link rel="icon" type="image/png" href="image/logo.png">' . "\n"
            . '  <link rel="apple-touch-icon" href="image/icon-192.png">';
    }
    $block = "<!--brand:start-->\n"
        . '  <title>' . $title . '</title>' . "\n"
        . '  <meta name="description" content="' . $description . '">' . "\n"
        . '  <!-- Deploying standalone: replace the relative URLs below with absolute ones' . "\n"
        . '       (https://your-domain.example/...) if you want rich link previews. -->' . "\n"
        . '  <link rel="canonical" href="index.html">' . "\n"
        . '  <meta property="og:type" content="profile">' . "\n"
        . '  <meta property="og:site_name" content="' . $siteName . '">' . "\n"
        . '  <meta property="og:url" content="index.html">' . "\n"
        . '  <meta property="og:title" content="' . $title . '">' . "\n"
        . '  <meta property="og:description" content="' . $description . '">' . "\n"
        . '  <meta property="og:image" content="image/og-cover.jpg">' . "\n"
        . '  <meta property="og:image:width" content="1200">' . "\n"
        . '  <meta property="og:image:height" content="630">' . "\n"
        . '  <meta name="twitter:card" content="summary_large_image">' . "\n"
        . '  <meta name="twitter:title" content="' . $title . '">' . "\n"
        . '  <meta name="twitter:description" content="' . $description . '">' . "\n"
        . '  <meta name="twitter:image" content="image/og-cover.jpg">' . "\n"
        . $icons . "\n"
        . '  <!--brand:end-->';
    // Callback so a "$" or "\" typed into the title can never be interpreted as
    // a backreference by preg_replace().
    $updated = preg_replace_callback('/<!--brand:start-->.*?<!--brand:end-->/s', static fn(): string => $block, $html, 1);
    if (!is_string($updated) || $updated === $html) return false;
    return atomic_write($indexFile, $updated);
}

/**
 * A POST value made safe for string functions.
 *
 * PHP parses `field[]=x` into an array, and in PHP 8 every trim()/strip_tags()/
 * preg_replace() call on that array is a fatal TypeError — a 500 on an otherwise
 * ordinary save. Anything that is not a scalar degrades to '' here, so a tampered
 * or malformed request can never take the save handler down.
 */
function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_scalar($value) ? trim((string)$value) : '';
}

/** Like post_string(), but keeps the caller's whitespace (rich text). */
function post_text(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_scalar($value) ? (string)$value : '';
}

/** One entry out of a posted array, as a trimmed string. */
function post_item($value): string
{
    return is_scalar($value) ? trim((string)$value) : '';
}

/**
 * Normalise a user-entered link into a safe href, or '' when it is not a
 * usable destination. Allowed: http(s), mailto, tel, in-page anchors and
 * root-relative paths. Everything else — javascript:, data:, vbscript:,
 * protocol-relative //host, URLs with embedded credentials, control
 * characters — is rejected. Bare domains like "example.com/x" become https.
 */
function normalize_link(string $value): string
{
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > 2048) return '';
    // Whitespace inside a URL is always a bug (javascript:alert(1) tricks).
    if (preg_match('/[\x00-\x20\x7F]/', $value)) return '';

    // Absolute http(s) — validate the host, allow unicode domains.
    if (preg_match('#^https?://#i', $value)) {
        if (parse_url($value, PHP_URL_USER) !== null || parse_url($value, PHP_URL_PASS) !== null) return '';
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $parts = parse_url($value);
            if (!is_array($parts) || empty($parts['host'])) return '';
            $host = $parts['host'];
            if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7E]/', $host)) {
                $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if ($ascii === false) return '';
                $host = $ascii;
            }
            $rebuilt = strtolower($parts['scheme']) . '://' . $host
                . (isset($parts['port']) ? ':' . $parts['port'] : '')
                . ($parts['path'] ?? '')
                . (isset($parts['query']) ? '?' . $parts['query'] : '')
                . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
            if (!filter_var($rebuilt, FILTER_VALIDATE_URL)) return '';
            return $rebuilt;
        }
        return $value;
    }

    if (preg_match('#^mailto:#i', $value)) {
        return preg_match('#^mailto:[^@\s]+@[^@\s]+(?:\?[^\s]*)?$#i', $value) ? $value : '';
    }
    if (preg_match('#^tel:#i', $value)) {
        return preg_match('#^tel:\+?[0-9().\-\s]{3,}$#', $value) ? $value : '';
    }

    // In-page anchor.
    if ($value[0] === '#') return preg_match('/^#[A-Za-z0-9_-]{1,64}$/', $value) ? $value : '';
    // Root-relative path.
    if ($value[0] === '/') return preg_match("#^/[A-Za-z0-9._~!$&'()*+,;=:@%/-]*$#", $value) ? $value : '';

    // Bare domain like "example.com" -> assume https.
    if (preg_match('#^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+(?:[/?#]\S*)?$#i', $value)) {
        return normalize_link('https://' . $value);
    }
    return '';
}

function sanitize_url(string $value): string
{
    return normalize_link($value);
}

/** True when the admin typed something that cannot become a safe link. */
function link_is_invalid(string $value): bool
{
    $value = trim($value);
    return $value !== '' && normalize_link($value) === '';
}

/**
 * Resize an uploaded image and save it as WebP (falls back to JPEG if the
 * GD build lacks WebP support). Returns the saved basename on success or ''
 * on failure. The destination path's extension is replaced to match the
 * actual output format.
 *
 * - caps both width and height to $maxDim
 * - applies EXIF orientation for JPEGs
 * - removes $oldPath when it points into data/uploads and differs from dest
 */
function resize_and_save_image(string $tmp, string &$dest, int $maxDim = 1600, string $oldPath = ''): string
{
    $info = @getimagesize($tmp);
    if (!$info) return '';
    [$w, $h, $type] = $info;
    if ($w < 1 || $h < 1) return '';

    $create = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $create = 'imagecreatefromjpeg'; break;
        case IMAGETYPE_PNG:  $create = 'imagecreatefrompng'; break;
        case IMAGETYPE_WEBP: $create = function_exists('imagecreatefromwebp') ? 'imagecreatefromwebp' : null; break;
        case IMAGETYPE_GIF:  $create = 'imagecreatefromgif'; break;
        default:
            if (defined('IMAGETYPE_AVIF') && $type === IMAGETYPE_AVIF && function_exists('imagecreatefromavif')) {
                $create = 'imagecreatefromavif'; break;
            }
            return '';
    }
    if (!$create || !function_exists($create)) return '';
    $src = @$create($tmp);
    if (!$src) return '';

    // Honour camera orientation so portraits don't arrive rotated.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $orientation = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
        if (in_array($orientation, [3, 6, 8], true)) {
            $rotated = null;
            if ($orientation === 3) $rotated = @imagerotate($src, 180, 0);
            if ($orientation === 6) $rotated = @imagerotate($src, -90, 0);
            if ($orientation === 8) $rotated = @imagerotate($src, 90, 0);
            if ($rotated) {
                imagedestroy($src);
                $src = $rotated;
                $w = imagesx($src);
                $h = imagesy($src);
            }
        }
    }

    $scale = min(1.0, $maxDim / max($w, $h));
    $newW = max(1, (int)round($w * $scale));
    $newH = max(1, (int)round($h * $scale));
    if ($newW !== $w || $newH !== $h) {
        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);
        $src = $dst;
    } elseif ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP || $type === IMAGETYPE_GIF) {
        imagealphablending($src, true);
        imagesavealpha($src, true);
    }

    $preferWebp = function_exists('imagewebp');
    $ext = $preferWebp ? 'webp' : 'jpg';
    $dest = preg_replace('/\.[^.]+$/', '.' . $ext, $dest);
    if ($dest === null || $dest === '') { imagedestroy($src); return ''; }

    $ok = $preferWebp ? @imagewebp($src, $dest, 82) : @imagejpeg($src, $dest, 84);
    imagedestroy($src);
    if (!$ok) return '';

    $saved = basename($dest);
    $oldPath = trim($oldPath);
    if ($oldPath !== '' && preg_match('#^data/uploads/([A-Za-z0-9._-]+)$#', $oldPath, $m)) {
        $oldFile = __DIR__ . '/../data/uploads/' . $m[1];
        if ($m[1] !== $saved && is_file($oldFile)) @unlink($oldFile);
    }
    return $saved;
}

function allowed_upload_image_tmp(string $tmp): bool
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
    if ($finfo) finfo_close($finfo);
    if ($mime !== '' && !in_array($mime, $allowed, true)) return false;
    $info = @getimagesize($tmp);
    if (!$info) return false;
    $type = $info[2] ?? 0;
    return in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true);
}

$cardFile = __DIR__ . '/../data/card.json';
$card = [];
if (is_file($cardFile)) {
    $decoded = json_decode((string)file_get_contents($cardFile), true);
    if (is_array($decoded)) $card = $decoded;
}

// Defensive shaping: card.json is hand-editable, so one malformed value must
// never take the whole panel down. Collections rendered by the panel are
// coerced to lists of arrays; tag values must stay scalar for implode().
foreach (['stats', 'socials', 'contacts', 'specialLinks', 'countries', 'gallery', 'sections'] as $listKey) {
    if (!array_key_exists($listKey, $card)) continue;
    if (!is_array($card[$listKey])) { $card[$listKey] = []; continue; }
    $rows = [];
    foreach ($card[$listKey] as $row) {
        if (!is_array($row)) continue;
        // htmlspecialchars() throws on arrays in PHP 8, so drop nested values
        // from flat lists; these collections only ever hold flat fields.
        foreach ($row as $field => $value) {
            if (!is_scalar($value) && $value !== null) unset($row[$field]);
        }
        $rows[] = $row;
    }
    $card[$listKey] = $rows;
}
foreach (['experience', 'education', 'certs', 'projects'] as $listKey) {
    if (isset($card[$listKey]) && is_array($card[$listKey])) {
        $card[$listKey] = array_values(array_filter($card[$listKey], 'is_array'));
    }
}
if (isset($card['tags']) && is_array($card['tags'])) {
    $card['tags'] = array_values(array_filter($card['tags'], 'is_scalar'));
} elseif (array_key_exists('tags', $card)) {
    $card['tags'] = [];
}
foreach (['name', 'role', 'slogan', 'photo', 'photo_shape', 'about', 'about_html', 'date_format', 'site_title', 'site_description', 'site_logo', 'font_family', 'bg_color', 'default_language', 'lang_switcher_visible'] as $scalarKey) {
    if (array_key_exists($scalarKey, $card) && !is_scalar($card[$scalarKey])) {
        unset($card[$scalarKey]);
    }
}
if (isset($card['banner']) && !is_array($card['banner'])) {
    unset($card['banner']);
}

$csrf = csrf_token();

// Content-site defaults for sections the panel can override.
$siteContent = [];
$siteFile = __DIR__ . '/../data/content-site.json';
if (is_file($siteFile)) {
    $decodedSite = json_decode((string)file_get_contents($siteFile), true);
    if (is_array($decodedSite)) $siteContent = $decodedSite;
}
$cardExperience = (isset($card['experience']) && is_array($card['experience'])) ? $card['experience'] : ($siteContent['experience'] ?? []);
$cardEducation = (isset($card['education']) && is_array($card['education'])) ? $card['education'] : ($siteContent['education'] ?? []);
$cardCerts = (isset($card['certs']) && is_array($card['certs'])) ? $card['certs'] : ($siteContent['certs'] ?? []);
$cardProjects = (isset($card['projects']) && is_array($card['projects'])) ? $card['projects'] : ($siteContent['projects'] ?? []);

// Save handler
$linkErrors = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'saveCard') {
    if (!secure_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    $aboutSource = post_text('about_html');
    if ($aboutSource === '') $aboutSource = post_text('about');
    $newCard = [
        'name' => post_string('name'),
        'role' => post_string('role'),
        'tags' => array_values(array_filter(array_map('trim', explode(',', post_string('tags'))), static fn($t) => $t !== '')),
        'stats' => [],
         'about' => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $aboutSource)), ENT_QUOTES, 'UTF-8'))),
        'slogan' => post_string('slogan'),
        'photo' => post_string('photo'),
        'contacts' => [],
        'socials' => [],
        'specialLinks' => [],
        'countries' => [],
        'banner' => null,
        'gallery' => []
    ];

    // Stats
    $statNums = isset($_POST['stat_num']) && is_array($_POST['stat_num']) ? $_POST['stat_num'] : [];
    $statLabels = isset($_POST['stat_label']) && is_array($_POST['stat_label']) ? $_POST['stat_label'] : [];
    foreach ($statNums as $i => $n) {
        $n = post_item($n); $l = post_item($statLabels[$i] ?? '');
        if ($n !== '' || $l !== '') $newCard['stats'][] = ['num' => $n, 'label' => $l];
    }

    // Contacts
    $cLabels = isset($_POST['contact_label']) && is_array($_POST['contact_label']) ? $_POST['contact_label'] : [];
    $cValues = isset($_POST['contact_value']) && is_array($_POST['contact_value']) ? $_POST['contact_value'] : [];
    $cUrls = isset($_POST['contact_url']) && is_array($_POST['contact_url']) ? $_POST['contact_url'] : [];
    $cIcons = isset($_POST['contact_icon']) && is_array($_POST['contact_icon']) ? $_POST['contact_icon'] : [];
    foreach ($cLabels as $i => $l) {
        $l = post_item($l); $v = post_item($cValues[$i] ?? ''); $rawUrl = post_item($cUrls[$i] ?? ''); $u = sanitize_url($rawUrl); $ic = post_item($cIcons[$i] ?? '');
        if (link_is_invalid($rawUrl)) $linkErrors++;
        // Resolve both new keys and old icon names to the shared app catalog so
        // the public card always gets a colour/logo it knows.
        $appKey = app_lookup($ic, $l, $appCatalog, $appAliases);
        if ($appKey !== '') $ic = $appKey;
        if ($l !== '' || $v !== '') $newCard['contacts'][] = ['label' => $l, 'value' => $v, 'url' => $u, 'icon' => $ic];
    }

    // Socials
    $sLabels = isset($_POST['social_label']) && is_array($_POST['social_label']) ? $_POST['social_label'] : [];
    $sUrls = isset($_POST['social_url']) && is_array($_POST['social_url']) ? $_POST['social_url'] : [];
    $sColors = isset($_POST['social_color']) && is_array($_POST['social_color']) ? $_POST['social_color'] : [];
    $sIcons = isset($_POST['social_icon']) && is_array($_POST['social_icon']) ? $_POST['social_icon'] : [];
    foreach ($sLabels as $i => $l) {
        $l = post_item($l); $rawUrl = post_item($sUrls[$i] ?? ''); $u = sanitize_url($rawUrl); $c = post_item($sColors[$i] ?? ''); $ic = post_item($sIcons[$i] ?? '');
        if (link_is_invalid($rawUrl)) $linkErrors++;
        // The picker sends the catalog key; the brand colour is authoritative
        // on the server so a stale/tampered hidden field cannot break it.
        $appKey = app_lookup($ic, $l, $appCatalog, $appAliases);
        if ($appKey !== '') {
            $ic = $appKey;
            $c = $appCatalog[$appKey]['color'];
        }
        if ($l !== '' || $u !== '') $newCard['socials'][] = ['label' => $l, 'url' => $u, 'color' => $c, 'icon' => $ic];
    }

    // Special links
    $slLabels = isset($_POST['special_label']) && is_array($_POST['special_label']) ? $_POST['special_label'] : [];
    $slSubs = isset($_POST['special_sub']) && is_array($_POST['special_sub']) ? $_POST['special_sub'] : [];
    $slUrls = isset($_POST['special_url']) && is_array($_POST['special_url']) ? $_POST['special_url'] : [];
    foreach ($slLabels as $i => $l) {
        $l = post_item($l); $s = post_item($slSubs[$i] ?? ''); $rawUrl = post_item($slUrls[$i] ?? ''); $u = sanitize_url($rawUrl);
        if (link_is_invalid($rawUrl)) $linkErrors++;
        if ($l !== '' || $u !== '') $newCard['specialLinks'][] = ['label' => $l, 'sub' => $s, 'url' => $u];
    }

    // Countries — the form only posts codes chosen from $countryOptions, and
    // the display name is looked up here rather than trusted from the request.
    $cCodes = isset($_POST['country_code']) && is_array($_POST['country_code']) ? $_POST['country_code'] : [];
    $seenCountries = [];
    foreach ($cCodes as $code) {
        if (!is_string($code)) continue;
        $code = strtolower(trim($code));
        if (!isset($countryOptions[$code]) || isset($seenCountries[$code])) continue;
        $seenCountries[$code] = true;
        $newCard['countries'][] = ['code' => $code, 'name' => $countryOptions[$code]];
    }

    // Banner
    $bannerImage = post_string('banner_image');
    // Only accept a banner image that already lives in the uploads directory.
    // A raw path from the form (../, absolute, or external URL) is dropped so
    // it can never become a path-traversal or off-site reference.
    if ($bannerImage !== '' && !preg_match('#^data/uploads/[A-Za-z0-9._-]+$#', $bannerImage)) {
        $bannerImage = '';
    }
    // Optional banner upload (overrides the path above)
    if (isset($_FILES['banner_upload']) && $_FILES['banner_upload']['error'] === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['banner_upload']['tmp_name'] ?? '');
        $size = (int)($_FILES['banner_upload']['size'] ?? 0);
        if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && allowed_upload_image_tmp($tmp)) {
            $dir = __DIR__ . '/../data/uploads';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $dest = $dir . '/card_banner_' . bin2hex(random_bytes(6)) . '.webp';
            $saved = resize_and_save_image($tmp, $dest, 1600, $bannerImage);
            if ($saved !== '') {
                $bannerImage = 'data/uploads/' . $saved;
            }
        }
    }
    $rawBannerUrl = post_string('banner_url');
    if (link_is_invalid($rawBannerUrl)) $linkErrors++;
    $bannerUrl = sanitize_url($rawBannerUrl);
    $bannerTitle = post_string('banner_title');
    $bannerSub = post_string('banner_sub');
    if ($bannerImage !== '') {
        $newCard['banner'] = [
            'image' => $bannerImage,
            'url' => $bannerUrl,
            'title' => $bannerTitle,
            'subtitle' => $bannerSub
        ];
    } else {
        unset($newCard['banner']);
    }

    // Profile photo upload (auto-take from panel)
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['photo_upload']['tmp_name'] ?? '');
        $size = (int)($_FILES['photo_upload']['size'] ?? 0);
        if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && allowed_upload_image_tmp($tmp)) {
            $dir = __DIR__ . '/../data/uploads';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $oldPhoto = (isset($card['photo']) && is_string($card['photo'])) ? $card['photo'] : '';
            $dest = $dir . '/card_' . bin2hex(random_bytes(6)) . '.webp';
            $saved = resize_and_save_image($tmp, $dest, 1200, $oldPhoto);
            if ($saved !== '') {
                $newCard['photo'] = 'data/uploads/' . $saved;
            }
        }
    }

    // Browser-tab identity: title, description and logo. The logo can be an
    // existing upload/image path or a fresh upload; anything else (../, remote
    // URLs, odd extensions) is discarded so it can never become an injection.
    $siteLogo = post_string('site_logo');
    if ($siteLogo !== '' && !preg_match('#^(?:data/uploads|image)/[A-Za-z0-9_-][A-Za-z0-9._-]*$#', $siteLogo)) {
        $siteLogo = '';
    }
    if (post_string('site_logo_clear') === '1') {
        $siteLogo = '';
    }
    if (isset($_FILES['site_logo_upload']) && $_FILES['site_logo_upload']['error'] === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['site_logo_upload']['tmp_name'] ?? '');
        $size = (int)($_FILES['site_logo_upload']['size'] ?? 0);
        if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && allowed_upload_image_tmp($tmp)) {
            $dir = __DIR__ . '/../data/uploads';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $dest = $dir . '/site_logo_' . bin2hex(random_bytes(6)) . '.webp';
            $saved = resize_and_save_image($tmp, $dest, 512, $siteLogo);
            if ($saved !== '') {
                $siteLogo = 'data/uploads/' . $saved;
            }
        }
    }
    $newCard['site_title'] = mb_substr(trim((string)preg_replace('/\s+/u', ' ', post_string('site_title'))), 0, 80);
    $newCard['site_description'] = mb_substr(trim((string)preg_replace('/\s+/u', ' ', post_string('site_description'))), 0, 200);
    $newCard['site_logo'] = $siteLogo;
    $allowedFonts = [];
    $fontCatalogFile = __DIR__ . '/../fonts/fonts.json';
    if (is_file($fontCatalogFile)) {
        $decodedFonts = json_decode((string)file_get_contents($fontCatalogFile), true);
        if (is_array($decodedFonts)) {
            foreach ($decodedFonts as $f) {
                if (is_array($f) && isset($f['name']) && is_string($f['name'])) $allowedFonts[] = $f['name'];
            }
        }
    }
    $chosenFont = post_string('font_family');
    if ($chosenFont !== '' && !in_array($chosenFont, $allowedFonts, true)) $chosenFont = '';
    $newCard['font_family'] = $chosenFont;
    $bgColor = post_string('bg_color');
    if ($bgColor !== '' && !preg_match('/^#[0-9a-f]{3,8}$/i', $bgColor)) $bgColor = '';
    $newCard['bg_color'] = $bgColor;
    $photoShape = post_string('photo_shape');
    if (!in_array($photoShape, ['circle', 'square', 'rounded'], true)) $photoShape = 'circle';
    $newCard['photo_shape'] = $photoShape;
    $allowedLangs = ['fa', 'en'];
    $defLang = post_string('default_language');
    if ($defLang !== '' && !in_array($defLang, $allowedLangs, true)) $defLang = '';
    $newCard['default_language'] = $defLang;
    $newCard['lang_switcher_visible'] = (post_string('lang_switcher_visible') === '1') ? '1' : '0';

    // Gallery
    $gallery = [];
    $galleryImages = isset($_POST['gallery_image']) && is_array($_POST['gallery_image']) ? $_POST['gallery_image'] : [];
    $galleryTitles = isset($_POST['gallery_title']) && is_array($_POST['gallery_title']) ? $_POST['gallery_title'] : [];
    $galleryUrls = isset($_POST['gallery_url']) && is_array($_POST['gallery_url']) ? $_POST['gallery_url'] : [];
    if ($galleryImages !== []) {
        foreach ($galleryImages as $i => $img) {
            $img = post_item($img);
            if (isset($_FILES['gallery_upload']) && isset($_FILES['gallery_upload']['name'][$i]) && $_FILES['gallery_upload']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = (string)($_FILES['gallery_upload']['tmp_name'][$i] ?? '');
                $size = (int)($_FILES['gallery_upload']['size'][$i] ?? 0);
                if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && allowed_upload_image_tmp($tmp)) {
                    $dir = __DIR__ . '/../data/uploads';
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    $dest = $dir . '/card_' . bin2hex(random_bytes(6)) . '.webp';
                    $saved = resize_and_save_image($tmp, $dest, 1600, $img);
                    if ($saved !== '') {
                        $img = 'data/uploads/' . $saved;
                    }
                }
            }
            if ($img !== '' && !preg_match('#^data/uploads/[A-Za-z0-9._-]+$#', $img)) {
                $img = '';
            }
            if ($img === '') continue;
            $rawGalleryUrl = post_item($galleryUrls[$i] ?? '');
            if (link_is_invalid($rawGalleryUrl)) $linkErrors++;
            $gallery[] = [
                'image' => $img,
                'title' => post_item($galleryTitles[$i] ?? ''),
                'url' => sanitize_url($rawGalleryUrl)
            ];
        }
    }
    $newCard['date_format'] = post_string('date_format') === 'jalali' ? 'jalali' : 'gregorian';
    $newCard['gallery'] = $gallery;

    // Experience override (admin-managed, Jalali start/end dates).
    if (isset($_POST['exp_role']) && is_array($_POST['exp_role'])) {
        $experience = [];
        foreach ($_POST['exp_role'] as $i => $role) {
            $role = is_string($role) ? trim($role) : '';
            $company = isset($_POST['exp_company'][$i]) && is_string($_POST['exp_company'][$i]) ? trim($_POST['exp_company'][$i]) : '';
            $period = isset($_POST['exp_period'][$i]) && is_string($_POST['exp_period'][$i]) ? trim($_POST['exp_period'][$i]) : '';
            $periodJalali = isset($_POST['exp_period_jalali'][$i]) && is_string($_POST['exp_period_jalali'][$i]) ? trim($_POST['exp_period_jalali'][$i]) : '';
            $details = isset($_POST['exp_details'][$i]) && is_string($_POST['exp_details'][$i]) ? trim(str_replace(["\r\n", "\r"], "\n", $_POST['exp_details'][$i])) : '';
            $start = parse_jalali_value($_POST['exp_start'][$i] ?? '');
            $isCurrent = !empty($_POST['exp_current_val'][$i]);
            $end = $isCurrent ? null : parse_jalali_value($_POST['exp_end'][$i] ?? '');
            if ($role === '' && $company === '' && $period === '' && $details === '' && !$start && !$end) continue;

            $item = ['role' => $role, 'company' => $company, 'period' => $period];
            if ($periodJalali !== '') $item['period_jalali'] = $periodJalali;
            if ($details !== '') $item['details'] = $details;
            if ($start || $end) {
                // An end date picked without a start is still real data: keep it
                // (the public card shows it as the only period it knows).
                if ($start) {
                    $item['start'] = $start;
                    $gs = jalali_to_gregorian($start['jy'], $start['jm'], $start['jd']);
                    if ($gs) $item['start_g'] = sprintf('%04d-%02d-%02d', $gs['gy'], $gs['gm'], $gs['gd']);
                }
                $item['current'] = $isCurrent && (bool)$start;
                if ($end) {
                    $item['end'] = $end;
                    $ge = jalali_to_gregorian($end['jy'], $end['jm'], $end['jd']);
                    if ($ge) $item['end_g'] = sprintf('%04d-%02d-%02d', $ge['gy'], $ge['gm'], $ge['gd']);
                }
            }
            $experience[] = $item;
        }
        $newCard['experience'] = $experience;
    } elseif (post_string('experience_managed') === '1') {
        // Every row was removed on purpose: an explicit empty list clears the
        // old entries rather than falling back to the content-site defaults.
        $newCard['experience'] = [];
    }


    // Education override (admin-managed, Jalali date received).
    $eduDegreeSrc = isset($_POST['edu_degree']) && is_array($_POST['edu_degree']) ? $_POST['edu_degree'] : (isset($_POST['edu_title']) && is_array($_POST['edu_title']) ? $_POST['edu_title'] : null);
    if (is_array($eduDegreeSrc)) {
        $education = [];
        $eduFiles = (isset($_FILES['edu_file']) && is_array($_FILES['edu_file']['name'] ?? null)) ? $_FILES['edu_file'] : null;
        $eduFileRemove = (isset($_POST['edu_file_remove']) && is_array($_POST['edu_file_remove'])) ? $_POST['edu_file_remove'] : [];
        foreach ($eduDegreeSrc as $i => $degree) {
            $degree = is_string($degree) ? trim($degree) : '';
            $field = isset($_POST['edu_field'][$i]) && is_string($_POST['edu_field'][$i]) ? trim($_POST['edu_field'][$i]) : '';
            // Backward compat: older saves posted edu_title only.
            $legacyTitle = isset($_POST['edu_title'][$i]) && is_string($_POST['edu_title'][$i]) ? trim($_POST['edu_title'][$i]) : '';
            if ($degree === '' && $field === '' && $legacyTitle !== '' && !isset($_POST['edu_degree'])) {
                $degree = $legacyTitle;
            }
            $title = trim($degree . ($degree !== '' && $field !== '' ? ' — ' : '') . $field);
            $org = isset($_POST['edu_org'][$i]) && is_string($_POST['edu_org'][$i]) ? trim($_POST['edu_org'][$i]) : '';
            $meta = isset($_POST['edu_meta'][$i]) && is_string($_POST['edu_meta'][$i]) ? trim($_POST['edu_meta'][$i]) : '';
            $metaJalali = isset($_POST['edu_meta_jalali'][$i]) && is_string($_POST['edu_meta_jalali'][$i]) ? trim($_POST['edu_meta_jalali'][$i]) : '';
            $date = parse_jalali_value($_POST['edu_date'][$i] ?? '');
            $dateG = isset($_POST['edu_date_g'][$i]) && is_string($_POST['edu_date_g'][$i]) ? trim($_POST['edu_date_g'][$i]) : '';
            if ($title === '' && $org === '' && $meta === '' && !$date) continue;

            $item = ['title' => $title, 'org' => $org, 'meta' => $meta];
            if ($degree !== '') $item['degree_type'] = $degree;
            if ($field !== '') $item['field'] = $field;
            if ($metaJalali !== '') $item['meta_jalali'] = $metaJalali;
            if ($date) {
                $item['date'] = $date;
                $item['date_label_jalali'] = format_jalali_date($date);
                $gs = jalali_to_gregorian($date['jy'], $date['jm'], $date['jd']);
                if ($gs) {
                    $item['date_g'] = sprintf('%04d-%02d-%02d', $gs['gy'], $gs['gm'], $gs['gd']);
                    $item['date_label_en'] = (string)$gs['gy'];
                } elseif ($dateG !== '') {
                    $item['date_g'] = $dateG;
                    $item['date_label_en'] = substr($dateG, 0, 4);
                }
            } elseif ($dateG !== '') {
                $item['date_g'] = $dateG;
                $item['date_label_en'] = substr($dateG, 0, 4);
            }
            // Optional diploma/certificate file (image or PDF).
            $existingEduFile = (isset($cardEducation[$i]) && is_array($cardEducation[$i])) ? (string)($cardEducation[$i]['file'] ?? '') : '';
            if (isset($eduFileRemove[$i]) && $eduFileRemove[$i] === '1' && $existingEduFile !== '') {
                $old = basename($existingEduFile);
                $oldPath = __DIR__ . '/../' . ltrim($existingEduFile, '/');
                if (preg_match('/^[A-Za-z0-9._-]+$/', $old) && is_file($oldPath)) @unlink($oldPath);
                $existingEduFile = '';
            }
            if ($eduFiles !== null && isset($eduFiles['name'][$i]) && $eduFiles['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = (string)$eduFiles['tmp_name'][$i];
                $orig = basename((string)$eduFiles['name'][$i]);
                $size = (int)$eduFiles['size'][$i];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $imageExts = ['jpg','jpeg','png','webp','gif'];
                $pdfExts = ['pdf'];
                $allowed = array_merge($imageExts, $pdfExts);
                if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && in_array($ext, $allowed, true)) {
                    $dest = 'data/uploads/edu_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $destAbs = __DIR__ . '/../' . $dest;
                    $saved = '';
                    if (in_array($ext, $imageExts, true) && allowed_upload_image_tmp($tmp)) {
                        $savedName = resize_and_save_image($tmp, $destAbs, 1600, $existingEduFile);
                        if ($savedName !== '') $saved = 'data/uploads/' . $savedName;
                    } elseif ($ext === 'pdf') {
                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                        $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                        if ($finfo) finfo_close($finfo);
                        if ($mime === '' || $mime === 'application/pdf' || $mime === 'application/octet-stream') {
                            if (@move_uploaded_file($tmp, $destAbs)) $saved = $dest;
                        }
                    }
                    if ($saved !== '') {
                        if ($existingEduFile !== '') {
                            $old = basename($existingEduFile);
                            $oldPath = __DIR__ . '/../' . ltrim($existingEduFile, '/');
                            if (preg_match('/^[A-Za-z0-9._-]+$/', $old) && is_file($oldPath)) @unlink($oldPath);
                        }
                        $existingEduFile = $saved;
                    }
                }
            }
            if ($existingEduFile !== '') $item['file'] = $existingEduFile;

            $education[] = $item;
        }
        $newCard['education'] = $education;
    } elseif (post_string('education_managed') === '1') {
        $newCard['education'] = [];
    }

    // Certifications override (admin-managed, Jalali date received).
    if (isset($_POST['cert_title']) && is_array($_POST['cert_title'])) {
        $certs = [];
        $certFiles = (isset($_FILES['cert_file']) && is_array($_FILES['cert_file']['name'] ?? null)) ? $_FILES['cert_file'] : null;
        $certFileRemove = (isset($_POST['cert_file_remove']) && is_array($_POST['cert_file_remove'])) ? $_POST['cert_file_remove'] : [];
        foreach ($_POST['cert_title'] as $i => $title) {
            $title = is_string($title) ? trim($title) : '';
            $org = isset($_POST['cert_org'][$i]) && is_string($_POST['cert_org'][$i]) ? trim($_POST['cert_org'][$i]) : '';
            $meta = isset($_POST['cert_meta'][$i]) && is_string($_POST['cert_meta'][$i]) ? trim($_POST['cert_meta'][$i]) : '';
            $metaJalali = isset($_POST['cert_meta_jalali'][$i]) && is_string($_POST['cert_meta_jalali'][$i]) ? trim($_POST['cert_meta_jalali'][$i]) : '';
            $date = parse_jalali_value($_POST['cert_date'][$i] ?? '');
            $dateG = isset($_POST['cert_date_g'][$i]) && is_string($_POST['cert_date_g'][$i]) ? trim($_POST['cert_date_g'][$i]) : '';
            if ($title === '' && $org === '' && $meta === '' && !$date) continue;

            $item = ['title' => $title, 'org' => $org, 'meta' => $meta];
            if ($metaJalali !== '') $item['meta_jalali'] = $metaJalali;
            if ($date) {
                $item['date'] = $date;
                $item['date_label_jalali'] = format_jalali_date($date);
                $gs = jalali_to_gregorian($date['jy'], $date['jm'], $date['jd']);
                if ($gs) {
                    $item['date_g'] = sprintf('%04d-%02d-%02d', $gs['gy'], $gs['gm'], $gs['gd']);
                    $item['date_label_en'] = (string)$gs['gy'];
                } elseif ($dateG !== '') {
                    $item['date_g'] = $dateG;
                    $item['date_label_en'] = substr($dateG, 0, 4);
                }
            } elseif ($dateG !== '') {
                $item['date_g'] = $dateG;
                $item['date_label_en'] = substr($dateG, 0, 4);
            }
            // Optional certificate file (image or PDF).
            $existingFile = (isset($cardCerts[$i]) && is_array($cardCerts[$i])) ? (string)($cardCerts[$i]['file'] ?? '') : '';
            if (isset($certFileRemove[$i]) && $certFileRemove[$i] === '1' && $existingFile !== '') {
                $old = basename($existingFile);
                $oldPath = __DIR__ . '/../' . ltrim($existingFile, '/');
                if (preg_match('/^[A-Za-z0-9._-]+$/', $old) && is_file($oldPath)) @unlink($oldPath);
                $existingFile = '';
            }
            if ($certFiles !== null && isset($certFiles['name'][$i]) && $certFiles['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = (string)$certFiles['tmp_name'][$i];
                $orig = basename((string)$certFiles['name'][$i]);
                $size = (int)$certFiles['size'][$i];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $imageExts = ['jpg','jpeg','png','webp','gif'];
                $pdfExts = ['pdf'];
                $allowed = array_merge($imageExts, $pdfExts);
                if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && in_array($ext, $allowed, true)) {
                    $dest = 'data/uploads/cert_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $destAbs = __DIR__ . '/../' . $dest;
                    $saved = '';
                    if (in_array($ext, $imageExts, true) && allowed_upload_image_tmp($tmp)) {
                        $savedName = resize_and_save_image($tmp, $destAbs, 1600, $existingFile);
                        if ($savedName !== '') $saved = 'data/uploads/' . $savedName;
                    } elseif ($ext === 'pdf') {
                        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                        $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                        if ($finfo) finfo_close($finfo);
                        if ($mime === '' || $mime === 'application/pdf' || $mime === 'application/octet-stream') {
                            if (@move_uploaded_file($tmp, $destAbs)) $saved = $dest;
                        }
                    }
                    if ($saved !== '') {
                        if ($existingFile !== '') {
                            $old = basename($existingFile);
                            $oldPath = __DIR__ . '/../' . ltrim($existingFile, '/');
                            if (preg_match('/^[A-Za-z0-9._-]+$/', $old) && is_file($oldPath)) @unlink($oldPath);
                        }
                        $existingFile = $saved;
                    }
                }
            }
            if ($existingFile !== '') $item['file'] = $existingFile;

            $certs[] = $item;
        }
        $newCard['certs'] = $certs;
    } elseif (post_string('certs_managed') === '1') {
        $newCard['certs'] = [];
    }

    // Projects override (simple, admin-managed).
    if (isset($_POST['proj_title']) && is_array($_POST['proj_title'])) {
        $projects = [];
        foreach ($_POST['proj_title'] as $i => $title) {
            $title = is_string($title) ? trim($title) : '';
            $url = isset($_POST['proj_url'][$i]) && is_string($_POST['proj_url'][$i]) ? trim($_POST['proj_url'][$i]) : '';
            if ($title === '' && $url === '') continue;
            $projects[] = ['title' => $title, 'url' => $url];
        }
        $newCard['projects'] = $projects;
    } elseif (post_string('projects_managed') === '1') {
        $newCard['projects'] = [];
    }

    // Rich-text "About me" override (optional). Stored as sanitized HTML.
    if (isset($_POST['about_html'])) {
        $ah = is_string($_POST['about_html']) ? trim($_POST['about_html']) : '';
        $ah = strip_tags($ah, '<b><strong><i><em><u><br><p>');
        if ($ah === '') {
            unset($newCard['about_html']);
        } else {
            $newCard['about_html'] = $ah;
        }
    }

    // Resume upload/download (stored outside public web paths and served by resume.php).
    $resume = (isset($card['resume']) && is_array($card['resume'])) ? $card['resume'] : [];
    $resumeTitle = post_string('resume_title');
    $resumeTitle = mb_substr(trim((string)preg_replace('/\s+/u', ' ', $resumeTitle)), 0, 120);
    if ($resumeTitle !== '') $resume['title'] = $resumeTitle; else unset($resume['title']);
    if (post_string('resume_remove') === '1' && !empty($resume['file']) && is_string($resume['file'])) {
        $old = basename($resume['file']);
        $oldPath = __DIR__ . '/../data/resume/' . $old;
        if (preg_match('/^[A-Za-z0-9._-]+$/', $old) && is_file($oldPath)) @unlink($oldPath);
        $resume = [];
    }
    if (isset($_FILES['resume_file']) && ($_FILES['resume_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($_FILES['resume_file']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
            $tmp = (string)($_FILES['resume_file']['tmp_name'] ?? '');
            $orig = basename((string)($_FILES['resume_file']['name'] ?? ''));
            $size = (int)($_FILES['resume_file']['size'] ?? 0);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowedExt = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
            $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
            if ($finfo) finfo_close($finfo);
            $mimeOk = $mime === '' ? isset($allowedExt[$ext]) : in_array($mime, $allowedExt, true) || ($ext === 'doc' && $mime === 'application/octet-stream');
            if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 10 * 1024 * 1024 && isset($allowedExt[$ext]) && $mimeOk) {
                $rdir = __DIR__ . '/../data/resume';
                if (!is_dir($rdir)) @mkdir($rdir, 0755, true);
                $fname = 'resume_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (@move_uploaded_file($tmp, $rdir . '/' . $fname)) {
                    if (!empty($resume['file']) && is_string($resume['file'])) {
                        $old = basename($resume['file']);
                        if ($old !== $fname && preg_match('/^[A-Za-z0-9._-]+$/', $old)) {
                            $oldPath = $rdir . '/' . $old;
                            if (is_file($oldPath)) @unlink($oldPath);
                        }
                    }
                    $resume['file'] = $fname;
                    $resume['original_name'] = mb_substr(trim((string)preg_replace('/\s+/u', ' ', $orig)), 0, 180);
                    $resume['mime'] = $allowedExt[$ext];
                    $resume['size'] = $size;
                    $resume['updated_at'] = date('c');
                }
            } else {
                $linkErrors++; // surface a validation issue to the admin
            }
        } elseif (($_FILES['resume_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE) {
            $linkErrors++;
        }
    }
    if (!empty($resume['file']) && is_string($resume['file']) && preg_match('/^[A-Za-z0-9._-]+$/', basename($resume['file']))) {
        $newCard['resume'] = $resume;
    } else {
        unset($newCard['resume']);
    }

    // Map / location (address parts + map provider links).
    $mapCountry = trim((string)($_POST['map_country'] ?? ''));
    $mapProvince = trim((string)($_POST['map_province'] ?? ''));
    $mapCity = trim((string)($_POST['map_city'] ?? ''));
    $mapDistrict = trim((string)($_POST['map_district'] ?? ''));
    $mapAddress = (string)($_POST['map_address'] ?? '');
    $mapAddress = trim((string)preg_replace('/\s+/u', ' ', $mapAddress));
    $mapAddress = mb_substr($mapAddress, 0, 500);
    foreach (['country' => &$mapCountry, 'province' => &$mapProvince, 'city' => &$mapCity, 'district' => &$mapDistrict] as $k => &$v) {
        $v = mb_substr(trim((string)preg_replace('/\s+/u', ' ', $v)), 0, 120);
    }
    unset($v);
    $mapBalad = sanitize_url((string)($_POST['map_balad'] ?? ''));
    $mapNeshan = sanitize_url((string)($_POST['map_neshan'] ?? ''));
    $mapGoogle = sanitize_url((string)($_POST['map_google'] ?? ''));
    $hasMap = $mapCountry !== '' || $mapProvince !== '' || $mapCity !== '' || $mapDistrict !== '' || $mapAddress !== '' || $mapBalad !== '' || $mapNeshan !== '' || $mapGoogle !== '';
    if ($hasMap) {
        $map = [];
        if ($mapCountry !== '') $map['country'] = $mapCountry;
        if ($mapProvince !== '') $map['province'] = $mapProvince;
        if ($mapCity !== '') $map['city'] = $mapCity;
        if ($mapDistrict !== '') $map['district'] = $mapDistrict;
        if ($mapAddress !== '') $map['address'] = $mapAddress;
        if ($mapBalad !== '') $map['balad'] = $mapBalad;
        if ($mapNeshan !== '') $map['neshan'] = $mapNeshan;
        if ($mapGoogle !== '') $map['google'] = $mapGoogle;
        $newCard['map'] = $map;
    } else {
        unset($newCard['map']);
    }

    // Sections — fixed keys reorder the existing card sections, any other key
    // (custom_*) carries its own title/text and is built by js/card.js.
    $sections = [];
    $sectionLabels = isset($_POST['section_label']) && is_array($_POST['section_label']) ? $_POST['section_label'] : [];
    $sectionContents = isset($_POST['section_content']) && is_array($_POST['section_content']) ? $_POST['section_content'] : [];
    $seenSectionKeys = [];
    if (isset($_POST['section_key']) && is_array($_POST['section_key'])) {
        foreach ($_POST['section_key'] as $i => $key) {
            $key = is_scalar($key) ? strtolower(trim((string)$key)) : '';
            if (!preg_match('/^[a-z0-9_-]{1,40}$/', $key) || isset($seenSectionKeys[$key])) continue;
            $label = is_string($sectionLabels[$i] ?? null) ? trim($sectionLabels[$i]) : '';
            $label = trim((string)preg_replace('/\s+/u', ' ', $label));
            $label = mb_substr($label, 0, 80);
            $isCustom = !in_array($key, $knownSectionKeys, true);
            if ($label === '') $label = $isCustom ? 'بخش جدید' : $key;

            $enabled = true;
            if (isset($_POST['section_enabled'][$i]) && is_scalar($_POST['section_enabled'][$i])) {
                $enabled = ($_POST['section_enabled'][$i] == '1');
            } elseif (isset($_POST['section_enabled_hidden'][$i]) && is_scalar($_POST['section_enabled_hidden'][$i])) {
                $enabled = ($_POST['section_enabled_hidden'][$i] == '1');
            }

            $section = [
                'key' => $key,
                'label' => $label,
                'enabled' => $enabled,
                'order' => max(1, intval($_POST['section_order'][$i] ?? ($i + 1)))
            ];

            if ($isCustom) {
                $content = is_string($sectionContents[$i] ?? null) ? $sectionContents[$i] : '';
                $content = str_replace(["\r\n", "\r"], "\n", trim($content));
                $content = mb_substr($content, 0, 2000);
                if ($content !== '') $section['content'] = $content;
            }

            $seenSectionKeys[$key] = true;
            $sections[] = $section;
        }
    }
    if (empty($sections)) {
        $sections = [
            ['key' => 'countries', 'label' => "Countries I've Worked In", 'enabled' => true, 'order' => 1],
            ['key' => 'about', 'label' => 'About Me', 'enabled' => true, 'order' => 2],
            ['key' => 'contact', 'label' => 'Contact', 'enabled' => true, 'order' => 3],
            ['key' => 'socials', 'label' => 'Social Media', 'enabled' => true, 'order' => 4],
            ['key' => 'experience', 'label' => 'Work Experience', 'enabled' => true, 'order' => 5],
            ['key' => 'skills', 'label' => 'Skills', 'enabled' => true, 'order' => 6],
            ['key' => 'education', 'label' => 'Education', 'enabled' => true, 'order' => 7],
            ['key' => 'certs', 'label' => 'Certifications', 'enabled' => true, 'order' => 8],
            ['key' => 'gallery', 'label' => 'Gallery', 'enabled' => true, 'order' => 9],
            ['key' => 'portfolio', 'label' => 'Projects', 'enabled' => true, 'order' => 10],
            ['key' => 'resume', 'label' => 'Resume', 'enabled' => true, 'order' => 11]
        ];
    }
    usort($sections, function($a, $b){ return ($a['order'] <=> $b['order']); });
    $newCard['sections'] = array_values($sections);

    $json = json_encode($newCard, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // atomic_write() is the same tmp+rename helper credentials.php uses, so a
    // full disk or a dropped rename can never leave a half-written card.json
    // behind — and "saved" is only reported when the bytes actually landed.
    if ($json !== false && atomic_write($cardFile, $json)) {
        $msg = 'saved';
        // Keep the static head (social previews / crawlers that skip JS) in
        // sync with the panel while the live card is rewritten by js/card.js.
        sync_site_head(__DIR__ . '/../index.html', $newCard);
    } else {
        $msg = 'error';
    }
    // Return to the section the admin was editing. The hidden field is filled
    // by the submit listener; anything unexpected is dropped so it can never
    // become a header injection or point at a missing section.
    $returnSection = post_string('return_section');
    $returnHash = preg_match('/^sec-[a-z0-9-]{1,40}$/', $returnSection) ? '#' . $returnSection : '';
    $linkParam = $linkErrors > 0 ? '&links=' . min(99, $linkErrors) : '';
    header('Location: card.php?msg=' . $msg . $linkParam . $returnHash);
    exit;
}

$msg = $_GET['msg'] ?? '';
$linkWarn = isset($_GET['links']) ? min(99, max(0, (int)$_GET['links'])) : 0;

// Change-password handler. Lives outside the card form (see the markup below)
// so a password submit can never be mistaken for a card save.
$pwError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'changePassword') {
    require_valid_csrf();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!is_string($current) || !is_string($new) || !is_string($confirm)) {
        $pwError = 'درخواست نامعتبر است.';
    } elseif (!auth_verify($current)) {
        $pwError = 'رمز عبور فعلی درست نیست.';
    } elseif (mb_strlen($new) < 6) {
        $pwError = 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.';
    } elseif ($new !== $confirm) {
        $pwError = 'تکرار رمز عبور جدید مطابقت ندارد.';
    } elseif (!auth_set_password($new)) {
        $pwError = 'ذخیرهٔ رمز عبور ممکن نشد. دسترسی نوشتن به فایل admin/auth_hash.php را بررسی کنید.';
    } else {
        // A password change ends any other signed-in session that could still
        // be riding the old credential, then sends the admin back to a clean
        // page so the form cannot be re-submitted on refresh.
        session_regenerate_id(true);
        header('Location: card.php?msg=password');
        exit;
    }
}

// Display-name handler: lets the admin change the greeting name from the panel.
$nameError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'changeDisplayName') {
    require_valid_csrf();
    $postedName = $_POST['display_name'] ?? '';
    if (!is_string($postedName)) {
        $nameError = 'درخواست نامعتبر است.';
    } elseif (mb_strlen(trim($postedName)) > 60) {
        $nameError = 'نام نمایشی باید حداکثر ۶۰ کاراکتر باشد.';
    } elseif (!auth_set_display_name(trim($postedName))) {
        $nameError = 'ذخیرهٔ نام نمایشی ممکن نشد.';
    } else {
        session_regenerate_id(true);
        header('Location: card.php?msg=name');
        exit;
    }
}

$currentUser = (string)(load_auth()['username'] ?? 'admin');
$displayName = auth_display_name();
$gateError = '';
$gateOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'setLoginGate') {
    require_valid_csrf();
    $newGate = trim((string)($_POST['login_gate'] ?? ''));
    if ($newGate === '') {
        secrets_set('login_gate', '');
        $gateOk = true;
    } elseif (mb_strlen($newGate) < 8) {
        $gateError = 'کلید باید حداقل ۸ کاراکتر باشد.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $newGate)) {
        $gateError = 'فقط حروف، اعداد، نقطه، خط تیره و زیرخط مجاز است.';
    } else {
        secrets_set('login_gate', $newGate);
        $gateOk = true;
    }
    if ($gateOk) {
        session_regenerate_id(true);
        header('Location: card.php?msg=gate');
        exit;
    }
}
$displayName = auth_display_name();
$viewCount = 0;
$viewsFile = __DIR__ . '/../data/views.json';
if (is_file($viewsFile)) {
    $vd = json_decode((string)file_get_contents($viewsFile), true);
    if (is_array($vd)) $viewCount = (int)($vd['total'] ?? 0);
}
$aboutHtml = '';
if (isset($card['about_html']) && is_string($card['about_html']) && trim($card['about_html']) !== '') {
    $aboutHtml = strip_tags($card['about_html'], '<b><strong><i><em><u><br><p>');
} elseif (!empty($card['about'])) {
    // One source of truth: if only the legacy plain "about" exists, seed the
    // rich editor with it so the two places always show the same text.
    $aboutHtml = nl2br(htmlspecialchars((string)$card['about'], ENT_QUOTES, 'UTF-8'));
}
$brandMeta = brand_meta($card);
$panelLogo = $brandMeta['logo'];
$brandFallbackTitle = $brandMeta['name'] . ' — Digital Business Card';
$brandFallbackDescription = trim($brandMeta['name'] . (trim((string)($card['role'] ?? '')) !== '' ? ' — ' . trim((string)$card['role']) : ''));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مدیریت کارت ویزیت دیجیتال | <?= htmlspecialchars($displayName !== '' ? $displayName : 'Your Name') ?></title>
<link rel="stylesheet" href="../css/fonts.css">
<link rel="stylesheet" href="../css/fonts-catalog.css">
<style>
:root{
  color-scheme:light;
  --bg:#f3f6fb;
  --surface:#ffffff;
  --surface-soft:#f8fafc;
  --surface-2:#f1f5f9;
  --line:#e5eaf2;
  --line-strong:#d6deea;
  --ink:#0f172a;
  --ink-2:#334155;
  --muted:#64748b;
  --faint:#94a3b8;
  --accent:#2563eb;
  --accent-strong:#1d4ed8;
  --accent-soft:#eaf1ff;
  --ok:#15803d;
  --ok-soft:#ecfdf3;
  --warn:#b45309;
  --warn-soft:#fffbeb;
  --danger:#dc2626;
  --danger-soft:#fef2f2;
  --radius-lg:18px;
  --radius:14px;
  --radius-sm:10px;
  --shadow-sm:0 1px 2px rgba(15,23,42,.04);
  --sidebar:268px;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
body{background:var(--bg);color:var(--ink);font-family:Tahoma,'Segoe UI',system-ui,sans-serif;font-size:14px;line-height:1.7;min-height:100vh}
a{color:inherit;text-decoration:none}
a:focus-visible,button:focus-visible,select:focus-visible{outline:2px solid rgba(37,99,235,.55);outline-offset:2px}
button,input,select,textarea{font-family:inherit}
svg{display:block}
.app-shell{display:flex;min-height:100vh}
.sidebar{position:sticky;top:0;height:100vh;width:var(--sidebar);flex:none;display:flex;flex-direction:column;gap:18px;padding:22px 16px 18px;background:var(--surface);border-inline-end:1px solid var(--line);box-shadow:0 0 0 1px rgba(15,23,42,.02)}
.brand{display:flex;align-items:center;gap:12px;padding:2px 6px}
.brand-text{display:flex;flex-direction:column;line-height:1.45}
.brand-text strong{font-size:14.5px;font-weight:800}
.brand-text span{font-size:11.5px;color:var(--muted)}
.side-nav{display:flex;flex-direction:column;gap:2px;overflow-y:auto;margin-inline:-6px;padding-inline:6px 4px;scrollbar-width:thin;scroll-behavior:smooth;max-height:calc(100vh - 200px)}
.nav-label{font-size:10.5px;font-weight:800;color:var(--faint);letter-spacing:.06em;padding:16px 10px 6px;text-transform:uppercase;border-top:1px solid var(--line);margin-top:4px}
.nav-label:first-child{border-top:0;margin-top:0;padding-top:4px}
.nav-link{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;color:var(--ink-2);font-size:13px;font-weight:500;transition:background .18s ease,color .18s ease,transform .12s ease;border-inline-start:3px solid transparent}
.nav-link svg{width:17px;height:17px;flex:none;color:var(--faint);transition:color .15s ease}
.nav-link:hover{background:var(--surface-soft);color:var(--ink);transform:translateX(-2px)}
.nav-link.active{background:var(--accent);color:#fff;font-weight:700;box-shadow:0 4px 12px -6px rgba(37,99,235,.6);border-inline-start:3px solid #fff;transform:none}
.nav-link.active svg{color:#fff}
.side-foot{margin-top:auto;display:flex;flex-direction:column;gap:10px;padding-top:16px;border-top:1px solid var(--line)}
.side-foot .btn{width:100%}
.mobile-save{display:none}
.side-note{font-size:11.5px;line-height:1.8;color:var(--faint);padding:0 6px}
.main{flex:1;min-width:0;padding:30px clamp(18px,3.6vw,46px) 64px}
.main-inner{max-width:1060px;margin:0 auto}
.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:24px}
.page-eyebrow{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:var(--accent);background:var(--accent-soft);padding:4px 10px;border-radius:999px;margin-bottom:10px}
.page-title{font-size:clamp(21px,2.4vw,27px);font-weight:800;letter-spacing:-.01em;line-height:1.35}
.page-sub{color:var(--muted);font-size:13px;margin-top:5px}
.topbar-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:11px;padding:9px 15px;font-weight:600;font-size:13px;border:1px solid transparent;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease,box-shadow .15s ease;white-space:nowrap}
.btn svg{width:16px;height:16px}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 12px 24px -14px rgba(37,99,235,.95)}
.btn-primary:hover{background:var(--accent-strong)}
.btn-ghost{background:var(--surface);color:var(--ink-2);border-color:var(--line-strong)}
.btn-ghost:hover{background:var(--surface-soft);color:var(--ink)}
.btn-icon{padding:9px 11px}
.logout-form{display:inline-flex}
.alert{display:flex;align-items:center;gap:10px;border-radius:12px;padding:11px 15px;margin-bottom:16px;font-size:13px;font-weight:500}
.alert-ok{background:var(--ok-soft);color:var(--ok);border:1px solid #c7ecd6}
.alert-err{background:var(--danger-soft);color:var(--danger);border:1px solid #f6cccc}
.toast{position:fixed;bottom:22px;inset-inline-start:22px;z-index:80;margin-bottom:0;box-shadow:0 18px 42px -20px rgba(15,23,42,.4);cursor:pointer;animation:toastIn .35s cubic-bezier(.2,.8,.2,1) both}
.toast.alert-ok::before{content:"✓";font-weight:800}
.toast.alert-err::before{content:"!";display:grid;place-items:center;width:18px;height:18px;border-radius:50%;background:currentColor;color:#fff;font-size:11px;font-weight:800;flex:none}
.toast.is-hiding{animation:toastOut .22s ease forwards}
@keyframes toastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
@keyframes toastOut{to{opacity:0;transform:translateY(10px)}}
.scroll-progress{position:fixed;top:0;inset-inline:0;height:3px;z-index:70;background:linear-gradient(90deg,#60a5fa,var(--accent));transform:scaleX(0);transform-origin:right;transition:transform .12s linear;pointer-events:none}
.panel-footer{padding:12px 20px;border-top:1px solid var(--line);color:var(--muted);font-size:12px;text-align:center;background:var(--surface)}
.panel-footer a{color:var(--accent);text-decoration:none;font-weight:600}
.save-state{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;padding:5px 11px;border-radius:999px;border:1px solid #c7ecd6;background:var(--ok-soft);color:var(--ok);white-space:nowrap;transition:background .18s ease,border-color .18s ease,color .18s ease}
.save-state::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.save-state[data-state="dirty"]{background:var(--warn-soft);border-color:#fde68a;color:var(--warn)}
.save-state[data-state="saving"]{background:var(--accent-soft);border-color:#c7d7fb;color:var(--accent-strong)}
/* Live Persian date + clock in the panel topbar */
.topbar-clock{display:inline-flex;align-items:center;gap:9px;margin-bottom:12px;padding:6px 12px;border-radius:999px;background:var(--surface);border:1px solid var(--line);box-shadow:var(--shadow-sm);font-size:12px;font-weight:600;color:var(--ink-2);white-space:nowrap}
.topbar-clock .clock-ico{width:14px;height:14px;color:var(--accent);flex:none}
.topbar-clock .clock-time{color:var(--ink);font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:.03em;min-width:68px;text-align:center}
.topbar-clock .clock-sep{width:1px;height:12px;background:var(--line-strong);flex:none}
/* Profile photo frame picker (circle / square / rounded) */
.shape-picker{display:flex;flex-wrap:wrap;gap:8px}
.shape-option{position:relative;display:inline-flex;align-items:center;gap:9px;padding:9px 13px;border-radius:11px;border:1px solid var(--line-strong);background:var(--surface);cursor:pointer;font-size:12.5px;font-weight:600;color:var(--ink-2);transition:border-color .15s ease,background .15s ease,color .15s ease,box-shadow .15s ease}
.shape-option:hover{border-color:#c3cddd}
.shape-option input{position:absolute;opacity:0;pointer-events:none}
.shape-option:has(input:checked),.shape-option.is-active{border-color:var(--accent);background:var(--accent-soft);color:var(--accent-strong);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.shape-swatch{width:18px;height:18px;flex:none;background:linear-gradient(135deg,#cbd5e1,#94a3b8);border:1px solid rgba(15,23,42,.18)}
.shape-swatch.shape-circle{border-radius:50%}
.shape-swatch.shape-square{border-radius:2px}
.shape-swatch.shape-rounded{border-radius:6px}
/* Theme preset swatches */
.theme-presets{display:flex;flex-wrap:wrap;gap:8px}
.theme-swatch{position:relative;display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;border:1px solid var(--line-strong);background:var(--surface);cursor:pointer;font-size:12.5px;font-weight:600;color:var(--ink-2);transition:border-color .15s ease,background .15s ease,color .15s ease,box-shadow .15s ease}
.theme-swatch:hover{border-color:#c3cddd}
.theme-swatch input{position:absolute;opacity:0;pointer-events:none}
.theme-swatch:has(input:checked),.theme-swatch.is-active{border-color:var(--accent);background:var(--accent-soft);color:var(--accent-strong);box-shadow:0 0 0 3px rgba(37,99,235,.10)}
.swatch-dot{width:16px;height:16px;flex:none;border-radius:50%;border:1px solid rgba(15,23,42,.18)}
.btn:active{transform:translateY(1px)}
.btn.is-loading{pointer-events:none;opacity:.85}
.btn.is-loading::before{content:"";width:13px;height:13px;border-radius:50%;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;animation:spin .7s linear infinite;flex:none}
@keyframes spin{to{transform:rotate(360deg)}}
.grid{display:grid;grid-template-columns:1fr;gap:18px}
.card{scroll-margin-top:18px;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:clip;transition:border-color .2s ease,box-shadow .2s ease}
.card:focus-within{border-color:#c7d7fb;box-shadow:0 0 0 4px rgba(37,99,235,.05)}
.card:target{border-color:#c7d7fb;animation:targetGlow 1s ease}
@keyframes targetGlow{0%{box-shadow:0 0 0 4px rgba(37,99,235,.18)}100%{box-shadow:var(--shadow-sm)}}
.card-header{display:flex;align-items:flex-start;gap:13px;padding:19px 22px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff,#fbfcff)}
.card-icon{width:38px;height:38px;border-radius:11px;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;flex:none}
.card-icon svg{width:19px;height:19px}
.card-header-text{display:flex;flex-direction:column;gap:2px;min-width:0}
.card-title{font-size:15px;font-weight:700}
.card-sub{color:var(--muted);font-size:12.5px}
.card-body{display:grid;grid-template-columns:repeat(12,1fr);gap:15px 17px;padding:21px 22px}
.field{grid-column:1/-1;display:flex;flex-direction:column;gap:7px;min-width:0}
.field.half{grid-column:span 6}
.field.third{grid-column:span 4}
.field-label{font-weight:600;font-size:12.5px;color:var(--ink-2)}
.field-hint{font-size:11.5px;color:var(--faint);line-height:1.7}
.field-warn{display:inline-flex;align-items:center;gap:5px;margin-top:6px;font-size:11px;font-weight:600;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;padding:3px 9px}
.field-warn::before{content:"!";display:inline-grid;place-items:center;width:13px;height:13px;border-radius:50%;background:#f59e0b;color:#fff;font-size:9px;font-weight:800;flex:none}
.field-input.is-invalid,.field-textarea.is-invalid{border-color:#dc2626!important;box-shadow:0 0 0 3px rgba(220,38,38,.12)!important;background:#fef2f2}
.field-error{display:none;font-size:11.5px;font-weight:600;color:#dc2626;margin-top:5px}
.field-error.is-shown{display:block}
.field-input,.field-textarea{width:100%;border-radius:var(--radius-sm);border:1px solid var(--line-strong);background:var(--surface);padding:9px 12px;font-size:13.5px;color:var(--ink);transition:border-color .15s ease,box-shadow .15s ease}
.field-textarea{min-height:112px;resize:vertical;line-height:1.9}
.field-input:hover,.field-textarea:hover{border-color:#c3cddd}
.field-input:focus,.field-textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.field-input[type=file]{padding:7px 10px;background:var(--surface-soft);cursor:pointer;font-size:12.5px}
.field-input[type=color]{width:56px;height:40px;padding:4px;cursor:pointer;border-radius:10px;flex:none}
.field-input[type=file]::file-selector-button{margin-inline-end:10px;padding:6px 12px;border:1px solid var(--line-strong);border-radius:8px;background:#fff;color:var(--ink-2);font-family:inherit;font-size:12px;font-weight:600;cursor:pointer}
.field-input:disabled{background:var(--surface-soft);color:var(--muted);cursor:not-allowed}
select.field-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 12px center;padding-inline-start:34px;cursor:pointer}
.list{display:flex;flex-direction:column;gap:11px;grid-column:1/-1}
.row-card{position:relative;display:flex;flex-direction:column;gap:13px;border:1px solid var(--line);border-radius:var(--radius);padding:18px 18px 15px;background:var(--surface);box-shadow:var(--shadow-sm);transition:border-color .15s ease,box-shadow .15s ease}
.row-card .remove{position:absolute;top:10px;left:10px;border:none;background:var(--surface-soft);color:var(--danger);font-weight:700;font-size:11.5px;cursor:pointer;padding:4px 9px;border-radius:8px;transition:background .15s ease,color .15s ease}
.row-card .remove:hover{background:var(--danger-soft)}
.card-body>.row-grid{grid-column:1/-1}
.row-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:13px}
.row-grid .field{grid-column:span 6}
.row-grid .field.full{grid-column:1/-1}
.row-grid .field.third{grid-column:span 4}
.add-row{grid-column:1/-1;justify-self:start;border-radius:10px;border:1px dashed #b8c9ef;background:transparent;color:var(--accent);font-weight:600;padding:8px 14px;font-size:12.5px;cursor:pointer;transition:background .15s ease}
.add-row:hover{background:var(--accent-soft)}
/* App-aware rows: --brand / --brand-soft / --brand-line are set inline (server
   for existing rows, JS when the picker changes) so the entire block wears the
   selected app's colour and logo. */
.app-row{--brand:#64748b;--brand-soft:rgba(100,116,139,.1);--brand-line:rgba(100,116,139,.32);border-color:var(--brand-line);background:linear-gradient(180deg,var(--brand-soft),var(--surface) 58%);transition:border-color .2s ease,background .2s ease}
.app-row-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding-inline-end:46px}
.app-badge{width:40px;height:40px;border-radius:12px;background:var(--brand);color:#fff;display:grid;place-items:center;flex:none;box-shadow:0 10px 22px -13px var(--brand);transition:background .2s ease,box-shadow .2s ease}
.app-badge svg{width:21px;height:21px}
.app-row[data-app=""] .app-badge{background:var(--surface-2);color:var(--faint);border:1px dashed var(--line-strong);box-shadow:none}
.app-meta{display:flex;flex-direction:column;gap:2px;min-width:0;flex:1}
.app-name{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.45}
.app-hint{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--faint)}
.app-hint code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:10.5px;direction:ltr;color:var(--muted);background:var(--surface);border:1px solid var(--line);border-radius:5px;padding:1px 5px}
.app-hint[hidden]{display:none}
.app-picker{flex:none;display:flex;flex-direction:column;gap:6px;min-width:174px;margin-inline-start:auto}
.app-picker .field-label{font-size:11.5px}
.app-picker select{padding-block:7px;font-size:12.5px;background-color:var(--surface)}
@media (max-width:640px){
  .app-row-head{padding-inline-end:40px}
  .app-picker{width:100%;min-width:0;margin-inline-start:0}
  .section-item{flex-wrap:wrap;gap:10px}
  .section-order{width:100%;justify-content:flex-start;flex-wrap:wrap}
  .section-move{width:32px;height:32px}
  .field-input[type=color]{width:48px;height:36px}
}
.empty-state{grid-column:1/-1;padding:13px 15px;border-radius:12px;background:var(--surface-soft);color:var(--faint);font-size:12.5px;text-align:center}
.empty-state[hidden]{display:none}
.section-grid{grid-column:1/-1;display:flex;flex-direction:column;gap:9px}
.section-item{display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid var(--line);border-radius:12px;padding:11px 15px;background:var(--surface);cursor:grab;transition:border-color .15s ease,box-shadow .15s ease}
.section-item:hover{border-color:#c7d7fb;box-shadow:var(--shadow-sm)}
.section-item.dragging{opacity:.6;background:var(--accent-soft)}
.section-drag-handle{cursor:grab;color:var(--faint);font-size:18px;line-height:1;user-select:none;padding:6px 8px;flex:none;letter-spacing:-4px;transition:color .15s ease,background .15s ease;border-radius:8px;background:var(--surface-soft);border:1px solid var(--line)}
.section-drag-handle:hover{color:var(--accent);background:var(--accent-soft);border-color:var(--accent)}
.section-drag-handle:active{cursor:grabbing}
.section-item-left{display:flex;align-items:center;gap:11px;min-width:0}
.section-toggle{position:relative;width:40px;height:23px;flex:none;cursor:pointer}
.section-toggle input{opacity:0;width:0;height:0}
.section-toggle-track{position:absolute;inset:0;border-radius:999px;background:#cbd5e1;transition:background .18s ease}
.section-toggle-thumb{position:absolute;top:3px;right:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(15,23,42,.2);transition:right .18s ease}
.section-toggle input:checked ~ .section-toggle-track{background:var(--accent)}
.section-toggle input:checked ~ .section-toggle-track .section-toggle-thumb{right:20px}
.section-info{display:flex;flex-direction:column;gap:1px;min-width:0}
.section-name{font-weight:600;font-size:13px}
.section-key{color:var(--faint);font-size:11px;direction:ltr}
.section-meta-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.section-order{display:flex;align-items:center;gap:7px;color:var(--muted);font-size:12px;flex:none}
.section-order input{width:62px;border-radius:8px;border:1px solid var(--line-strong);padding:6px 8px;text-align:center;background:var(--surface);color:var(--ink)}
.section-item.is-custom{flex-wrap:wrap;align-items:flex-start;cursor:grab}
.section-item.is-custom .section-item-left{align-items:flex-start}
.section-item.is-custom .section-key{direction:rtl}
.section-custom-fields{flex:1 0 100%;display:grid;grid-template-columns:1fr;gap:10px;padding-top:11px;margin-top:1px;border-top:1px dashed var(--line);cursor:default}
#sec-experience .row-card,#sec-projects .row-card{background:linear-gradient(180deg,var(--surface),var(--surface-soft));border-color:#dbeafe;box-shadow:0 8px 24px -18px rgba(37,99,235,.35)}
#sec-experience .row-card:hover,#sec-projects .row-card:hover{border-color:#93c5fd;box-shadow:0 12px 28px -18px rgba(37,99,235,.45)}
#sec-experience .row-card .remove,#sec-projects .row-card .remove{background:#eff6ff;color:#1d4ed8}
#sec-experience .row-card .remove:hover,#sec-projects .row-card .remove:hover{background:#dbeafe;color:#1e40af}
#sec-experience .field-label,#sec-projects .field-label{color:#1e3a8a;font-weight:700}
.rich-editor{background:linear-gradient(180deg,#ffffff,#f8fafc)}
.rich-toolbar{display:flex;gap:6px;margin-bottom:10px;padding:6px;border:1px solid var(--line);border-radius:10px;background:var(--surface-soft);width:fit-content}
.rich-btn{min-width:34px;height:32px;border-radius:8px;border:1px solid var(--line-strong);background:var(--surface);color:var(--ink);font-size:13px;font-weight:800;cursor:pointer;transition:background .15s ease,border-color .15s ease,transform .12s ease;display:grid;place-items:center}
.rich-btn:hover{background:var(--accent-soft);border-color:var(--accent)}
.rich-editor{min-height:260px;border:1px solid var(--line-strong);border-radius:12px;padding:14px 16px;background:var(--surface);color:var(--ink);font-size:15px;line-height:1.9;outline:none;direction:auto;box-shadow:inset 0 1px 2px rgba(15,23,42,.04)}
.grid > .card.is-inactive{display:none !important}
.rich-editor:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.exp-period-block{display:flex;flex-direction:column;gap:10px;padding-top:2px}
.exp-period-note{font-size:11px;font-weight:600;color:var(--accent);background:var(--accent-soft);border-radius:999px;padding:2px 8px;margin-inline-start:6px}
.exp-period-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.exp-date-btn{display:inline-flex;align-items:center;gap:9px;min-width:170px;padding:9px 13px;border-radius:11px;border:1px solid var(--line-strong);background:var(--surface);color:var(--ink);font-size:13px;font-weight:600;cursor:pointer;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
.exp-date-btn:hover{border-color:#b9cdf3;background:var(--surface-soft)}
.exp-date-btn.is-empty{color:var(--faint);font-weight:500}
.exp-date-btn.is-empty .exp-date-ico{color:var(--faint)}
.exp-date-btn.is-muted{color:var(--accent);background:var(--accent-soft);border-color:#c7d9fb}
.exp-date-ico{width:16px;height:16px;flex:none;color:var(--accent)}
.exp-date-value{white-space:nowrap}
.exp-period-sep{color:var(--faint);font-weight:700}
.exp-current{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:var(--ink-2);padding:8px 11px;border-radius:10px;background:var(--surface-soft);border:1px solid var(--line);cursor:pointer;user-select:none}
.exp-current input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer}
.exp-row.is-current .exp-current{background:var(--accent-soft);border-color:#c7d9fb;color:var(--accent-strong)}
.exp-calendar{margin-top:2px;width:min(360px,100%);border:1px solid var(--line);border-radius:14px;background:var(--surface);box-shadow:var(--shadow-md);padding:12px;animation:expCalIn .16s ease}
@keyframes expCalIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.exp-cal-top{display:flex;align-items:center;gap:7px;margin-bottom:10px}
.exp-cal-nav{width:30px;height:30px;flex:none;display:grid;place-items:center;border-radius:9px;border:1px solid var(--line);background:var(--surface-soft);color:var(--ink-2);font-size:17px;line-height:1;cursor:pointer;transition:background .15s ease,color .15s ease}
.exp-cal-nav:hover{background:var(--accent-soft);color:var(--accent)}
.exp-cal-selects{display:flex;gap:6px;flex:1;min-width:0}
.exp-cal-selects select{flex:1;min-width:0;border:1px solid var(--line);border-radius:9px;background:var(--surface);color:var(--ink);font-size:12.5px;padding:6px 8px;font-family:inherit}
.exp-cal-selects select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.exp-cal-week{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px}
.exp-cal-week span{text-align:center;font-size:10.5px;font-weight:700;color:var(--faint)}
.exp-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
.exp-cal-day{height:32px;border:none;border-radius:9px;background:transparent;color:var(--ink-2);font-size:12.5px;font-weight:600;cursor:pointer;display:grid;place-items:center;transition:background .12s ease,color .12s ease}
.exp-cal-day:hover{background:var(--accent-soft);color:var(--accent-strong)}
.exp-cal-day.is-empty{visibility:hidden}
.exp-cal-day.is-today{box-shadow:inset 0 0 0 1.5px #bfd3f5;color:var(--accent-strong)}
.exp-cal-day.is-selected{background:var(--accent);color:#fff;box-shadow:0 6px 14px -8px rgba(37,99,235,.9)}
.exp-cal-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:10px;padding-top:9px;border-top:1px solid var(--line)}
.exp-cal-foot button{border:none;background:none;font-family:inherit;font-size:12px;font-weight:700;color:var(--accent);cursor:pointer;padding:4px 8px;border-radius:7px}
.exp-cal-foot button:hover{background:var(--accent-soft)}
.exp-cal-foot button.is-danger{color:var(--danger)}
.exp-cal-foot button.is-danger:hover{background:var(--danger-soft)}
.exp-duration{display:flex;align-items:center;gap:7px;flex-wrap:wrap;min-height:0}
.exp-duration:empty{display:none}
.exp-duration-label{font-size:11.5px;font-weight:700;color:var(--faint)}
.exp-chip{display:inline-flex;align-items:baseline;gap:5px;background:var(--accent-soft);color:var(--accent-strong);border:1px solid #d5e3ff;border-radius:999px;padding:4px 10px;font-size:11.5px;font-weight:600}
.exp-chip b{font-size:13px;font-weight:800}
.exp-duration-warn{font-size:11.5px;font-weight:600;color:var(--danger)}
.exp-legacy{font-size:11.5px;color:var(--faint)}
.section-remove{border:none;background:transparent;color:var(--danger);font-weight:600;font-size:11.5px;cursor:pointer;padding:4px 7px;border-radius:6px;transition:background .15s ease}
.section-move{width:28px;height:28px;flex:none;display:grid;place-items:center;border:1px solid var(--line-strong);background:var(--surface);color:var(--ink-2);font-size:11px;font-weight:800;cursor:pointer;border-radius:8px;transition:background .15s ease,color .15s ease,border-color .15s ease;padding:0}
.section-move:hover{background:var(--accent-soft);color:var(--accent);border-color:var(--accent)}
.section-remove:hover{background:var(--danger-soft)}
.section-add{grid-column:1/-1;display:grid;gap:11px;padding:15px;border:1px dashed #c4d3ee;border-radius:var(--radius);background:var(--surface-soft)}
.section-add-head{display:flex;align-items:center;gap:10px}
.section-add-icon{width:28px;height:28px;border-radius:9px;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;font-weight:700;font-size:15px;flex:none}
.section-add-text{display:flex;flex-direction:column;gap:1px}
.section-add-title{font-size:13px;font-weight:700}
.section-add-sub{font-size:11.5px;color:var(--faint)}
.section-add-fields{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.6fr);gap:10px}
.section-add-fields .field{grid-column:auto}
.section-add-fields .field.full{grid-column:1/-1}
.section-add .btn{justify-self:start}
@media (max-width:640px){.section-add-fields{grid-template-columns:1fr}}
.gallery-preview{margin-top:2px;width:132px;height:84px;border-radius:10px;overflow:hidden;border:1px solid var(--line)}
.gallery-preview img{width:100%;height:100%;object-fit:cover}
.footer-actions{grid-column:1/-1;display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-top:2px}
.country-picker{grid-column:1/-1;display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;padding:15px;border:1px dashed #c4d3ee;border-radius:var(--radius);background:var(--surface-soft)}
.country-picker .field{flex:1 1 240px;grid-column:auto}
.country-list{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:8px}
.country-chip{display:inline-flex;align-items:center;gap:7px;padding:6px 7px 6px 11px;background:var(--surface);border:1px solid var(--line-strong);border-radius:999px;font-size:12.5px;font-weight:600;transition:border-color .15s ease,box-shadow .15s ease}
.country-chip:hover{border-color:#c7d7fb;box-shadow:var(--shadow-sm)}
.country-chip img{width:22px;height:16px;object-fit:cover;border-radius:3px;box-shadow:0 0 0 1px rgba(15,23,42,.06);flex:none}
.chip-code{display:grid;place-items:center;width:26px;height:17px;border-radius:4px;background:var(--surface-2);color:var(--muted);font-size:9px;font-weight:700;letter-spacing:.04em;direction:ltr}
.chip-remove{width:20px;height:20px;border:none;border-radius:50%;background:transparent;color:var(--faint);font-size:15px;line-height:1;display:grid;place-items:center;cursor:pointer;transition:background .15s ease,color .15s ease}
.chip-remove:hover{background:var(--danger-soft);color:var(--danger)}
@keyframes cardIn{from{opacity:0;transform:translateY(9px)}to{opacity:1;transform:none}}
@keyframes chipIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:none}}
@keyframes rowIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
@media(prefers-reduced-motion:no-preference){
  .grid > .card{animation:cardIn .42s cubic-bezier(.2,.8,.2,1) both}
  .country-chip{animation:chipIn .18s ease both}
  .row-card{animation:rowIn .2s ease both}
}
@media(max-width:1040px){
  .app-shell{display:block}
  .sidebar{position:sticky;top:0;z-index:30;height:auto;width:auto;flex-direction:column;gap:10px;padding:12px 16px;border-inline-end:0;border-bottom:1px solid var(--line);background:rgba(255,255,255,.94);backdrop-filter:blur(10px)}
  .brand-text span{display:none}
  .mobile-save{display:inline-flex;margin-inline-start:auto;padding:7px 13px;font-size:12.5px}
  .side-nav{flex-direction:row;overflow-x:auto;gap:4px;padding:0 0 2px;margin:0 -4px;scrollbar-width:none}
  .side-nav::-webkit-scrollbar{display:none}
  .nav-label{display:none}
  .nav-link{white-space:nowrap;padding:7px 11px;font-size:12.5px}
  .nav-link svg{width:15px;height:15px}
  .side-foot{display:none}
  .main{padding:20px 16px 52px}
  .card{scroll-margin-top:128px}
}
/* ---- Settings panel (date format) ---- */
.settings-wrap{grid-column:1/-1;display:flex;flex-direction:column;gap:16px}
.font-samples{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin-top:4px}
.font-sample{display:flex;flex-direction:column;gap:4px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--card);cursor:pointer;text-align:right;transition:transform .15s ease,border-color .15s ease,box-shadow .15s ease}
.font-sample:hover{transform:translateY(-2px);border-color:var(--accent);box-shadow:0 6px 18px -12px rgba(0,0,0,.25)}
.font-sample.is-active{border-color:var(--accent);background:var(--accent-soft,rgba(99,102,241,.08))}
.font-sample-name{font-size:12px;color:var(--text-2);font-weight:600}
.font-sample-text{font-size:16px;line-height:1.6;color:var(--text)}
.settings-row{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;padding:18px 20px;border:1px solid var(--line);border-radius:var(--radius);background:var(--surface-soft)}
.settings-copy{display:flex;flex-direction:column;gap:4px;min-width:220px;flex:1}
.settings-title{font-weight:800;color:var(--ink);font-size:14px}
.settings-sub{font-size:12.5px;line-height:1.8;color:var(--muted)}
.segmented{display:inline-flex;align-items:center;gap:4px;padding:4px;border:1px solid var(--line);border-radius:999px;background:var(--surface);flex:none}
.segmented label{position:relative;display:block;cursor:pointer}
.segmented input{position:absolute;opacity:0;pointer-events:none}
.segmented span{display:flex;align-items:center;justify-content:center;min-width:104px;padding:8px 18px;border-radius:999px;font-size:13px;font-weight:700;color:var(--muted);transition:background .18s ease,color .18s ease,box-shadow .18s ease}
.segmented input:checked + span{background:var(--accent);color:#fff;box-shadow:0 10px 22px -12px rgba(37,99,235,.85)}
.segmented input:focus-visible + span{outline:2px solid rgba(37,99,235,.55);outline-offset:2px}
.settings-preview{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.settings-preview-card{padding:14px 16px;border:1px dashed var(--line-strong);border-radius:var(--radius);background:#fff;display:flex;flex-direction:column;gap:3px}
.settings-preview-card span{font-size:11.5px;font-weight:700;letter-spacing:.02em;color:var(--faint)}
.settings-preview-card strong{font-size:15px;font-weight:800;color:var(--ink)}
.settings-note{display:flex;align-items:flex-start;gap:8px;padding:12px 14px;border-radius:var(--radius-sm);background:var(--accent-soft);color:var(--ink-2);font-size:12.5px;line-height:1.8}
.settings-actions{display:flex;justify-content:flex-end;gap:10px}
.brand-preview{grid-column:1/-1;display:flex;align-items:flex-end;gap:8px;padding:14px 14px 0;border:1px solid var(--line-strong);border-radius:var(--radius);background:linear-gradient(180deg,#eef3fa,#e7edf6)}
.brand-preview-dots{display:inline-flex;gap:4px;padding-bottom:12px}
.brand-preview-dots i{width:7px;height:7px;border-radius:50%;background:#c5d0df}
.brand-preview-tab{display:flex;align-items:center;gap:8px;min-width:0;max-width:min(420px,100%);padding:9px 12px;border:1px solid var(--line-strong);border-bottom:0;border-radius:10px 10px 0 0;background:#fff;box-shadow:0 -2px 10px -8px rgba(15,23,42,.3)}
.brand-preview-favicon{width:22px;height:22px;flex:none;border-radius:6px;display:grid;place-items:center;overflow:hidden;background:linear-gradient(135deg,var(--accent),var(--accent-strong));color:#fff;font-size:8px;font-weight:800;letter-spacing:.03em}
.brand-preview-favicon img{width:100%;height:100%;object-fit:cover}
.brand-preview-title{font-size:12px;font-weight:600;color:var(--ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.brand-preview-x{margin-inline-start:auto;color:var(--faint);font-size:14px;line-height:1}
.logo-preview{grid-column:1/-1;display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px dashed var(--line-strong);border-radius:var(--radius);background:var(--surface-soft)}
.logo-preview img{width:44px;height:44px;object-fit:cover;border-radius:10px;border:1px solid var(--line);background:#fff}
.logo-preview span{font-size:12px;color:var(--muted);line-height:1.8}
.brand-desc{min-height:74px}
.settings-note svg{width:16px;height:16px;flex:none;margin-top:3px;color:var(--accent)}
.inline-link{color:var(--accent);font-weight:700;text-decoration:underline;text-underline-offset:3px}
.section-item.is-disabled .section-info{opacity:.55}
.section-item.is-disabled .section-toggle-track{background:#e2e8f0}
.section-state{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--ok-soft);color:var(--ok);margin-inline-start:2px}
.section-state[data-state="off"]{background:var(--danger-soft);color:var(--danger)}
@media(max-width:640px){
  .settings-row{padding:15px 16px}
  .segmented{width:100%}
  .segmented label{flex:1}
  .segmented span{min-width:0;width:100%}
  .settings-preview{grid-template-columns:1fr}
}
@media(max-width:920px){
  .field.half,.field.third,.row-grid .field,.row-grid .field.third{grid-column:1/-1}
}
@media(max-width:640px){
  .topbar-actions{width:100%}
  .save-state{width:100%;justify-content:center}
  .topbar-actions .btn-primary{flex:1}
  .card-header{padding:15px 16px}
  .card-body{padding:17px 16px}
  .country-picker .btn{width:100%}
  .section-item{flex-wrap:wrap}
  .section-order{margin-inline-start:auto}
}
</style>
</head>
<body>
<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-text">
        <strong>کارت ویزیت دیجیتال</strong>
        <span>پنل مدیریت</span>
      </div>
      <button class="btn btn-primary mobile-save js-save" type="submit" form="cardForm">ذخیره</button>
    </div>

    <nav class="side-nav" aria-label="بخش‌های پنل">
      <span class="nav-label">محتوای کارت</span>
      <a class="nav-link active" href="#sec-main">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        اطلاعات اصلی
      </a>
      <a class="nav-link" href="#sec-stats">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-6"/><path d="M22 20H2"/></svg>
        آمار سریع
      </a>
      <a class="nav-link" href="#sec-about">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
        دربارهٔ من
      </a>
      <a class="nav-link" href="#sec-contacts">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg>
        راه‌های ارتباطی
      </a>
      <a class="nav-link" href="#sec-socials">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/></svg>
        شبکه‌های اجتماعی
      </a>
      <a class="nav-link" href="#sec-specials">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
        لینک‌های ویژه
      </a>
      <a class="nav-link" href="#sec-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
        بنر بالا تصویر
      </a>
      <a class="nav-link" href="#sec-countries">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18Z"/></svg>
        کشورها
      </a>
      <a class="nav-link" href="#sec-gallery">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        تصاویر نمونه کار
      </a>

       <a class="nav-link" href="#sec-experience">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        تجربهٔ کاری
      </a>
      <a class="nav-link" href="#sec-projects">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
        پروژه‌ها
      </a>

      <a class="nav-link" href="#sec-education">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        مدرک تحصیلی
      </a>
      <a class="nav-link" href="#sec-certs">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/></svg>
        مدارک و گواهینامه‌ها
      </a>

      <span class="nav-label">چیدمان و حساب</span>
      <a class="nav-link" href="#sec-resume">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
        رزومه
      </a>
      <a class="nav-link" href="#sec-map">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        آدرس و نقشه
      </a>
      <a class="nav-link" href="#sec-branding">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><circle cx="8" cy="6.5" r=".9" fill="currentColor" stroke="none"/><circle cx="11" cy="6.5" r=".9" fill="currentColor" stroke="none"/></svg>
        هویت و تب مرورگر
      </a>
      <a class="nav-link" href="#sec-font">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19h16"/><path d="M8 5h8l-4 9z"/><path d="M6 14h12"/></svg>
        فونت کارت
      </a>
      <a class="nav-link" href="#sec-settings">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 8.96 4.6H9a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9v.05a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1Z"/></svg>
        تنظیمات نمایش
      </a>
      <a class="nav-link" href="backup.php" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        دانلود بکاپ
      </a>
      <a class="nav-link" href="#sec-sections">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>
        ترتیب بخش‌ها
      </a>
      <a class="nav-link" href="#sec-displayname">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        نام نمایشی
      </a>
      <a class="nav-link" href="#sec-security">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
        امنیت حساب
      </a>
      <a class="nav-link" href="#sec-gate">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
        آدرس ورود
      </a>
    </nav>

    <div class="side-foot">
      <button class="btn btn-primary js-save" type="submit" form="cardForm">ذخیره تغییرات</button>
      <p class="side-note">بعد از ویرایش، دکمهٔ «ذخیره تغییرات» را بزن. تغییرات همان لحظه روی کارت اعمال می‌شود.</p>
    </div>
  </aside>

  <main class="main">
    <div class="main-inner">
      <header class="topbar">
        <div>
          <div class="topbar-clock" aria-label="تاریخ و ساعت">
            <svg class="clock-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span class="clock-time" id="panelClockTime">—</span>
            <span class="clock-sep" aria-hidden="true"></span>
            <span class="clock-date" id="panelClockDate">—</span>
          </div>
          <span class="page-eyebrow">پنل مدیریت</span>
          <h1 class="page-title">کارت ویزیت دیجیتال</h1>
          <p class="page-sub"><?= $displayName !== '' ? 'خوش آمدی، ' . htmlspecialchars($displayName) . '؛ ' : '' ?>هر بخش را از منوی کنار انتخاب کن و همهٔ تغییرات را یک‌جا ذخیره کن. <span class="view-count">👁 <?= number_format($viewCount) ?> بازدید</span></p>
        </div>
        <div class="topbar-actions">
          <span class="save-state" id="saveState" data-state="idle" aria-live="polite">همه تغییرات ذخیره شده</span>
          <a class="btn btn-ghost" href="../index.html" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            پیش‌نمایش کارت
          </a>
          <form class="logout-form" method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <button class="btn btn-ghost" type="submit" title="خروج از حساب">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
              خروج
            </button>
          </form>
          <button class="btn btn-primary js-save" type="submit" form="cardForm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
            ذخیره تغییرات
          </button>
        </div>
      </header>

      <?php if ($msg === 'saved' && $linkWarn === 0): ?><div class="alert alert-ok toast" role="status" title="برای بستن کلیک کن">تغییرات با موفقیت ذخیره شد.</div><?php endif; ?>
      <?php if ($msg === 'saved' && $linkWarn > 0): ?><div class="alert alert-err toast" role="alert" title="برای بستن کلیک کن"><?= (int)$linkWarn ?> لینک نامعتبر بود و ذخیره نشد. آدرس‌ها باید با <span dir="ltr">https://</span> شروع شوند یا دامنهٔ کامل باشند.</div><?php endif; ?>
      <?php if ($msg === 'error'): ?><div class="alert alert-err toast" role="alert" title="برای بستن کلیک کن">خطا در ذخیره‌سازی. لطفاً دوباره تلاش کن.</div><?php endif; ?>
      <?php if ($msg === 'password'): ?><div class="alert alert-ok toast" role="status" title="برای بستن کلیک کن">رمز عبور با موفقیت تغییر کرد.</div><?php endif; ?>

      <form id="cardForm" method="post" action="card.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="saveCard">
        <input type="hidden" name="return_section" id="returnSection" value="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <!-- Managed markers: the row arrays above are absent when the admin
             removes every row, so these tell the save handler that an empty
             list is intentional and must clear the old entries. -->
        <input type="hidden" name="experience_managed" value="1">
        <input type="hidden" name="education_managed" value="1">
        <input type="hidden" name="certs_managed" value="1">
        <input type="hidden" name="projects_managed" value="1">
        <div class="grid">

      <section class="card" id="sec-main">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">اطلاعات اصلی</h2>
            <p class="card-sub">نام، عنوان شغلی، معرفی و عکس پروفایل</p>
          </div>
        </header>
        <div class="card-body">
          <div class="field half"><label class="field-label">نام و نام خانوادگی</label><input class="field-input" name="name" value="<?= htmlspecialchars($card['name'] ?? '') ?>"></div>
          <div class="field half"><label class="field-label">عنوان / سمت</label><input class="field-input" name="role" value="<?= htmlspecialchars($card['role'] ?? '') ?>"></div>
          <div class="field"><label class="field-label">برچسب‌ها (با کاما جدا کن)</label><input class="field-input" name="tags" value="<?= htmlspecialchars(implode(',', is_array($card['tags'] ?? null) ? $card['tags'] : [])) ?>" placeholder="Cisco CCNA, Network+, Windows Server"></div>
           <div class="field"><label class="field-label">درباره من</label><span class="field-hint">متن دربارهٔ من در بخش «دربارهٔ من» ویرایش می‌شود (ویرایشگر پیشرفته).</span></div>
          <div class="field"><label class="field-label">شعار / تگ‌لاین (زیر عنوان شغلی)</label><input class="field-input" name="slogan" value="<?= htmlspecialchars($card['slogan'] ?? '') ?>" placeholder="ساختن شبکه‌هایی که هرگز متوقف نمی‌شوند."></div>
           <div class="field half">
             <label class="field-label">فرمت نمایش تاریخ</label>
             <div class="field-hint">قالب تاریخ‌ها در بخش <a class="inline-link" href="#sec-settings">تنظیمات نمایش</a> انتخاب می‌شود و روی کل کارت اعمال می‌گردد.</div>
           </div>
          <div class="field"><label class="field-label">مسیر عکس پروفایل (URL یا مسیر)</label><input class="field-input" name="photo" value="<?= htmlspecialchars($card['photo'] ?? '') ?>" dir="ltr" placeholder="data/uploads/hero.jpg"></div>
          <div class="field"><label class="field-label">آپلود عکس پروفایل</label><input class="field-input" type="file" name="photo_upload" accept="image/*"></div>
          <div class="field">
            <span class="field-label">شکل تصویر پروفایل</span>
            <?php
              $photoShape = (string)($card['photo_shape'] ?? 'circle');
              if (!in_array($photoShape, ['circle', 'square', 'rounded'], true)) $photoShape = 'circle';
            ?>
            <div class="shape-picker" role="radiogroup" aria-label="شکل تصویر پروفایل">
              <?php foreach (['circle' => 'دایره', 'square' => 'مربع', 'rounded' => 'مربع دورگرد'] as $shapeVal => $shapeLabel): ?>
                <label class="shape-option<?= $photoShape === $shapeVal ? ' is-active' : '' ?>">
                  <input type="radio" name="photo_shape" value="<?= $shapeVal ?>" <?= $photoShape === $shapeVal ? 'checked' : '' ?>>
                  <span class="shape-swatch shape-<?= $shapeVal ?>" aria-hidden="true"></span>
                  <span><?= $shapeLabel ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <span class="field-hint">شکل قاب عکس پروفایل روی کارت ویزیت؛ پیش‌فرض «دایره» است.</span>
          </div>
          <?php if (!empty($card['photo'])): ?><div class="gallery-preview"><img src="<?= htmlspecialchars(admin_preview_url($card['photo'])) ?>" alt=""></div><?php endif; ?>
        </div>
      </section>

      <section class="card" id="sec-stats">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-6"/><path d="M22 20H2"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">آمار سریع</h2>
            <p class="card-sub">اعداد و عنوان‌های نمایش داده شده در بالای کارت؛ برای پنهان کردن این نوار همه را حذف کن.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="stats-container">
            <?php foreach ($card['stats'] ?? [] as $s): ?>
            <div class="row-card">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="row-grid">
                <div class="field"><label class="field-label">عدد</label><input class="field-input" name="stat_num[]" value="<?= htmlspecialchars($s['num'] ?? '') ?>" placeholder="۷+" dir="auto"></div>
                <div class="field"><label class="field-label">عنوان</label><input class="field-input" name="stat_label[]" value="<?= htmlspecialchars($s['label'] ?? '') ?>" placeholder="سال تجربه" dir="auto"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addStat()">+ افزودن آمار</button>
        </div>
      </section>

      <section class="card" id="sec-socials">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">شبکه‌های اجتماعی</h2>
            <p class="card-sub">با انتخاب نوع اپلیکیشن، رنگ برند و لوگوی آن خودکار روی بلوک اعمال می‌شود</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="socials-container">
            <?php foreach ($card['socials'] ?? [] as $s):
              $appKey = app_lookup((string)($s['icon'] ?? ''), (string)($s['label'] ?? ''), $appCatalog, $appAliases);
              $app = $appCatalog[$appKey] ?? null;
              $brand = $app['color'] ?? (trim((string)($s['color'] ?? '')) ?: '#64748b');
            ?>
            <div class="row-card app-row" data-app="<?= htmlspecialchars($appKey) ?>" style="--brand:<?= htmlspecialchars($brand) ?>;--brand-soft:<?= app_hex_rgba($brand, 0.10) ?>;--brand-line:<?= app_hex_rgba($brand, 0.34) ?>">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="app-row-head">
                <span class="app-badge" aria-hidden="true"><?= $app['icon'] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/></svg>' ?></span>
                <div class="app-meta">
                  <span class="app-name"><?= htmlspecialchars($app['label'] ?? 'یک اپلیکیشن انتخاب کنید') ?></span>
                  <span class="app-hint"<?= $app ? '' : ' hidden' ?>>رنگ برند <code><?= htmlspecialchars($brand) ?></code></span>
                </div>
                <div class="app-picker">
                  <label class="field-label">نوع اپلیکیشن</label>
                  <select class="field-input app-select" aria-label="نوع اپلیکیشن"><?= app_picker_options($appCatalog, $appKey) ?></select>
                </div>
              </div>
              <div class="row-grid">
                <div class="field"><label class="field-label">عنوان</label><input class="field-input" name="social_label[]" value="<?= htmlspecialchars($s['label'] ?? '') ?>" placeholder="Instagram"></div>
                <div class="field">
                  <label class="field-label">آدرس لینک</label>
                  <input class="field-input js-url" name="social_url[]" value="<?= htmlspecialchars($s['url'] ?? '') ?>" dir="ltr" placeholder="https://instagram.com/username">
                  <?php if (trim((string)($s['url'] ?? '')) === ''): ?><span class="field-warn">بدون لینک — این شبکه در کارت نمایش داده نمی‌شود</span><?php endif; ?>
                </div>
              </div>
              <input type="hidden" class="js-app-icon" name="social_icon[]" value="<?= htmlspecialchars($appKey !== '' ? $appKey : (string)($s['icon'] ?? '')) ?>">
              <input type="hidden" class="js-app-color" name="social_color[]" value="<?= htmlspecialchars($app['color'] ?? (string)($s['color'] ?? '')) ?>">
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addSocial()">+ افزودن شبکه اجتماعی</button>
        </div>
      </section>

      <section class="card" id="sec-contacts">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">راه‌های ارتباطی</h2>
            <p class="card-sub">واتساپ، تلگرام، تلفن، ایمیل و... — رنگ و لوگو با انتخاب اپلیکیشن خودکار تنظیم می‌شود</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="contacts-container">
            <?php foreach ($card['contacts'] ?? [] as $c):
              $appKey = app_lookup((string)($c['icon'] ?? ''), (string)($c['label'] ?? ''), $appCatalog, $appAliases);
              $app = $appCatalog[$appKey] ?? null;
              $brand = $app['color'] ?? '#64748b';
            ?>
            <div class="row-card app-row" data-app="<?= htmlspecialchars($appKey) ?>" style="--brand:<?= htmlspecialchars($brand) ?>;--brand-soft:<?= app_hex_rgba($brand, 0.10) ?>;--brand-line:<?= app_hex_rgba($brand, 0.34) ?>">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="app-row-head">
                <span class="app-badge" aria-hidden="true"><?= $app['icon'] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/></svg>' ?></span>
                <div class="app-meta">
                  <span class="app-name"><?= htmlspecialchars($app['label'] ?? 'یک اپلیکیشن انتخاب کنید') ?></span>
                  <span class="app-hint"<?= $app ? '' : ' hidden' ?>>رنگ برند <code><?= htmlspecialchars($brand) ?></code></span>
                </div>
                <div class="app-picker">
                  <label class="field-label">نوع اپلیکیشن</label>
                  <select class="field-input app-select" aria-label="نوع اپلیکیشن"><?= app_picker_options($appCatalog, $appKey) ?></select>
                </div>
              </div>
              <div class="row-grid">
                <div class="field"><label class="field-label">عنوان</label><input class="field-input" name="contact_label[]" value="<?= htmlspecialchars($c['label'] ?? '') ?>" placeholder="WhatsApp"></div>
                <div class="field"><label class="field-label">مقدار نمایشی</label><input class="field-input" name="contact_value[]" value="<?= htmlspecialchars($c['value'] ?? '') ?>" placeholder="Message"></div>
                <div class="field full"><label class="field-label">لینک</label><input class="field-input js-url" name="contact_url[]" value="<?= htmlspecialchars($c['url'] ?? '') ?>" dir="ltr" placeholder="https://wa.me/..."></div>
              </div>
              <input type="hidden" class="js-app-icon" name="contact_icon[]" value="<?= htmlspecialchars($appKey !== '' ? $appKey : (string)($c['icon'] ?? '')) ?>">
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addContact()">+ افزودن راه ارتباطی</button>
        </div>
      </section>

      <section class="card" id="sec-specials">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">لینک‌های ویژه</h2>
            <p class="card-sub">در انتهای بخش «راه‌های ارتباطی» کارت نمایش داده می‌شوند.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="specials-container">
            <?php foreach ($card['specialLinks'] ?? [] as $sl): ?>
            <div class="row-card">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="row-grid">
                <div class="field full"><label class="field-label">عنوان</label><input class="field-input" name="special_label[]" value="<?= htmlspecialchars($sl['label'] ?? '') ?>" dir="auto"></div>
                <div class="field full"><label class="field-label">زیرعنوان</label><input class="field-input" name="special_sub[]" value="<?= htmlspecialchars($sl['sub'] ?? '') ?>" dir="auto"></div>
                <div class="field full"><label class="field-label">لینک</label><input class="field-input js-url" name="special_url[]" value="<?= htmlspecialchars($sl['url'] ?? '') ?>" dir="ltr"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addSpecial()">+ افزودن لینک ویژه</button>
        </div>
      </section>

      <section class="card" id="sec-banner">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">بنر بالا تصویر</h2>
            <p class="card-sub">تصویر ۱۶:۹ با لینک و متن</p>
          </div>
        </header>
        <div class="card-body">
          <?php $banner = $card['banner'] ?? []; ?>
          <div class="field"><label class="field-label">مسیر تصویر</label><input class="field-input" name="banner_image" value="<?= htmlspecialchars($banner['image'] ?? '') ?>" dir="ltr" placeholder="data/uploads/banner.jpg"></div>
          <div class="field"><label class="field-label">آپلود تصویر بنر</label><input class="field-input" type="file" name="banner_upload" accept="image/*"></div>
          <?php if (!empty($banner['image'])): ?><div class="gallery-preview"><img src="<?= htmlspecialchars(admin_preview_url($banner['image'])) ?>" alt=""></div><?php endif; ?>
          <div class="field"><label class="field-label">لینک</label><input class="field-input js-url" name="banner_url" value="<?= htmlspecialchars($banner['url'] ?? '') ?>" dir="ltr" placeholder="https://"></div>
          <div class="field half"><label class="field-label">عنوان</label><input class="field-input" name="banner_title" value="<?= htmlspecialchars($banner['title'] ?? '') ?>"></div>
          <div class="field half"><label class="field-label">زیرعنوان</label><input class="field-input" name="banner_sub" value="<?= htmlspecialchars($banner['subtitle'] ?? '') ?>"></div>
        </div>
      </section>

      <section class="card" id="sec-countries">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18Z"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">کشورهایی که کار کردم</h2>
            <p class="card-sub">فقط نام کشور را از لیست انتخاب کن؛ پرچم و کد خودکار کنارش نمایش داده می‌شود</p>
          </div>
        </header>
        <div class="card-body">
          <?php
          $selectedCountries = [];
          foreach ($card['countries'] ?? [] as $c) {
              $code = strtolower(trim((string)($c['code'] ?? '')));
              if ($code === '') continue;
              $selectedCountries[] = [
                  'code' => $code,
                  'name' => $countryOptions[$code] ?? (string)($c['name'] ?? strtoupper($code)),
              ];
          }
          ?>

          <div class="country-picker">
            <div class="field">
              <label class="field-label" for="country-add">افزودن کشور</label>
              <select class="field-input" id="country-add">
                <option value="">انتخاب از لیست…</option>
                <?php foreach ($countryGroups as $groupLabel => $groupCodes): ?>
                <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                  <?php foreach ($groupCodes as $code): ?>
                    <?php if (!isset($countryOptions[$code])) continue; ?>
                  <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($countryOptions[$code]) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="button" class="btn btn-ghost" id="country-add-btn">افزودن به لیست</button>
          </div>

          <div class="country-list" id="country-list">
            <?php foreach ($selectedCountries as $c): ?>
            <span class="country-chip" data-code="<?= htmlspecialchars($c['code']) ?>">
              <?php if (in_array($c['code'], $flagCodes, true)): ?>
              <img src="../image/flags/<?= htmlspecialchars($c['code']) ?>.png" alt="" data-code="<?= htmlspecialchars($c['code']) ?>">
              <?php else: ?>
              <span class="chip-code"><?= htmlspecialchars(strtoupper($c['code'])) ?></span>
              <?php endif; ?>
              <span><?= htmlspecialchars($c['name']) ?></span>
              <button type="button" class="chip-remove" aria-label="حذف <?= htmlspecialchars($c['name']) ?>">×</button>
              <input type="hidden" name="country_code[]" value="<?= htmlspecialchars($c['code']) ?>">
            </span>
            <?php endforeach; ?>
          </div>
          <div class="empty-state" id="country-empty" <?= $selectedCountries === [] ? '' : 'hidden' ?>>هنوز کشوری اضافه نشده است. از لیست بالا انتخاب کن.</div>
        </div>
      </section>

      <section class="card" id="sec-about">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">دربارهٔ من</h2>
            <p class="card-sub">متن معرفی کارت ویزیت؛ می‌توانی بخشی را انتخاب کنی و ضخیم، کج یا زیرخط‌دار کنی</p>
          </div>
        </header>
        <div class="card-body">
          <div class="field full">
            <div class="rich-toolbar" role="toolbar" aria-label="فرمت متن">
              <button type="button" class="rich-btn" onclick="document.execCommand('bold')" title="ضخیم"><b>B</b></button>
              <button type="button" class="rich-btn" onclick="document.execCommand('italic')" title="کج"><i>I</i></button>
              <button type="button" class="rich-btn" onclick="document.execCommand('underline')" title="زیرخط"><u>U</u></button>
            </div>
            <div id="about-editor" class="rich-editor" contenteditable="true" dir="auto"><?= $aboutHtml ?></div>
            <textarea name="about_html" id="about_html" hidden><?= htmlspecialchars($aboutHtml) ?></textarea>
            <p class="field-hint" style="margin-top:8px;">متن را در کادر بالا بنویس؛ برای فرمت، بخش مورد نظر را انتخاب کن و دکمه را بزن.</p>
          </div>
        </div>
      </section>

      <section class="card" id="sec-experience">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">تجربهٔ کاری</h2>
            <p class="card-sub">مدیریت آیتم‌های نمایش داده‌شده در بخش Work Experience</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="experience-container">
            <?php foreach (($cardExperience ?? []) as $e): ?>
            <?php
              $role = is_array($e) ? ($e['role'] ?? ($e['en']['role'] ?? '')) : '';
              $company = is_array($e) ? ($e['company'] ?? ($e['en']['org'] ?? '')) : '';
              $period = is_array($e) ? ($e['period'] ?? ($e['en']['period'] ?? '')) : '';
              $periodJalali = is_array($e) ? ($e['period_jalali'] ?? '') : '';
              $details = is_array($e) ? ($e['details'] ?? '') : '';
              $start = clean_jalali_date($e['start'] ?? null);
              $end = clean_jalali_date($e['end'] ?? null);
              $isCurrent = is_array($e) && !empty($e['current']);
              $jMonthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
              $startVal = $start ? sprintf('%04d-%02d-%02d', $start['jy'], $start['jm'], $start['jd']) : '';
              $endVal = $end ? sprintf('%04d-%02d-%02d', $end['jy'], $end['jm'], $end['jd']) : '';
              $startLabel = $start ? ($start['jd'] . ' ' . $jMonthNames[$start['jm'] - 1] . ' ' . $start['jy']) : '';
              $endLabel = $end ? ($end['jd'] . ' ' . $jMonthNames[$end['jm'] - 1] . ' ' . $end['jy']) : '';
            ?>
            <div class="row-card exp-row<?= $isCurrent ? ' is-current' : '' ?>" data-legacy-period="<?= (!$start && ($period !== '' || $periodJalali !== '')) ? '1' : '0' ?>">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="row-grid">
                <div class="field"><label class="field-label">سمت / عنوان</label><input class="field-input" name="exp_role[]" value="<?= htmlspecialchars((string)$role) ?>" dir="auto"></div>
                <div class="field"><label class="field-label">شرکت / سازمان</label><input class="field-input" name="exp_company[]" value="<?= htmlspecialchars((string)$company) ?>" dir="auto"></div>
              </div>
              <div class="exp-period-block">
                <span class="field-label">دوره کاری <span class="exp-period-note">تقویم شمسی</span></span>
                <div class="exp-period-row">
                  <button type="button" class="exp-date-btn<?= $start ? '' : ' is-empty' ?>" data-kind="start" onclick="openExpCalendar(this)">
                    <svg class="exp-date-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                    <span class="exp-date-value" data-label-start><?= $start ? htmlspecialchars($startLabel) : 'تاریخ شروع' ?></span>
                  </button>
                  <span class="exp-period-sep" aria-hidden="true">—</span>
                  <button type="button" class="exp-date-btn<?= ($end || $isCurrent) ? '' : ' is-empty' ?><?= $isCurrent ? ' is-muted' : '' ?>" data-kind="end" onclick="openExpCalendar(this)">
                    <svg class="exp-date-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                    <span class="exp-date-value" data-label-end><?= $isCurrent ? 'تاکنون' : ($end ? htmlspecialchars($endLabel) : 'تاریخ پایان') ?></span>
                  </button>
                  <label class="exp-current"><input type="checkbox" class="exp-current-cb" onchange="onExpCurrentChange(this)" <?= $isCurrent ? 'checked' : '' ?>> <span>تاکنون</span></label>
                </div>
                <div class="exp-calendar" hidden></div>
                <div class="exp-duration" aria-live="polite"></div>
                <input type="hidden" name="exp_start[]" class="exp-start" value="<?= $startVal ?>">
                <input type="hidden" name="exp_end[]" class="exp-end" value="<?= $endVal ?>">
                <input type="hidden" name="exp_current_val[]" class="exp-current-val" value="<?= $isCurrent ? '1' : '0' ?>">
                <input type="hidden" name="exp_period[]" class="exp-period" value="<?= htmlspecialchars((string)$period) ?>">
                <input type="hidden" name="exp_period_jalali[]" class="exp-period-jalali" value="<?= htmlspecialchars((string)$periodJalali) ?>">
                <div class="field full" style="margin-top:4px;">
                  <label class="field-label">توضیحات / کارهای انجام‌شده / علت خروج (اختیاری)</label>
                  <textarea class="field-textarea" name="exp_details[]" rows="3" dir="auto" placeholder="مثلاً: مسئول راه‌اندازی شبکه، پشتیبانی ۴۰ کاربر، خروج به دلیل ..."><?= htmlspecialchars((string)$details) ?></textarea>
                  <p class="field-hint">این متن وقتی موس یا انگشت روی تجربه می‌رود نمایش داده می‌شود (نه زیر آن).</p>
                </div>
                <?php if ($period !== '' && !$start): ?><p class="field-hint exp-legacy">دورهٔ فعلی: <span dir="ltr"><?= htmlspecialchars((string)$period) ?></span> — برای انتخاب از تقویم، تاریخ شروع را بزن.</p><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addExperience()">+ افزودن تجربه</button>
        </div>
      </section>

      <section class="card" id="sec-projects">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">پروژه‌ها</h2>
            <p class="card-sub">مدیریت آیتم‌های نمایش داده‌شده در بخش Projects</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="projects-container">
            <?php foreach (($cardProjects ?? []) as $pr): ?>
            <?php
              $title = is_array($pr) ? ($pr['title'] ?? ($pr['en']['title'] ?? '')) : '';
              $url = is_array($pr) ? ($pr['url'] ?? '') : '';
            ?>
            <div class="row-card">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="row-grid">
                <div class="field"><label class="field-label">عنوان پروژه</label><input class="field-input" name="proj_title[]" value="<?= htmlspecialchars((string)$title) ?>" dir="auto"></div>
                <div class="field full"><label class="field-label">لینک</label><input class="field-input" name="proj_url[]" value="<?= htmlspecialchars((string)$url) ?>" dir="ltr"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addProject()">+ افزودن پروژه</button>
        </div>
      </section>

       <section class="card" id="sec-education">
         <header class="card-header">
           <div class="card-icon">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
           </div>
           <div class="card-header-text">
             <h2 class="card-title">مدرک تحصیلی</h2>
             <p class="card-sub">مدیریت آیتم‌های بخش Education</p>
           </div>
         </header>
         <div class="card-body">
           <div class="list" id="education-container">
             <?php foreach (($cardEducation ?? []) as $e): ?>
             <?php
               $title = is_array($e) ? ($e['title'] ?? ($e['en']['title'] ?? '')) : '';
                $degreeType = is_array($e) ? (string)($e['degree_type'] ?? '') : '';
                $fieldOfStudy = is_array($e) ? (string)($e['field'] ?? ($e['major'] ?? '')) : '';
                if ($degreeType === '' && $fieldOfStudy === '' && $title !== '') { $degreeType = $title; }
               $org = is_array($e) ? ($e['org'] ?? ($e['company'] ?? ($e['en']['org'] ?? ($e['en']['company'] ?? '')))) : '';
               $meta = is_array($e) ? ($e['meta'] ?? ($e['en']['meta'] ?? '')) : '';
               $date = clean_jalali_date($e['date'] ?? null);
               $dateG = is_array($e) && is_scalar($e['date_g'] ?? null) ? (string)$e['date_g'] : '';
               $dateVal = $date ? sprintf('%04d-%02d-%02d', $date['jy'], $date['jm'], $date['jd']) : '';
               $dateLabel = $date ? format_jalali_date($date) : 'انتخاب تاریخ';
               $metaJalaliVal = is_array($e) ? (is_scalar($e['meta_jalali'] ?? null) ? (string)$e['meta_jalali'] : (is_scalar($e['date_label_jalali'] ?? null) ? (string)$e['date_label_jalali'] : ($date ? format_jalali_date($date) : ''))) : '';
             ?>
             <div class="row-card edu-row">
               <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
               <div class="row-grid">
                  <div class="field"><label class="field-label">نوع مدرک</label><input class="field-input" name="edu_degree[]" value="<?= htmlspecialchars($degreeType) ?>" dir="auto" placeholder="دیپلم، کارشناسی، ارشد، دکتری …"></div>                   <div class="field"><label class="field-label">رشته تحصیلی</label><input class="field-input" name="edu_field[]" value="<?= htmlspecialchars($fieldOfStudy) ?>" dir="auto" placeholder="مثلاً مهندسی نرم‌افزار"></div>                   <div class="field full"><label class="field-label">موسسه / دانشگاه</label><input class="field-input" name="edu_org[]" value="<?= htmlspecialchars((string)$org) ?>" dir="auto"></div>
                  <?php $eduFile = is_array($e) ? (string)($e['file'] ?? '') : ''; ?>
                  <div class="field full">
                    <label class="field-label">فایل مدرک (اختیاری — تصویر یا PDF)</label>
                    <input class="field-input" type="file" name="edu_file[]" accept="image/*,.pdf,application/pdf">
                    <?php if ($eduFile !== ''): ?>
                      <p class="field-hint" style="margin-top:8px">فایل فعلی: <strong dir="ltr"><?= htmlspecialchars(basename($eduFile), ENT_QUOTES, 'UTF-8') ?></strong> — <a href="../<?= htmlspecialchars($eduFile, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">مشاهده / دانلود</a></p>
                      <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;color:var(--ink-2);margin-top:6px"><input type="checkbox" name="edu_file_remove[]" value="1"> حذف فایل فعلی</label>
                    <?php else: ?>
                      <p class="field-hint" style="margin-top:8px">فایلی بارگذاری نشده است.</p>
                    <?php endif; ?>
                  </div>


               </div>
               <div class="exp-period-block">
                 <span class="field-label">تاریخ دریافت (تقویم شمسی)</span>
                 <div class="exp-period-row">
                   <button type="button" class="exp-date-btn<?= $date ? '' : ' is-empty' ?>" onclick="openSingleDateCalendar(this)">
                     <svg class="exp-date-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                     <span class="exp-date-value single-date-label"><?= $date ? htmlspecialchars($dateLabel) : 'انتخاب تاریخ' ?></span>
                   </button>
                 </div>
                 <div class="exp-calendar" hidden></div>
                 <input type="hidden" name="edu_date[]" class="single-date" value="<?= $dateVal ?>">
                 <input type="hidden" name="edu_date_g[]" class="single-date-g" value="<?= htmlspecialchars((string)$dateG) ?>">
                 <input type="hidden" name="edu_meta[]" class="single-meta" value="<?= htmlspecialchars((string)$meta) ?>">
                  <input type="hidden" name="edu_meta_jalali[]" class="single-meta-jalali" value="<?= htmlspecialchars((string)$metaJalaliVal) ?>">
                 <?php if ($meta !== '' && !$date): ?><p class="field-hint exp-legacy">دورهٔ فعلی: <span dir="ltr"><?= htmlspecialchars((string)$meta) ?></span></p><?php endif; ?>
               </div>
             </div>
             <?php endforeach; ?>
           </div>
           <button type="button" class="add-row" onclick="addEducation()">+ افزودن مدرک</button>
         </div>
       </section>

       <section class="card" id="sec-certs">
         <header class="card-header">
           <div class="card-icon">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/></svg>
           </div>
           <div class="card-header-text">
             <h2 class="card-title">مدارک و گواهینامه‌ها</h2>
             <p class="card-sub">مدیریت آیتم‌های بخش Certifications</p>
           </div>
         </header>
         <div class="card-body">
           <div class="list" id="certs-container">
             <?php foreach (($cardCerts ?? []) as $e): ?>
             <?php
               $title = is_array($e) ? ($e['title'] ?? ($e['en']['title'] ?? '')) : '';
               $org = is_array($e) ? ($e['org'] ?? ($e['company'] ?? ($e['en']['org'] ?? ($e['en']['company'] ?? '')))) : '';
               $meta = is_array($e) ? ($e['meta'] ?? ($e['en']['meta'] ?? '')) : '';
               $date = clean_jalali_date($e['date'] ?? null);
               $dateG = is_array($e) && is_scalar($e['date_g'] ?? null) ? (string)$e['date_g'] : '';
               $dateVal = $date ? sprintf('%04d-%02d-%02d', $date['jy'], $date['jm'], $date['jd']) : '';
               $dateLabel = $date ? format_jalali_date($date) : 'انتخاب تاریخ';
               $metaJalaliVal = is_array($e) ? (is_scalar($e['meta_jalali'] ?? null) ? (string)$e['meta_jalali'] : (is_scalar($e['date_label_jalali'] ?? null) ? (string)$e['date_label_jalali'] : ($date ? format_jalali_date($date) : ''))) : '';
             ?>
             <div class="row-card cert-row">
               <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
               <div class="row-grid">
                 <div class="field"><label class="field-label">عنوان / مدرک</label><input class="field-input" name="cert_title[]" value="<?= htmlspecialchars((string)$title) ?>" dir="auto"></div>
                 <div class="field"><label class="field-label">موسسه / صادرکننده</label><input class="field-input" name="cert_org[]" value="<?= htmlspecialchars((string)$org) ?>" dir="auto"></div>
               </div>
               <?php $certFile = is_array($e) ? (string)($e['file'] ?? '') : ''; ?>
               <div class="field full">
                 <label class="field-label">فایل گواهینامه (اختیاری — تصویر یا PDF)</label>
                 <input class="field-input" type="file" name="cert_file[]" accept="image/*,.pdf,application/pdf">
                 <?php if ($certFile !== ''): ?>
                   <p class="field-hint" style="margin-top:8px">فایل فعلی: <strong dir="ltr"><?= htmlspecialchars(basename($certFile), ENT_QUOTES, 'UTF-8') ?></strong> — <a href="../<?= htmlspecialchars($certFile, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">مشاهده / دانلود</a></p>
                   <label style="display:inline-flex;align-items:center;gap:8px;font-size:12.5px;color:var(--ink-2);margin-top:6px"><input type="checkbox" name="cert_file_remove[]" value="1"> حذف فایل فعلی</label>
                 <?php else: ?>
                   <p class="field-hint" style="margin-top:8px">فایلی بارگذاری نشده است.</p>
                 <?php endif; ?>
               </div>
               <div class="exp-period-block">
                 <span class="field-label">تاریخ دریافت (تقویم شمسی)</span>
                 <div class="exp-period-row">
                   <button type="button" class="exp-date-btn<?= $date ? '' : ' is-empty' ?>" onclick="openSingleDateCalendar(this)">
                     <svg class="exp-date-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                     <span class="exp-date-value single-date-label"><?= $date ? htmlspecialchars($dateLabel) : 'انتخاب تاریخ' ?></span>
                   </button>
                 </div>
                 <div class="exp-calendar" hidden></div>
                 <input type="hidden" name="cert_date[]" class="single-date" value="<?= $dateVal ?>">
                 <input type="hidden" name="cert_date_g[]" class="single-date-g" value="<?= htmlspecialchars((string)$dateG) ?>">
                 <input type="hidden" name="cert_meta[]" class="single-meta" value="<?= htmlspecialchars((string)$meta) ?>">
                  <input type="hidden" name="cert_meta_jalali[]" class="single-meta-jalali" value="<?= htmlspecialchars((string)$metaJalaliVal) ?>">
                 <?php if ($meta !== '' && !$date): ?><p class="field-hint exp-legacy">دورهٔ فعلی: <span dir="ltr"><?= htmlspecialchars((string)$meta) ?></span></p><?php endif; ?>
               </div>
             </div>
             <?php endforeach; ?>
           </div>
           <button type="button" class="add-row" onclick="addCert()">+ افزودن گواهینامه</button>
         </div>
       </section>
      <section class="card" id="sec-branding">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><circle cx="6.5" cy="6.5" r=".9" fill="currentColor" stroke="none"/><circle cx="9.5" cy="6.5" r=".9" fill="currentColor" stroke="none"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">هویت و تب مرورگر</h2>
            <p class="card-sub">نام و لوگویی که در تب مرورگر، آیکن سایت و پیش‌نمایش شبکه‌های اجتماعی دیده می‌شود</p>
          </div>
        </header>
        <div class="card-body">
          <div class="settings-wrap">
            <div class="field">
              <label class="field-label" for="brandTitleInput">عنوان تب مرورگر</label>
              <input class="field-input" id="brandTitleInput" name="site_title" maxlength="80" dir="auto" value="<?= htmlspecialchars((string)($card['site_title'] ?? '')) ?>" placeholder="<?= htmlspecialchars($brandFallbackTitle) ?>">
              <span class="field-hint">اگر خالی بماند، «<?= htmlspecialchars($brandFallbackTitle) ?>» نمایش داده می‌شود.</span>
            </div>

            <div class="field">
              <label class="field-label" for="brandDescriptionInput">توضیح کوتاه (meta description)</label>
              <textarea class="field-textarea brand-desc" id="brandDescriptionInput" name="site_description" maxlength="200" dir="auto" placeholder="<?= htmlspecialchars($brandFallbackDescription) ?>"><?= htmlspecialchars((string)($card['site_description'] ?? '')) ?></textarea>
              <span class="field-hint">در نتایج جست‌وجو و پیش‌نمایش لینک استفاده می‌شود؛ خالی بماند از نام و عنوان شغلی («<?= htmlspecialchars($brandFallbackDescription) ?>») ساخته می‌شود.</span>
            </div>

            <div class="field">
              <label class="field-label" for="brandLogoInput">مسیر لوگو</label>
              <input class="field-input" id="brandLogoInput" name="site_logo" dir="ltr" value="<?= htmlspecialchars((string)($card['site_logo'] ?? '')) ?>" placeholder="data/uploads/site-logo.png">
              <span class="field-hint">فقط مسیرهای داخل <span dir="ltr">image/</span> یا <span dir="ltr">data/uploads/</span> پذیرفته می‌شوند. برای بهترین نتیجه یک تصویر مربعی PNG آپلود کن.</span>
            </div>

            <div class="field">
              <label class="field-label" for="brandLogoUpload">آپلود لوگوی جدید</label>
              <input class="field-input" id="brandLogoUpload" type="file" name="site_logo_upload" accept="image/png,image/jpeg,image/webp,image/gif,image/avif">
              <label class="exp-current"><input type="checkbox" name="site_logo_clear" value="1"> <span>حذف لوگو و بازگشت به لوگوی پیش‌فرض</span></label>
            </div>

            <div class="brand-preview" aria-live="polite">
              <span class="brand-preview-dots" aria-hidden="true"><i></i><i></i><i></i></span>
              <span class="brand-preview-tab">
                <span class="brand-preview-favicon" id="brandPreviewIcon"><?php if ($panelLogo !== ''): ?><img src="<?= htmlspecialchars(admin_preview_url($panelLogo)) ?>" alt=""><?php else: ?>DB<?php endif; ?></span>
                <span class="brand-preview-title" id="brandPreviewTitle" dir="auto"><?= htmlspecialchars($brandMeta['title']) ?></span>
                <span class="brand-preview-x" aria-hidden="true">×</span>
              </span>
            </div>

            <div class="settings-actions">
              <button type="submit" class="btn btn-primary">ذخیره هویت</button>
            </div>
          </div>
        </div>
      </section>
      <section class="card" id="sec-font">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16"/><path d="M6 16l6-12 6 12"/><path d="M8 12h8"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">فونت کارت</h2>
            <p class="card-sub">فونت متن کارت ویزیت دیجیتال را انتخاب کن؛ تغییر بلافاصله در پیش‌نمایش اعمال می‌شود.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="settings-wrap">
            <div class="settings-preview">
              <div class="brand-preview" id="fontPreview" dir="auto" style="font-family: system-ui, sans-serif;">
                <span class="brand-preview-title" id="fontPreviewTitle" dir="auto">نام شما — Digital Business Card</span>
              </div>
            </div>
            <div class="field">
              <label class="field-label">فونت</label>
              <select class="field-input" name="font_family" id="fontFamilySelect" dir="auto">
                <option value="">سیستم پیش‌فرض</option>
                <?php
                  $fontList = [];
                  $fontCatalogPath = __DIR__ . '/../fonts/fonts.json';
                  if (is_file($fontCatalogPath)) {
                    $decoded = json_decode((string)file_get_contents($fontCatalogPath), true);
                    if (is_array($decoded)) $fontList = $decoded;
                  }
                  $currentFont = (string)($card['font_family'] ?? '');
                  if ($currentFont === '') $currentFont = '';
                  foreach ($fontList as $f) {
                    if (!is_array($f) || !isset($f['name'])) continue;
                    $name = (string)$f['name'];
                    echo '<option value="' . htmlspecialchars($name) . '"' . ($name === $currentFont ? ' selected' : '') . '>' . htmlspecialchars($name) . '</option>';
                  }
                ?>
              </select>
              <span class="field-hint">فونت سیستم مرورگر استفاده می‌شود. برای افزودن فونت، مجوز بازتوزیع آن را بررسی کنید.</span>
            </div>
            <div class="field">
              <span class="field-label">تم رنگی پیش‌فرض</span>
              <?php
                $themePresets = [
                    'default'   => ['#f8fafc', 'پیش‌فرض'],
                    'blue'      => ['#eff6ff', 'آبی'],
                    'warm'      => ['#fef3e2', 'گرم'],
                    'mint'      => ['#ecfdf5', 'نعنایی'],
                    'lavender'  => ['#f5f3ff', 'اسطوخودوسی'],
                    'gray'      => ['#f1f5f9', 'خاکستری'],
                ];
                $curBg = (string)($card['bg_color'] ?? '');
                if ($curBg === '') $curBg = '#f8fafc';
              ?>
              <div class="theme-presets" role="radiogroup" aria-label="تم رنگی پیش‌فرض">
                <?php foreach ($themePresets as $key => $pc): ?>
                  <label class="theme-swatch<?= strtolower($curBg) === strtolower($pc[0]) ? ' is-active' : '' ?>">
                    <input type="radio" name="theme_preset" value="<?= htmlspecialchars($key) ?>" data-color="<?= htmlspecialchars($pc[0]) ?>" <?= strtolower($curBg) === strtolower($pc[0]) ? 'checked' : '' ?>>
                    <span class="swatch-dot" style="background:<?= htmlspecialchars($pc[0]) ?>"></span>
                    <span><?= htmlspecialchars($pc[1]) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <span class="field-hint">یک تم را انتخاب کنید یا رنگ دلخواه را از انتخاب‌گر پایین انتخاب کنید.</span>
            </div>
            <div class="field">
              <label class="field-label" for="bg_color">رنگ پس‌زمینهٔ صفحه (سفارشی)</label>
              <div style="display:flex;align-items:center;gap:10px">
                <input class="field-input" type="color" id="bg_color" name="bg_color" value="<?= htmlspecialchars(($card['bg_color'] ?? '') !== '' ? $card['bg_color'] : '#f8fafc') ?>" dir="ltr">
                <span id="bg_color_hex" style="font-family:monospace;font-size:13px;color:var(--ink-2);min-width:72px" dir="ltr"><?= htmlspecialchars(($card['bg_color'] ?? '') !== '' ? $card['bg_color'] : '#f8fafc') ?></span>
              </div>
              <span class="field-hint">رنگ پشت کارت ویزیت در صفحهٔ مرورگر.</span>
            </div>
            <div class="font-samples" id="fontSamples">
              <?php foreach ($fontList as $f):
                if (!is_array($f) || !isset($f['name'])) continue;
                $fname = (string)$f['name'];
              ?>
              <button type="button" class="font-sample" data-font="<?= htmlspecialchars($fname) ?>" style="font-family: <?= htmlspecialchars($fname) ?>, sans-serif;">
                <span class="font-sample-name"><?= htmlspecialchars($fname) ?></span>
                <span class="font-sample-text">نمونه متن فارسی — ۰۱۲۳۴۵۶۷۸۹</span>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>

      <section class="card" id="sec-settings">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 8.96 4.6H9a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9v.05a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1Z"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">تنظیمات نمایش</h2>
            <p class="card-sub">قالب تاریخ‌ها را یک‌جا عوض کن؛ همهٔ تاریخ‌های کارت ویزیت با هم تغییر می‌کنند.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="settings-wrap">
            <div class="settings-row">
              <div class="settings-copy">
                <span class="settings-title">قالب نمایش تاریخ</span>
                <span class="settings-sub">تاریخ تجربه‌های کاری، مدرک تحصیلی و گواهینامه‌ها با این قالب روی کارت نمایش داده می‌شوند.</span>
              </div>
              <div class="segmented" role="radiogroup" aria-label="قالب نمایش تاریخ">
                <label>
                  <input type="radio" name="date_format" value="jalali" <?= (($card['date_format'] ?? 'gregorian') === 'jalali') ? 'checked' : '' ?>>
                  <span>شمسی</span>
                </label>
                <label>
                  <input type="radio" name="date_format" value="gregorian" <?= (($card['date_format'] ?? 'gregorian') !== 'jalali') ? 'checked' : '' ?>>
                  <span>میلادی</span>
                </label>
              </div>
            </div>

            <div class="settings-preview" aria-live="polite">
              <div class="settings-preview-card">
                <span>نمونه تاریخ شمسی</span>
                <strong id="settings-today-jalali" dir="auto">—</strong>
              </div>
              <div class="settings-preview-card">
                <span>نمونه تاریخ میلادی</span>
                <strong id="settings-today-gregorian" dir="auto">—</strong>
              </div>
            </div>

            <div class="settings-note">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/></svg>
              <span>تغییر این تنظیم فقط نحوهٔ نمایش را عوض می‌کند؛ تاریخ‌های ذخیره‌شده حذف نمی‌شوند و هر زمان می‌توانی به قالب قبلی برگردی.</span>
            </div>

            <div class="settings-row">
              <div class="settings-copy">
                <span class="settings-title">سوئیچ زبان</span>
                <span class="settings-sub">نمایش/پنهان‌کردن دکمهٔ تغییر زبان روی کارت و تعیین زبان پیش‌فرض.</span>
              </div>
              <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px">
                  <input type="checkbox" name="lang_switcher_visible" value="1" <?= (($card['lang_switcher_visible'] ?? '1') !== '0') ? 'checked' : '' ?>>
                  نمایش سوئیچ زبان
                </label>
                <select class="field-input" name="default_language" dir="auto" style="min-width:160px">
                  <?php foreach (['fa'=>'فارسی','en'=>'English'] as $code => $label): ?>
                    <option value="<?= htmlspecialchars($code) ?>" <?= (($card['default_language'] ?? '') === $code) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="settings-actions">
              <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
            </div>
          </div>
        </div>
      </section>

      <section class="card" id="sec-sections">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">ترتیب و نمایش بخش‌ها</h2>
            <p class="card-sub">همهٔ بخش‌های کارت — از جمله تحصیلات، گواهینامه‌ها و بخش‌های دلخواه — در این لیست می‌آیند. با سوییچ هر بخش را نمایش/پنهان کن و با شماره یا آیکن ⋮⋮ ترتیبش را عوض کن.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="section-grid" id="sections-container">
            <?php
            $defaultSections = [
                ['key' => 'countries', 'label' => "Countries I've Worked In"],
                ['key' => 'about', 'label' => 'About Me'],
                ['key' => 'contact', 'label' => 'Contact'],
                ['key' => 'socials', 'label' => 'Social Media'],
                ['key' => 'experience', 'label' => 'Work Experience'],
                ['key' => 'skills', 'label' => 'Skills'],
                ['key' => 'education', 'label' => 'Education'],
                 ['key' => 'certs', 'label' => 'Certifications'],
                ['key' => 'gallery', 'label' => 'Gallery'],
                ['key' => 'portfolio', 'label' => 'Projects'],
                ['key' => 'resume', 'label' => 'Resume'],
                ['key' => 'map', 'label' => 'Location & Map'],
            ];
            $secs = is_array($card['sections'] ?? null) ? array_values(array_filter($card['sections'], 'is_array')) : [];
            if (empty($secs)) {
                foreach ($defaultSections as $i => $s) {
                    $secs[] = ['key' => $s['key'], 'label' => $s['label'], 'enabled' => true, 'order' => $i + 1];
                }
            }
            usort($secs, function($a,$b){ return (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0); });
            foreach ($secs as $idx => $sec):
              $secKey = (string)($sec['key'] ?? '');
              $secLabel = (string)($sec['label'] ?? $secKey);
              $isCustom = !in_array($secKey, $knownSectionKeys, true);
            ?>
            <div class="section-item<?= $isCustom ? ' is-custom' : '' ?><?= empty($sec['enabled']) ? ' is-disabled' : '' ?>">
              <div class="section-item-left">
                <span class="section-drag-handle" draggable="true" title="کشیدن برای تغییر ترتیب" aria-label="تغییر ترتیب">⋮⋮</span>
                <label class="section-toggle" draggable="false">
                  <input type="checkbox" name="section_enabled[<?= $idx ?>]" value="1" <?= !empty($sec['enabled']) ? 'checked' : '' ?> onchange="setSectionEnabled(this)" draggable="false" aria-label="نمایش بخش در کارت ویزیت" title="روشن/خاموش کردن نمایش این بخش">
                  <input type="hidden" name="section_enabled_hidden[<?= $idx ?>]" value="<?= !empty($sec['enabled']) ? '1' : '0' ?>">
                  <span class="section-toggle-track"><span class="section-toggle-thumb"></span></span>
                </label>
                <div class="section-info">
                  <span class="section-name"><?= htmlspecialchars($secLabel) ?></span>
                  <span class="section-meta-row">
                    <span class="section-key"><?= $isCustom ? 'بخش دلخواه' : htmlspecialchars($secKey) ?></span>
                    <span class="section-state" data-state="<?= !empty($sec['enabled']) ? 'on' : 'off' ?>"><?= !empty($sec['enabled']) ? 'نمایش' : 'پنهان' ?></span>
                  </span>
                </div>
              </div>
              <div class="section-order">
                <button type="button" class="section-move" onclick="moveSection(this,-1)" aria-label="بالا" title="بالا">▲</button>
                <button type="button" class="section-move" onclick="moveSection(this,1)" aria-label="پایین" title="پایین">▼</button>
                <span>ترتیب</span>
                <input class="js-section-order" type="number" draggable="false" name="section_order[<?= $idx ?>]" value="<?= intval($sec['order'] ?? ($idx+1)) ?>" min="1">
                <?php if ($isCustom): ?>
                <button type="button" class="section-remove" onclick="removeSection(this)">حذف</button>
                <?php endif; ?>
              </div>
              <?php if ($isCustom): ?>
              <div class="section-custom-fields">
                <div class="field">
                  <label class="field-label">عنوان بخش</label>
                  <input class="field-input js-section-title" type="text" draggable="false" name="section_label[<?= $idx ?>]" value="<?= htmlspecialchars($secLabel) ?>" maxlength="80" placeholder="مثلاً خدمات من" oninput="syncSectionTitle(this)" dir="auto">
                </div>
                <div class="field">
                  <label class="field-label">متن بخش</label>
                  <textarea class="field-input field-textarea" name="section_content[<?= $idx ?>]" rows="3" maxlength="2000" placeholder="متنی که زیر عنوان این بخش روی کارت ویزیت نمایش داده می‌شود" dir="auto"><?= htmlspecialchars((string)($sec['content'] ?? '')) ?></textarea>
                </div>
              </div>
              <?php else: ?>
              <input type="hidden" name="section_label[<?= $idx ?>]" value="<?= htmlspecialchars($secLabel) ?>">
              <input type="hidden" name="section_content[<?= $idx ?>]" value="">
              <?php endif; ?>
              <input type="hidden" name="section_key[<?= $idx ?>]" value="<?= htmlspecialchars($secKey) ?>">
            </div>
            <?php endforeach; ?>
          </div>

          <div class="section-add">
            <div class="section-add-head">
              <span class="section-add-icon" aria-hidden="true">+</span>
              <div class="section-add-text">
                <span class="section-add-title">افزودن بخش دلخواه</span>
                <span class="section-add-sub">عنوان و متن خودت را بنویس تا به کارت ویزیت اضافه شود</span>
              </div>
            </div>
            <div class="section-add-fields">
              <div class="field">
                <label class="field-label" for="new-section-title">عنوان بخش</label>
                <input class="field-input" type="text" id="new-section-title" maxlength="80" placeholder="مثلاً خدمات من" onkeydown="if(event.key==='Enter'){event.preventDefault();addSection();}" dir="auto">
              </div>
              <div class="field">
                <label class="field-label" for="new-section-text">متن بخش (اختیاری)</label>
                <textarea class="field-input field-textarea" id="new-section-text" rows="2" maxlength="2000" placeholder="توضیح کوتاه دربارهٔ این بخش..." dir="auto"></textarea>
              </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="addSection()">+ افزودن بخش</button>
          </div>
        </div>
      </section>

      <section class="card" id="sec-gallery">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">تصاویر نمونه کار</h2>
            <p class="card-sub">با نسبت ۱۹٫۶ / ۹ نمایش داده میشه. میشه آپلود یا لینک بزنی</p>
          </div>
        </header>
        <div class="card-body">
          <div class="list" id="gallery-container">
            <?php foreach ($card['gallery'] ?? [] as $g): ?>
            <div class="row-card">
              <button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>
              <div class="row-grid">
                <div class="field full"><label class="field-label">آدرس عکس (URL)</label><input class="field-input" name="gallery_image[]" value="<?= htmlspecialchars($g['image'] ?? '') ?>" dir="ltr" placeholder="data/uploads/photo.jpg یا https://"></div>
                <div class="field full"><label class="field-label">آپلود فایل</label><input class="field-input" type="file" name="gallery_upload[]" accept="image/*"></div>
                <div class="field"><label class="field-label">عنوان</label><input class="field-input" name="gallery_title[]" value="<?= htmlspecialchars($g['title'] ?? '') ?>" dir="auto"></div>
                <div class="field"><label class="field-label">لینک (اختیاری)</label><input class="field-input js-url" name="gallery_url[]" value="<?= htmlspecialchars($g['url'] ?? '') ?>" dir="ltr" placeholder="https://"></div>
              </div>
              <?php if (!empty($g['image'])): ?>
              <div class="gallery-preview"><img src="<?= htmlspecialchars(admin_preview_url($g['image'])) ?>" alt="" loading="lazy" decoding="async"></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="add-row" onclick="addGallery()">+ افزودن عکس</button>
        </div>
      </section>

      <?php $mapData = (isset($card['map']) && is_array($card['map'])) ? $card['map'] : []; ?>
      <section class="card" id="sec-map">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">آدرس و نقشه</h2>
            <p class="card-sub">آدرس کسب‌وکار/شغل و لینک‌های نقشه (بلد، نشان، گوگل‌مپ). در کارت ویزیت به‌صورت لینک نمایش داده می‌شود.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="row-grid">
            <div class="field">
              <label class="field-label" for="map_country">کشور</label>
              <input class="field-input" id="map_country" name="map_country" value="<?= htmlspecialchars((string)($mapData['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="auto" placeholder="مثلاً ایران">
            </div>
            <div class="field">
              <label class="field-label" for="map_province">استان</label>
              <input class="field-input" id="map_province" name="map_province" value="<?= htmlspecialchars((string)($mapData['province'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="auto" placeholder="مثلاً تهران">
            </div>
            <div class="field">
              <label class="field-label" for="map_city">شهر</label>
              <input class="field-input" id="map_city" name="map_city" value="<?= htmlspecialchars((string)($mapData['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="auto" placeholder="مثلاً تهران">
            </div>
            <div class="field">
              <label class="field-label" for="map_district">بخش</label>
              <input class="field-input" id="map_district" name="map_district" value="<?= htmlspecialchars((string)($mapData['district'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="auto" placeholder="مثلاً مرکزی">
            </div>
            <div class="field full">
              <label class="field-label" for="map_address">ادامهٔ آدرس</label>
              <textarea class="field-textarea" id="map_address" name="map_address" rows="2" dir="auto" placeholder="مثلاً خیابان ولیعصر، پلاک ۱۲، واحد ۳"><?= htmlspecialchars((string)($mapData['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="field full">
              <label class="field-label" for="map_balad">لینک نقشه — بلد (Balad)</label>
              <input class="field-input js-url" id="map_balad" name="map_balad" value="<?= htmlspecialchars((string)($mapData['balad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="ltr" placeholder="https://balad.ir/...">
            </div>
            <div class="field full">
              <label class="field-label" for="map_neshan">لینک نقشه — نشان (Neshan)</label>
              <input class="field-input js-url" id="map_neshan" name="map_neshan" value="<?= htmlspecialchars((string)($mapData['neshan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="ltr" placeholder="https://neshan.org/...">
            </div>
            <div class="field full">
              <label class="field-label" for="map_google">لینک نقشه — گوگل‌مپ (Google Maps)</label>
              <input class="field-input js-url" id="map_google" name="map_google" value="<?= htmlspecialchars((string)($mapData['google'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="ltr" placeholder="https://maps.google.com/...">
            </div>
          </div>
        </div>
      </section>

      <?php $resumeData = (isset($card['resume']) && is_array($card['resume'])) ? $card['resume'] : []; ?>
      <section class="card" id="sec-resume">
        <header class="card-header">
          <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
          </div>
          <div class="card-header-text">
            <h2 class="card-title">رزومه (دانلود برای مخاطب)</h2>
            <p class="card-sub">فایل PDF یا Word بارگذاری کن تا بازدیدکننده بتواند آن را دانلود کند. حداکثر ۱۰ مگابایت.</p>
          </div>
        </header>
        <div class="card-body">
          <div class="row-grid">
            <div class="field full">
              <label class="field-label" for="resume_title">عنوان نمایشی (اختیاری)</label>
              <input class="field-input" id="resume_title" name="resume_title" value="<?= htmlspecialchars((string)($resumeData['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" dir="auto" placeholder="مثلاً رزومهٔ شما">
            </div>
            <div class="field full">
              <label class="field-label" for="resume_file">فایل رزومه (PDF, DOC, DOCX)</label>
              <input class="field-input" id="resume_file" type="file" name="resume_file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
              <div class="field-hint" style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--surface-2,#f8f8f7)">
                <?php if (!empty($resumeData['file'])): ?>
                  <span>فایل فعلی: <strong dir="ltr"><?= htmlspecialchars((string)($resumeData['original_name'] ?? $resumeData['file']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                  <a href="../resume.php" target="_blank" rel="noopener" class="inline-link">دانلود / بررسی</a>
                <?php else: ?>
                  <span>هنوز فایلی بارگذاری نشده است.</span>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($resumeData['file'])): ?>
            <div class="field full" style="margin-top:-2px">
              <label style="display:inline-flex;align-items:center;gap:10px;font-size:13px;color:var(--ink-2);cursor:pointer;padding:8px 10px;border:1px dashed var(--line);border-radius:10px;background:#fff">
                <input type="checkbox" name="resume_remove" value="1"> حذف فایل رزومهٔ فعلی
              </label>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <div class="footer-actions">
        <button type="submit" class="btn btn-primary js-save">ذخیره همه تغییرات</button>
        <a class="btn btn-ghost" href="../index.html" target="_blank">مشاهده کارت ویزیت</a>
      </div>

        </div>
      </form>

      <form id="securityForm" method="post" action="card.php" class="grid" autocomplete="off" style="margin-top:18px">
        <input type="hidden" name="action" value="changePassword">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <section class="card" id="sec-security">
          <header class="card-header">
            <div class="card-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div class="card-header-text">
              <h2 class="card-title">امنیت حساب</h2>
              <p class="card-sub">تغییر رمز عبور ورود به پنل مدیریت</p>
            </div>
          </header>
      <div class="card-body">
        <?php if ($pwError !== ''): ?><div class="alert alert-err" role="alert" style="grid-column:1/-1;margin-bottom:0;"><?= htmlspecialchars($pwError) ?></div><?php endif; ?>

        <div class="field half">
          <label class="field-label" for="current_username">نام کاربری</label>
          <input class="field-input" id="current_username" value="<?= htmlspecialchars($currentUser) ?>" dir="ltr" disabled>
          <span class="field-hint">نام نمایشی: <?= htmlspecialchars($displayName !== '' ? $displayName : '—') ?> — از بخش «نام نمایشی» در انتهای همین صفحه قابل تغییر است.</span>
        </div>
        <div class="field half">
          <label class="field-label" for="current_password">رمز عبور فعلی</label>
          <input class="field-input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="field half">
          <label class="field-label" for="new_password">رمز عبور جدید</label>
          <input class="field-input" type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
          <span class="field-hint">حداقل ۶ کاراکتر.</span>
        </div>
        <div class="field half">
          <label class="field-label" for="confirm_password">تکرار رمز عبور جدید</label>
          <input class="field-input" type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="footer-actions">
          <button type="submit" class="btn btn-primary">تغییر رمز عبور</button>
        </div>
      </div>
    </section>
  </form>
  <form method="post" action="card.php" class="grid" autocomplete="off" style="margin-top:18px">
     <input type="hidden" name="action" value="setLoginGate">
     <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
     <section class="card" id="sec-gate">
       <header class="card-header">
         <div class="card-icon">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
         </div>
         <div class="card-header-text">
           <h2 class="card-title">آدرس ورود (مثل وردپرس)</h2>
           <p class="card-sub">با تنظیم یک کلید مخفی، صفحهٔ ورود فقط با آن کلید باز می‌شود (۴۰۴ برای بقیه).</p>
         </div>
       </header>
       <div class="card-body">
         <?php if ($gateError !== ''): ?><div class="alert alert-err" role="alert" style="grid-column:1/-1;margin-bottom:0;"><?= htmlspecialchars($gateError) ?></div><?php endif; ?>
         <?php if (($_GET['msg'] ?? '') === 'gate'): ?><div class="alert alert-ok" role="status" style="grid-column:1/-1;margin-bottom:0;">کلید ورود ذخیره شد.</div><?php endif; ?>
         <div class="field full">
           <label class="field-label" for="login_gate">کلید مخفی (خالی = بدون محافظ)</label>
           <input class="field-input" type="text" id="login_gate" name="login_gate" value="<?= htmlspecialchars(secrets_get('login_gate')) ?>" dir="ltr" placeholder="مثلاً my-secret-key-2024">
           <span class="field-hint">
             <?php $gk = secrets_get('login_gate'); if ($gk !== ''): ?>
               آدرس ورود: <code>admin/login.php?key=<?= htmlspecialchars(urlencode($gk)) ?></code>
             <?php else: ?>
               بدون کلید، صفحهٔ ورود برای همه باز است.
             <?php endif; ?>
           </span>
         </div>
         <div class="footer-actions">
           <button type="submit" class="btn btn-primary">ذخیرهٔ کلید ورود</button>
         </div>
       </div>
     </section>
   </form>

  <form method="post" action="card.php" class="grid" autocomplete="off" style="margin-top:18px">
    <input type="hidden" name="action" value="changeDisplayName">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <section class="card" id="sec-displayname">
      <header class="card-header">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        </div>
        <div class="card-header-text">
          <h2 class="card-title">نام نمایشی</h2>
          <p class="card-sub">نامی که زیر عنوان ورود و در پنل نمایش داده می‌شود</p>
        </div>
      </header>
      <div class="card-body">
        <?php if ($nameError !== ''): ?><div class="alert alert-err" role="alert" style="grid-column:1/-1;margin-bottom:0;"><?= htmlspecialchars($nameError) ?></div><?php endif; ?>
        <?php if (($_GET['msg'] ?? '') === 'name'): ?><div class="alert alert-ok" role="status" style="grid-column:1/-1;margin-bottom:0;">نام نمایشی ذخیره شد.</div><?php endif; ?>
        <div class="field full">
          <label class="field-label" for="display_name">نام نمایشی</label>
          <input class="field-input" type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($displayName) ?>" maxlength="60" placeholder="مثلاً نام شما" autocomplete="name">
        </div>
        <div class="footer-actions">
          <button type="submit" class="btn btn-primary">ذخیرهٔ نام نمایشی</button>
        </div>
      </div>
    </section>
  </form>

    </div>
  </main>
</div>
<footer class="panel-footer">Digital Business Card Designer: Digital Business Card</footer>

<script>
function addStat(){
  const c=document.getElementById('stats-container');
  const d=document.createElement('div');
  d.className='row-card';
  d.innerHTML='<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button><div class="row-grid"><div class="field"><label class="field-label">عدد</label><input class="field-input" name="stat_num[]" dir="auto"></div><div class="field"><label class="field-label">عنوان</label><input class="field-input" name="stat_label[]" dir="auto"></div></div>';
  c.appendChild(d);
}
function addSocial(){
  const c=document.getElementById('socials-container');
  const d=document.createElement('div');
  d.className='row-card app-row';
  d.innerHTML=appRowHtml('social','ig');
  c.appendChild(d);
  setAppRow(d,'ig',{autoLabel:true});
  const first=d.querySelector('input[name="social_label[]"]');
  if(first) first.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}
function addContact(){
  const c=document.getElementById('contacts-container');
  const d=document.createElement('div');
  d.className='row-card app-row';
  d.innerHTML=appRowHtml('contact','wa');
  c.appendChild(d);
  setAppRow(d,'wa',{autoLabel:true});
  const first=d.querySelector('input[name="contact_label[]"]');
  if(first) first.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}
/* App picker: choosing an app recolours the row, swaps the logo and fills the
   hidden icon/colour fields the server saves. Rows already in the page are
   linked the same way, so the panel and the public card always agree. */
(function initAppPickers(){
  const APPS = <?= json_encode($appCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const PLACEHOLDER = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/></svg>';

  function esc(value){
    return String(value).replace(/[&<>"']/g, function(ch){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[ch];
    });
  }
  function hexToRgba(hex, alpha){
    hex = String(hex || '').replace('#','');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    if (!/^[0-9a-f]{6}$/i.test(hex)) return 'rgba(100,116,139,' + alpha + ')';
    return 'rgba(' + parseInt(hex.slice(0,2),16) + ',' + parseInt(hex.slice(2,4),16) + ',' + parseInt(hex.slice(4,6),16) + ',' + alpha + ')';
  }
  function options(selected){
    return '<option value="">انتخاب کنید…</option>' + Object.keys(APPS).map(function(key){
      return '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>' + esc(APPS[key].label) + '</option>';
    }).join('');
  }
  function appRowHtml(kind, key){
    const app = APPS[key] || null;
    const brand = app ? app.color : '#64748b';
    const fields = kind === 'social'
      ? '<div class="field"><label class="field-label">عنوان</label><input class="field-input" name="social_label[]" placeholder="Instagram" dir="auto"></div>'
        + '<div class="field"><label class="field-label">آدرس لینک</label><input class="field-input js-url" name="social_url[]" dir="ltr" placeholder="https://instagram.com/username"></div>'
      : '<div class="field"><label class="field-label">عنوان</label><input class="field-input" name="contact_label[]" placeholder="WhatsApp" dir="auto"></div>'
        + '<div class="field"><label class="field-label">مقدار نمایشی</label><input class="field-input" name="contact_value[]" placeholder="Message" dir="auto"></div>'
        + '<div class="field full"><label class="field-label">لینک</label><input class="field-input js-url" name="contact_url[]" dir="ltr" placeholder="https://wa.me/..."></div>';
    const colorField = kind === 'social'
      ? '<input type="hidden" class="js-app-color" name="social_color[]" value="' + esc(app ? app.color : '') + '">'
      : '';
    return '<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>'
      + '<div class="app-row-head">'
      +   '<span class="app-badge" aria-hidden="true">' + (app ? app.icon : PLACEHOLDER) + '</span>'
      +   '<div class="app-meta">'
      +     '<span class="app-name">' + esc(app ? app.label : 'یک اپلیکیشن انتخاب کنید') + '</span>'
      +     '<span class="app-hint"' + (app ? '' : ' hidden') + '>رنگ برند <code>' + esc(brand) + '</code></span>'
      +   '</div>'
      +   '<div class="app-picker"><label class="field-label">نوع اپلیکیشن</label>'
      +     '<select class="field-input app-select" aria-label="نوع اپلیکیشن">' + options(key) + '</select>'
      +   '</div>'
      + '</div>'
      + '<div class="row-grid">' + fields + '</div>'
      + '<input type="hidden" class="js-app-icon" name="' + (kind === 'social' ? 'social_icon[]' : 'contact_icon[]') + '" value="' + esc(app ? key : '') + '">'
      + colorField;
  }
  function setAppRow(row, key, opts){
    const app = APPS[key] || null;
    const brand = app ? app.color : '#64748b';
    row.dataset.app = app ? key : '';
    row.style.setProperty('--brand', brand);
    row.style.setProperty('--brand-soft', hexToRgba(brand, 0.10));
    row.style.setProperty('--brand-line', hexToRgba(brand, 0.34));

    const badge = row.querySelector('.app-badge');
    if (badge) badge.innerHTML = app ? app.icon : PLACEHOLDER;
    const name = row.querySelector('.app-name');
    if (name) name.textContent = app ? app.label : 'یک اپلیکیشن انتخاب کنید';
    const hint = row.querySelector('.app-hint');
    if (hint) {
      if (app) {
        hint.innerHTML = 'رنگ برند <code>' + esc(app.color) + '</code>';
        hint.hidden = false;
      } else {
        hint.textContent = '';
        hint.hidden = true;
      }
    }
    const iconField = row.querySelector('.js-app-icon');
    if (iconField) iconField.value = key;
    const colorField = row.querySelector('.js-app-color');
    if (colorField) colorField.value = app ? app.color : (key ? colorField.value : '');

    const label = row.querySelector('input[name="social_label[]"], input[name="contact_label[]"]');
    if (label) {
      const current = label.value.trim();
      const previous = row.dataset.appLabel || '';
      if (opts && opts.autoLabel && (current === '' || current === previous)) {
        label.value = app ? app.label : '';
      }
      row.dataset.appLabel = label.value.trim();
    }
  }

  // New rows created by addSocial()/addContact() use these two helpers.
  window.appRowHtml = appRowHtml;
  window.setAppRow = setAppRow;

  document.addEventListener('change', function(e){
    const select = e.target.closest && e.target.closest('.app-select');
    if (!select) return;
    const row = select.closest('.app-row');
    if (row) setAppRow(row, select.value, {autoLabel:true});
  });

  document.querySelectorAll('.app-row').forEach(function(row){
    const select = row.querySelector('.app-select');
    const iconField = row.querySelector('.js-app-icon');
    const key = iconField ? iconField.value : (select ? select.value : (row.dataset.app || ''));
    setAppRow(row, key, {autoLabel:false});
  });
})();
function addSpecial(){
  const c=document.getElementById('specials-container');
  const d=document.createElement('div');
  d.className='row-card';
  d.innerHTML='<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button><div class="row-grid"><div class="field full"><label class="field-label">عنوان</label><input class="field-input" name="special_label[]" dir="auto"></div><div class="field full"><label class="field-label">زیرعنوان</label><input class="field-input" name="special_sub[]" dir="auto"></div><div class="field full"><label class="field-label">لینک</label><input class="field-input js-url" name="special_url[]" dir="ltr"></div></div>';
  c.appendChild(d);
}

/* Sections: toggle, live title, remove a custom section, and add a new one.
   Every array uses the same explicit index so gaps after a removal cannot
   shift section_order away from its section. */
var sectionSeq = 0;

function setSectionEnabled(cb){
  const item = cb.closest('.section-item');
  if (!item) return;
  const hidden = item.querySelector('input[type="hidden"][name^="section_enabled_hidden"]');
  if (hidden) hidden.value = cb.checked ? '1' : '0';
  item.classList.toggle('is-disabled', !cb.checked);
  const state = item.querySelector('.section-state');
  if (state) {
    state.dataset.state = cb.checked ? 'on' : 'off';
    state.textContent = cb.checked ? 'نمایش' : 'پنهان';
  }
}
function syncSectionTitle(input){
  const item = input.closest('.section-item');
  const name = item ? item.querySelector('.section-name') : null;
  if (name) name.textContent = input.value.trim() || 'بخش بدون عنوان';
}
function renumberSections(){
  const container = document.getElementById('sections-container');
  if (!container) return;
  container.querySelectorAll('.section-item').forEach(function(item, i){
    const input = item.querySelector('.js-section-order');
    if (!input) return;
    input.value = i + 1;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });
}
function removeSection(btn){
  const item = btn.closest('.section-item');
  if (!item) return;
  item.remove();
  renumberSections();
}
function moveSection(btn, dir){
  const item = btn.closest('.section-item');
  const container = document.getElementById('sections-container');
  if (!item || !container) return;
  var siblings = Array.from(container.querySelectorAll('.section-item'));
  var idx = siblings.indexOf(item);
  var targetIdx = idx + (dir > 0 ? 1 : -1);
  if (targetIdx < 0 || targetIdx >= siblings.length) return;
  var target = siblings[targetIdx];
  if (dir < 0) {
    container.insertBefore(item, target);
  } else {
    container.insertBefore(item, target.nextSibling);
  }
  renumberSections();
  try { item.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (e) {}
}
function addSection(){
  const container = document.getElementById('sections-container');
  const titleInput = document.getElementById('new-section-title');
  const textInput = document.getElementById('new-section-text');
  if (!container || !titleInput) return;
  if (typeof titleInput.reportValidity === 'function' && !titleInput.reportValidity()) return;
  const title = titleInput.value.trim();
  if (!title) { titleInput.focus(); return; }
  const content = textInput ? textInput.value.trim() : '';
  const idx = 'n' + Date.now().toString(36) + (sectionSeq++);

  let maxOrder = 0;
  container.querySelectorAll('.js-section-order').forEach(function(inp){
    const n = parseInt(inp.value, 10);
    if (!isNaN(n) && n > maxOrder) maxOrder = n;
  });
  const order = maxOrder + 1;

  const item = document.createElement('div');
  item.className = 'section-item is-custom';
  item.draggable = false;
  item.innerHTML =
    '<div class="section-item-left">'
      + '<span class="section-drag-handle" draggable="true" title="کشیدن برای تغییر ترتیب" aria-label="تغییر ترتیب">⋮⋮</span>'
      + '<label class="section-toggle">'
        + '<input type="checkbox" name="section_enabled[' + idx + ']" value="1" checked onchange="setSectionEnabled(this)" draggable="false" aria-label="نمایش بخش در کارت ویزیت" title="روشن/خاموش کردن نمایش این بخش">'
        + '<input type="hidden" name="section_enabled_hidden[' + idx + ']" value="1">'
        + '<span class="section-toggle-track"><span class="section-toggle-thumb"></span></span>'
      + '</label>'
      + '<div class="section-info"><span class="section-name"></span><span class="section-meta-row"><span class="section-key">بخش دلخواه</span><span class="section-state" data-state="on">نمایش</span></span></div>'
    + '</div>'
    + '<div class="section-order"><span>ترتیب</span>'
      + '<input class="js-section-order" type="number" draggable="false" name="section_order[' + idx + ']" value="' + order + '" min="1">'
      + '<button type="button" class="section-remove" onclick="removeSection(this)">حذف</button>'
    + '</div>'
    + '<div class="section-custom-fields">'
      + '<div class="field"><label class="field-label">عنوان بخش</label>'
        + '<input class="field-input js-section-title" type="text" draggable="false" name="section_label[' + idx + ']" maxlength="80" placeholder="مثلاً خدمات من" oninput="syncSectionTitle(this)" dir="auto">'
      + '</div>'
      + '<div class="field"><label class="field-label">متن بخش</label>'
        + '<textarea class="field-input field-textarea" name="section_content[' + idx + ']" rows="3" maxlength="2000" placeholder="متنی که زیر عنوان این بخش روی کارت ویزیت نمایش داده می‌شود" dir="auto"></textarea>'
      + '</div>'
    + '</div>'
    + '<input type="hidden" name="section_key[' + idx + ']" value="' + idx + '">';

  container.appendChild(item);
  const titleField = item.querySelector('.js-section-title');
  const contentField = item.querySelector('textarea[name^="section_content"]');
  const nameEl = item.querySelector('.section-name');
  if (titleField) titleField.value = title;
  if (nameEl) nameEl.textContent = title;
  if (contentField) contentField.value = content;

  titleInput.value = '';
  if (textInput) textInput.value = '';
  if (titleField) titleField.dispatchEvent(new Event('input', { bubbles: true }));
  item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  if (contentField) contentField.focus({ preventScroll: true });
}

/* Country picker: the panel only stores the selected names. Each chip carries
   a hidden country_code[] input; the server maps the code back to its name. */
(function initCountryPicker(){
  const list = document.getElementById('country-list');
  const select = document.getElementById('country-add');
  const addBtn = document.getElementById('country-add-btn');
  const empty = document.getElementById('country-empty');
  if (!list || !select || !addBtn) return;

  const FLAGS = <?= json_encode($flagCodes, JSON_UNESCAPED_UNICODE) ?>;

  function refreshEmpty(){
    if (!empty) return;
    empty.hidden = list.querySelector('.country-chip') !== null;
  }

  function syncOptions(){
    const used = new Set(
      Array.from(list.querySelectorAll('.country-chip')).map(function(chip){
        return chip.dataset.code;
      })
    );
    Array.from(select.options).forEach(function(opt){
      if (!opt.value) return;
      opt.disabled = used.has(opt.value);
    });
    if (used.has(select.value)) select.value = '';
  }

  function buildChip(code, name){
    const chip = document.createElement('span');
    chip.className = 'country-chip';
    chip.dataset.code = code;

    if (FLAGS.indexOf(code) !== -1) {
      const img = document.createElement('img');
      img.src = '../image/flags/' + code + '.png';
      img.alt = '';
      img.addEventListener('error', function(){
        const badge = document.createElement('span');
        badge.className = 'chip-code';
        badge.textContent = code.toUpperCase();
        if (img.parentNode) img.parentNode.replaceChild(badge, img);
      });
      chip.appendChild(img);
    } else {
      const badge = document.createElement('span');
      badge.className = 'chip-code';
      badge.textContent = code.toUpperCase();
      chip.appendChild(badge);
    }

    const label = document.createElement('span');
    label.textContent = name;
    chip.appendChild(label);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'chip-remove';
    remove.setAttribute('aria-label', 'حذف ' + name);
    remove.textContent = '×';
    chip.appendChild(remove);

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'country_code[]';
    hidden.value = code;
    chip.appendChild(hidden);

    return chip;
  }

  function addSelected(){
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;
    if (list.querySelector('.country-chip[data-code="' + opt.value + '"]')) return;
    list.appendChild(buildChip(opt.value, opt.textContent.trim()));
    select.value = '';
    syncOptions();
    refreshEmpty();
  }

  addBtn.addEventListener('click', addSelected);
  select.addEventListener('change', addSelected);

  // Remove works for both chips rendered by PHP and chips added here.
  list.addEventListener('click', function(e){
    const remove = e.target.closest('.chip-remove');
    if (!remove) return;
    const chip = remove.closest('.country-chip');
    if (chip) chip.remove();
    syncOptions();
    refreshEmpty();
  });

  syncOptions();
  refreshEmpty();
})();

/* Highlight the sidebar link for whichever section is on screen. */
(function initNavSpy(){
  return; // disabled: single-section view (initSectionTabs) handles active highlighting
  const links = Array.from(document.querySelectorAll('.side-nav .nav-link'));
  if (!links.length) return;
  const targets = links
    .map(function(link){
      const id = link.getAttribute('href').slice(1);
      return { link: link, el: document.getElementById(id) };
    })
    .filter(function(item){ return item.el; });
  if (!targets.length) return;

  function setActive(link){
    links.forEach(function(l){ l.classList.toggle('active', l === link); });
    // On the mobile layout the nav scrolls horizontally; keep the active item
    // visible without scrolling the whole page.
    const nav = link.closest('.side-nav');
    if (nav && window.matchMedia('(max-width:1040px)').matches) {
      const linkRect = link.getBoundingClientRect();
      const navRect = nav.getBoundingClientRect();
      if (linkRect.left < navRect.left || linkRect.right > navRect.right) {
        nav.scrollBy({ left: linkRect.left - navRect.left - 12, behavior: 'smooth' });
      }
    }
  }

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function(entries){
      const visible = entries
        .filter(function(e){ return e.isIntersecting; })
        .sort(function(a, b){ return b.intersectionRatio - a.intersectionRatio; });
      if (visible.length) {
        const found = targets.find(function(t){ return t.el === visible[0].target; });
        if (found) setActive(found.link);
      }
    }, { rootMargin: '-15% 0px -65% 0px', threshold: [0, 0.2, 0.6] });
    targets.forEach(function(t){ observer.observe(t.el); });
  }

  links.forEach(function(link){
    link.addEventListener('click', function(){ setActive(link); });
  });
})();

/* Thin reading-progress line at the very top of the page. */
(function initScrollProgress(){
  const bar = document.getElementById('scrollProgress');
  if (!bar) return;
  let frame = 0;
  function update(){
    frame = 0;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const progress = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
    bar.style.transform = 'scaleX(' + progress + ')';
  }
  function schedule(){
    if (!frame) frame = window.requestAnimationFrame(update);
  }
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule);
  update();
})();

/* Saved / unsaved feedback + Ctrl+S + a guard against losing edits. */
(function initSaveState(){
  const form = document.getElementById('cardForm');
  const state = document.getElementById('saveState');
  if (!form) return;
  const IDLE_TEXT = 'همه تغییرات ذخیره شده';
  const DIRTY_TEXT = 'تغییرات ذخیره نشده';
  let dirty = false;
  let leaving = false;

  function paint(mode){
    if (!state) return;
    state.dataset.state = mode;
    state.textContent = mode === 'dirty' ? DIRTY_TEXT : (mode === 'saving' ? 'در حال ذخیره…' : IDLE_TEXT);
  }
  function markDirty(){
    if (dirty) return;
    dirty = true;
    paint('dirty');
  }

  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('click', function(e){
    if (e.target.closest('.remove,.chip-remove,.add-row')) markDirty();
  });
  // ------------------------------------------------------------------
  // Link validation, mirroring PHP's normalize_link() closely enough to
  // stop an unusable URL before the form leaves the page.
  // ------------------------------------------------------------------
  function isProbablyLink(v){
    if (/[\x00-\x20\x7F]/.test(v)) return false;
    if (v.length > 2048) return false;
    if (/^https?:\/\/\S+$/i.test(v)) return true;
    if (/^mailto:[^@\s]+@[^@\s]+(\?\S*)?$/i.test(v)) return true;
    if (/^tel:\+?[0-9().\-\s]{3,}$/i.test(v)) return true;
    if (/^#[A-Za-z0-9_-]{1,64}$/.test(v)) return true;
    if (/^\/[A-Za-z0-9._~!$&'()*+,;=:@%\/-]*$/.test(v)) return true;
    if (/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+(?:[\/?#]\S*)?$/i.test(v)) return true;
    return false;
  }
  function showFieldError(input, message){
    input.classList.add('is-invalid');
    var next = input.nextElementSibling;
    if (!next || !next.classList.contains('field-error')) {
      next = document.createElement('p');
      next.className = 'field-error';
      input.insertAdjacentElement('afterend', next);
    }
    next.textContent = message;
    next.classList.add('is-shown');
  }
  function clearFieldError(input){
    input.classList.remove('is-invalid');
    var next = input.nextElementSibling;
    if (next && next.classList.contains('field-error')) next.classList.remove('is-shown');
  }
  form.addEventListener('input', function(e){
    if (e.target && e.target.classList && e.target.classList.contains('js-url') && e.target.classList.contains('is-invalid')) {
      clearFieldError(e.target);
    }
  });

  form.addEventListener('submit', function(e){
    // Client-side link check: catch a bad URL before PHP silently drops it,
    // and point at the exact field that needs attention.
    var firstBad = null;
    form.querySelectorAll('.js-url').forEach(function(inp){
      var v = (inp.value || '').trim();
      if (!v || isProbablyLink(v)) { clearFieldError(inp); return; }
      showFieldError(inp, 'آدرس معتبر نیست. با https:// شروع کن یا دامنهٔ کامل بنویس.');
      if (!firstBad) firstBad = inp;
    });
    if (firstBad) {
      e.preventDefault();
      firstBad.focus({ preventScroll: true });
      firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    var ed = document.getElementById('about-editor');
    var ta = document.getElementById('about_html');
    if (ed && ta) ta.value = ed.innerHTML;
    // Remember which panel section was open, so the post-save redirect can
    // come back to it instead of dropping the admin on "اطلاعات اصلی".
    var returnSection = document.getElementById('returnSection');
    if (returnSection) {
      var hash = (location.hash || '').replace('#', '');
      returnSection.value = /^sec-[a-z0-9-]+$/.test(hash) ? hash : '';
    }
    leaving = true;
    paint('saving');
    document.querySelectorAll('.js-save').forEach(function(btn){ btn.classList.add('is-loading'); });
    // If the request is slow or fails, never leave the buttons stuck forever.
    window.setTimeout(function(){
      document.querySelectorAll('.js-save').forEach(function(btn){ btn.classList.remove('is-loading'); });
      paint(dirty ? 'dirty' : 'idle');
    }, 15000);
  });
  window.addEventListener('beforeunload', function(e){
    if (!dirty || leaving) return;
    e.preventDefault();
    e.returnValue = '';
  });

  document.addEventListener('keydown', function(e){
    if (!(e.ctrlKey || e.metaKey) || String(e.key).toLowerCase() !== 's') return;
    e.preventDefault();
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
  });
})();

/* Settings → a quick live sample of both calendar formats. */
(function initSettingsPreview(){
  const j = document.getElementById('settings-today-jalali');
  const g = document.getElementById('settings-today-gregorian');
  if (!j && !g) return;
  const now = new Date();
  try {
    if (j) j.textContent = now.toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' });
    if (g) g.textContent = now.toLocaleDateString('en-GB', { year: 'numeric', month: 'long', day: 'numeric' });
  } catch (err) {
    if (j) j.textContent = now.toLocaleDateString();
    if (g) g.textContent = now.toLocaleDateString();
  }
})();

/* Toasts fade themselves out; clicking one closes it early. */
(function initToasts(){
  document.querySelectorAll('.toast').forEach(function(toast){
    function hide(){
      toast.classList.add('is-hiding');
      window.setTimeout(function(){ toast.remove(); }, 240);
    }
    toast.addEventListener('click', hide);
    window.setTimeout(hide, 5200);
  });
})();

function addGallery(){
  const c=document.getElementById('gallery-container');
  const d=document.createElement('div');
  d.className='row-card';
  d.innerHTML='<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button><div class="row-grid"><div class="field full"><label class="field-label">آدرس عکس</label><input class="field-input" name="gallery_image[]" dir="ltr"></div><div class="field full"><label class="field-label">آپلود فایل</label><input class="field-input" type="file" name="gallery_upload[]" accept="image/*"></div><div class="field"><label class="field-label">عنوان</label><input class="field-input" name="gallery_title[]" dir="auto"></div><div class="field"><label class="field-label">لینک (اختیاری)</label><input class="field-input js-url" name="gallery_url[]" dir="ltr"></div></div>';
  c.appendChild(d);
}
const EXP_CAL_ICON = '<svg class="exp-date-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>';

function addExperience(){
  const c=document.getElementById('experience-container');
  const d=document.createElement('div');
  d.className='row-card exp-row';
  d.innerHTML =
    '<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>'
    + '<div class="row-grid">'
    + '<div class="field"><label class="field-label">سمت / عنوان</label><input class="field-input" name="exp_role[]" dir="auto"></div>'
    + '<div class="field"><label class="field-label">شرکت / سازمان</label><input class="field-input" name="exp_company[]" dir="auto"></div>'
    + '</div>'
    + '<div class="exp-period-block">'
    + '<span class="field-label">دوره کاری <span class="exp-period-note">تقویم شمسی</span></span>'
    + '<div class="exp-period-row">'
    + '<button type="button" class="exp-date-btn is-empty" data-kind="start" onclick="openExpCalendar(this)">' + EXP_CAL_ICON + '<span class="exp-date-value" data-label-start>تاریخ شروع</span></button>'
    + '<span class="exp-period-sep" aria-hidden="true">—</span>'
    + '<button type="button" class="exp-date-btn is-empty" data-kind="end" onclick="openExpCalendar(this)">' + EXP_CAL_ICON + '<span class="exp-date-value" data-label-end>تاریخ پایان</span></button>'
    + '<label class="exp-current"><input type="checkbox" class="exp-current-cb" onchange="onExpCurrentChange(this)"> <span>تاکنون</span></label>'
    + '</div>'
    + '<div class="field full" style="margin-top:4px;">'
    + '<label class="field-label">توضیحات / کارهای انجام‌شده / علت خروج (اختیاری)</label>'
    + '<textarea class="field-textarea" name="exp_details[]" rows="3" dir="auto" placeholder="مثلاً: مسئول راه‌اندازی شبکه، پشتیبانی ۴۰ کاربر، خروج به دلیل ..."></textarea>'
    + '<p class="field-hint">این متن وقتی موس یا انگشت روی تجربه می‌رود نمایش داده می‌شود (نه زیر آن).</p>'
    + '</div>'
    + '<div class="exp-calendar" hidden></div>'
    + '<div class="exp-duration" aria-live="polite"></div>'
    + '<input type="hidden" name="exp_start[]" class="exp-start" value="">'
    + '<input type="hidden" name="exp_end[]" class="exp-end" value="">'
    + '<input type="hidden" name="exp_current_val[]" class="exp-current-val" value="0">'
    + '<input type="hidden" name="exp_period[]" class="exp-period" value="">'
    + '<input type="hidden" name="exp_period_jalali[]" class="exp-period-jalali" value="">'
    + '</div>';
  c.appendChild(d);
  refreshExpRow(d);
  const f=d.querySelector('input[name="exp_role[]"]');
  if(f) f.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}
function addEducation(){
  const c = document.getElementById('education-container');
  const d = document.createElement('div');
  d.className = 'row-card edu-row';
  d.innerHTML =
    '<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>'
    + '<div class="row-grid">'
    + '<div class="field"><label class="field-label">نوع مدرک</label><input class="field-input" name="edu_degree[]" dir="auto" placeholder="دیپلم، کارشناسی، ارشد، دکتری …"></div>'
    + '<div class="field"><label class="field-label">رشته تحصیلی</label><input class="field-input" name="edu_field[]" dir="auto" placeholder="مثلاً مهندسی نرم‌افزار"></div>'
    + '<div class="field full"><label class="field-label">موسسه / دانشگاه</label><input class="field-input" name="edu_org[]" dir="auto"></div>'
    + '<div class="field full"><label class="field-label">فایل مدرک (اختیاری — تصویر یا PDF)</label><input class="field-input" type="file" name="edu_file[]" accept="image/*,.pdf,application/pdf"></div>'
    + '</div>'
    + '<div class="exp-period-block">'
    + '<span class="field-label">تاریخ دریافت (تقویم شمسی)</span>'
    + '<div class="exp-period-row">'
    + '<button type="button" class="exp-date-btn is-empty" onclick="openSingleDateCalendar(this)">' + EXP_CAL_ICON + '<span class="exp-date-value single-date-label">انتخاب تاریخ</span></button>'
    + '</div>'
    + '<div class="exp-calendar" hidden></div>'
    + '<input type="hidden" name="edu_date[]" class="single-date" value="">'
    + '<input type="hidden" name="edu_date_g[]" class="single-date-g" value="">'
    + '<input type="hidden" name="edu_meta[]" class="single-meta" value="">'
    + '<input type="hidden" name="edu_meta_jalali[]" class="single-meta-jalali" value="">'
    + '</div>';
  c.appendChild(d);
  refreshSingleDateRow(d);
  const f = d.querySelector('input[name="edu_degree[]"]');
  if (f) f.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}

function addCert(){
  const c = document.getElementById('certs-container');
  const d = document.createElement('div');
  d.className = 'row-card cert-row';
  d.innerHTML =
    '<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button>'
    + '<div class="row-grid">'
    + '<div class="field"><label class="field-label">عنوان / مدرک</label><input class="field-input" name="cert_title[]" dir="auto"></div>'
    + '<div class="field"><label class="field-label">موسسه / صادرکننده</label><input class="field-input" name="cert_org[]" dir="auto"></div>'
    + '</div>'
    + '<div class="field full"><label class="field-label">فایل گواهینامه (اختیاری — تصویر یا PDF)</label><input class="field-input" type="file" name="cert_file[]" accept="image/*,.pdf,application/pdf"></div>'
    + '<div class="exp-period-block">'
    + '<span class="field-label">تاریخ دریافت (تقویم شمسی)</span>'
    + '<div class="exp-period-row">'
    + '<button type="button" class="exp-date-btn is-empty" onclick="openSingleDateCalendar(this)">' + EXP_CAL_ICON + '<span class="exp-date-value single-date-label">انتخاب تاریخ</span></button>'
    + '</div>'
    + '<div class="exp-calendar" hidden></div>'
    + '<input type="hidden" name="cert_date[]" class="single-date" value="">'
    + '<input type="hidden" name="cert_date_g[]" class="single-date-g" value="">'
    + '<input type="hidden" name="cert_meta[]" class="single-meta" value="">'
    + '<input type="hidden" name="cert_meta_jalali[]" class="single-meta-jalali" value="">'
    + '</div>';
  c.appendChild(d);
  refreshSingleDateRow(d);
  const f = d.querySelector('input[name="cert_title[]"]');
  if (f) f.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}

function openSingleDateCalendar(btn){
  const row = btn.closest('.row-card');
  if (!row) return;
  const cal = row.querySelector('.exp-calendar');
  if (!cal) return;
  if (!cal.hidden){ cal.hidden = true; return; }
  const hidden = row.querySelector('.single-date');
  const sel = parseJDate(hidden.value);
  const t = todayJalali();
  row._calView = sel ? {jy: sel.jy, jm: sel.jm} : {jy: t.jy, jm: t.jm};
  cal.hidden = false;
  drawSingleDateCalendar(row);
}

function drawSingleDateCalendar(row){
  const cal = row.querySelector('.exp-calendar');
  const view = row._calView;
  if (!view) return;
  const hidden = row.querySelector('.single-date');
  const sel = parseJDate(hidden.value);
  const len = jalaliMonthLength(view.jy, view.jm);
  const firstJdn = j2d(view.jy, view.jm, 1);
  const g = d2g(firstJdn);
  const firstDow = new Date(g.gy, g.gm-1, g.gd).getDay();
  const offset = (firstDow + 1) % 7;
  const today = todayJalali();
  let cells = '';
  for (let i=0;i<offset;i++) cells += '<span class="exp-cal-day is-empty"></span>';
  for (let dd=1; dd<=len; dd++){
    const isSel = sel && sel.jy===view.jy && sel.jm===view.jm && sel.jd===dd;
    const isToday = today && today.jy===view.jy && today.jm===view.jm && today.jd===dd;
    cells += '<button type="button" class="exp-cal-day' + (isSel?' is-selected':'') + (isToday?' is-today':'') + '" onclick="pickSingleDate(this,' + dd + ')">' + faNum(dd) + '</button>';
  }
  const yearOpts = EXP_CAL_YEARS.map(function(y){
    return '<option value="' + y + '"' + (y===view.jy?' selected':'') + '>' + faNum(y) + '</option>';
  }).join('');
  const monthOpts = JALALI_MONTHS.map(function(m,i){
    return '<option value="' + (i+1) + '"' + ((i+1)===view.jm?' selected':'') + '>' + m + '</option>';
  }).join('');
  cal.innerHTML =
    '<div class="exp-cal-top">'
      + '<button type="button" class="exp-cal-nav" onclick="singleCalNav(this,-1)" aria-label="ماه قبل">›</button>'
      + '<div class="exp-cal-selects">'
      + '<select class="exp-cal-month" onchange="singleCalJump(this)">' + monthOpts + '</select>'
      + '<select class="exp-cal-year" onchange="singleCalJump(this)">' + yearOpts + '</select>'
      + '</div>'
      + '<button type="button" class="exp-cal-nav" onclick="singleCalNav(this,1)" aria-label="ماه بعد">‹</button>'
    + '</div>'
    + '<div class="exp-cal-week"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div>'
    + '<div class="exp-cal-grid">' + cells + '</div>'
    + '<div class="exp-cal-foot">'
      + '<button type="button" onclick="singleCalToday(this)">امروز</button>'
      + '<button type="button" class="is-danger" onclick="singleCalClear(this)">پاک کردن</button>'
    + '</div>';
}

function singleCalJump(sel){
  const row = sel.closest('.row-card');
  const view = row._calView;
  const cal = row.querySelector('.exp-calendar');
  view.jm = +cal.querySelector('.exp-cal-month').value;
  view.jy = +cal.querySelector('.exp-cal-year').value;
  drawSingleDateCalendar(row);
}
function singleCalNav(btn, dir){
  const row = btn.closest('.row-card');
  const view = row._calView;
  view.jm += dir;
  if (view.jm > 12){ view.jm=1; view.jy++; }
  if (view.jm < 1){ view.jm=12; view.jy--; }
  if (view.jy < EXP_CAL_YEARS[0]) view.jy = EXP_CAL_YEARS[0];
  if (view.jy > EXP_CAL_YEARS[EXP_CAL_YEARS.length-1]) view.jy = EXP_CAL_YEARS[EXP_CAL_YEARS.length-1];
  drawSingleDateCalendar(row);
}
function pickSingleDate(btn, day){
  const row = btn.closest('.row-card');
  const hidden = row.querySelector('.single-date');
  const view = row._calView;
  hidden.value = view.jy + '-' + String(view.jm).padStart(2,'0') + '-' + String(day).padStart(2,'0');
  const cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  // Keep the original "meta" label untouched; the server derives the
  // Gregorian/Jalali labels from the picked date on save.
  const g = jalaliToGregorian(view.jy, view.jm, day);
  const gHidden = row.querySelector('.single-date-g');
  if (g && gHidden) gHidden.value = g.gy + '-' + String(g.gm).padStart(2,'0') + '-' + String(g.gd).padStart(2,'0');
  const metaJalaliHidden = row.querySelector('.single-meta-jalali');
  if (metaJalaliHidden) metaJalaliHidden.value = faNum(day) + ' ' + JALALI_MONTHS[view.jm - 1] + ' ' + faNum(view.jy);
  refreshSingleDateRow(row);
}
function singleCalToday(btn){
  const row = btn.closest('.row-card');
  const hidden = row.querySelector('.single-date');
  const t = todayJalali();
  hidden.value = t.jy + '-' + String(t.jm).padStart(2,'0') + '-' + String(t.jd).padStart(2,'0');
  const cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  const g = jalaliToGregorian(t.jy, t.jm, t.jd);
  const gHidden = row.querySelector('.single-date-g');
  if (g && gHidden) gHidden.value = g.gy + '-' + String(g.gm).padStart(2,'0') + '-' + String(g.gd).padStart(2,'0');
  const metaJalaliHidden2 = row.querySelector('.single-meta-jalali');
  if (metaJalaliHidden2) metaJalaliHidden2.value = formatJDate(t);
  refreshSingleDateRow(row);
}
function singleCalClear(btn){
  const row = btn.closest('.row-card');
  const hidden = row.querySelector('.single-date');
  hidden.value = '';
  const cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  const gHidden = row.querySelector('.single-date-g');
  if (gHidden) gHidden.value = '';
  refreshSingleDateRow(row);
}
function refreshSingleDateRow(row){
  if (!row) return;
  const hidden = row.querySelector('.single-date');
  const sel = parseJDate(hidden.value);
  const btn = row.querySelector('.exp-date-btn');
  const label = row.querySelector('.single-date-label');
  if (sel){ label.textContent = formatJDate(sel); btn.classList.remove('is-empty'); }
  else { label.textContent = 'انتخاب تاریخ'; btn.classList.add('is-empty'); }
  const metaJalali = row.querySelector('.single-meta-jalali');
  if (metaJalali) metaJalali.value = sel ? formatJDate(sel) : '';
}

// Close single-date calendars on outside click (reuse existing listener by extending)
document.addEventListener('click', function(e){
  document.querySelectorAll('.exp-calendar').forEach(function(cal){
    if (cal.hidden) return;
    const row = cal.closest('.row-card');
    if (row && row.querySelector('.single-date') && !row.contains(e.target)){
      cal.hidden = true;
    }
  });
});

function addProject(){
  const c=document.getElementById('projects-container');
  const d=document.createElement('div');
  d.className='row-card';
  d.innerHTML='<button type="button" class="remove" onclick="this.parentElement.remove()">حذف</button><div class="row-grid"><div class="field"><label class="field-label">عنوان پروژه</label><input class="field-input" name="proj_title[]" dir="auto"></div><div class="field full"><label class="field-label">لینک</label><input class="field-input" name="proj_url[]" dir="ltr"></div></div>';
  c.appendChild(d);
  const f=d.querySelector('input[name="proj_title[]"]');
  if(f) f.focus({preventScroll:true});
  d.scrollIntoView({block:'nearest',behavior:'smooth'});
}

/* Jalali (Shamsi) calendar + experience date picker. */
const JALALI_BREAKS = [-61,9,38,199,426,686,756,818,1111,1181,1210,1635,2060,2097,2192,2262,2324,2394,2456,3178];
const JALALI_MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
const EXP_EN_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const EXP_CAL_YEARS = (function(){ const a=[]; for(let y=1300;y<=1440;y++) a.push(y); return a; })();

function jalDiv(a,b){ return Math.trunc(a/b); }
function jalMod(a,b){ return a - Math.trunc(a/b)*b; }

function jalCal(jy){
  if (jy < JALALI_BREAKS[0] || jy >= JALALI_BREAKS[JALALI_BREAKS.length-1]) return null;
  var bl=JALALI_BREAKS.length, gy=jy+621, leapJ=-14, jp=JALALI_BREAKS[0], jump=0;
  for (var i=1;i<bl;i++){
    var jm=JALALI_BREAKS[i]; jump=jm-jp;
    if (jy < jm) break;
    leapJ += jalDiv(jump,33)*8 + jalDiv(jalMod(jump,33),4);
    jp=jm;
  }
  var n=jy-jp;
  leapJ += jalDiv(n,33)*8 + jalDiv(jalMod(n,33)+3,4);
  if (jalMod(jump,33)===4 && jump-n===4) leapJ += 1;
  var leapG = jalDiv(gy,4) - jalDiv((jalDiv(gy,100)+1)*3,4) - 150;
  var march = 20 + leapJ - leapG;
  if (jump - n < 6) n = n - jump + jalDiv(jump+4,33)*33;
  var leap = jalMod(jalMod(n+1,33)-1,4);
  if (leap === -1) leap = 4;
  return {leap:leap, gy:gy, march:march};
}
function j2d(jy,jm,jd){
  var r=jalCal(jy); if(!r) return null;
  return g2d(r.gy,3,r.march) + (jm-1)*31 - jalDiv(jm,7)*(jm-7) + jd - 1;
}
function g2d(gy,gm,gd){
  var d = jalDiv((gy + jalDiv(gm-8,6) + 100100)*1461,4)
       + jalDiv(153*jalMod(gm+9,12)+2,5)
       + gd - 34840408;
  return d - jalDiv(jalDiv(gy+100100+jalDiv(gm-8,6),100)*3,4) + 752;
}
function d2g(jdn){
  var j=4*jdn+139361631;
  j = j + jalDiv(jalDiv(4*jdn+183187720,146097)*3,4)*4 - 3908;
  var i = jalDiv(jalMod(j,1461),4)*5 + 308;
  var gd = jalDiv(jalMod(i,153),5)+1;
  var gm = jalMod(jalDiv(i,153),12)+1;
  var gy = jalDiv(j,1461)-100100 + jalDiv(8-gm,6);
  return {gy:gy, gm:gm, gd:gd};
}
function d2j(jdn){
  var g=d2g(jdn), gy=g.gy, jy=gy-621, r=jalCal(jy);
  if(!r) return null;
  var jdn1f=g2d(gy,3,r.march), k=jdn-jdn1f, jm, jd;
  if (k >= 0) {
    if (k <= 185){ jm=1+jalDiv(k,31); jd=jalMod(k,31)+1; return {jy:jy,jm:jm,jd:jd}; }
    k -= 186;
  } else {
    jy -= 1; k += 179; if (r.leap===1) k += 1;
  }
  jm = 7 + jalDiv(k,30);
  jd = jalMod(k,30)+1;
  return {jy:jy,jm:jm,jd:jd};
}
function jalaliToGregorian(jy,jm,jd){
  var jdn=j2d(jy,jm,jd); if(jdn===null) return null; return d2g(jdn);
}
function gregorianToJalali(gy,gm,gd){
  return d2j(g2d(gy,gm,gd));
}
function jalaliMonthLength(jy,jm){
  if (jm <= 6) return 31;
  if (jm <= 11) return 30;
  var r=jalCal(jy); return (r && r.leap===0) ? 30 : 29;
}
function todayJalali(){ var t=new Date(); return gregorianToJalali(t.getFullYear(), t.getMonth()+1, t.getDate()); }
function parseJDate(s){
  if (!s || typeof s !== 'string') return null;
  var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
  if (!m) return null;
  var jy=+m[1], jm=+m[2], jd=+m[3];
  if (jm<1 || jm>12 || jd<1 || jd>jalaliMonthLength(jy,jm)) return null;
  return {jy:jy, jm:jm, jd:jd};
}
function faNum(n){ return String(n).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
function formatJDate(j){ return faNum(j.jd) + ' ' + JALALI_MONTHS[j.jm-1] + ' ' + faNum(j.jy); }
function jalaliDiff(a,b){
  if (!a || !b) return null;
  var cmp = a.jy*10000+a.jm*100+a.jd > b.jy*10000+b.jm*100+b.jd;
  if (cmp) return null;
  var y=b.jy-a.jy, m=b.jm-a.jm, d=b.jd-a.jd;
  if (d < 0){
    m--;
    var pm=b.jm-1, py=b.jy;
    if (pm===0){ pm=12; py--; }
    d += jalaliMonthLength(py,pm);
  }
  if (m < 0){ y--; m += 12; }
  if (y < 0){ y=0; m=0; d=0; }
  return {y:y, m:m, d:d};
}

function toFaDigits(s){ return String(s).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d];}); }

function buildExpPeriodEn(start, end, current){
  var gs = jalaliToGregorian(start.jy, start.jm, start.jd);
  if (!gs) return '';
  var sLabel = EXP_EN_MONTHS[gs.gm-1] + ' ' + gs.gy;
  var diff;
  var eLabel;
  if (current){
    eLabel = 'Present';
    diff = jalaliDiff(start, todayJalali());
  } else if (end){
    var ge = jalaliToGregorian(end.jy, end.jm, end.jd);
    if (!ge) return '';
    eLabel = EXP_EN_MONTHS[ge.gm-1] + ' ' + ge.gy;
    diff = jalaliDiff(start, end);
  } else {
    return sLabel;
  }
  var parts=[];
  if (diff && diff.y>0) parts.push(diff.y + ' yr' + (diff.y>1?'s':''));
  if (diff && diff.m>0) parts.push(diff.m + ' mo' + (diff.m>1?'s':''));
  var extra = parts.length ? ' · ' + parts.join(' ') : '';
  return sLabel + ' – ' + eLabel + extra;
}

function buildExpPeriodJalali(start, end, current){
  var sLabel = formatJDate(start);
  var diff;
  var eLabel;
  if (current){ eLabel = 'تاکنون'; diff = jalaliDiff(start, todayJalali()); }
  else if (end){ eLabel = formatJDate(end); diff = jalaliDiff(start, end); }
  else return sLabel;
  var parts = [];
  if (diff && diff.y > 0) parts.push(faNum(diff.y) + ' سال');
  if (diff && diff.m > 0) parts.push(faNum(diff.m) + ' ماه');
  var extra = parts.length ? ' · ' + parts.join(' ') : '';
  return sLabel + ' – ' + eLabel + extra;
}

function refreshExpRow(row){
  if (!row) return;
  var start = parseJDate(row.querySelector('.exp-start').value);
  var end = parseJDate(row.querySelector('.exp-end').value);
  var cb = row.querySelector('.exp-current-cb');
  var current = !!(cb && cb.checked);
  row.querySelector('.exp-current-val').value = current ? '1' : '0';
  row.classList.toggle('is-current', current);

  var startBtn = row.querySelector('[data-kind="start"]');
  var endBtn = row.querySelector('[data-kind="end"]');
  var startLabel = row.querySelector('[data-label-start]');
  var endLabel = row.querySelector('[data-label-end]');

  if (start){ startLabel.textContent = formatJDate(start); startBtn.classList.remove('is-empty'); }
  else { startLabel.textContent = 'تاریخ شروع'; startBtn.classList.add('is-empty'); }

  if (current){
    endLabel.textContent = 'تاکنون';
    endBtn.classList.add('is-empty','is-muted');
  } else if (end){
    endLabel.textContent = formatJDate(end);
    endBtn.classList.remove('is-empty','is-muted');
  } else {
    endLabel.textContent = 'تاریخ پایان';
    endBtn.classList.add('is-empty');
    endBtn.classList.remove('is-muted');
  }

  var durEl = row.querySelector('.exp-duration');
  if (start && (end || current)){
    var diff = jalaliDiff(start, current ? todayJalali() : end);
    if (diff === null){
      durEl.innerHTML = '<span class="exp-duration-warn">تاریخ پایان باید بعد از شروع باشد.</span>';
    } else if (diff.y === 0 && diff.m === 0 && diff.d === 0){
      durEl.innerHTML = '<span class="exp-duration-label">مدت تجربه: </span><span class="exp-chip"><b>۰</b> روز</span>';
    } else {
      var chips = '';
      if (diff.y > 0) chips += '<span class="exp-chip"><b>' + faNum(diff.y) + '</b> سال</span>';
      if (diff.m > 0) chips += '<span class="exp-chip"><b>' + faNum(diff.m) + '</b> ماه</span>';
      if (diff.d > 0) chips += '<span class="exp-chip"><b>' + faNum(diff.d) + '</b> روز</span>';
      if (!chips) chips = '<span class="exp-chip"><b>۰</b> روز</span>';
      durEl.innerHTML = '<span class="exp-duration-label">مدت تجربه: </span>' + chips;
    }
  } else {
    durEl.innerHTML = '';
  }

  var hidden = row.querySelector('.exp-period');
  var jalaliHidden = row.querySelector('.exp-period-jalali');
  if (start){
    var enStr = buildExpPeriodEn(start, end, current);
    if (enStr) hidden.value = enStr;
    if (jalaliHidden) jalaliHidden.value = buildExpPeriodJalali(start, end, current);
    row.dataset.legacyPeriod = '0';
    var legacy = row.querySelector('.exp-legacy');
    if (legacy) legacy.hidden = true;
  } else if (row.dataset.legacyPeriod !== '1'){
    // Clearing the start date also clears a previously auto-generated period.
    // A row that only ever had a stored (legacy) period keeps it untouched.
    if (jalaliHidden) jalaliHidden.value = '';
    if (hidden) hidden.value = '';
  }
}

function openExpCalendar(btn){
  var row = btn.closest('.exp-row');
  if (!row) return;
  var kind = btn.getAttribute('data-kind');
  if (row.classList.contains('is-current') && kind === 'end') return;
  var cal = row.querySelector('.exp-calendar');
  if (row.dataset.calKind === kind && !cal.hidden){
    cal.hidden = true;
    delete row.dataset.calKind;
    return;
  }
  row.dataset.calKind = kind;
  var hidden = row.querySelector(kind === 'start' ? '.exp-start' : '.exp-end');
  var sel = parseJDate(hidden.value);
  var t = todayJalali();
  row._calView = sel ? {jy: sel.jy, jm: sel.jm} : {jy: t.jy, jm: t.jm};
  cal.hidden = false;
  drawExpCalendar(row);
}

function drawExpCalendar(row){
  var cal = row.querySelector('.exp-calendar');
  var view = row._calView;
  if (!view) return;
  var kind = row.dataset.calKind;
  var hidden = row.querySelector(kind === 'start' ? '.exp-start' : '.exp-end');
  var sel = parseJDate(hidden.value);
  var len = jalaliMonthLength(view.jy, view.jm);
  var firstJdn = j2d(view.jy, view.jm, 1);
  var g = d2g(firstJdn);
  var firstDow = new Date(g.gy, g.gm-1, g.gd).getDay();
  var offset = (firstDow + 1) % 7;
  var today = todayJalali();
  var cells = '';
  for (var i=0;i<offset;i++) cells += '<span class="exp-cal-day is-empty"></span>';
  for (var dd=1; dd<=len; dd++){
    var isSel = sel && sel.jy===view.jy && sel.jm===view.jm && sel.jd===dd;
    var isToday = today && today.jy===view.jy && today.jm===view.jm && today.jd===dd;
    cells += '<button type="button" class="exp-cal-day' + (isSel?' is-selected':'') + (isToday?' is-today':'') + '" onclick="pickExpDay(this,' + dd + ')">' + faNum(dd) + '</button>';
  }
  var yearOpts = EXP_CAL_YEARS.map(function(y){
    return '<option value="' + y + '"' + (y===view.jy?' selected':'') + '>' + faNum(y) + '</option>';
  }).join('');
  var monthOpts = JALALI_MONTHS.map(function(m,i){
    return '<option value="' + (i+1) + '"' + ((i+1)===view.jm?' selected':'') + '>' + m + '</option>';
  }).join('');
  cal.innerHTML =
    '<div class="exp-cal-top">'
      + '<button type="button" class="exp-cal-nav" onclick="expCalNav(this,-1)" aria-label="ماه قبل">›</button>'
      + '<div class="exp-cal-selects">'
      + '<select class="exp-cal-month" onchange="expCalJump(this)">' + monthOpts + '</select>'
      + '<select class="exp-cal-year" onchange="expCalJump(this)">' + yearOpts + '</select>'
      + '</div>'
      + '<button type="button" class="exp-cal-nav" onclick="expCalNav(this,1)" aria-label="ماه بعد">‹</button>'
    + '</div>'
    + '<div class="exp-cal-week"><span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span></div>'
    + '<div class="exp-cal-grid">' + cells + '</div>'
    + '<div class="exp-cal-foot">'
      + '<button type="button" onclick="expCalToday(this)">امروز</button>'
      + '<button type="button" class="is-danger" onclick="expCalClear(this)">پاک کردن</button>'
    + '</div>';
}

function expCalJump(sel){
  var row = sel.closest('.exp-row');
  var view = row._calView;
  var cal = row.querySelector('.exp-calendar');
  var monthSel = cal.querySelector('.exp-cal-month');
  var yearSel = cal.querySelector('.exp-cal-year');
  view.jm = +monthSel.value;
  view.jy = +yearSel.value;
  drawExpCalendar(row);
}

function expCalNav(btn, dir){
  var row = btn.closest('.exp-row');
  var view = row._calView;
  view.jm += dir;
  if (view.jm > 12){ view.jm=1; view.jy++; }
  if (view.jm < 1){ view.jm=12; view.jy--; }
  if (view.jy < EXP_CAL_YEARS[0]) view.jy = EXP_CAL_YEARS[0];
  if (view.jy > EXP_CAL_YEARS[EXP_CAL_YEARS.length-1]) view.jy = EXP_CAL_YEARS[EXP_CAL_YEARS.length-1];
  drawExpCalendar(row);
}

function pickExpDay(btn, day){
  var row = btn.closest('.exp-row');
  var kind = row.dataset.calKind;
  var hidden = row.querySelector(kind === 'start' ? '.exp-start' : '.exp-end');
  var view = row._calView;
  hidden.value = view.jy + '-' + String(view.jm).padStart(2,'0') + '-' + String(day).padStart(2,'0');
  var cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  delete row.dataset.calKind;
  refreshExpRow(row);
}

function expCalToday(btn){
  var row = btn.closest('.exp-row');
  var kind = row.dataset.calKind;
  var hidden = row.querySelector(kind === 'start' ? '.exp-start' : '.exp-end');
  var t = todayJalali();
  hidden.value = t.jy + '-' + String(t.jm).padStart(2,'0') + '-' + String(t.jd).padStart(2,'0');
  var cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  delete row.dataset.calKind;
  refreshExpRow(row);
}

function expCalClear(btn){
  var row = btn.closest('.exp-row');
  var kind = row.dataset.calKind;
  var hidden = row.querySelector(kind === 'start' ? '.exp-start' : '.exp-end');
  hidden.value = '';
  var cal = row.querySelector('.exp-calendar');
  cal.hidden = true;
  delete row.dataset.calKind;
  refreshExpRow(row);
}

function onExpCurrentChange(cb){
  var row = cb.closest('.exp-row');
  refreshExpRow(row);
}

// Close any open calendar when clicking outside.
document.addEventListener('click', function(e){
  document.querySelectorAll('.exp-calendar').forEach(function(cal){
    if (cal.hidden) return;
    var row = cal.closest('.exp-row');
    if (row && !row.contains(e.target)){ cal.hidden = true; delete row.dataset.calKind; }
  });
});

function initExpRows(){ document.querySelectorAll('.exp-row').forEach(refreshExpRow); }
function initSingleDateRows(){ document.querySelectorAll('.edu-row, .cert-row').forEach(refreshSingleDateRow); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ initExpRows(); initSingleDateRows(); });
else { initExpRows(); initSingleDateRows(); }


// Drag-and-drop reordering for sections
(function initSectionDrag(){
  const container = document.getElementById('sections-container');
  if(!container) return;
  let dragged = null;
  container.addEventListener('dragstart', function(e){
    const handle = e.target.closest('.section-drag-handle');
    if(!handle) return;
    const item = handle.closest('.section-item');
    if(!item) return;
    dragged = item;
    item.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  container.addEventListener('dragend', function(){
    if(dragged) dragged.classList.remove('dragging');
    dragged = null;
    renumberSections();
  });
  container.addEventListener('dragover', function(e){
    if(!dragged) return;
    e.preventDefault();
    const item = e.target.closest('.section-item');
    if(!item || item === dragged) return;
    const rect = item.getBoundingClientRect();
    const mid = rect.top + rect.height / 2;
    if(e.clientY < mid){
      container.insertBefore(dragged, item);
    } else {
      container.insertBefore(dragged, item.nextSibling);
    }
  });
})();

// Single-section view: clicking a sidebar item shows only that section.
(function initSectionTabs(){
  const cards = Array.from(document.querySelectorAll('.grid > .card'));
  const links = Array.from(document.querySelectorAll('.side-nav .nav-link'));
  if (!cards.length) return;
  function show(id){
    if (!id || !document.getElementById(id)) return;
    cards.forEach(function(c){ c.classList.toggle('is-inactive', c.id !== id); });
    links.forEach(function(a){ a.classList.toggle('active', a.getAttribute('href') === '#' + id); });
     // keep active visible
     const activeLink = links.find(function(a){ return a.getAttribute('href') === '#' + id; });
     if (activeLink && window.matchMedia('(max-width:1040px)').matches) { const nav = activeLink.closest('.side-nav'); if (nav) { const lr = activeLink.getBoundingClientRect(); const nr = nav.getBoundingClientRect(); if (lr.left < nr.left || lr.right > nr.right) nav.scrollBy({ left: lr.left - nr.left - 12, behavior: 'smooth' }); } }
    if (location.hash !== '#' + id) {
      try { history.replaceState(null, '', '#' + id); } catch(e){}
    }
    const main = document.querySelector('.main');
    if (main) main.scrollTo({top:0});
  }
  links.forEach(function(a){
    a.addEventListener('click', function(e){
      const href = a.getAttribute('href') || '';
      if (href.charAt(0) !== '#') return;
      e.preventDefault();
      show(href.slice(1));
    });
  });
  // In-page links inside a panel card (e.g. the date-format shortcut) should
  // also switch to the target section instead of scrolling to a hidden block.
  document.querySelectorAll('.main a[href^="#"]').forEach(function(a){
    if (a.closest('.side-nav')) return;
    a.addEventListener('click', function(e){
      const id = (a.getAttribute('href') || '').slice(1);
      if (!id || !cards.some(function(c){ return c.id === id; })) return;
      e.preventDefault();
      show(id);
    });
  });
  const initial = (location.hash && document.getElementById(location.hash.slice(1)))
    ? location.hash.slice(1)
    : cards[0].id;
  show(initial);
  window.addEventListener('hashchange', function(){
    const id = (location.hash || '').slice(1);
    if (id && document.getElementById(id)) show(id);
  });
})();
// Live browser-tab preview for the branding section.
(function initBrandPreview(){
  var titleInput = document.getElementById('brandTitleInput');
  var logoInput = document.getElementById('brandLogoInput');
  var logoUpload = document.getElementById('brandLogoUpload');
  var clearLogo = document.querySelector('input[name="site_logo_clear"]');
  var previewTitle = document.getElementById('brandPreviewTitle');
  var previewIcon = document.getElementById('brandPreviewIcon');
  if (!titleInput || !previewTitle || !previewIcon) return;

  var fallbackTitle = <?= json_encode($brandFallbackTitle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var defaultLogo = <?= json_encode(admin_preview_url('image/logo.png'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var currentLogo = <?= json_encode($panelLogo !== '' ? admin_preview_url($panelLogo) : '', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  function previewPath(path){
    path = String(path || '').trim();
    if (!path) return '';
    if (/^(?:[a-z][a-z0-9+.-]*:)?\/\//i.test(path) || path.charAt(0) === '/') return path;
    return '../' + path.replace(/^\.?\//, '');
  }

  function setIcon(src){
    previewIcon.textContent = '';
    if (src) {
      var img = document.createElement('img');
      img.src = src;
      img.alt = '';
      previewIcon.appendChild(img);
    } else {
      previewIcon.textContent = 'DB';
    }
  }

  function updateTitle(){
    previewTitle.textContent = (titleInput.value || '').trim() || fallbackTitle;
  }

  function useCurrentLogo(){
    var manual = previewPath(logoInput && logoInput.value);
    setIcon(manual || currentLogo);
  }

  titleInput.addEventListener('input', updateTitle);
  if (logoInput) logoInput.addEventListener('input', function(){ if (!clearLogo || !clearLogo.checked) useCurrentLogo(); });
  if (clearLogo) clearLogo.addEventListener('change', function(){
    if (clearLogo.checked) {
      setIcon('');
    } else {
      useCurrentLogo();
    }
  });
  if (logoUpload) logoUpload.addEventListener('change', function(){
    var file = logoUpload.files && logoUpload.files[0];
    if (!file) return;
    var url = URL.createObjectURL(file);
    setIcon(url);
    if (clearLogo) clearLogo.checked = false;
  });
  updateTitle();
})();
// Live font preview for the font section.
(function initFontPreview(){
  var sel = document.getElementById('fontFamilySelect');
  var preview = document.getElementById('fontPreview');
  if (!sel || !preview) return;
  var samples = document.querySelectorAll('.font-sample');
  function apply(){ preview.style.fontFamily = (sel.value || 'system-ui') + ', sans-serif'; }
  function syncActive(){
    samples.forEach(function(b){ b.classList.toggle('is-active', b.getAttribute('data-font') === sel.value); });
  }
  sel.addEventListener('change', function(){ apply(); syncActive(); });
  samples.forEach(function(b){
    b.addEventListener('click', function(){
      sel.value = b.getAttribute('data-font') || sel.value;
      sel.dispatchEvent(new Event('change', {bubbles:true}));
    });
  });
  apply();
  syncActive();
})();

// Live Persian (Jalali) date + clock in the panel topbar.
(function initPanelClock(){
  var timeEl = document.getElementById('panelClockTime');
  var dateEl = document.getElementById('panelClockDate');
  if (!timeEl || !dateEl) return;
  var timeFmt = null, dateFmt = null;
  try {
    timeFmt = new Intl.DateTimeFormat('fa-IR', {hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false});
    dateFmt = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});
  } catch (e) {}
  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function toFa(v){ return String(v).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); }
  function jalaliDate(now){
    // Chromium's fa-IR order can vary, so compose «دوشنبه ۲۵ آبان ۱۴۰۵» manually.
    if (!dateFmt) return toFa(now.toLocaleDateString());
    var out = {};
    dateFmt.formatToParts(now).forEach(function(p){ out[p.type] = p.value; });
    return [out.weekday, out.day, out.month, out.year].filter(Boolean).join(' ');
  }
  function tick(){
    var now = new Date();
    timeEl.textContent = timeFmt ? timeFmt.format(now) : toFa(pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()));
    dateEl.textContent = jalaliDate(now);
  }
  tick();
  // Tick on the second boundary so the seconds never appear to skip.
  window.setTimeout(function(){ tick(); window.setInterval(tick, 1000); }, 1000 - (Date.now() % 1000));
})();

// Profile photo frame picker: keeps the highlighted option in sync even in
// browsers that do not support :has().
(function initPhotoShape(){
  var picker = document.querySelector('.shape-picker');
  if (!picker) return;
  var options = Array.prototype.slice.call(picker.querySelectorAll('.shape-option'));
  function sync(){
    options.forEach(function(opt){
      var input = opt.querySelector('input');
      opt.classList.toggle('is-active', !!(input && input.checked));
    });
  }
  options.forEach(function(opt){
    var input = opt.querySelector('input');
    if (input) input.addEventListener('change', sync);
  });
  sync();
})();

// Theme preset picker: selecting a swatch sets the bg_color input value.
(function initThemePreset(){
  var picker = document.querySelector('.theme-presets');
  if (!picker) return;
  var bgInput = document.getElementById('bg_color');
  var options = Array.prototype.slice.call(picker.querySelectorAll('.theme-swatch'));
  function sync(){
    options.forEach(function(opt){
      var input = opt.querySelector('input');
      opt.classList.toggle('is-active', !!(input && input.checked));
    });
  }
  options.forEach(function(opt){
    var input = opt.querySelector('input');
    if (input) input.addEventListener('change', function(){
      if (bgInput && input.dataset.color) bgInput.value = input.dataset.color;
      sync();
    });
  });
  var hexEl = document.getElementById('bg_color_hex');
  function updateHex(){ if (hexEl && bgInput) hexEl.textContent = bgInput.value; }
  if (bgInput) bgInput.addEventListener('input', function(){
    // Clear preset selection when a custom color is typed.
    options.forEach(function(opt){ var inp = opt.querySelector('input'); if (inp) inp.checked = false; });
    sync();
    updateHex();
  });
  // Swatch change also updates hex.
  options.forEach(function(opt){
    var input = opt.querySelector('input');
    if (input) input.addEventListener('change', function(){ if (bgInput && input.dataset.color) { bgInput.value = input.dataset.color; if (hexEl) hexEl.textContent = input.dataset.color; } });
  });
  updateHex();
  sync();
})();
</script>
</body>
</html>
