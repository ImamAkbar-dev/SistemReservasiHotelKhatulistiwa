<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../../config/database.php';

$granularitas = isset($_GET['granularitas']) ? $_GET['granularitas'] : 'hari'; // default 'hari'

// Ambil nilai periode dari GET (bisa kosong)
$periode_awal_raw = isset($_GET['periode_awal']) && $_GET['periode_awal'] !== '' ? $_GET['periode_awal'] : null;
$periode_akhir_raw = isset($_GET['periode_akhir']) && $_GET['periode_akhir'] !== '' ? $_GET['periode_akhir'] : null;

// Fungsi untuk mendapatkan range tanggal dari database (min dan max tanggal_Check_In)
function getDatabaseMinMax($conn) {
    $range = $conn->query("SELECT MIN(tanggal_Check_In) as min_tgl, MAX(tanggal_Check_In) as max_tgl FROM reservasi")->fetch();
    return [$range['min_tgl'], $range['max_tgl']];
}

// Konversi input menjadi tanggal mulai dan akhir (untuk query SQL)
function getDateRangeForQuery($granularitas, $awal_raw, $akhir_raw, $conn) {
    if (!$awal_raw || !$akhir_raw) {
        list($min_date, $max_date) = getDatabaseMinMax($conn);
        if ($granularitas == 'hari') {
            return [$min_date, $max_date];
        } elseif ($granularitas == 'bulan') {
            // Ubah ke tanggal pertama dan terakhir dari bulan pertama dan bulan terakhir
            $start = $min_date ? date('Y-m-01', strtotime($min_date)) : date('Y-m-01');
            $end = $max_date ? date('Y-m-t', strtotime($max_date)) : date('Y-m-t');
            return [$start, $end];
        } else { // tahun
            $start = $min_date ? date('Y-01-01', strtotime($min_date)) : date('Y-01-01');
            $end = $max_date ? date('Y-12-31', strtotime($max_date)) : date('Y-12-31');
            return [$start, $end];
        }
    }
    // Jika sudah ada input, konversi sesuai granularitas
    if ($granularitas == 'hari') {
        return [$awal_raw, $akhir_raw];
    } elseif ($granularitas == 'bulan') {
        // Input bulan dalam format YYYY-MM, jadikan tanggal 1 dan akhir bulan
        $start = date('Y-m-01', strtotime($awal_raw . '-01'));
        $end = date('Y-m-t', strtotime($akhir_raw . '-01'));
        return [$start, $end];
    } else { // tahun
        $start = $awal_raw . '-01-01';
        $end = $akhir_raw . '-12-31';
        return [$start, $end];
    }
}

list($periode_awal, $periode_akhir) = getDateRangeForQuery($granularitas, $periode_awal_raw, $periode_akhir_raw, $conn);

// Hitung statistik
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

// Query grafik
$labels = [];
$values = [];

