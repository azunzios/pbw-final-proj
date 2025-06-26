<?php
require_once 'php/db_connection.php';
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();
$stmt->close();

if (!$pet) {
    exit('Peliharaan tidak ditemukan.');
}

$page_title = 'Manajemen ' . htmlspecialchars($pet['name']);
require 'includes/header.php';
?>

<h1>Manajemen <?php echo htmlspecialchars($pet['name']); ?></h1>
<form action="php/pet_handler.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
    <div class="form-group">
        <label for="name">Nama:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="notes">Deskripsi:</label>
        <input type="text" id="notes" name="notes" value="<?php echo htmlspecialchars($pet['notes']); ?>">
    </div>
    <div class="form-group">
        <label>Foto Saat Ini:</label>
        <img src="<?php echo !empty($pet['photo_path']) ? $pet['photo_path'] : 'https://via.placeholder.com/100'; ?>" alt="Foto" style="max-width: 100px; display: block; margin-bottom: 10px;">
        <label for="photo">Ganti Foto:</label>
        <input type="file" id="photo" name="photo" accept="image/*">
    </div>
    <button type="submit" class="btn">Save</button>
</form>

<div class="form-link">
    <p><a href="lihat_peliharaan.php">&laquo; Back</a></p>
</div>

<div class="download-link-container" style="margin-top: 20px; text-align: center;">
    <a href="php/export_csv.php?pet_id=<?php echo $pet['id']; ?>" class="download-link">Unduh data dan history perawatan (.csv)</a>
</div>
<div class="form-link"></div>

<?php
require 'includes/footer.php';
?>