<?php
require_once '../includes/config.php';
requireStudent();

$db = getDB();
$sid = $_SESSION['student_id'];
$student = $db->query("SELECT * FROM students WHERE id=$sid")->fetch_assoc();
$class   = $student['year'] . ' ' . $student['degree'];

// Attendance stats
$att_total   = $db->query("SELECT COUNT(*) as c FROM attendance WHERE student_id=$sid")->fetch_assoc()['c'];
$att_present = $db->query("SELECT COUNT(*) as c FROM attendance WHERE student_id=$sid AND status='Present'")->fetch_assoc()['c'];
$att_pct     = $att_total > 0 ? round($att_present/$att_total*100) : 0;

// Marks summary
$marks_data = $db->query("
    SELECT sub.name as subject, m.marks_obtained, m.max_marks, m.grade, m.exam_type, m.exam_date
    FROM marks m JOIN subjects sub ON m.subject_id=sub.id
    WHERE m.student_id=$sid
    ORDER BY m.exam_date DESC
");
$all_marks = [];
while ($r = $marks_data->fetch_assoc()) $all_marks[] = $r;

$total_obtained = array_sum(array_column($all_marks,'marks_obtained'));
$total_max      = array_sum(array_column($all_marks,'max_marks'));
$overall_pct    = $total_max > 0 ? round($total_obtained/$total_max*100,1) : 0;
$overall_grade  = getGrade($overall_pct);

// Subject-wise performance (for radar chart)
$subj_perf = $db->query("
    SELECT sub.name, AVG(m.marks_obtained/m.max_marks*100) as avg
    FROM marks m JOIN subjects sub ON m.subject_id=sub.id
    WHERE m.student_id=$sid GROUP BY sub.name
");
$s_labels=[]; $s_data=[];
while ($r = $subj_perf->fetch_assoc()) { $s_labels[]=$r['name']; $s_data[]=round($r['avg'],1); }

// Attendance weekly (last 14 days)
$att_weekly = [];
for ($i=13;$i>=0;$i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $r = $db->query("SELECT status FROM attendance WHERE student_id=$sid AND date='$d' LIMIT 1")->fetch_assoc();
    $att_weekly[] = ['date'=>$d,'label'=>date('d/m',strtotime($d)),'status'=>$r['status']??null];
}

// Notices
$notices = $db->query("SELECT * FROM notices WHERE target IN ('All','Students') ORDER BY created_at DESC LIMIT 5");

$page_title = 'My Dashboard';
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portal — EduTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#080c14;--surface:#0e1520;--card:#111827;--card2:#141f30;
  --border:#1e2d42;--accent:#10b981;--accent2:#059669;--blue:#4f8ef7;
  --gold:#f59e0b;--red:#ef4444;--text:#e2e8f0;--muted:#64748b;
  --sidebar-w:240px;
}
html,body{height:100%;font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);}

