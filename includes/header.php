<?php
// includes/header.php — Shared layout header & sidebar navigation

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compute relative root
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$root = ($current_dir === 'DexVac' || $current_dir === 'htdocs') ? '' : '../';

$current_file = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'Admin';

// Compute user initials for avatar
$name_parts = explode(' ', trim($user_name));
$initials = strtoupper(substr($name_parts[0] ?? 'U', 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
} else {
    $initials .= strtoupper(substr($user_name, 1, 1));
}

// Role-based navigation definition
$role_navs = [
    'Admin' => [
        'OVERVIEW' => [
            $root . 'admin/dashboard.php' => ['label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge', 'file' => 'dashboard.php']
        ],
        'MANAGE' => [
            $root . 'admin/manage-hospitals.php' => ['label' => 'Hospitals', 'icon' => 'fa-solid fa-hospital', 'file' => 'manage-hospitals.php'],
            $root . 'admin/manage-vaccines.php'  => ['label' => 'Vaccines', 'icon' => 'fa-solid fa-syringe', 'file' => 'manage-vaccines.php'],
            $root . 'admin/manage-requests.php'  => ['label' => 'Requests', 'icon' => 'fa-solid fa-calendar-check', 'file' => 'manage-requests.php']
        ]
    ],
    'Hospital' => [
        'OVERVIEW' => [
            $root . 'hospital/dashboard.php'     => ['label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge', 'file' => 'dashboard.php']
        ],
        'CLINIC' => [
            $root . 'hospital/update-status.php' => ['label' => 'Appointments & Stock', 'icon' => 'fa-solid fa-calendar-check', 'file' => 'update-status.php']
        ]
    ],
    'Parent' => [
        'MY FAMILY' => [
            $root . 'parent/dashboard.php'      => ['label' => 'Home', 'icon' => 'fa-solid fa-house', 'file' => 'dashboard.php'],
            $root . 'parent/register-child.php' => ['label' => 'My Children', 'icon' => 'fa-solid fa-children', 'file' => 'register-child.php'],
            $root . 'parent/book-hospital.php'  => ['label' => 'Book Appointment', 'icon' => 'fa-solid fa-calendar-plus', 'file' => 'book-hospital.php'],
            $root . 'parent/reports.php'        => ['label' => 'Vaccination History', 'icon' => 'fa-solid fa-file-medical', 'file' => 'reports.php']
        ]
    ]
];

$active_sections = $role_navs[$user_role] ?? $role_navs['Admin'];
?>
<!doctype html>
<html lang="en" data-oldweb="saas2020">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VaxCare — <?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></title>
  <!-- OldwebUI Framework CSS -->
  <link rel="stylesheet" href="https://chabdulwahhab.github.io/OldwebUI/dist/oldweb-all.css">
  <link rel="stylesheet" href="<?php echo $root; ?>assets/css/style.css">
</head>
<body class="r-layout-sidebar">

  <!-- Mobile Topbar with Hamburger Menu -->
  <div class="r-mobile-bar">
    <button type="button" class="r-hamburger-btn" id="mobileMenuBtn" aria-label="Open navigation menu">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="r-mobile-brand">
      <div class="r-brand-logo" style="width: 28px; height: 28px; font-size: 13px;">
        <i class="fa-solid fa-syringe"></i>
      </div>
      <span style="font-weight: 700; font-size: 15px; color: #0f172a;">DexVacs</span>
    </div>
    <div class="r-user-avatar" style="width: 28px; height: 28px; font-size: 11px;" title="<?php echo htmlspecialchars($user_name); ?>">
      <?php echo htmlspecialchars($initials); ?>
    </div>
  </div>

  <!-- Mobile Backdrop Overlay -->
  <div class="r-sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="r-sidebar" id="appSidebar">
    <div class="r-sidebar-brand">
      <div class="r-brand-logo">
        <i class="fa-solid fa-solid fa-syringe"></i>
      </div>
      <div class="r-brand-meta">
        <h1>DexVacs</h1>
        <div class="brand-sub">Vaccine System</div>
      </div>
      <button type="button" class="r-sidebar-close" id="sidebarCloseBtn" aria-label="Close menu">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <nav class="r-sidebar-nav">
      <?php foreach ($active_sections as $section_title => $links): ?>
        <div class="r-nav-section"><?php echo htmlspecialchars($section_title); ?></div>
        <?php foreach ($links as $url => $item): ?>
          <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo ($current_file === $item['file']) ? 'active' : ''; ?>">
            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="r-sidebar-footer">
      <div class="r-user-box">
        <div class="r-user-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div class="r-user-meta">
          <span class="user-name" title="<?php echo htmlspecialchars($user_name); ?>"><?php echo htmlspecialchars($user_name); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
        </div>
      </div>
      <a href="<?php echo $root; ?>logout.php" class="r-sign-out-btn" title="Sign out of account">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
        <span>Sign Out</span>
      </a>
    </div>
  </aside>

  <main class="r-main">
    <?php if (!empty($page_title)): ?>
      <div class="r-page-header">
        <div class="r-header-title">
          <h2><?php echo htmlspecialchars($page_title); ?></h2>
          <div class="breadcrumbs">
            <span>VaxCare</span> &bull; <span style="color: #0f172a; font-weight: 500;"><?php echo htmlspecialchars($page_title); ?></span>
          </div>
        </div>
        <div class="r-header-actions">
          <?php if (!empty($header_action)): ?>
            <?php echo $header_action; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

