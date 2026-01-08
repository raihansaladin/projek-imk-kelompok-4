<?php
// dashboard/src/items.php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

// Inisialisasi variabel
$errors = [];
$success = "";
$form_data = [];

// ========== CREATE / UPDATE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ADD NEW ITEM
    if ($action === 'add_item') {
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'lost';
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $user_name = trim($_POST['user_name'] ?? '');
        $user_contact = trim($_POST['user_contact'] ?? '');
        $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'open';
        
        // Simpan data form untuk ditampilkan kembali jika error
        $form_data = $_POST;
        
        // Validasi
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($location)) $errors[] = "Location is required.";
        if (empty($user_name)) $errors[] = "Reporter name is required.";
        if (empty($user_contact)) $errors[] = "Contact information is required.";
        if (!in_array($type, ['lost', 'found'])) $type = 'lost';
        if (!in_array($status, ['open', 'matched', 'returned'])) $status = 'open';
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO items (title, description, type, status, user_name, user_contact, location, date_reported, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$title, $description, $type, $status, $user_name, $user_contact, $location, $date_reported]);
                
                if ($result) {
                    $success = "✅ Item added successfully!";
                    $form_data = []; // Reset form data setelah sukses
                    
                    // Redirect untuk menghindari resubmission
                    header("Location: items.php?success=added");
                    exit;
                } else {
                    $errors[] = "❌ Failed to add item.";
                }
            } catch (PDOException $e) {
                $errors[] = "❌ Database error: " . $e->getMessage();
                error_log("Add item error: " . $e->getMessage());
            }
        }
    }
    
    // UPDATE ITEM
    elseif ($action === 'update_item' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'lost';
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $user_name = trim($_POST['user_name'] ?? '');
        $user_contact = trim($_POST['user_contact'] ?? '');
        $date_reported = $_POST['date_reported'] ?? '';
        $status = $_POST['status'] ?? 'open';
        
        // Validasi
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($location)) $errors[] = "Location is required.";
        if (empty($user_name)) $errors[] = "Reporter name is required.";
        if (empty($user_contact)) $errors[] = "Contact information is required.";
        if (!in_array($type, ['lost', 'found'])) $type = 'lost';
        if (!in_array($status, ['open', 'matched', 'returned'])) $status = 'open';
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE items SET title=?, description=?, type=?, status=?, user_name=?, user_contact=?, location=?, date_reported=? WHERE id=?");
                $result = $stmt->execute([$title, $description, $type, $status, $user_name, $user_contact, $location, $date_reported, $id]);
                
                if ($result) {
                    $success = "✅ Item updated successfully!";
                    header("Location: items.php?success=updated&id=" . $id);
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "❌ Failed to update item: " . $e->getMessage();
            }
        }
    }
    
    // DELETE ITEM
    elseif ($action === 'delete_item' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $success = "✅ Item deleted successfully!";
                header("Location: items.php?success=deleted");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "❌ Failed to delete item: " . $e->getMessage();
        }
    }
    
    // UPDATE STATUS ONLY
    elseif ($action === 'update_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] ?? 'open';
        if (in_array($status, ['open', 'matched', 'returned'])) {
            try {
                $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
                $result = $stmt->execute([$status, $id]);
                
                if ($result) {
                    $success = "✅ Status updated successfully!";
                    // Tidak redirect agar dropdown tetap berfungsi
                }
            } catch (PDOException $e) {
                $errors[] = "❌ Failed to update status: " . $e->getMessage();
            }
        }
    }
}

// Cek jika ada success message dari redirect
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added': $success = "✅ Item added successfully!"; break;
        case 'updated': 
            $success = "✅ Item updated successfully!";
            if (isset($_GET['id'])) {
                $edit_id = (int)$_GET['id'];
            }
            break;
        case 'deleted': $success = "✅ Item deleted successfully!"; break;
    }
}

