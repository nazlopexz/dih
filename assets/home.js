(function () {
  const form = document.getElementById('searchForm');
  const qInput = document.getElementById('q');
  const fieldSelect = document.getElementById('field');
  const perPageSelect = document.getElementById('per_page');
  const pinnedBody = document.getElementById('pinnedBody');
  const regularBody = document.getElementById('regularBody');
  const pagination = document.getElementById('pagination');

  function paramsFromUrl() {
    const p = new URLSearchParams(window.location.search);
    return {
      q: p.get('q') || '',
      field: p.get('field') || 'title',
      per_page: p.get('per_page') || '10',
      page: p.get('page') || '1',
    };
  }

  function applyParamsToForm(params) {
    qInput.value = params.q;
    fieldSelect.value = params.field;
    perPageSelect.value = params.per_page;
  }

  function pasteUrl(slug) {
    return '/' + encodeURIComponent(slug);
  }

  function buildRow(row) {
    const tr = document.createElement('tr');
    tr.addEventListener('click', () => { window.location.href = pasteUrl(row.slug); });

    const titleTd = document.createElement('td');
    const a = document.createElement('a');
    a.className = 'title-link';
    a.href = pasteUrl(row.slug);
    a.textContent = row.title;
    titleTd.appendChild(a);
    if (row.pinned) {
      const tag = document.createElement('span');
      tag.className = 'pin-tag';
      tag.textContent = 'Pinned';
      titleTd.appendChild(tag);
    }
    tr.appendChild(titleTd);

    [row.comments, row.views, row.author, row.date].forEach(val => {
      const td = document.createElement('td');
      td.textContent = val;
      tr.appendChild(td);
    });

    return tr;
  }

  function renderTable(tbody, rows, emptyMsg) {
    tbody.innerHTML = '';
    if (!rows.length) {
      const tr = document.createElement('tr');
      tr.className = 'empty-row';
      const td = document.createElement('td');
      td.colSpan = 5;
      td.textContent = emptyMsg;
      tr.appendChild(td);
      tbody.appendChild(tr);
      return;
    }
    rows.forEach(row => tbody.appendChild(buildRow(row)));
  }

  function renderPagination(page, totalPages, params) {
    pagination.innerHTML = '';
    if (totalPages <= 1) return;
    for (let p = 1; p <= totalPages; p++) {
      if (p === page) {
        const span = document.createElement('span');
        span.className = 'current';
        span.textContent = p;
        pagination.appendChild(span);
      } else {
        const a = document.createElement('a');
        a.textContent = p;
        a.href = '#';
        a.addEventListener('click', (e) => {
          e.preventDefault();
          loadPastes({ ...params, page: p });
        });
        pagination.appendChild(a);
      }
    }
  }

  async function loadPastes(params) {
    const query = new URLSearchParams(params).toString();
    history.replaceState(null, '', '?' + query);

    pinnedBody.innerHTML = '<tr class="empty-row"><td colspan="5">Loading...</td></tr>';
    regularBody.innerHTML = '<tr class="empty-row"><td colspan="5">Loading...</td></tr>';

    try {
      const res = await fetch('api/pastes.php?' + query);
      const data = await res.json();

      renderTable(pinnedBody, data.pinned, 'No pinned pastes yet.');
      renderTable(regularBody, data.regular, 'No pastes found.');
      renderPagination(data.page, data.total_pages, params);
    } catch (err) {
      pinnedBody.innerHTML = '<tr class="empty-row"><td colspan="5">Could not load pastes.</td></tr>';
      regularBody.innerHTML = '';
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    loadPastes({
      q: qInput.value.trim(),
      field: fieldSelect.value,
      per_page: perPageSelect.value,
      page: '1',
    });
  });

  const initial = paramsFromUrl();
  applyParamsToForm(initial);
  loadPastes(initial);
})();
