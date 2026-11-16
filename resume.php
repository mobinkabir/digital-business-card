<?php
declare(strict_types=1);

/**
 * Serves the uploaded resume file as an attachment.
 * The file itself lives in data/resume (not web-served); metadata is in card.json.
 */

$cardFile = __DIR__ . '/data/card.json';
if (!is_file($cardFile)) {
    http_response_code(404);
    exit('Not found');
}
$card = json_decode((string)file_get_contents($cardFile), true);
if (!is_array($card) || empty($card['resume']) || !is_array($card['resume'])) {
    http_response_code(404);
    exit('Not found');
}
$resume = $card['resume'];
$file = isset($resume['file']) && is_string($resume['file']) ? basename($resume['file']) : '';
if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+\.(pdf|doc|docx)$/i', $file)) {
    http_response_code(404);
    exit('Not found');
}
$path = __DIR__ . '/data/resume/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

// Increment download counter in views.json (best-effort).
$viewsFile = __DIR__ . '/data/views.json';
$views = [];
if (is_file($viewsFile)) {
    $decoded = json_decode((string)file_get_contents($viewsFile), true);
    if (is_array($decoded)) $views = $decoded;
}
$views['resumeDownloads'] = max(0, (int)($views['resumeDownloads'] ?? 0)) + 1;
$views['updated_at'] = date('c');
@file_put_contents($viewsFile, json_encode($views, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';
$original = isset($resume['original_name']) && is_string($resume['original_name']) ? $resume['original_name'] : ('resume.' . $ext);
// ASCII fallback + RFC 5987 filename* for unicode names.
$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $original) ?: ('resume.' . $ext);
$utf8 = rawurlencode($original);

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . $utf8);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
