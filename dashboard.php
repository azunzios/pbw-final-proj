<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Cek otentikasi dan dapatkan data pengguna
checkAuth();
$user = getCurrentUser();
$pageTitle = 'Beranda';

// Inisialisasi variabel statistik untuk mencegah error jika query gagal
$pet_stats = ['total_pets' => 0, 'dogs' => 0, 'cats' => 0, 'birds' => 0, 'fish' => 0, 'others' => 0];
$schedule_stats = ['total_today' => 0, 'completed' => 0, 'missed' => 0, 'remaining' => 0];
$next_schedule = null;
$today = date('Y-m-d');

try {
    $pdo = connectDB();
    $userId = $user['id'];

    // 1. Ambil statistik peliharaan
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_pets,
            COUNT(CASE WHEN type = 'Anjing' THEN 1 END) as dogs,
            COUNT(CASE WHEN type = 'Kucing' THEN 1 END) as cats,
            COUNT(CASE WHEN type = 'Burung' THEN 1 END) as birds,
            COUNT(CASE WHEN type = 'Ikan' THEN 1 END) as fish,
            COUNT(CASE WHEN type NOT IN ('Anjing', 'Kucing', 'Burung', 'Ikan') THEN 1 END) as others
        FROM pets WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $pet_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Ambil statistik jadwal hari ini
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_today,
            COUNT(CASE WHEN si.is_done = 1 THEN 1 END) as completed,
            COUNT(CASE WHEN si.is_done = 0 AND CONCAT(si.date, ' ', s.schedule_time) < NOW() THEN 1 END) as missed,
            COUNT(CASE WHEN si.is_done = 0 AND CONCAT(si.date, ' ', s.schedule_time) >= NOW() THEN 1 END) as remaining
        FROM schedule_instances si 
        JOIN schedules s ON si.schedule_id = s.id 
        WHERE s.user_id = ? AND si.date = ?
    ");
    $stmt->execute([$userId, $today]);
    $schedule_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Ambil jadwal terdekat yang belum selesai
    $stmt = $pdo->prepare("
        SELECT 
            s.care_type, s.schedule_time, p.name as pet_name, si.id as instance_id
        FROM schedules s 
        JOIN pets p ON s.pet_id = p.id 
        JOIN schedule_instances si ON s.id = si.schedule_id
        WHERE s.user_id = ? AND si.date = ? AND si.is_done = 0 AND CONCAT(si.date, ' ', s.schedule_time) > NOW()
        ORDER BY s.schedule_time LIMIT 1
    ");
    $stmt->execute([$userId, $today]);
    $next_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Bisa ditambahkan logging error di sini, misal: error_log($e->getMessage());
}

/**
 * Helper function untuk format waktu tersisa.
 */
function formatTimeUntil($scheduleTime)
{
    $now = new DateTime();
    $target = new DateTime(date('Y-m-d') . ' ' . $scheduleTime);
    if ($now > $target) return 'Telah lewat';

    $interval = $now->diff($target);
    $minutes = $interval->h * 60 + $interval->i;

    if ($minutes >= 60) {
        return floor($minutes / 60) . ' jam ' . ($minutes % 60) . ' menit lagi';
    }
    return $minutes . ' menit lagi';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - PetCare Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>
    <?php 
    $pageTitle = 'Beranda'; 
    include 'components/header.php';
    ?>
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>
        <main class="main-content">
            <section class="section">
                <h2 class="section-title">Ikhtisar</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$pet_stats['total_pets'] ?></div>
                        <div class="stat-label">Total Peliharaan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$schedule_stats['total_today'] ?></div>
                        <div class="stat-label">Jadwal Hari Ini</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-success"><?= (int)$schedule_stats['completed'] ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?= (int)$schedule_stats['missed'] ?></div>
                        <div class="stat-label">Terlewat</div>
                    </div>
                </div>

                <h3 class="chart-title">Peliharaan per Kategori</h3>
                <div class="stats-grid">
                    <div class="stat-card category-dog">
                        <div class="stat-number"><?= (int)$pet_stats['dogs'] ?></div>
                        <div class="stat-label">🐶 Anjing</div>
                    </div>
                    <div class="stat-card category-cat">
                        <div class="stat-number"><?= (int)$pet_stats['cats'] ?></div>
                        <div class="stat-label">🐱 Kucing</div>
                    </div>
                    <div class="stat-card category-bird">
                        <div class="stat-number"><?= (int)$pet_stats['birds'] ?></div>
                        <div class="stat-label">🐦 Burung</div>
                    </div>
                    <div class="stat-card category-fish">
                        <div class="stat-number"><?= (int)$pet_stats['fish'] ?></div>
                        <div class="stat-label">🐠 Ikan</div>
                    </div>
                </div>
            </section>

            <?php if ($next_schedule): ?>
                <section class="section">
                    <h2 class="section-title">Jadwal Terdekat</h2>
                    <div class="schedule-card">
                        <div class="schedule-info">
                            <h3><?= htmlspecialchars($next_schedule['care_type']) ?></h3>
                            <div class="schedule-time">
                                🐾 <?= htmlspecialchars($next_schedule['pet_name']) ?> •
                                ⏰ <?= date('H:i', strtotime($next_schedule['schedule_time'])) ?>
                                <span>(<?= formatTimeUntil($next_schedule['schedule_time']) ?>)</span>
                            </div>
                        </div>
                        <div class="schedule-actions">
                            <button class="btn btn-complete" onclick="completeSchedule(<?= (int)$next_schedule['instance_id'] ?>)">
                                ✓ Tandai Selesai
                            </button>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="section">
                <h2 class="section-title">Pintasan</h2>
                <div class="shortcuts-grid">
                    <a href="schedules.php" class="shortcut-card">
                        <div class="shortcut-icon">📅</div>
                        <div class="shortcut-title">Kelola Jadwal</div>
                        <div class="shortcut-desc">Atur jadwal perawatan</div>
                    </a>
                    <a href="pets.php" class="shortcut-card">
                        <div class="shortcut-icon">🐾</div>
                        <div class="shortcut-title">Kelola Peliharaan</div>
                        <div class="shortcut-desc">Tambah dan kelola data</div>
                    </a>
                    <a href="measurements.php" class="shortcut-card">
                        <div class="shortcut-icon">📊</div>
                        <div class="shortcut-title">Pengukuran</div>
                        <div class="shortcut-desc">Pantau perkembangan</div>
                    </a>
                    <a href="profile.php" class="shortcut-card">
                        <div class="shortcut-icon">⚙️</div>
                        <div class="shortcut-title">Pengaturan</div>
                        <div class="shortcut-desc">Atur profil & preferensi</div>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function completeSchedule(instanceId) {
            if (!confirm('Apakah Anda yakin jadwal ini sudah selesai?')) return;

            fetch('api/complete-schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'instance_id=' + instanceId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Terjadi kesalahan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                });
        }
    </script>
</body>

</html>