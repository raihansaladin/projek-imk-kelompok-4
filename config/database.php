<?php
// config/database.php
require_once __DIR__ . '/config.php'; // Menggunakan config.php yang ada di folder yang sama

$host = $config['db']['host'] ?? 'localhost';
$dbname = $config['db']['name'] ?? 'lostfound';
$username = $config['db']['user'] ?? 'root';
$password = $config['db']['pass'] ?? '';
$charset = $config['db']['charset'] ?? 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>