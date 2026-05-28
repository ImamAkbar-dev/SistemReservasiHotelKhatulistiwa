<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../../config/database.php';

// Ambil semua data pegawai
$stmt = $conn->query("SELECT * FROM pegawai ORDER BY id_Pegawai");
$pegawai = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pegawai - Hotel Khatulistiwa</title>
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
        .btn-hapus { background: #EF4444; color: white; border: none; padding: 4px 12px; border-radius: 20px; cursor: pointer; margin-left: 8px; }
        .btn-edit { background: #F59E0B; color: white; border: none; padding: 4px 12px; border-radius: 20px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #E2E8F0; }
        th { background: #F8FAFC; color: #1E3A8A; }
        tr:hover { background: #F1F5F9; }

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
            width: 450px;
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
        .modal-content input, .modal-content textarea {
            width: 100%;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #CBD5E0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
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
            <a href="kelola_kamar.php">Kelola Kamar</a>
            <a href="kelola_pegawai.php" class="active">Kelola Pegawai</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="btn" onclick="openTambahModal()">+ Tambah Pegawai</button>
    </div>

    <table>
        <thead>
            <tr><th>ID Pegawai</th><th>Nama</th><th>No. HP</th><th>Alamat</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($pegawai as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['id_Pegawai']) ?></td>
                <td><?= htmlspecialchars($p['nama_Pegawai']) ?></td>
                <td><?= htmlspecialchars($p['nomorhp_Pegawai']) ?></td>
                <td><?= htmlspecialchars($p['alamat_Pegawai']) ?></td>
                <td>
                    <button class="btn-edit" onclick="openEditModal('<?= $p['id_Pegawai'] ?>', '<?= htmlspecialchars($p['nama_Pegawai']) ?>', '<?= htmlspecialchars($p['nomorhp_Pegawai']) ?>', '<?= htmlspecialchars($p['alamat_Pegawai']) ?>')">✏️ Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah Pegawai -->
<div id="tambahModal" class="modal">
    <div class="modal-content">
        <h3>Tambah Pegawai Baru</h3>
        <form id="tambahForm" action="../../process/admin/proses_tambah_pegawai.php" method="POST">
            <label>Nama Lengkap:</label>
            <input type="text" name="nama_pegawai" required>
            <label>Nomor HP:</label>
            <input type="text" name="nomorhp" required>
            <label>Alamat:</label>
            <textarea name="alamat" rows="2" required></textarea>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeTambahModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pegawai -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Edit Pegawai</h3>
        <form id="editForm" action="../../process/admin/edit_pegawai.php" method="POST">
            <input type="hidden" name="id_pegawai" id="edit_id_pegawai">
            <label>Nama Lengkap:</label>
            <input type="text" name="nama_pegawai" id="edit_nama" required>
            <label>Nomor HP:</label>
            <input type="text" name="nomorhp" id="edit_nomorhp" required>
            <label>Alamat:</label>
            <textarea name="alamat" id="edit_alamat" rows="2" required></textarea>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-save btn-save-edit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Konfirmasi Hapus (alert biasa, tapi bisa pakai modal juga jika mau) -->
<script>
    function openTambahModal() { document.getElementById('tambahModal').style.display = 'flex'; }
    function closeTambahModal() { document.getElementById('tambahModal').style.display = 'none'; }
    
    function openEditModal(id, nama, nomorhp, alamat) {
        document.getElementById('edit_id_pegawai').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_nomorhp').value = nomorhp;
        document.getElementById('edit_alamat').value = alamat;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
    
    function confirmDelete(id, nama) {
        if (confirm(`Yakin ingin menghapus pegawai ${nama} (${id})?`)) {
            window.location.href = `../../process/admin/hapus_pegawai.php?id=${id}`;
        }
    }
    
    window.onclick = function(event) {
        if (event.target == document.getElementById('tambahModal')) closeTambahModal();
        if (event.target == document.getElementById('editModal')) closeEditModal();
    }
</script>
</body>
</html>