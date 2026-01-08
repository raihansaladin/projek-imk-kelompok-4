<?php
// dashboard/src/base.php

function getBaseTemplate($title, $content, $icon = 'icon-screen-desktop') {
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= htmlspecialchars($title) ?> - Lost & Found Admin</title>
  
  <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
  <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"> -->


  
  <style>
    .card {
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 20px;
    }
    
    .badge-status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .table-responsive {
      border-radius: 8px;
      overflow: hidden;
    }
    
    .page-header {
      margin-bottom: 30px;
    }
    
    .alert {
      border-radius: 8px;
      margin-bottom: 20px;
    }

    
  </style>
</head>
<body>
<div class="container-scroller">
  
  <!-- Navbar -->
  <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
      <a class="navbar-brand brand-logo" href="index.php">
        <span style="font-weight: bold; font-size: 20px; color: #4B49AC;">Lost & Found</span>
      </a>
      <a class="navbar-brand brand-logo-mini" href="index.php">
        <span style="font-weight: bold; font-size: 16px; color: #4B49AC;">L&F</span>
      </a>
      <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
        <span class="icon-menu"></span>
      </button>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-center">
      <h5 class="mb-0 font-weight-medium d-none d-lg-flex">Lost & Found Admin Dashboard</h5>
      <ul class="navbar-nav navbar-nav-right">
        <li class="nav-item dropdown">
          <a class="nav-link" id="profileDropdown" href="#" data-bs-toggle="dropdown">
            <div class="navbar-profile">
              <img class="img-xs rounded-circle" src="assets/images/faces/face8.jpg" alt="Profile">
              <p class="mb-0 d-none d-sm-block navbar-profile-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
            </div>
          </a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
            <a href="profile.php" class="dropdown-item">
              <i class="icon-user text-primary"></i> Profile
            </a>
            <a href="settings.php" class="dropdown-item">
              <i class="icon-settings text-primary"></i> Settings
            </a>
            <div class="dropdown-divider"></div>
            <a href="../../logout.php" class="dropdown-item">
              <i class="icon-power text-primary"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </div>
  </nav>
  
  <div class="container-fluid page-body-wrapper">
    
    <!-- Sidebar -->
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <ul class="nav">
        <li class="nav-item nav-profile">
          <a href="#" class="nav-link">
            <div class="profile-image">
              <img class="img-xs rounded-circle" src="assets/images/faces/face8.jpg" alt="profile image">
              <div class="dot-indicator bg-success"></div>
            </div>
            <div class="text-wrapper">
              <p class="profile-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?></p>
              <p class="designation"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Admin') ?></p>
            </div>
          </a>
        </li>
        
        <li class="nav-item nav-category">
          <span class="nav-link">NAVIGATION</span>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="icon-screen-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>
        
        <li class="nav-item nav-category">
          <span class="nav-link">ITEMS MANAGEMENT</span>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'items.php' ? 'active' : '' ?>" href="items.php">
            <i class="icon-bag menu-icon"></i>
            <span class="menu-title">Items</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'claims.php' ? 'active' : '' ?>" href="claims.php">
            <i class="icon-briefcase menu-icon"></i>
            <span class="menu-title">Claims</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : '' ?>" href="admins.php">
            <i class="icon-plus menu-icon"></i>
            <span class="menu-title">Admins</span>
          </a>
        </li>

        <li class="nav-item nav-back">
            <a href="/projek-imk/index.php" class="btn btn-outline-light" title="Back to Main Site">
                <i class="icon-home"></i> Main Site
            </a>
        </li>
      </ul>
    </nav>
    
    <div class="main-panel">
      <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
          <h3 class="page-title">
            <i class="<?= htmlspecialchars($icon) ?> text-primary"></i>
            <?= htmlspecialchars($title) ?>
          </h3>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($title) ?></li>
            </ol>
          </nav>
        </div>
        
        <?= $content ?>
        
      </div>
      
      <!-- Footer -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
            Copyright © <?= date('Y') ?> Lost & Found System
          </span>
        </div>
      </footer>
      
    </div>
  </div>
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
    <?php
    return ob_get_clean();
}
?>