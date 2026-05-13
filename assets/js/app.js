/**
 * APIForge — Main App JavaScript
 * PHP CI3 Edition
 */
const App = (() => {

  const BASE = window.AppConfig?.baseUrl || '/abdullahpostman/';
  const INDEX = window.AppConfig?.indexPage || '';
  let currentReqId = null;
  let tabCounter = 100;

  // ─── AJAX HELPER ───────────────────────────────────────────
  async function ajax(url, method = 'GET', data = null) {
    const opts = { method, headers: {} };
    if (data) {
      const fd = new FormData();
      Object.entries(data).forEach(([k, v]) => fd.append(k, v ?? ''));
      opts.body = fd;
    }
    // Support both mod_rewrite ON (clean URLs) and OFF (index.php in URL)
    const fullUrl = BASE + (INDEX ? INDEX + '/' : '') + url;
    try {
      const res = await fetch(fullUrl, opts);
      if (!res.ok && res.status === 404 && !INDEX) {
        // Fallback: try with index.php
        const res2 = await fetch(BASE + 'index.php/' + url, opts);
        return res2.json();
      }
      return res.json();
    } catch(e) {
      throw e;
    }
  }

  // ─── TOAST ─────────────────────────────────────────────────
  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    document.getElementById('toastIcon').textContent = icons[type] || '✅';
    document.getElementById('toastMsg').textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2500);
  }

  // ─── LOADING BAR ───────────────────────────────────────────
  let loadingEl = null;
  function showLoading() {
    if (!loadingEl) {
      loadingEl = document.createElement('div');
      loadingEl.className = 'loading-bar';
      document.body.prepend(loadingEl);
    }
    loadingEl.style.width = '0%';
    loadingEl.style.transition = 'none';
    setTimeout(() => {
      loadingEl.style.transition = 'width 1.5s ease';
      loadingEl.style.width = '80%';
    }, 10);
  }

  function hideLoading() {
    if (!loadingEl) return;
    loadingEl.style.width = '100%';
    setTimeout(() => { loadingEl.style.width = '0'; }, 400);
  }

  // ─── METHOD COLOR ──────────────────────────────────────────
  function updateMethodColor() {
    const sel = document.getElementById('methodSelect');
    const colors = { GET: '#61affe', POST: '#49cc90', PUT: '#fca130', DELETE: '#f93e3e', PATCH: '#50e3c2', HEAD: '#8b8fa8', OPTIONS: '#8b8fa8' };
    sel.style.color = colors[sel.value] || '#8b8fa8';
    // Update active tab
    const activeTab = document.querySelector('.tab.active .tab-method');
    if (activeTab) {
      activeTab.textContent = sel.value;
      activeTab.className = 'tab-method method-' + sel.value.toLowerCase();
    }
  }

  // ─── COLLECTION TOGGLE ─────────────────────────────────────
  function toggleCollection(header) {
    const arrow = header.querySelector('.coll-arrow');
    const list = header.nextElementSibling;
    arrow.classList.toggle('open');
    list.classList.toggle('open');
  }

  // ─── LOAD REQUEST ──────────────────────────────────────────
  async function loadRequest(id) {
    showLoading();
    try {
      const data = await ajax('requests/load/' + id);
      if (data.error) { showToast(data.error, 'error'); return; }

      currentReqId = id;
      document.getElementById('urlInput').value = data.url || '';
      document.getElementById('methodSelect').value = data.method || 'GET';
      updateMethodColor();

      // Headers
      const headers = data.headers ? JSON.parse(data.headers) : [];
      rebuildTable('headersBody', headers, true);
      document.getElementById('headerCount').textContent = headers.filter(h => h.key).length;

      // Params
      const params = data.params ? JSON.parse(data.params) : [];
      rebuildTable('paramsBody', params);
      document.getElementById('paramCount').textContent = params.filter(p => p.key).length;

      // Body
      if (data.body_type && data.body_type !== 'none') {
        document.querySelector(`input[name="bodyType"][value="${data.body_type}"]`).checked = true;
        toggleBodyEditor();
        document.getElementById('bodyEditor').value = data.body_content || '';
      }

      // Auth
      if (data.auth_type) {
        document.getElementById('authType').value = data.auth_type;
        const authData = data.auth_data ? JSON.parse(data.auth_data) : {};
        if (data.auth_type === 'bearer') document.getElementById('bearerToken').value = authData.token || '';
        if (data.auth_type === 'basic') {
          document.getElementById('basicUser').value = authData.username || '';
          document.getElementById('basicPass').value = authData.password || '';
        }
        if (data.auth_type === 'api-key') {
          document.getElementById('apiKeyName').value = authData.key || 'X-API-Key';
          document.getElementById('apiKeyValue').value = authData.value || '';
        }
        toggleAuthFields();
      }

      // Scripts
      if (document.getElementById('preScriptEditor')) document.getElementById('preScriptEditor').value = data.pre_request_script || '';
      if (document.getElementById('testsEditor')) document.getElementById('testsEditor').value = data.test_script || '';

      // Sidebar active
      document.querySelectorAll('.request-item').forEach(i => i.classList.remove('active'));
      const item = document.querySelector(`.request-item[data-id="${id}"]`);
      if (item) item.classList.add('active');

      // Update tab label
      const activeTab = document.querySelector('.tab.active .tab-label');
      if (activeTab) activeTab.textContent = data.name || 'Request';

      showToast('📂 ' + (data.name || 'Request') + ' loaded');
    } catch (e) {
      showToast('Failed to load request', 'error');
    }
    hideLoading();
  }

  // ─── REBUILD TABLE (params/headers) ────────────────────────
  function rebuildTable(tbodyId, rows, isHeaders = false) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.innerHTML = '';
    const data = rows.length ? rows : [{}];
    data.forEach(row => {
      tbody.appendChild(makeTableRow(tbodyId, row.key || '', row.value || '', row.description || '', row.enabled !== false));
    });
    // Always add empty row at end
    if (rows.length) tbody.appendChild(makeTableRow(tbodyId, '', '', '', false));
  }

  function makeTableRow(tbodyId, key = '', value = '', desc = '', enabled = true) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="param-checkbox" ${enabled ? 'checked' : ''}></td>
      <td><input class="param-input" value="${escHtml(key)}" placeholder="Key" oninput="App.updateBadgeCount('${tbodyId}')"></td>
      <td><input class="param-input" value="${escHtml(value)}" placeholder="Value"></td>
      <td><input class="param-input" value="${escHtml(desc)}" placeholder="Description"></td>
      <td><button class="del-row-btn" onclick="this.closest('tr').remove();App.updateBadgeCount('${tbodyId}')">×</button></td>
    `;
    return tr;
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function addParamRow() {
    const tbody = document.getElementById('paramsBody');
    tbody.appendChild(makeTableRow('paramsBody'));
  }

  function addHeaderRow() {
    const tbody = document.getElementById('headersBody');
    tbody.appendChild(makeTableRow('headersBody'));
  }

	function addFormRow() {
	  const tbody = document.getElementById('formDataBody');
	  tbody.appendChild(makeFormRow());
	}
	
	function makeFormRow(key = '', value = '', type = 'text', enabled = true, desc = '') {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="checkbox" class="param-checkbox" ${enabled ? 'checked' : ''}></td>
    <td><input class="param-input" value="${escHtml(key)}" placeholder="Key"></td>
    <td>
      <select class="form-type-select" onchange="App.toggleFormRowType(this)" style="
        background:var(--bg-main);
        color:var(--text-secondary);
        border:1px solid var(--border);
        border-radius:5px;
        padding:3px 6px;
        font-size:11px;
        cursor:pointer;
        margin-right:4px;
      ">
        <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
        <option value="file" ${type === 'file' ? 'selected' : ''}>File</option>
      </select>
    </td>
    <td class="form-value-cell">
      ${type === 'file'
        ? `<input type="file" class="form-file-input" multiple style="font-size:11px;color:var(--text-secondary);cursor:pointer">`
        : `<input class="param-input form-text-input" value="${escHtml(value)}" placeholder="Value">`
      }
    </td>
    <td><input class="param-input" value="${escHtml(desc)}" placeholder="Description"></td>
    <td><button class="del-row-btn" onclick="this.closest('tr').remove()">×</button></td>
  `;
  return tr;
}

