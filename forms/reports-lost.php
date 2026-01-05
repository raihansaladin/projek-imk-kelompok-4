<?php
// forms/report-lost.php
require_once __DIR__ . '/../config/database.php';

$success = false;
$errors = [];
$form_data = [];

// Tangani form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dan sanitasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $user_name = trim($_POST['user_name'] ?? '');
    $user_contact = trim($_POST['user_contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date_reported = $_POST['date_reported'] ?? '';
    $type = 'lost'; // Selalu 'lost' untuk form ini
    
    // Validasi
    if (empty($title)) $errors[] = "Judul barang harus diisi";
    if (empty($user_name)) $errors[] = "Nama pelapor harus diisi";
    if (empty($user_contact)) $errors[] = "Kontak harus diisi";
    if (empty($location)) $errors[] = "Lokasi harus diisi";
    if (empty($date_reported)) $errors[] = "Tanggal hilang harus diisi";
    
    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO items (
                    title, description, user_name, user_contact, 
                    location, date_reported, type, status, created_at
                ) VALUES (
                    :title, :description, :user_name, :user_contact,
                    :location, :date_reported, :type, 'open', NOW()
                )
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':user_name' => $user_name,
                ':user_contact' => $user_contact,
                ':location' => $location,
                ':date_reported' => $date_reported,
                ':type' => $type
            ]);
            
            $success = true;
            $form_data = []; // Reset form
            
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    } else {
        // Simpan data form untuk ditampilkan kembali
        $form_data = $_POST;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Barang Hilang - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h1 {
            color: #4361ee;
            margin-bottom: 10px;
        }
        .form-header .badge {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1rem;
        }
        .required::after {
            content: " *";
            color: #ef4444;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
        }
        .back-link {
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .back-link:hover {
            color: #4361ee;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-search-heart"></i> Lost & Found
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navmenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navmenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#items">Barang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="report-lost.php">Lapor Hilang</a></li>
                    <li class="nav-item"><a class="nav-link" href="report-found.php">Lapor Ditemukan</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Form -->
    <div class="form-container">
        <div class="form-header">
            <span class="badge"><i class="bi bi-exclamation-triangle"></i> LAPOR BARANG HILANG</span>
            <h1>Laporkan Barang Hilang Anda</h1>
            <p class="text-muted">Isi form berikut dengan detail barang yang hilang. Data Anda akan aman dan hanya digunakan untuk proses pencarian.</p>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <strong>Berhasil!</strong> Laporan barang hilang telah disimpan. Kami akan membantu mencari barang Anda.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-3">
                    <a href="../index.php" class="btn btn-success btn-sm">
                        <i class="bi bi-house"></i> Kembali ke Home
                    </a>
                    <a href="report-lost.php" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Lapor Lagi
                    </a>
                </div>
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

        <!-- Form -->
        <form method="POST" action="" id="lostItemForm">
            <div class="row g-3">
                <!-- Kolom Kiri -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label required">Judul Barang</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>"
                               placeholder="Contoh: Dompet Kulit Hitam" required>
                        <div class="form-text">Berikan judul yang deskriptif dan mudah dikenali</div>
                    </div>

                    <div class="mb-3">
                        <label for="user_name" class="form-label required">Nama Pelapor</label>
                        <input type="text" class="form-control" id="user_name" name="user_name"
                               value="<?php echo htmlspecialchars($form_data['user_name'] ?? ''); ?>"
                               placeholder="Nama lengkap Anda" required>
                    </div>

                    <div class="mb-3">
                        <label for="user_contact" class="form-label required">Kontak (Email/Telepon)</label>
                        <input type="text" class="form-control" id="user_contact" name="user_contact"
                               value="<?php echo htmlspecialchars($form_data['user_contact'] ?? ''); ?>"
                               placeholder="081234567890 atau email@domain.com" required>
                        <div class="form-text">Akan digunakan untuk menghubungi jika barang ditemukan</div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label required">Lokasi Kehilangan</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>"
                               placeholder="Contoh: Perpustakaan Utama, Lantai 2" required>
                        <div class="form-text">Sebutkan lokasi spesifik dimana barang hilang</div>
                    </div>

                    <div class="mb-3">
                        <label for="date_reported" class="form-label required">Tanggal Hilang</label>
                        <input type="date" class="form-control" id="date_reported" name="date_reported"
                               value="<?php echo htmlspecialchars($form_data['date_reported'] ?? ''); ?>"
                               max="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori Barang (Opsional)</label>
                        <select class="form-select" name="category">
                            <option value="">Pilih Kategori</option>
                            <option value="elektronik" <?php echo (isset($form_data['category']) && $form_data['category'] == 'elektronik') ? 'selected' : ''; ?>>Elektronik</option>
                            <option value="dompet" <?php echo (isset($form_data['category']) && $form_data['category'] == 'dompet') ? 'selected' : ''; ?>>Dompet & Uang</option>
                            <option value="kunci" <?php echo (isset($form_data['category']) && $form_data['category'] == 'kunci') ? 'selected' : ''; ?>>Kunci</option>
                            <option value="tas" <?php echo (isset($form_data['category']) && $form_data['category'] == 'tas') ? 'selected' : ''; ?>>Tas & Ransel</option>
                            <option value="buku" <?php echo (isset($form_data['category']) && $form_data['category'] == 'buku') ? 'selected' : ''; ?>>Buku & Catatan</option>
                            <option value="aksesoris" <?php echo (isset($form_data['category']) && $form_data['category'] == 'aksesoris') ? 'selected' : ''; ?>>Aksesoris</option>
                            <option value="lainnya" <?php echo (isset($form_data['category']) && $form_data['category'] == 'lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label">Deskripsi Detail</label>
                <textarea class="form-control" id="description" name="description" rows="4" 
                          placeholder="Deskripsikan barang secara detail: warna, merek, ukuran, ciri khas, isi barang, dll."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                <div class="form-text">Semakin detail deskripsi, semakin besar kemungkinan ditemukan</div>
            </div>

            <!-- Informasi Penting -->
            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle"></i> Informasi Penting:</h6>
                <ul class="mb-0">
                    <li>Data yang Anda isi akan ditampilkan publik untuk membantu pencarian</li>
                    <li>Pastikan kontak yang Anda berikan aktif dan dapat dihubungi</li>
                    <li>Setelah barang ditemukan, sistem akan menghubungi Anda</li>
                    <li>Anda juga dapat melakukan <a href="claim-item.php">klaim barang</a> jika menemukan barang milik orang lain</li>
                </ul>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="../index.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Kembali ke Home
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="bi bi-send"></i> Kirim Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="footer-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="copyright">© 2026 Lost & Found | IMK Kelompok 4</p>
                    </div>
                    <div class="col-md-6">
                        <p class="made-by">Dibuat dengan <i class="bi bi-heart-fill"></i> untuk masyarakat kampus</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi form client-side
        document.getElementById('lostItemForm').addEventListener('submit', function(e) {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('date_reported');
            
            if (dateInput.value > today) {
                e.preventDefault();
                alert('Tanggal hilang tidak boleh lebih dari hari ini');
                dateInput.focus();
            }
        });

        // Set max date untuk date picker
        document.getElementById('date_reported').max = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>