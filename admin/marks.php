<?php
require_once '../includes/config.php';
$db = getDB();
$error = ''; $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $student_id  = (int)$_POST['student_id'];
        $subject_id  = (int)$_POST['subject_id'];
        $exam_type   = sanitize($_POST['exam_type']);
        $marks_obt   = (float)$_POST['marks_obtained'];
        $max_marks   = (int)$_POST['max_marks'];
        $exam_date   = $_POST['exam_date'] ?? date('Y-m-d');
        $remarks     = sanitize($_POST['remarks'] ?? '');
        $pct         = $max_marks > 0 ? ($marks_obt/$max_marks*100) : 0;
        $grade       = getGrade($pct);

        $stmt = $db->prepare("INSERT INTO marks (student_id,subject_id,exam_type,marks_obtained,max_marks,grade,remarks,exam_date) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iisdisss', $student_id,$subject_id,$exam_type,$marks_obt,$max_marks,$grade,$remarks,$exam_date);
        if ($stmt->execute()) {
            flash('success', 'Marks saved!');
            redirect('marks.php');
        } else { $error = 'Error: '.$db->error; }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM marks WHERE id=$id");
        flash('success', 'Record deleted.');
        redirect('marks.php');
    }
}

$filter_year    = sanitize($_GET['year'] ?? '');
$filter_subject = (int)($_GET['subject_id'] ?? 0);
$filter_type    = sanitize($_GET['exam_type'] ?? '');

$where = "WHERE 1";
if ($filter_year)    $where .= " AND s.year='$filter_year'";
if ($filter_subject) $where .= " AND m.subject_id=$filter_subject";
if ($filter_type)    $where .= " AND m.exam_type='$filter_type'";

