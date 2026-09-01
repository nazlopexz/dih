<?php
header('Content-Type: application/json');
require __DIR__ . '/../includes/functions.php';

$meta = load_meta();

$q = trim($_GET['q'] ?? '');
$field = ($_GET['field'] ?? 'title') === 'content' ? 'content' : 'title';
$perPageOptions = [10, 25, 50];
$perPage = in_array((int)($_GET['per_page'] ?? 10), $perPageOptions, true) ? (int)$_GET['per_page'] : 10;
$page = max(1, (int)($_GET['page'] ?? 1));

function gather_rows(string $dir, array $meta, bool $pinned): array {
    $rows = [];
    foreach (glob($dir . '/*.txt') as $file) {
        $slug = basename($file, '.txt');
        if (!isset($meta[$slug])) continue;
        $rows[] = $meta[$slug] + ['slug' => $slug, 'pinned' => $pinned];
    }
    return $rows;
}

function row_matches(array $row, string $q, string $field): bool {
    if ($q === '') return true;
    $needle = mb_strtolower($q);
    if ($field === 'title') {
        return str_contains(mb_strtolower($row['title']), $needle);
    }
    $dir = $row['pinned'] ? PINNED_DIR : PASTE_DIR;
    $path = $dir . '/' . $row['slug'] . '.txt';
    $content = file_exists($path) ? file_get_contents($path) : '';
    return str_contains(mb_strtolower($content), $needle);
}

function strip_row(array $r): array {
    return [
        'slug' => $r['slug'],
        'title' => $r['title'],
        'comments' => count($r['comments'] ?? []),
        'views' => (int)($r['views'] ?? 0),
        'author' => $r['author'] ?? 'Anonymous',
        'date' => $r['date'],
        'pinned' => $r['pinned'],
    ];
}

$pinnedRows = gather_rows(PINNED_DIR, $meta, true);
$regularRows = gather_rows(PASTE_DIR, $meta, false);

if ($q !== '') {
    $pinnedRows = array_values(array_filter($pinnedRows, fn($r) => row_matches($r, $q, $field)));
    $regularRows = array_values(array_filter($regularRows, fn($r) => row_matches($r, $q, $field)));
}

usort($pinnedRows, fn($a, $b) => strcmp($b['date'], $a['date']));
usort($regularRows, fn($a, $b) => strcmp($b['date'], $a['date']));

$totalRegular = count($regularRows);
$totalPages = max(1, (int)ceil($totalRegular / $perPage));
$page = min($page, $totalPages);
$regularPageRows = array_slice($regularRows, ($page - 1) * $perPage, $perPage);

echo json_encode([
    'pinned' => array_map('strip_row', $pinnedRows),
    'regular' => array_map('strip_row', $regularPageRows),
    'page' => $page,
    'total_pages' => $totalPages,
]);
