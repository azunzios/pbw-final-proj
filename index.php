<?php
// index.php

$page_title = 'Beranda'; // Tentukan judul halaman
$extra_css = ['css/beranda.css']; // Tentukan CSS tambahan untuk halaman ini
require 'includes/header.php'; // Panggil header

// Kode PHP spesifik untuk halaman ini tetap di sini
require 'php/db_connection.php';

$upcoming_schedule = null;
if(isset($_SESSION['active_program_id'])) {
    $stmt_schedule = $conn->prepare(
        "SELECT title, schedule_time, label FROM schedules 
         WHERE program_id = ? AND is_done = 0 AND schedule_time > NOW() 
         ORDER BY schedule_time ASC LIMIT 1"
    );
    $stmt_schedule->bind_param("i", $_SESSION['active_program_id']);
    $stmt_schedule->execute();
    $result_schedule = $stmt_schedule->get_result();
    $upcoming_schedule = $result_schedule->fetch_assoc();
    $stmt_schedule->close();
}

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

<div class="content-container">
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
            <a href="buat_program.php" class="btn btn-secondary">Tambah Program</a>
        </div>

        <div class="upcoming-schedule">
            <h3>Jadwal Terdekat</h3>
            <?php if ($upcoming_schedule): ?>
                <div class="schedule-item">
                    <p><strong><?php echo htmlspecialchars($upcoming_schedule['title']); ?></strong></p>
                    <p>Label: <?php echo htmlspecialchars($upcoming_schedule['label']); ?></p>
                    <p><?php echo (new DateTime($upcoming_schedule['schedule_time']))->format('d F Y - H:i'); ?></p>
                    <form action="php/mark_done_handler.php" method="POST">
                        <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($upcoming_schedule['id'] ?? ''); ?>">
                        <input type="hidden" name="redirect_url" value="/index.php">
                        <button type="submit" class="btn-done">Sudah</button>
                    </form>
                </div>
            <?php else: ?>
                <p>Tidak ada jadwal terdekat.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>

<?php
require 'includes/footer.php'; // Panggil footer
?>