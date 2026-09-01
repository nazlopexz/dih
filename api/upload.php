<?php
header('Content-Type: application/json');
require __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($ip)) {
    http_response_code(429);
    echo json_encode(['error' => "You're uploading too fast. Wait a bit and try again."]);
    exit;
}

$title = trim($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';

if ($content === '') {
    echo json_encode(['error' => "Paste can't be empty."]);
    exit;
}
if (mb_strlen($content) > MAX_PASTE_CHARS) {
    echo json_encode(['error' => 'Paste is too long (10,000 character limit).']);
    exit;
}
if (strpos($content, "\0") !== false) {
    echo json_encode(['error' => 'Invalid content.']);
    exit;
}
// don't let anyone try to sneak server-executable tags into a "text" paste
if (preg_match('/<\?php|<\?=|<%/i', $content)) {
    echo json_encode(['error' => "That content isn't allowed."]);
    exit;
}

if ($title === '') {
    $title = 'untitled';
}
if (!is_valid_title($title)) {
    echo json_encode(['error' => 'Title must be 1-80 characters, letters/numbers/spaces/-/_ only.']);
    exit;
}

$slug = slugify($title);
if ($slug === '') {
    $slug = 'paste' . substr(bin2hex(random_bytes(3)), 0, 6);
}

$meta = load_meta();
$base = $slug;
$i = 2;
while (isset($meta[$slug]) || file_exists(PASTE_DIR . "/$slug.txt") || file_exists(PINNED_DIR . "/$slug.txt")) {
    $slug = $base . $i;
    $i++;
}

if (file_put_contents(PASTE_DIR . "/$slug.txt", $content, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save paste. Try again.']);
    exit;
}

$meta[$slug] = [
    'title' => $title,
    'date' => date('Y-m-d H:i'),
    'views' => 0,
    'author' => 'Anonymous',
    'comments' => [],
];
save_meta($meta);

echo json_encode(['success' => true, 'slug' => $slug]);
