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
  const res = await apiGet('../api/auth/csrf');
  csrfToken = res.csrf_token || null;
}

async function logout() {
  await apiPost('../api/auth/logout', {});
  window.location.href = '../pages/login.html';
}

function statusBadge(status) {
  const s = String(status || '');
  if (s === 'Approved' || s === 'Cleared')
    return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(52,211,153,.15);color:#34d399;">\u2705 '+s+'</span>';
  if (s === 'Rejected')
    return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(248,113,113,.15);color:#f87171;">\u274C Rejected</span>';
  if (s === 'InProgress')
    return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(99,102,241,.15);color:#818cf8;">\u21BB In Progress</span>';
  return '<span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(251,191,36,.15);color:#fbbf24;">\u23F3 '+( s || 'Pending')+'</span>';
}
