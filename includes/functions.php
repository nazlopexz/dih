<?php
// shared helpers for the paste backend — meta storage, slugs, rate limiting

define('DATA_DIR', __DIR__ . '/../data');
define('DATA_FILE', DATA_DIR . '/meta.json');
define('RATE_FILE', DATA_DIR . '/ratelimit.json');
define('PASTE_DIR', __DIR__ . '/../pastes');
define('PINNED_DIR', __DIR__ . '/../pinned');

const MAX_PASTE_CHARS = 10000;
const MAX_TITLE_CHARS = 80;
const MAX_COMMENT_CHARS = 2000;

function load_meta(): array {
    if (!file_exists(DATA_FILE)) return [];
    $raw = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_meta(array $data): void {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// title -> url-safe slug, e.g. "How To Make A Cake" -> "howtomakeacake"
function slugify(string $title): string {
    $slug = strtolower(trim($title));
    return preg_replace('/[^a-z0-9]/', '', $slug);
}

function is_valid_title(string $title): bool {
    return $title !== '' && mb_strlen($title) <= MAX_TITLE_CHARS && preg_match('/^[A-Za-z0-9 _\-]+$/', $title);
}

// checks pinned/ first (developer moves pastes there manually), then pastes/
function find_paste_file(string $slug) {
    $slug = basename($slug); // no path traversal
    $pinnedPath = PINNED_DIR . "/$slug.txt";
    $normalPath = PASTE_DIR . "/$slug.txt";
    if (file_exists($pinnedPath)) return ['dir' => 'pinned', 'path' => $pinnedPath];
    if (file_exists($normalPath)) return ['dir' => 'pastes', 'path' => $normalPath];
    return null;
}

// basic per-IP throttle so one person can't spam the upload endpoint
function check_rate_limit(string $ip, int $max = 5, int $windowSeconds = 600): bool {
    $log = [];
    if (file_exists(RATE_FILE)) {
        $log = json_decode(file_get_contents(RATE_FILE), true) ?: [];
    }
    $now = time();
    $recent = array_values(array_filter($log[$ip] ?? [], fn($t) => $now - $t < $windowSeconds));
    if (count($recent) >= $max) return false;
    $recent[] = $now;
    $log[$ip] = $recent;
    file_put_contents(RATE_FILE, json_encode($log), LOCK_EX);
    return true;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
