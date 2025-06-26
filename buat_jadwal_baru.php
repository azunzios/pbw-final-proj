<?php
$page_title = 'Buat Jadwal Baru';
require 'includes/header.php';
// buat_jadwal_baru.php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['active_program_id'])) {
    header('Location: login.php');
    exit();
}

require 'php/db_connection.php';
$program_id = $_SESSION['active_program_id'];

// Ambil semua pet dari program aktif untuk ditampilkan di dropdown
$stmt = $conn->prepare("SELECT id, name FROM pets WHERE program_id = ? ORDER BY name");
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();
$pets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Jadwal Baru</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hidden-field { display: none; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="form-container">
            <h1>Buat Jadwal Baru</h1>
            <form action="php/schedule_handler.php" method="POST">
                <div class="form-group">
                    <label for="agenda_name">Nama agenda:</label>
                    <input type="text" id="agenda_name" name="agenda_name" required>
                </div>
                
                <div class="form-group">
                    <label for="pet_id">Pet:</label>
                    <select id="pet_id" name="pet_id">
                        <option value="">-- Umum (Tidak spesifik) --</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>"><?php echo htmlspecialchars($pet['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <hr style="margin: 20px 0;">

                <div class="form-group" id="non-routine-fields">
                    <label for="schedule_date">Tanggal:</label>
                    <input type="date" name="schedule_date" value="<?php echo date('Y-m-d'); ?>">
                    <label for="schedule_time">Jam:</label>
                    <input type="time" name="schedule_time" required>
                </div>
                
                <div class="form-group">
                    <label><input type="checkbox" id="is_routine"> Jadwal rutin</label>
                </div>

                <div id="routine-options" class="hidden-field">
                    <div class="form-group">
                        <label for="routine_type">Tipe:</label>
                        <select id="routine_type" name="routine_type">
                            <option value="harian">Harian</option>
                            <option value="mingguan">Mingguan</option>
                            <option value="bulanan">Bulanan</option>
                        </select>
                    </div>
                    <div id="routine-details"></div>
                </div>

                <div class="form-group">
                    <label for="label">Label:</label>
                    <select id="label" name="label">
                        <option value="feeding">Feeding</option>
                        <option value="event">Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Submit</button>
            </form>
             <div class="form-link">
                <p><a href="index.php">&laquo; Kembali ke Beranda</a></p>
            </div>
        </div>
    </div>

    <script src="js/jadwal_form.js"></script>
</body>
</html>
<?php
require 'includes/footer.php';
?>