<?php
// student/notices.php
require_once '../includes/config.php';
requireStudent();
$db = getDB();

$notices = $db->query("SELECT n.*, a.name as author FROM notices n LEFT JOIN admins a ON n.posted_by=a.id WHERE target IN ('All','Students') ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Notices — EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#080c14;--surface:#0e1520;--card:#111827;--border:#1e2d42;--accent:#10b981;--blue:#4f8ef7;--text:#e2e8f0;--muted:#64748b;--sidebar-w:240px;}
html,body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;}
.sidebar-logo{padding:28px 24px 24px;border-bottom:1px solid var(--border);font-family:'Syne',sans-serif;font-weight:800;display:flex;align-items:center;gap:10px;}
.logo-box{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--blue));border-radius:8px;display:flex;align-items:center;justify-content:center;}
.sidebar-nav{flex:1;padding:16px 12px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:var(--muted);font-size:.9rem;font-weight:500;transition:all .2s;margin-bottom:2px;}
.nav-item:hover{background:rgba(16,185,129,.08);color:var(--text);}
.nav-item.active{background:rgba(16,185,129,.15);color:var(--accent);border:1px solid rgba(16,185,129,.2);}
.sidebar-footer{padding:16px 12px;border-top:1px solid var(--border);}
.btn-logout{display:flex;align-items:center;gap:8px;justify-content:center;padding:9px;border-radius:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#fca5a5;font-size:.85rem;text-decoration:none;width:100%;}
.main{margin-left:var(--sidebar-w);padding:32px;}
.notice-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:16px;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo"><div class="logo-box">🎒</div> My Portal</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item">⬡ Dashboard</a>
    <a href="attendance.php" class="nav-item">◎ My Attendance</a>
    <a href="marks.php" class="nav-item">◈ My Marks</a>
    <a href="results.php" class="nav-item">⬡ Results</a>
    <a href="notices.php" class="nav-item active">◆ Notices</a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>
<div class="main">
  <div style="margin-bottom:24px;">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;">📢 Notices</h1>
    <p style="color:var(--muted);margin-top:4px;">School announcements</p>
  </div>
  <?php while ($n = $notices->fetch_assoc()): ?>
  <div class="notice-card">
    <div style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:8px;"><?= htmlspecialchars($n['title']) ?></div>
    <p style="font-size:.88rem;color:var(--muted);line-height:1.7;margin-bottom:12px;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
    <div style="font-size:.75rem;color:var(--muted);">
      📅 <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?> &nbsp;·&nbsp; 👤 <?= htmlspecialchars($n['author'] ?? 'Admin') ?>
    </div>
  </div>
  <?php endwhile; ?>
</div>
</body>
</html>