<?php
// debug.php - tempatkan di root folder
echo "<h2>Debug Database Connection</h2>";

// 1. Cek apakah config.php ada
echo "1. Cek config.php: ";
if (file_exists('app/config.php')) {
    echo "✅ ADA<br>";
    $config = require 'app/config.php';
    echo "Database name: " . $config['db']['name'] . "<br>";
    echo "Database host: " . $config['db']['host'] . "<br>";
    echo "Database user: " . $config['db']['user'] . "<br>";
} else {
    echo "❌ TIDAK ADA<br>";
}

// 2. Coba koneksi langsung
echo "<br>2. Coba koneksi langsung:<br>";
try {
    $host = 'localhost';
    $dbname = 'lostfound'; // Nama database yang benar
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Koneksi berhasil ke database: $dbname<br>";
    
    // 3. Cek tabel
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<br>3. Tabel yang ada:<br>";
    if (empty($tables)) {
        echo "❌ Tidak ada tabel<br>";
    } else {
        foreach ($tables as $table) {
            echo "✅ $table<br>";
        }
    }
    
    // 4. Cek data di items
    if (in_array('items', $tables)) {
        echo "<br>4. Data di tabel items:<br>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
        $count = $stmt->fetch()['count'];
        echo "Jumlah data: $count<br>";
        
        $stmt = $pdo->query("SELECT * FROM items LIMIT 3");
        $items = $stmt->fetchAll();
        foreach ($items as $item) {
            echo "ID: {$item['id']}, Title: {$item['title']}, Type: {$item['type']}<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    
    // Coba buat database jika tidak ada
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<br>Mencoba membuat database...<br>";
        try {
            $pdo_temp = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo_temp->exec("USE `$dbname`");
            echo "✅ Database '$dbname' berhasil dibuat<br>";
        } catch (PDOException $e2) {
            echo "❌ Gagal membuat database: " . $e2->getMessage() . "<br>";
        }
    }
}
?>