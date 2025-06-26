<?php
$page_title = 'Tambah Peliharaan Baru';
require 'includes/header.php';
?>

<h1>Tambah Peliharaan Baru</h1>
<form action="php/pet_handler.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">
    <div class="form-group">
        <label for="name">Nama:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="notes">Deskripsi:</label>
        <input type="text" id="notes" name="notes">
    </div>
    <div class="form-group">
        <label for="photo">Foto:</label>
        <input type="file" id="photo" name="photo" accept="image/*">
    </div>
    <button type="submit" class="btn">Submit</button>
</form>
<div class="form-link">
    <p><a href="lihat_peliharaan.php">&laquo; Kembali</a></p>
</div>

<?php
require 'includes/footer.php';
?>