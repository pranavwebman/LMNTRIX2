<?php
// admin/index.php
// LMNTrix Hidden Administration Control Panel

require_once __DIR__ . '/../includes/auth.php';

$admin = get_current_user_data();

if (!$admin || !in_array($admin['role'], ['admin', 'owner'], true)) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style='background:#e4ebf0;font-family:sans-serif;display:grid;place-items:center;height:100vh;margin:0;'><div style='text-align:center;'><h1>403 Forbidden</h1><p>Access denied.</p><a href='../index.php' style='color:#4a90c7;'>Return to LMNTrix</a></div></body></html>";
    exit;
}

$csrfToken = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>LMNTRIX — System Administration</title>
<style>
:root{
  --bg:#e4ebf0;
  --text:#3a4756;
  --text-muted:#6d7b8d;
  --text-dim:#9aa6b5;
  --accent:#4a90c7;
  --accent-deep:#2d6b9e;
  --online:#4fb87a;
  --shadow-dark:#a3b1c6;
  --shadow-light:#ffffff;
  --clay:1;
}

*,*::before,*::after{box-sizing:border-box;}
body{
  margin:0;
  background:var(--bg);
  color:var(--text);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  font-size:14px;
  line-height:1.5;
  padding:20px;
}

.clay{
  background:var(--bg);
  border-radius:18px;
  box-shadow:
    calc(6px * var(--clay)) calc(6px * var(--clay)) calc(14px * var(--clay)) var(--shadow-dark),
    calc(-6px * var(--clay)) calc(-6px * var(--clay)) calc(14px * var(--clay)) var(--shadow-light);
  padding:20px;
  margin-bottom:20px;
}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.header h1{
  margin:0;
  font-size:18px;
  letter-spacing:.2em;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:16px;
  margin-top:16px;
}

.stat-box{
  text-align:center;
  padding:16px;
}

.stat-num{
  font-size:28px;
  font-weight:700;
  color:var(--accent-deep);
}

.stat-label{
  font-size:10px;
  letter-spacing:.15em;
  text-transform:uppercase;
  color:var(--text-dim);
}

.btn{
  padding:8px 14px;
  border:none;
  border-radius:10px;
  background:var(--bg);
  color:var(--text);
  font-weight:600;
  cursor:pointer;
  box-shadow:
    calc(3px * var(--clay)) calc(3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-dark),
    calc(-3px * var(--clay)) calc(-3px * var(--clay)) calc(7px * var(--clay)) var(--shadow-light);
  transition:all .2s ease;
}

.btn:active{
  box-shadow:
    inset calc(2px * var(--clay)) calc(2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-dark),
    inset calc(-2px * var(--clay)) calc(-2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-light);
}

