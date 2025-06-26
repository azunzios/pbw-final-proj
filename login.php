<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Manajer Peliharaan</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            <h1>Aplikasi Manajer Peliharaan</h1>
            
            <?php
            // Menampilkan pesan error jika ada
            if (isset($_GET['error'])) {
                echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
            }
            ?>

            <form action="php/login_handler.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Masuk</button>
            </form>
            <div class="form-link">
                <p>Belum punya akun? <a href="signup.php">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html>