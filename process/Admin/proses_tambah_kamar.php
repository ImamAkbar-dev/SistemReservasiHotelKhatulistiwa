<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../../public/login.php");
    exit;
}

$id_tipe = $_POST['id_tipe'];
$nomor_kamar = $_POST['nomor_kamar'];
$status = $_POST['status'];

// Generate id_Kamar otomatis (misal KM016, KM017, ...)
$stmt = $conn->query("SELECT id_Kamar FROM kamar ORDER BY id_Kamar DESC LIMIT 1");
$last = $stmt->fetch();
if($last) {
    $num = (int)substr($last['id_Kamar'], 2) + 1;
    $new_id = 'KM' . str_pad($num, 3, '0', STR_PAD_LEFT);
} else {
    $new_id = 'KM001';
}

try {
    $stmt = $conn->prepare("INSERT INTO kamar (id_Kamar, nomor_Kamar, status_Kamar, id_Tipe) VALUES (?, ?, ?, ?)");
    $stmt->execute([$new_id, $nomor_kamar, $status, $id_tipe]);
    header("Location: ../../public/admin/kelola_kamar.php?status=added");
} catch(PDOException $e) {
    die("Gagal menambah kamar: " . $e->getMessage());
}
?>