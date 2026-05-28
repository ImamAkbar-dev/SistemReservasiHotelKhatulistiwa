<?php
require_once '../../config/database.php';
$id = $_GET['id'] ?? '';

// Ambil data singkat untuk ditampilkan di konfirmasi
$stmt = $conn->prepare("SELECT r.id_Reservasi, p.nama_Pelanggan 
                      FROM reservasi r 
                      JOIN pelanggan p ON r.id_Pelanggan = p.id_Pelanggan 
                      WHERE r.id_Reservasi = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Data tidak ditemukan!");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Hapus</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #F4F4EB; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .confirm-box { background: #fff; padding: 40px; border: 4px solid #000; box-shadow: 10px 10px 0px #ff5252; text-align: center; max-width: 400px; }
        h2 { font-weight: 900; text-transform: uppercase; color: #ff5252; }
        p { font-weight: 700; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 25px; border: 3px solid #000; text-decoration: none; font-weight: 900; box-shadow: 4px 4px 0px #000; transition: 0.2s; text-transform: uppercase; }
        .btn-yes { background: #ff5252; color: #fff; margin-right: 10px; }
        .btn-no { background: #e0e0e0; color: #000; }
        .btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px #000; }
    </style>
</head>
<body>
    <div class="confirm-box">
        <h2>KONFIRMASI HAPUS</h2>
        <p>Apakah Anda yakin ingin menghapus reservasi <b><?= htmlspecialchars($row['id_Reservasi']) ?></b> atas nama <b><?= htmlspecialchars($row['nama_Pelanggan']) ?></b>?</p>
        
        <a href="../../process/Resepsionis/delete.php?id=<?= $id ?>" class="btn btn-yes">YA, HAPUS</a>
        <a href="index.php" class="btn btn-no">BATAL</a>
    </div>
</body>
</html>