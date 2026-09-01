<?php
require __DIR__ . '/includes/functions.php';

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

function paste_url(string $slug): string {
    return '/' . rawurlencode($slug);
}

function pagination_url(int $page, string $q, string $field, int $perPage): string {
    return '?' . http_build_query(['q' => $q, 'field' => $field, 'per_page' => $perPage, 'page' => $page]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>dihbin.lol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="field">
  <div class="blob blob-c"></div>
  <div class="blob blob-a"></div>
  <div class="blob blob-b"></div>
</div>
<div class="grain"></div>

<nav class="topnav">
  <a class="brand" href="index2.php">DIHBIN.LOL</a>
  <div class="nav-links">
    <a href="index2.php">Home</a>
    <a href="upload.html">Add Paste</a>
    <a href="users.html">Users</a>
    <a href="tos.html">Terms of Service</a>
  </div>
</nav>

<main style="position:relative; z-index:3;">

  <div class="hero">
    <span class="eggplant">🍆</span>
    <div class="sub">dihbin.lol</div>
  </div>

  <form class="board search-board" method="get" action="index2.php">
    <div class="field-group" style="flex:2 1 260px;">
      <label for="q">Search</label>
      <input type="search" id="q" name="q" placeholder="search pastes..." value="<?= e($q) ?>">
    </div>
    <div class="field-group" style="flex:1 1 140px;">
      <label for="field">Search in</label>
      <select id="field" name="field">
        <option value="title" <?= $field === 'title' ? 'selected' : '' ?>>Title</option>
        <option value="content" <?= $field === 'content' ? 'selected' : '' ?>>Paste content</option>
      </select>
    </div>
    <div class="field-group" style="flex:1 1 120px;">
      <label for="per_page">Per page</label>
      <select id="per_page" name="per_page">
        <?php foreach ($perPageOptions as $opt): ?>
          <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group" style="flex:0 0 auto; justify-content:flex-end;">
      <label>&nbsp;</label>
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </form>

  <div class="table-wrap">
    <div class="table-heading">Pinned Pastes</div>
    <table class="pastes">
      <thead>
        <tr><th>Title</th><th>Comments</th><th>Views</th><th>Author</th><th>Added</th></tr>
      </thead>
      <tbody>
        <?php if (!$pinnedRows): ?>
          <tr class="empty-row"><td colspan="5">No pinned pastes yet.</td></tr>
        <?php else: foreach ($pinnedRows as $row): ?>
          <tr onclick="window.location='<?= e(paste_url($row['slug'])) ?>'">
            <td>
              <a class="title-link" href="<?= e(paste_url($row['slug'])) ?>"><?= e($row['title']) ?></a>
              <span class="pin-tag">Pinned</span>
            </td>
            <td><?= count($row['comments'] ?? []) ?></td>
            <td><?= (int)($row['views'] ?? 0) ?></td>
            <td><?= e($row['author'] ?? 'Anonymous') ?></td>
            <td><?= e($row['date']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="table-wrap">
    <div class="table-heading">Recent Pastes</div>
    <table class="pastes">
      <thead>
        <tr><th>Title</th><th>Comments</th><th>Views</th><th>Author</th><th>Added</th></tr>
      </thead>
      <tbody>
        <?php if (!$regularPageRows): ?>
          <tr class="empty-row"><td colspan="5">No pastes found.</td></tr>
        <?php else: foreach ($regularPageRows as $row): ?>
          <tr onclick="window.location='<?= e(paste_url($row['slug'])) ?>'">
            <td><a class="title-link" href="<?= e(paste_url($row['slug'])) ?>"><?= e($row['title']) ?></a></td>
            <td><?= count($row['comments'] ?? []) ?></td>
            <td><?= (int)($row['views'] ?? 0) ?></td>
            <td><?= e($row['author'] ?? 'Anonymous') ?></td>
            <td><?= e($row['date']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p === $page): ?>
            <span class="current"><?= $p ?></span>
          <?php else: ?>
            <a href="<?= e(pagination_url($p, $q, $field, $perPage)) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<script src="assets/blobs.js"></script>
</body>
</html>
