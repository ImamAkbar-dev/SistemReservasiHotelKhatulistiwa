<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/login.php");
    exit;
}
require_once '../../config/database.php';

$id = $_GET['id'] ?? '';
if(!$id) {
    header("Location: ../../public/Resepsionis/pembayaran.php");
    exit;
}

try {
    // Update status pembayaran menjadi Lunas
    $stmt = $conn->prepare("UPDATE reservasi SET status_Pembayaran = 'Lunas' WHERE id_Reservasi = ?");
    $stmt->execute([$id]);
    header("Location: ../../public/Resepsionis/pembayaran.php?status=success");
} catch(PDOException $e) {
    die("Gagal memproses pembayaran: " . $e->getMessage());
}
?>