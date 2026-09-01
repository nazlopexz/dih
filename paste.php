<?php
session_start();
require __DIR__ . '/includes/functions.php';

$slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $_GET['slug'] ?? '');
$meta = load_meta();
$found = $slug ? find_paste_file($slug) : null;

if (!$found || !isset($meta[$slug])) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Not found — dihbin.lol</title>
      <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
      <div class="center-page">
        <div class="board" style="text-align:center;">
          <h1 style="font-family:'Bebas Neue',sans-serif; font-size:32px; margin-bottom:10px;">404</h1>
          <p style="color:var(--muted);">That paste doesn't exist.</p>
          <p style="margin-top:16px;"><a class="btn" href="index2.php">Back home</a></p>
        </div>
      </div>
    </body>
    </html>
    <?php
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
$content = file_get_contents($found['path']);
$comments = $entry['comments'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($entry['title']) ?> — dihbin.lol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body data-slug="<?= e($slug) ?>">

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

<button class="side-toggle" id="sideToggle" title="toggle panel">☰</button>

<div class="editor-shell">
  <div class="editor-main" id="editorMain">
    <pre class="content-view"><?= e($content) ?></pre>
  </div>

  <aside class="side-panel" id="sidePanel">
    <a class="side-panel-header" href="index2.php">DIHBIN.LOL</a>

    <div>
      <div class="field-group"><label>Title</label></div>
      <div style="font-size:15px; font-weight:500;"><?= e($entry['title']) ?><?= $found['dir'] === 'pinned' ? ' <span class="pin-tag">Pinned</span>' : '' ?></div>
    </div>

    <div class="meta-line"><span>Author</span><span><?= e($entry['author'] ?? 'Anonymous') ?></span></div>
    <div class="meta-line"><span>Added</span><span><?= e($entry['date']) ?></span></div>
    <div class="meta-line"><span>Views</span><span><?= (int)$entry['views'] ?></span></div>

    <div style="border-top:1px solid var(--border); padding-top:16px;">
      <div class="table-heading" style="font-size:15px; margin-bottom:10px;">Comments</div>

      <div class="comments" id="commentsList">
        <?php if (!$comments): ?>
          <p style="color:var(--muted); font-size:12.5px;">No comments yet.</p>
        <?php else: foreach ($comments as $c): ?>
          <div class="comment">
            <div class="who"><span><?= e($c['author']) ?></span><span><?= e($c['date']) ?></span></div>
            <div class="body-text"><?= e($c['text']) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="field-group" style="margin-top:14px;">
        <label for="commentInput">Add a comment</label>
        <textarea id="commentInput" class="comment-input" maxlength="2000" placeholder="say something..."></textarea>
      </div>
      <div class="char-count" id="commentCount">0 / 2,000</div>
      <button class="btn btn-primary" id="commentBtn" style="margin-top:8px; width:100%;">Post comment</button>
      <div class="status-msg" id="commentStatus"></div>
    </div>
  </aside>
</div>

<script src="assets/blobs.js"></script>
<script src="assets/paste.js"></script>
</body>
</html>
