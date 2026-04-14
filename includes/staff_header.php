<?php
// includes/staff_header.php
requireStaff();
$staff_name = $_SESSION['staff_name'] ?? 'Staff';
$current_page = basename($_SERVER['PHP_SELF'], '.php');

$nav = [
    'dashboard'  => ['icon' => '⬡', 'label' => 'Dashboard'],
    'attendance' => ['icon' => '◎', 'label' => 'Attendance'],
    'marks'      => ['icon' => '◈', 'label' => 'Marks'],
    'notices'    => ['icon' => '📢', 'label' => 'Notices'],
];

$page_titles = [
    'dashboard'  => 'Dashboard',
    'attendance' => 'Attendance',
    'marks'      => 'Marks & Results',
    'notices'    => 'Notices',
];
$page_title = $page_titles[$current_page] ?? 'EduTrack';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?> — EduTrack Staff</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg: #080c14;
  --surface: #0e1520;
  --card: #111827;
  --card2: #141f30;
  --border: #1e2d42;
  --accent: #f59e0b;
  --accent2: #f97316;
  --blue: #4f8ef7;
  --gold: #f59e0b;
  --green: #10b981;
  --red: #ef4444;
  --orange: #f97316;
  --text: #e2e8f0;
  --muted: #64748b;
  --sidebar-w: 240px;
}

html, body { height: 100%; font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); }

.sidebar {
  position: fixed; left: 0; top: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  z-index: 100;
}
.sidebar-logo {
  padding: 28px 24px 24px;
  border-bottom: 1px solid var(--border);
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 1.1rem;
  display: flex; align-items: center; gap: 10px;
}
.logo-box {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
}
.sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
.nav-label {
  font-size: .65rem; font-weight: 700; letter-spacing: .12em;
  text-transform: uppercase; color: var(--muted);
  padding: 0 12px; margin: 16px 0 8px;
}
.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 10px;
  text-decoration: none; color: var(--muted);
  font-size: .9rem; font-weight: 500;
  transition: all .2s; margin-bottom: 2px;
}
.nav-item:hover { background: rgba(245,158,11,.08); color: var(--text); }
.nav-item.active {
  background: linear-gradient(135deg, rgba(245,158,11,.18), rgba(249,115,22,.12));
  color: var(--accent);
  border: 1px solid rgba(245,158,11,.2);
}
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }

.sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
.user-card {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px;
  background: var(--card); border-radius: 10px;
  margin-bottom: 8px;
}
.avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.user-info { min-width: 0; }
.user-name { font-size: .85rem; font-weight: 600; }
.user-role { font-size: .72rem; color: var(--muted); }
.btn-logout {
  display: flex; align-items: center; gap: 8px; justify-content: center;
  padding: 9px; border-radius: 10px;
  background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
  color: #fca5a5; font-size: .85rem; font-weight: 500;
  text-decoration: none; transition: all .2s; width: 100%;
}
.btn-logout:hover { background: rgba(239,68,68,.18); }

.main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
.topbar {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 0 32px; height: 65px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
}
.topbar-left h1 { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; }
.topbar-left .breadcrumb { font-size: .78rem; color: var(--muted); margin-top: 2px; }
.breadcrumb span { color: var(--accent); }
.page-content { padding: 32px; flex: 1; }

/* Reuse common styles */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px; }
.stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; position: relative; overflow: hidden; transition: transform .2s; }
.stat-card:hover { transform: translateY(-3px); }
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.stat-card.blue::before { background: linear-gradient(90deg, #4f8ef7, #60a5fa); }
.stat-card.green::before { background: linear-gradient(90deg, var(--green), #34d399); }
.stat-card.gold::before { background: linear-gradient(90deg, var(--gold), #fbbf24); }
.stat-card.red::before { background: linear-gradient(90deg, var(--red), #f87171); }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 16px; }
.stat-icon.blue { background: rgba(79,142,247,.12); }
.stat-icon.green { background: rgba(16,185,129,.12); }
.stat-icon.gold { background: rgba(245,158,11,.12); }
.stat-icon.red { background: rgba(239,68,68,.12); }
.stat-val { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 6px; }
.stat-label { font-size: .82rem; color: var(--muted); }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.panel { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.panel-head { padding: 20px 24px 0; display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-family: 'Syne', sans-serif; font-size: .95rem; font-weight: 700; }
.panel-sub { font-size: .78rem; color: var(--muted); margin-top: 2px; }
.panel-body { padding: 20px 24px 24px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
td { padding: 13px 16px; font-size: .88rem; border-bottom: 1px solid rgba(30,45,66,.5); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(245,158,11,.04); }
.badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 100px; font-size: .73rem; font-weight: 600; }
.badge-green { background: rgba(16,185,129,.12); color: var(--green); }
.badge-red { background: rgba(239,68,68,.12); color: var(--red); }
.badge-gold { background: rgba(245,158,11,.12); color: var(--gold); }
.badge-blue { background: rgba(79,142,247,.12); color: var(--blue); }
.badge-purple { background: rgba(124,58,237,.12); color: #a78bfa; }

.form-row { display: grid; gap: 16px; margin-bottom: 16px; }
.form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.form-field label { display: block; font-size: .77rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
.form-field input, .form-field select, .form-field textarea { width: 100%; background: var(--card2); border: 1px solid var(--border); border-radius: 10px; padding: 11px 14px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; }
.form-field input:focus, .form-field select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(245,158,11,.12); }
.form-field select option { background: var(--card); }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; box-shadow: 0 2px 12px rgba(245,158,11,.3); }
.btn-primary:hover { box-shadow: 0 4px 20px rgba(245,158,11,.5); transform: translateY(-1px); }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent); }
.btn-danger { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.2); color: var(--red); }
.btn-sm { padding: 7px 14px; font-size: .8rem; border-radius: 8px; }
.alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: .88rem; }
.alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }
.alert-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .2s; }
.modal-overlay.open { opacity: 1; pointer-events: auto; }
.modal { background: var(--card); border: 1px solid var(--border); border-radius: 20px; width: 90%; max-width: 580px; max-height: 85vh; overflow-y: auto; transform: scale(.95); transition: transform .2s; }
.modal-overlay.open .modal { transform: scale(1); }
.modal-head { padding: 24px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.modal-head h3 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.1rem; }
.modal-close { background: none; border: none; color: var(--muted); font-size: 1.4rem; cursor: pointer; }
.modal-body { padding: 24px 28px; }
.modal-foot { padding: 16px 28px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }
.progress { height: 6px; background: var(--border); border-radius: 10px; overflow: hidden; }
.progress-bar { height: 100%; border-radius: 10px; transition: width .6s ease; }
.chart-container { position: relative; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-box">👨‍🏫</div>
    Staff Portal
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <?php foreach ($nav as $page => $item): ?>
    <a href="<?= $page ?>.php" class="nav-item <?= $current_page === $page ? 'active' : '' ?>">
      <span class="nav-icon"><?= $item['icon'] ?></span>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar"><?= strtoupper(substr($staff_name, 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($staff_name) ?></div>
        <div class="user-role">Staff</div>
      </div>
    </div>
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <h1><?= $page_title ?></h1>
      <div class="breadcrumb">Staff Portal / <span><?= $page_title ?></span></div>
    </div>
  </div>
  <div class="page-content">
