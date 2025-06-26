<?php
// index.php

$page_title = 'Beranda'; // Tentukan judul halaman
$extra_css = ['css/beranda.css']; // Tentukan CSS tambahan untuk halaman ini
require 'includes/header.php'; // Panggil header

// Kode PHP spesifik untuk halaman ini tetap di sini
require 'php/db_connection.php';
$user_id = $_SESSION['user_id'];

// Cek program user...
$stmt = $conn->prepare("SELECT program_id FROM program_members WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$programs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!empty($programs)) {
    $_SESSION['active_program_id'] = $programs[0]['program_id'];
}
?>

<?php if (empty($programs)): ?>
    <div class="no-program-view">
        <h2>Selamat Datang!</h2>
        <p>Anda belum mempunyai program peliharaan apapun.</p>
        <a href="buat_program.php" class="btn btn-primary">Buat Baru</a>
        <p class="or-text">atau</p>
        <a href="gabung_program.php" class="btn btn-secondary">Tambah yang sudah ada</a>
    </div>
<?php else: ?>
    <div class="dashboard-view">
        <h2>Program: Peliharaan Rumah</h2>
        
        <div class="quick-access">
            <a href="lihat_peliharaan.php" class="btn">Lihat atau Atur Peliharaan</a>
            <a href="buat_jadwal_baru.php" class="btn">Buat Jadwal Baru</a>
            <a href="jadwal_lengkap.php" class="btn">Lihat Jadwal Lengkap</a>
        </div>

        <div class="upcoming-schedule">
            <h3>Jadwal Terdekat</h3>
            <div class="schedule-item">
                <p><strong>Memberi makan Mujair</strong></p>
                <p><?php echo date('d F Y - H:i', time()); ?></p>
                <button class="btn-done">Sudah</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require 'includes/footer.php'; // Panggil footer
?>