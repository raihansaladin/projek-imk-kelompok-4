<?php
// dashboard/src/index.php

// Pastikan path yang benar untuk config files
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Panggil fungsi auth
requireAdmin();

// Fetch dashboard statistics
try {
    // Total items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items");
    $stmt->execute();
    $total_items = $stmt->fetchColumn();
    
    // Lost items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'lost'");
    $stmt->execute();
    $lost_count = $stmt->fetchColumn();
    
    // Found items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'found'");
    $stmt->execute();
    $found_count = $stmt->fetchColumn();
    
    // Open items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE status = 'open'");
    $stmt->execute();
    $open_count = $stmt->fetchColumn();
    
    // Returned items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE status = 'returned'");
    $stmt->execute();
    $returned_count = $stmt->fetchColumn();
    
    // Pending claims count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE status = 'pending'");
    $stmt->execute();
    $pending_claims = $stmt->fetchColumn();
    
    // Approved claims count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE status = 'approved'");
    $stmt->execute();
    $approved_claims = $stmt->fetchColumn();
    
    // Recent items (last 10)
    $items = $pdo->query('SELECT * FROM items ORDER BY created_at DESC LIMIT 10')->fetchAll();
    
    // Recent claims (last 10)
    $claims = $pdo->query('SELECT c.*, i.title AS item_title, i.type AS item_type 
                          FROM claims c 
                          JOIN items i ON c.item_id = i.id 
                          ORDER BY c.created_at DESC LIMIT 10')->fetchAll();
                          
} catch (PDOException $e) {
    // Set default values if error
    $total_items = $lost_count = $found_count = $open_count = $returned_count = $pending_claims = $approved_claims = 0;
    $items = [];
    $claims = [];
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard - Lost & Found Admin</title>
  
  <!-- CSS -->
  <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
  
  <style>
    .stat-card {
      border-radius: 10px;
      border: none;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
      font-size: 2.5rem;
      opacity: 0.8;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .badge-status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
  </style>
</head>
<body>
<div class="container-scroller">
  
  <!-- Navbar Manual (tanpa include) -->
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
              <p class="mb-0 d-none d-sm-block navbar-profile-name"><?= $_SESSION['user_name'] ?? 'Admin' ?></p>
            </div>
          </a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
            <a href="profile.php" class="dropdown-item">
              <i class="mdi mdi-account text-primary"></i> Profile
            </a>
            <a href="settings.php" class="dropdown-item">
              <i class="mdi mdi-settings text-primary"></i> Settings
            </a>
            <div class="dropdown-divider"></div>
            <a href="../../logout.php" class="dropdown-item">
              <i class="mdi mdi-logout text-primary"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </div>
  </nav>
  
  <div class="container-fluid page-body-wrapper">
    
    <!-- Sidebar Manual (tanpa include) -->
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <ul class="nav">
        <li class="nav-item nav-profile">
          <a href="#" class="nav-link">
            <div class="profile-image">
              <img class="img-xs rounded-circle" src="assets/images/faces/face8.jpg" alt="profile image">
              <div class="dot-indicator bg-success"></div>
            </div>
            <div class="text-wrapper">
              <p class="profile-name"><?= $_SESSION['user_name'] ?? 'Administrator' ?></p>
              <p class="designation"><?= $_SESSION['user_role'] ?? 'Admin' ?></p>
            </div>
          </a>
        </li>
        
        <li class="nav-item nav-category">
          <span class="nav-link">NAVIGATION</span>
        </li>
        
        <li class="nav-item">
          <a class="nav-link active" href="index.php">
            <i class="icon-screen-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>
        
        <li class="nav-item nav-category">
          <span class="nav-link">ITEMS MANAGEMENT</span>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="items.php">
            <i class="icon-bag menu-icon"></i>
            <span class="menu-title">Items</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="claims.php">
            <i class="icon-briefcase menu-icon"></i>
            <span class="menu-title">Claims</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="admins.php">
            <i class="icon-plus menu-icon"></i>
            <span class="menu-title">Admins</span>
          </a>
        </li>
      </ul>
    </nav>
    
    <div class="main-panel">
      <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
          <h3 class="page-title">
            <i class="icon-screen-desktop text-primary"></i>
            Dashboard Overview
          </h3>
        </div>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row">
          <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 grid-margin stretch-card">
            <div class="card stat-card bg-primary text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="stat-number"><?= $total_items ?></div>
                    <div class="stat-label">Total Items</div>
                  </div>
                  <div class="stat-icon">
                    <i class="icon-bag"></i>
                  </div>
                </div>
                <div class="mt-3">
                  <small>Lost: <?= $lost_count ?> | Found: <?= $found_count ?></small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 grid-margin stretch-card">
            <div class="card stat-card bg-warning text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="stat-number"><?= $pending_claims ?></div>
                    <div class="stat-label">Pending Claims</div>
                  </div>
                  <div class="stat-icon">
                    <i class="icon-clock"></i>
                  </div>
                </div>
                <div class="mt-3">
                  <small>Awaiting review</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 grid-margin stretch-card">
            <div class="card stat-card bg-success text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="stat-number"><?= $returned_count ?></div>
                    <div class="stat-label">Returned Items</div>
                  </div>
                  <div class="stat-icon">
                    <i class="icon-check"></i>
                  </div>
                </div>
                <div class="mt-3">
                  <small>Successfully returned</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 grid-margin stretch-card">
            <div class="card stat-card bg-info text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="stat-number"><?= $open_count ?></div>
                    <div class="stat-label">Open Items</div>
                  </div>
                  <div class="stat-icon">
                    <i class="icon-lock-open"></i>
                  </div>
                </div>
                <div class="mt-3">
                  <small>Need attention</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
          <!-- Recent Items -->
          <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="card-title">Recent Items</h4>
                  <a href="items.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($items)): ?>
                      <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                          No items found
                        </td>
                      </tr>
                      <?php endif; ?>
                      
                      <?php foreach ($items as $item): ?>
                      <tr>
                        <td>#<?= $item['id'] ?></td>
                        <td>
                          <strong><?= htmlspecialchars($item['title']) ?></strong>
                          <?php if ($item['description']): ?>
                          <br><small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?>...</small>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge-status badge-<?= $item['type'] === 'lost' ? 'danger' : 'success' ?>">
                            <?= ucfirst($item['type']) ?>
                          </span>
                        </td>
                        <td><?= htmlspecialchars($item['location']) ?></td>
                        <td>
                          <span class="badge-status badge-<?= 
                            $item['status'] === 'open' ? 'warning' : 
                            ($item['status'] === 'returned' ? 'success' : 'info')
                          ?>">
                            <?= ucfirst($item['status']) ?>
                          </span>
                        </td>
                        <td>
                          <small><?= date('M d, Y', strtotime($item['created_at'])) ?></small>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Claims -->
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="card-title">Recent Claims</h4>
                  <a href="claims.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                
                <div class="recent-claims">
                  <?php if (empty($claims)): ?>
                  <div class="text-center text-muted py-3">
                    No claims found
                  </div>
                  <?php endif; ?>
                  
                  <?php foreach ($claims as $claim): ?>
                  <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex justify-content-between">
                      <h6 class="mb-1"><?= htmlspecialchars($claim['item_title']) ?></h6>
                      <small class="text-muted"><?= date('M d', strtotime($claim['created_at'])) ?></small>
                    </div>
                    <p class="mb-1">
                      <small>Claimer: <?= htmlspecialchars($claim['claimer_name']) ?></small>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="badge-status badge-<?= 
                        $claim['status'] === 'pending' ? 'warning' : 
                        ($claim['status'] === 'approved' ? 'success' : 'danger')
                      ?>">
                        <?= ucfirst($claim['status']) ?>
                      </span>
                      <small class="text-muted">Item: <?= ucfirst($claim['item_type']) ?></small>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      </div>
      
      <!-- Footer -->
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
            Copyright © <?= date('Y') ?> Lost & Found System
          </span>
          <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
            Admin Dashboard v1.0
          </span>
        </div>
      </footer>
      
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
</body>
</html>