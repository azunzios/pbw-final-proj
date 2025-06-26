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
<div class="form-container">
<h1>Manajemen <?php echo htmlspecialchars($pet['name']); ?></h1>
<form action="php/pet_handler.php" method="POST" enctype="multipart/form-data" id="formEditPet">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
    <div class="form-group">
        <label for="name">Nama:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="jenis">Jenis Peliharaan:</label>
        <select id="jenis" name="jenis" required>
            <option value="">-- Pilih Jenis --</option>
            <option value="Kucing" <?php if($pet['jenis']==='Kucing') echo 'selected'; ?>>Kucing</option>
            <option value="Anjing" <?php if($pet['jenis']==='Anjing') echo 'selected'; ?>>Anjing</option>
            <option value="Burung" <?php if($pet['jenis']==='Burung') echo 'selected'; ?>>Burung</option>
            <option value="Ikan" <?php if($pet['jenis']==='Ikan') echo 'selected'; ?>>Ikan</option>
            <option value="Kelinci" <?php if($pet['jenis']==='Kelinci') echo 'selected'; ?>>Kelinci</option>
            <option value="Lainnya" <?php if($pet['jenis']==='Lainnya') echo 'selected'; ?>>Lainnya</option>
        </select>
        <input type="text" id="jenis_lain" name="jenis_lain" placeholder="Isi jenis lain..." style="margin-top:8px;<?php if($pet['jenis']!=='Lainnya') echo 'display:none;'; ?>" value="<?php echo htmlspecialchars($pet['jenis_lain']); ?>">
    </div>
    <div class="form-group">
        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">-- Pilih Gender --</option>
            <option value="Jantan" <?php if($pet['gender']==='Jantan') echo 'selected'; ?>>Jantan</option>
            <option value="Betina" <?php if($pet['gender']==='Betina') echo 'selected'; ?>>Betina</option>
        </select>
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
<form action="php/pet_handler.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus peliharaan ini?');" style="margin-top:16px;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
    <button type="submit" class="btn btn-danger">Hapus Peliharaan</button>
</form>
</div>

<div class="form-link">
    <p><a href="lihat_peliharaan.php">&laquo; Back</a></p>
</div>

<div class="download-link-container" style="margin-top: 20px; text-align: center;">
    <a href="php/export_csv.php?pet_id=<?php echo $pet['id']; ?>" class="download-link">Unduh data dan history perawatan (.csv)</a>
</div>
<div class="form-link"></div>

<script>
document.getElementById('jenis').addEventListener('change', function() {
    var lain = document.getElementById('jenis_lain');
    if(this.value === 'Lainnya') {
        lain.style.display = 'block';
        lain.required = true;
    } else {
        lain.style.display = 'none';
        lain.required = false;
        lain.value = '';
    }
});
</script>

<?php
require 'includes/footer.php';
?>