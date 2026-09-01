(function () {
  const slug = decodeURIComponent(window.location.pathname.replace(/^\//, ''));

  const pasteView = document.getElementById('pasteView');
  const notFound = document.getElementById('notFound');
  const contentView = document.getElementById('contentView');
  const titleDisplay = document.getElementById('titleDisplay');
  const authorDisplay = document.getElementById('authorDisplay');
  const dateDisplay = document.getElementById('dateDisplay');
  const viewsDisplay = document.getElementById('viewsDisplay');
  const commentsList = document.getElementById('commentsList');
  const sideToggle = document.getElementById('sideToggle');
  const sidePanel = document.getElementById('sidePanel');
  const commentInput = document.getElementById('commentInput');
  const commentCount = document.getElementById('commentCount');
  const commentBtn = document.getElementById('commentBtn');
  const commentStatus = document.getElementById('commentStatus');

  const MAX_COMMENT = 2000;

  sideToggle.addEventListener('click', () => {
    sidePanel.classList.toggle('closed');
  });

  commentInput.addEventListener('input', () => {
    const len = commentInput.value.length;
    commentCount.textContent = `${len.toLocaleString()} / ${MAX_COMMENT.toLocaleString()}`;
    commentCount.classList.toggle('warn', len > MAX_COMMENT);
  });

  function showStatus(msg, isError) {
    commentStatus.textContent = msg;
    commentStatus.className = 'status-msg show ' + (isError ? 'error' : 'ok');
  }

  function renderComment(c) {
    const div = document.createElement('div');
    div.className = 'comment';

    const who = document.createElement('div');
    who.className = 'who';
    const author = document.createElement('span');
    author.textContent = c.author;
    const date = document.createElement('span');
    date.textContent = c.date;
    who.appendChild(author);
    who.appendChild(date);

    const body = document.createElement('div');
    body.className = 'body-text';
    body.textContent = c.text;

    div.appendChild(who);
    div.appendChild(body);
    return div;
  }

  function renderComments(comments) {
    commentsList.innerHTML = '';
    if (!comments.length) {
      const p = document.createElement('p');
      p.style.color = 'var(--muted)';
      p.style.fontSize = '12.5px';
      p.textContent = 'No comments yet.';
      commentsList.appendChild(p);
      return;
    }
    comments.forEach(c => commentsList.appendChild(renderComment(c)));
  }

  async function loadPaste() {
    if (!slug) {
      pasteView.style.display = 'none';
      notFound.style.display = 'flex';
      return;
    }

    try {
      const res = await fetch('api/paste.php?slug=' + encodeURIComponent(slug));
      const data = await res.json();

      if (data.error) {
        pasteView.style.display = 'none';
        notFound.style.display = 'flex';
        return;
      }

      document.title = data.title + ' — dihbin.lol';
      contentView.textContent = data.content;
      titleDisplay.textContent = data.title;
      if (data.pinned) {
        const tag = document.createElement('span');
        tag.className = 'pin-tag';
        tag.textContent = 'Pinned';
        titleDisplay.appendChild(document.createTextNode(' '));
        titleDisplay.appendChild(tag);
      }
      authorDisplay.textContent = data.author;
      dateDisplay.textContent = data.date;
      viewsDisplay.textContent = data.views;
      renderComments(data.comments);
    } catch (err) {
      pasteView.style.display = 'none';
      notFound.style.display = 'flex';
    }
  }

  commentBtn.addEventListener('click', async () => {
    const text = commentInput.value.trim();
    if (!text) {
      showStatus("Comment can't be empty.", true);
      return;
    }
    if (text.length > MAX_COMMENT) {
      showStatus('Comment is too long (2,000 character limit).', true);
      return;
    }

    commentBtn.disabled = true;
    commentBtn.textContent = 'Posting...';

    try {
      const body = new URLSearchParams({ slug, comment: text });
      const res = await fetch('api/comment.php', { method: 'POST', body });
      const data = await res.json();

      if (data.error) {
        showStatus(data.error, true);
      } else {
        const emptyMsg = commentsList.querySelector('p');
        if (emptyMsg) emptyMsg.remove();
        commentsList.appendChild(renderComment(data.comment));
        commentInput.value = '';
        commentCount.textContent = `0 / ${MAX_COMMENT.toLocaleString()}`;
        showStatus('Comment posted.', false);
      }
    } catch (err) {
      showStatus('Something went wrong. Try again.', true);
    } finally {
      commentBtn.disabled = false;
      commentBtn.textContent = 'Post comment';
    }
  });

  loadPaste();
})();
