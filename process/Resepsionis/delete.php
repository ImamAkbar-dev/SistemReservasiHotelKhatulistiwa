<?php
require_once '../../config/database.php';
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/login.php");
    exit;
}

try {
    $conn->beginTransaction();
    $id = $_GET['id'];

    // 1. Ambil ID Kamar untuk mengubah status kamar kembali jadi 'Tersedia'
    $stmtKamar = $conn->prepare("SELECT id_Kamar FROM detail_kamar WHERE id_Reservasi = ?");
    $stmtKamar->execute([$id]);
    $rooms = $stmtKamar->fetchAll(PDO::FETCH_COLUMN);

    if ($rooms) {
        $placeholders = implode(',', array_fill(0, count($rooms), '?'));
        $stmtUpdateKamar = $conn->prepare("UPDATE kamar SET status_Kamar = 'Tersedia' WHERE id_Kamar IN ($placeholders)");
        $stmtUpdateKamar->execute($rooms);
    }

    // 2. Hapus data di tabel-tabel detail (anak)
    $conn->prepare("DELETE FROM detail_kamar WHERE id_Reservasi = ?")->execute([$id]);
    $conn->prepare("DELETE FROM detail_reservasi WHERE id_Reservasi = ?")->execute([$id]);

    // 3. Hapus data di tabel reservasi (induk)
    $stmt = $conn->prepare("DELETE FROM reservasi WHERE id_Reservasi = ?");
    $stmt->execute([$id]);

    // 4. (Dihapus) Tidak menghapus pelanggan agar data histori tetap ada
    // Jika memang ingin hapus pelanggan, harus cek dulu apakah pelanggan punya reservasi lain

    $conn->commit();
    header("Location: ../../public/Resepsionis/index.php?status=deleted");
} catch (Exception $e) {
    $conn->rollBack();
    die("Gagal hapus data: " . $e->getMessage());
}
?>