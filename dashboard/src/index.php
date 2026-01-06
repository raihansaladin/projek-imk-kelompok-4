<?php
// dashboard/dashboard.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Hanya admin yang bisa akses
requireAdmin();
// handle POST actions (update status, approve/reject, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['action'])) {
    try {
      if ($_POST['action'] === 'update_item_status' && !empty($_POST['item_id']) && !empty($_POST['status'])) {
        $stmt = $pdo->prepare('UPDATE items SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$_POST['status'], $_POST['item_id']]);
      }
      if ($_POST['action'] === 'delete_item' && !empty($_POST['item_id'])) {
        $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$_POST['item_id']]);
      }
      if ($_POST['action'] === 'approve_claim' && !empty($_POST['claim_id'])) {
        $stmt = $pdo->prepare('UPDATE claims SET status = "approved", reviewed_at = NOW() WHERE id = ?');
        $stmt->execute([$_POST['claim_id']]);
      }
      if ($_POST['action'] === 'reject_claim' && !empty($_POST['claim_id'])) {
        $stmt = $pdo->prepare('UPDATE claims SET status = "rejected", reviewed_at = NOW() WHERE id = ?');
        $stmt->execute([$_POST['claim_id']]);
      }
    } catch (PDOException $e) {
      // You may log error in production
    }
  }
  header('Location: ' . $_SERVER['REQUEST_URI']);
  exit();
}

