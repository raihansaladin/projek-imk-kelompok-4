<?php
// dashboard/src/claims.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$status = trim($_GET['status'] ?? '');

// ACTION approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $claim_id = (int)($_POST['claim_id'] ?? 0);

    try {
        if ($_POST['action'] === 'approve') {
            // 1) approve claim
            $pdo->prepare("UPDATE claims SET status='approved', reviewed_at=NOW() WHERE id=?")->execute([$claim_id]);

            // 2) item jadi returned
            $itemId = (int)$pdo->query("SELECT item_id FROM claims WHERE id={$claim_id}")->fetchColumn();
            if ($itemId) {
                $pdo->prepare("UPDATE items SET status='returned', is_claimed=1 WHERE id=?")->execute([$itemId]);
            }
        }

        if ($_POST['action'] === 'reject') {
            $pdo->prepare("UPDATE claims SET status='rejected', reviewed_at=NOW() WHERE id=?")->execute([$claim_id]);
        }
    } catch (PDOException $e) {
        // Error handling
    }

    header("Location: claims.php?" . http_build_query($_GET));
    exit;
}

// Get statistics
try {
    $total_claims = $pdo->query("SELECT COUNT(*) FROM claims")->fetchColumn();
    $pending_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetchColumn();
    $approved_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'approved'")->fetchColumn();
    $rejected_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'rejected'")->fetchColumn();
} catch (PDOException $e) {
    $total_claims = $pending_claims = $approved_claims = $rejected_claims = 0;
}

// WHERE
$where = "WHERE 1=1";
$params = [];
if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
    $where .= " AND c.status = ?";
    $params[] = $status;
}

$sql = "
  SELECT c.*, i.title AS item_title, i.type AS item_type, i.status AS item_status,
         DATE_FORMAT(c.created_at, '%b %d, %Y') as claim_date
  FROM claims c
  JOIN items i ON c.item_id = i.id
  $where
  ORDER BY c.created_at DESC
  LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll();

// Prepare content
ob_start();
?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="text-center">
                    <h2 class="mb-0"><?= $total_claims ?></h2>
                    <p class="mb-0">Total Claims</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="text-center">
                    <h2 class="mb-0"><?= $pending_claims ?></h2>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="text-center">
                    <h2 class="mb-0"><?= $approved_claims ?></h2>
                    <p class="mb-0">Approved</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="text-center">
                    <h2 class="mb-0"><?= $rejected_claims ?></h2>
                    <p class="mb-0">Rejected</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="card-title">Claims Management</h4>
            
            <form method="get" class="form-inline">
                <div class="form-group mr-2">
                    <select class="form-control" name="status" onchange="this.form.submit()">
                        <option value="">All Claims</option>
                        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
                    </select>
                </div>
                <a href="claims.php" class="btn btn-light">Reset</a>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Claimer</th>
                        <th>Contact</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($claims)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No claims found
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($claims as $c): ?>
                        <tr>
                            <td>#<?= (int)$c['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($c['item_title'] ?? '-') ?></strong>
                                <br>
                                <small class="text-muted">Item: <?= htmlspecialchars($c['item_type'] ?? '-') ?></small>
                            </td>
                            <td>
                                <span class="badge badge-<?= $c['item_type'] === 'lost' ? 'danger' : 'success' ?>">
                                    <?= ucfirst($c['item_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['claimer_name'] ?? '-') ?></td>
                            <td>
                                <small><?= htmlspecialchars($c['claimer_contact'] ?? '-') ?></small>
                            </td>
                            <td>
                                <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars(substr($c['proof_text'] ?? '', 0, 50)) ?>...
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= 
                                    $c['status'] === 'pending' ? 'warning' : 
                                    ($c['status'] === 'approved' ? 'success' : 'danger')
                                ?>">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($c['claim_date']) ?></small>
                            </td>
                            <td>
                                <?php if ($c['status'] === 'pending'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                                            <button class="btn btn-success" name="action" value="approve"
                                                onclick="return confirm('Approve this claim?')">
                                                <i class="icon-check"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                                            <button class="btn btn-danger" name="action" value="reject"
                                                onclick="return confirm('Reject this claim?')">
                                                <i class="icon-close"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <small class="text-muted">
                Showing <?= count($claims) ?> of <?= $total_claims ?> claims
                <?php if ($status): ?>
                    (Filtered by: <?= $status ?>)
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Include base template
require_once __DIR__ . '/base.php';
echo getBaseTemplate('Claims Management', $content, 'icon-briefcase');
?>