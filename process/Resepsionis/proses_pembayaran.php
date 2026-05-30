<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/login.php");
    exit;
}
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reservasi = $_POST['id_reservasi'] ?? '';
    $metode_id    = $_POST['metode'] ?? '';          // id_Pembayaran dari dropdown
    $jumlah_bayar = $_POST['jumlah_bayar'] ?? 0;

    if (!$id_reservasi || !$metode_id) {
        header("Location: ../../public/Resepsionis/pembayaran.php?error=Data tidak lengkap");
        exit;
    }

    try {
        // Update status pembayaran menjadi Lunas dan simpan metode pembayaran
        $stmt = $conn->prepare("UPDATE reservasi SET status_Pembayaran = 'Lunas', id_Pembayaran = ? WHERE id_Reservasi = ?");
        $stmt->execute([$metode_id, $id_reservasi]);

        // (Opsional) Jika ingin menyimpan jumlah bayar untuk metode tunai, bisa ditambahkan di tabel terpisah
        // Misalnya buat tabel `pembayaran_detail` atau kolom `jumlah_bayar` di `reservasi`

        header("Location: ../../public/Resepsionis/pembayaran.php?status=success");
        exit;
    } catch (PDOException $e) {
        die("Gagal memproses pembayaran: " . $e->getMessage());
    }
} else {
    // Jika diakses tanpa POST (misal langsung via URL), redirect ke halaman pembayaran
    header("Location: ../../public/Resepsionis/pembayaran.php");
    exit;
}
?>
