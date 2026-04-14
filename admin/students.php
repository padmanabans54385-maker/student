<?php
require_once '../includes/config.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$error = ''; $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $name       = sanitize($_POST['name']);
        $email      = sanitize($_POST['email']);
        $sid        = sanitize($_POST['student_id']);
        $class      = sanitize($_POST['class']);
        $section    = sanitize($_POST['section']);
        $phone      = sanitize($_POST['phone']);
        $gender     = sanitize($_POST['gender']);
        $dob        = $_POST['dob'] ?? '';
        $password   = password_hash($_POST['password'] ?: 'password', PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO students (student_id,name,email,password,class,section,phone,gender,dob) VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $sid,$name,$email,$password,$class,$section,$phone,$gender,$dob);
        if ($stmt->execute()) {
            flash('success', 'Student added successfully!');
            redirect('students.php');
        } else {
            $error = 'Error: ' . $db->error;
        }
    } elseif ($act === 'edit') {
        $id      = (int)$_POST['id'];
        $name    = sanitize($_POST['name']);
        $class   = sanitize($_POST['class']);
        $section = sanitize($_POST['section']);
        $phone   = sanitize($_POST['phone']);
        $status  = sanitize($_POST['status']);

        $stmt = $db->prepare("UPDATE students SET name=?,class=?,section=?,phone=?,status=? WHERE id=?");
        $stmt->bind_param('sssssi', $name,$class,$section,$phone,$status,$id);
        if ($stmt->execute()) {
            flash('success', 'Student updated!');
            redirect('students.php');
        } else { $error = 'Update failed.'; }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM students WHERE id=$id");
        flash('success', 'Student removed.');
        redirect('students.php');
    }
}

// Fetch for edit
$edit_student = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $edit_student = $db->query("SELECT * FROM students WHERE id=$id")->fetch_assoc();
}

// Search & filter
$search = sanitize($_GET['q'] ?? '');
$filter_class = sanitize($_GET['class'] ?? '');
$where = "WHERE status != 'deleted'";
if ($search)       $where .= " AND (name LIKE '%$search%' OR student_id LIKE '%$search%' OR email LIKE '%$search%')";
if ($filter_class) $where .= " AND class='$filter_class'";

$students = $db->query("SELECT * FROM students $where ORDER BY created_at DESC");
$classes  = $db->query("SELECT DISTINCT class FROM students ORDER BY class");

$success = flash('success');

require_once '../includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Top bar -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;gap:16px;flex-wrap:wrap;">
  <form method="GET" style="display:flex;gap:10px;flex:1;">
    <input type="text" name="q" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>"
           style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px 16px;color:var(--text);font-size:.9rem;outline:none;flex:1;max-width:300px;">
    <select name="class" style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-size:.9rem;outline:none;">
      <option value="">All Classes</option>
      <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
      <option value="<?= $c['class'] ?>" <?= $filter_class===$c['class']?'selected':'' ?>><?= $c['class'] ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
  </form>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Student</button>
</div>

<!-- Table -->
<div class="panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">All Students</div>
      <div class="panel-sub"><?= $students->num_rows ?> records found</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th><th>ID</th><th>Class</th><th>Contact</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:12px;">
              <div class="avatar" style="width:36px;height:36px;font-size:.85rem;">
                <?= strtoupper(substr($s['name'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($s['email']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-blue"><?= $s['student_id'] ?></span></td>
          <td><?= $s['class'] ?> – <?= $s['section'] ?></td>
          <td style="font-size:.85rem;"><?= $s['phone'] ?: '—' ?></td>
          <td>
            <span class="badge <?= $s['status']==='Active' ? 'badge-green' : 'badge-red' ?>">
              <?= $s['status'] ?>
            </span>
          </td>
          <td style="color:var(--muted);font-size:.8rem;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:8px;">
              <button class="btn btn-outline btn-sm"
                onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)">Edit</button>
              <form method="POST" onsubmit="return confirm('Delete this student?');" style="display:inline;">
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Student Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Add New Student</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="add">
      <div class="modal-body">
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Full Name *</label>
            <input type="text" name="name" required placeholder="e.g. Rahul Kumar">
          </div>
          <div class="form-field">
            <label>Student ID *</label>
            <input type="text" name="student_id" required placeholder="e.g. STU007">
          </div>
        </div>
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Email *</label>
            <input type="email" name="email" required placeholder="student@email.com">
          </div>
          <div class="form-field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Leave blank for 'password'">
          </div>
        </div>
        <div class="form-row cols-3">
          <div class="form-field">
            <label>Class *</label>
            <select name="class" required>
              <option value="Class 9">Class 9</option>
              <option value="Class 10" selected>Class 10</option>
              <option value="Class 11">Class 11</option>
              <option value="Class 12">Class 12</option>
            </select>
          </div>
          <div class="form-field">
            <label>Section</label>
            <select name="section">
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
            </select>
          </div>
          <div class="form-field">
            <label>Gender</label>
            <select name="gender">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Phone</label>
            <input type="tel" name="phone" placeholder="10-digit number">
          </div>
          <div class="form-field">
            <label>Date of Birth</label>
            <input type="date" name="dob">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Student</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Edit Student</h3>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body">
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Full Name</label>
            <input type="text" name="name" id="editName" required>
          </div>
          <div class="form-field">
            <label>Phone</label>
            <input type="tel" name="phone" id="editPhone">
          </div>
        </div>
        <div class="form-row cols-3">
          <div class="form-field">
            <label>Class</label>
            <select name="class" id="editClass">
              <option value="Class 9">Class 9</option>
              <option value="Class 10">Class 10</option>
              <option value="Class 11">Class 11</option>
              <option value="Class 12">Class 12</option>
            </select>
          </div>
          <div class="form-field">
            <label>Section</label>
            <select name="section" id="editSection">
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
            </select>
          </div>
          <div class="form-field">
            <label>Status</label>
            <select name="status" id="editStatus">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function editStudent(s) {
  document.getElementById('editId').value = s.id;
  document.getElementById('editName').value = s.name;
  document.getElementById('editPhone').value = s.phone || '';
  document.getElementById('editClass').value = s.class;
  document.getElementById('editSection').value = s.section;
  document.getElementById('editStatus').value = s.status;
  openModal('editModal');
}

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(o =>
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); })
);
</script>

<?php require_once '../includes/admin_footer.php'; ?>