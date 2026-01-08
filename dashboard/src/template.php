<?php
// dashboard/src/template.php
function renderAdminPage($title, $content_callback, $icon = 'icon-screen-desktop') {
    require_once __DIR__ . '/../../config/auth.php';
    require_once __DIR__ . '/../../config/database.php';
    requireAdmin();
    
    ob_start();
    call_user_func($content_callback);
    $content = ob_get_clean();
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{$title} - Lost & Found Admin</title>
  
  <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
</head>
<body>
<div class="container-scroller">
  
  <!-- Navbar -->
  <?php 
  \$navbar_path = __DIR__ . '/partials/_navbar.html';
  if (file_exists(\$navbar_path)) {
      include \$navbar_path;
  }
  ?>
  
  <div class="container-fluid page-body-wrapper">
    
    <!-- Sidebar -->
    <?php 
    \$sidebar_path = __DIR__ . '/partials/_sidebar.html';
    if (file_exists(\$sidebar_path)) {
        include \$sidebar_path;
    }
    ?>
    
    <div class="main-panel">
      <div class="content-wrapper">
        
        <!-- Page Header -->
        <div class="page-header">
          <h3 class="page-title">
            <i class="{$icon} text-primary"></i>
            {$title}
          </h3>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">{$title}</li>
            </ol>
          </nav>
        </div>
        
        <!-- Content -->
        {$content}
        
      </div>
      
      <!-- Footer -->
      <?php 
      \$footer_path = __DIR__ . '/partials/_footer.html';
      if (file_exists(\$footer_path)) {
          include \$footer_path;
      }
      ?>
      
    </div>
  </div>
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
</body>
</html>
HTML;
}
?>