if ($granularitas == 'hari') {
    $sqlChart = "SELECT tanggal_Check_In as tgl, SUM(total_Biaya) as pendapatan
                FROM reservasi
                WHERE tanggal_Check_In BETWEEN :awal AND :akhir
                GROUP BY tanggal_Check_In
                ORDER BY tgl ASC";
    $stmtChart = $conn->prepare($sqlChart);
    $stmtChart->execute([':awal' => $periode_awal, ':akhir' => $periode_akhir]);
    $chartData = $stmtChart->fetchAll();
    foreach ($chartData as $row) {
        $labels[] = date('d M Y', strtotime($row['tgl']));
        $values[] = $row['pendapatan'];
    }
} elseif ($granularitas == 'tahun') {
    $sqlChart = "SELECT YEAR(tanggal_Check_In) as tahun, SUM(total_Biaya) as pendapatan
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
} else { // bulan
    $sqlChart = "SELECT DATE_FORMAT(tanggal_Check_In, '%Y-%m') as bulan, SUM(total_Biaya) as pendapatan
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

// Untuk menampilkan nilai default di form input (sesuai granularitas)
$display_awal = '';
$display_akhir = '';
if ($granularitas == 'hari') {
    $display_awal = $periode_awal;
    $display_akhir = $periode_akhir;
} elseif ($granularitas == 'bulan') {
    $display_awal = date('Y-m', strtotime($periode_awal));
    $display_akhir = date('Y-m', strtotime($periode_akhir));
} else {
    $display_awal = date('Y', strtotime($periode_awal));
    $display_akhir = date('Y', strtotime($periode_akhir));
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
        .error-popup { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #EF4444; color: white; padding: 12px 24px; border-radius: 30px; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; font-size: 0.9rem; white-space: nowrap; }
        @media (max-width: 640px) { body { padding: 20px; } .stat-number { font-size: 2rem; } .error-popup { white-space: normal; width: 90%; } }
    </style>
</head>
<body>
<div class="error-popup" id="errorPopup"></div>
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
        <form method="GET" id="filterForm" class="filter-group">
            <div class="filter-inputs" id="periodeInputs">
                <!-- Dinamis oleh JavaScript -->
            </div>
            <div>
                <label>📊 Grafik per</label>
                <select name="granularitas" id="granularitas">
                    <option value="hari" <?= $granularitas == 'hari' ? 'selected' : '' ?>>Hari</option>
                    <option value="bulan" <?= $granularitas == 'bulan' ? 'selected' : '' ?>>Bulan</option>
                    <option value="tahun" <?= $granularitas == 'tahun' ? 'selected' : '' ?>>Tahun</option>
                </select>
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

    <div class="chart-card">
        <h3>📈 Pendapatan per <?= ucfirst($granularitas) ?> (Rp)</h3>
        <canvas id="revenueChart"></canvas>
    </div>

    <footer>
        Sistem Informasi Reservasi Hotel Khatulistiwa &copy; 2026
    </footer>
</div>

<script>
    const granularitasSelect = document.getElementById('granularitas');
    const periodeContainer = document.getElementById('periodeInputs');
    let currentGranularitas = '<?= $granularitas ?>';
    let currentAwal = '<?= $display_awal ?>';
    let currentAkhir = '<?= $display_akhir ?>';

    function showError(message) {
        const popup = document.getElementById('errorPopup');
        popup.textContent = message;
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 4000);
    }

    function renderInputs() {
        const granularitas = granularitasSelect.value;
        let html = '';
        const currentYear = new Date().getFullYear();
        if (granularitas === 'hari') {
            html = `
                <div><label>📅 Dari</label><input type="date" name="periode_awal" id="periode_awal" value="${currentAwal}"></div>
                <div><label>📅 Sampai</label><input type="date" name="periode_akhir" id="periode_akhir" value="${currentAkhir}"></div>
            `;
        } else if (granularitas === 'bulan') {
            html = `
                <div><label>📅 Dari bulan</label><input type="month" name="periode_awal" id="periode_awal" value="${currentAwal}"></div>
                <div><label>📅 Sampai bulan</label><input type="month" name="periode_akhir" id="periode_akhir" value="${currentAkhir}"></div>
            `;
        } else { // tahun
            html = `
                <div><label>📅 Dari tahun</label><input type="number" name="periode_awal" id="periode_awal" value="${currentAwal}" min="2000"></div>
                <div><label>📅 Sampai tahun</label><input type="number" name="periode_akhir" id="periode_akhir" value="${currentAkhir}" min="2000"></div>
            `;
        }
        periodeContainer.innerHTML = html;
        const awalInput = document.getElementById('periode_awal');
        const akhirInput = document.getElementById('periode_akhir');
        if (awalInput && akhirInput) {
            awalInput.addEventListener('change', validateDates);
            akhirInput.addEventListener('change', validateDates);
        }
    }

    function validateDates() {
        const granularitas = granularitasSelect.value;
        const awal = document.getElementById('periode_awal').value;
        const akhir = document.getElementById('periode_akhir').value;
        if (!awal || !akhir) return true;
        if (granularitas === 'hari') {
            if (awal > akhir) {
                showError('Tanggal awal tidak boleh lebih dari tanggal akhir');
                return false;
            }
        } else if (granularitas === 'bulan') {
            if (awal > akhir) {
                showError('Bulan awal tidak boleh lebih dari bulan akhir');
                return false;
            }
        } else {
            if (parseInt(awal) > parseInt(akhir)) {
                showError('Tahun awal tidak boleh lebih dari tahun akhir');
                return false;
            }
        }
        return true;
    }

    granularitasSelect.addEventListener('change', function() {
        // Simpan nilai input sebelumnya (jika ada)
        const oldAwal = document.getElementById('periode_awal')?.value || currentAwal;
        const oldAkhir = document.getElementById('periode_akhir')?.value || currentAkhir;
        currentAwal = oldAwal;
        currentAkhir = oldAkhir;
        renderInputs();
    });

    document.getElementById('filterForm').addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    renderInputs();

    // Chart
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
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                },
                legend: { position: 'top' }
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
