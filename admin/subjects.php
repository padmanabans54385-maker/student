<?php
require_once '../includes/config.php';
$db = getDB();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'add') {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $class = sanitize($_POST['class']);
        $max = (int)$_POST['max_marks'];
        $pass = (int)$_POST['pass_marks'];
        $stmt = $db->prepare("INSERT INTO subjects (name,code,class,max_marks,pass_marks) VALUES(?,?,?,?,?)");
        $stmt->bind_param('sssii', $name,$code,$class,$max,$pass);
        if ($stmt->execute()) { flash('success','Subject added!'); redirect('subjects.php'); }
        else $error = 'Code may already exist.';
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM subjects WHERE id=$id");
        flash('success','Subject deleted.'); redirect('subjects.php');
    }
}

$subjects = $db->query("SELECT s.*, COUNT(m.id) as mark_count FROM subjects s LEFT JOIN marks m ON s.id=m.subject_id GROUP BY s.id ORDER BY s.class, s.name");
$success = flash('success');
require_once '../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Subject</button>
</div>

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Subject</th><th>Code</th><th>Class</th><th>Max Marks</th><th>Pass Marks</th><th>Records</th><th></th></tr>
      </thead>
      <tbody>
        <?php while ($s = $subjects->fetch_assoc()): ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></td>
          <td><span class="badge badge-blue"><?= $s['code'] ?></span></td>
          <td><?= $s['class'] ?></td>
          <td><?= $s['max_marks'] ?></td>
          <td><?= $s['pass_marks'] ?></td>
          <td><?= $s['mark_count'] ?> entries</td>
          <td>
            <form method="POST" onsubmit="return confirm('Delete this subject?');" style="display:inline;">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Del</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Add Subject</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="add">
      <div class="modal-body">
        <div class="form-row cols-2">
          <div class="form-field"><label>Subject Name *</label><input type="text" name="name" required placeholder="e.g. Mathematics"></div>
          <div class="form-field"><label>Code *</label><input type="text" name="code" required placeholder="e.g. MATH10"></div>
        </div>
        <div class="form-row cols-3">
          <div class="form-field">
            <label>Class *</label>
            <select name="class" required>
              <?php foreach (['Class 9','Class 10','Class 11','Class 12'] as $c): ?>
              <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field"><label>Max Marks</label><input type="number" name="max_marks" value="100" min="1"></div>
          <div class="form-field"><label>Pass Marks</label><input type="number" name="pass_marks" value="35" min="1"></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Subject</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o =>
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); })
);
</script>

<?php require_once '../includes/admin_footer.php'; ?>