<!-- TOP BAR -->
<div class="topbar">
  <div class="logo">
    <div class="logo-icon">⚡</div>
    APIForge
  </div>
  <div class="topbar-sep"></div>
  <button class="topbar-btn" onclick="App.newCollectionModal()">📁 New Collection</button>
  <button class="topbar-btn" onclick="App.showWorkspaces()">🗂️ Workspaces</button>
  <button class="topbar-btn" onclick="App.importModal()">📥 Import</button>
  <button class="topbar-btn primary" onclick="App.exportAll()">📤 Export</button>
  <div class="topbar-right">
    <button class="topbar-btn" onclick="App.showShortcuts()">⌨️ Shortcuts</button>
    <button class="topbar-btn" onclick="App.toggleTheme()">🌙</button>
    <a href="<?= site_url('profile') ?>" class="topbar-btn" style="text-decoration:none">👤 Profile</a>
    <a href="<?= site_url('logout') ?>" class="topbar-btn" style="text-decoration:none">🚪 Logout</a>
    <div class="user-avatar" title="<?= isset($user['name']) ? $user['name'] : '' ?>"><?= isset($user['avatar']) ? $user['avatar'] : '' ?></div>
  </div>
</div>

<!-- MAIN -->
<div class="main">

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-search">
      <div class="search-wrap">
        <input class="search-input" id="sidebarSearch" placeholder="Search requests..." type="text" oninput="App.searchRequests(this.value)">
      </div>
    </div>
    <div class="sidebar-actions">
      <button onclick="App.newRequestTab()">+ Request</button>
      <button onclick="App.newCollectionModal()">📁 New</button>
    </div>

    <div class="collections-list" id="collectionsList">
      <?php foreach ($collections as $col): ?>
      <div class="collection-item" data-id="<?= $col->id ?>">
        <div class="collection-header" onclick="App.toggleCollection(this)">
          <span class="coll-arrow open">▶</span>
          <span class="coll-icon"><?= $col->icon ?></span>
          <span class="coll-name"><?= htmlspecialchars($col->name) ?></span>
          <span class="coll-count"><?= count($col->requests) ?></span>
          <span class="coll-menu" onclick="App.collectionMenu(event, <?= $col->id ?>)">⋯</span>
        </div>
        <div class="request-list open">
          <?php foreach ($col->requests as $req): ?>
          <div class="request-item" data-id="<?= $req->id ?>" onclick="App.loadRequest(<?= $req->id ?>)">
            <span class="method-badge <?= $req->method ?>"><?= $req->method ?></span>
            <span class="req-name"><?= htmlspecialchars($req->name) ?></span>
            <span class="req-opts" onclick="App.requestMenu(event, <?= $req->id ?>)">⋯</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- WORKSPACE -->
  <div class="workspace">

    <!-- TABS BAR -->
    <div class="tabs-bar" id="tabsBar">
      <div class="tab active" id="tab-new" data-req-id="">
        <span class="tab-method method-get">GET</span>
        <span class="tab-label">New Request</span>
        <span class="tab-close" onclick="App.closeTab(event, 'tab-new')">×</span>
      </div>
      <?php foreach ($tabs as $tab): ?>
      <div class="tab" id="tab-<?= $tab->id ?>" data-req-id="<?= $tab->request_id ?>" onclick="App.switchTab('tab-<?= $tab->id ?>')">
        <span class="tab-method method-<?= strtolower($tab->method) ?>"><?= $tab->method ?></span>
        <span class="tab-label"><?= htmlspecialchars($tab->tab_name) ?></span>
        <span class="tab-close" onclick="App.closeTab(event, 'tab-<?= $tab->id ?>')">×</span>
      </div>
      <?php endforeach; ?>
      <div class="tab-new" onclick="App.newRequestTab()" title="New Tab">+</div>
    </div>

    <!-- REQUEST BUILDER -->
    <div class="request-builder">
      <div class="url-row">
        <select class="method-select" id="methodSelect" onchange="App.updateMethodColor()">
          <option>GET</option>
          <option>POST</option>
          <option>PUT</option>
          <option>DELETE</option>
          <option>PATCH</option>
          <option>HEAD</option>
          <option>OPTIONS</option>
        </select>
        <input class="url-input" id="urlInput" placeholder="Enter URL or {{base_url}}/endpoint" value="">
        <button class="save-btn" onclick="App.saveRequest()">💾 Save</button>
        <button class="send-btn" id="sendBtn" onclick="App.sendRequest()">▶ Send</button>
      </div>

      <div class="req-tabs">
        <div class="req-tab active" onclick="App.switchReqTab(this,'params')">Params <span class="badge-count" id="paramCount">0</span></div>
        <div class="req-tab" onclick="App.switchReqTab(this,'auth')">Authorization</div>
        <div class="req-tab" onclick="App.switchReqTab(this,'headers')">Headers <span class="badge-count" id="headerCount">0</span></div>
        <div class="req-tab" onclick="App.switchReqTab(this,'body')">Body</div>
        <div class="req-tab" onclick="App.switchReqTab(this,'preScript')">Pre-request Script</div>
        <div class="req-tab" onclick="App.switchReqTab(this,'tests')">Tests</div>
        <div class="req-tab" onclick="App.switchReqTab(this,'settings')">Settings</div>
      </div>
    </div>

    <!-- PARAMS PANEL -->
    <div class="req-body-area" id="panel-params">
      <table class="params-table">
        <thead><tr><th></th><th>Key</th><th>Value</th><th>Description</th><th></th></tr></thead>
        <tbody id="paramsBody"></tbody>
      </table>
      <button class="add-row-btn" onclick="App.addParamRow()">+ Add Parameter</button>
    </div>

    <!-- AUTH PANEL -->
    <div class="req-body-area panel-hidden" id="panel-auth">
      <div class="auth-type-row">
        <label>Auth Type:</label>
        <select id="authType" onchange="App.toggleAuthFields()">
          <option value="none">No Auth</option>
          <option value="bearer">Bearer Token</option>
          <option value="basic">Basic Auth</option>
          <option value="api-key">API Key</option>
          <option value="oauth2">OAuth 2.0</option>
        </select>
      </div>
      <div id="authFields-bearer" class="auth-fields">
        <div class="var-row"><span class="var-label">Token</span>
          <input class="var-input full" id="bearerToken" placeholder="Enter Bearer token...">
        </div>
      </div>
      <div id="authFields-basic" class="auth-fields panel-hidden">
        <div class="var-row"><span class="var-label">Username</span><input class="var-input full" id="basicUser" placeholder="Username"></div>
        <div class="var-row"><span class="var-label">Password</span><input class="var-input full" id="basicPass" type="password" placeholder="Password"></div>
      </div>
      <div id="authFields-api-key" class="auth-fields panel-hidden">
        <div class="var-row"><span class="var-label">Key Name</span><input class="var-input full" id="apiKeyName" value="X-API-Key"></div>
        <div class="var-row"><span class="var-label">Key Value</span><input class="var-input full" id="apiKeyValue" placeholder="API Key value..."></div>
        <div class="var-row"><span class="var-label">Add to</span>
          <select id="apiKeyIn" class="var-input" style="cursor:pointer">
            <option value="header">Header</option>
            <option value="query">Query Params</option>
          </select>
        </div>
      </div>
    </div>

    <!-- HEADERS PANEL -->
    <div class="req-body-area panel-hidden" id="panel-headers">
      <table class="params-table">
        <thead><tr><th></th><th>Header</th><th>Value</th><th>Description</th><th></th></tr></thead>
        <tbody id="headersBody"></tbody>
      </table>
      <button class="add-row-btn" onclick="App.addHeaderRow()">+ Add Header</button>
    </div>

    <!-- BODY PANEL -->
    <div class="req-body-area panel-hidden" id="panel-body">
      <div class="body-type-row">
        <label><input type="radio" name="bodyType" value="none" checked onchange="App.toggleBodyEditor()"> none</label>
        <label><input type="radio" name="bodyType" value="raw" onchange="App.toggleBodyEditor()"> raw</label>
        <label><input type="radio" name="bodyType" value="form-data" onchange="App.toggleBodyEditor()"> form-data</label>
        <label><input type="radio" name="bodyType" value="x-www-form-urlencoded" onchange="App.toggleBodyEditor()"> x-www-form-urlencoded</label>
        <select id="rawBodyFormat" style="margin-left:auto">
          <option value="json">JSON</option><option value="text">Text</option>
          <option value="xml">XML</option><option value="html">HTML</option>
        </select>
      </div>
      <div id="bodyEditorWrap" class="panel-hidden">
        <textarea class="json-editor" id="bodyEditor" spellcheck="false" placeholder='{"key": "value"}'></textarea>
      </div>
       <div id="formDataWrap" class="panel-hidden">
        <table class="params-table">
          <thead>
			  <tr>
				<th style="width:32px"></th>
				<th>Key</th>
				<th style="width:80px">Type</th>
				<th>Value</th>
				<th>Description</th>
				<th style="width:30px"></th>
			  </tr>
			</thead>
          <tbody id="formDataBody"></tbody>
        </table>
        <button class="add-row-btn" onclick="App.addFormRow()">+ Add Field</button>
      </div>
    </div>
	

    <!-- PRE-REQUEST SCRIPT -->
    <div class="req-body-area panel-hidden" id="panel-preScript">
      <textarea class="json-editor" id="preScriptEditor" spellcheck="false" style="color:#61affe;height:140px" placeholder="// Pre-request Script
