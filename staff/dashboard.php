<?php
require_once '../includes/config.php';
require_once '../includes/staff_header.php';

$db = getDB();

// Stats
$total_students = $db->query("SELECT COUNT(*) as c FROM students WHERE status='Active'")->fetch_assoc()['c'];
$total_subjects = $db->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];
$today = date('Y-m-d');
$today_present = $db->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
$total_marks = $db->query("SELECT COUNT(*) as c FROM marks")->fetch_assoc()['c'];
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card blue">
    <div class="stat-icon blue">👥</div>
    <div class="stat-val"><?= $total_students ?></div>
    <div class="stat-label">Total Students</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">✅</div>
    <div class="stat-val"><?= $today_present ?></div>
    <div class="stat-label">Present Today</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">📚</div>
    <div class="stat-val"><?= $total_subjects ?></div>
    <div class="stat-label">Subjects</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red">📝</div>
    <div class="stat-val"><?= $total_marks ?></div>
    <div class="stat-label">Mark Entries</div>
  </div>
</div>

<!-- Quick Links -->
<div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
  <a href="attendance.php" class="btn btn-primary">📋 Mark Attendance</a>
  <a href="marks.php" class="btn btn-outline">📝 Enter Marks</a>
</div>

  </div>
</div>
</body>
</html>
