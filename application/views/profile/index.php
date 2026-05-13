<div class="topbar">
  <div class="logo">
    <div class="logo-icon">⚡</div>
    APIForge
  </div>
  <div class="topbar-sep"></div>
  <a href="<?= site_url('dashboard') ?>" class="topbar-btn" style="text-decoration:none">← Back to Dashboard</a>
  <div class="topbar-right">
    <a href="<?= site_url('logout') ?>" class="topbar-btn" style="text-decoration:none">🚪 Logout</a>
    <div class="user-avatar"><?= isset($user['avatar']) ? $user['avatar'] : '' ?></div>
  </div>
</div>

<div style="display:flex;align-items:center;justify-content:center;height:calc(100vh - 48px);background:var(--bg-main)">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:40px;width:420px">
    <div style="text-align:center;margin-bottom:32px">
      <div style="width:72px;height:72px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#fff;margin:0 auto 12px;font-family:'JetBrains Mono',monospace">
        <?= htmlspecialchars($profile->avatar ?? 'U') ?>
      </div>
      <h2 style="font-size:18px;font-weight:600;color:var(--text-primary);margin:0"><?= htmlspecialchars($profile->name ?? '') ?></h2>
      <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0"><?= htmlspecialchars($profile->email ?? '') ?></p>
    </div>

    <div id="profileAlert" style="display:none;padding:10px 14px;border-radius:8px;font-size:12.5px;margin-bottom:16px"></div>

    <div class="form-group">
      <label style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px">Full Name</label>
      <input type="text" id="profileName" class="var-input full" value="<?= htmlspecialchars($profile->name ?? '') ?>" style="margin-top:6px;border:1px solid var(--border);border-radius:6px;padding:8px 12px;width:100%;box-sizing:border-box">
    </div>
    <div class="form-group" style="margin-top:16px">
      <label style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px">New Password <span style="color:var(--text-muted)">(leave blank to keep)</span></label>
      <input type="password" id="profilePass" class="var-input full" placeholder="••••••••" style="margin-top:6px;border:1px solid var(--border);border-radius:6px;padding:8px 12px;width:100%;box-sizing:border-box">
    </div>
    <button onclick="saveProfile()" class="btn-primary" style="width:100%;margin-top:24px;padding:10px">Save Changes</button>
  </div>
</div>

<script>
async function saveProfile() {
  const name = document.getElementById('profileName').value.trim();
  const pass = document.getElementById('profilePass').value;
  const alert = document.getElementById('profileAlert');
  if (!name) { showAlert('Name cannot be empty', 'error'); return; }
  const fd = new FormData();
  fd.append('name', name);
  if (pass) fd.append('password', pass);
  const r = await fetch('<?= site_url('profile/update') ?>', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.success) { showAlert('✅ Profile updated!', 'success'); document.getElementById('profilePass').value = ''; }
  else showAlert('❌ Update failed', 'error');
}
function showAlert(msg, type) {
  const el = document.getElementById('profileAlert');
  el.textContent = msg;
  el.style.display = 'block';
  el.style.background = type === 'success' ? 'rgba(0,212,170,0.12)' : 'rgba(249,62,62,0.12)';
  el.style.color = type === 'success' ? 'var(--success)' : 'var(--error)';
  el.style.border = '1px solid ' + (type === 'success' ? 'var(--success)' : 'var(--error)');
  setTimeout(() => el.style.display = 'none', 3000);
}
</script>
