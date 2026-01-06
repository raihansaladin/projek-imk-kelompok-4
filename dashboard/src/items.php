<?php
// dashboard/src/items.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$q = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');     // lost|found|''
$status = trim($_GET['status'] ?? ''); // open|matched|returned|''
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 20;
if ($limit > 100) $limit = 100;
$offset = ($page - 1) * $limit;

// ACTIONS
// ACTIONS: ADD / UPDATE STATUS / DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  $action = $_POST['action'];

  try {
    // ===== ADD ITEM =====
    if ($action === 'add_item') {
      $title = trim($_POST['title'] ?? '');
      $type  = $_POST['type'] ?? 'lost';
      $category = trim($_POST['category'] ?? 'Other');
      $location = trim($_POST['location'] ?? '');
      $event_date = $_POST['event_date'] ?? null;
      $description = trim($_POST['description'] ?? '');
      $status = $_POST['status'] ?? 'open';

      // optional contact fields (kalau kolomnya ada)
      $contact_type  = $_POST['contact_type'] ?? 'none';
      $contact_value = trim($_POST['contact_value'] ?? '');

      // VALIDASI
      $errors = [];
      if ($title === '') $errors[] = "Title wajib diisi.";
      if (!in_array($type, ['lost','found'], true)) $errors[] = "Type tidak valid.";
      if ($location === '') $errors[] = "Location wajib diisi.";
      if (!in_array($status, ['open','matched','returned'], true)) $status = 'open';

      // kalau ada error, simpan ke session untuk ditampilkan
      if ($errors) {
        $_SESSION['flash_error'] = implode(" ", $errors);
        header("Location: items.php");
        exit;
      }

      // INSERT (versi aman: coba insert kolom yang umum)
      $stmt = $pdo->prepare("
        INSERT INTO items (title, type, category, description, location, event_date, status, contact_type, contact_value, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
      ");
      $stmt->execute([
        $title, $type, $category, $description, $location,
        $event_date, $status, $contact_type, $contact_value
      ]);

      $_SESSION['flash_success'] = "Item berhasil ditambahkan.";
    }

    // ===== UPDATE STATUS =====
    if ($action === 'update_item_status') {
      $item_id = (int)($_POST['item_id'] ?? 0);
      $newStatus = $_POST['status'] ?? 'open';
      if (!in_array($newStatus, ['open','matched','returned'], true)) $newStatus = 'open';

      $stmt = $pdo->prepare("UPDATE items SET status=?, updated_at=NOW() WHERE id=?");
      $stmt->execute([$newStatus, $item_id]);

      $_SESSION['flash_success'] = "Status item berhasil diupdate.";
    }

    // ===== DELETE ITEM =====
    if ($action === 'delete_item') {
      $item_id = (int)($_POST['item_id'] ?? 0);

      // optional: hapus file gambar jika ada kolom image_path
      $img = null;
      try {
        $imgStmt = $pdo->prepare("SELECT image_path FROM items WHERE id=?");
        $imgStmt->execute([$item_id]);
        $img = $imgStmt->fetchColumn();
      } catch (Throwable $e) {}

      $stmt = $pdo->prepare("DELETE FROM items WHERE id=?");
      $stmt->execute([$item_id]);

      if ($img) {
        $path = __DIR__ . "/../../public/uploads/" . $img;
        if (is_file($path)) @unlink($path);
      }

      $_SESSION['flash_success'] = "Item berhasil dihapus.";
    }

  } catch (PDOException $e) {
    $_SESSION['flash_error'] = "DB Error: " . $e->getMessage();
  }

  header("Location: items.php");
  exit;
}


// WHERE
$where = "WHERE 1=1";
$params = [];

if ($type !== '' && in_array($type, ['lost','found'], true)) {
  $where .= " AND type = ?";
  $params[] = $type;
}

if ($status !== '' && in_array($status, ['open','matched','returned'], true)) {
  $where .= " AND status = ?";
  $params[] = $status;
}

if ($q !== '') {
  $where .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like; $params[] = $like;
}

// COUNT
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

// LIST (LIMIT/OFFSET ditempel INT)
$sql = "SELECT * FROM items $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Items - Admin</title>
  <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/vertical-light-layout/style.css">
</head>
<body>
<div class="container-scroller">

  <?php /* TOP NAV + SIDEBAR: paling mudah COPY dari index.php kamu,
           lalu paste bagian navbar + sidebar di sini juga.
           (Karena template dashboard biasanya tidak pakai partial) */ ?>

  <!-- ===== COPY NAVBAR + SIDEBAR DARI index.php kamu (yang sudah disederhanakan) ===== -->

  <div class="container-fluid page-body-wrapper">
    <!-- sidebar sudah di-copy dari index.php -->
    <div class="main-panel">
      <div class="content-wrapper">

        <div class="page-header">
          <h3 class="page-title">Items (tabel: items)</h3>
        </div>

        <div class="card">
          <div class="card-body">
            <form method="get" class="row" style="gap:10px;align-items:end;">
              <div class="form-group col-md-4">
                <label>Search</label>
                <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="title/location/desc">
              </div>

              <div class="form-group col-md-2">
                <label>Type</label>
                <select class="form-control" name="type">
                  <option value="">All</option>
                  <option value="lost"  <?= $type==='lost'?'selected':'' ?>>lost</option>
                  <option value="found" <?= $type==='found'?'selected':'' ?>>found</option>
                </select>
              </div>

              <div class="form-group col-md-2">
                <label>Status</label>
                <select class="form-control" name="status">
                  <option value="">All</option>
                  <option value="open"     <?= $status==='open'?'selected':'' ?>>open</option>
                  <option value="matched"  <?= $status==='matched'?'selected':'' ?>>matched</option>
                  <option value="returned" <?= $status==='returned'?'selected':'' ?>>returned</option>
                </select>
              </div>

              <div class="form-group col-md-2">
                <label>Limit</label>
                <select class="form-control" name="limit">
                  <?php foreach ([10,20,50,100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit===$l?'selected':'' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group col-md-2">
                <button class="btn btn-primary">Apply</button>
              </div>
            </form>

            <?php
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<?php if ($flashSuccess): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body">
    <h4 class="card-title">Tambah Item (Insert ke tabel items)</h4>

    <form method="post" class="row" style="gap:10px;align-items:end;">
      <input type="hidden" name="action" value="add_item">

      <div class="form-group col-md-4">
        <label>Title</label>
        <input class="form-control" name="title" required>
      </div>

      <div class="form-group col-md-2">
        <label>Type</label>
        <select class="form-control" name="type">
          <option value="lost">lost</option>
          <option value="found">found</option>
        </select>
      </div>

      <div class="form-group col-md-2">
        <label>Status</label>
        <select class="form-control" name="status">
          <option value="open">open</option>
          <option value="matched">matched</option>
          <option value="returned">returned</option>
        </select>
      </div>

      <div class="form-group col-md-4">
        <label>Category</label>
        <input class="form-control" name="category" placeholder="Electronics / ID Card / Bag...">
      </div>

      <div class="form-group col-md-4">
        <label>Location</label>
        <input class="form-control" name="location" required>
      </div>

      <div class="form-group col-md-2">
        <label>Event Date</label>
        <input type="date" class="form-control" name="event_date">
      </div>

      <div class="form-group col-md-6">
        <label>Description</label>
        <input class="form-control" name="description" placeholder="Deskripsi singkat...">
      </div>

      <div class="form-group col-md-2">
        <label>Contact Type</label>
        <select class="form-control" name="contact_type">
          <option value="none">none</option>
          <option value="whatsapp">whatsapp</option>
          <option value="email">email</option>
        </select>
      </div>

      <div class="form-group col-md-4">
        <label>Contact Value</label>
        <input class="form-control" name="contact_value" placeholder="08xxx / email@...">
      </div>

      <div class="form-group col-md-2">
        <button class="btn btn-primary" type="submit">Add Item</button>
      </div>
    </form>
  </div>
</div>

            <div class="table-responsive border rounded p-1 mt-3">
              <table class="table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Event Date</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="width:220px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$items): ?>
                    <tr><td colspan="8">No data</td></tr>
                  <?php endif; ?>

                  <?php foreach ($items as $i => $it): ?>
                    <tr>
                      <td><?= $offset + $i + 1 ?></td>
                      <td><?= htmlspecialchars($it['title'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($it['type'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($it['location'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($it['event_date'] ?? '-') ?></td>
                      <td>
                        <div class="badge badge-<?= ($it['status']==='open'?'danger':($it['status']==='matched'?'success':'info')) ?> p-2">
                          <?= htmlspecialchars($it['status'] ?? '-') ?>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($it['created_at'] ?? '-') ?></td>

                      <td>
                        <form method="post" style="display:inline-block;margin-right:6px;">
                          <input type="hidden" name="action" value="update_item_status">
                          <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                          <select name="status" class="form-select form-select-sm" style="display:inline-block;width:auto;">
                            <option value="open"     <?= ($it['status']==='open')?'selected':'' ?>>open</option>
                            <option value="matched"  <?= ($it['status']==='matched')?'selected':'' ?>>matched</option>
                            <option value="returned" <?= ($it['status']==='returned')?'selected':'' ?>>returned</option>
                          </select>
                          <button class="btn btn-sm btn-primary" type="submit">Update</button>
                        </form>
    <form method="post" onsubmit="return confirm('Hapus item ini?');" style="display:inline-block;">
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

            <div class="d-flex mt-3 flex-wrap align-items-center justify-content-between">
              <p class="text-muted mb-0">Total: <?= $totalItems ?> items</p>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php
                  $base = $_GET;
                  for ($p=1;$p<=$totalPages;$p++){
                    $base['page']=$p;
                    $url = "items.php?".http_build_query($base);
                    echo $p===$page
                      ? "<span class='badge badge-primary p-2'>$p</span>"
                      : "<a class='badge badge-light p-2' href='$url'>$p</a>";
                  }
                ?>
              </div>
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
