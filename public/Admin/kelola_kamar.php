<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../../config/database.php';

// Ambil semua tipe kamar
$tipe = $conn->query("SELECT * FROM tipekamar ORDER BY id_Tipe")->fetchAll();

// Ambil semua kamar per tipe
$kamarPerTipe = [];
foreach($tipe as $t) {
    $stmt = $conn->prepare("SELECT * FROM kamar WHERE id_Tipe = ? ORDER BY nomor_Kamar");
    $stmt->execute([$t['id_Tipe']]);
    $kamarPerTipe[$t['id_Tipe']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kamar - Hotel Khatulistiwa</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #F0F7FF; padding: 30px; color: #1E293B; }
        .container { max-width: 1200px; margin: 0 auto; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .logo-area h1 { font-size: 1.8rem; color: #1E3A8A; border-left: 5px solid #2563EB; padding-left: 15px; }
        .logo-area p { color: #475569; margin-top: 5px; font-size: 0.9rem; }
        .nav-menu { display: flex; gap: 5px; background: white; padding: 8px 20px; border-radius: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .nav-menu a { text-decoration: none; padding: 8px 20px; border-radius: 30px; color: #1E3A8A; font-weight: 600; transition: 0.2s; }
        .nav-menu a:hover { background: #EFF6FF; }
        .nav-menu .active { background: #2563EB; color: white; }
        .logout-btn { background: #EF4444; color: white !important; margin-left: 10px; }
        .logout-btn:hover { background: #DC2626 !important; }
        .btn { background: #2563EB; color: white; padding: 8px 16px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 5px 0; border: none; cursor: pointer; }
        .btn-small { font-size: 0.8em; padding: 4px 12px; background: #10B981; }
        .tipe-card { background: white; border: 1px solid #E2E8F0; margin: 20px 0; padding: 20px; border-radius: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .tipe-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
        .kamar-list { margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px; }
        .badge-kamar { background: #F1F5F9; padding: 6px 14px; border-radius: 30px; font-size: 0.85rem; border: 1px solid #E2E8F0; }
        .edit-tipe-btn { background: #F59E0B; color: white; border: none; padding: 6px 12px; border-radius: 20px; cursor: pointer; font-weight: bold; margin-left: 10px; }
        
        /* Modal Pop-up */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 24px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 20px 30px rgba(0,0,0,0.2);
            animation: fadeIn 0.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-content h3 {
            color: #1E3A8A;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        .modal-content label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #475569;
        }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #CBD5E0;
            border-radius: 12px;
            font-size: 1rem;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }
        .btn-cancel {
            background: #E2E8F0;
            color: #1E293B;
            padding: 8px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-save {
            background: #059669;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-save-edit {
            background: #059669;
        }
        .btn-save:hover { background: #047450; }
        .btn-save-edit:hover { background: #047450; }
        .btn-cancel:hover { background: #CBD5E0; }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <div class="logo-area">
            <h1>Hotel Khatulistiwa</h1>
            <p>Dashboard Administrator</p>
        </div>
        <div class="nav-menu">
            <a href="laporan.php">Laporan</a>
            <a href="kelola_kamar.php" class="active">Kelola Kamar</a>
            <a href="kelola_pegawai.php">Kelola Pegawai</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="btn" onclick="openTambahModal()">+ Tambah Tipe Kamar Baru</button>
    </div>

    <?php foreach($tipe as $t): ?>
    <div class="tipe-card">
        <div class="tipe-header">
            <div>
                <strong><?= htmlspecialchars($t['nama_tipe']) ?></strong> (ID: <?= $t['id_Tipe'] ?>) - 
                Rp <?= number_format($t['harga_Per_Malam'], 0, ',', '.') ?> / malam
                <button class="edit-tipe-btn" onclick="openEditModal('<?= $t['id_Tipe'] ?>', '<?= htmlspecialchars($t['nama_tipe']) ?>', '<?= $t['harga_Per_Malam'] ?>')">✏️ Edit</button>
            </div>
            <button class="btn btn-small" onclick="openTambahKamarModal('<?= $t['id_Tipe'] ?>')">+ Tambah Nomor Kamar</button>
        </div>
        <div class="kamar-list">
            <?php if(empty($kamarPerTipe[$t['id_Tipe']])): ?>
                <span class="badge-kamar">Belum ada kamar</span>
            <?php else: ?>
                <?php foreach($kamarPerTipe[$t['id_Tipe']] as $kamar): ?>
                    <span class="badge-kamar"><?= $kamar['nomor_Kamar'] ?> (<?= $kamar['status_Kamar'] ?>)</span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Tambah Tipe Kamar -->
<div id="tambahModal" class="modal">
    <div class="modal-content">
        <h3>Tambah Tipe Kamar Baru</h3>
        <form id="tambahForm" action="../../process/admin/proses_tambah_tipe.php" method="POST">
            <label>Nama Tipe:</label>
            <input type="text" name="nama_tipe" required>
            <label>Harga per Malam (Rp):</label>
            <input type="number" name="harga" required>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeTambahModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Tipe Kamar -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Tipe Kamar</h3>
        <form id="editForm" action="../../process/admin/edit_tipe.php" method="POST">
            <input type="hidden" name="id_tipe" id="edit_id_tipe">
            <label>Nama Tipe:</label>
            <input type="text" name="nama_tipe" id="edit_nama_tipe" required>
            <label>Harga per Malam (Rp):</label>
            <input type="number" name="harga" id="edit_harga" required>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-save btn-save-edit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Nomor Kamar -->
<div id="tambahKamarModal" class="modal">
    <div class="modal-content">
        <h3>Tambah Nomor Kamar</h3>
        <form id="tambahKamarForm" action="../../process/admin/proses_tambah_kamar.php" method="POST">
            <input type="hidden" name="id_tipe" id="kamar_id_tipe">
            <label>Nomor Kamar:</label>
            <input type="text" name="nomor_kamar" required>
            <label>Status Awal:</label>
            <select name="status">
                <option value="Tersedia">Tersedia</option>
                <option value="Terisi">Terisi</option>
            </select>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeTambahKamarModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal Tambah Tipe
    function openTambahModal() {
        document.getElementById('tambahModal').style.display = 'flex';
    }
    function closeTambahModal() {
        document.getElementById('tambahModal').style.display = 'none';
    }
    // Modal Edit Tipe
    function openEditModal(id, nama, harga) {
        document.getElementById('edit_id_tipe').value = id;
        document.getElementById('edit_nama_tipe').value = nama;
        document.getElementById('edit_harga').value = harga;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    // Modal Tambah Kamar
    function openTambahKamarModal(id_tipe) {
        document.getElementById('kamar_id_tipe').value = id_tipe;
        document.getElementById('tambahKamarModal').style.display = 'flex';
    }
    function closeTambahKamarModal() {
        document.getElementById('tambahKamarModal').style.display = 'none';
    }
    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        const tambahModal = document.getElementById('tambahModal');
        const editModal = document.getElementById('editModal');
        const kamarModal = document.getElementById('tambahKamarModal');
        if (event.target == tambahModal) closeTambahModal();
        if (event.target == editModal) closeEditModal();
        if (event.target == kamarModal) closeTambahKamarModal();
    }
</script>
</body>
</html>