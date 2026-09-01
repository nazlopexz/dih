<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../includes/functions.php';

$slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['slug'] ?? '');
$meta = load_meta();
$found = $slug ? find_paste_file($slug) : null;

if (!$found || !isset($meta[$slug])) {
    http_response_code(404);
    echo json_encode(['error' => 'Paste not found.']);
    exit;
}

// count a view once per browser session per paste, not on every refresh
$viewedKey = 'viewed_' . $slug;
if (empty($_SESSION[$viewedKey])) {
    $meta[$slug]['views'] = ($meta[$slug]['views'] ?? 0) + 1;
    save_meta($meta);
    $_SESSION[$viewedKey] = true;
}

$entry = $meta[$slug];

echo json_encode([
    'slug' => $slug,
    'title' => $entry['title'],
    'date' => $entry['date'],
    'views' => (int)$entry['views'],
    'author' => $entry['author'] ?? 'Anonymous',
    'pinned' => $found['dir'] === 'pinned',
    'content' => file_get_contents($found['path']),
    'comments' => $entry['comments'] ?? [],
]);
