<?php
header('Content-Type: application/json');
require __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($ip, 15, 600)) {
    http_response_code(429);
    echo json_encode(['error' => "You're commenting too fast. Slow down."]);
    exit;
}

$slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $_POST['slug'] ?? '');
$text = trim($_POST['comment'] ?? '');

if ($slug === '' || !find_paste_file($slug)) {
    echo json_encode(['error' => 'Paste not found.']);
    exit;
}
if ($text === '') {
    echo json_encode(['error' => "Comment can't be empty."]);
    exit;
}
if (mb_strlen($text) > MAX_COMMENT_CHARS) {
    echo json_encode(['error' => 'Comment is too long (2,000 character limit).']);
    exit;
}

$meta = load_meta();
if (!isset($meta[$slug])) {
    echo json_encode(['error' => 'Paste not found.']);
    exit;
}

$comment = [
    'author' => 'Anonymous',
    'date' => date('Y-m-d H:i'),
    'text' => $text,
];

$meta[$slug]['comments'][] = $comment;
save_meta($meta);

echo json_encode(['success' => true, 'comment' => $comment]);