// pm.environment.set('token', 'abc123');
// console.log('Running before request...');"></textarea>
    </div>

    <!-- TESTS -->
    <div class="req-body-area panel-hidden" id="panel-tests">
      <textarea class="json-editor" id="testsEditor" spellcheck="false" style="color:#fca130;height:140px" placeholder="// Test Scripts
// pm.test('Status 200', () => pm.response.to.have.status(200));
// const json = pm.response.json();
// pm.expect(json.status).to.equal('success');"></textarea>
    </div>

    <!-- SETTINGS -->
    <div class="req-body-area panel-hidden" id="panel-settings">
      <div class="settings-grid">
        <div class="setting-row"><span>Follow Redirects</span><input type="checkbox" id="sFollowRedirects" checked class="toggle-cb"></div>
        <div class="setting-row"><span>SSL Verification</span><input type="checkbox" id="sSSL" checked class="toggle-cb"></div>
        <div class="setting-row"><span>Timeout (ms)</span><input class="param-input" id="sTimeout" value="30000" style="width:100px;border:1px solid var(--border);border-radius:5px;background:var(--bg-card)"></div>
      </div>
    </div>

    <!-- RESPONSE PANEL -->
    <div class="response-panel" id="responsePanel">
      <div class="response-header">
        <span class="response-title">Response</span>
        <div class="status-pill success panel-hidden" id="statusPill">
          <div class="status-dot"></div>
          <span id="statusCode">200 OK</span>
        </div>
        <div class="resp-meta panel-hidden" id="respMeta">
          <span><strong id="respTime">0</strong> ms</span>
          <span><strong id="respSize">0</strong></span>
        </div>
        <div class="resp-actions">
          <button class="resp-action-btn" onclick="App.copyResponse()">📋 Copy</button>
          <button class="resp-action-btn" onclick="App.beautifyResponse()">✨ Beautify</button>
          <button class="resp-action-btn" onclick="App.saveResponseToFile()">💾 Save</button>
          <button class="resp-action-btn" onclick="App.clearResponse()">🗑 Clear</button>
        </div>
      </div>
      <div class="resp-tabs">
        <div class="resp-tab active" onclick="App.switchRespTab(this,'resp-body')">Body</div>
        <div class="resp-tab" onclick="App.switchRespTab(this,'resp-headers')">Headers <span class="badge-count" id="respHeaderCount">0</span></div>
        <div class="resp-tab" onclick="App.switchRespTab(this,'resp-cookies')">Cookies</div>
        <div class="resp-tab" onclick="App.switchRespTab(this,'resp-testResults')">Test Results</div>
      </div>
      <div class="resp-body" id="resp-body">
        <div class="no-resp" id="noRespMsg">
          <div class="no-resp-icon">📭</div>
          <span>Enter a URL and click <strong>Send</strong> to get started</span>
        </div>
        <pre id="responseContent" class="panel-hidden"></pre>
      </div>
      <div class="resp-body panel-hidden" id="resp-headers">
        <table class="params-table" id="responseHeadersTable">
          <thead><tr><th>Header</th><th>Value</th></tr></thead>
          <tbody id="responseHeadersBody"></tbody>
        </table>
      </div>
      <div class="resp-body panel-hidden" id="resp-cookies">
        <table class="params-table">
          <thead><tr><th>Name</th><th>Value</th><th>Domain</th><th>Expires</th></tr></thead>
          <tbody id="responseCookiesBody"></tbody>
        </table>
      </div>
      <div class="resp-body panel-hidden" id="resp-testResults">
        <div id="testResultsList" style="display:flex;flex-direction:column;gap:8px;font-size:12.5px;font-family:'JetBrains Mono',monospace;padding:4px 0"></div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <!-- Environment -->
    <div class="right-panel-section">
      <div class="rp-header">🌍 Environment</div>
      <div class="rp-content">
        <select class="env-selector" id="envSelector" onchange="App.switchEnvironment(this.value)">
          <?php foreach ($environments as $env): ?>
          <option value="<?= $env->id ?>" <?= $env->is_active ? 'selected' : '' ?>><?= htmlspecialchars($env->name) ?></option>
          <?php endforeach; ?>
        </select>
        <div style="display:flex;justify-content:space-between;margin:8px 0 6px;align-items:center">
          <span style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px">Variables</span>
          <button onclick="App.editEnvironment()" style="font-size:10px;background:none;border:none;color:var(--accent);cursor:pointer">Edit</button>
        </div>
        <div id="envVarsList">
          <?php foreach ($env_vars as $v): ?>
          <div class="var-row">
            <span class="var-key">{{<?= htmlspecialchars($v->var_key) ?>}}</span>
            <span class="var-val"><?= htmlspecialchars(substr($v->var_value, 0, 20)) ?><?= strlen($v->var_value) > 20 ? '…' : '' ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="right-panel-section">
      <div class="rp-header" style="display:flex;justify-content:space-between">
        <span>🕐 History</span>
        <button onclick="App.clearHistory()" style="font-size:10px;background:none;border:none;color:var(--error);cursor:pointer">Clear</button>
      </div>
      <div class="rp-content" id="historyList">
        <?php foreach ($history as $h): ?>
        <div class="history-item" onclick="App.loadFromHistory(<?= $h->id ?>)">
          <span class="hist-method method-<?= strtolower($h->method) ?>"><?= $h->method ?></span>
          <div style="flex:1;overflow:hidden">
            <div class="hist-url"><?= htmlspecialchars(parse_url($h->url, PHP_URL_PATH) ?: $h->url) ?></div>
            <div style="font-size:9px;color:var(--text-muted)"><?= date('H:i', strtotime($h->created_at)) ?> · <?= $h->response_status ?> · <?= $h->response_time ?>ms</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Stats -->
    <div class="right-panel-section" style="flex:1">
      <div class="rp-header">📊 Stats</div>
      <div class="rp-content">
        <div style="display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;justify-content:space-between;font-size:11px">
            <span style="color:var(--text-muted)">Total Requests</span>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--accent)"><?= number_format($stats['total_requests']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px">
            <span style="color:var(--text-muted)">Success Rate</span>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--success)"><?= $stats['success_rate'] ?>%</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px">
            <span style="color:var(--text-muted)">Avg Response</span>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--warning)"><?= $stats['avg_time'] ?>ms</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px">
            <span style="color:var(--text-muted)">Collections</span>
            <span style="font-family:'JetBrains Mono',monospace;color:var(--text-secondary)"><?= $stats['collections'] ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- STATUS BAR -->
