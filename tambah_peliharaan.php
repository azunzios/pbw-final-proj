<?php
$page_title = 'Tambah Peliharaan Baru';
require 'includes/header.php';
?>
<div class="form-container">
<h1>Tambah Peliharaan Baru</h1>
<form action="php/pet_handler.php" method="POST" enctype="multipart/form-data" id="formTambahPet">
    <input type="hidden" name="action" value="create">
    <div class="form-group">
        <label for="name">Nama:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="jenis">Jenis Peliharaan:</label>
        <select id="jenis" name="jenis" required>
            <option value="">-- Pilih Jenis --</option>
            <option value="Kucing">Kucing</option>
            <option value="Anjing">Anjing</option>
            <option value="Burung">Burung</option>
            <option value="Ikan">Ikan</option>
            <option value="Kelinci">Kelinci</option>
            <option value="Lainnya">Lainnya</option>
        </select>
        <input type="text" id="jenis_lain" name="jenis_lain" placeholder="Isi jenis lain..." style="display:none;margin-top:8px;">
    </div>
    <div class="form-group">
        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
            <option value="">-- Pilih Gender --</option>
            <option value="Jantan">Jantan</option>
            <option value="Betina">Betina</option>
            <option value="Lainnya">Lainnya</option>
        </select>
        <input type="text" id="gender_lain" name="gender_lain" placeholder="Isi gender lain..." style="display:none;margin-top:8px;">
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
document.getElementById('gender').addEventListener('change', function() {
    var lain = document.getElementById('gender_lain');
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
<div class="form-link">
    <p><a href="lihat_peliharaan.php">&laquo; Kembali</a></p>
</div>
</div>
<?php
require 'includes/footer.php';
?>