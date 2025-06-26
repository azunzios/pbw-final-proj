<?php
// php/export_csv.php
session_start();
require 'db_connection.php';

// Validasi: pastikan user login dan pet_id ada
if (!isset($_SESSION['user_id']) || !isset($_GET['pet_id'])) {
    exit('Akses ditolak.');
}

$pet_id = (int)$_GET['pet_id'];
$program_id = $_SESSION['active_program_id'];

// Keamanan: Cek apakah pet ini benar-benar milik program user yang sedang aktif
$stmt_check = $conn->prepare("SELECT name FROM pets WHERE id = ? AND program_id = ?");
$stmt_check->bind_param("ii", $pet_id, $program_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows == 0) {
    exit('Anda tidak memiliki izin untuk mengakses data ini.');
}
$pet = $result_check->fetch_assoc();
$pet_name = $pet['name'];
$stmt_check->close();

// --- Pengaturan Header HTTP untuk Unduhan CSV ---
$filename = "history_perawatan_" . strtolower(str_replace(' ', '_', $pet_name)) . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Buka output stream PHP untuk menulis file CSV
$output = fopen('php://output', 'w');

// Tulis baris header untuk file CSV
fputcsv($output, [
    'Tanggal Jadwal', 
    'Jam', 
    'Agenda', 
    'Label', 
    'Status', 
    'Diselesaikan Pada', 
    'Diselesaikan Oleh'
]);

// --- Ambil Data History Perawatan dari Database ---
$sql = "SELECT 
            s.schedule_time, 
            s.title, 
            s.label, 
            s.is_done, 
            s.done_at, 
            u.username as done_by_username
        FROM schedules s
        LEFT JOIN users u ON s.done_by_user_id = u.id
        WHERE s.pet_id = ?
        ORDER BY s.schedule_time DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

// --- Tulis setiap baris data ke file CSV ---
while ($row = $result->fetch_assoc()) {
    $schedule_date = new DateTime($row['schedule_time']);
    
    $csv_row = [
        $schedule_date->format('Y-m-d'), // Tanggal Jadwal
        $schedule_date->format('H:i'),   // Jam
        $row['title'],                   // Agenda
        $row['label'],                   // Label
        $row['is_done'] ? 'Selesai' : 'Belum Selesai', // Status
        $row['is_done'] ? (new DateTime($row['done_at']))->format('Y-m-d H:i') : '-', // Diselesaikan Pada
        $row['is_done'] ? $row['done_by_username'] : '-' // Diselesaikan Oleh
    ];
    
    fputcsv($output, $csv_row);
}

$stmt->close();
fclose($output);
$conn->close();
exit();
?>