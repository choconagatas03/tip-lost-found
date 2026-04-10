<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['signin.php', 'register.php', 'process_register.php', 'process_signin.php'];

$user_name = $_SESSION['fullname'] ?? '';
$user_role = $_SESSION['role'] ?? 'student';
$user_initial = strtoupper(substr($user_name, 0, 1)) ?: 'U';

if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
  header("Location: signin.php");
  exit;
}

// Page titles for top bar
$page_titles = [
  'index.php'           => ['Lost Items', 'Browse reported lost items'],
  'claimed_items.php'   => ['Claimed Items', 'Items that have been returned'],
  'account.php'         => ['My Account', 'Manage your profile and password'],
  'admin_dashboard.php' => ['Dashboard', 'Overview of the lost & found system'],
  'manage_items.php'    => ['Manage Items', 'Add, edit, or remove lost item records'],
  'manage_users.php'    => ['Manage Users', 'View and manage registered members'],
  'reports.php'         => ['Reports & Export', 'Generate and download reports'],
];

$page_title   = $page_titles[$current_page][0] ?? 'Lost & Found';
$page_subtitle = $page_titles[$current_page][1] ?? 'TIP Manila Lost & Found System';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> — TIP Lost & Found</title>
  <link rel="stylesheet" href="assets/style.css?v=20">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <?php if ($current_page === 'signin.php' || $current_page === 'register.php'): ?>
    <!-- Auth layout — no sidebar -->
    <div class="auth-layout">
      <nav class="top-navbar">
        <a class="navbar-brand" href="signin.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
          <div class="navbar-brand-icon"><i class="fas fa-thumbtack"></i></div>
          <span class="navbar-title">TIP Lost &amp; Found</span>
        </a>
        <ul>
          <li><a href="signin.php" <?php echo $current_page === 'signin.php' ? ' style="background:var(--tip-gold);color:var(--ink);border-color:var(--tip-gold);"' : ''; ?>>Sign In</a></li>
          <li><a href="register.php" <?php echo $current_page === 'register.php' ? ' style="background:var(--tip-gold);color:var(--ink);border-color:var(--tip-gold);"' : ''; ?>>Register</a></li>
        </ul>
      </nav>
      <div class="auth-body">

      <?php else: ?>
        <!-- App layout — with sidebar -->
        <div class="student-layout">

          <!-- SIDEBAR -->
          <aside id="sidebar" class="sidebar collapsed">

            <!-- Brand -->
            <div class="sidebar-brand">
              <div class="sidebar-brand-icon"><i class="fas fa-thumbtack"></i></div>
              <div class="sidebar-brand-text">
                <strong>TIP Lost &amp; Found</strong>
                <span>Manila Campus</span>
              </div>
            </div>

            <!-- Navigation -->
            <div class="sidebar-section">

              <?php if ($user_role === 'student'): ?>
                <div class="sidebar-section-label">Menu</div>
                <ul>
                  <li>
                    <a href="index.php" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-search"></i></span>
                      <span class="label">Lost Items</span>
                    </a>
                  </li>
                  <li>
                    <a href="claimed_items.php" <?php echo $current_page === 'claimed_items.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-box-open"></i></span>
                      <span class="label">Claimed Items</span>
                    </a>
                  </li>
                  <li>
                    <a href="account.php" <?php echo $current_page === 'account.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                      <span class="label">My Account</span>
                    </a>
                  </li>
                </ul>

              <?php elseif ($user_role === 'admin'): ?>
                <div class="sidebar-section-label">Overview</div>
                <ul>
                  <li>
                    <a href="admin_dashboard.php" <?php echo $current_page === 'admin_dashboard.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                      <span class="label">Dashboard</span>
                    </a>
                  </li>
                </ul>
                <div class="sidebar-section-label" style="margin-top:14px;">Management</div>
                <ul>
                  <li>
                    <a href="manage_items.php" <?php echo $current_page === 'manage_items.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                      <span class="label">Manage Items</span>
                    </a>
                  </li>
                  <li>
                    <a href="manage_users.php" <?php echo $current_page === 'manage_users.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-users"></i></span>
                      <span class="label">Manage Users</span>
                    </a>
                  </li>
                  <li>
                    <a href="reports.php" <?php echo $current_page === 'reports.php' ? 'class="active"' : ''; ?>>
                      <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                      <span class="label">Reports &amp; Export</span>
                    </a>
                  </li>
                </ul>
              <?php endif; ?>

              <ul style="margin-top:8px;">
                <li class="logout-item">
                  <a href="logout.php">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="label">Sign Out</span>
                  </a>
                </li>
              </ul>

            </div><!-- /sidebar-section -->

            <!-- Footer toggle -->
            <div class="sidebar-footer">
              <button id="hamburger" class="hamburger" aria-label="Toggle sidebar">
                <i class="fas fa-bars hamburger-icon"></i>
                <span class="hamburger-label">Collapse</span>
              </button>
            </div>

          </aside><!-- /sidebar -->

          <!-- MAIN CONTENT -->
          <div class="content collapsed" id="main-content">

            <!-- Top bar -->
            <header class="top-bar">
              <div class="top-bar-left">
                <h2><?php echo htmlspecialchars($page_title); ?></h2>
                <p><?php echo htmlspecialchars($page_subtitle); ?></p>
              </div>
              <div class="top-bar-right">
                <a href="account.php" class="user-chip" title="My Account">
                  <div class="user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                  <span class="label"><?php echo htmlspecialchars($user_name); ?></span>
                </a>
              </div>
            </header>

            <!-- Page content -->
            <div class="page-content">
              <div class="container">

              <?php endif; // end auth vs app check 
              ?>

              <script>
                document.addEventListener("DOMContentLoaded", function() {
                  const hamburger = document.getElementById("hamburger");
                  const sidebar = document.getElementById("sidebar");
                  const content = document.getElementById("main-content");

                  if (!hamburger || !sidebar) return;

                  function setCollapsed(collapsed) {
                    sidebar.classList.toggle("collapsed", collapsed);
                    sidebar.classList.toggle("open", !collapsed && window.innerWidth <= 768);
                    if (content) content.classList.toggle("collapsed", collapsed);
                    try {
                      localStorage.setItem("sidebarCollapsed", collapsed ? "1" : "0");
                    } catch (e) {}
                  }

                  // Restore state
                  try {
                    const saved = localStorage.getItem("sidebarCollapsed");
                    if (saved !== null) setCollapsed(saved === "1");
                  } catch (e) {}

                  hamburger.addEventListener("click", function() {
                    const isCollapsed = sidebar.classList.contains("collapsed");
                    setCollapsed(!isCollapsed);
                  });

                  // Mobile overlay close
                  document.addEventListener("click", function(e) {
                    if (window.innerWidth <= 768 && sidebar.classList.contains("open")) {
                      if (!sidebar.contains(e.target)) setCollapsed(true);
                    }
                  });
                });
              </script>