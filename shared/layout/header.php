<?php
// shared/layout/header.php
$page_title = $page_title ?? 'Department System';
$show_nav   = $show_nav ?? true;
$nav_role   = $_SESSION['role'] ?? '';
$user_id    = $_SESSION['user_id'] ?? null;

$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$proj_root = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$base_path = str_replace($doc_root, '', $proj_root);
$base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
$base_path = rtrim($base_path, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> — Department System</title>
  <link rel="stylesheet" href="<?= $base_path ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="<?= $base_path ?>/dashboard" class="site-logo">Department<span>.</span>System</a>
    <?php if ($show_nav && $user_id): ?>
    <nav class="header-nav">
      <a href="<?= $base_path ?>/dashboard" class="nav-link">Dashboard</a>
      <a href="<?= $base_path ?>/community/profile" class="nav-link">Profile</a>
      <a href="<?= $base_path ?>/community/leaderboard" class="nav-link">Leaderboard</a>
      <?php if (has_permission('review_requests')): ?>
        <a href="<?= $base_path ?>/community/reviewer_dashboard" class="nav-link">Review Requests</a>
      <?php endif; ?>
      <?php if (has_permission('view_faculty_dashboard') && $_SESSION['role'] !== 'faculty'): ?>
        <a href="<?= $base_path ?>/academics/manage_subjects" class="nav-link">Academics</a>
      <?php endif; ?>

      <?php if ($_SESSION['role'] === 'student'): ?>
          <?php
          require_once __DIR__ . '/../../shared/config/db.php';
          // Check for live meetings
          $student_id = $_SESSION['user_id'];
          $s_sql = "SELECT class_id FROM students WHERE user_id = ?";
          $s_stmt = $conn->prepare($s_sql);
          $s_stmt->bind_param("i", $student_id);
          $s_stmt->execute();
          $s_user = $s_stmt->get_result()->fetch_assoc();
          
          $has_live = false;
          $live_meetings = null;
          if ($s_user && !empty($s_user['class_id'])) {
              $m_sql = "SELECT ls.*, u.name as faculty_name, COALESCE(t.name, s.name) as topic 
                        FROM live_sessions ls 
                        JOIN users u ON ls.faculty_id = u.id 
                        LEFT JOIN topics t ON ls.topic_id = t.id
                        LEFT JOIN subjects s ON ls.subject_id = s.id
                        WHERE ls.class_id = ? AND ls.status = 'live' 
                        ORDER BY ls.started_at DESC";
              $m_stmt = $conn->prepare($m_sql);
              $m_stmt->bind_param("i", $s_user['class_id']);
              $m_stmt->execute();
              $live_meetings = $m_stmt->get_result();
              $has_live = $live_meetings->num_rows > 0;
          }
          ?>
          <div class="notification-container" style="position: relative; display: inline-flex; align-items: center; cursor: pointer; margin: 0 1rem;" onclick="toggleNotifications()">
              <div class="bell-icon <?= $has_live ? 'ringing' : '' ?>" style="font-size: 20px;">🔔</div>
              <?php if ($has_live): ?>
                  <span class="pulse-dot"></span>
                  <div id="notif-dropdown" class="card" style="display: none; position: absolute; top: 40px; right: -100px; width: 300px; z-index: 1000; padding: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); background: white; color: #1a1a1a; text-align: left;">
                      <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">Live Class Alerts</h4>
                      <?php while($m = $live_meetings->fetch_assoc()): ?>
                          <div style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; margin-bottom: 5px;">
                              <p style="margin: 0; font-weight: 700; color: #ef4444; font-size: 11px;">🔴 LIVE NOW</p>
                              <p style="margin: 5px 0; font-size: 13px;"><strong><?= htmlspecialchars($m['topic']) ?></strong></p>
                              <p style="margin: 0; font-size: 11px; color: #64748b;">By <?= htmlspecialchars($m['faculty_name']) ?></p>
                              <a href="<?= $base_path ?>/academics/join_class?room=<?= htmlspecialchars($m['room_code']) ?>" class="btn btn-sm btn-primary" style="width: 100%; margin-top: 10px; text-align: center; display: block; text-decoration: none;">Join Classroom</a>
                          </div>
                      <?php endwhile; ?>
                  </div>
              <?php endif; ?>
          </div>

          <style>
              .bell-icon.ringing { animation: ring 1s infinite; color: #ef4444; }
              @keyframes ring {
                  0% { transform: rotate(0); }
                  10% { transform: rotate(15deg); }
                  20% { transform: rotate(-15deg); }
                  30% { transform: rotate(10deg); }
                  40% { transform: rotate(-10deg); }
                  50% { transform: rotate(0); }
                  100% { transform: rotate(0); }
              }
              .pulse-dot {
                  position: absolute;
                  top: -2px;
                  right: -2px;
                  width: 8px;
                  height: 8px;
                  background: #ef4444;
                  border-radius: 50%;
                  border: 1px solid white;
                  box-shadow: 0 0 0 0 rgba(239, 68, 68, 1);
                  animation: pulse-red 2s infinite;
              }
              @keyframes pulse-red {
                  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
                  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
                  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
              }
          </style>

          <script>
          function toggleNotifications() {
              const dropdown = document.getElementById('notif-dropdown');
              if (dropdown) {
                  dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
              }
          }
          </script>
      <?php endif; ?>

      <a href="<?= $base_path ?>/api/auth/logout" class="nav-link">Logout</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="main-content">
    <?php if (isset($_SESSION['msg_success'])): ?>
      <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
        <div class="alert alert-success" style="margin-bottom: 1rem;"><?= $_SESSION['msg_success'] ?></div>
        <?php unset($_SESSION['msg_success']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_error'])): ?>
      <div class="page-wrap" style="padding-top: 1rem; padding-bottom: 0;">
        <div class="alert alert-error" style="margin-bottom: 1rem;"><?= $_SESSION['msg_error'] ?></div>
        <?php unset($_SESSION['msg_error']); ?>
      </div>
    <?php endif; ?>
