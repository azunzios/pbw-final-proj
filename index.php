<?php
require_once 'config/database.php';

// Cek apakah user sudah login
session_start();

// Cek session login biasa
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Cek remember me cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name 
            FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            header('Location: dashboard.php');
            exit;
        } else {
            // Token tidak valid, hapus cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (Exception $e) {
        // Token error, hapus cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// Cek apakah ada pesan error
$error_message = '';
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - PetCare Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-left">
            <img src="assets/img/login_img.png" alt="PetCare Logo" class="login">
            <div class="login-web-info">
                <div class="pet-icons">
                    <div class="pet-icon">🐶</div>
                    <div class="pet-icon">🐱</div>
                    <div class="pet-icon">🐰</div>
                    <div class="pet-icon">🐦</div>
                    <div class="pet-icon">🐠</div>
                </div>
                <div class="app-logo">
                    <img src="assets/img/logo.svg" alt="PetCare Logo">
                </div>
                <div class="app-subtitle">
                    Kelola <span id="animal-container"><span id="animal">Peliharaan</span></span> Kesayangan Anda
                </div>


            </div>  
        </div>

        <div class="login-right">
            <!-- Login Form -->
            <form class="login-form active" id="loginForm" onsubmit="handleLogin(event)">
                <div class="login-header">
                    <img class="login-hi" src="assets/img/hi.svg" alt="Login Icon">
                    <h1 class="login-title">Selamat Datang</h1>
                    <p class="login-subtitle">Masuk ke akun Anda</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan nama pengguna"
                        required
                        autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan kata sandi"
                        required
                        autocomplete="current-password">
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Ingat saya</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary login-btn">
                    Masuk
                </button>

                <div class="register-link">
                    <p>Belum punya akun? <a href="#" onclick="showRegister()">Daftar di sini</a></p>
                </div>
            </form>

            <!-- Register Form -->
            <form class="login-form" id="registerForm" onsubmit="handleRegister(event)">
                <div class="login-header">
                    <h1 class="login-title">Daftar Akun</h1>
                    <p class="login-subtitle">Buat akun baru Anda</p>
                </div>

                <div class="form-group">
                    <label for="reg_fullname" class="form-label">Nama Lengkap</label>
                    <input
                        type="text"
                        id="reg_fullname"
                        name="fullname"
                        class="form-control"
                        placeholder="Masukkan nama lengkap"
                        required>
                </div>

                <div class="form-group">
                    <label for="reg_username" class="form-label">Nama Pengguna</label>
                    <input
                        type="text"
                        id="reg_username"
                        name="username"
                        class="form-control"
                        placeholder="Pilih nama pengguna"
                        required
                        autocomplete="new-username">
                </div>

                <div class="form-group">
                    <label for="reg_email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="reg_email"
                        name="email"
                        class="form-control"
                        placeholder="Masukkan alamat email"
                        required
                        autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="reg_password" class="form-label">Kata Sandi</label>
                    <input
                        type="password"
                        id="reg_password"
                        name="password"
                        class="form-control"
                        placeholder="Buat kata sandi"
                        required
                        autocomplete="new-password"
                        minlength="6"
                        oninput="checkPasswordMatch()">
                </div>

                <div class="form-group">
                    <label for="reg_confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
                    <input
                        type="password"
                        id="reg_confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Ulangi kata sandi"
                        required
                        autocomplete="new-password"
                        oninput="checkPasswordMatch()">
                    <small id="passwordMatchMessage" class="text-danger" style="display: none;">Kata sandi tidak cocok</small>
                </div>

                <button type="submit" class="btn btn-primary login-btn">
                    Daftar
                </button>

                <div class="register-link">
                    <p>Sudah punya akun? <a href="#" onclick="showLogin()">Masuk di sini</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        const animals = ['Kucing', 'Anjing', 'Ikan', 'Burung', 'Kambing', 'Sapi'];
        let index = 0;
        const animalSpan = document.getElementById('animal');

        function animateAnimal() {
            // Phase 1: Slide out (move current text up)
            animalSpan.classList.add('slide-out');
            
            setTimeout(() => {
                // Phase 2: Change text while off-screen
                animalSpan.textContent = animals[index];
                index = (index + 1) % animals.length;
                
                // Phase 3: Position new text below (ready to slide in)
                animalSpan.classList.remove('slide-out');
                animalSpan.classList.add('slide-in');
                
                // Phase 4: Slide in (move new text to center)
                setTimeout(() => {
                    animalSpan.classList.remove('slide-in');
                    animalSpan.classList.add('visible');
                }, 50);
                
                // Clean up classes for next animation
                setTimeout(() => {
                    animalSpan.classList.remove('visible');
                }, 400);
                
            }, 400); // Wait for slide-out to complete
        }

        // Initialize with first animal
        animalSpan.textContent = animals[index];
        index = 1;

        // Start animation loop
        setInterval(animateAnimal, 2500);
    </script>
</body>

</html>