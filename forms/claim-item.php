<?php
// forms/claim-item.php
require_once __DIR__ . '/../config/database.php';

$items = [];
$success = false;
$errors = [];

// Ambil semua barang yang ditemukan (type = 'found' dan status = 'open')
try {
    $stmt = $pdo->query("
    SELECT id, title, description, location, date_reported, created_at 
    FROM items 
    WHERE status = 'open'
      AND type IN ('found', 'lost')
    ORDER BY created_at DESC
");

    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Gagal mengambil data barang: " . $e->getMessage();
}

// Tangani form klaim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $claimer_name = trim($_POST['claimer_name'] ?? '');
    $claimer_contact = trim($_POST['claimer_contact'] ?? '');
    $proof_text = trim($_POST['proof_text'] ?? '');
    
    // Validasi
    if ($item_id <= 0) $errors[] = "Pilih barang yang ingin diklaim";
    if (empty($claimer_name)) $errors[] = "Nama pengklaim harus diisi";
    if (empty($claimer_contact)) $errors[] = "Kontak pengklaim harus diisi";
    if (empty($proof_text)) $errors[] = "Bukti kepemilikan harus diisi";
    
    // Jika tidak ada error, simpan klaim ke tabel claims
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO claims (
                    item_id, claimer_name, claimer_contact, proof_text, status, created_at
                ) VALUES (
                    :item_id, :claimer_name, :claimer_contact, :proof_text, 'pending', NOW()
                )
            ");
            
            $stmt->execute([
                ':item_id' => $item_id,
                ':claimer_name' => $claimer_name,
                ':claimer_contact' => $claimer_contact,
                ':proof_text' => $proof_text
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Gagal mengirim klaim: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klaim Barang - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .container-custom {
            max-width: 1000px;
            margin: 80px auto 40px;
        }
        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section-header .badge {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
        }
        .card-item {
            transition: transform 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .card-item:hover {
            transform: translateY(-5px);
            border-color: #4361ee;
        }
        .card-item.selected {
            border-color: #4361ee;
            background-color: rgba(67, 97, 238, 0.05);
        }
        .claim-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <!-- Navbar sama seperti di reports-lost.php -->

    <div class="container container-custom">
        <div class="section-header">
            <span class="badge"><i class="bi bi-hand-thumbs-up"></i> KLAIM BARANG</span>
            <h1>Klaim Barang yang Ditemukan</h1>
            <p class="text-muted">Pilih barang yang ingin diklaim dan isi form verifikasi</p>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <strong>Klaim berhasil dikirim!</strong> Admin akan menghubungi Anda untuk proses verifikasi.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5><i class="bi bi-exclamation-triangle"></i> Terdapat kesalahan:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Daftar Barang -->
            <div class="col-lg-6">
                <h4 class="mb-3"><i class="bi bi-list-check"></i> Barang yang Ditemukan</h4>
                <?php if (empty($items)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Tidak ada barang yang bisa diklaim saat ini.
                    </div>
                <?php else: ?>
                    <div class="row g-3" id="itemsList">
                        <?php foreach ($items as $item): ?>
                            <div class="col-12">
                                <div class="card card-item" data-item-id="<?php echo $item['id']; ?>">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <p class="card-text small text-muted mb-1">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($item['location']); ?>
                                        </p>
                                        <p class="card-text small">
                                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($item['date_reported'])); ?>
                                            </span>
                                            <button class="btn btn-sm btn-outline-primary select-item" 
                                                    data-item-id="<?php echo $item['id']; ?>"
                                                    data-item-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                Pilih
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form Klaim -->
            <div class="col-lg-6">
                <div class="claim-form">
                    <h4 class="mb-3"><i class="bi bi-clipboard-check"></i> Form Klaim</h4>
                    <form method="POST" action="">
                        <input type="hidden" id="item_id" name="item_id" value="">
                        
                        <div class="mb-3">
                            <label for="selected_item" class="form-label">Barang yang Diklaim</label>
                            <input type="text" class="form-control" id="selected_item" 
                                   placeholder="Pilih barang dari daftar di samping" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="claimer_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="claimer_name" name="claimer_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="claimer_contact" class="form-label">Kontak (Telepon/Email)</label>
                            <input type="text" class="form-control" id="claimer_contact" name="claimer_contact" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="proof_text" class="form-label">Bukti Kepemilikan</label>
                            <textarea class="form-control" id="proof_text" name="proof_text" rows="4" required 
                                      placeholder="Deskripsikan ciri-ciri barang, lokasi kehilangan, atau bukti lain yang menunjukkan barang tersebut milik Anda"></textarea>
                            <div class="form-text">Semakin detail bukti yang diberikan, semakin cepat proses verifikasi</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-shield-check"></i> <strong>Verifikasi akan dilakukan oleh admin</strong>
                            <p class="mb-0 small">Klaim Anda akan diperiksa oleh admin sebelum barang diserahkan.</p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Kirim Klaim
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pilih item untuk diklaim
        document.querySelectorAll('.select-item').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const itemId = this.getAttribute('data-item-id');
                const itemTitle = this.getAttribute('data-item-title');
                
                // Update form
                document.getElementById('item_id').value = itemId;
                document.getElementById('selected_item').value = itemTitle;
                
                // Update UI
                document.querySelectorAll('.card-item').forEach(card => {
                    card.classList.remove('selected');
                });
                this.closest('.card-item').classList.add('selected');
                
                // Scroll ke form
                document.getElementById('selected_item').focus();
            });
        });
    </script>
</body>
</html>