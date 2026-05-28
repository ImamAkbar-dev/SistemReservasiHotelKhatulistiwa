<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: ../../public/login.php");
    exit;
}
// Resepsionis saja yang boleh akses halaman reservasi
if($_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../../public/admin/laporan.php");
    exit;
}

require_once '../../config/database.php';

try {
    $conn->beginTransaction();

    // -- BAGIAN A: HITUNG LAMA MENGINAP DAN TOTAL BIAYA --
    $check_in = new DateTime($_POST['check_in']);
    $check_out = new DateTime($_POST['check_out']);
    $lama_menginap = $check_in->diff($check_out)->days;
    if ($lama_menginap == 0) $lama_menginap = 1; 

    $total_biaya_all = 0;
    $detail_data = [];

    foreach ($_POST['id_tipe'] as $index => $tipeId) {
        $jumlah = $_POST['jumlah_kamar'][$index];
        $stmtHarga = $conn->prepare("SELECT harga_Per_Malam FROM tipekamar WHERE id_Tipe = ?");
        $stmtHarga->execute([$tipeId]);
        $harga_per_malam = $stmtHarga->fetchColumn();
        
        $sub_total = $harga_per_malam * $lama_menginap * $jumlah;
        $total_biaya_all += $sub_total;
        
        $detail_data[] = [
            'id_tipe' => $tipeId,
            'jumlah' => $jumlah,
            'sub_total' => $sub_total
        ];
    }

    // -- BAGIAN B: GENERATE ID RESERVASI OTOMATIS --
    // Ambil ID reservasi terakhir (format RVxxx)
    $stmtLast = $conn->query("SELECT id_Reservasi FROM reservasi ORDER BY id_Reservasi DESC LIMIT 1");
    $last = $stmtLast->fetch();
    if ($last) {
        $num = (int)substr($last['id_Reservasi'], 2) + 1;
        $new_id = 'RV' . str_pad($num, 3, '0', STR_PAD_LEFT);
    } else {
        $new_id = 'RV001';
    }

    // -- BAGIAN C: SIMPAN DATA KE DATABASE --
    //1.-- BAGIAN SIMPAN PELANGGAN (Cek duplikat email) --
    $email = $_POST['Email'];
    $nama = $_POST['nama_pelanggan'];
    $nomorhp = $_POST['nomorhp_Pelanggan'];

    // Cek apakah email sudah terdaftar
    $stmtCek = $conn->prepare("SELECT id_Pelanggan FROM pelanggan WHERE Email = ?");
    $stmtCek->execute([$email]);
    $existing = $stmtCek->fetch();

    if ($existing) {
        // Email sudah ada, gunakan id_pelanggan yang sudah ada
        $id_pelanggan = $existing['id_Pelanggan'];
        // Opsional: update nama & nomor hp jika berbeda (silahkan sesuaikan)
        $stmtUpdate = $conn->prepare("UPDATE pelanggan SET nama_Pelanggan = ?, nomorhp_Pelanggan = ? WHERE id_Pelanggan = ?");
        $stmtUpdate->execute([$nama, $nomorhp, $id_pelanggan]);
    } else {
        // Email baru, insert pelanggan baru
        $stmtP = $conn->prepare("INSERT INTO pelanggan (nama_Pelanggan, Email, nomorhp_Pelanggan) VALUES (?, ?, ?)");
        $stmtP->execute([$nama, $email, $nomorhp]);
        $id_pelanggan = $conn->lastInsertId();
    }

    // 2. Simpan Reservasi Utama dengan ID otomatis
    $total_kamar_all = array_sum($_POST['jumlah_kamar']);
    $stmtR = $conn->prepare("INSERT INTO reservasi (id_Reservasi, tanggal_Check_In, tanggal_Check_Out, id_Pelanggan, id_Pegawai, id_Pembayaran, status_Pembayaran, total_Kamar, total_Biaya) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmtR->execute([
        $new_id, 
        $_POST['check_in'], 
        $_POST['check_out'], 
        $id_pelanggan, 
        $_POST['id_Pegawai'],
        $_POST['id_pembayaran'], 
        $_POST['status'], 
        $total_kamar_all, 
        $total_biaya_all
    ]);

    // 3. Simpan Detail Tipe Kamar
    $stmtD = $conn->prepare("INSERT INTO detail_reservasi (id_Reservasi, id_Tipe, jumlah_Kamar, lama_Menginap, sub_Total) VALUES (?,?,?,?,?)");
    foreach ($detail_data as $d) {
        $stmtD->execute([$new_id, $d['id_tipe'], $d['jumlah'], $lama_menginap, $d['sub_total']]);
    }

    // 4. Simpan Detail Kamar Fisik & Update Status Kamar
    $rooms = explode(',', $_POST['selected_rooms']);
    foreach ($rooms as $roomId) {
        if (!empty($roomId)) {
            $conn->prepare("INSERT INTO detail_kamar (id_Reservasi, id_Kamar) VALUES (?,?)")->execute([$new_id, $roomId]);
            $conn->prepare("UPDATE kamar SET status_Kamar = 'Terisi' WHERE id_Kamar = ?")->execute([$roomId]);
        }
    }

    $conn->commit();
    header("Location: ../../public/Resepsionis/index.php?status=sukses");
} catch (Exception $e) {
    $conn->rollBack();
    die("Gagal memproses data: " . $e->getMessage());
}
?>