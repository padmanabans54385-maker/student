<?php
require_once '../includes/config.php';
$db = getDB();
$error = ''; $success = '';

// Save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'save') {
    $subject_id = (int)$_POST['subject_id'];
    $date       = $_POST['date'];
    $statuses   = $_POST['status'] ?? [];

    foreach ($statuses as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = in_array($status, ['Present','Absent','Late']) ? $status : 'Present';
        $stmt = $db->prepare("INSERT INTO attendance (student_id, subject_id, date, status, marked_by)
                               VALUES(?,?,?,?,?)
                               ON DUPLICATE KEY UPDATE status=VALUES(status)");
        $stmt->bind_param('iissi', $student_id, $subject_id, $date, $status, $_SESSION['staff_id']);
        $stmt->execute();
    }
    flash('success', 'Attendance saved for ' . date('d M Y', strtotime($date)));
    redirect('attendance.php?subject_id='.$subject_id.'&date='.$date);
}

$sel_subject = (int)($_GET['subject_id'] ?? 0);
$sel_date    = $_GET['date'] ?? date('Y-m-d');
$subjects = $db->query("SELECT * FROM subjects ORDER BY year, degree, name");

// Students for selected subject (match by year + degree)
$students_list = [];
$sel_class = '';
if ($sel_subject) {
    $subj = $db->query("SELECT year, degree FROM subjects WHERE id=$sel_subject")->fetch_assoc();
    if ($subj) {
        $sel_class = $subj['year'] . ' ' . $subj['degree'];
        $res = $db->query("SELECT * FROM students WHERE year='{$subj['year']}' AND degree='{$subj['degree']}' AND status='Active' ORDER BY name");
        while ($r = $res->fetch_assoc()) $students_list[] = $r;
    }
}

// Existing attendance
$existing = [];
if ($sel_subject && $sel_date && $students_list) {
    $res = $db->query("SELECT student_id, status FROM attendance WHERE subject_id=$sel_subject AND date='$sel_date'");
    while ($r = $res->fetch_assoc()) $existing[$r['student_id']] = $r['status'];
}

// Stats for summary
$att_summary = [];
if ($sel_date) {
    $res = $db->query("SELECT status, COUNT(*) as c FROM attendance WHERE date='$sel_date' GROUP BY status");
    while ($r = $res->fetch_assoc()) $att_summary[$r['status']] = $r['c'];
}

