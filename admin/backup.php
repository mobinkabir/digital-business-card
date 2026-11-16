<?php
require_once __DIR__ . '/auth.php';

$root = realpath(__DIR__ . '/..');
$backupFile = sys_get_temp_dir() . '/card-backup-' . bin2hex(random_bytes(8)) . '.tar.gz';

try {
    $archive = new PharData($backupFile);
    $cardFile = $root . '/data/card.json';
    if (is_file($cardFile)) {
        $archive->addFile($cardFile, 'card.json');
    }
    $uploadsDir = $root . '/data/uploads';
    if (is_dir($uploadsDir)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iter as $f) {
            if (!$f->isFile()) continue;
            $rel = 'uploads/' . str_replace('\\', '/', $iter->getSubPathname());
            $archive->addFile($f->getPathname(), $rel);
        }
    }
    unset($archive);
} catch (\Throwable $e) {
    http_response_code(500);
    exit('backup failed: ' . $e->getMessage());
}

if (!is_file($backupFile)) {
    http_response_code(500);
    exit('backup archive could not be created');
}

$downloadName = 'card-backup-' . date('Ymd-His') . '.tar.gz';
header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($backupFile));
readfile($backupFile);
@unlink($backupFile);
exit;