<div class="statusbar">
  <span><span class="status-dot-green"></span> Ready</span>
  <span id="currentEnvLabel">ENV: <?= $active_env ? htmlspecialchars($active_env->name) : 'None' ?></span>
  <span>APIForge v1.0.0 — PHP CI3</span>
  <span style="margin-left:auto">⌘K Search &nbsp;|&nbsp; ⌘S Save &nbsp;|&nbsp; ⌘Enter Send</span>
</div>

<!-- MODAL: New Collection -->
<div class="modal-overlay panel-hidden" id="modal-collection">
  <div class="modal-card">
    <div class="modal-header"><span>📁 New Collection</span><button onclick="App.closeModal('modal-collection')">×</button></div>
    <div class="modal-body">
      <div class="form-group"><label>Collection Name</label><input type="text" id="newCollName" placeholder="My API Collection"></div>
      <div class="form-group"><label>Icon</label>
        <select id="newCollIcon">
          <option value="📦">📦 Package</option><option value="👤">👤 User</option>
          <option value="🔐">🔐 Auth</option><option value="💳">💳 Payment</option>
          <option value="📊">📊 Analytics</option><option value="🛒">🛒 Shop</option>
          <option value="📬">📬 Messages</option><option value="⚙️">⚙️ Settings</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="App.closeModal('modal-collection')">Cancel</button>
      <button class="btn-primary" onclick="App.createCollection()">Create Collection</button>
    </div>
  </div>