function toggleFormRowType(select) {
  const td = select.closest('tr').querySelector('.form-value-cell');
  const type = select.value;
  if (type === 'file') {
    td.innerHTML = `<input type="file" class="form-file-input" multiple style="font-size:11px;color:var(--text-secondary);cursor:pointer">`;
  } else {
    td.innerHTML = `<input class="param-input form-text-input" placeholder="Value">`;
  }
}

function collectFormData() {
  const rows = [];
  let hasFiles = false;
 
  document.querySelectorAll('#formDataBody tr').forEach(tr => {
    const cb       = tr.querySelector('.param-checkbox');
    const keyInput = tr.querySelectorAll('.param-input, .form-text-input')[0];
    // key input is always first .param-input in the row
    const keyEl    = tr.querySelector('td:nth-child(2) input');
    const typeEl   = tr.querySelector('.form-type-select');
    const fileEl   = tr.querySelector('.form-file-input');
    const textEl   = tr.querySelector('.form-text-input');
 
    if (!keyEl || !keyEl.value.trim()) return;
    if (cb && !cb.checked) return;
 
    const key  = keyEl.value.trim();
    const type = typeEl ? typeEl.value : 'text';
 
    if (type === 'file' && fileEl && fileEl.files.length > 0) {
      hasFiles = true;
      rows.push({ key, type: 'file', files: fileEl.files });
    } else if (type === 'text') {
      rows.push({ key, type: 'text', value: textEl ? textEl.value : '' });
    }
  });
 
  return { hasFiles, rows };
}




  function updateBadgeCount(tbodyId) {
    const rows = document.querySelectorAll(`#${tbodyId} .param-input`);
    const filled = [...rows].filter((r, i) => i % 4 === 0 && r.value.trim()).length;
    if (tbodyId === 'paramsBody') document.getElementById('paramCount').textContent = filled;
    if (tbodyId === 'headersBody') document.getElementById('headerCount').textContent = filled;
  }

  // ─── COLLECT TABLE DATA ────────────────────────────────────
  function collectTable(tbodyId) {
    const rows = [];
    document.querySelectorAll(`#${tbodyId} tr`).forEach(tr => {
      const inputs = tr.querySelectorAll('.param-input');
      const cb = tr.querySelector('.param-checkbox');
      if (inputs[0] && inputs[0].value.trim()) {
        rows.push({ key: inputs[0].value, value: inputs[1]?.value || '', description: inputs[2]?.value || '', enabled: cb?.checked !== false });
      }
    });
    return rows;
  }

  // ─── SEND REQUEST ──────────────────────────────────────────
 async function sendRequest() {
  const url = document.getElementById('urlInput').value.trim();
  if (!url) { showToast('Please enter a URL', 'warning'); return; }
 
  const btn = document.getElementById('sendBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Sending...';
  showLoading();
 
  document.getElementById('noRespMsg').classList.remove('panel-hidden');
  document.getElementById('responseContent').classList.add('panel-hidden');
  document.getElementById('statusPill').classList.add('panel-hidden');
  document.getElementById('respMeta').classList.add('panel-hidden');
 
  const method   = document.getElementById('methodSelect').value;
  const headers  = collectTable('headersBody');
  const params   = collectTable('paramsBody');
  const bodyType = document.querySelector('input[name="bodyType"]:checked')?.value || 'none';
  const authType = document.getElementById('authType')?.value || 'none';
 
  let authData = {};
  if (authType === 'bearer') authData = { token: document.getElementById('bearerToken')?.value };
  if (authType === 'basic')  authData = { username: document.getElementById('basicUser')?.value, password: document.getElementById('basicPass')?.value };
  if (authType === 'api-key') authData = { key: document.getElementById('apiKeyName')?.value, value: document.getElementById('apiKeyValue')?.value, in: document.getElementById('apiKeyIn')?.value };
 
  try {
    let result;
 
    // ── FILE UPLOAD path ──────────────────────────────────────
    if (bodyType === 'form-data') {
      const { hasFiles, rows } = collectFormData();
 
      if (hasFiles) {
        // Use real multipart FormData so files actually upload
        const fd = new FormData();
        fd.append('method',    method);
        fd.append('url',       url);
        fd.append('headers',   JSON.stringify(headers));
        fd.append('params',    JSON.stringify(params));
        fd.append('body_type', 'form-data');
        fd.append('auth_type', authType);
        fd.append('auth_data', JSON.stringify(authData));
        fd.append('request_id', currentReqId || '');
 
        // Append text fields
        const textRows = rows.filter(r => r.type === 'text');
        fd.append('form_text_rows', JSON.stringify(textRows));
 
        // Append files
        rows.filter(r => r.type === 'file').forEach(r => {
          for (let i = 0; i < r.files.length; i++) {
            fd.append(r.key, r.files[i], r.files[i].name);
          }
        });
 
        const fullUrl = BASE + (INDEX ? INDEX + '/' : '') + 'api/send';
        const res = await fetch(fullUrl, { method: 'POST', body: fd });
        result = await res.json();
      } else {
        // No files — normal JSON path
        const textRows = rows.map(r => ({ key: r.key, value: r.value, enabled: true }));
        result = await ajax('api/send', 'POST', {
          method, url,
          headers:    JSON.stringify(headers),
          params:     JSON.stringify(params),
          body_type:  bodyType,
          body:       JSON.stringify(textRows),
          auth_type:  authType,
          auth_data:  JSON.stringify(authData),
          request_id: currentReqId || '',
        });
      }
 
    // ── Normal path (raw / urlencoded / none) ─────────────────
    } else {
      let bodyContent = '';
      if (bodyType === 'raw') {
        bodyContent = document.getElementById('bodyEditor')?.value || '';
      } else if (bodyType === 'x-www-form-urlencoded') {
        const formRows = collectTable('formDataBody');
        bodyContent = JSON.stringify(formRows);
      }
 
      result = await ajax('api/send', 'POST', {
        method, url,
        headers:    JSON.stringify(headers),
        params:     JSON.stringify(params),
        body_type:  bodyType,
        body:       bodyContent,
        auth_type:  authType,
        auth_data:  JSON.stringify(authData),
        request_id: currentReqId || '',
      });
    }
 
    hideLoading();
    btn.disabled = false;
    btn.innerHTML = '▶ Send';
 
    if (!result.success && !result.status) {
      showToast('Request failed: ' + (result.error || 'Unknown error'), 'error');
      return;
    }
    renderResponse(result);
 
  } catch (e) {
    hideLoading();
    btn.disabled = false;
    btn.innerHTML = '▶ Send';
    showToast('Network error: ' + e.message, 'error');
  }
}

  function renderResponse(result) {
    const pill = document.getElementById('statusPill');
    const code = result.status;

    pill.className = 'status-pill ' + (code >= 200 && code < 300 ? 'success' : code >= 400 ? 'error' : 'warning');
    document.getElementById('statusCode').textContent = code + ' ' + (result.status_text || '');
    document.getElementById('respTime').textContent = result.time || 0;
    document.getElementById('respSize').textContent = result.size || '0 B';

    pill.classList.remove('panel-hidden');
    document.getElementById('respMeta').classList.remove('panel-hidden');

    // Body
    const body = result.body || '';
    const content = document.getElementById('responseContent');
    document.getElementById('noRespMsg').classList.add('panel-hidden');
    content.classList.remove('panel-hidden');

    if (result.content_type && result.content_type.includes('json')) {
      try {
        const parsed = JSON.parse(body);
        content.innerHTML = syntaxHighlight(JSON.stringify(parsed, null, 2));
      } catch {
        content.textContent = body;
      }
    } else {
      content.textContent = body;
    }

    // Response headers
    const respHB = document.getElementById('responseHeadersBody');
    respHB.innerHTML = '';
    const hdrs = result.headers || {};
    document.getElementById('respHeaderCount').textContent = Object.keys(hdrs).length;
    Object.entries(hdrs).forEach(([k, v]) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td style="padding:6px 10px;border:1px solid var(--border);font-family:'JetBrains Mono',monospace;font-size:11.5px">${escHtml(k)}</td><td style="padding:6px 10px;border:1px solid var(--border);color:var(--accent2);font-family:'JetBrains Mono',monospace;font-size:11.5px">${escHtml(v)}</td>`;
      respHB.appendChild(tr);
    });

    // Run inline test scripts
    runTestScripts(result);

    // Add to history panel
    addToHistoryPanel(result);

    showToast('✅ ' + result.status + ' ' + result.status_text + ' in ' + result.time + 'ms');
  }

  // ─── SYNTAX HIGHLIGHT ──────────────────────────────────────
  function syntaxHighlight(json) {
    return json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
        let cls = 'json-num';
        if (/^"/.test(match)) cls = /:$/.test(match) ? 'json-key' : 'json-str';
        else if (/true|false/.test(match)) cls = 'json-bool';
        else if (/null/.test(match)) cls = 'json-null';
        return `<span class="${cls}">${match}</span>`;
      });
  }

  // ─── INLINE TEST SCRIPTS ───────────────────────────────────
  function runTestScripts(result) {
    const script = document.getElementById('testsEditor')?.value || '';
    const list = document.getElementById('testResultsList');
    if (!list) return;
    list.innerHTML = '';
    if (!script.trim()) return;

    const tests = [];
    const pm = {
      test: (name, fn) => {
        try { fn(); tests.push({ name, pass: true }); }
        catch (e) { tests.push({ name, pass: false, msg: e.message }); }
      },
      response: {
        to: { have: { status: (s) => { if (result.status !== s) throw new Error(`Expected ${s}, got ${result.status}`); } } },
        json: () => JSON.parse(result.body),
        code: result.status,
        time: result.time,
      },
      expect: (val) => ({
        to: {
          equal: (exp) => { if (val !== exp) throw new Error(`Expected ${exp}, got ${val}`); },
          be: { an: (type) => { if (typeof val !== type && !Array.isArray(val)) throw new Error(`Expected ${type}`); } },
          have: { status: (s) => { if (result.status !== s) throw new Error(`Expected ${s}`); } },
        }
      }),
    };

    try { new Function('pm', script)(pm); } catch(e) {}

    tests.forEach(t => {
      const div = document.createElement('div');
      div.style.cssText = `display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;border-left:3px solid ${t.pass ? 'var(--success)' : 'var(--error)'};background:${t.pass ? 'rgba(0,212,170,0.08)' : 'rgba(249,62,62,0.08)'}`;
      div.innerHTML = `<span style="color:${t.pass ? 'var(--success)' : 'var(--error)'}">${t.pass ? '✓' : '✗'}</span><span>${escHtml(t.name)}${t.msg ? ` <span style="color:var(--text-muted)">(${escHtml(t.msg)})</span>` : ''}</span>`;
      list.appendChild(div);
    });
  }

  // ─── ADD TO HISTORY PANEL ──────────────────────────────────
  function addToHistoryPanel(result) {
    const list = document.getElementById('historyList');
    if (!list) return;
    const url = document.getElementById('urlInput').value;
    const method = document.getElementById('methodSelect').value;
    const div = document.createElement('div');
    div.className = 'history-item';
    div.innerHTML = `
      <span class="hist-method method-${method.toLowerCase()}">${method}</span>
      <div style="flex:1;overflow:hidden">
        <div class="hist-url">${escHtml(url.replace(/^https?:\/\/[^\/]+/, ''))}</div>
        <div style="font-size:9px;color:var(--text-muted)">${new Date().toLocaleTimeString()} · ${result.status} · ${result.time}ms</div>
      </div>`;
    list.prepend(div);
  }

  // ─── TABS ──────────────────────────────────────────────────
  function switchTab(tabId) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    const tab = document.getElementById(tabId);
    if (tab) {
      tab.classList.add('active');
      const reqId = tab.dataset.reqId;
      if (reqId) loadRequest(reqId);
    }
  }

  function newRequestTab() {
    const id = 'tab-new-' + (++tabCounter);
    const bar = document.getElementById('tabsBar');
    const plus = bar.querySelector('.tab-new');
    const tab = document.createElement('div');
    tab.className = 'tab';
    tab.id = id;
    tab.dataset.reqId = '';
    tab.innerHTML = `<span class="tab-method method-get">GET</span><span class="tab-label">New Request</span><span class="tab-close" onclick="App.closeTab(event,'${id}')">×</span>`;
    tab.onclick = (e) => { if (!e.target.classList.contains('tab-close')) switchTab(id); };
    bar.insertBefore(tab, plus);
    switchTab(id);
    document.getElementById('urlInput').value = '';
    document.getElementById('methodSelect').value = 'GET';
    updateMethodColor();
    currentReqId = null;
    document.querySelectorAll('.request-item').forEach(i => i.classList.remove('active'));
  }

  function closeTab(e, tabId) {
    e.stopPropagation();
    const tabs = document.querySelectorAll('.tab');
    if (tabs.length <= 1) { showToast('Cannot close last tab', 'warning'); return; }
    const tab = document.getElementById(tabId);
    const wasActive = tab.classList.contains('active');
    tab.remove();
    if (wasActive) {
      const remaining = document.querySelectorAll('.tab');
      if (remaining.length) { remaining[remaining.length - 1].click(); }
    }
  }

  // ─── REQUEST TABS (Params/Headers etc) ────────────────────
  function switchReqTab(el, panelId) {
    document.querySelectorAll('.req-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    ['params','auth','headers','body','preScript','tests','settings'].forEach(id => {
      const p = document.getElementById('panel-' + id);
      if (p) p.classList.toggle('panel-hidden', id !== panelId);
    });
  }

  // ─── RESPONSE TABS ─────────────────────────────────────────
  function switchRespTab(el, panelId) {
    document.querySelectorAll('.resp-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    ['resp-body','resp-headers','resp-cookies','resp-testResults'].forEach(id => {
      const p = document.getElementById(id);
      if (p) p.classList.toggle('panel-hidden', id !== panelId);
    });
  }

  // ─── AUTH FIELDS ──────────────────────────────────────────
  function toggleAuthFields() {
    const type = document.getElementById('authType')?.value || 'none';
    ['bearer','basic','api-key'].forEach(t => {
      const el = document.getElementById('authFields-' + t);
      if (el) el.classList.toggle('panel-hidden', t !== type);
    });
  }

  // ─── BODY EDITOR ──────────────────────────────────────────
  function toggleBodyEditor() {
    const type = document.querySelector('input[name="bodyType"]:checked')?.value || 'none';
    document.getElementById('bodyEditorWrap')?.classList.toggle('panel-hidden', type !== 'raw');
    document.getElementById('formDataWrap')?.classList.toggle('panel-hidden', !['form-data','x-www-form-urlencoded'].includes(type));
    document.getElementById('rawBodyFormat')?.classList.toggle('panel-hidden', type !== 'raw');
  }

  // ─── SAVE REQUEST ─────────────────────────────────────────
  function saveRequest() {
    const url = document.getElementById('urlInput').value;
    const method = document.getElementById('methodSelect').value;
    if (!url) { showToast('Enter a URL first', 'warning'); return; }
    document.getElementById('saveReqName').value = method + ' ' + (url.split('/').pop() || 'Request');
    openModal('modal-save');
  }

  async function doSaveRequest() {
    const name = document.getElementById('saveReqName').value || 'Untitled';
    const collId = document.getElementById('saveReqCollection').value;
    const headers = collectTable('headersBody');
    const params  = collectTable('paramsBody');
    const bodyType = document.querySelector('input[name="bodyType"]:checked')?.value || 'none';
    const authType = document.getElementById('authType')?.value || 'none';

    const result = await ajax('requests/save', 'POST', {
      id: currentReqId || '',
      collection_id: collId,
      name,
      method: document.getElementById('methodSelect').value,
      url: document.getElementById('urlInput').value,
      headers: JSON.stringify(headers),
      params: JSON.stringify(params),
      body_type: bodyType,
      body_content: document.getElementById('bodyEditor')?.value || '',
      auth_type: authType,
      pre_request_script: document.getElementById('preScriptEditor')?.value || '',
      test_script: document.getElementById('testsEditor')?.value || '',
    });

    if (result.success) {
      currentReqId = result.id;
      closeModal('modal-save');
      showToast('💾 Request saved!');
      // Update sidebar
      setTimeout(() => location.reload(), 800);
    } else {
      showToast('Failed to save', 'error');
    }
  }

  // ─── COLLECTION ───────────────────────────────────────────
  function newCollectionModal() { openModal('modal-collection'); }

  async function createCollection() {
    const name = document.getElementById('newCollName').value.trim();
    const icon = document.getElementById('newCollIcon').value;
    if (!name) { showToast('Enter collection name', 'warning'); return; }

    const result = await ajax('collections/store', 'POST', { name, icon });
    if (result.success) {
      closeModal('modal-collection');
      showToast('📁 Collection created!');
      // Add to sidebar
      const list = document.getElementById('collectionsList');
      const div = document.createElement('div');
      div.className = 'collection-item';
      div.dataset.id = result.id;
      div.innerHTML = `
        <div class="collection-header" onclick="App.toggleCollection(this)">
          <span class="coll-arrow open">▶</span>
          <span class="coll-icon">${icon}</span>
          <span class="coll-name">${escHtml(name)}</span>
          <span class="coll-count">0</span>
        </div>
        <div class="request-list open"></div>`;
      list.prepend(div);
      document.getElementById('newCollName').value = '';
    }
  }

  async function collectionMenu(e, id) {
    e.stopPropagation();
    if (confirm('Delete this collection and all its requests?')) {
      await ajax('collections/delete/' + id, 'POST');
      document.querySelector(`.collection-item[data-id="${id}"]`)?.remove();
      showToast('🗑 Collection deleted');
    }
  }

  // ─── REQUEST MENU ─────────────────────────────────────────
  async function requestMenu(e, id) {
    e.stopPropagation();
    const action = prompt('Action: (d)uplicate, (x)delete');
    if (action === 'd') {
      const r = await ajax('requests/duplicate/' + id, 'POST');
      if (r.success) { showToast('📋 Duplicated!'); setTimeout(() => location.reload(), 500); }
    } else if (action === 'x') {
      if (confirm('Delete this request?')) {
        await ajax('requests/delete/' + id, 'POST');
        document.querySelector(`.request-item[data-id="${id}"]`)?.remove();
        showToast('🗑 Deleted');
      }
    }
  }

  // ─── ENVIRONMENT ──────────────────────────────────────────
  async function switchEnvironment(id) {
    const r = await ajax('environments/set_active/' + id, 'POST');
    if (r.success) {
      const envName = document.getElementById('envSelector').options[document.getElementById('envSelector').selectedIndex].text;
      document.getElementById('currentEnvLabel').textContent = 'ENV: ' + envName;
      // Update vars list
      const varsList = document.getElementById('envVarsList');
      if (varsList) {
        varsList.innerHTML = (r.vars || []).map(v =>
          `<div class="var-row"><span class="var-key">{{${escHtml(v.var_key)}}}</span><span class="var-val">${escHtml((v.var_value||'').substring(0,20))}${(v.var_value||'').length > 20 ? '…' : ''}</span></div>`
        ).join('');
      }
      showToast('🌍 Environment switched');
    }
  }

  async function editEnvironment() {
    const envId = document.getElementById('envSelector').value;
    const r = await ajax('environments/get/' + envId);
    const tbody = document.getElementById('envVarsEditor');
    tbody.innerHTML = '';
    (r.vars || []).forEach(v => {
      tbody.appendChild(makeEnvRow(v.var_key, v.var_value, v.is_enabled));
    });
    tbody.appendChild(makeEnvRow('', '', 1));
    openModal('modal-env');
  }

  function makeEnvRow(key = '', value = '', enabled = 1) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="checkbox" class="param-checkbox" ${enabled ? 'checked' : ''}></td>
      <td><input class="param-input" value="${escHtml(key)}" placeholder="VARIABLE_NAME"></td>
      <td><input class="param-input" value="${escHtml(value)}" placeholder="value"></td>
      <td><button class="del-row-btn" onclick="this.closest('tr').remove()">×</button></td>`;
    return tr;
  }

  function addEnvVar() {
    document.getElementById('envVarsEditor').appendChild(makeEnvRow());
  }

  async function saveEnvironment() {
    const envId = document.getElementById('envSelector').value;
    const vars = [];
    document.querySelectorAll('#envVarsEditor tr').forEach(tr => {
      const inputs = tr.querySelectorAll('.param-input');
      const cb = tr.querySelector('.param-checkbox');
      if (inputs[0]?.value.trim()) {
        vars.push({ key: inputs[0].value, value: inputs[1]?.value || '', enabled: cb?.checked ? 1 : 0 });
      }
    });
    const r = await ajax('environments/save', 'POST', { id: envId, vars: JSON.stringify(vars) });
    if (r.success) {
      closeModal('modal-env');
      showToast('🌍 Environment saved!');
      switchEnvironment(envId);
    }
  }

  // ─── HISTORY ──────────────────────────────────────────────
  async function loadFromHistory(id) {
    const r = await ajax('history/load/' + id);
    if (r) {
      document.getElementById('urlInput').value = r.url;
      document.getElementById('methodSelect').value = r.method;
      updateMethodColor();
      if (r.response_body) {
        renderResponse({
          status: r.response_status,
          status_text: '',
          time: r.response_time,
          size: r.response_size + ' B',
          body: r.response_body,
          headers: r.response_headers ? JSON.parse(r.response_headers) : {},
          content_type: 'application/json',
        });
      }
      showToast('🕐 History loaded');
    }
  }

  async function clearHistory() {
    if (!confirm('Clear all history?')) return;
    await ajax('history/clear', 'POST');
    document.getElementById('historyList').innerHTML = '';
    showToast('🗑 History cleared');
  }

  // ─── RESPONSE ACTIONS ─────────────────────────────────────
  function copyResponse() {
    const text = document.getElementById('responseContent')?.innerText || '';
    navigator.clipboard.writeText(text).then(() => showToast('📋 Copied!'));
  }

  function beautifyResponse() {
    const el = document.getElementById('responseContent');
    if (!el) return;
    try {
      const parsed = JSON.parse(el.innerText);
      el.innerHTML = syntaxHighlight(JSON.stringify(parsed, null, 2));
      showToast('✨ Beautified!');
    } catch { showToast('Not valid JSON', 'error'); }
  }

  function saveResponseToFile() {
    const body = document.getElementById('responseContent')?.innerText || '';
    const blob = new Blob([body], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'response_' + Date.now() + '.json';
    a.click();
    showToast('💾 Saved!');
  }

  function clearResponse() {
    document.getElementById('responseContent').classList.add('panel-hidden');
    document.getElementById('noRespMsg').classList.remove('panel-hidden');
    document.getElementById('statusPill').classList.add('panel-hidden');
    document.getElementById('respMeta').classList.add('panel-hidden');
    showToast('🗑 Cleared');
  }

  // ─── SEARCH ───────────────────────────────────────────────
  function searchRequests(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.request-item').forEach(item => {
      const name = item.querySelector('.req-name')?.textContent.toLowerCase() || '';
      item.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('.collection-item').forEach(col => {
      const visible = [...col.querySelectorAll('.request-item')].some(i => i.style.display !== 'none');
      col.style.display = visible || !q ? '' : 'none';
    });
  }

  // ─── MODALS ───────────────────────────────────────────────
  function openModal(id) { document.getElementById(id)?.classList.remove('panel-hidden'); }
  function closeModal(id) { document.getElementById(id)?.classList.add('panel-hidden'); }

  // ─── MISC ─────────────────────────────────────────────────
  // ─── WORKSPACES ───────────────────────────────────────────
  async function showWorkspaces() {
    const workspaces = await ajax('workspaces');
    const list = workspaces.map(ws => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px;background:var(--bg-main)">
        <div>
          <div style="font-weight:600;font-size:13px;color:var(--text-primary)">🗂️ ${escHtml(ws.name)}</div>
          ${ws.description ? `<div style="font-size:11px;color:var(--text-muted);margin-top:2px">${escHtml(ws.description)}</div>` : ''}
        </div>
        <button onclick="deleteWorkspace(${ws.id})" style="background:none;border:none;color:var(--error);cursor:pointer;font-size:16px" title="Delete">🗑</button>
      </div>`).join('') || '<div style="color:var(--text-muted);font-size:12px;text-align:center;padding:20px">No workspaces yet</div>';
    document.getElementById('workspaceList').innerHTML = list;
    openModal('modal-workspaces');
  }

  async function createWorkspace() {
    const name = document.getElementById('wsName').value.trim();
    const desc = document.getElementById('wsDesc').value.trim();
    if (!name) { showToast('Enter workspace name', 'warning'); return; }
    const r = await ajax('workspaces/store', 'POST', { name, description: desc });
    if (r.success) {
      showToast('🗂️ Workspace created!');
      document.getElementById('wsName').value = '';
      document.getElementById('wsDesc').value = '';
      showWorkspaces();
    }
  }

  async function deleteWorkspace(id) {
    if (!confirm('Delete this workspace? This will delete all its collections!')) return;
    await ajax('workspaces/delete/' + id, 'POST');
    showToast('🗑 Workspace deleted');
    showWorkspaces();
    setTimeout(() => location.reload(), 800);
  }

  // ─── IMPORT ────────────────────────────────────────────────
function importModal() {
  // Reset all tabs to default
  document.querySelectorAll('.import-tab-btn').forEach((btn, i) => {
    btn.style.borderBottomColor = i === 0 ? 'var(--accent)' : 'transparent';
    btn.style.color = i === 0 ? 'var(--accent)' : 'var(--text-muted)';
  });
  document.querySelectorAll('.import-tab-panel').forEach((p, i) => {
    p.classList.toggle('panel-hidden', i !== 0);
  });
  // Reset file btn label
  const btn = document.getElementById('importFileBtn');
  if (btn) btn.style.display = '';
  openModal('modal-import');
}

  async function doImport() {
  const fileInput = document.getElementById('importFile');
  const file = fileInput.files[0];
  if (!file) { showToast('Select a file first', 'warning'); return; }
  try {
    const text = await file.text();
    // Try JSON first, then YAML (basic)
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      showToast('Only JSON format supported for file import', 'error');
      return;
    }
    await parseAndImportCollection(json);
  } catch(e) {
    showToast('Invalid file: ' + e.message, 'error');
  }
}

function switchImportTab(el, tabId) {
  document.querySelectorAll('.import-tab-btn').forEach(b => {
    b.style.borderBottomColor = 'transparent';
    b.style.color = 'var(--text-muted)';
    b.style.fontWeight = '';
  });
  el.style.borderBottomColor = 'var(--accent)';
  el.style.color = 'var(--accent)';
  el.style.fontWeight = '600';
 
  document.querySelectorAll('.import-tab-panel').forEach(p => p.classList.add('panel-hidden'));
  document.getElementById(tabId)?.classList.remove('panel-hidden');
 
  // Show/hide Import File button — only on file tab
  const fileBtn = document.getElementById('importFileBtn');
  if (fileBtn) fileBtn.style.display = tabId === 'tab-file' ? '' : 'none';
}
 
// Drag & Drop handlers
function importDragOver(e) {
  e.preventDefault();
  const zone = document.getElementById('importDropZone');
  if (zone) {
    zone.style.borderColor = 'var(--accent)';
    zone.style.background = 'rgba(99,102,241,0.07)';
  }
}
 
function importDragLeave(e) {
  const zone = document.getElementById('importDropZone');
  if (zone) {
    zone.style.borderColor = 'var(--border)';
    zone.style.background = 'var(--bg-main)';
  }
}
 
function importDrop(e) {
  e.preventDefault();
  importDragLeave(e);
  const file = e.dataTransfer.files[0];
  if (!file) return;
  document.getElementById('importFileName').textContent = file.name;
  // Inject into file input
  const dt = new DataTransfer();
  dt.items.add(file);
  document.getElementById('importFile').files = dt.files;
  showToast('📄 File ready: ' + file.name);
}
 
function onImportFileSelect(input) {
  const name = input.files[0]?.name || '';
  document.getElementById('importFileName').textContent = name;
}
 
// ── Import from URL ───────────────────────────────────────────
function importFromUrl() {
  const url = document.getElementById('urlImportInput')?.value.trim();
  if (!url) { showToast('Enter a URL', 'warning'); return; }
 
  // Just open it as a new request tab
  document.getElementById('urlInput').value = url;
  document.getElementById('methodSelect').value = 'GET';
  updateMethodColor();
  currentReqId = null;
 
  closeModal('modal-import');
  showToast('🔗 URL loaded as GET request!');
}
 
// ── Import from cURL ──────────────────────────────────────────
function importFromCurl() {
  const curlText = document.getElementById('curlInput')?.value.trim();
  if (!curlText) { showToast('Paste a cURL command first', 'warning'); return; }
 
  try {
    const parsed = parseCurl(curlText);
 
    // Load into request builder
    document.getElementById('urlInput').value = parsed.url;
    document.getElementById('methodSelect').value = parsed.method;
    updateMethodColor();
    currentReqId = null;
 
    // Set headers
    const headersBody = document.getElementById('headersBody');
    headersBody.innerHTML = '';
    parsed.headers.forEach(h => {
      headersBody.appendChild(makeTableRow('headersBody', h.key, h.value, '', true));
    });
    headersBody.appendChild(makeTableRow('headersBody', '', '', '', false));
    document.getElementById('headerCount').textContent = parsed.headers.length;
 
    // Set body
    if (parsed.body) {
      document.querySelector('input[name="bodyType"][value="raw"]').checked = true;
      toggleBodyEditor();
      document.getElementById('bodyEditor').value = parsed.body;
      // Switch to body tab
      const bodyTab = document.querySelector('.req-tab[onclick*="body"]');
      if (bodyTab) switchReqTab(bodyTab, 'body');
    }
 
    // Set auth if Bearer found
    const authHeader = parsed.headers.find(h => h.key.toLowerCase() === 'authorization');
    if (authHeader && authHeader.value.startsWith('Bearer ')) {
      document.getElementById('authType').value = 'bearer';
      document.getElementById('bearerToken').value = authHeader.value.replace('Bearer ', '');
      toggleAuthFields();
    }
 
    closeModal('modal-import');
    showToast('⚡ cURL imported successfully!');
  } catch(e) {
    showToast('Could not parse cURL: ' + e.message, 'error');
  }
}
 
// ── cURL Parser ───────────────────────────────────────────────
function parseCurl(curl) {
  const result = { method: 'GET', url: '', headers: [], body: '' };
 
  // Normalize — remove line continuations
  const normalized = curl.replace(/\\\n\s*/g, ' ').replace(/\s+/g, ' ').trim();
 
  // Method: -X POST or --request POST
  const methodMatch = normalized.match(/(?:-X|--request)\s+([A-Z]+)/i);
  if (methodMatch) result.method = methodMatch[1].toUpperCase();
 
  // URL — first bare https?:// or quoted url after curl
  const urlMatch = normalized.match(/curl\s+(?:-[^\s]+\s+[^\s]+\s+)*['"]?(https?:\/\/[^\s'"]+)['"]?/i)
    || normalized.match(/['"]?(https?:\/\/[^\s'"]+)['"]?/);
  if (urlMatch) result.url = urlMatch[1];
 
  // Headers: -H 'Key: Value' or --header 'Key: Value'
  const headerRegex = /(?:-H|--header)\s+['"]([^'"]+)['"]/gi;
  let hMatch;
  while ((hMatch = headerRegex.exec(normalized)) !== null) {
    const parts = hMatch[1].split(': ');
    if (parts.length >= 2) {
      result.headers.push({ key: parts[0].trim(), value: parts.slice(1).join(': ').trim() });
    }
  }
 
  // Body: -d or --data or --data-raw
  const bodyMatch = normalized.match(/(?:-d|--data(?:-raw)?)\s+['"](.+?)['"](?=\s+-|\s*$)/i)
    || normalized.match(/(?:-d|--data(?:-raw)?)\s+'([\s\S]+?)'/i)
    || normalized.match(/(?:-d|--data(?:-raw)?)\s+"([\s\S]+?)"/i);
  if (bodyMatch) {
    result.body = bodyMatch[1].replace(/\\"/g, '"').replace(/\\n/g, '\n');
    if (result.method === 'GET') result.method = 'POST';
  }
 
  // If no explicit method but has body → POST
  if (!methodMatch && result.body) result.method = 'POST';
 
  return result;
}
 
// ── Import from Raw HTTP text ─────────────────────────────────
function importFromRaw() {
  const raw = document.getElementById('rawImportInput')?.value.trim();
  if (!raw) { showToast('Paste some text first', 'warning'); return; }
 
  try {
    // Try JSON collection first
    try {
      const json = JSON.parse(raw);
      closeModal('modal-import');
      parseAndImportCollection(json);
      return;
    } catch {}
 
    // Try raw HTTP format: "METHOD /path HTTP/1.1\nHost: ...\n\nbody"
    const lines = raw.split('\n');
    const firstLine = lines[0].trim();
    const httpMatch = firstLine.match(/^(GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS)\s+(\S+)/i);
 
    if (httpMatch) {
      const method = httpMatch[1].toUpperCase();
      let url = httpMatch[2];
 
      // Find Host header to build full URL
      const hostLine = lines.find(l => l.toLowerCase().startsWith('host:'));
      const host = hostLine ? hostLine.split(':').slice(1).join(':').trim() : '';
      if (host && !url.startsWith('http')) url = 'https://' + host + url;
 
      // Parse headers (lines between first line and blank line)
      const headers = [];
      let bodyStart = -1;
      for (let i = 1; i < lines.length; i++) {
        if (lines[i].trim() === '') { bodyStart = i + 1; break; }
        const colonIdx = lines[i].indexOf(':');
        if (colonIdx > 0) {
          headers.push({ key: lines[i].slice(0, colonIdx).trim(), value: lines[i].slice(colonIdx + 1).trim() });
        }
      }
      const body = bodyStart > 0 ? lines.slice(bodyStart).join('\n').trim() : '';
 
      // Load into builder
      document.getElementById('urlInput').value = url;
      document.getElementById('methodSelect').value = method;
      updateMethodColor();
      currentReqId = null;
 
      const headersBody = document.getElementById('headersBody');
      headersBody.innerHTML = '';
      headers.forEach(h => headersBody.appendChild(makeTableRow('headersBody', h.key, h.value, '', true)));
      headersBody.appendChild(makeTableRow('headersBody', '', '', '', false));
      document.getElementById('headerCount').textContent = headers.filter(h => h.key.toLowerCase() !== 'host').length;
 
      if (body) {
        document.querySelector('input[name="bodyType"][value="raw"]').checked = true;
        toggleBodyEditor();
        document.getElementById('bodyEditor').value = body;
      }
 
      closeModal('modal-import');
      showToast('📝 Raw HTTP parsed successfully!');
    } else {
      showToast('Could not parse — try cURL tab instead', 'error');
    }
  } catch(e) {
    showToast('Parse error: ' + e.message, 'error');
  }
}

  async function parseAndImportCollection(json) {
    let collName = 'Imported Collection';
    let requests = [];

    // Support Postman v2.1 format
    if (json.info && json.item) {
      collName = json.info.name || collName;
      requests = flattenPostmanItems(json.item);
    }
    // Support Postman v1 format
    else if (json.name && json.requests) {
      collName = json.name;
      requests = json.requests.map(r => ({
        name: r.name,
        method: r.method || 'GET',
        url: typeof r.url === 'string' ? r.url : (r.url?.raw || ''),
        headers: (r.headerData || []).map(h => ({ key: h.key, value: h.value, enabled: !h.disabled })),
        body: r.rawModeData || '',
        body_type: r.dataMode === 'raw' ? 'raw' : 'none',
      }));
    } else {
      showToast('Unknown collection format', 'error');
      return;
    }

    // Create collection
    const coll = await ajax('collections/store', 'POST', { name: collName, icon: '📥' });
    if (!coll.success) { showToast('Failed to create collection', 'error'); return; }

    // Import requests
    let count = 0;
    for (const req of requests) {
      await ajax('requests/save', 'POST', {
        collection_id: coll.id,
        name: req.name || 'Request',
        method: req.method || 'GET',
        url: req.url || '',
        headers: JSON.stringify(req.headers || []),
        params: JSON.stringify([]),
        body_type: req.body_type || 'none',
        body_content: req.body || '',
        auth_type: 'none',
      });
      count++;
    }

    closeModal('modal-import');
    showToast(`✅ Imported "${collName}" with ${count} request(s)!`);
    setTimeout(() => location.reload(), 1000);
  }

  function flattenPostmanItems(items, result = []) {
    for (const item of items) {
      if (item.item) {
        flattenPostmanItems(item.item, result);
      } else if (item.request) {
        const r = item.request;
        result.push({
          name: item.name,
          method: r.method || 'GET',
          url: typeof r.url === 'string' ? r.url : (r.url?.raw || ''),
          headers: (r.header || []).map(h => ({ key: h.key, value: h.value, enabled: !h.disabled })),
          body: r.body?.raw || '',
          body_type: r.body?.mode === 'raw' ? 'raw' : (r.body?.mode === 'formdata' ? 'form-data' : 'none'),
        });
      }
    }
    return result;
  }

  // ─── EXPORT ────────────────────────────────────────────────
  async function exportAll() {
    showToast('📤 Preparing export...');
    const collections = await ajax('collections');
    const exportData = {
      info: { name: 'APIForge Export', schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json', _postman_id: 'apiforge-' + Date.now() },
      item: collections.map(col => ({
        name: col.name,
        item: (col.requests || []).map(req => ({
          name: req.name,
          request: {
            method: req.method,
            header: req.headers ? JSON.parse(req.headers).map(h => ({ key: h.key, value: h.value, disabled: !h.enabled })) : [],
            url: { raw: req.url, host: [req.url] },
            body: req.body_content ? { mode: req.body_type === 'raw' ? 'raw' : req.body_type, raw: req.body_content } : undefined,
          }
        }))
      }))
    };

    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'apiforge_export_' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
    showToast('✅ Exported as Postman-compatible JSON!');
  }

  // ─── SHORTCUTS MODAL ──────────────────────────────────────
  function showShortcuts() {
    openModal('modal-shortcuts');
  }

  // ─── THEME TOGGLE ─────────────────────────────────────────
  let _darkMode = localStorage.getItem('apiforge_theme') !== 'light';
  function applyTheme(dark) {
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    const btn = document.querySelector('.topbar-btn[onclick*="toggleTheme"]');
    if (btn) btn.textContent = dark ? '🌙' : '☀️';
    localStorage.setItem('apiforge_theme', dark ? 'dark' : 'light');
  }
  function toggleTheme() {
    _darkMode = !_darkMode;
    applyTheme(_darkMode);
    showToast(_darkMode ? '🌙 Dark mode on' : '☀️ Light mode on');
  }

  // ─── KEYBOARD SHORTCUTS ───────────────────────────────────
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey)) {
      if (e.key === 'Enter') { e.preventDefault(); sendRequest(); }
      if (e.key === 's') { e.preventDefault(); saveRequest(); }
      if (e.key === 't') { e.preventDefault(); newRequestTab(); }
      if (e.key === 'k') { e.preventDefault(); document.getElementById('sidebarSearch')?.focus(); }
      if (e.key === 'd') { e.preventDefault(); toggleTheme(); }
    }
  });

  // ─── INIT ─────────────────────────────────────────────────
  function init() {
    // Apply saved theme on load
    applyTheme(_darkMode);
    updateMethodColor();
    toggleAuthFields();
    toggleBodyEditor();
    // Add empty rows to tables
    if (!document.getElementById('paramsBody').children.length) addParamRow();
    if (!document.getElementById('headersBody').children.length) addHeaderRow();
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(m => {
      m.addEventListener('click', e => { if (e.target === m) m.classList.add('panel-hidden'); });
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  // ─── PUBLIC API ───────────────────────────────────────────
  return {
    sendRequest, loadRequest, saveRequest, doSaveRequest,
    updateMethodColor, switchTab, newRequestTab, closeTab,
    switchReqTab, switchRespTab, toggleCollection, toggleAuthFields, toggleBodyEditor,
    addParamRow, addHeaderRow, addFormRow, addEnvVar, updateBadgeCount, toggleFormRowType,
    newCollectionModal, createCollection, collectionMenu, requestMenu,
    switchEnvironment, editEnvironment, saveEnvironment,
    loadFromHistory, clearHistory,
    copyResponse, beautifyResponse, saveResponseToFile, clearResponse,
    searchRequests, openModal, closeModal,
    showWorkspaces, createWorkspace, deleteWorkspace, importModal, doImport, exportAll, showShortcuts, toggleTheme,
	switchImportTab, importDragOver, importDragLeave, importDrop,
	onImportFileSelect, importFromUrl, importFromCurl, importFromRaw,
  };
})();
