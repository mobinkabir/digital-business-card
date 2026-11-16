<?php
// Privacy-friendly view counter for the digital business card.
// POST or GET ?hit=1 increments; plain GET returns the current count.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$file = __DIR__ . '/data/views.json';
$lock = fopen($file . '.lock', 'c');
if ($lock) flock($lock, LOCK_EX);

$data = ['total' => 0, 'last' => null];
if (is_file($file)) {
    $decoded = json_decode((string)file_get_contents($file), true);
    if (is_array($decoded)) {
        $data['total'] = (int)($decoded['total'] ?? 0);
        $data['last'] = $decoded['last'] ?? null;
    }
}

$hit = ($_SERVER['REQUEST_METHOD'] === 'POST') || isset($_GET['hit']);
if ($hit) {
    $data['total']++;
    $data['last'] = date('c');
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if ($lock) { flock($lock, LOCK_UN); fclose($lock); }

echo json_encode(['total' => $data['total']], JSON_UNESCAPED_UNICODE);
