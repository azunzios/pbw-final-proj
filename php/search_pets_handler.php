<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['program_id']) || !isset($_SESSION['user_id'])) {
    exit('Akses ditolak');
}

$program_id = (int)$_GET['program_id'];
$query = $_GET['query'] ?? '';

// Ambil data pet berdasarkan query pencarian
$sql = "SELECT id, name, photo_path FROM pets WHERE program_id = ? AND name LIKE ?";
$stmt = $conn->prepare($sql);
$searchQuery = "%" . $query . "%";
$stmt->bind_param("is", $program_id, $searchQuery);
$stmt->execute();
$result = $stmt->get_result();
$pets = $result->fetch_all(MYSQLI_ASSOC);

if (empty($pets)) {
    echo "<p>Tidak ada peliharaan yang cocok.</p>";
} else {
    foreach ($pets as $pet) {
        $photo_path = !empty($pet['photo_path']) ? $pet['photo_path'] : 'https://via.placeholder.com/50';
        $pet_name = htmlspecialchars($pet['name']);
        echo "<a href='manajemen_pet.php?id={$pet['id']}' class='pet-item'>";
        echo "<img src='{$photo_path}' alt='Foto {$pet_name}'>";
        echo "<span>{$pet_name}</span>";
        echo "</a>";
    }
}

$stmt->close();
$conn->close();
?>