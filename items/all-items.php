<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = mysqli_connect("localhost", "root", "", "lostfound");
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$query = "SELECT * FROM items ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Lost & Found</title>

<!-- BOOTSTRAP ICON -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
    --primary-blue: #3b82f6;
    --soft-blue: #eaf1ff;
    --dark-blue: #1e3a8a;
}

/* GLOBAL (TETAP) */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: var(--soft-blue);
}

/* ===== HEADER LOST & FOUND (TAMBAHAN SAJA) ===== */
.lf-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #1f2937;
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 24px;
}

.lf-header .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-weight: 600;
    font-size: 18px;
}

.lf-header .brand i {
    color: #38bdf8;
    font-size: 20px;
}

/* ================= ITEMS ================= */
.section-items {
    padding: 60px 20px;
}

.section-items h2 {
    text-align: center;
    color: var(--dark-blue);
    margin-bottom: 40px;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 22px;
    max-width: 1200px;
    margin: auto;
}

.card {
    background: #fff;
    padding: 18px;
    border-radius: 16px;
    border: 1px solid #e5edff;
    box-shadow: 0 8px 20px rgba(59,130,246,.08);
    transition: .3s;
}

.card::before {
    content: "";
    display: block;
    height: 4px;
    background: var(--primary-blue);
    border-radius: 16px 16px 0 0;
    margin: -18px -18px 12px -18px;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 34px rgba(59,130,246,.18);
}

.card h3 {
    margin: 0 0 10px;
    color: #111827;
}

.card p {
    color: #374151;
    line-height: 1.6;
}

.badge {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}

.lost { background: #ef4444; }
.found { background: var(--primary-blue); }

small {
    color: #6b7280;
    display: block;
    margin-top: 10px;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="lf-header">
    <div class="brand">
        <i class="bi bi-search"></i>
        <span>Lost & Found</span>
    </div>
</div>

<!-- ITEMS -->
<section id="items" class="section-items">
<h2>📦 Semua Barang Dilaporkan</h2>

<div class="items-grid">
<?php while ($row = mysqli_fetch_assoc($result)) : ?>
    <div class="card">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <p>
            📍 <?= htmlspecialchars($row['location']) ?><br>
            📅 <?= date('d M Y', strtotime($row['date_reported'])) ?>
        </p>
        <span class="badge <?= htmlspecialchars($row['type']) ?>">
            <?= strtoupper($row['type']) ?>
        </span>
        <small>
            👤 <?= htmlspecialchars($row['user_name']) ?><br>
            ☎ <?= htmlspecialchars($row['user_contact']) ?>
        </small>
    </div>
<?php endwhile; ?>
</div>
</section>

</body>
</html>
