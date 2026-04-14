<?php
require_once '../includes/config.php';
require_once '../includes/admin_header.php';

$db = getDB();

// Stats
$total_students = $db->query("SELECT COUNT(*) as c FROM students WHERE status='Active'")->fetch_assoc()['c'];
$total_subjects = $db->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];
$today = date('Y-m-d');
$today_present = $db->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
$avg_marks = $db->query("SELECT AVG(marks_obtained/max_marks*100) as avg FROM marks")->fetch_assoc()['avg'];
$avg_marks = round($avg_marks ?? 0, 1);

// Attendance trend — last 7 days
$att_labels = []; $att_present = []; $att_absent = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $att_labels[] = date('D', strtotime($d));
    $p = $db->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Present'")->fetch_assoc()['c'];
    $a = $db->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Absent'")->fetch_assoc()['c'];
    $att_present[] = (int)$p;
    $att_absent[] = (int)$a;
}

// Class-wise student distribution
$class_data = $db->query("SELECT class, COUNT(*) as cnt FROM students WHERE status='Active' GROUP BY class ORDER BY class");
$class_labels = []; $class_counts = [];
while ($row = $class_data->fetch_assoc()) {
    $class_labels[] = $row['class'];
    $class_counts[] = (int)$row['cnt'];
}

// Subject-wise average marks
$subj_marks = $db->query("
    SELECT s.name, AVG(m.marks_obtained) as avg
    FROM marks m JOIN subjects s ON m.subject_id=s.id
    GROUP BY m.subject_id, s.name
    ORDER BY avg DESC LIMIT 6
");
$subj_labels = []; $subj_avgs = [];
while ($row = $subj_marks->fetch_assoc()) {
    $subj_labels[] = $row['name'];
    $subj_avgs[] = round($row['avg'], 1);
}

// Grade distribution
$grade_data = $db->query("SELECT grade, COUNT(*) as cnt FROM marks GROUP BY grade ORDER BY grade");
$grade_labels = []; $grade_counts = []; $grade_colors = [];
while ($row = $grade_data->fetch_assoc()) {
    $grade_labels[] = $row['grade'];
    $grade_counts[] = (int)$row['cnt'];
    $grade_colors[] = getGradeColor($row['grade']);
}

// Recent students
$recent_students = $db->query("SELECT name, student_id, class, section, created_at FROM students ORDER BY created_at DESC LIMIT 6");

// Notices
$notices = $db->query("SELECT title, created_at FROM notices ORDER BY created_at DESC LIMIT 4");
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue">👥</div>
    <div class="stat-val"><?= $total_students ?></div>
    <div class="stat-label">Total Students</div>
    <span class="stat-trend up">↑ Active</span>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">✅</div>
    <div class="stat-val"><?= $today_present ?></div>
    <div class="stat-label">Present Today</div>
    <span class="stat-trend up">↑ Today</span>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon purple">📚</div>
    <div class="stat-val"><?= $total_subjects ?></div>
    <div class="stat-label">Subjects</div>
    <span class="stat-trend up">All Classes</span>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">🏆</div>
    <div class="stat-val"><?= $avg_marks ?>%</div>
    <div class="stat-label">Avg Performance</div>
    <span class="stat-trend <?= $avg_marks >= 70 ? 'up' : 'down' ?>"><?= $avg_marks >= 70 ? '↑ Good' : '↓ Needs Attn' ?></span>
  </div>
</div>

<!-- Charts Row 1 -->
<div class="grid-3" style="margin-bottom:20px;">
  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Attendance Trend</div>
        <div class="panel-sub">Last 7 days — Present vs Absent</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:240px;">
        <canvas id="attendanceChart"></canvas>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Students by Class</div>
        <div class="panel-sub">Distribution overview</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:240px;">
        <canvas id="classChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Charts Row 2 -->
<div class="grid-2" style="margin-bottom:20px;">
  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Subject-wise Performance</div>
        <div class="panel-sub">Average marks per subject</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:220px;">
        <canvas id="subjectChart"></canvas>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Grade Distribution</div>
        <div class="panel-sub">All exams combined</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:220px;">
        <canvas id="gradeChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row -->
<div class="grid-2">
  <!-- Recent Students -->
  <div class="panel">
    <div class="panel-head" style="padding-bottom:16px;">
      <div>
        <div class="panel-title">Recent Enrollments</div>
        <div class="panel-sub">Newly added students</div>
      </div>
      <a href="students.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Student</th><th>ID</th><th>Class</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php while ($s = $recent_students->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:30px;height:30px;font-size:.75rem;"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                <span><?= htmlspecialchars($s['name']) ?></span>
              </div>
            </td>
            <td><span class="badge badge-blue"><?= $s['student_id'] ?></span></td>
            <td><?= $s['class'] ?> - <?= $s['section'] ?></td>
            <td style="color:var(--muted);font-size:.8rem;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Notices -->
  <div class="panel">
    <div class="panel-head" style="padding-bottom:16px;">
      <div>
        <div class="panel-title">Recent Notices</div>
        <div class="panel-sub">Latest announcements</div>
      </div>
      <a href="notices.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <div class="panel-body" style="padding-top:0;">
      <?php while ($n = $notices->fetch_assoc()): ?>
      <div style="padding:14px 0;border-bottom:1px solid var(--border);">
        <div style="font-size:.88rem;font-weight:500;margin-bottom:4px;"><?= htmlspecialchars($n['title']) ?></div>
        <div style="font-size:.75rem;color:var(--muted);"><?= date('d M Y, g:i A', strtotime($n['created_at'])) ?></div>
      </div>
      <?php endwhile; ?>
      <div style="margin-top:16px;">
        <a href="notices.php?action=add" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">+ Post New Notice</a>
      </div>
    </div>
  </div>
</div>

<!-- Quick Links -->
<div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
  <a href="students.php?action=add" class="btn btn-primary">+ Add Student</a>
  <a href="attendance.php" class="btn btn-outline">📋 Mark Attendance</a>
  <a href="marks.php?action=add" class="btn btn-outline">📝 Enter Marks</a>
  <a href="reports.php" class="btn btn-outline">📊 View Reports</a>
</div>

<script>
const chartDefaults = {
  plugins: { legend: { labels: { color: '#94a3b8', font: { family: 'DM Sans', size: 12 } } } },
  scales: {
    x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(30,45,66,.8)' } },
    y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(30,45,66,.8)' } }
  }
};

// Attendance Chart
new Chart(document.getElementById('attendanceChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($att_labels) ?>,
    datasets: [
      { label: 'Present', data: <?= json_encode($att_present) ?>,
        backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 6, borderSkipped: false },
      { label: 'Absent', data: <?= json_encode($att_absent) ?>,
        backgroundColor: 'rgba(239,68,68,.5)', borderRadius: 6, borderSkipped: false }
    ]
  },
  options: { ...chartDefaults, responsive: true, maintainAspectRatio: false,
    plugins: { ...chartDefaults.plugins, legend: { ...chartDefaults.plugins.legend, position: 'top' } }
  }
});

