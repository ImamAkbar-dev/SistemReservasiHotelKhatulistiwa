<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/login.php");
    exit;
}
require_once '../../config/database.php';

$id = $_GET['id'] ?? '';
if(!$id) {
    header("Location: ../../public/Resepsionis/checkout.php");
    exit;
}

try {
    $conn->beginTransaction();
    // Update status pembayaran menjadi Lunas (jika belum)
    $stmt = $conn->prepare("UPDATE reservasi SET status_Pembayaran = 'Lunas' WHERE id_Reservasi = ?");
    $stmt->execute([$id]);
    // Ambil semua kamar dari detail_kamar
    $stmtKamar = $conn->prepare("SELECT id_Kamar FROM detail_kamar WHERE id_Reservasi = ?");
    $stmtKamar->execute([$id]);
    $rooms = $stmtKamar->fetchAll(PDO::FETCH_COLUMN);
    foreach($rooms as $roomId) {
        $conn->prepare("UPDATE kamar SET status_Kamar = 'Tersedia' WHERE id_Kamar = ?")->execute([$roomId]);
    }
    $conn->commit();
    header("Location: ../../public/Resepsionis/checkout.php?status=success");
} catch(Exception $e) {
    $conn->rollBack();
    die("Gagal check-out: " . $e->getMessage());
}
?>