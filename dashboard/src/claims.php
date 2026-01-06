<?php
// dashboard/src/claims.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$status = trim($_GET['status'] ?? ''); // pending|approved|rejected|''

// ACTION approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  $claim_id = (int)($_POST['claim_id'] ?? 0);

  try {
    if ($_POST['action'] === 'approve') {
      // 1) approve claim
      $pdo->prepare("UPDATE claims SET status='approved', reviewed_at=NOW() WHERE id=?")->execute([$claim_id]);

      // 2) item jadi returned (sesuai requirement admin)
      $itemId = (int)$pdo->query("SELECT item_id FROM claims WHERE id={$claim_id}")->fetchColumn();
      if ($itemId) {
        $pdo->prepare("UPDATE items SET status='returned', updated_at=NOW() WHERE id=?")->execute([$itemId]);
      }
    }

    if ($_POST['action'] === 'reject') {
      $pdo->prepare("UPDATE claims SET status='rejected', reviewed_at=NOW() WHERE id=?")->execute([$claim_id]);
    }
  } catch (PDOException $e) {}

  header("Location: claims.php?" . http_build_query($_GET));
  exit;
}

// WHERE
$where = "WHERE 1=1";
$params = [];
if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
  $where .= " AND c.status = ?";
  $params[] = $status;
}

$sql = "
  SELECT c.*, i.title AS item_title, i.type AS item_type, i.status AS item_status
  FROM claims c
  JOIN items i ON c.item_id = i.id
  $where
  ORDER BY c.created_at DESC
  LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Claims - Admin</title>
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
          <h3 class="page-title">Claims (tabel: claims)</h3>
        </div>

        <div class="card">
          <div class="card-body">

            <form method="get" class="row" style="gap:10px;align-items:end;">
              <div class="form-group col-md-3">
                <label>Status</label>
                <select class="form-control" name="status">
                  <option value="">All</option>
                  <option value="pending"  <?= $status==='pending'?'selected':'' ?>>pending</option>
                  <option value="approved" <?= $status==='approved'?'selected':'' ?>>approved</option>
                  <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>rejected</option>
                </select>
              </div>
              <div class="form-group col-md-2">
                <button class="btn btn-primary">Filter</button>
              </div>
            </form>

            <div class="table-responsive border rounded p-1 mt-3">
              <table class="table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Claimer</th>
                    <th>Contact</th>
                    <th>Proof</th>
                    <th>Status</th>
                    <th style="width:200px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$claims): ?>
                    <tr><td colspan="8">No data</td></tr>
                  <?php endif; ?>

                  <?php foreach ($claims as $c): ?>
                    <tr>
                      <td><?= (int)$c['id'] ?></td>
                      <td><?= htmlspecialchars($c['item_title'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($c['item_type'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($c['claimer_name'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($c['claimer_contact'] ?? '-') ?></td>
                      <td style="white-space:normal;max-width:260px;"><?= nl2br(htmlspecialchars($c['proof_text'] ?? '-')) ?></td>
                      <td>
                        <div class="badge badge-<?= ($c['status']==='pending'?'warning':($c['status']==='approved'?'success':'danger')) ?> p-2">
                          <?= htmlspecialchars($c['status']) ?>
                        </div>
                      </td>
                      <td>
                        <?php if (($c['status'] ?? '') === 'pending'): ?>
                          <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;">
                            <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-sm btn-success" name="action" value="approve"
                              onclick="return confirm('Approve claim? Item akan jadi returned.')">Approve</button>
                            <button class="btn btn-sm btn-danger" name="action" value="reject"
                              onclick="return confirm('Reject claim?')">Reject</button>
                          </form>
                        <?php else: ?>
                          <span class="text-muted">—</span>
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
</div>

<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
</body>
</html>
