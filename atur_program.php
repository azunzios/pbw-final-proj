<?php
$page_title = 'Ganti atau Atur Program';
$extra_css = ['css/beranda.css'];
require 'includes/header.php';
require 'php/db_connection.php';

// Ambil semua program yang diikuti user
$sql = "SELECT p.id, p.program_name, p.owner_user_id, pm.alias 
        FROM programs p 
        JOIN program_members pm ON p.id = pm.program_id 
        WHERE pm.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h2>Daftar Program Anda</h2>
<div class="program-list">
    <?php foreach ($programs as $program): ?>
        <div class="program-item">
            <div class="program-info">
                <strong><?php echo htmlspecialchars($program['program_name']); ?></strong>
                (<?php echo htmlspecialchars($program['alias']); ?>)
            </div>
            <div class="program-actions">
                <?php if ($program['owner_user_id'] == $_SESSION['user_id']): ?>
                    <a href="edit_program.php?id=<?php echo $program['id']; ?>" class="btn-action edit">Ubah</a>
                <?php else: ?>
                    <form action="php/program_handler.php" method="POST" onsubmit="return confirm('Anda yakin ingin keluar dari program ini?');">
                        <input type="hidden" name="action" value="leave_program">
                        <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                        <button type="submit" class="btn-action leave">Keluar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="form-link">
    <p><a href="index.php">&laquo; Kembali ke Beranda</a></p>
</div>

<?php
require 'includes/footer.php';
?>