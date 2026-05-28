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

// Generate id_Kamar otomatis (misal KM016, KM017, ...)
$stmt = $conn->query("SELECT id_Pegawai FROM pegawai ORDER BY id_Pegawai DESC LIMIT 1");
$last = $stmt->fetch();
if($last) {
    $num = (int)substr($last['id_Pegawai'], 2) + 1;
    $new_id = 'PG' . str_pad($num, 3, '0', STR_PAD_LEFT);
} else {
    $new_id = 'PG001';
}

try {
    $stmt = $conn->prepare("INSERT INTO pegawai (id_Pegawai, nama_Pegawai, nomorhp_Pegawai, alamat_Pegawai) VALUES (?, ?, ?, ?)");
    $stmt->execute([$new_id, $nama, $nomorhp, $alamat]);
    header("Location: ../../public/admin/kelola_pegawai.php?status=added");
} catch(PDOException $e) {
    die("Gagal menambah pegawai: " . $e->getMessage());
}
?>