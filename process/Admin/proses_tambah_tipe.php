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

// Generate id_Tipe otomatis
$stmt = $conn->query("SELECT id_Tipe FROM tipekamar ORDER BY id_Tipe DESC LIMIT 1");
$last = $stmt->fetch();
if($last) {
    $num = (int)substr($last['id_Tipe'], 2) + 1;
    $new_id = 'TK' . str_pad($num, 3, '0', STR_PAD_LEFT);
} else {
    $new_id = 'TK001';
}

try {
    $stmt = $conn->prepare("INSERT INTO tipekamar (id_Tipe, nama_tipe, harga_Per_Malam) VALUES (?, ?, ?)");
    $stmt->execute([$new_id, $nama_tipe, $harga]);
    header("Location: ../../public/admin/kelola_kamar.php?status=added");
} catch(PDOException $e) {
    die("Gagal menambah tipe: " . $e->getMessage());
}
?>