<?php
// app/includes/header.php
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
  <link rel="stylesheet" href="<?= $base_path ?>/public/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="<?= $base_path ?>/public/dashboard.php" class="site-logo">Department<span>.</span>System</a>
    <?php if ($show_nav && $user_id): ?>
    <nav class="header-nav">
      <a href="<?= $base_path ?>/public/dashboard.php" class="nav-link">Dashboard</a>
      <?php if ($nav_role === 'student'): ?>
        <a href="<?= $base_path ?>/public/community/profile.php" class="nav-link">Profile</a>
      <?php endif; ?>
      <?php if (in_array($nav_role, ['senior','faculty','hod'])): ?>
        <a href="<?= $base_path ?>/public/community/reviewer_dashboard.php" class="nav-link">Review Requests</a>
      <?php endif; ?>
      <?php if (in_array($nav_role, ['faculty','creator'])): ?>
        <a href="<?= $base_path ?>/public/academics/manage_subjects.php" class="nav-link">Academics</a>
      <?php endif; ?>
      <a href="<?= $base_path ?>/app/auth/logout.php" class="nav-link">Logout</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="main-content">
