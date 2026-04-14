<?php
require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'add') {
        $title   = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $target  = sanitize($_POST['target']);
        $admin   = $_SESSION['admin_id'];
        $stmt = $db->prepare("INSERT INTO notices (title,content,posted_by,target) VALUES(?,?,?,?)");
        $stmt->bind_param('ssis', $title,$content,$admin,$target);
        if ($stmt->execute()) { flash('success','Notice posted!'); redirect('notices.php'); }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM notices WHERE id=$id");
        flash('success','Notice deleted.'); redirect('notices.php');
    }
}

$notices = $db->query("SELECT n.*, a.name as posted_by_name FROM notices n LEFT JOIN admins a ON n.posted_by=a.id ORDER BY n.created_at DESC");
$success = flash('success');
require_once '../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Post Notice</button>
</div>

<div style="display:grid;gap:16px;">
  <?php while ($n = $notices->fetch_assoc()): ?>
  <div class="panel">
    <div class="panel-body" style="padding:20px 24px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <h3 style="font-family:'Syne',sans-serif;font-size:1rem;"><?= htmlspecialchars($n['title']) ?></h3>
            <span class="badge badge-blue"><?= $n['target'] ?></span>
          </div>
          <p style="font-size:.88rem;color:var(--muted);line-height:1.6;margin-bottom:12px;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
          <div style="font-size:.75rem;color:var(--muted);">
            📅 <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
            &nbsp;·&nbsp; 👤 <?= htmlspecialchars($n['posted_by_name'] ?? 'Admin') ?>
          </div>
        </div>
        <form method="POST" onsubmit="return confirm('Delete this notice?');" style="margin-left:16px;">
          <input type="hidden" name="act" value="delete">
          <input type="hidden" name="id" value="<?= $n['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
</div>

<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Post New Notice</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="act" value="add">
      <div class="modal-body">
        <div class="form-row"><div class="form-field"><label>Title *</label><input type="text" name="title" required placeholder="Notice title"></div></div>
        <div class="form-row"><div class="form-field"><label>Content *</label><textarea name="content" rows="4" required placeholder="Write the notice content..." style="resize:vertical;"></textarea></div></div>
        <div class="form-row"><div class="form-field"><label>Target Audience</label>
          <select name="target"><option value="All">All</option><option value="Students">Students</option></select>
        </div></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Notice</button>
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