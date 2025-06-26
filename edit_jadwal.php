<?php
$page_title = 'Edit Jadwal';
require 'includes/header.php';
require 'php/db_connection.php';

$schedule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data jadwal yang ada
$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();
if (!$schedule) {
    exit('Jadwal tidak ditemukan.');
}

// Ambil data pets untuk dropdown
$stmt_pets = $conn->prepare("SELECT id, name FROM pets WHERE program_id = ?");
$stmt_pets->bind_param("i", $_SESSION['active_program_id']);
$stmt_pets->execute();
$pets = $stmt_pets->get_result()->fetch_all(MYSQLI_ASSOC);

$schedule_datetime = new DateTime($schedule['schedule_time']);
?>

<h1>Edit Jadwal</h1>
<p class="info-text">Anda sedang mengedit jadwal untuk tanggal <?php echo $schedule_datetime->format('d F Y'); ?>.</p>

<form action="php/schedule_handler.php" method="POST">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
    
    <div class="form-group">
        <label for="title">Nama agenda:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($schedule['title']); ?>" required>
    </div>
    <div class="form-group">
        <label for="pet_id">Pet:</label>
        <select id="pet_id" name="pet_id">
            <option value="">-- Umum --</option>
            <?php foreach ($pets as $pet): ?>
                <option value="<?php echo $pet['id']; ?>" <?php echo ($pet['id'] == $schedule['pet_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pet['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="schedule_time">Jam:</label>
        <input type="time" name="schedule_time" value="<?php echo $schedule_datetime->format('H:i'); ?>" required>
    </div>
    <div class="form-group">
        <label for="label">Label:</label>
        <select id="label" name="label">
            <option value="feeding" <?php echo ($schedule['label'] == 'feeding') ? 'selected' : ''; ?>>Feeding</option>
            <option value="event" <?php echo ($schedule['label'] == 'event') ? 'selected' : ''; ?>>Event</option>
            <option value="other" <?php echo ($schedule['label'] == 'other') ? 'selected' : ''; ?>>Other</option>
        </select>
    </div>
    <button type="submit" class="btn">Simpan Perubahan</button>
</form>

<div class="form-link">
    <p><a href="jadwal_lengkap.php">&laquo; Batal</a></p>
</div>

<?php
require 'includes/footer.php';
?>