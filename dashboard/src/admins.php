<?php
// dashboard/src/admins.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$errors = [];
$success = "";

// Tambah admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_admin') {
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
            // Perbaiki: tabel users tidak punya kolom password_hash dan role
            // Sesuaikan dengan struktur tabel users yang ada
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Cek dulu struktur tabel users
            $stmt = $pdo->prepare("INSERT INTO users(username, email, password, full_name, created_at, is_active) 
                                  VALUES(?, ?, ?, ?, NOW(), 1)");
            
            // Buat username dari email
            $username = strtolower(explode('@', $email)[0]);
            $stmt->execute([$username, $email, $hash, $name]);
            $success = "Admin berhasil dibuat.";
        } catch (PDOException $e) {
            $errors[] = "Gagal membuat admin. Email mungkin sudah digunakan.";
        }
    }
}

// List admins
$admins = [];
try {
    $admins = $pdo->query("SELECT id, username, email, full_name, created_at, last_login, is_active 
                          FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Gagal memuat data admin: " . $e->getMessage();
}

// Prepare content
ob_start();
?>
<div class="card">
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <h4 class="card-title mb-4">Add New Admin</h4>
                <form method="post" class="forms-sample">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter full name" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="icon-plus"></i> Add Admin
                    </button>
                    <button type="reset" class="btn btn-light">Reset</button>
                </form>
            </div>
            
            <div class="col-md-6">
                <h4 class="card-title mb-4">Admin Statistics</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="mb-0"><?= count($admins) ?></h2>
                                    <p class="mb-0">Total Admins</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="text-center">
                                    <h2 class="mb-0">
                                        <?= count(array_filter($admins, function($a) { 
                                            return $a['is_active'] == 1; 
                                        })) ?>
                                    </h2>
                                    <p class="mb-0">Active Admins</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h4 class="card-title mb-4">Admin List</h4>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No admins found
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= (int)$admin['id'] ?></td>
                            <td><?= htmlspecialchars($admin['username']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['full_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $admin['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $admin['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($admin['created_at'])) ?></td>
                            <td>
                                <?php if ($admin['last_login']): ?>
                                    <?= date('M d, Y H:i', strtotime($admin['last_login'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Include base template
require_once __DIR__ . '/base.php';
echo getBaseTemplate('Admin Management', $content, 'icon-user-follow');
?>