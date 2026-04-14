<?php
// student/attendance.php
require_once '../includes/config.php';
requireStudent();
$db = getDB();
$sid = $_SESSION['student_id'];

$records = $db->query("
    SELECT a.*, sub.name as subject
    FROM attendance a JOIN subjects sub ON a.subject_id=sub.id
    WHERE a.student_id=$sid ORDER BY a.date DESC LIMIT 60
");
$all = [];
while ($r = $records->fetch_assoc()) $all[] = $r;

$total   = count($all);
$present = count(array_filter($all, fn($r) => $r['status']==='Present'));
$absent  = count(array_filter($all, fn($r) => $r['status']==='Absent'));
$late    = count(array_filter($all, fn($r) => $r['status']==='Late'));
$pct     = $total > 0 ? round($present/$total*100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Attendance — EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#080c14;--surface:#0e1520;--card:#111827;--border:#1e2d42;--accent:#10b981;--blue:#4f8ef7;--gold:#f59e0b;--red:#ef4444;--text:#e2e8f0;--muted:#64748b;--sidebar-w:240px;}
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
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;text-align:center;}
.stat-val{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;margin-bottom:4px;}
.stat-label{font-size:.78rem;color:var(--muted);}
.panel{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.panel-head{padding:18px 22px;border-bottom:1px solid var(--border);}
.panel-title{font-family:'Syne',sans-serif;font-weight:700;}
table{width:100%;border-collapse:collapse;}
th{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:12px 16px;font-size:.87rem;border-bottom:1px solid rgba(30,45,66,.5);}
tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:100px;font-size:.73rem;font-weight:600;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo"><div class="logo-box">🎒</div> My Portal</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item">⬡ Dashboard</a>
    <a href="attendance.php" class="nav-item active">◎ My Attendance</a>
    <a href="marks.php" class="nav-item">◈ My Marks</a>
    <a href="results.php" class="nav-item">⬡ Results</a>
    <a href="notices.php" class="nav-item">◆ Notices</a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>
<div class="main">
  <div style="margin-bottom:24px;">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;">📅 My Attendance</h1>
    <p style="color:var(--muted);margin-top:4px;">Last 60 attendance records</p>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="stat-val" style="color:var(--accent);"><?= $pct ?>%</div><div class="stat-label">Overall Rate</div></div>
    <div class="stat-card"><div class="stat-val" style="color:var(--accent);"><?= $present ?></div><div class="stat-label">Present</div></div>
    <div class="stat-card"><div class="stat-val" style="color:var(--red);"><?= $absent ?></div><div class="stat-label">Absent</div></div>
    <div class="stat-card"><div class="stat-val" style="color:var(--gold);"><?= $late ?></div><div class="stat-label">Late</div></div>
  </div>

  <?php if ($pct < 75): ?>
  <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:14px 18px;margin-bottom:20px;color:#fca5a5;font-size:.88rem;">
    ⚠️ Your attendance is below 75%. Maintain at least 75% attendance to appear in examinations.
  </div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-head"><div class="panel-title">Attendance Log</div></div>
    <table>
      <thead><tr><th>Date</th><th>Subject</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($all as $r): ?>
        <tr>
          <td><?= date('d M Y, D', strtotime($r['date'])) ?></td>
          <td><?= htmlspecialchars($r['subject']) ?></td>
          <td>
            <?php if ($r['status']==='Present'): ?>
              <span class="badge" style="background:rgba(16,185,129,.12);color:var(--accent);">✓ Present</span>
            <?php elseif ($r['status']==='Absent'): ?>
              <span class="badge" style="background:rgba(239,68,68,.12);color:var(--red);">✗ Absent</span>
            <?php else: ?>
              <span class="badge" style="background:rgba(245,158,11,.12);color:var(--gold);">⏰ Late</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>