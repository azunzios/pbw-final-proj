<?php
$page_title = 'Gabung Program';
require 'includes/header.php';
?>
<div class="form-container">
    <h1>Gabung Program</h1>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    <form action="php/program_handler.php" method="POST">
        <input type="hidden" name="action" value="join_program">
        <div class="form-group">
            <label for="join_code">Kode Program:</label>
            <input type="text" id="join_code" name="join_code" maxlength="10" required>
        </div>
        <div class="form-group">
            <label for="alias">Alias (nama panggilan Anda di program ini):</label>
            <input type="text" id="alias" name="alias" required>
        </div>
        <button type="submit" class="btn">Gabung</button>
    </form>
    <div class="form-link">
        <a href="index.php">&laquo; Kembali ke Beranda</a>
    </div>
</div>
<?php
require 'includes/footer.php';
?>
