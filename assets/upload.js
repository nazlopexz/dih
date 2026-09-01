(function () {
  const textarea = document.getElementById('pasteContent');
  const titleInput = document.getElementById('pasteTitle');
  const contentCount = document.getElementById('contentCount');
  const titleCount = document.getElementById('titleCount');
  const uploadBtn = document.getElementById('uploadBtn');
  const statusMsg = document.getElementById('statusMsg');
  const sideToggle = document.getElementById('sideToggle');
  const sidePanel = document.getElementById('sidePanel');
  const editorMain = document.getElementById('editorMain');

  const MAX_CONTENT = 10000;
  const MAX_TITLE = 80;
  // matches the server-side rule in includes/functions.php — keep in sync
  const TITLE_PATTERN = /^[A-Za-z0-9 _-]*$/;

  function updateCounts() {
    const len = textarea.value.length;
    contentCount.textContent = `${len.toLocaleString()} / ${MAX_CONTENT.toLocaleString()}`;
    contentCount.classList.toggle('warn', len > MAX_CONTENT);

    const tlen = titleInput.value.length;
    titleCount.textContent = `${tlen} / ${MAX_TITLE}`;
    titleCount.classList.toggle('warn', tlen > MAX_TITLE);
  }

  textarea.addEventListener('input', updateCounts);
  titleInput.addEventListener('input', () => {
    // strip anything that isn't letters/numbers/space/-/_ as they type
    titleInput.value = titleInput.value.replace(/[^A-Za-z0-9 _-]/g, '');
    updateCounts();
  });

  updateCounts();

  sideToggle.addEventListener('click', () => {
    sidePanel.classList.toggle('closed');
    editorMain.style.marginRight = sidePanel.classList.contains('closed') ? '0' : '0';
  });

  function showStatus(msg, isError) {
    statusMsg.textContent = msg;
    statusMsg.className = 'status-msg show ' + (isError ? 'error' : 'ok');
  }

  uploadBtn.addEventListener('click', async () => {
    const content = textarea.value;
    const title = titleInput.value.trim();

    if (!content.trim()) {
      showStatus("Paste can't be empty.", true);
      return;
    }
    if (content.length > MAX_CONTENT) {
      showStatus('Paste is too long (10,000 character limit).', true);
      return;
    }
    if (title && !TITLE_PATTERN.test(title)) {
      showStatus('Title can only contain letters, numbers, spaces, - and _.', true);
      return;
    }
    if (title.length > MAX_TITLE) {
      showStatus('Title is too long (80 character limit).', true);
      return;
    }

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    try {
      const body = new URLSearchParams({ title, content });
      const res = await fetch('api/upload.php', { method: 'POST', body });
      const data = await res.json();

      if (data.error) {
        showStatus(data.error, true);
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload';
        return;
      }

      showStatus('Uploaded! Redirecting...', false);
      window.location.href = '/' + data.slug;
    } catch (err) {
      showStatus('Something went wrong. Try again.', true);
      uploadBtn.disabled = false;
      uploadBtn.textContent = 'Upload';
    }
  });
})();
