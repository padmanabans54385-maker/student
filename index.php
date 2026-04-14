<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) redirect('admin/dashboard.php');
if (isStudentLoggedIn()) redirect('student/dashboard.php');

$error = '';
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] ?? 'student';
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();

        if ($role === 'admin') {
            $stmt = $db->prepare("SELECT id, name, password FROM admins WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['role'] = 'admin';
                redirect('admin/dashboard.php');
            } else {
                $error = 'Invalid admin credentials.';
            }
        } else {
            $stmt = $db->prepare("SELECT id, name, student_id, class, section, password FROM students WHERE email = ? AND status = 'Active'");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();

            if ($student && password_verify($password, $student['password'])) {
                $_SESSION['student_id']  = $student['id'];
                $_SESSION['student_uid'] = $student['student_id'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['student_class'] = $student['class'];
                $_SESSION['role'] = 'student';
                redirect('student/dashboard.php');
            } else {
                $error = 'Invalid student credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduTrack — Student Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #080c14;
    --surface: #0e1520;
    --card: #131c2b;
    --border: #1e2d42;
    --accent: #4f8ef7;
    --accent2: #7c3aed;
    --gold: #f59e0b;
    --text: #e2e8f0;
    --muted: #64748b;
    --success: #10b981;
    --danger: #ef4444;
  }

  html, body {
    height: 100%;
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow: hidden;
  }

  /* Animated background */
  .bg-grid {
    position: fixed; inset: 0; z-index: 0;
    background-image:
      linear-gradient(rgba(79,142,247,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(79,142,247,.04) 1px, transparent 1px);
    background-size: 40px 40px;
  }

  .orb {
    position: fixed; border-radius: 50%; filter: blur(80px); opacity: .25; z-index: 0;
    animation: drift 8s ease-in-out infinite;
  }
  .orb-1 { width:500px;height:500px;background:radial-gradient(#4f8ef7,transparent); top:-150px;left:-100px; }
  .orb-2 { width:400px;height:400px;background:radial-gradient(#7c3aed,transparent); bottom:-100px;right:-80px; animation-delay:-4s; }
  .orb-3 { width:300px;height:300px;background:radial-gradient(#f59e0b,transparent); top:50%;left:60%; animation-delay:-2s; opacity:.12; }

  @keyframes drift {
    0%,100% { transform: translate(0,0) scale(1); }
    50% { transform: translate(30px,-20px) scale(1.05); }
  }

  /* Layout */
  .wrapper {
    position: relative; z-index: 10;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 480px;
  }

  /* Left panel */
  .hero {
    display: flex; flex-direction: column;
    justify-content: center; align-items: flex-start;
    padding: 60px 80px;
  }

  .logo {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem; font-weight: 700;
    letter-spacing: .12em; text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 60px;
    display: flex; align-items: center; gap: 10px;
  }
  .logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
  }

  .hero-tag {
    font-size: .75rem; font-weight: 500; letter-spacing: .15em;
    text-transform: uppercase; color: var(--gold);
    background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.25);
    padding: 5px 14px; border-radius: 100px;
    margin-bottom: 28px;
  }

  .hero h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2.4rem, 4vw, 3.8rem);
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 24px;
  }
  .hero h1 span {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }

  .hero p {
    font-size: 1.05rem; color: var(--muted);
    line-height: 1.7; max-width: 440px;
    margin-bottom: 48px;
  }

  .stats-row {
    display: flex; gap: 40px;
  }
  .stat { }
  .stat-num {
    font-family: 'Syne', sans-serif;
    font-size: 2rem; font-weight: 800;
    background: linear-gradient(135deg, var(--accent), #a78bfa);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .stat-label { font-size: .8rem; color: var(--muted); margin-top: 2px; }

  /* Right panel — login card */
  .login-panel {
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 50px;
  }

  .login-card {
    width: 100%;
    animation: slideIn .5s cubic-bezier(.22,1,.36,1);
  }
  @keyframes slideIn {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
  }

  .login-card h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.7rem; font-weight: 700;
    margin-bottom: 6px;
  }
  .login-card .sub {
    color: var(--muted); font-size: .9rem; margin-bottom: 36px;
  }

  /* Role toggle */
  .role-toggle {
    display: grid; grid-template-columns: 1fr 1fr;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 5px;
    margin-bottom: 32px;
    gap: 4px;
  }
  .role-btn {
    padding: 10px; border: none; border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem; font-weight: 500;
    cursor: pointer;
    transition: all .25s;
    background: transparent; color: var(--muted);
  }
  .role-btn.active {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    box-shadow: 0 4px 16px rgba(79,142,247,.35);
  }

  /* Form */
  .form-group { margin-bottom: 20px; }
  .form-group label {
    display: block; font-size: .8rem; font-weight: 500;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--muted); margin-bottom: 8px;
  }
  .form-group input {
    width: 100%;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 13px 16px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  .form-group input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.15);
  }
  .form-group input::placeholder { color: var(--muted); }

  .demo-hint {
    font-size: .78rem; color: var(--muted);
    background: rgba(79,142,247,.08);
    border: 1px solid rgba(79,142,247,.15);
    border-radius: 8px; padding: 10px 14px;
    margin-bottom: 24px;
    line-height: 1.6;
  }
  .demo-hint strong { color: var(--accent); }

  .btn-login {
    width: 100%;
    padding: 14px;
    border: none; border-radius: 10px;
    font-family: 'Syne', sans-serif;
    font-size: 1rem; font-weight: 700;
    letter-spacing: .05em;
    cursor: pointer;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    transition: all .2s;
    box-shadow: 0 4px 20px rgba(79,142,247,.3);
  }
  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(79,142,247,.45);
  }
  .btn-login:active { transform: translateY(0); }

  .alert {
    padding: 12px 16px; border-radius: 10px;
    font-size: .88rem; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .alert-error {
    background: rgba(239,68,68,.12);
    border: 1px solid rgba(239,68,68,.3);
    color: #fca5a5;
  }
  .alert-success {
    background: rgba(16,185,129,.12);
    border: 1px solid rgba(16,185,129,.3);
    color: #6ee7b7;
  }

  @media (max-width: 900px) {
    .wrapper { grid-template-columns: 1fr; }
    .hero { display: none; }
    .login-panel { border-left: none; }
  }
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="wrapper">
  <!-- Hero -->
  <div class="hero">
    <div class="logo">
      <div class="logo-icon">🎓</div>
      EduTrack
    </div>
    <div class="hero-tag">✦ Advanced Student Management</div>
    <h1>Manage. Track.<br><span>Achieve Excellence.</span></h1>
    <p>A comprehensive platform for tracking attendance, managing marks, analyzing performance, and empowering every student's academic journey.</p>
    <div class="stats-row">
      <div class="stat">
        <div class="stat-num">98%</div>
        <div class="stat-label">Accuracy Rate</div>
      </div>
      <div class="stat">
        <div class="stat-num">5K+</div>
        <div class="stat-label">Students Tracked</div>
      </div>
      <div class="stat">
        <div class="stat-num">Real-time</div>
        <div class="stat-label">Analytics</div>
      </div>
    </div>
  </div>

  <!-- Login Panel -->
  <div class="login-panel">
    <div class="login-card">
      <h2>Welcome Back 👋</h2>
      <p class="sub">Sign in to your account to continue</p>

      <!-- Role toggle -->
      <div class="role-toggle" id="roleToggle">
        <button class="role-btn active" data-role="admin" onclick="setRole('admin',this)">🛡 Admin</button>
        <button class="role-btn" data-role="student" onclick="setRole('student',this)">🎒 Student</button>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <input type="hidden" name="role" id="roleInput" value="admin">

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" id="emailInput" placeholder="Enter your email" required
                 value="admin@school.com">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required
                 value="password">
        </div>

        <div class="demo-hint" id="demoHint">
          <strong>Admin Demo:</strong> admin@school.com / password<br>
          <strong>Student Demo:</strong> arjun@student.com / password
        </div>

        <button type="submit" class="btn-login">Sign In →</button>
      </form>
    </div>
  </div>
</div>

<script>
function setRole(role, btn) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('roleInput').value = role;

  if (role === 'admin') {
    document.getElementById('emailInput').value = 'admin@school.com';
  } else {
    document.getElementById('emailInput').value = 'arjun@student.com';
  }
}

// Auto-detect role from PHP error context
<?php if ($_POST['role'] ?? '' === 'student'): ?>
document.querySelector('[data-role="student"]').click();
<?php endif; ?>
</script>
</body>
</html>