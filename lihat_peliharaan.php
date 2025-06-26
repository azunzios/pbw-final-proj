<?php
$page_title = 'Atur Peliharaan';
$extra_css = ['css/beranda.css']; // Pakai style yang mirip
require 'includes/header.php';
require 'php/db_connection.php';

$program_id = $_SESSION['active_program_id'];

// Ambil semua pet dari program aktif
$stmt = $conn->prepare("SELECT id, name, photo_path FROM pets WHERE program_id = ? ORDER BY name");
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();
$pets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h2>Lihat atau Atur Peliharaan</h2>

<div class="form-group">
    <input type="search" id="search-pet" placeholder="Search pet...">
</div>

<a href="tambah_peliharaan.php" class="btn">Tambah Peliharaan Baru</a>

<div id="pet-list" class="pet-list-container">
    <?php if (empty($pets)): ?>
        <p>Belum ada peliharaan ditambahkan.</p>
    <?php else: ?>
        <?php foreach ($pets as $pet): ?>
            <a href="manajemen_pet.php?id=<?php echo $pet['id']; ?>" class="pet-item">
                <img src="<?php echo !empty($pet['photo_path']) ? $pet['photo_path'] : 'https://via.placeholder.com/50'; ?>" alt="Foto <?php echo htmlspecialchars($pet['name']); ?>">
                <span><?php echo htmlspecialchars($pet['name']); ?></span>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div class="form-link">
    <p><a href="index.php">&laquo; Kembali ke Beranda</a></p>
</div>
    
<script>
document.getElementById('search-pet').addEventListener('keyup', function() {
    let searchQuery = this.value;
    let programId = <?php echo $program_id; ?>;
    fetch(`php/search_pets_handler.php?query=${searchQuery}&program_id=${programId}`)
    .then(response => response.text())
    .then(data => {
        document.getElementById('pet-list').innerHTML = data;
    });
});
</script>

<?php
require 'includes/footer.php';
?>