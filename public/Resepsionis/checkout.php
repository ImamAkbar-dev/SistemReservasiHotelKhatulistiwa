<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../public/login.php");
    exit;
}
require_once '../../config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT r.*, p.nama_Pelanggan, pg.nama_Pegawai, pb.metode_Pembayaran,
          (SELECT GROUP_CONCAT(CONCAT(tk.nama_tipe, ' (', dr.jumlah_Kamar, ')') SEPARATOR ', ') 
           FROM detail_reservasi dr JOIN tipekamar tk ON dr.id_Tipe = tk.id_Tipe 
           WHERE dr.id_Reservasi = r.id_Reservasi) as info_tipe_kamar,
          (SELECT GROUP_CONCAT(k.nomor_Kamar SEPARATOR ', ') 
           FROM detail_kamar dk JOIN kamar k ON dk.id_Kamar = k.id_Kamar 
           WHERE dk.id_Reservasi = r.id_Reservasi) as list_nomor_kamar
          FROM reservasi r 
          JOIN pelanggan p ON r.id_Pelanggan = p.id_Pelanggan 
          JOIN pegawai pg ON r.id_Pegawai = pg.id_Pegawai
          JOIN pembayaran pb ON r.id_Pembayaran = pb.id_Pembayaran
          WHERE r.status_Pembayaran = 'Lunas'
            AND r.tanggal_Check_Out >= CURDATE()
            AND EXISTS (SELECT 1 FROM detail_kamar dk JOIN kamar k ON dk.id_Kamar = k.id_Kamar 
                        WHERE dk.id_Reservasi = r.id_Reservasi AND k.status_Kamar = 'Terisi')";

if (!empty($search)) {
    $query .= " AND (p.nama_Pelanggan LIKE :search OR r.tanggal_Check_In LIKE :search OR r.tanggal_Check_Out LIKE :search)";
    $query .= " ORDER BY r.tanggal_Check_Out ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $query .= " ORDER BY r.tanggal_Check_Out ASC";
    $stmt = $conn->query($query);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Check-Out - Hotel Khatulistiwa</title>
    <style>
        .search-form { margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #F8FAFC; padding: 15px; border-radius: 12px; }
        .search-form input[type="text"] { flex: 1; padding: 10px; border: 1px solid #CBD5E0; border-radius: 8px; }
        .search-form button { background: #2563EB; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        .search-form a { background: #E2E8F0; color: #1E293B; padding: 10px 20px; border-radius: 8px; text-decoration: none; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #F0F7FF; padding: 30px; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 25px; border-radius: 15px; border-top: 8px solid #2563EB; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .logo-area h1 { font-size: 1.8rem; color: #1E3A8A; border-left: 5px solid #2563EB; padding-left: 15px; }
        .logo-area p { color: #475569; margin-top: 5px; }
        .nav-menu { display: flex; gap: 5px; background: white; padding: 8px 20px; border-radius: 40px; }
        .nav-menu a { text-decoration: none; padding: 8px 20px; border-radius: 30px; color: #1E3A8A; font-weight: 600; }
        .nav-menu a:hover { background: #EFF6FF; }
        .nav-menu .active { background: #2563EB; color: white; }
        .logout-btn { background: #EF4444; color: white !important; margin-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #EFF6FF; color: #1E40AF; padding: 15px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #F1F5F9; }
        .status-badge.lunas { background: #D1FAE5; color: #065F46; padding: 5px 12px; border-radius: 20px; }
        details { background: #F8FAFC; padding: 15px; border-radius: 10px; }
        summary { cursor: pointer; color: #2563EB; font-weight: bold; }
        .btn-checkout { background: #10B981; color: white; padding: 6px 12px; border-radius: 20px; text-decoration: none; cursor: pointer; border: none; }
        .kosong { text-align: center; padding: 40px; color: #64748B; }
        
        /* Modal Konfirmasi */
        .modal-confirm { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-confirm-content { background: white; padding: 25px; border-radius: 20px; width: 350px; text-align: center; }
        .modal-confirm-content p { margin: 20px 0; }
        .btn-yes { background: #10B981; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; margin-right: 10px; }
        .btn-no { background: #E2E8F0; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <div class="logo-area">
            <h1>Hotel Khatulistiwa</h1>
            <p>Proses Check-Out</p>
        </div>
        <div class="nav-menu">
            <a href="index.php">Reservasi</a>
            <a href="checkout.php" class="active">Check-Out</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Cari nama pelanggan atau tanggal (YYYY-MM-DD)" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Cari</button>
        <?php if($search): ?>
            <a href="checkout.php">Reset</a>
        <?php endif; ?>
    </form>

    <h3>Daftar Reservasi Lunas - Siap Check-Out</h3>
    <?php if (count($data) == 0): ?>
        <div class="kosong">Tidak ada reservasi yang perlu di-check-out.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Check In/Out</th><th>Detail Kamar</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_Pelanggan']) ?></td>
                <td><?= $row['tanggal_Check_In'] ?> / <?= $row['tanggal_Check_Out'] ?></td>
                <td>
                    <details>
                        <summary>Lihat detail</summary>
                        <div style="font-size: 0.85rem;">
                            <p><strong>Tipe:</strong> <?= htmlspecialchars($row['info_tipe_kamar'] ?? '-') ?></p>
                            <p><strong>Nomor Kamar:</strong> <?= htmlspecialchars($row['list_nomor_kamar'] ?? '-') ?></p>
                            <p><strong>Total:</strong> Rp <?= number_format($row['total_Biaya'], 0, ',', '.') ?></p>
                        </div>
                    </details>
                </td>
                <td>
                    <button class="btn-checkout" onclick="confirmCheckout('<?= $row['id_Reservasi'] ?>')">✅ Check-Out</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Check-Out -->
<div id="confirmModal" class="modal-confirm">
    <div class="modal-confirm-content">
        <h3>Konfirmasi Check-Out</h3>
        <p>Apakah Anda yakin ingin melakukan check-out untuk reservasi ini?</p>
        <button class="btn-no" onclick="closeConfirmModal()">Batal</button>
        <a id="confirmLink" href="#" class="btn-yes" style="text-decoration: none; display: inline-block;">Ya, Check-Out</a>
    </div>
</div>

<script>
    function confirmCheckout(id) {
        document.getElementById('confirmLink').href = '../../process/Resepsionis/proses_checkout.php?id=' + id;
        document.getElementById('confirmModal').style.display = 'flex';
    }
    function closeConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('confirmModal')) {
            closeConfirmModal();
        }
    }
</script>
</body>
</html>
