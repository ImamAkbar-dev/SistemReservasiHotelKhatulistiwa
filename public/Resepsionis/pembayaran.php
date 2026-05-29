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

// Ambil daftar metode pembayaran dari database
$metodeList = $conn->query("SELECT id_Pembayaran, metode_Pembayaran FROM pembayaran")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran - Hotel Khatulistiwa</title>
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
        .status-badge.belum { background: #FFE4E6; color: #9F1239; padding: 5px 12px; border-radius: 20px; }
        .btn-bayar { background: #F59E0B; color: white; padding: 6px 12px; border-radius: 20px; text-decoration: none; cursor: pointer; border: none; }
        details { background: #F8FAFC; padding: 15px; border-radius: 10px; }
        summary { cursor: pointer; color: #2563EB; }
        
        /* Modal style */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 20px; width: 400px; max-width: 90%; }
        .modal-content h3 { margin-bottom: 20px; color: #1E3A8A; }
        .modal-content label { display: block; margin-top: 15px; font-weight: bold; }
        .modal-content select, .modal-content input { width: 100%; padding: 10px; margin-top: 5px; border-radius: 8px; border: 1px solid #CBD5E0; }
        .kembalian { margin-top: 10px; padding: 8px; background: #F1F5F9; border-radius: 8px; color: #059669; font-weight: bold; }
        .modal-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-proses { background: #059669; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
        .btn-batal { background: #E2E8F0; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
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
                <td>
                    <button class="btn-bayar" onclick="openModal('<?= $row['id_Reservasi'] ?>', <?= $row['total_Biaya'] ?>)">Bayar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Modal Pembayaran -->
<div id="bayarModal" class="modal">
    <div class="modal-content">
        <h3>Proses Pembayaran</h3>
        <form id="formPembayaran" action="../../process/Resepsionis/proses_pembayaran.php" method="POST">
            <input type="hidden" name="id_reservasi" id="modal_id_reservasi">
            <input type="hidden" name="total_biaya" id="modal_total_biaya">
            <label>Total Biaya</label>
            <input type="text" id="total_biaya_display" readonly style="background:#F1F5F9;">
            <label>Metode Pembayaran</label>
            <select name="metode" id="metode_pembayaran" required>
                <option value="">-- Pilih --</option>
                <?php foreach($metodeList as $m): ?>
                    <option value="<?= $m['id_Pembayaran'] ?>"><?= $m['metode_Pembayaran'] ?></option>
                <?php endforeach; ?>
            </select>
            <div id="tunaiField" style="display:none;">
                <label>Jumlah Bayar (Rp)</label>
                <input type="number" id="jumlah_bayar" name="jumlah_bayar" step="1000" min="0">
                <div id="kembalian" class="kembalian"></div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn-batal" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-proses">Proses</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentTotal = 0;
    function openModal(id, total) {
        document.getElementById('modal_id_reservasi').value = id;
        document.getElementById('modal_total_biaya').value = total;
        document.getElementById('total_biaya_display').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        currentTotal = total;
        document.getElementById('bayarModal').style.display = 'flex';
        // Reset
        document.getElementById('metode_pembayaran').value = '';
        document.getElementById('tunaiField').style.display = 'none';
        document.getElementById('jumlah_bayar').value = '';
        document.getElementById('kembalian').innerHTML = '';
    }
    function closeModal() {
        document.getElementById('bayarModal').style.display = 'none';
    }
    document.getElementById('metode_pembayaran').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex]?.text || '';
        if (selected.toLowerCase() === 'cash' || selected.toLowerCase() === 'tunai') {
            document.getElementById('tunaiField').style.display = 'block';
        } else {
            document.getElementById('tunaiField').style.display = 'none';
            document.getElementById('jumlah_bayar').removeAttribute('required');
        }
    });
    document.getElementById('jumlah_bayar').addEventListener('input', function() {
        let bayar = parseInt(this.value) || 0;
        let kembalian = bayar - currentTotal;
        let div = document.getElementById('kembalian');
        if (kembalian >= 0) {
            div.innerHTML = 'Kembalian: Rp ' + new Intl.NumberFormat('id-ID').format(kembalian);
            div.style.color = '#059669';
        } else {
            div.innerHTML = 'Jumlah bayar kurang: Rp ' + new Intl.NumberFormat('id-ID').format(-kembalian);
            div.style.color = '#EF4444';
        }
    });
    // Validasi sebelum submit
    document.getElementById('formPembayaran').addEventListener('submit', function(e) {
        let metode = document.getElementById('metode_pembayaran').value;
        if (!metode) {
            alert('Pilih metode pembayaran!');
            e.preventDefault();
            return false;
        }
        let selectedText = document.getElementById('metode_pembayaran').options[document.getElementById('metode_pembayaran').selectedIndex].text;
        if (selectedText.toLowerCase() === 'cash' || selectedText.toLowerCase() === 'tunai') {
            let bayar = parseInt(document.getElementById('jumlah_bayar').value) || 0;
            if (bayar < currentTotal) {
                alert('Jumlah bayar kurang dari total biaya!');
                e.preventDefault();
                return false;
            }
        }
        return true;
    });
    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        if (event.target == document.getElementById('bayarModal')) {
            closeModal();
        }
    }
</script>
</body>
</html>