// fetch dashboard data
try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'lost'");
  $stmt->execute();
  $lost_count = $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'found'");
  $stmt->execute();
  $found_count = $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE status = 'pending'");
  $stmt->execute();
  $claims_pending = $stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE status = 'matched'");
  $stmt->execute();
  $matches_count = $stmt->fetchColumn();

  $items = $pdo->query('SELECT * FROM items ORDER BY created_at DESC LIMIT 100')->fetchAll();
  $claims = $pdo->query('SELECT c.*, i.title AS item_title FROM claims c JOIN items i ON c.item_id = i.id ORDER BY c.created_at DESC LIMIT 100')->fetchAll();
} catch (PDOException $e) {
  $lost_count = $found_count = $claims_pending = $matches_count = 0;
  $items = [];
  $claims = [];
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lost & Found - Admin Dashboard</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="assets/vendors/jvectormap/jquery-jvectormap.css">
    <link rel="stylesheet" href="assets/vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="assets/vendors/chartist/chartist.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="row p-0 m-0 proBanner" id="proBanner">
        <div class="col-md-12 p-0 m-0">
          <div class="card-body card-body-padding d-flex align-items-center justify-content-between">
            <div class="ps-lg-1">
              <div class="d-flex align-items-center justify-content-between">
                <p class="mb-0 font-weight-medium me-3 buy-now-text">Free 24/7 customer support, updates, and more with this template!</p>
                <a href="https://www.bootstrapdash.com/product/stellar-admin-template/" target="_blank" class="btn me-2 buy-now-btn border-0">Buy Now</a>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <a href="https://www.bootstrapdash.com/product/stellar-admin-template/"><i class="icon-home me-3 text-white"></i></a>
              <button id="bannerClose" class="btn border-0 p-0">
                <i class="icon-close text-white me-0"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- partial:partials/_navbar.html -->
      <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
          <a class="navbar-brand brand-logo" href="index.html">
            <img src="assets/images/logo.svg" alt="logo" class="logo-dark" />
            <img src="assets/images/logo-light.svg" alt="logo-light" class="logo-light">
          </a>
          <a class="navbar-brand brand-logo-mini" href="index.html"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="icon-menu"></span>
          </button>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-center">
          <h5 class="mb-0 font-weight-medium d-none d-lg-flex">Welcome to Lost & Found Admin</h5>
          <ul class="navbar-nav navbar-nav-right">
            <form class="search-form d-none d-md-block" action="#">
              <i class="icon-magnifier"></i>
              <input type="search" class="form-control" placeholder="Search Here" title="Search here">
            </form>
            <li class="nav-item"><a href="#" class="nav-link"><i class="icon-basket-loaded"></i></a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="icon-chart"></i></a></li>
            <li class="nav-item dropdown">
              <a class="nav-link count-indicator message-dropdown" id="messageDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="icon-speech"></i>
                <span class="count">7</span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="messageDropdown">
                <a class="dropdown-item py-3">
                  <p class="mb-0 font-weight-medium float-start me-2">You have 7 unread mails </p>
                  <span class="badge badge-pill badge-primary float-end">View all</span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face10.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis font-weight-medium text-dark">Marian Garner </p>
                    <p class="font-weight-light small-text"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face12.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis font-weight-medium text-dark">David Grey </p>
                    <p class="font-weight-light small-text"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face1.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis font-weight-medium text-dark">Travis Jenkins </p>
                    <p class="font-weight-light small-text"> The meeting is cancelled </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown language-dropdown d-none d-sm-flex align-items-center">
              <a class="nav-link d-flex align-items-center dropdown-toggle" id="LanguageDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="d-inline-flex">
                  <i class="flag-icon flag-icon-us"></i>
                </div>
                <span class="profile-text font-weight-normal">English</span>
              </a>
              <div class="dropdown-menu dropdown-menu-left navbar-dropdown py-2" aria-labelledby="LanguageDropdown">
                <a class="dropdown-item">
                  <i class="flag-icon flag-icon-us"></i> English </a>
                <a class="dropdown-item">
                  <i class="flag-icon flag-icon-fr"></i> French </a>
                <a class="dropdown-item">
                  <i class="flag-icon flag-icon-ae"></i> Arabic </a>
                <a class="dropdown-item">
                  <i class="flag-icon flag-icon-ru"></i> Russian </a>
              </div>
            </li>
            <li class="nav-item dropdown d-none d-xl-inline-flex user-dropdown">
              <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="img-xs rounded-circle ms-2" src="assets/images/faces/face8.jpg" alt="Profile image"> <span class="font-weight-normal"> Henry Klein </span></a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="img-md rounded-circle" src="assets/images/faces/face8.jpg" alt="Profile image">
                  <p class="mb-1 mt-3">Henry Klein</p>
                  <p class="font-weight-light text-muted mb-0">kleinhenry@gmail.com</p>
                </div>
                <a class="dropdown-item"><i class="dropdown-item-icon icon-user text-primary"></i> My Profile <span class="badge badge-pill badge-danger">1</span></a>
                <a class="dropdown-item"><i class="dropdown-item-icon icon-speech text-primary"></i> Messages</a>
                <a class="dropdown-item"><i class="dropdown-item-icon icon-energy text-primary"></i> Activity</a>
                <a class="dropdown-item"><i class="dropdown-item-icon icon-question text-primary"></i> FAQ</a>
                <a class="dropdown-item"><i class="dropdown-item-icon icon-power text-primary"></i>Sign Out</a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="icon-menu"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <!-- partial:partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item navbar-brand-mini-wrapper">
      <a class="nav-link navbar-brand brand-logo-mini" href="index.php">
        <img src="assets/images/logo-mini.svg" alt="logo" />
      </a>
    </li>
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="profile-image">
          <img class="img-xs rounded-circle" src="assets/images/faces/face8.jpg" alt="profile image">
          <div class="dot-indicator bg-success"></div>
        </div>
        <div class="text-wrapper">
          <p class="profile-name">Administrator</p>
          <p class="designation">Admin Panel</p>
        </div>
      </a>
    </li>
    
    <!-- DASHBOARD -->
    <li class="nav-item nav-category">
      <span class="nav-link">Navigation</span>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php">
        <span class="menu-title">Dashboard</span>
        <i class="icon-screen-desktop menu-icon"></i>
      </a>
    </li>
    
    <!-- ITEMS MANAGEMENT -->
    <li class="nav-item nav-category">
      <span class="nav-link">Items Management</span>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="items.php">
        <span class="menu-title">Items</span>
        <i class="icon-bag menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="claims.php">
        <span class="menu-title">claims</span>
        <i class="icon-briefcase menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="admins.php">
        <span class="menu-title">Admins</span>
        <i class="icon-plus menu-icon"></i>
      </a>
    </li>
    
    
  </ul>
</nav>
<!-- partial -->
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-md-8 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body performane-indicator-card">
                    <div class="d-sm-flex">
                      <h4 class="card-title flex-shrink-1">Performance Indicator</h4>
                      <p class="m-sm-0 ms-sm-auto flex-shrink-0">
                        <span class="data-time-range ms-0">7d</span>
                        <span class="data-time-range active">2w</span>
                        <span class="data-time-range">1m</span>
                        <span class="data-time-range">3m</span>
                        <span class="data-time-range">6m</span>
                      </p>
                    </div>
                    <div class="d-sm-flex flex-wrap mt-3">
                      <div class="d-flex align-items-center">
                        <span class="dot-indicator bg-primary ms-2"></span>
                        <p class="mb-0 ms-2 text-muted font-weight-semibold">Complaints (2098)</p>
                      </div>
                      <div class="d-flex align-items-center">
                        <span class="dot-indicator bg-info ms-2"></span>
                        <p class="mb-0 ms-2 text-muted font-weight-semibold"> Task Done (1123)</p>
                      </div>
                      <div class="d-flex align-items-center">
                        <span class="dot-indicator bg-danger ms-2"></span>
                        <p class="mb-0 ms-2 text-muted font-weight-semibold">Attends (876)</p>
                      </div>
                    </div>
                    <div class="dotted-chart-height">
                      <canvas id="performance-indicator-chart" class="mt-5"></canvas>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Sessions by channel</h4>
                    <div class="aligner-wrapper py-3">
                      <div class="doughnut-chart-height">
                        <canvas id="sessionsDoughnutChart" height="210"></canvas>
                      </div>
                      <div class="wrapper d-flex flex-column justify-content-center absolute absolute-center">
                        <h2 class="text-center mb-0 font-weight-bold">8.234</h2>
                        <small class="d-block text-center text-muted  font-weight-semibold mb-0">Total Leads</small>
                      </div>
                    </div>
                    <div class="wrapper mt-4 d-flex flex-wrap align-items-cente">
                      <div class="d-flex">
                        <span class="square-indicator bg-danger ms-2"></span>
                        <p class="mb-0 ms-2">Assigned</p>
                      </div>
                      <div class="d-flex">
                        <span class="square-indicator bg-success ms-2"></span>
                        <p class="mb-0 ms-2">Not Assigned</p>
                      </div>
                      <div class="d-flex">
                        <span class="square-indicator bg-warning ms-2"></span>
                        <p class="mb-0 ms-2">Reassigned</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Quick Action Toolbar Ends-->
            <div class="row">
              <div class="col-md-12 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-12">
                        <div class="d-sm-flex align-items-baseline report-summary-header">
                          <h5 class="font-weight-semibold">Report Summary</h5> <span class="ms-auto">Updated Report</span> <button class="btn btn-icons border-0 p-2"><i class="icon-refresh"></i></button>
                        </div>
                      </div>
                    </div>
                    <div class="row report-inner-cards-wrapper">
                      <div class=" col-md -6 col-xl report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">LOST ITEMS</span>
                          <h4><?= (int)($lost_count ?? 0) ?></h4>
                          <span class="report-count"> recent</span>
                        </div>
                        <div class="inner-card-icon bg-success">
                          <i class="icon-rocket"></i>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">FOUND ITEMS</span>
                          <h4><?= (int)($found_count ?? 0) ?></h4>
                          <span class="report-count"> recent</span>
                        </div>
                        <div class="inner-card-icon bg-danger">
                          <i class="icon-briefcase"></i>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">CLAIMS PENDING</span>
                          <h4><?= (int)($claims_pending ?? 0) ?></h4>
                          <span class="report-count"> awaiting review</span>
                        </div>
                        <div class="inner-card-icon bg-warning">
                          <i class="icon-globe-alt"></i>
                        </div>
                      </div>
                      <div class="col-md-6 col-xl report-inner-card">
                        <div class="inner-card-text">
                          <span class="report-title">MATCHES</span>
                          <h4><?= (int)($matches_count ?? 0) ?></h4>
                          <span class="report-count"> confirmed</span>
                        </div>
                        <div class="inner-card-icon bg-primary">
                          <i class="icon-diamond"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-sm-flex align-items-center mb-4">
                      <h4 class="card-title mb-sm-0">Reported Items</h4>
                      <a href="#" class="text-dark ms-auto mb-3 mb-sm-0"> View all Reports</a>
                    </div>
                    <div class="table-responsive border rounded p-1">
                      <table class="table">
                        <thead>
                          <tr>
                            <th class="font-weight-bold">Reporter</th>
                            <th class="font-weight-bold">Item</th>
                            <th class="font-weight-bold">Location</th>
                            <th class="font-weight-bold">Reported at</th>
                            <th class="font-weight-bold">Event date</th>
                            <th class="font-weight-bold">Status</th>
                            <th class="font-weight-bold">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($items as $it): ?>
                          <tr>
                            <td><?= htmlspecialchars($it['user_name'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($it['title']) ?> </small></td>
                            <td><?= htmlspecialchars($it['location']) ?></td>
                            <td><?= htmlspecialchars($it['created_at']) ?></td>
                            <td><?= htmlspecialchars($it['date_reported']) ?></td>
                            <td>
                              <div class="badge badge-<?= $it['status'] === 'open' ? 'danger' : ($it['status'] === 'matched' ? 'success' : 'info') ?> p-2"><?= htmlspecialchars($it['status']) ?></div>
                            </td>
                            <td>
                              <form method="post" style="display:inline-block; margin-right:6px;">
                                <input type="hidden" name="action" value="update_item_status">
                                <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="display:inline-block; width:auto;">
                                  <option value="open" <?= $it['status'] === 'open' ? 'selected' : '' ?>>open</option>
                                  <option value="matched" <?= $it['status'] === 'matched' ? 'selected' : '' ?>>matched</option>
                                  <option value="returned" <?= $it['status'] === 'returned' ? 'selected' : '' ?>>returned</option>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Update</button>
                              </form>
                              <form method="post" style="display:inline-block;" onsubmit="return confirm('Hapus item ini?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                              </form>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <div class="d-flex mt-4 flex-wrap align-items-center">
                      <p class="text-muted mb-sm-0">Showing <?= count($items) ?> entries</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-sm-flex align-items-center mb-4">
                      <h4 class="card-title mb-sm-0">Claims</h4>
                      <a href="#" class="text-dark ms-auto mb-3 mb-sm-0"> View all Claims</a>
                    </div>
                    <div class="table-responsive border rounded p-1">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Claim ID</th>
                            <th>Item</th>
                            <th>Claimer</th>
                            <th>Contact</th>
                            <th>Proof</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($claims as $c): ?>
                          <tr>
                            <td><?= (int)$c['id'] ?></td>
                            <td><?= htmlspecialchars($c['item_title']) ?></td>
                            <td><?= htmlspecialchars($c['claimer_name']) ?></td>
                            <td><?= htmlspecialchars($c['claimer_contact']) ?></td>
                            <td><?= nl2br(htmlspecialchars($c['proof_text'])) ?></td>
                            <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                            <td><div class="badge badge-<?= $c['status']==='pending' ? 'warning' : ($c['status']==='approved' ? 'success' : 'danger') ?> p-2"><?= htmlspecialchars($c['status']) ?></div></td>
                            <td>
                              <?php if ($c['status'] === 'pending'): ?>
                                <form method="post" style="display:inline-block; margin-right:6px;">
                                  <input type="hidden" name="action" value="approve_claim">
                                  <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                                  <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                </form>
                                <form method="post" style="display:inline-block;">
                                  <input type="hidden" name="action" value="reject_claim">
                                  <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                                  <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                </form>
                              <?php else: ?>
                                —
                              <?php endif; ?>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2026 Lost & Found. All rights reserved. <a href="#"> Terms of use</a><a href="#">Privacy Policy</a></span>
              <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i class="icon-heart text-danger"></i></span>
            </div>
          </footer>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="assets/vendors/chart.js/chart.umd.js"></script>
    <script src="assets/vendors/jvectormap/jquery-jvectormap.min.js"></script>
    <script src="assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="assets/vendors/moment/moment.min.js"></script>
    <script src="assets/vendors/daterangepicker/daterangepicker.js"></script>
    <script src="assets/vendors/chartist/chartist.min.js"></script>
    <script src="assets/vendors/progressbar.js/progressbar.min.js"></script>
    <script src="assets/js/jquery.cookie.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/hoverable-collapse.js"></script>
    <script src="assets/js/misc.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="assets/js/dashboard.js"></script>
    <!-- End custom js for this page -->
  </body>
</html>