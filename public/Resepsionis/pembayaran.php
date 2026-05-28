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
          WHERE r.status_Pembayaran = 'Belum'";

if (!empty($search)) {
    $query .= " AND (p.nama_Pelanggan LIKE :search OR r.tanggal_Check_In LIKE :search OR r.tanggal_Check_Out LIKE :search)";
    $query .= " ORDER BY r.tanggal_Check_In ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $query .= " ORDER BY r.tanggal_Check_In ASC";
    $stmt = $conn->query($query);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran - Hotel Khatulistiwa</title>
    <style>
        /* style sama seperti sebelumnya */
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
        .status-badge.belum { background: #FFE4E6; color: #9F1239; padding: 5px 12px; border-radius: 20px; }
        .btn-bayar { background: #F59E0B; color: white; padding: 6px 12px; border-radius: 20px; text-decoration: none; }
        details { background: #F8FAFC; padding: 15px; border-radius: 10px; }
        summary { cursor: pointer; color: #2563EB; }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <div class="logo-area">
            <h1>Hotel Khatulistiwa</h1>
            <p>Pencatatan Pembayaran</p>
        </div>
        <div class="nav-menu">
            <a href="index.php">Reservasi</a>
            <a href="checkout.php">Check-Out</a>
            <a href="pembayaran.php" class="active">Pembayaran</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Cari nama pelanggan atau tanggal (YYYY-MM-DD)" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Cari</button>
        <?php if($search): ?>
            <a href="pembayaran.php">Reset</a>
        <?php endif; ?>
    </form>

    <h3>Reservasi dengan status <span style="color:#9F1239;">Belum Lunas</span></h3>
    <?php if(empty($data)): ?>
        <p style="margin-top:20px;">Semua reservasi sudah lunas atau tidak ditemukan.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Check In/Out</th><th>Total Biaya</th><th>Detail Kamar</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_Pelanggan']) ?></td>
                <td><?= $row['tanggal_Check_In'] ?> / <?= $row['tanggal_Check_Out'] ?></td>
                <td>Rp <?= number_format($row['total_Biaya'], 0, ',', '.') ?></td>
                <td>
                    <details>
                        <summary>Lihat detail</summary>
                        <div style="font-size: 0.85rem;">
                            <p><strong>Tipe:</strong> <?= htmlspecialchars($row['info_tipe_kamar'] ?? '-') ?></p>
                            <p><strong>Nomor Kamar:</strong> <?= htmlspecialchars($row['list_nomor_kamar'] ?? '-') ?></p>
                        </div>
                    </details>
                </td>
                <td><a href="../../process/Resepsionis/proses_pembayaran.php?id=<?= $row['id_Reservasi'] ?>" class="btn-bayar" onclick="return confirm('Konfirmasi pembayaran?')">Bayar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>