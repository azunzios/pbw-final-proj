<?php
// php/db_connection.php

$servername = "localhost"; // Biasanya "localhost"
$username = "root"; // Username database Anda
$password = "admin"; // Password database Anda
$dbname = "db_manajer_peliharaan";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}
?>