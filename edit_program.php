<?php
$page_title = 'Pengaturan Program';
require 'includes/header.php';
require 'php/db_connection.php';

$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Keamanan: Pastikan user adalah pemilik program
$stmt = $conn->prepare("SELECT * FROM programs WHERE id = ? AND owner_user_id = ?");
$stmt->bind_param("ii", $program_id, $_SESSION['user_id']);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
if (!$program) {
    exit('Akses ditolak atau program tidak ditemukan.');
}

// Ambil daftar anggota
$sql_members = "SELECT u.id, u.username FROM users u JOIN program_members pm ON u.id = pm.user_id WHERE pm.program_id = ?";
$stmt_members = $conn->prepare($sql_members);
$stmt_members->bind_param("i", $program_id);
$stmt_members->execute();
$members = $stmt_members->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="form-container">
<h2>Pengaturan: <?php echo htmlspecialchars($program['program_name']); ?></h2>

<form action="php/program_handler.php" method="POST">
    <input type="hidden" name="action" value="update_details">
    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
    <div class="form-group">
        <label>Nama Program:</label>
        <input type="text" name="program_name" value="<?php echo htmlspecialchars($program['program_name']); ?>">
    </div>
    <div class="form-group">
        <label>Deskripsi:</label>
        <textarea name="description"><?php echo htmlspecialchars($program['description']); ?></textarea>
    </div>
    <button type="submit" class="btn">Simpan Informasi</button>
</form>

<hr style="margin: 30px 0;">

<h3>Anggota</h3>
<div class="program-list">
    <?php foreach($members as $member): ?>
    <div class="program-item">
        <span><?php echo htmlspecialchars($member['username']); ?> <?php echo ($member['id'] == $program['owner_user_id']) ? '(Pembuat)' : ''; ?></span>
        <?php if($member['id'] != $_SESSION['user_id']): ?>
        <form action="php/program_handler.php" method="POST" onsubmit="return confirm('Yakin hapus anggota ini?');">
            <input type="hidden" name="action" value="remove_member">
            <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
            <input type="hidden" name="user_id_to_remove" value="<?php echo $member['id']; ?>">
            <button type="submit" class="btn-action leave">Hapus</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<hr style="margin: 30px 0;">

<div class="danger-zone">
    <h3>Peringatan</h3>
    <p>Menghapus program akan menghilangkan semua data terkait (peliharaan, jadwal, dll.) secara permanen.</p>
    <form action="php/program_handler.php" method="POST" onsubmit="return confirm('ANDA YAKIN INGIN MENGHAPUS SELURUH PROGRAM INI? AKSI INI TIDAK BISA DIBATALKAN.');">
        <input type="hidden" name="action" value="delete_program">
        <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
        <button type="submit" class="btn btn-danger">Hapus Program Ini</button>
    </form>
</div>
</div>

<?php
require 'includes/footer.php';
?>