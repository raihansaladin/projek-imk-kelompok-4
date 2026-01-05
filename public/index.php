<?php
declare(strict_types=1);
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/repo.php';

$q = [
  'type' => $_GET['type'] ?? '',
  'status' => $_GET['status'] ?? '',
  'category' => trim($_GET['category'] ?? ''),
  'location' => trim($_GET['location'] ?? ''),
  'keyword' => trim($_GET['keyword'] ?? ''),
];
$items = list_items($pdo, $q);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Lost & Found Kampus</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <h2>Lost & Found Kampus</h2>
    <div>
      <a class="btn" href="report_lost.php">Lapor Hilang</a>
      <a class="btn" href="report_found.php">Lapor Ditemukan</a>
    </div>
  </div>

  <div class="card">
    <form method="get" class="grid">
      <select name="type">
        <option value="">Semua</option>
        <option value="lost" <?= $q['type']==='lost'?'selected':'' ?>>Hilang</option>
        <option value="found" <?= $q['type']==='found'?'selected':'' ?>>Ditemukan</option>
      </select>

      <select name="status">
        <option value="">Status (Semua)</option>
        <option value="open" <?= $q['status']==='open'?'selected':'' ?>>Open</option>
        <option value="matched" <?= $q['status']==='matched'?'selected':'' ?>>Matched</option>
        <option value="returned" <?= $q['status']==='returned'?'selected':'' ?>>Returned</option>
      </select>

      <input name="category" placeholder="Kategori (contoh: kartu, hp)" value="<?= e($q['category']) ?>">
      <input name="location" placeholder="Lokasi (contoh: gedung A, perpustakaan)" value="<?= e($q['location']) ?>">
      <input name="keyword" placeholder="Kata kunci (judul/deskripsi)" value="<?= e($q['keyword']) ?>">
      <button class="btn" type="submit">Cari</button>
    </form>
    <div class="small">Tip: isi 1–2 filter saja biar cepat.</div>
  </div>

  <?php if (!$items): ?>
    <div class="card">Belum ada data.</div>
  <?php endif; ?>

  <?php foreach ($items as $it): ?>
    <div class="card">
      <div style="display:flex;gap:12px;align-items:flex-start">
        <?php if (!empty($it['photo_path'])): ?>
          <img src="<?= e($it['photo_path']) ?>" style="width:92px;height:92px;object-fit:cover;border-radius:12px;border:1px solid #eee">
        <?php else: ?>
          <div style="width:92px;height:92px;border-radius:12px;border:1px dashed #ddd;display:flex;align-items:center;justify-content:center" class="small">No photo</div>
        <?php endif; ?>

        <div style="flex:1">
          <div>
            <span class="badge"><?= e(strtoupper($it['type'])) ?></span>
            <span class="badge"><?= e($it['status']) ?></span>
            <span class="small">• <?= e($it['event_date']) ?> • <?= e($it['location']) ?></span>
          </div>
          <h3 style="margin:8px 0"><a href="item.php?id=<?= (int)$it['id'] ?>"><?= e($it['title']) ?></a></h3>
          <div class="small"><?= e($it['category']) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="small">Admin: <a href="../admin/index.php">panel verifikasi klaim</a></div>
</div>
</body>
</html>
