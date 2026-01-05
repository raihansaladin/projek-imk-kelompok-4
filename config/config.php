<?php
// app/config.php
declare(strict_types=1);

return [
  'db' => [
    'host' => 'localhost',
    'name' => 'lostfound',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],
  'upload_dir' => __DIR__ . '/../public/uploads',
  'base_url' => '/lostfound/public', // sesuaikan jika folder berbeda
];
