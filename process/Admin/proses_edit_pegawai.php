<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../../public/login.php");
    exit;
}

$id_pegawai = $_POST['id_pegawai'];
$nama = $_POST['nama_pegawai'];
$nomorhp = $_POST['nomorhp'];
$alamat = $_POST['alamat'];

try {
    $stmt = $conn->prepare("UPDATE pegawai SET nama_Pegawai = ?, nomorhp_Pegawai = ?, alamat_Pegawai = ? WHERE id_Pegawai = ?");
    $stmt->execute([$nama, $nomorhp, $alamat, $id_pegawai]);
    header("Location: ../../public/admin/kelola_pegawai.php?status=updated");
} catch(PDOException $e) {
    die("Gagal mengupdate pegawai: " . $e->getMessage());
}
?>