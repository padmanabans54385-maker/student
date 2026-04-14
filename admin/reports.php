<?php
require_once '../includes/config.php';
$db = getDB();

// Top performers
$top_students = $db->query("
    SELECT s.name, s.student_id as uid, CONCAT(s.year,' ',s.degree) as class,
           AVG(m.marks_obtained/m.max_marks*100) as avg_pct,
           COUNT(m.id) as exams
    FROM marks m JOIN students s ON m.student_id=s.id
    GROUP BY m.student_id
    ORDER BY avg_pct DESC LIMIT 8
");

// Attendance rate by class
$att_by_class = $db->query("
    SELECT CONCAT(s.year,' ',s.degree) as class,
           COUNT(CASE WHEN a.status='Present' THEN 1 END) as present,
           COUNT(a.id) as total
    FROM attendance a JOIN students s ON a.student_id=s.id
    GROUP BY s.year, s.degree ORDER BY s.year, s.degree
");
$att_labels = []; $att_rates = [];
while ($r = $att_by_class->fetch_assoc()) {
    $att_labels[] = $r['class'];
    $att_rates[]  = $r['total'] > 0 ? round($r['present']/$r['total']*100,1) : 0;
}

// Monthly marks trend
$monthly = $db->query("
    SELECT DATE_FORMAT(exam_date,'%b %Y') as mo,
           AVG(marks_obtained/max_marks*100) as avg
    FROM marks WHERE exam_date IS NOT NULL
    GROUP BY DATE_FORMAT(exam_date,'%Y-%m')
    ORDER BY MIN(exam_date) DESC LIMIT 6
");
$mo_labels = []; $mo_avgs = [];
$rows = [];
while ($r = $monthly->fetch_assoc()) $rows[] = $r;
foreach (array_reverse($rows) as $r) {
    $mo_labels[] = $r['mo'];
    $mo_avgs[]   = round($r['avg'],1);
}

require_once '../includes/admin_header.php';
?>

<div class="grid-2" style="margin-bottom:20px;">
  <!-- Attendance by class -->
  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Attendance Rate by Class</div>
        <div class="panel-sub">% Present across all recorded dates</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:260px;">
        <canvas id="attByClassChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Monthly trend -->
  <div class="panel">
    <div class="panel-head">
      <div>
        <div class="panel-title">Performance Trend</div>
        <div class="panel-sub">Average marks % over time</div>
      </div>
    </div>
    <div class="panel-body">
      <div class="chart-container" style="height:260px;">
        <canvas id="trendChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Top Performers -->
<div class="panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">🏆 Top Performers</div>
      <div class="panel-sub">Ranked by average exam score</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Rank</th><th>Student</th><th>Class</th><th>Avg Score</th><th>Exams</th><th>Grade</th><th>Performance</th></tr>
      </thead>
      <tbody>
        <?php $rank = 1; while ($s = $top_students->fetch_assoc()):
          $grade = getGrade($s['avg_pct']);
          $color = getGradeColor($grade);
        ?>
        <tr>
          <td>
            <span style="font-family:'Syne',sans-serif;font-weight:800;color:<?= $rank<=3?'var(--gold)':'var(--muted)' ?>;">
              <?= $rank<=3 ? ['🥇','🥈','🥉'][$rank-1] : "#$rank" ?>
            </span>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar" style="width:32px;height:32px;font-size:.8rem;background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>88);">
                <?= strtoupper(substr($s['name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:.75rem;color:var(--muted);"><?= $s['uid'] ?></div>
              </div>
            </div>
          </td>
          <td><?= $s['class'] ?></td>
          <td>
            <span style="font-family:'Syne',sans-serif;font-weight:700;color:<?= $color ?>;">
              <?= round($s['avg_pct'],1) ?>%
            </span>
          </td>
          <td><?= $s['exams'] ?> exams</td>
          <td>
            <span class="badge" style="background:<?= $color ?>22;color:<?= $color ?>;"><?= $grade ?></span>
          </td>
          <td style="width:200px;">
            <div class="progress">
              <div class="progress-bar" style="width:<?= min($s['avg_pct'],100) ?>%;background:<?= $color ?>;"></div>
            </div>
          </td>
        </tr>
        <?php $rank++; endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const gridColor = 'rgba(30,45,66,.8)';
const tickColor = '#64748b';

// Attendance by class
new Chart(document.getElementById('attByClassChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($att_labels) ?>,
    datasets: [{
      label: 'Attendance %',
      data: <?= json_encode($att_rates) ?>,
      backgroundColor: <?= json_encode(array_map(fn($r) => $r >= 75 ? 'rgba(16,185,129,.7)' : ($r >= 50 ? 'rgba(245,158,11,.7)' : 'rgba(239,68,68,.7)'), $att_rates)) ?>,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { min: 0, max: 100, ticks: { color: tickColor, callback: v => v+'%' }, grid: { color: gridColor } },
      x: { ticks: { color: tickColor }, grid: { display: false } }
    }
  }
});

// Trend line
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($mo_labels) ?>,
    datasets: [{
      label: 'Avg Score %',
      data: <?= json_encode($mo_avgs) ?>,
      borderColor: '#4f8ef7',
      backgroundColor: 'rgba(79,142,247,.08)',
      borderWidth: 2.5,
      pointBackgroundColor: '#4f8ef7',
      pointRadius: 5,
      fill: true, tension: 0.4,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { min: 0, max: 100, ticks: { color: tickColor, callback: v => v+'%' }, grid: { color: gridColor } },
      x: { ticks: { color: tickColor }, grid: { color: gridColor } }
    }
  }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>