// ========== READ / SEARCH ==========
$q = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Jika ada edit_id, ambil data item
$edit_item = null;
if ($edit_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_item = $stmt->fetch();
        
        if (!$edit_item) {
            $errors[] = "❌ Item not found.";
            $edit_id = 0;
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Failed to load item for editing: " . $e->getMessage();
        $edit_id = 0;
    }
}

// Jika ada view_id, ambil data item
$view_item = null;
if ($view_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$view_id]);
        $view_item = $stmt->fetch();
        
        if (!$view_item) {
            $errors[] = "❌ Item not found.";
            $view_id = 0;
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Failed to load item: " . $e->getMessage();
        $view_id = 0;
    }
}

// WHERE untuk pencarian
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
    $where .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ? OR user_name LIKE ? OR user_contact LIKE ?)";
    $like = "%$q%";
    $params[] = $like; 
    $params[] = $like; 
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Hitung statistik
try {
    $total_items = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $lost_items = $pdo->query("SELECT COUNT(*) FROM items WHERE type = 'lost'")->fetchColumn();
    $found_items = $pdo->query("SELECT COUNT(*) FROM items WHERE type = 'found'")->fetchColumn();
    $open_items = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'open'")->fetchColumn();
    $matched_items = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'matched'")->fetchColumn();
    $returned_items = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'returned'")->fetchColumn();
} catch (PDOException $e) {
    $total_items = $lost_items = $found_items = $open_items = $matched_items = $returned_items = 0;
}

// COUNT untuk pagination
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM items $where");
    $countStmt->execute($params);
    $filteredItems = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $filteredItems = 0;
}

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 20;
if ($limit > 100) $limit = 100;
$offset = ($page - 1) * $limit;
$totalPages = max(1, (int)ceil($filteredItems / $limit));

// Ambil data items dengan pagination
try {
    $sql = "SELECT *, DATE_FORMAT(created_at, '%b %d, %Y %H:%i') as formatted_created FROM items $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
    $errors[] = "❌ Failed to load items: " . $e->getMessage();
}

// Prepare content
ob_start();
?>
<!-- Success/Error Messages -->
<?php if (!empty($errors) || !empty($success)): ?>
<div class="row">
    <div class="col-md-12">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h6><i class="icon-ban"></i> Error</h6>
                <?php foreach ($errors as $error): ?>
                    <div class="mb-1"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h6><i class="icon-check"></i> Success</h6>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $total_items ?></h2>
                <p class="mb-0"><small>Total Items</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $lost_items ?></h2>
                <p class="mb-0"><small>Lost Items</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $found_items ?></h2>
                <p class="mb-0"><small>Found Items</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $open_items ?></h2>
                <p class="mb-0"><small>Open</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $matched_items ?></h2>
                <p class="mb-0"><small>Matched</small></p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-3">
        <div class="card stat-card bg-secondary text-white">
            <div class="card-body p-3 text-center">
                <h2 class="mb-0"><?= $returned_items ?></h2>
                <p class="mb-0"><small>Returned</small></p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Card -->