$success = flash('success');
require_once '../includes/staff_header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Filter Bar -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-body" style="padding:20px 24px;">
    <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-field" style="margin:0;flex:1;min-width:180px;">
        <label>Subject</label>
        <select name="subject_id" onchange="this.form.submit()">
          <option value="">— Select Subject —</option>
          <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>" <?= $sel_subject==$s['id']?'selected':'' ?>>
            <?= htmlspecialchars($s['year'] . ' ' . $s['degree'] . ' — ' . $s['name']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-field" style="margin:0;min-width:160px;">
        <label>Date</label>
        <input type="date" name="date" value="<?= $sel_date ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn btn-primary">Load</button>
    </form>
  </div>
</div>

<!-- Summary Cards -->
<div class="stat-grid" style="margin-bottom:20px;">
  <div class="stat-card green">
    <div class="stat-icon green">✅</div>
    <div class="stat-val"><?= $att_summary['Present'] ?? 0 ?></div>
    <div class="stat-label">Present</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red">❌</div>
    <div class="stat-val"><?= $att_summary['Absent'] ?? 0 ?></div>
    <div class="stat-label">Absent</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">⏰</div>
    <div class="stat-val"><?= $att_summary['Late'] ?? 0 ?></div>
    <div class="stat-label">Late</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue">📊</div>
    <div class="stat-val"><?= count($students_list) ?></div>
    <div class="stat-label">Total Students</div>
  </div>
</div>

<?php if ($students_list && $sel_subject): ?>
<!-- Attendance Form -->
<div class="panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">Mark Attendance</div>
      <div class="panel-sub"><?= $sel_class ?> — <?= date('l, d M Y', strtotime($sel_date)) ?></div>
    </div>
    <div style="display:flex;gap:8px;">
      <button type="button" class="btn btn-outline btn-sm" onclick="markAll('Present')">✅ All Present</button>
      <button type="button" class="btn btn-outline btn-sm" onclick="markAll('Absent')">❌ All Absent</button>
    </div>
  </div>
  <form method="POST">
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="subject_id" value="<?= $sel_subject ?>">
    <input type="hidden" name="date" value="<?= $sel_date ?>">

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Student</th><th>ID</th><th style="width:300px;">Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($students_list as $i => $s): ?>
          <?php $status = $existing[$s['id']] ?? 'Present'; ?>
          <tr>
            <td style="color:var(--muted);"><?= $i+1 ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" style="width:32px;height:32px;font-size:.8rem;"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                <span><?= htmlspecialchars($s['name']) ?></span>
              </div>
            </td>
            <td><span class="badge badge-blue"><?= $s['student_id'] ?></span></td>
            <td>
              <div style="display:flex;gap:8px;">
                <?php foreach (['Present','Absent','Late'] as $opt): ?>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;
                       padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:500;
                       border:1px solid <?= $opt==='Present' ? 'rgba(16,185,129,.3)' : ($opt==='Absent'?'rgba(239,68,68,.3)':'rgba(245,158,11,.3)') ?>;
                       background:<?= $status===$opt ? ($opt==='Present'?'rgba(16,185,129,.15)':($opt==='Absent'?'rgba(239,68,68,.15)':'rgba(245,158,11,.15)')):'transparent' ?>;
                       color:<?= $opt==='Present'?'var(--green)':($opt==='Absent'?'var(--red)':'var(--gold)') ?>;"
                       id="lbl_<?= $s['id'].'_'.$opt ?>">
                  <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $opt ?>"
                         <?= $status===$opt?'checked':'' ?>
                         onchange="updateLabel(<?= $s['id'] ?>)"
                         style="display:none;">
                  <?= $opt==='Present'?'✅':($opt==='Absent'?'❌':'⏰') ?> <?= $opt ?>
                </label>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:20px 24px;border-top:1px solid var(--border);">
      <button type="submit" class="btn btn-primary">💾 Save Attendance</button>
    </div>
  </form>
</div>

<?php elseif (!$sel_subject): ?>
<div style="text-align:center;padding:80px 40px;color:var(--muted);">
  <div style="font-size:3rem;margin-bottom:16px;">📋</div>
  <div style="font-size:1rem;">Select a subject and date to mark attendance</div>
</div>
<?php else: ?>
<div style="text-align:center;padding:80px 40px;color:var(--muted);">
  <div style="font-size:3rem;margin-bottom:16px;">👥</div>
  <div>No active students found for this class.</div>
</div>
<?php endif; ?>

<script>
function markAll(status) {
  document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(r => {
    r.checked = true;
    updateLabel(r.name.match(/\d+/)[0]);
  });
}

function updateLabel(studentId) {
  ['Present','Absent','Late'].forEach(opt => {
    const lbl = document.getElementById(`lbl_${studentId}_${opt}`);
    const radio = lbl?.querySelector('input[type="radio"]');
    if (!lbl || !radio) return;
    const colors = {
      Present: { bg:'rgba(16,185,129,.15)', border:'rgba(16,185,129,.3)' },
      Absent:  { bg:'rgba(239,68,68,.15)', border:'rgba(239,68,68,.3)' },
      Late:    { bg:'rgba(245,158,11,.15)', border:'rgba(245,158,11,.3)' }
    };
    if (radio.checked) {
      lbl.style.background = colors[opt].bg;
      lbl.style.borderColor = colors[opt].border;
    } else {
      lbl.style.background = 'transparent';
    }
  });
}
</script>

  </div>
</div>
</body>
</html>
