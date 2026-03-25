let csrfToken = null;

async function apiGet(path) {
  const res = await fetch(path, {
    method: 'GET',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Request failed: ${res.status}`);
  return data;
}

async function apiPost(path, payload = {}) {
  const headers = { 'Content-Type': 'application/json' };
  if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

  const res = await fetch(path, {
    method: 'POST',
    credentials: 'same-origin',
    headers,
    body: JSON.stringify(payload ?? {}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Request failed: ${res.status}`);
  return data;
}

async function loadCsrfToken() {
  const res = await apiGet('/api/auth/csrf');
  csrfToken = res.csrf_token || null;
}

async function logout() {
  await apiPost('/api/auth/logout', {});
  window.location.href = '/pages/login.html';
}

function statusBadge(status) {
  const s = String(status || '');
  if (s === 'Approved') return '<span class="badge bg-success">Approved</span>';
  if (s === 'Rejected') return '<span class="badge bg-danger">Rejected</span>';
  return '<span class="badge bg-warning text-dark">Pending</span>';
}

