<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'pbwuser');
define('DB_PASS', 'passwordku');
define('DB_NAME', 'petcare_pbw');

// Fungsi koneksi database
function connectDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Inisialisasi database
function initializeDatabase() {
    try {
        // Koneksi tanpa database terlebih dahulu
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Baca dan eksekusi schema
        $schema = file_get_contents(__DIR__ . '/../database_schema.sql');
        $pdo->exec($schema);
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Cek dan inisialisasi database jika belum ada
$test_conn = @new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
if (!$test_conn) {
    initializeDatabase();
}
?>