<div class="card">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="icon-bag"></i> Items Management
                <span class="badge badge-primary ml-2"><?= $filteredItems ?> items</span>
            </h5>
            <div>
                <?php if ($q || $type || $status): ?>
                <a href="items.php" class="btn btn-sm btn-light mr-2">
                    <i class="icon-close"></i> Clear Filters
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addItemModal">
                    <i class="icon-plus"></i> Add New Item
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <form method="get" class="row g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="icon-magnifier"></i></span>
                            </div>
                            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" 
                                   placeholder="Search items...">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control" name="type">
                            <option value="">All Types</option>
                            <option value="lost" <?= $type==='lost'?'selected':'' ?>>Lost</option>
                            <option value="found" <?= $type==='found'?'selected':'' ?>>Found</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control" name="status">
                            <option value="">All Status</option>
                            <option value="open" <?= $status==='open'?'selected':'' ?>>Open</option>
                            <option value="matched" <?= $status==='matched'?'selected':'' ?>>Matched</option>
                            <option value="returned" <?= $status==='returned'?'selected':'' ?>>Returned</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-control" name="limit">
                            <option value="10" <?= $limit==10?'selected':'' ?>>10 per page</option>
                            <option value="20" <?= $limit==20?'selected':'' ?>>20 per page</option>
                            <option value="50" <?= $limit==50?'selected':'' ?>>50 per page</option>
                            <option value="100" <?= $limit==100?'selected':'' ?>>100 per page</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="icon-magnifier"></i> Search
                        </button>
                    </div>
                </form>
                
                <?php if ($q || $type || $status): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <strong>Active filters:</strong>
                        <?php if ($q): ?>Search: "<?= htmlspecialchars($q) ?>" <?php endif; ?>
                        <?php if ($type): ?>| Type: <?= $type == 'lost' ? 'Lost' : 'Found' ?> <?php endif; ?>
                        <?php if ($status): ?>| Status: <?= ucfirst($status) ?> <?php endif; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-container-wrapper">
            <div class="table-container">
                <table class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th class="sticky-col first-col">ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th>Reporter</th>
                            <th>Contact</th>
                            <th>Reported Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="sticky-col last-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <div class="mb-3">
                                        <i class="icon-ban" style="font-size: 48px; color: #ddd;"></i>
                                    </div>
                                    <h5>No items found</h5>
                                    <p class="mb-0">Try changing your search criteria</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="sticky-col first-col">
                                    <strong>#<?= $item['id'] ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $item['type'] === 'lost' ? 'danger' : 'success' ?>">
                                        <i class="icon-<?= $item['type'] === 'lost' ? 'close' : 'check' ?>"></i>
                                        <?= ucfirst($item['type']) ?>
                                    </span>
                                </td>
                                <td class="description-cell">
                                    <div class="text-truncate" title="<?= htmlspecialchars($item['description'] ?: 'No description') ?>">
                                        <?= htmlspecialchars($item['description'] ?: 'No description') ?>
                                    </div>
                                </td>
                                <td>
                                    <i class="icon-location-pin text-muted mr-1"></i>
                                    <?= htmlspecialchars($item['location']) ?>
                                </td>
                                <td>
                                    <i class="icon-user text-muted mr-1"></i>
                                    <?= htmlspecialchars($item['user_name']) ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="icon-phone text-muted mr-1"></i>
                                        <?= htmlspecialchars($item['user_contact']) ?>
                                    </small>
                                </td>
                                <td>
                                    <i class="icon-calendar text-muted mr-1"></i>
                                    <?= date('M d, Y', strtotime($item['date_reported'])) ?>
                                </td>
                                <td>
                                    <form method="post" class="status-form" onchange="this.submit()">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <select name="status" class="form-control form-control-sm status-select" 
                                                data-status="<?= $item['status'] ?>">
                                            <option value="open" <?= $item['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                            <option value="matched" <?= $item['status'] === 'matched' ? 'selected' : '' ?>>Matched</option>
                                            <option value="returned" <?= $item['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="icon-clock text-muted mr-1"></i>
                                        <?= $item['formatted_created'] ?>
                                    </small>
                                </td>
                                <td class="sticky-col last-col">
                                    <div class="action-buttons">
                                        <a href="items.php?view=<?= $item['id'] ?>" class="btn btn-info btn-sm action-btn" title="View Details">
                                            <i class="icon-eye"></i>
                                        </a>
                                        <a href="items.php?edit=<?= $item['id'] ?>" class="btn btn-warning btn-sm action-btn" title="Edit Item">
                                            <i class="icon-pencil"></i>
                                        </a>
                                        <form method="post" class="d-inline delete-form">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm action-btn" 
                                                    title="Delete Item"
                                                    onclick="return confirm('Delete item #<?= $item['id'] ?>: <?= htmlspecialchars($item['title']) ?>?')">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Scroll hint -->
        <div class="text-center mt-2 mb-3">
            <small class="text-muted">
                <i class="icon-arrow-left-circle"></i> Scroll horizontally to see more columns <i class="icon-arrow-right-circle"></i>
            </small>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="icon-arrow-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item disabled">
                        <span class="page-link">
                            Page <?= $page ?> of <?= $totalPages ?>
                        </span>
                    </li>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                Next <i class="icon-arrow-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addItemModalLabel"><i class="icon-plus"></i> Add New Item</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" id="addItemForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_item">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required
                                       value="<?= htmlspecialchars($form_data['title'] ?? '') ?>"
                                       placeholder="e.g., Black Wallet, iPhone 12, etc.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" required>
                                    <option value="lost" <?= ($form_data['type'] ?? 'lost') === 'lost' ? 'selected' : '' ?>>Lost Item</option>
                                    <option value="found" <?= ($form_data['type'] ?? '') === 'found' ? 'selected' : '' ?>>Found Item</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Describe the item in detail..."><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="location" required 
                                       value="<?= htmlspecialchars($form_data['location'] ?? '') ?>"
                                       placeholder="e.g., Main Building Lobby, Cafeteria, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reported Date</label>
                                <input type="date" class="form-control" name="date_reported" 
                                       value="<?= $form_data['date_reported'] ?? date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reporter Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_name" required
                                       value="<?= htmlspecialchars($form_data['user_name'] ?? '') ?>"
                                       placeholder="Name of person reporting">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Information <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_contact" required 
                                       value="<?= htmlspecialchars($form_data['user_contact'] ?? '') ?>"
                                       placeholder="Phone number or email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="open" <?= ($form_data['status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="matched" <?= ($form_data['status'] ?? '') === 'matched' ? 'selected' : '' ?>>Matched</option>
                            <option value="returned" <?= ($form_data['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="icon-plus"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<?php if ($edit_item): ?>
<div class="modal fade show" id="editItemModal" tabindex="-1" role="dialog" aria-labelledby="editItemModalLabel" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editItemModalLabel"><i class="icon-pencil"></i> Edit Item #<?= $edit_item['id'] ?></h5>
                <a href="items.php" class="close text-white">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <form method="post" id="editItemForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_item">
                    <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" 
                                       value="<?= htmlspecialchars($edit_item['title']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" required>
                                    <option value="lost" <?= $edit_item['type'] === 'lost' ? 'selected' : '' ?>>Lost Item</option>
                                    <option value="found" <?= $edit_item['type'] === 'found' ? 'selected' : '' ?>>Found Item</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($edit_item['description']) ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="location" 
                                       value="<?= htmlspecialchars($edit_item['location']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reported Date</label>
                                <input type="date" class="form-control" name="date_reported" 
                                       value="<?= $edit_item['date_reported'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reporter Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_name" 
                                       value="<?= htmlspecialchars($edit_item['user_name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Information <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="user_contact" 
                                       value="<?= htmlspecialchars($edit_item['user_contact']) ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="open" <?= $edit_item['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="matched" <?= $edit_item['status'] === 'matched' ? 'selected' : '' ?>>Matched</option>
                            <option value="returned" <?= $edit_item['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="items.php" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="icon-pencil"></i> Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- View Item Modal -->
<?php if ($view_item): ?>
<div class="modal fade show" id="viewItemModal" tabindex="-1" role="dialog" aria-labelledby="viewItemModalLabel" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewItemModalLabel"><i class="icon-eye"></i> Item Details #<?= $view_item['id'] ?></h5>
                <a href="items.php" class="close text-white">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="icon-info text-primary"></i> Basic Information</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="40%">ID:</th>
                                <td>#<?= $view_item['id'] ?></td>
                            </tr>
                            <tr>
                                <th>Title:</th>
                                <td><?= htmlspecialchars($view_item['title']) ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td>
                                    <span class="badge badge-<?= $view_item['type'] === 'lost' ? 'danger' : 'success' ?>">
                                        <?= ucfirst($view_item['type']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge badge-<?= 
                                        $view_item['status'] === 'open' ? 'warning' : 
                                        ($view_item['status'] === 'returned' ? 'success' : 'info')
                                    ?>">
                                        <?= ucfirst($view_item['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td><?= htmlspecialchars($view_item['location']) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="icon-user text-primary"></i> Reporter Information</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="40%">Name:</th>
                                <td><?= htmlspecialchars($view_item['user_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Contact:</th>
                                <td><?= htmlspecialchars($view_item['user_contact']) ?></td>
                            </tr>
                            <tr>
                                <th>Reported Date:</th>
                                <td><?= date('M d, Y', strtotime($view_item['date_reported'])) ?></td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td><?= date('M d, Y H:i', strtotime($view_item['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><i class="icon-notebook text-primary"></i> Description</h6>
                        <div class="border rounded p-3 bg-light">
                            <?= nl2br(htmlspecialchars($view_item['description'] ?: 'No description provided')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="items.php" class="btn btn-light">Close</a>
                <a href="items.php?edit=<?= $view_item['id'] ?>" class="btn btn-warning">
                    <i class="icon-pencil"></i> Edit
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Initialize tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

// Auto-focus search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
    
    // Auto-focus first field in add modal when opened
    $('#addItemModal').on('shown.bs.modal', function () {
        $(this).find('input[name="title"]').focus();
    });
});

// Update status select styling
document.querySelectorAll('.status-select').forEach(select => {
    const status = select.getAttribute('data-status');
    select.className = 'form-control form-control-sm status-select';
    
    switch(status) {
        case 'open':
            select.classList.add('status-open');
            break;
        case 'matched':
            select.classList.add('status-matched');
            break;
        case 'returned':
            select.classList.add('status-returned');
            break;
    }
});

// Auto-hide success messages after 5 seconds
setTimeout(function() {
    $('.alert-success').fadeOut('slow');
}, 5000);

// Auto-focus first field in add modal when opened
$('#addItemModal').on('shown.bs.modal', function () {
    $(this).find('input[name="title"]').focus();
});
</script>

<style>
/* Table Container */
.table-container-wrapper {
    overflow-x: auto;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    background: white;
}

.table-container {
    min-width: 1200px;
    position: relative;
}

/* Sticky Columns */
.sticky-col {
    position: sticky;
    background: white;
    z-index: 10;
}

.first-col {
    left: 0;
    border-right: 2px solid #dee2e6;
    min-width: 80px;
}

.last-col {
    right: 0;
    border-left: 2px solid #dee2e6;
    min-width: 140px;
}

/* Table Styling */
.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table th {
    white-space: nowrap;
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 20;
    border-bottom: 2px solid #dee2e6;
}

.table th.sticky-col {
    z-index: 30;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #eee;
}

/* Description Cell */
.description-cell {
    max-width: 200px;
    min-width: 150px;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

/* Status Select */
.status-select {
    min-width: 100px;
    cursor: pointer;
    transition: all 0.3s;
    padding: 0.25rem 0.5rem;
    height: 30px;
}

.status-open {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.status-matched {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.status-returned {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

/* Statistics Cards */
.stat-card {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

/* Scrollbar Styling */
.table-container-wrapper::-webkit-scrollbar {
    height: 8px;
}

.table-container-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-container-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive */
@media (max-width: 768px) {
    .table-container {
        min-width: 1000px;
    }
    
    .action-btn {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    .first-col {
        min-width: 60px;
    }
    
    .last-col {
        min-width: 120px;
    }
}

/* Badge Colors */
.badge-danger {
    background-color: #dc3545;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-info {
    background-color: #17a2b8;
}

.badge-primary {
    background-color: #007bff;
}

.badge-secondary {
    background-color: #6c757d;
}

/* Modal Improvements */
.modal-header {
    border-radius: 10px 10px 0 0;
    padding: 15px 20px;
}

.modal-body {
    padding: 20px;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* Required field indicator */
.text-danger {
    color: #dc3545 !important;
}

/* Form validation styling */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
}

.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
}

/* Modal show manually */
.modal.show {
    display: block !important;
}
</style>
<?php
$content = ob_get_clean();

// Include base template
require_once __DIR__ . '/base.php';
echo getBaseTemplate('Items Management', $content, 'icon-bag');
?>