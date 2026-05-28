<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Tipe Kamar</title>
    <style>/* sederhana */</style>
</head>
<body>
    <h2>Tambah Tipe Kamar Baru</h2>
    <form action="../../process/admin/proses_tambah_tipe.php" method="POST">
        <label>ID Tipe (contoh: TK005): <input type="text" name="id_tipe" required></label><br>
        <label>Nama Tipe: <input type="text" name="nama_tipe" required></label><br>
        <label>Harga per Malam: <input type="number" name="harga" required></label><br>
        <button type="submit">Simpan</button>
        <a href="kelola_kamar.php">Batal</a>
    </form>
</body>
</html>