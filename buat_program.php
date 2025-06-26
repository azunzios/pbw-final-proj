<?php
$page_title = 'Buat Program';
require 'includes/header.php';
?>
<div class="form-container">
<h1>Buat Program Baru</h1>
<?php if (isset($_GET['success']) && isset($_SESSION['last_join_code'])): ?>
    <div class="success-message" style="background:#e0ffe0;color:#222;padding:12px;margin-bottom:16px;border-radius:4px;">
        Program berhasil dibuat!<br>Kode program Anda: <strong><?php echo htmlspecialchars($_SESSION['last_join_code']); ?></strong><br>
        Bagikan kode ini ke teman untuk gabung ke program Anda.
    </div>
    <?php unset($_SESSION['last_join_code']); ?>
<?php endif; ?>
<form action="php/program_handler.php" method="POST">
    <input type="hidden" name="action" value="create">
    <div class="form-group">
        <label for="program_name">Nama Program:</label>
        <input type="text" id="program_name" name="program_name" placeholder="Contoh: Peliharaan Rumah" required>
    </div>
    <div class="form-group">
        <label for="alias">Alias (nama familiar untuk kamu sendiri):</label>
        <input type="text" id="alias" name="alias" placeholder="Contoh: Kucing-kucingku" required>
    </div>
    <div class="form-group">
        <label for="description">Deskripsi:</label>
        <textarea id="description" name="description" rows="3"></textarea>
    </div>
    <button type="submit" class="btn">Buat</button>
</form>
<div class="form-link">
    <p><a href="index.php">&laquo; Kembali ke Beranda</a></p>
</div>
</div>
<?php
require 'includes/footer.php';
?>