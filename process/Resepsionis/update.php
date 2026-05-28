<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/login.php");
    exit;
}
require_once '../../config/database.php';

// Fungsi untuk redirect dengan pesan error
function redirectError($id, $message) {
    $_SESSION['edit_error'] = $message;
    header("Location: ../../public/Resepsionis/edit.php?id=" . urlencode($id));
    exit;
}

try {
    if(!isset($_POST['id_reservasi'], $_POST['check_in'], $_POST['check_out'], $_POST['tipe_data'], $_POST['selected_rooms'])) {
        throw new Exception("Data form tidak lengkap.");
    }

    $id_reservasi = $_POST['id_reservasi'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $tipeData = json_decode($_POST['tipe_data'], true);
    $selectedRooms = explode(',', $_POST['selected_rooms']);
    $selectedRooms = array_filter($selectedRooms);

    if (empty($tipeData)) throw new Exception("Tidak ada tipe kamar yang dipilih.");
    if (empty($selectedRooms)) throw new Exception("Tidak ada kamar yang dipilih.");

    $date_in = new DateTime($check_in);
    $date_out = new DateTime($check_out);
    $lama_menginap = $date_in->diff($date_out)->days;
    if ($lama_menginap == 0) $lama_menginap = 1;

    $conn->beginTransaction();

    // Ambil daftar kamar lama
    $stmtOldRooms = $conn->prepare("SELECT id_Kamar FROM detail_kamar WHERE id_Reservasi = ?");
    $stmtOldRooms->execute([$id_reservasi]);
    $oldRooms = $stmtOldRooms->fetchAll(PDO::FETCH_COLUMN);

    // Hapus detail_kamar dan detail_reservasi lama
    $conn->prepare("DELETE FROM detail_kamar WHERE id_Reservasi = ?")->execute([$id_reservasi]);
    $conn->prepare("DELETE FROM detail_reservasi WHERE id_Reservasi = ?")->execute([$id_reservasi]);

    // Update status kamar lama menjadi Tersedia
    if (!empty($oldRooms)) {
        $placeholders = implode(',', array_fill(0, count($oldRooms), '?'));
        $stmtFree = $conn->prepare("UPDATE kamar SET status_Kamar = 'Tersedia' WHERE id_Kamar IN ($placeholders)");
        $stmtFree->execute($oldRooms);
    }

    // Insert detail reservasi baru
    $total_biaya = 0;
    $total_kamar = 0;
    $stmtDetail = $conn->prepare("INSERT INTO detail_reservasi (id_Reservasi, id_Tipe, jumlah_Kamar, lama_Menginap, sub_Total) VALUES (?, ?, ?, ?, ?)");
    foreach ($tipeData as $tipe) {
        $stmtHarga = $conn->prepare("SELECT harga_Per_Malam FROM tipekamar WHERE id_Tipe = ?");
        $stmtHarga->execute([$tipe['id_Tipe']]);
        $harga = $stmtHarga->fetchColumn();
        if (!$harga) throw new Exception("Harga tipe kamar tidak ditemukan.");
        $sub_total = $harga * $lama_menginap * $tipe['jumlah_Kamar'];
        $total_biaya += $sub_total;
        $total_kamar += $tipe['jumlah_Kamar'];
        $stmtDetail->execute([$id_reservasi, $tipe['id_Tipe'], $tipe['jumlah_Kamar'], $lama_menginap, $sub_total]);
    }

    // Insert detail_kamar baru dan update status kamar menjadi Terisi
    foreach ($selectedRooms as $roomId) {
        // Cek apakah kamar tersedia (atau sedang dipakai oleh reservasi ini? sudah dihapus sebelumnya)
        $stmtCek = $conn->prepare("SELECT status_Kamar FROM kamar WHERE id_Kamar = ?");
        $stmtCek->execute([$roomId]);
        $status = $stmtCek->fetchColumn();
        if ($status !== 'Tersedia') {
            throw new Exception("Kamar dengan ID $roomId tidak tersedia.");
        }
        $conn->prepare("INSERT INTO detail_kamar (id_Reservasi, id_Kamar) VALUES (?, ?)")->execute([$id_reservasi, $roomId]);
        $conn->prepare("UPDATE kamar SET status_Kamar = 'Terisi' WHERE id_Kamar = ?")->execute([$roomId]);
    }

    // Update reservasi utama
    $stmtUpdate = $conn->prepare("UPDATE reservasi SET tanggal_Check_In = ?, tanggal_Check_Out = ?, total_Biaya = ?, total_Kamar = ? WHERE id_Reservasi = ?");
    $stmtUpdate->execute([$check_in, $check_out, $total_biaya, $total_kamar, $id_reservasi]);

    $conn->commit();
    header("Location: ../../public/Resepsionis/index.php?status=updated");
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    redirectError($id_reservasi ?? $_POST['id_reservasi'] ?? '', $e->getMessage());
}
?>