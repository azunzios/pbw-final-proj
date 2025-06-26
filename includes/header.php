<?php
// includes/header.php
session_start();

// Halaman ini tidak memerlukan login, jadi kita buat pengecualian
$no_auth_pages = ['/login.php', '/signup.php'];

// Jika halaman ini memerlukan login DAN user belum login, tendang ke login.php
if (!in_array($_SERVER['PHP_SELF'], $no_auth_pages) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? ''; // Ambil username jika ada
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Manajer Peliharaan'; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <div class="main-container">
        <?php if (isset($_SESSION['user_id'])): // Tampilkan header hanya jika sudah login ?>
            <div class="app-header">
                <div class="user-info">
                    <a href="atur_program.php" style="color: inherit; text-decoration: none;">Atur Program</a>
                </div>
                <div class="menu-icon">
                    <a href="php/logout.php" class="logout-link">Log Out</a>
                </div>
            </div>
        <?php endif; ?>
        <!-- Mulai konten utama -->