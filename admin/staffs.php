<?php
require_once '../includes/config.php';

$db = getDB();
$error = ''; $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $name       = sanitize($_POST['name']);
        $email      = sanitize($_POST['email']);
        $phone      = sanitize($_POST['phone']);
        $department = sanitize($_POST['department']);
        $password   = password_hash($_POST['password'] ?: 'password', PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO staffs (name,email,password,phone,department) VALUES(?,?,?,?,?)");
        $stmt->bind_param('sssss', $name,$email,$password,$phone,$department);
        if ($stmt->execute()) {
            flash('success', 'Staff member added!');
            redirect('staffs.php');
        } else {
            $error = 'Error: ' . $db->error;
        }
    } elseif ($act === 'edit') {
        $id         = (int)$_POST['id'];
        $name       = sanitize($_POST['name']);
        $phone      = sanitize($_POST['phone']);
        $department = sanitize($_POST['department']);
        $status     = sanitize($_POST['status']);

        $stmt = $db->prepare("UPDATE staffs SET name=?,phone=?,department=?,status=? WHERE id=?");
        $stmt->bind_param('ssssi', $name,$phone,$department,$status,$id);
        if ($stmt->execute()) {
            flash('success', 'Staff updated!');
            redirect('staffs.php');
        } else { $error = 'Update failed.'; }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM staffs WHERE id=$id");
        flash('success', 'Staff removed.');
        redirect('staffs.php');
    }
}

$staffs = $db->query("SELECT * FROM staffs ORDER BY created_at DESC");
$success = flash('success');

require_once '../includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Staff</button>
</div>

<div class="panel">
  <div class="panel-head">
    <div>
      <div class="panel-title">All Staff Members</div>
      <div class="panel-sub"><?= $staffs->num_rows ?> records found</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Staff</th><th>Department</th><th>Contact</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($s = $staffs->fetch_assoc()): ?>
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
          <td><span class="badge badge-purple"><?= htmlspecialchars($s['department'] ?: '—') ?></span></td>
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
                onclick="editStaff(<?= htmlspecialchars(json_encode($s)) ?>)">Edit</button>
              <form method="POST" onsubmit="return confirm('Delete this staff member?');" style="display:inline;">
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

<!-- Add Staff Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Add New Staff</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="add">
      <div class="modal-body">
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Full Name *</label>
            <input type="text" name="name" required placeholder="e.g. Dr. Ramesh Kumar">
          </div>
          <div class="form-field">
            <label>Email *</label>
            <input type="email" name="email" required placeholder="staff@email.com">
          </div>
        </div>
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Leave blank for 'password'">
          </div>
          <div class="form-field">
            <label>Department</label>
            <input type="text" name="department" placeholder="e.g. Computer Science">
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Phone</label>
            <input type="tel" name="phone" placeholder="10-digit number">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Edit Staff</h3>
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
        <div class="form-row cols-2">
          <div class="form-field">
            <label>Department</label>
            <input type="text" name="department" id="editDepartment">
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

function editStaff(s) {
  document.getElementById('editId').value = s.id;
  document.getElementById('editName').value = s.name;
  document.getElementById('editPhone').value = s.phone || '';
  document.getElementById('editDepartment').value = s.department || '';
  document.getElementById('editStatus').value = s.status;
  openModal('editModal');
}

document.querySelectorAll('.modal-overlay').forEach(o =>
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); })
);
</script>

<?php require_once '../includes/admin_footer.php'; ?>