// Class Distribution — Doughnut
new Chart(document.getElementById('classChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($class_labels) ?>,
    datasets: [{
      data: <?= json_encode($class_counts) ?>,
      backgroundColor: ['#4f8ef7','#7c3aed','#10b981','#f59e0b','#ef4444','#f97316'],
      borderWidth: 0, hoverOffset: 8
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, cutout: '65%',
    plugins: { legend: { position: 'right', labels: { color: '#94a3b8', padding: 14, font: { size: 12 } } } }
  }
});

// Subject Performance — Horizontal Bar
new Chart(document.getElementById('subjectChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($subj_labels) ?>,
    datasets: [{
      label: 'Avg Marks',
      data: <?= json_encode($subj_avgs) ?>,
      backgroundColor: 'rgba(79,142,247,.7)',
      borderRadius: 6,
    }]
  },
  options: {
    ...chartDefaults, responsive: true, maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { min: 0, max: 100, ticks: { color: '#64748b' }, grid: { color: 'rgba(30,45,66,.8)' } },
      y: { ticks: { color: '#94a3b8' }, grid: { display: false } }
    }
  }
});

// Grade Distribution — Pie
new Chart(document.getElementById('gradeChart'), {
  type: 'pie',
  data: {
    labels: <?= json_encode($grade_labels) ?>,
    datasets: [{
      data: <?= json_encode($grade_counts) ?>,
      backgroundColor: <?= json_encode($grade_colors) ?>,
      borderWidth: 0, hoverOffset: 6
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'right', labels: { color: '#94a3b8', padding: 14, font: { size: 12 } } } }
  }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>