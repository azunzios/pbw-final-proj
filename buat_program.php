<?php
$page_title = 'Buat Program';
require 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Program Baru</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            <h1>Buat Program Baru</h1>
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
    </div>
</body>
</html>
<?php
require 'includes/footer.php';
?>