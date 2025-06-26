<?php
$page_title = 'Jadwal Lengkap';
$extra_css = ['css/jadwal.css'];
require 'includes/header.php';
require 'php/db_connection.php';

$program_id = $_SESSION['active_program_id'];

// --- LOGIKA PENGATURAN TANGGAL ---
$start_of_week_str = $_GET['start_date'] ?? 'now';
$start_of_week = new DateTime($start_of_week_str);
if ($start_of_week->format('N') != 1) {
    $start_of_week->modify('last monday');
}
$end_of_week = (clone $start_of_week)->modify('+6 days');
$prev_week_start = (clone $start_of_week)->modify('-7 days')->format('Y-m-d');
$next_week_start = (clone $start_of_week)->modify('+7 days')->format('Y-m-d');

// --- PENGAMBILAN DATA JADWAL ---
$sql = "SELECT s.id, s.title, s.schedule_time, s.is_done, p.name as pet_name 
        FROM schedules s 
        LEFT JOIN pets p ON s.pet_id = p.id 
        WHERE s.program_id = ? AND s.schedule_time BETWEEN ? AND ? 
        ORDER BY s.schedule_time ASC";
$stmt = $conn->prepare($sql);
$start_date_sql = $start_of_week->format('Y-m-d 00:00:00');
$end_date_sql = $end_of_week->format('Y-m-d 23:59:59');
$stmt->bind_param("iss", $program_id, $start_date_sql, $end_date_sql);
$stmt->execute();
$result = $stmt->get_result();

$schedules_by_day = [];
while ($row = $result->fetch_assoc()) {
    $day_of_week = (new DateTime($row['schedule_time']))->format('N');
    $schedules_by_day[$day_of_week][] = $row;
}
$stmt->close();
$conn->close();
$days_in_indonesian = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
?>

<h2>Jadwal Lengkap</h2>
<a href="buat_jadwal_baru.php" class="btn">Buat Jadwal Baru</a>

<div class="calendar-nav">
    <a href="?start_date=<?php echo $prev_week_start; ?>" class="nav-arrow">&lt;</a>
    <span class="current-week"><?php echo $start_of_week->format('d M') . ' - ' . $end_of_week->format('d M Y'); ?></span>
    <a href="?start_date=<?php echo $next_week_start; ?>" class="nav-arrow">&gt;</a>
</div>

<div class="calendar-grid">
    <?php for ($i = 1; $i <= 7; $i++): ?>
        <div class="day-column">
            <div class="day-header"><?php echo $days_in_indonesian[$i]; ?></div>
            <div class="day-body">
                <?php if (isset($schedules_by_day[$i])): ?>
                    <?php foreach ($schedules_by_day[$i] as $schedule): ?>
                        <div class="schedule-card <?php echo $schedule['is_done'] ? 'done' : ''; ?>">
                            <div class="card-actions">
                                <a href="edit_jadwal.php?id=<?php echo $schedule['id']; ?>" class="action-btn edit-btn">&#9998;</a>
                                <form action="php/schedule_handler.php" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus jadwal ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" class="action-btn delete-btn">&times;</button>
                                </form>
                            </div>
                            <div class="schedule-time"><?php echo (new DateTime($schedule['schedule_time']))->format('H:i'); ?></div>
                            <div class="schedule-title"><?php echo htmlspecialchars($schedule['title']); ?></div>
                            <?php if($schedule['pet_name']): ?>
                                <div class="schedule-pet"><?php echo htmlspecialchars($schedule['pet_name']); ?></div>
                            <?php endif; ?>

                            <?php if (!$schedule['is_done']): ?>
                                <form action="php/mark_done_handler.php" method="POST" enctype="multipart/form-data" class="done-form">
                                    </form>
                            <?php else: ?>
                                <div class="done-status">&#10004; Selesai</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>

<div class="form-link" style="margin-top: 20px;">
    <p><a href="index.php">&laquo; Kembali ke Beranda</a></p>
</div>

<?php
require 'includes/footer.php';
?>