</div>

<!-- MODAL: Save Request -->
<div class="modal-overlay panel-hidden" id="modal-save">
  <div class="modal-card">
    <div class="modal-header"><span>💾 Save Request</span><button onclick="App.closeModal('modal-save')">×</button></div>
    <div class="modal-body">
      <div class="form-group"><label>Request Name</label><input type="text" id="saveReqName" placeholder="My Request"></div>
      <div class="form-group"><label>Save to Collection</label>
        <select id="saveReqCollection">
          <option value="">-- No Collection --</option>
          <?php foreach ($collections as $col): ?>
          <option value="<?= $col->id ?>"><?= htmlspecialchars($col->name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="App.closeModal('modal-save')">Cancel</button>
      <button class="btn-primary" onclick="App.doSaveRequest()">Save</button>
    </div>
  </div>
</div>

<!-- MODAL: Environment Editor -->
<div class="modal-overlay panel-hidden" id="modal-env">
  <div class="modal-card" style="width:560px">
    <div class="modal-header"><span>🌍 Edit Environment</span><button onclick="App.closeModal('modal-env')">×</button></div>
    <div class="modal-body">
      <table class="params-table" style="margin-bottom:8px">
        <thead><tr><th></th><th>Variable</th><th>Value</th><th></th></tr></thead>
        <tbody id="envVarsEditor"></tbody>
      </table>
      <button class="add-row-btn" onclick="App.addEnvVar()">+ Add Variable</button>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="App.closeModal('modal-env')">Cancel</button>
      <button class="btn-primary" onclick="App.saveEnvironment()">Save</button>
    </div>
  </div>
</div>

<!-- MODAL: Workspaces -->
<div class="modal-overlay panel-hidden" id="modal-workspaces">
  <div class="modal-card" style="width:520px">
    <div class="modal-header"><span>🗂️ Workspaces</span><button onclick="App.closeModal('modal-workspaces')">×</button></div>
    <div class="modal-body">
      <div id="workspaceList" style="margin-bottom:16px;max-height:220px;overflow-y:auto"></div>
      <div style="border-top:1px solid var(--border);padding-top:14px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px">Create New Workspace</div>
        <div class="form-group"><label>Workspace Name</label><input type="text" id="wsName" placeholder="My Project Workspace" style="margin-top:4px"></div>
        <div class="form-group" style="margin-top:10px"><label>Description (optional)</label><input type="text" id="wsDesc" placeholder="Short description..." style="margin-top:4px"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="App.closeModal('modal-workspaces')">Close</button>
      <button class="btn-primary" onclick="App.createWorkspace()">+ Create Workspace</button>
    </div>
  </div>
</div>


<!-- MODAL: Import -->
<div class="modal-overlay panel-hidden" id="modal-import">
  <div class="modal-card" style="width:580px;max-height:90vh;overflow-y:auto">
    <div class="modal-header">
      <span>📥 Import</span>
      <button onclick="App.closeModal('modal-import')">×</button>
    </div>
    <div class="modal-body" style="padding:0">
 
      <!-- Import Tabs -->
      <div style="display:flex;border-bottom:1px solid var(--border);padding:0 20px">
        <button class="import-tab-btn active" onclick="App.switchImportTab(this,'tab-file')"    style="padding:12px 16px;background:none;border:none;border-bottom:2px solid var(--accent);color:var(--accent);font-size:12px;cursor:pointer;font-family:'Sora',sans-serif;font-weight:600">📄 File</button>
        <button class="import-tab-btn"        onclick="App.switchImportTab(this,'tab-curl')"    style="padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;color:var(--text-muted);font-size:12px;cursor:pointer;font-family:'Sora',sans-serif">⚡ cURL</button>
        <button class="import-tab-btn"        onclick="App.switchImportTab(this,'tab-url')"     style="padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;color:var(--text-muted);font-size:12px;cursor:pointer;font-family:'Sora',sans-serif">🔗 URL</button>
        <button class="import-tab-btn"        onclick="App.switchImportTab(this,'tab-raw')"     style="padding:12px 16px;background:none;border:none;border-bottom:2px solid transparent;color:var(--text-muted);font-size:12px;cursor:pointer;font-family:'Sora',sans-serif">📝 Raw Text</button>
      </div>
 
      <!-- TAB: File -->
      <div id="tab-file" class="import-tab-panel" style="padding:20px">
        <div id="importDropZone" style="
          background:var(--bg-main);
          border:2px dashed var(--border);
          border-radius:12px;
          padding:36px 20px;
          text-align:center;
          cursor:pointer;
          transition:border-color 0.2s,background 0.2s;
        "
          onclick="document.getElementById('importFile').click()"
          ondragover="App.importDragOver(event)"
          ondragleave="App.importDragLeave(event)"
          ondrop="App.importDrop(event)"
        >
          <div style="font-size:36px;margin-bottom:10px">📄</div>
          <div style="font-size:13px;color:var(--text-primary);font-weight:600;margin-bottom:6px">Drop file here or click to browse</div>
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:16px">Supports Postman v1, v2.1 JSON · OpenAPI 3.0 YAML/JSON</div>
          <button class="btn-secondary" style="pointer-events:none">📂 Browse File</button>
          <div id="importFileName" style="margin-top:10px;font-size:11px;color:var(--accent);font-family:'JetBrains Mono',monospace;min-height:16px"></div>
        </div>
        <input type="file" id="importFile" accept=".json,.yaml,.yml" style="display:none"
          onchange="App.onImportFileSelect(this)">
      </div>
 
      <!-- TAB: cURL -->
      <div id="tab-curl" class="import-tab-panel panel-hidden" style="padding:20px">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">Paste a cURL command — it will be converted into a request automatically.</div>
        <textarea id="curlInput" spellcheck="false" style="
          width:100%;
          height:160px;
          background:var(--bg-main);
          border:1px solid var(--border);
          border-radius:8px;
          color:var(--accent2);
          font-family:'JetBrains Mono',monospace;
          font-size:12px;
          padding:12px;
          resize:vertical;
          outline:none;
          box-sizing:border-box;
        " placeholder="curl -X POST https://api.example.com/users \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer token123' \
  -d '{&quot;name&quot;: &quot;John&quot;}'"></textarea>
        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn-secondary" onclick="document.getElementById('curlInput').value=''">🗑 Clear</button>
          <button class="btn-primary" onclick="App.importFromCurl()" style="margin-left:auto">⚡ Convert & Import</button>
        </div>
      </div>
 
      <!-- TAB: URL -->
      <div id="tab-url" class="import-tab-panel panel-hidden" style="padding:20px">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">Paste a URL — it will be added as a GET request directly.</div>
        <input id="urlImportInput" type="url" style="
          width:100%;
          background:var(--bg-main);
          border:1px solid var(--border);
          border-radius:8px;
          color:var(--text-primary);
          font-family:'JetBrains Mono',monospace;
          font-size:13px;
          padding:11px 14px;
          outline:none;
          box-sizing:border-box;
        " placeholder="https://api.example.com/endpoint">
        <div style="font-size:11px;color:var(--text-muted);margin-top:10px">This will create a new GET request tab with this URL pre-filled.</div>
        <div style="margin-top:14px;display:flex;justify-content:flex-end">
          <button class="btn-primary" onclick="App.importFromUrl()">🔗 Open as Request</button>
        </div>
      </div>
 
      <!-- TAB: Raw Text -->
      <div id="tab-raw" class="import-tab-panel panel-hidden" style="padding:20px">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">Paste raw HTTP request text or JSON collection.</div>
        <textarea id="rawImportInput" spellcheck="false" style="
          width:100%;
          height:160px;
          background:var(--bg-main);
          border:1px solid var(--border);
          border-radius:8px;
          color:var(--text-primary);
          font-family:'JetBrains Mono',monospace;
          font-size:12px;
          padding:12px;
          resize:vertical;
          outline:none;
          box-sizing:border-box;
        " placeholder="POST /api/users HTTP/1.1
Host: example.com
Content-Type: application/json
Authorization: Bearer token123
 
{&quot;name&quot;: &quot;John&quot;, &quot;email&quot;: &quot;john@example.com&quot;}"></textarea>
        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn-secondary" onclick="document.getElementById('rawImportInput').value=''">🗑 Clear</button>
          <button class="btn-primary" onclick="App.importFromRaw()" style="margin-left:auto">📝 Parse & Import</button>
        </div>
      </div>
 
    </div>
 
    <div class="modal-footer" style="justify-content:space-between">
      <div style="font-size:11px;color:var(--text-muted)">💡 Tip: You can drag & drop files anywhere on the file tab</div>
      <div style="display:flex;gap:8px">
        <button class="btn-secondary" onclick="App.closeModal('modal-import')">Cancel</button>
        <button class="btn-primary" id="importFileBtn" onclick="App.doImport()">📥 Import File</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Shortcuts -->
<div class="modal-overlay panel-hidden" id="modal-shortcuts">
  <div class="modal-card" style="width:440px">
    <div class="modal-header"><span>⌨️ Keyboard Shortcuts</span><button onclick="App.closeModal('modal-shortcuts')">×</button></div>
    <div class="modal-body">
      <table style="width:100%;border-collapse:collapse;font-size:12.5px">
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:10px 4px;color:var(--text-muted)">Send Request</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + Enter</kbd></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:10px 4px;color:var(--text-muted)">Save Request</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + S</kbd></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:10px 4px;color:var(--text-muted)">New Tab</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + T</kbd></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:10px 4px;color:var(--text-muted)">Focus Search</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + K</kbd></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:10px 4px;color:var(--text-muted)">Toggle Theme</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + D</kbd></td>
        </tr>
        <tr>
          <td style="padding:10px 4px;color:var(--text-muted)">Close Tab</td>
          <td style="padding:10px 4px;text-align:right"><kbd style="background:var(--bg-main);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">Ctrl + W</kbd></td>
        </tr>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn-primary" onclick="App.closeModal('modal-shortcuts')">Got it!</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
  <span class="toast-icon" id="toastIcon">✅</span>
  <span id="toastMsg">Done</span>
</div>

<!-- PHP CONFIG for JS -->
<script>
window.AppConfig = {
  baseUrl: '<?= base_url() ?>',
  indexPage: '<?= config_item("index_page") ?>',
  userId: <?= $user['id'] ?>,
  activeEnvId: <?= $active_env ? $active_env->id : 'null' ?>,
};
</script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
