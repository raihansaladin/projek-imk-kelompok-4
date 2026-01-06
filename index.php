<?php
// index.php - Halaman utama Lost & Found
require_once __DIR__ . '/config/database.php';

// Inisialisasi variabel
$lost_count = 0;
$found_count = 0;
$returned_count = 0;
$users_count = 0;
$recent_items = [];
$error = false;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<!-- Debug: Database connected successfully -->\n";
    
    // Debug: Cek total items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM items");
    $debug_total = $stmt->fetch()['total'];
    echo "<!-- Debug: Total items in database: $debug_total -->\n";
    
    // Debug: Cek tipe items
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM items GROUP BY type");
    $type_counts = $stmt->fetchAll();
    foreach ($type_counts as $row) {
        echo "<!-- Debug: Type {$row['type']}: {$row['count']} -->\n";
    }
    
    // 1. Hitung barang hilang (type = 'lost')
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE type = 'lost'");
    $stmt->execute();
    $result = $stmt->fetch();
    $lost_count = (int)$result['count'];
    echo "<!-- Debug: Lost items count: $lost_count -->\n";
    
    // 2. Hitung barang ditemukan (type = 'found')
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE type = 'found'");
    $stmt->execute();
    $result = $stmt->fetch();
    $found_count = (int)$result['count'];
    echo "<!-- Debug: Found items count: $found_count -->\n";
    
    // 3. Hitung barang berhasil dikembalikan
    // Cek kolom apa yang ada di tabel
    $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'status'");
    $has_status = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'is_claimed'");
    $has_is_claimed = $stmt->rowCount() > 0;
    
    echo "<!-- Debug: Has status column: " . ($has_status ? 'Yes' : 'No') . " -->\n";
    echo "<!-- Debug: Has is_claimed column: " . ($has_is_claimed ? 'Yes' : 'No') . " -->\n";
    
    if ($has_status && $has_is_claimed) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE status = 'returned' OR is_claimed = 1");
    } elseif ($has_status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE status = 'returned'");
    } elseif ($has_is_claimed) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE is_claimed = 1");
    } else {
        // Jika tidak ada kolom status/is_claimed, anggap semua belum dikembalikan
        $returned_count = 0;
    }
    
    if (isset($stmt)) {
        $stmt->execute();
        $result = $stmt->fetch();
        $returned_count = (int)$result['count'];
    }
    echo "<!-- Debug: Returned items count: $returned_count -->\n";
    
    // 4. Hitung pengguna unik
    // Cek jika ada tabel users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        $users_count = (int)$result['count'];
    } else {
        // Hitung dari tabel items
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_name) as count FROM items WHERE user_name IS NOT NULL AND user_name != ''");
        $result = $stmt->fetch();
        $users_count = (int)$result['count'];
    }
    echo "<!-- Debug: Users count: $users_count -->\n";
    
    // 5. Ambil 3 barang terbaru untuk ditampilkan
    $stmt = $pdo->prepare("
        SELECT i.*, 
               DATE_FORMAT(i.created_at, '%d %b %Y') as formatted_date
        FROM items i 
        ORDER BY i.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $recent_items = $stmt->fetchAll();
    echo "<!-- Debug: Recent items fetched: " . count($recent_items) . " -->\n";
    
} catch (PDOException $e) {
    $error = true;
    echo "<!-- Debug: Database Error: " . htmlspecialchars($e->getMessage()) . " -->\n";
    // Fallback values
    $lost_count = 2;
    $found_count = 1;
    $returned_count = 3;
    $users_count = 1500;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lost & Found - Temukan Barang Hilang Anda</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/main.css">
  
  <style>
    .stat-number {
      font-variant-numeric: tabular-nums;
    }
    .claimed-badge {
      background: #dcfce7;
      color: #166534;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">
      <i class="bi bi-search-heart"></i> Lost & Found
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="#hero">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Fitur</a></li>
        <li class="nav-item"><a class="nav-link" href="#stats">Statistik</a></li>
        <li class="nav-item"><a class="nav-link" href="#items">Barang</a></li>
        <li class="nav-item"><a class="nav-link" href="#process">Cara Kerja</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
         <!-- Jika sudah login -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="dashboard/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a></li>
            </ul>
        </li>
        <?php else: ?>
            <!-- Jika belum login -->
            <li class="nav-item">
                <a class="nav-link" href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i> Login Admin
                </a>
            </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="btn btn-report" href="forms/reports-lost.php">
            <i class="bi bi-plus-circle"></i> Lapor Barang
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="hero" class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8 mx-auto text-center">
        <h1 class="display-4 fw-bold mb-4">Temukan Barang Hilang dengan Mudah</h1>
        <p class="lead mb-5">Platform terpercaya untuk melaporkan dan menemukan barang hilang di lingkungan kampus. Bergabung dengan ribuan pengguna yang telah berhasil menemukan barang mereka.</p>
        
          <a href="#items" class="btn btn-hero-secondary btn-lg">
            <i class="bi bi-search"></i> Cari Barang
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-wave">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"></path>
    </svg>
  </div>
</section>

<!-- FEATURES -->
<section id="features" class="features-section py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-badge">Kenapa Memilih Kami?</span>
      <h2 class="section-title">Fitur Unggulan</h2>
      <p class="section-subtitle">Semua yang Anda butuhkan dalam satu platform</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card" data-bs-toggle="modal" data-bs-target="#fiturLapor">
          <div class="feature-icon">
            <i class="bi bi-pencil-square"></i>
          </div>
          <h5 class="feature-title">Lapor Barang</h5>
          <p class="feature-text">Laporkan barang hilang atau ditemukan dengan mudah dan cepat melalui form online kami.</p>
          <div class="feature-hover">
            <span>Klik untuk detail</span>
            <i class="bi bi-arrow-right"></i>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card" data-bs-toggle="modal" data-bs-target="#fiturCari">
          <div class="feature-icon">
            <i class="bi bi-search"></i>
          </div>
          <h5 class="feature-title">Cari Barang</h5>
          <p class="feature-text">Cari barang berdasarkan kategori, lokasi, dan tanggal dengan sistem pencarian canggih.</p>
          <div class="feature-hover">
            <span>Klik untuk detail</span>
            <i class="bi bi-arrow-right"></i>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card" data-bs-toggle="modal" data-bs-target="#fiturHubungi">
          <div class="feature-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <h5 class="feature-title">Aman & Terpercaya</h5>
          <p class="feature-text">Sistem verifikasi dan komunikasi terenkripsi untuk keamanan data pribadi Anda.</p>
          <div class="feature-hover">
            <span>Klik untuk detail</span>
            <i class="bi bi-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS SECTION -->
<section id="stats" class="stats-section py-5">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-briefcase"></i>
          </div>
          <h3 class="stat-number" data-count="<?php echo $lost_count; ?>">0</h3>
          <p class="stat-label">Barang Hilang</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-check-circle"></i>
          </div>
          <h3 class="stat-number" data-count="<?php echo $found_count; ?>">0</h3>
          <p class="stat-label">Barang Ditemukan</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-heart"></i>
          </div>
          <h3 class="stat-number" data-count="<?php echo $returned_count; ?>">0</h3>
          <p class="stat-label">Berhasil Dikembalikan</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="stat-number" data-count="1274">0</h3>
          <p class="stat-label">Pengguna Aktif</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROCESS -->
<section id="process" class="process-section py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title">Bagaimana Cara Kerjanya?</h2>
      <p class="section-subtitle">Hanya dalam 3 langkah mudah</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="process-card">
          <div class="process-number">01</div>
          <div class="process-icon">
            <i class="bi bi-pencil"></i>
          </div>
          <h5>Laporkan</h5>
          <p>Isi form laporan dengan detail barang yang hilang atau ditemukan.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="process-card">
          <div class="process-number">02</div>
          <div class="process-icon">
            <i class="bi bi-search"></i>
          </div>
          <h5>Cari & Cocokkan</h5>
          <p>Sistem akan mencocokkan laporan Anda dengan database kami.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="process-card">
          <div class="process-number">03</div>
          <div class="process-icon">
            <i class="bi bi-hand-thumbs-up"></i>
          </div>
          <h5>Hubungi & Ambil</h5>
          <p>Terhubung dengan pemilik/penemu melalui sistem komunikasi aman.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ITEMS -->
<section id="items" class="items-section py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title">Barang Terbaru</h2>
      <p class="section-subtitle">Lihat barang-barang yang baru dilaporkan</p>
    </div>

    <div class="row g-4">
      <?php if (!empty($recent_items)): ?>
        <?php foreach ($recent_items as $item): ?>
          <div class="col-lg-4 col-md-6">
            <div class="item-card">
              <div class="item-badge <?php echo $item['type']; ?>">
                <?php echo $item['type'] == 'lost' ? 'Hilang' : 'Ditemukan'; ?>
              </div>
              <div class="item-content">
                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                <p class="item-location">
                  <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($item['location']); ?>
                </p>
                <p class="item-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                <div class="item-meta">
                  <span><i class="bi bi-calendar"></i> <?php echo !empty($item['formatted_date']) ? $item['formatted_date'] : date('d M Y', strtotime($item['created_at'])); ?></span>
                  <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($item['user_name']); ?></span>
                </div>
                <?php if ($item['is_claimed']): ?>
                  <div class="claimed-badge">
                    <i class="bi bi-check-circle"></i> Telah Diklaim
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
      <!-- Fallback jika tidak ada data -->
      <div class="col-lg-4 col-md-6">
        <div class="item-card">
          <div class="item-badge lost">Hilang</div>
          <div class="item-content">
            <h5>Dompet Kulit Hitam</h5>
            <p class="item-location"><i class="bi bi-geo-alt"></i> Perpustakaan Utama</p>
            <p class="item-desc">Dompet kulit pria berisi KTM, SIM, dan beberapa kartu bank. Hilang tanggal 4 Januari 2026.</p>
            <div class="item-meta">
              <span><i class="bi bi-calendar"></i> 2 hari lalu</span>
              <span><i class="bi bi-eye"></i> 45 views</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 col-md-6">
        <div class="item-card">
          <div class="item-badge found">Ditemukan</div>
          <div class="item-content">
            <h5>HP Samsung Galaxy S23</h5>
            <p class="item-location"><i class="bi bi-geo-alt"></i> Kantin Timur</p>
            <p class="item-desc">HP warna hijau dengan casing transparan. Ditemukan di atas meja kantin.</p>
            <div class="item-meta">
              <span><i class="bi bi-calendar"></i> 1 hari lalu</span>
              <span><i class="bi bi-eye"></i> 32 views</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 col-md-6">
        <div class="item-card">
          <div class="item-badge lost">Hilang</div>
          <div class="item-content">
            <h5>Kunci Motor Honda</h5>
            <p class="item-location"><i class="bi bi-geo-alt"></i> Area Parkir FIK</p>
            <p class="item-desc">Gantungan kunci motor Honda dengan gantungan karakter anime.</p>
            <div class="item-meta">
              <span><i class="bi bi-calendar"></i> 3 jam lalu</span>
              <span><i class="bi bi-eye"></i> 18 views</span>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="text-center mt-5">
      <a href="items/all-items.php" class="btn btn-view-all">
        <i class="bi bi-list"></i> Lihat Semua Barang
      </a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-top">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <div class="footer-info">
            <a href="#" class="footer-logo">
              <i class="bi bi-search-heart"></i> Lost & Found
            </a>
            <p class="footer-about">
              Platform lost and found terpercaya untuk membantu menemukan barang hilang di lingkungan kampus.
            </p>
            <div class="social-links">
              <a href="#"><i class="bi bi-facebook"></i></a>
              <a href="#"><i class="bi bi-twitter"></i></a>
              <a href="#"><i class="bi bi-instagram"></i></a>
              <a href="#"><i class="bi bi-whatsapp"></i></a>
            </div>
          </div>
        </div>

        <div class="col-lg-2 col-md-6">
          <div class="footer-links">
            <h5>Menu</h5>
            <ul>
              <li><a href="#hero">Home</a></li>
              <li><a href="#features">Fitur</a></li>
              <li><a href="#items">Barang</a></li>
              <li><a href="#process">Cara Kerja</a></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="footer-links">
            <h5>Layanan</h5>
            <ul>
              <li><a href="forms/reports-lost.php">Lapor Barang Hilang</a></li>
              <li><a href="forms/report-found.html">Lapor Barang Ditemukan</a></li>
              <li><a href="items/all-items.php">Cari Barang</a></li>
              <li><a href="contact.php">Klaim Barang</a></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="footer-contact">
            <h5>Kontak</h5>
            <p><i class="bi bi-geo-alt"></i> Gedung Rektorat, Kampus Universitas</p>
            <p><i class="bi bi-envelope"></i> help@lostfound.ac.id</p>
            <p><i class="bi bi-phone"></i> (021) 1234-5678</p>
          </div>
        </div>
      </div>
    </div>
  </div>

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

<!-- MODALS FITUR -->
<div class="modal fade" id="fiturLapor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Lapor Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Form laporan yang sederhana namun lengkap memungkinkan Anda melaporkan barang hilang atau ditemukan dalam hitungan menit. Lengkapi dengan foto untuk hasil yang lebih akurat.</p>
        <ul class="feature-list">
          <li><i class="bi bi-check-circle"></i> Form yang mudah diisi</li>
          <li><i class="bi bi-check-circle"></i> Upload foto barang</li>
          <li><i class="bi bi-check-circle"></i> Kategori terstruktur</li>
          <li><i class="bi bi-check-circle"></i> Peta lokasi interaktif</li>
        </ul>
      </div>
      <div class="modal-footer">
        <a href="forms/reports-lost.php" class="btn btn-primary">Lapor Sekarang</a>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fiturCari" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-search"></i> Cari Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Sistem pencarian canggih dengan filter yang lengkap membantu Anda menemukan barang dengan cepat dan tepat.</p>
        <ul class="feature-list">
          <li><i class="bi bi-check-circle"></i> Filter berdasarkan kategori</li>
          <li><i class="bi bi-check-circle"></i> Pencarian berdasarkan lokasi</li>
          <li><i class="bi bi-check-circle"></i> Rentang tanggal kejadian</li>
          <li><i class="bi bi-check-circle"></i> Pencarian kata kunci</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fiturHubungi" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-shield-check"></i> Aman & Terpercaya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Keamanan data Anda adalah prioritas kami. Sistem komunikasi terenkripsi dan verifikasi multi-level memastikan transaksi yang aman.</p>
        <ul class="feature-list">
          <li><i class="bi bi-check-circle"></i> Enkripsi data end-to-end</li>
          <li><i class="bi bi-check-circle"></i> Verifikasi identitas</li>
          <li><i class="bi bi-check-circle"></i> Sistem rating pengguna</li>
          <li><i class="bi bi-check-circle"></i> Dukungan admin 24/7</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/script.js"></script>

<!-- Counter Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to animate counters
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 100; // 100 steps for smooth animation
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString('id-ID');
        }, 20);
    }

    // Observer for stats section
    const statsSection = document.getElementById('stats');
    const statNumbers = document.querySelectorAll('.stat-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                statNumbers.forEach(statNumber => {
                    const target = parseInt(statNumber.getAttribute('data-count'));
                    statNumber.textContent = '0';
                    animateCounter(statNumber, target);
                });
                observer.unobserve(statsSection);
            }
        });
    }, { threshold: 0.3 });

    if (statsSection) {
        observer.observe(statsSection);
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.paddingTop = '0.5rem';
            navbar.style.paddingBottom = '0.5rem';
            navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.paddingTop = '0.75rem';
            navbar.style.paddingBottom = '0.75rem';
            navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
        }
    });
});
</script>

</body>
</html>