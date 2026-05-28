<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
$id_tipe = $_GET['id_tipe'] ?? '';
if(!$id_tipe) die("Tipe kamar tidak dipilih.");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Kamar</title>
</head>
<body>
    <h2>Tambah Nomor Kamar Baru</h2>
    <form action="../../process/admin/proses_tambah_kamar.php" method="POST">
        <input type="hidden" name="id_tipe" value="<?= htmlspecialchars($id_tipe) ?>">
        <label>Nomor Kamar: <input type="text" name="nomor_kamar" required></label><br>
        <label>Status Awal: 
            <select name="status">
                <option value="Tersedia">Tersedia</option>
                <option value="Terisi">Terisi (untuk maintenance)</option>
            </select>
        </label><br>
        <button type="submit">Simpan</button>
        <a href="kelola_kamar.php">Batal</a>
    </form>
</body>
</html>