.btn-primary{
  background:linear-gradient(145deg,#5a9ed1,#3a7db3);
  color:#fff;
}

table{
  width:100%;
  border-collapse:collapse;
  margin-top:14px;
}

th, td{
  padding:10px 12px;
  text-align:left;
  border-bottom:1px solid rgba(163,177,198,.3);
  font-size:13px;
}

th{
  font-size:10px;
  letter-spacing:.15em;
  text-transform:uppercase;
  color:var(--text-dim);
}

input, select, textarea{
  width:100%;
  padding:10px;
  border:none;
  border-radius:10px;
  background:var(--bg);
  color:var(--text);
  box-shadow:
    inset calc(2px * var(--clay)) calc(2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-dark),
    inset calc(-2px * var(--clay)) calc(-2px * var(--clay)) calc(5px * var(--clay)) var(--shadow-light);
  margin-bottom:10px;
  outline:none;
}
</style>
</head>
<body>

<div class="clay header">
  <div>
    <h1>LMNTRIX ADMIN</h1>
    <span style="font-size:11px;color:var(--text-dim);">Logged in as <?php echo htmlspecialchars($admin['display_name']); ?> (<?php echo htmlspecialchars($admin['role']); ?>)</span>
  </div>
  <a href="../index.php" class="btn">Return to Chat</a>
</div>

<div class="grid">
  <div class="clay stat-box">
    <div class="stat-num" id="statUsers">-</div>
    <div class="stat-label">Total Users</div>
  </div>
  <div class="clay stat-box">
    <div class="stat-num" id="statOnline">-</div>
    <div class="stat-label">Online Users</div>
  </div>
  <div class="clay stat-box">
    <div class="stat-num" id="statMessages">-</div>
    <div class="stat-label">Total Messages</div>
  </div>
  <div class="clay stat-box">
    <div class="stat-num" id="statToday">-</div>
    <div class="stat-label">Messages Today</div>
  </div>
</div>

<div class="clay">
  <h3>Send Group Announcement</h3>
  <form id="announcementForm">
    <textarea id="announcementText" placeholder="Type announcement message..." rows="3" required></textarea>
    <button type="submit" class="btn btn-primary">Broadcast Announcement</button>
  </form>
</div>

<div class="clay">
  <h3>User Management</h3>
  <div id="usersTableContainer">Loading users...</div>
</div>

<div class="clay">
  <h3>System Audit Logs</h3>
  <div id="auditLogsContainer">Loading logs...</div>
</div>

<script>
const CSRF_TOKEN = "<?php echo $csrfToken; ?>";

function esc(str) {
  return String(str || '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

async function loadDashboard() {
  const res = await fetch('../api/admin.php?action=dashboard');
  const data = await res.json();
  if (data.success) {
    document.getElementById('statUsers').textContent = data.stats.total_users;
    document.getElementById('statOnline').textContent = data.stats.online_users;
    document.getElementById('statMessages').textContent = data.stats.total_messages;
    document.getElementById('statToday').textContent = data.stats.messages_today;

    let logHtml = '<table><thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>IP</th><th>Time</th></tr></thead><tbody>';
    data.audit_logs.forEach(l => {
      logHtml += `<tr>
        <td>${esc(l.admin_name)}</td>
        <td><strong>${esc(l.action)}</strong></td>
        <td>${esc(l.details || '')}</td>
        <td>${esc(l.ip_address || '')}</td>
        <td>${esc(l.created_at)}</td>
      </tr>`;
    });
    logHtml += '</tbody></table>';
    document.getElementById('auditLogsContainer').innerHTML = logHtml;
  }
}

async function loadUsers() {
  const res = await fetch('../api/admin.php?action=users');
  const data = await res.json();
  if (data.success) {
    let html = '<table><thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Messages</th><th>Action</th></tr></thead><tbody>';
    data.users.forEach(u => {
      html += `<tr>
        <td>${u.id}</td>
        <td><strong>${esc(u.display_name)}</strong> (@${esc(u.username)})</td>
        <td>${esc(u.email)}</td>
        <td>${esc(u.role)}</td>
        <td>${u.is_banned ? '<span style="color:red">Banned</span>' : (u.is_muted ? '<span style="color:orange">Muted</span>' : 'Active')}</td>
        <td>${u.message_count}</td>
        <td>
          <button class="btn" onclick="toggleMute(${u.id}, ${u.is_muted ? 0 : 1}, '${esc(u.display_name)}', '${esc(u.role)}')">${u.is_muted ? 'Unmute' : 'Mute'}</button>
          <button class="btn" onclick="toggleBan(${u.id}, ${u.is_banned ? 0 : 1}, '${esc(u.display_name)}', '${esc(u.role)}')">${u.is_banned ? 'Unban' : 'Ban'}</button>
        </td>
      </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('usersTableContainer').innerHTML = html;
  }
}

async function toggleMute(userId, mute, displayName, role) {
  await fetch('../api/admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'user_update',
      csrf_token: CSRF_TOKEN,
      user_id: userId,
      display_name: displayName,
      role: role,
      is_muted: mute
    })
  });
  loadUsers();
}

async function toggleBan(userId, ban, displayName, role) {
  await fetch('../api/admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'user_update',
      csrf_token: CSRF_TOKEN,
      user_id: userId,
      display_name: displayName,
      role: role,
      is_banned: ban
    })
  });
  loadUsers();
}

document.getElementById('announcementForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = document.getElementById('announcementText').value;
  const res = await fetch('../api/admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'send_announcement',
      csrf_token: CSRF_TOKEN,
      content: text
    })
  });
  const data = await res.json();
  if (data.success) {
    alert('Announcement broadcasted!');
    document.getElementById('announcementText').value = '';
    loadDashboard();
  }
});

loadDashboard();
loadUsers();
</script>
</body>
</html>
