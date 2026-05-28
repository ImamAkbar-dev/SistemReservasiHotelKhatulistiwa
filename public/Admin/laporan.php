<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../../config/database.php';

// Ambil periode dari GET
$periode_awal = isset($_GET['periode_awal']) && $_GET['periode_awal'] !== '' ? $_GET['periode_awal'] : null;
$periode_akhir = isset($_GET['periode_akhir']) && $_GET['periode_akhir'] !== '' ? $_GET['periode_akhir'] : null;

// Ambil granularitas (hari, bulan, tahun)
$granularitas = isset($_GET['granularitas']) ? $_GET['granularitas'] : 'bulan';

// Jika periode kosong, ambil min dan max dari database
if (!$periode_awal || !$periode_akhir) {
    $range = $conn->query("SELECT MIN(tanggal_Check_In) as min_tgl, MAX(tanggal_Check_In) as max_tgl FROM reservasi")->fetch();
    $periode_awal = $periode_awal ?: ($range['min_tgl'] ?? date('Y-m-01'));
    $periode_akhir = $periode_akhir ?: ($range['max_tgl'] ?? date('Y-m-t'));
}

// Query total keseluruhan untuk kartu statistik
$sqlStats = "SELECT 
                COUNT(*) AS total_reservasi,
                COALESCE(SUM(total_Kamar), 0) AS total_kamar_dipesan,
                COALESCE(SUM(total_Biaya), 0) AS total_pendapatan
            FROM reservasi
            WHERE tanggal_Check_In BETWEEN :awal AND :akhir";
$stmt = $conn->prepare($sqlStats);
$stmt->execute([':awal' => $periode_awal, ':akhir' => $periode_akhir]);
$data = $stmt->fetch();

$total_reservasi = number_format($data['total_reservasi']);
$total_kamar = number_format($data['total_kamar_dipesan']);
$total_pendapatan = 'Rp ' . number_format($data['total_pendapatan'], 0, ',', '.');

// Query grafik berdasarkan granularitas
$labels = [];
$values = [];

