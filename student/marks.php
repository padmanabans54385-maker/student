<?php
require_once '../includes/config.php';
requireStudent();
$db = getDB();
$sid = $_SESSION['student_id'];

$marks = $db->query("
    SELECT m.*, sub.name as subject, sub.pass_marks
    FROM marks m JOIN subjects sub ON m.subject_id=sub.id
    WHERE m.student_id=$sid ORDER BY m.exam_date DESC, m.id DESC
");
$all = [];
while ($r = $marks->fetch_assoc()) $all[] = $r;

// Group by subject
$by_subject = [];
foreach ($all as $m) {
    $by_subject[$m['subject']][] = $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Marks — EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--blue));display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;}
.btn-logout{display:flex;align-items:center;gap:8px;justify-content:center;padding:9px;border-radius:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#fca5a5;font-size:.85rem;text-decoration:none;width:100%;}
.main{margin-left:var(--sidebar-w);padding:32px;}
.topbar{margin-bottom:28px;}
.topbar h1{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;}
.topbar p{color:var(--muted);font-size:.9rem;margin-top:4px;}
.panel{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:20px;}
.panel-head{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-family:'Syne',sans-serif;font-weight:700;}
table{width:100%;border-collapse:collapse;}
th{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:12px 16px;font-size:.87rem;border-bottom:1px solid rgba(30,45,66,.5);}
tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:100px;font-size:.73rem;font-weight:600;}
.progress{height:6px;background:var(--border);border-radius:10px;overflow:hidden;}
.progress-bar{height:100%;border-radius:10px;}
.subject-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:24px;}
.subject-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo"><div class="logo-box">🎒</div> My Portal</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item">⬡ Dashboard</a>
    <a href="attendance.php" class="nav-item">◎ My Attendance</a>
    <a href="marks.php" class="nav-item active">◈ My Marks</a>
    <a href="results.php" class="nav-item">⬡ Results</a>
    <a href="notices.php" class="nav-item">◆ Notices</a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1>📝 My Marks</h1>
    <p>All examination results — <?= count($all) ?> records</p>
  </div>

  <!-- Subject summary cards -->
  <div class="subject-grid">
    <?php foreach ($by_subject as $subj => $entries):
      $avg = round(array_sum(array_map(fn($m) => $m['marks_obtained']/$m['max_marks']*100, $entries)) / count($entries), 1);
      $grade = getGrade($avg);
      $color = getGradeColor($grade);
    ?>
    <div class="subject-card">
      <div style="font-size:.78rem;color:var(--muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.07em;"><?= htmlspecialchars($subj) ?></div>
      <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:<?= $color ?>;"><?= $avg ?>%</div>
      <div style="font-size:.78rem;color:var(--muted);margin-bottom:10px;"><?= count($entries) ?> exam<?= count($entries)>1?'s':'' ?></div>
      <div class="progress"><div class="progress-bar" style="width:<?= $avg ?>%;background:<?= $color ?>;"></div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Detailed table -->
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Exam Records</div>
    </div>
    <table>
      <thead><tr><th>Subject</th><th>Exam Type</th><th>Marks</th><th>%</th><th>Grade</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($all as $m):
          $pct = round($m['marks_obtained']/$m['max_marks']*100,1);
          $grade = $m['grade'];
          $color = getGradeColor($grade);
          $pass = $m['marks_obtained'] >= $m['pass_marks'];
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($m['subject']) ?></td>
          <td><span class="badge" style="background:rgba(79,142,247,.12);color:var(--blue);"><?= $m['exam_type'] ?></span></td>
          <td><?= $m['marks_obtained'] ?> / <?= $m['max_marks'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress" style="width:50px;"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
              <span style="font-size:.82rem;"><?= $pct ?>%</span>
            </div>
          </td>
          <td><span class="badge" style="background:<?= $color ?>22;color:<?= $color ?>;"><?= $grade ?></span></td>
          <td>
            <span class="badge" style="background:<?= $pass?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>;color:<?= $pass?'var(--accent)':'var(--red)' ?>;">
              <?= $pass ? 'Pass' : 'Fail' ?>
            </span>
          </td>
          <td style="font-size:.8rem;color:var(--muted);"><?= $m['exam_date'] ? date('d M Y', strtotime($m['exam_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>