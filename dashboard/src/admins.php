<?php
// dashboard/src/admins.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$errors = [];
$success = "";

// Tambah admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_users') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? 'admin';

  if ($name === '') $errors[] = "Name wajib.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
  if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
  if (!in_array($role, ['admin','superadmin'], true)) $role = 'admin';

  if (!$errors) {
    try {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,?,NOW())");
      $stmt->execute([$name,$email,$hash,$role]);
      $success = "Admin berhasil dibuat.";
    } catch (PDOException $e) {
      $errors[] = "Gagal membuat admin. Email mungkin sudah digunakan.";
    }
  }
}

// List admins
$admins = [];
try {
  $admins = $pdo->query("SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Admins - Admin</title>
  <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
</head>
<body>
<div class="container-scroller">
  <!-- ===== COPY NAVBAR + SIDEBAR DARI index.php kamu ===== -->

  <div class="container-fluid page-body-wrapper">
    <div class="main-panel">
      <div class="content-wrapper">

        <div class="page-header">
          <h3 class="page-title">Admins (tabel: admins)</h3>
        </div>

        <div class="card">
          <div class="card-body">
            <?php if ($errors): ?>
              <div class="alert alert-danger"><?= implode("<br>", array_map('htmlspecialchars',$errors)) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <h4 class="card-title">Add Admin</h4>
            <form method="post" class="row" style="gap:10px;align-items:end;">
              <input type="hidden" name="action" value="create_admin">

              <div class="form-group col-md-3">
                <label>Name</label>
                <input class="form-control" name="name" required>
              </div>

              <div class="form-group col-md-3">
                <label>Email</label>
                <input class="form-control" name="email" type="email" required>
              </div>

              <div class="form-group col-md-3">
                <label>Password</label>
                <input class="form-control" name="password" type="password" required>
              </div>

              <div class="form-group col-md-2">
                <label>Role</label>
                <select class="form-control" name="role">
                  <option value="admin">admin</option>
                  <option value="superadmin">superadmin</option>
                </select>
              </div>

              <div class="form-group col-md-1">
                <button class="btn btn-primary">Add</button>
              </div>
            </form>

            <hr>

            <div class="table-responsive border rounded p-1">
              <table class="table">
                <thead>
                  <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$admins): ?>
                    <tr><td colspan="5">No admins</td></tr>
                  <?php endif; ?>

                  <?php foreach ($admins as $a): ?>
                    <tr>
                      <td><?= (int)$a['id'] ?></td>
                      <td><?= htmlspecialchars($a['name']) ?></td>
                      <td><?= htmlspecialchars($a['email']) ?></td>
                      <td><?= htmlspecialchars($a['role']) ?></td>
                      <td><?= htmlspecialchars($a['created_at']) ?></td>
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
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
</body>
</html>
