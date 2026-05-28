<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../../public/login.php");
    exit;
}

$id_tipe = $_POST['id_tipe'];
$nama_tipe = $_POST['nama_tipe'];
$harga = $_POST['harga'];

try {
    $stmt = $conn->prepare("UPDATE tipekamar SET nama_tipe = ?, harga_Per_Malam = ? WHERE id_Tipe = ?");
    $stmt->execute([$nama_tipe, $harga, $id_tipe]);
    header("Location: ../../public/admin/kelola_kamar.php?status=updated");
} catch(PDOException $e) {
    die("Gagal mengupdate tipe kamar: " . $e->getMessage());
}
?>