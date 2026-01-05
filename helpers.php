<?php
// app/helpers.php
declare(strict_types=1);

function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function upload_photo(array $file, string $uploadDir): ?string {
  if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  $mime = mime_content_type($file['tmp_name']);
  if (!isset($allowed[$mime])) return null;

  if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB

  ensure_dir($uploadDir);
  $ext = $allowed[$mime];
  $name = bin2hex(random_bytes(12)) . "." . $ext;
  $dest = rtrim($uploadDir, '/') . '/' . $name;

  if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

  // path relatif untuk ditampilkan
  return "uploads/" . $name;
}