$marks_list = $db->query("
    SELECT m.*, s.name as student_name, s.student_id as student_uid, s.year, s.degree,
           sub.name as subject_name, sub.type as subject_type
    FROM marks m
    JOIN students s ON m.student_id = s.id
    JOIN subjects sub ON m.subject_id = sub.id
    $where
    ORDER BY m.created_at DESC
    LIMIT 100
");

$students = $db->query("SELECT id, name, student_id, year, degree FROM students WHERE status='Active' ORDER BY name");
$subjects = $db->query("SELECT * FROM subjects ORDER BY year, degree, name");
$years    = $db->query("SELECT DISTINCT year FROM students ORDER BY year");

// Build subject data for JS (type info)
$subjects_js = [];
$subjects->data_seek(0);
while ($s = $subjects->fetch_assoc()) {
    $subjects_js[$s['id']] = ['type' => $s['type'], 'name' => $s['name'], 'year' => $s['year'], 'degree' => $s['degree']];
}

$success = flash('success');
require_once '../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;margin-bottom:4px;">Marks & Results</h2>
    <p style="font-size:.85rem;color:var(--muted);"><?= $marks_list->num_rows ?> records</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Marks</button>
</div>

<!-- Filters -->
<div class="panel" style="margin-bottom:20px;">
  <div class="panel-body" style="padding:16px 24px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-field" style="margin:0;min-width:140px;">
        <label>Year</label>
        <select name="year">
          <option value="">All Years</option>
          <?php $years->data_seek(0); while ($y = $years->fetch_assoc()): ?>
          <option value="<?= $y['year'] ?>" <?= $filter_year===$y['year']?'selected':'' ?>><?= $y['year'] ?> Year</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-field" style="margin:0;min-width:160px;">
        <label>Subject</label>
        <select name="subject_id">
          <option value="">All Subjects</option>
          <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>" <?= $filter_subject==$s['id']?'selected':'' ?>><?= $s['name'] ?> (<?= $s['year'] ?> - <?= $s['degree'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-field" style="margin:0;min-width:140px;">
        <label>Exam Type</label>
        <select name="exam_type">
          <option value="">All Types</option>
          <?php foreach (getAllExamTypes() as $t): ?>
          <option value="<?= $t ?>" <?= $filter_type===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="marks.php" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<!-- Table -->
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Student</th><th>Subject</th><th>Type</th><th>Marks</th><th>%</th><th>Grade</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
        <?php while ($m = $marks_list->fetch_assoc()):
          $pct = round($m['marks_obtained']/$m['max_marks']*100, 1);
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar" style="width:30px;height:30px;font-size:.75rem;"><?= strtoupper(substr($m['student_name'],0,1)) ?></div>
              <div>
                <div style="font-size:.88rem;font-weight:500;"><?= htmlspecialchars($m['student_name']) ?></div>
                <div style="font-size:.75rem;color:var(--muted);"><?= $m['year'] ?> - <?= $m['degree'] ?></div>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($m['subject_name']) ?></td>
          <td><span class="badge badge-purple"><?= $m['exam_type'] ?></span></td>
          <td><?= $m['marks_obtained'] ?> / <?= $m['max_marks'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress" style="width:60px;">
                <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= getGradeColor($m['grade']) ?>;"></div>
              </div>
              <span style="font-size:.82rem;"><?= $pct ?>%</span>
            </div>
          </td>
          <td>
            <span class="badge" style="background:<?= getGradeColor($m['grade']) ?>22;color:<?= getGradeColor($m['grade']) ?>;">
              <?= $m['grade'] ?>
            </span>
          </td>
          <td style="font-size:.8rem;color:var(--muted);"><?= $m['exam_date'] ? date('d M Y', strtotime($m['exam_date'])) : '—' ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Delete this record?');" style="display:inline;">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Del</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Marks Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Add Marks</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="add">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-field">
            <label>Student *</label>
            <select name="student_id" required>
              <option value="">— Select Student —</option>
              <?php $students->data_seek(0); while ($s = $students->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['student_id'] ?>) — <?= $s['year'] ?> <?= $s['degree'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Subject *</label>
            <select name="subject_id" id="subjectSelect" required onchange="updateExamTypes()">
              <option value="">— Select Subject —</option>
              <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>" data-type="<?= $s['type'] ?>"><?= htmlspecialchars($s['year'] . ' ' . $s['degree'] . ' — ' . $s['name']) ?> (<?= $s['type'] ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-field">
            <label>Exam Type *</label>
            <select name="exam_type" id="examTypeSelect" required>
              <option value="">— Select Subject First —</option>
            </select>
          </div>
        </div>
        <div class="form-row cols-3">
          <div class="form-field">
            <label>Marks Obtained *</label>
            <input type="number" name="marks_obtained" min="0" max="999" step="0.5" required>
          </div>
          <div class="form-field">
            <label>Max Marks</label>
            <input type="number" name="max_marks" value="100" min="1" required>
          </div>
          <div class="form-field">
            <label>Exam Date</label>
            <input type="date" name="exam_date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Remarks</label>
            <input type="text" name="remarks" placeholder="Optional remarks">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Marks</button>
      </div>
    </form>
  </div>
</div>

<script>
const theoryTypes = <?= json_encode(getTheoryExamTypes()) ?>;
const labTypes = <?= json_encode(getLabExamTypes()) ?>;

function updateExamTypes() {
  const select = document.getElementById('subjectSelect');
  const examSelect = document.getElementById('examTypeSelect');
  const option = select.options[select.selectedIndex];
  const type = option.getAttribute('data-type');

  examSelect.innerHTML = '';

  if (!type) {
    examSelect.innerHTML = '<option value="">— Select Subject First —</option>';
    return;
  }

  const types = type === 'Lab' ? labTypes : theoryTypes;
  types.forEach(t => {
    const opt = document.createElement('option');
    opt.value = t;
    opt.textContent = t;
    examSelect.appendChild(opt);
  });
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
  o.addEventListener('click', e => { if (e.target===o) o.classList.remove('open'); })
);
</script>

<?php require_once '../includes/admin_footer.php'; ?>