(function () {
  const sideToggle = document.getElementById('sideToggle');
  const sidePanel = document.getElementById('sidePanel');
  const commentInput = document.getElementById('commentInput');
  const commentCount = document.getElementById('commentCount');
  const commentBtn = document.getElementById('commentBtn');
  const commentStatus = document.getElementById('commentStatus');
  const commentsList = document.getElementById('commentsList');
  const slug = document.body.dataset.slug;

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

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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

        const div = document.createElement('div');
        div.className = 'comment';
        div.innerHTML = `
          <div class="who"><span>${escapeHtml(data.comment.author)}</span><span>${escapeHtml(data.comment.date)}</span></div>
          <div class="body-text">${escapeHtml(data.comment.text)}</div>
        `;
        commentsList.appendChild(div);

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
})();
