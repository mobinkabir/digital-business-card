<?php
declare(strict_types=1);

/**
 * Standalone card build: the panel has a single page (card.php), so /admin/
 * simply forwards there. auth.php redirects to login.php when not signed in.
 */
require_once __DIR__ . '/auth.php';

header('Location: card.php');
exit;
