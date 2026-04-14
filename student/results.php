<?php
// student/results.php — Full result card
require_once '../includes/config.php';
requireStudent();
$db = getDB();
$sid = $_SESSION['student_id'];
$student = $db->query("SELECT * FROM students WHERE id=$sid")->fetch_assoc();

$filter_type = sanitize($_GET['type'] ?? 'Final');

$marks = $db->query("
    SELECT m.*, sub.name as subject, sub.pass_marks
    FROM marks m JOIN subjects sub ON m.subject_id=sub.id
    WHERE m.student_id=$sid AND m.exam_type='$filter_type'
    ORDER BY sub.name
");
$all = [];
while ($r = $marks->fetch_assoc()) $all[] = $r;

$total_obt = array_sum(array_column($all,'marks_obtained'));
$total_max = array_sum(array_column($all,'max_marks'));
$overall   = $total_max > 0 ? round($total_obt/$total_max*100,1) : 0;
$grade     = getGrade($overall);
$passed    = empty(array_filter($all, fn($m) => $m['marks_obtained'] < $m['pass_marks']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Results — EduTrack</title>
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
.result-card{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;margin-bottom:20px;}
.result-header{background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(79,142,247,.1));padding:30px;border-bottom:1px solid var(--border);text-align:center;}
table{width:100%;border-collapse:collapse;}
th{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);padding:12px 20px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:13px 20px;font-size:.88rem;border-bottom:1px solid rgba(30,45,66,.5);}
tr:last-child td{border-bottom:none;}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:100px;font-size:.73rem;font-weight:600;}
.progress{height:5px;background:var(--border);border-radius:10px;overflow:hidden;}
.progress-bar{height:100%;border-radius:10px;}
.type-tabs{display:flex;gap:8px;margin-bottom:20px;}
.type-tab{padding:8px 18px;border-radius:10px;text-decoration:none;font-size:.88rem;font-weight:500;border:1px solid var(--border);color:var(--muted);transition:all .2s;}
.type-tab.active,.type-tab:hover{background:rgba(16,185,129,.15);color:var(--accent);border-color:rgba(16,185,129,.3);}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sidebar-logo"><div class="logo-box">🎒</div> My Portal</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item">⬡ Dashboard</a>
    <a href="attendance.php" class="nav-item">◎ My Attendance</a>
    <a href="marks.php" class="nav-item">◈ My Marks</a>
    <a href="results.php" class="nav-item active">⬡ Results</a>
    <a href="notices.php" class="nav-item">◆ Notices</a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>

<div class="main">
  <div style="margin-bottom:24px;">
    <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;">🎖 My Results</h1>
    <p style="color:var(--muted);margin-top:4px;">Official result card</p>
  </div>

  <div class="type-tabs">
    <?php foreach (['Unit Test','Mid Term','Final','Assignment'] as $t): ?>
    <a href="?type=<?= urlencode($t) ?>" class="type-tab <?= $filter_type===$t?'active':'' ?>"><?= $t ?></a>
    <?php endforeach; ?>
  </div>

  <div class="result-card">
    <div class="result-header">
      <div style="font-size:2rem;margin-bottom:8px;">🎓</div>
      <div style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;"><?= htmlspecialchars($student['name']) ?></div>
      <div style="color:var(--muted);font-size:.85rem;margin-top:4px;"><?= $student['student_id'] ?> · <?= $student['class'] ?> – <?= $student['section'] ?></div>
      <div style="margin-top:16px;display:flex;justify-content:center;gap:24px;">
        <div style="text-align:center;">
          <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= getGradeColor($grade) ?>;"><?= $overall ?>%</div>
          <div style="font-size:.8rem;color:var(--muted);">Overall</div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= getGradeColor($grade) ?>;"><?= $grade ?></div>
          <div style="font-size:.8rem;color:var(--muted);">Grade</div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= $passed?'var(--accent)':'var(--red)' ?>;"><?= $passed?'PASS':'FAIL' ?></div>
          <div style="font-size:.8rem;color:var(--muted);">Result</div>
        </div>
      </div>
    </div>

    <table>
      <thead><tr><th>Subject</th><th>Marks</th><th>Max</th><th>%</th><th>Grade</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($all as $m):
          $pct = round($m['marks_obtained']/$m['max_marks']*100,1);
          $gc = getGradeColor($m['grade']);
          $pass = $m['marks_obtained'] >= $m['pass_marks'];
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($m['subject']) ?></td>
          <td><?= $m['marks_obtained'] ?></td>
          <td><?= $m['max_marks'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress" style="width:50px;"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $gc ?>;"></div></div>
              <?= $pct ?>%
            </div>
          </td>
          <td><span class="badge" style="background:<?= $gc ?>22;color:<?= $gc ?>;"><?= $m['grade'] ?></span></td>
          <td>
            <span class="badge" style="background:<?= $pass?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>;color:<?= $pass?'var(--accent)':'var(--red)' ?>;">
              <?= $pass?'Pass':'Fail' ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($all): ?>
        <tr style="background:rgba(16,185,129,.05);">
          <td style="font-weight:700;">TOTAL</td>
          <td style="font-weight:700;"><?= $total_obt ?></td>
          <td style="font-weight:700;"><?= $total_max ?></td>
          <td style="font-weight:700;"><?= $overall ?>%</td>
          <td><span class="badge" style="background:<?= getGradeColor($grade) ?>22;color:<?= getGradeColor($grade) ?>;"><?= $grade ?></span></td>
          <td><span class="badge" style="background:<?= $passed?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>;color:<?= $passed?'var(--accent)':'var(--red)' ?>"><?= $passed?'PASS':'FAIL' ?></span></td>
        </tr>
        <?php else: ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">No results for <?= $filter_type ?> exam</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>