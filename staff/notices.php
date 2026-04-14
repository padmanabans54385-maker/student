<?php
require_once '../includes/config.php';
require_once '../includes/staff_header.php';

$db = getDB();

// Fetch notices targeted to All or Staffs
$notices = $db->query("SELECT n.*, a.name as posted_by_name FROM notices n LEFT JOIN admins a ON n.posted_by=a.id WHERE n.target IN ('All','Staffs') ORDER BY n.created_at DESC");
?>

<?php if ($notices->num_rows === 0): ?>
<div style="text-align:center;padding:80px 40px;color:var(--muted);">
  <div style="font-size:3rem;margin-bottom:16px;">📢</div>
  <div style="font-size:1rem;">No notices available at the moment.</div>
</div>
<?php else: ?>
<div style="display:grid;gap:16px;">
  <?php while ($n = $notices->fetch_assoc()): ?>
  <div class="panel">
    <div class="panel-body" style="padding:20px 24px;">
      <div style="display:flex;align-items:flex-start;gap:16px;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(79,142,247,.12);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">
          📢
        </div>
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
      </div>
    </div>
  </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

  </div>
</div>
</body>
</html>
