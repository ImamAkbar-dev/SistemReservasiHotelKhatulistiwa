<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../public/login.php");
    exit;
}
require_once '../../config/database.php';

// Ambil keyword pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query dasar dengan subquery untuk info tipe dan nomor kamar
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
          JOIN pembayaran pb ON r.id_Pembayaran = pb.id_Pembayaran";

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $query .= " WHERE p.nama_Pelanggan LIKE :search 
                OR r.tanggal_Check_In LIKE :search 
                OR r.tanggal_Check_Out LIKE :search";
    $query .= " ORDER BY r.id_Reservasi DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $query .= " ORDER BY r.id_Reservasi DESC";
    $stmt = $conn->query($query);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SI Reservasi Hotel Khatulistiwa - Resepsionis</title>
    <style>
        /* style sama seperti sebelumnya, tambahan untuk form pencarian */
        .search-form { margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #F8FAFC; padding: 15px; border-radius: 12px; }
        .search-form input[type="text"] { flex: 1; padding: 10px; border: 1px solid #CBD5E0; border-radius: 8px; font-size: 1rem; }
        .search-form button { background: #2563EB; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        .search-form a { background: #E2E8F0; color: #1E293B; padding: 10px 20px; border-radius: 8px; text-decoration: none; }
        /* sisanya seperti style awal */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #F0F7FF; padding: 30px; color: #1E293B; }
        .container { max-width: 1200px; margin: 0 auto; background: #FFF; padding: 25px; border-radius: 15px; box-shadow: 0 10px 25px rgba(30, 58, 138, 0.1); border-top: 8px solid #2563EB; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .logo-area h1 { font-size: 1.8rem; color: #1E3A8A; border-left: 5px solid #2563EB; padding-left: 15px; }
        .logo-area p { color: #475569; margin-top: 5px; font-size: 0.9rem; }
        .nav-menu { display: flex; gap: 5px; background: white; padding: 8px 20px; border-radius: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .nav-menu a { text-decoration: none; padding: 8px 20px; border-radius: 30px; color: #1E3A8A; font-weight: 600; }
        .nav-menu a:hover { background: #EFF6FF; }
        .nav-menu .active { background: #2563EB; color: white; }
        .logout-btn { background: #EF4444; color: white !important; margin-left: 10px; }
        .btn-add { background: #2563EB; color: white; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; display: inline-block; margin-bottom: 25px; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #EFF6FF; color: #1E40AF; padding: 15px; text-align: left; font-size: 0.8rem; border-bottom: 2px solid #DBEAFE; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #F1F5F9; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
        .lunas { background: #D1FAE5; color: #065F46; }
        .belum { background: #FFE4E6; color: #9F1239; }
        details { background: #F8FAFC; padding: 15px; border-radius: 10px; border: 1px solid #E2E8F0; margin-top: 5px; }
        summary { cursor: pointer; color: #2563EB; font-weight: bold; font-size: 0.85rem; }
        .btn-edit { color: #059669; text-decoration: none; font-weight: bold; margin-right: 15px; }
        .btn-delete { color: #EF4444; text-decoration: none; font-weight: bold; cursor: pointer; }
        #customModal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; width: 350px; margin: 15% auto; padding: 25px; border-radius: 12px; text-align: center; }
        .modal-btns { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
        .btn-yes { background: #DC2626; color: white; border: 2px solid #DC2626; padding: 10px 30px; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration:none; display: inline-block; }
        .btn-no { background: white; color: #DC2626; border: 2px solid #DC2626; padding: 10px 30px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <div class="logo-area">
            <h1>Hotel Khatulistiwa</h1>
            <p>Dashboard Resepsionis</p>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="active">Reservasi</a>
            <a href="checkout.php">Check-Out</a>
            <a href="pembayaran.php">Pembayaran</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <a href="tambah.php" class="btn-add">+ Reservasi Baru</a>
        <!-- Form Pencarian -->
        <form method="GET" class="search-form" style="margin-bottom:0;">
            <input type="text" name="search" placeholder="Cari nama pelanggan atau tanggal (YYYY-MM-DD)" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari</button>
            <?php if($search): ?>
                <a href="index.php">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Check In/Out</th><th>Status</th><th>Informasi Detail</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['nama_Pelanggan']) ?></strong></td>
                <td><?= $row['tanggal_Check_In'] ?> / <?= $row['tanggal_Check_Out'] ?></td>
                <td><span class="status-badge <?= ($row['status_Pembayaran'] == 'Lunas') ? 'lunas' : 'belum' ?>"><?= $row['status_Pembayaran'] ?></span></td>
                <td>
                    <details>
                        <summary>Details...</summary>
                        <div style="font-size: 0.85rem; padding-top:12px;">
                            <p><strong>Tipe Dipesan:</strong> <?= htmlspecialchars($row['info_tipe_kamar'] ?? '-') ?></p>
                            <p><strong>Nomor Kamar:</strong> <?= htmlspecialchars($row['list_nomor_kamar'] ?? '-') ?></p>
                            <p><strong>Pegawai:</strong> <?= htmlspecialchars($row['nama_Pegawai']) ?></p>
                            <p><strong>Metode Pembayaran:</strong> <?= htmlspecialchars($row['metode_Pembayaran']) ?></p>
                            <p><strong>Total Biaya:</strong> Rp <?= number_format($row['total_Biaya'], 0, ',', '.') ?></p>
                        </div>
                    </details>
                </td>
                <td>
                    <a href="edit.php?id=<?= $row['id_Reservasi'] ?>" class="btn-edit">Ubah</a>
                    <span class="btn-delete" onclick="showModal('<?= $row['id_Reservasi'] ?>')">Hapus</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if(empty($data)): ?>
        <p style="text-align:center; padding:20px;">Tidak ada data ditemukan.</p>
    <?php endif; ?>
</div>

<div id="customModal">
    <div class="modal-content">
        <h3>Konfirmasi Hapus</h3>
        <p>Apakah Anda yakin menghapus data reservasi ini?</p>
        <div class="modal-btns">
            <button class="btn-no" onclick="closeModal()">TIDAK</button>
            <a id="confirmDelete" href="#" class="btn-yes">YA</a>
        </div>
    </div>
</div>

<script>
    function showModal(id) {
        document.getElementById('customModal').style.display = 'block';
        document.getElementById('confirmDelete').href = '../../process/Resepsionis/delete.php?id=' + id;
    }
    function closeModal() {
        document.getElementById('customModal').style.display = 'none';
    }
</script>
</body>
</html>