.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:100;}
.sidebar-logo{padding:28px 24px 24px;border-bottom:1px solid var(--border);font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;display:flex;align-items:center;gap:10px;}
.logo-box{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--blue));border-radius:8px;display:flex;align-items:center;justify-content:center;}
.sidebar-nav{flex:1;padding:16px 12px;overflow-y:auto;}
.nav-label{font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:0 12px;margin:16px 0 8px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:var(--muted);font-size:.9rem;font-weight:500;transition:all .2s;margin-bottom:2px;}
.nav-item:hover{background:rgba(16,185,129,.08);color:var(--text);}
.nav-item.active{background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(79,142,247,.1));color:var(--accent);border:1px solid rgba(16,185,129,.2);}
.sidebar-footer{padding:16px 12px;border-top:1px solid var(--border);}
.user-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--card);border-radius:10px;margin-bottom:8px;}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--blue));display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0;}
.user-name{font-size:.85rem;font-weight:600;}
.user-role{font-size:.72rem;color:var(--muted);}
.btn-logout{display:flex;align-items:center;gap:8px;justify-content:center;padding:9px;border-radius:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#fca5a5;font-size:.85rem;font-weight:500;text-decoration:none;transition:all .2s;width:100%;}
.btn-logout:hover{background:rgba(239,68,68,.18);}
.main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 32px;height:65px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h1{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;}
.breadcrumb{font-size:.78rem;color:var(--muted);margin-top:2px;}
.breadcrumb span{color:var(--accent);}
.page-content{padding:32px;flex:1;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:transform .2s;}
.stat-card:hover{transform:translateY(-2px);}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat-card.green::before{background:linear-gradient(90deg,var(--accent),#34d399);}
.stat-card.blue::before{background:linear-gradient(90deg,var(--blue),#60a5fa);}
.stat-card.gold::before{background:linear-gradient(90deg,var(--gold),#fbbf24);}
.stat-card.red::before{background:linear-gradient(90deg,var(--red),#f87171);}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:14px;}
.stat-icon.green{background:rgba(16,185,129,.12);}
.stat-icon.blue{background:rgba(79,142,247,.12);}
.stat-icon.gold{background:rgba(245,158,11,.12);}
.stat-icon.red{background:rgba(239,68,68,.12);}
.stat-val{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;line-height:1;margin-bottom:5px;}
.stat-label{font-size:.8rem;color:var(--muted);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.grid-3{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;}
.panel{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
.panel-head{padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;}
.panel-title{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;}
.panel-sub{font-size:.78rem;color:var(--muted);margin-top:2px;}
.panel-body{padding:20px 24px 24px;}
table{width:100%;border-collapse:collapse;}
th{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:12px 16px;font-size:.87rem;border-bottom:1px solid rgba(30,45,66,.5);}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(16,185,129,.04);}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:100px;font-size:.73rem;font-weight:600;}
.badge-green{background:rgba(16,185,129,.12);color:var(--accent);}
.badge-red{background:rgba(239,68,68,.12);color:var(--red);}
.badge-gold{background:rgba(245,158,11,.12);color:var(--gold);}
.badge-blue{background:rgba(79,142,247,.12);color:var(--blue);}
.progress{height:6px;background:var(--border);border-radius:10px;overflow:hidden;}
.progress-bar{height:100%;border-radius:10px;transition:width .6s;}

/* Attendance dots */
.att-dots{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;}
.att-dot{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.6rem;cursor:default;}
.att-dot.present{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);}
.att-dot.absent{background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);}
.att-dot.late{background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.4);}
.att-dot.none{background:rgba(30,45,66,.5);border:1px solid var(--border);}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-box">🎒</div>
    My Portal
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu</div>
    <a href="dashboard.php" class="nav-item active">⬡ Dashboard</a>
    <a href="attendance.php" class="nav-item">◎ My Attendance</a>
    <a href="marks.php" class="nav-item">◈ My Marks</a>
    <a href="results.php" class="nav-item">⬡ Results</a>
    <a href="notices.php" class="nav-item">◆ Notices</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar"><?= strtoupper(substr($student['name'],0,1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($student['name']) ?></div>
        <div class="user-role"><?= $student['year'] ?> <?= $student['degree'] ?></div>
      </div>
    </div>
    <a href="../logout.php" class="btn-logout">⬡ Logout</a>
  </div>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div>
      <h1>Welcome back, <?= htmlspecialchars(explode(' ',$student['name'])[0]) ?>! 👋</h1>
      <div class="breadcrumb">Student Portal / <span>Dashboard</span></div>
    </div>
    <div style="font-size:.82rem;color:var(--muted);"><?= $student['student_id'] ?> · <?= $student['year'] ?> <?= $student['degree'] ?></div>
  </div>

  <div class="page-content">
    <!-- Stats -->
    <div class="stat-grid">
      <div class="stat-card <?= $att_pct>=75?'green':'red' ?>">
        <div class="stat-icon <?= $att_pct>=75?'green':'red' ?>">📅</div>
        <div class="stat-val"><?= $att_pct ?>%</div>
        <div class="stat-label">Attendance Rate</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-icon blue">📝</div>
        <div class="stat-val"><?= count($all_marks) ?></div>
        <div class="stat-label">Exams Taken</div>
      </div>
      <div class="stat-card gold">
        <div class="stat-icon gold">🏆</div>
        <div class="stat-val"><?= $overall_pct ?>%</div>
        <div class="stat-label">Overall Average</div>
      </div>
      <div class="stat-card <?= in_array($overall_grade,['A+','A','B+'])?'green':'gold' ?>">
        <div class="stat-icon <?= in_array($overall_grade,['A+','A','B+'])?'green':'gold' ?>">🎖</div>
        <div class="stat-val"><?= $overall_grade ?></div>
        <div class="stat-label">Overall Grade</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid-3">
      <div class="panel">
        <div class="panel-head">
          <div>
            <div class="panel-title">Subject Performance</div>
            <div class="panel-sub">Average score per subject</div>
          </div>
        </div>
        <div class="panel-body">
          <canvas id="subChart" style="height:240px;"></canvas>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <div>
            <div class="panel-title">Attendance</div>
            <div class="panel-sub">Last 14 days</div>
          </div>
        </div>
        <div class="panel-body">
          <div style="text-align:center;margin-bottom:16px;">
            <div style="font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;color:<?= $att_pct>=75?'var(--accent)':'var(--red)' ?>;"><?= $att_pct ?>%</div>
            <div style="font-size:.8rem;color:var(--muted);">Present Rate</div>
          </div>
          <div class="progress" style="height:8px;margin-bottom:20px;">
            <div class="progress-bar" style="width:<?= $att_pct ?>%;background:<?= $att_pct>=75?'var(--accent)':'var(--red)' ?>;"></div>
          </div>
          <div style="font-size:.78rem;color:var(--muted);margin-bottom:8px;">Last 14 days</div>
          <div class="att-dots">
            <?php foreach ($att_weekly as $d): ?>
            <div class="att-dot <?= $d['status'] ? strtolower($d['status']) : 'none' ?>"
                 title="<?= $d['date'] ?>: <?= $d['status'] ?: 'No Data' ?>">
              <?= $d['status']==='Present'?'✓':($d['status']==='Absent'?'✗':($d['status']==='Late'?'~':'·')) ?>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;gap:14px;margin-top:14px;font-size:.75rem;">
            <span style="color:var(--accent);">✓ Present</span>
            <span style="color:var(--red);">✗ Absent</span>
            <span style="color:var(--gold);">~ Late</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Marks + Notices -->
    <div class="grid-2">
      <div class="panel">
        <div class="panel-head" style="padding-bottom:16px;">
          <div><div class="panel-title">Recent Marks</div></div>
          <a href="marks.php" style="font-size:.82rem;color:var(--blue);text-decoration:none;">View All</a>
        </div>
        <table>
          <thead><tr><th>Subject</th><th>Type</th><th>Score</th><th>Grade</th></tr></thead>
          <tbody>
            <?php foreach (array_slice($all_marks,0,6) as $m):
              $pct = round($m['marks_obtained']/$m['max_marks']*100);
              $gc = getGradeColor($m['grade']);
            ?>
            <tr>
              <td><?= htmlspecialchars($m['subject']) ?></td>
              <td><span class="badge badge-blue" style="font-size:.7rem;"><?= $m['exam_type'] ?></span></td>
              <td><?= $m['marks_obtained'] ?>/<?= $m['max_marks'] ?></td>
              <td><span class="badge" style="background:<?= $gc ?>22;color:<?= $gc ?>;"><?= $m['grade'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-head" style="padding-bottom:16px;">
          <div><div class="panel-title">📢 Notices</div></div>
        </div>
        <div class="panel-body" style="padding-top:0;">
          <?php while ($n = $notices->fetch_assoc()): ?>
          <div style="padding:12px 0;border-bottom:1px solid var(--border);">
            <div style="font-size:.87rem;font-weight:500;margin-bottom:4px;"><?= htmlspecialchars($n['title']) ?></div>
            <div style="font-size:.75rem;color:var(--muted);"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const subLabels = <?= json_encode($s_labels) ?>;
const subData = <?= json_encode($s_data) ?>;
const barColors = subData.map(v => v >= 80 ? 'rgba(16,185,129,.75)' : v >= 60 ? 'rgba(79,142,247,.75)' : v >= 40 ? 'rgba(245,158,11,.75)' : 'rgba(239,68,68,.75)');

new Chart(document.getElementById('subChart'), {
  type: 'bar',
  data: {
    labels: subLabels,
    datasets: [{
      label: 'Score %',
      data: subData,
      backgroundColor: barColors,
      borderRadius: 8,
      borderSkipped: false,
      barThickness: subLabels.length <= 3 ? 36 : undefined,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ctx.parsed.x + '%' } }
    },
    scales: {
      x: {
        min: 0, max: 100,
        ticks: { color: '#64748b', callback: v => v + '%', stepSize: 20 },
        grid: { color: 'rgba(30,45,66,.6)' }
      },
      y: {
        ticks: { color: '#94a3b8', font: { size: 12, weight: 500 } },
        grid: { display: false }
      }
    }
  }
});
</script>
</body>
</html>