if ($granularitas == 'hari') {
    $sqlChart = "SELECT 
                    tanggal_Check_In as tgl,
                    SUM(total_Biaya) as pendapatan
                FROM reservasi
                WHERE tanggal_Check_In BETWEEN :awal AND :akhir
                GROUP BY tanggal_Check_In
                ORDER BY tanggal_Check_In ASC";
    $stmtChart = $conn->prepare($sqlChart);
    $stmtChart->execute([':awal' => $periode_awal, ':akhir' => $periode_akhir]);
    $chartData = $stmtChart->fetchAll();
    foreach ($chartData as $row) {
        $labels[] = date('d M Y', strtotime($row['tgl']));
        $values[] = $row['pendapatan'];
    }
} elseif ($granularitas == 'tahun') {
    $sqlChart = "SELECT 
                    YEAR(tanggal_Check_In) as tahun,
                    SUM(total_Biaya) as pendapatan
                FROM reservasi
                WHERE tanggal_Check_In BETWEEN :awal AND :akhir
                GROUP BY YEAR(tanggal_Check_In)
                ORDER BY tahun ASC";
    $stmtChart = $conn->prepare($sqlChart);
    $stmtChart->execute([':awal' => $periode_awal, ':akhir' => $periode_akhir]);
    $chartData = $stmtChart->fetchAll();
    foreach ($chartData as $row) {
        $labels[] = $row['tahun'];
        $values[] = $row['pendapatan'];
    }
} else { // bulan (default)
    $sqlChart = "SELECT 
                    DATE_FORMAT(tanggal_Check_In, '%Y-%m') as bulan,
                    SUM(total_Biaya) as pendapatan
                FROM reservasi
                WHERE tanggal_Check_In BETWEEN :awal AND :akhir
                GROUP BY DATE_FORMAT(tanggal_Check_In, '%Y-%m')
                ORDER BY MIN(tanggal_Check_In) ASC";
    $stmtChart = $conn->prepare($sqlChart);
    $stmtChart->execute([':awal' => $periode_awal, ':akhir' => $periode_akhir]);
    $chartData = $stmtChart->fetchAll();
    foreach ($chartData as $row) {
        $date = DateTime::createFromFormat('Y-m', $row['bulan']);
        $labels[] = $date->format('M Y');
        $values[] = $row['pendapatan'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Hotel Khatulistiwa</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #F0F7FF; padding: 30px; color: #1E293B; }
        .container { max-width: 1200px; margin: 0 auto; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .logo-area h1 { font-size: 1.8rem; color: #1E3A8A; border-left: 5px solid #2563EB; padding-left: 15px; }
        .logo-area p { color: #475569; margin-top: 5px; font-size: 0.9rem; }
        .nav-menu { display: flex; gap: 5px; background: white; padding: 8px 20px; border-radius: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .nav-menu a { text-decoration: none; padding: 8px 20px; border-radius: 30px; color: #1E3A8A; font-weight: 600; transition: 0.2s; }
        .nav-menu a:hover { background: #EFF6FF; }
        .nav-menu .active { background: #2563EB; color: white; }
        .logout-btn { background: #EF4444; color: white !important; margin-left: 10px; }
        .logout-btn:hover { background: #DC2626 !important; }
        .filter-card { background: white; border-radius: 20px; padding: 20px 25px; margin-bottom: 35px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; }
        .filter-group { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .filter-inputs { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-inputs label { font-weight: 600; color: #475569; margin-right: 5px; }
        .filter-inputs input, .filter-inputs select { padding: 10px 12px; border: 1px solid #CBD5E0; border-radius: 12px; font-family: inherit; background: white; }
        .btn-filter { background: #2563EB; color: white; border: none; padding: 10px 24px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-filter:hover { background: #1E40AF; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; border-radius: 24px; padding: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); transition: transform 0.2s; border-top: 6px solid; }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card h3 { font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; color: #64748B; }
        .stat-number { font-size: 2.8rem; font-weight: 800; margin-bottom: 10px; }
        .stat-desc { font-size: 0.8rem; color: #94A3B8; }
        .card-reservasi { border-top-color: #3B82F6; }
        .card-pendapatan { border-top-color: #10B981; }
        .card-kamar { border-top-color: #F59E0B; }
        .card-reservasi .stat-number { color: #3B82F6; }
        .card-pendapatan .stat-number { color: #10B981; }
        .card-kamar .stat-number { color: #F59E0B; }
        .chart-card { background: white; border-radius: 24px; padding: 25px; margin-top: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .chart-card h3 { margin-bottom: 20px; color: #1E3A8A; font-weight: 600; }
        canvas { max-height: 400px; width: 100%; }
        footer { text-align: center; margin-top: 40px; color: #94A3B8; font-size: 0.8rem; }
        @media (max-width: 640px) { body { padding: 20px; } .stat-number { font-size: 2rem; } }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <div class="logo-area">
            <h1>Hotel Khatulistiwa</h1>
            <p>Dashboard Administrator</p>
        </div>
        <div class="nav-menu">
            <a href="laporan.php" class="active">Laporan</a>
            <a href="kelola_kamar.php">Kelola Kamar</a>
            <a href="kelola_pegawai.php">Kelola Pegawai</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" class="filter-group">
            <div class="filter-inputs">
                <div>
                    <label>📅 Dari</label>
                    <input type="date" name="periode_awal" value="<?= htmlspecialchars($periode_awal) ?>">
                </div>
                <div>
                    <label>📅 Sampai</label>
                    <input type="date" name="periode_akhir" value="<?= htmlspecialchars($periode_akhir) ?>">
                </div>
                <div>
                    <label>📊 Grafik per</label>
                    <select name="granularitas">
                        <option value="hari" <?= $granularitas == 'hari' ? 'selected' : '' ?>>Hari</option>
                        <option value="bulan" <?= $granularitas == 'bulan' ? 'selected' : '' ?>>Bulan</option>
                        <option value="tahun" <?= $granularitas == 'tahun' ? 'selected' : '' ?>>Tahun</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-filter">Tampilkan</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card card-reservasi">
            <h3>Total Reservasi</h3>
            <div class="stat-number"><?= $total_reservasi ?></div>
            <div class="stat-desc">periode <?= $periode_awal ?> s/d <?= $periode_akhir ?></div>
        </div>
        <div class="stat-card card-pendapatan">
            <h3>Total Pendapatan</h3>
            <div class="stat-number"><?= $total_pendapatan ?></div>
            <div class="stat-desc">dari reservasi selesai</div>
        </div>
        <div class="stat-card card-kamar">
            <h3>Total Kamar Dipesan</h3>
            <div class="stat-number"><?= $total_kamar ?></div>
            <div class="stat-desc">akumulasi kamar</div>
        </div>
    </div>

    <!-- Grafik Pendapatan -->
    <div class="chart-card">
        <h3>📈 Pendapatan per <?= ucfirst($granularitas) ?> (Rp)</h3>
        <canvas id="revenueChart"></canvas>
    </div>

    <footer>
        Sistem Informasi Reservasi Hotel Khatulistiwa &copy; 2026
    </footer>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?= json_encode($values) ?>,
                backgroundColor: '#3B82F6',
                borderColor: '#1E3A8A',
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>