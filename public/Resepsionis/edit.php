<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../login.php");
    exit;
}
require_once '../../config/database.php';

$id = $_GET['id'] ?? '';
if(!$id) die("ID reservasi tidak ditemukan.");

// Ambil data reservasi utama
$stmtRes = $conn->prepare("SELECT * FROM reservasi WHERE id_Reservasi = ?");
$stmtRes->execute([$id]);
$reservasi = $stmtRes->fetch(PDO::FETCH_ASSOC);
if(!$reservasi) die("Data reservasi tidak ditemukan.");

// Ambil detail tipe kamar yang sudah dipesan
$stmtDetail = $conn->prepare("
    SELECT dr.id_Tipe, dr.jumlah_Kamar, tk.nama_tipe, tk.harga_Per_Malam
    FROM detail_reservasi dr
    JOIN tipekamar tk ON dr.id_Tipe = tk.id_Tipe
    WHERE dr.id_Reservasi = ?
");
$stmtDetail->execute([$id]);
$detail_tipe = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua tipe kamar (untuk dropdown tambah)
$allTipe = $conn->query("SELECT id_Tipe, nama_tipe, harga_Per_Malam FROM tipekamar")->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua kamar (untuk grid di step 3)
$allRooms = $conn->query("SELECT id_Kamar, nomor_Kamar, id_Tipe, status_Kamar FROM kamar")->fetchAll(PDO::FETCH_ASSOC);

// Cek error dari session (untuk error dari proses update)
$errorMsg = $_SESSION['edit_error'] ?? '';
unset($_SESSION['edit_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Reservasi - Hotel Khatulistiwa</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .modal-card { background: white; max-width: 800px; width: 100%; border-radius: 24px; padding: 25px; box-shadow: 0 20px 30px rgba(0,0,0,0.2); border-top: 8px solid #2563EB; position: relative; }
        .step-header { display: flex; align-items: center; gap: 15px; border-bottom: 2px solid #EFF6FF; padding-bottom: 10px; margin-bottom: 20px; }
        .step-header h2 { font-size: 1.4rem; font-weight: bold; color: #1E3A8A; }
        .btn-back-top { background: none; border: none; color: #2563EB; cursor: pointer; font-size: 1rem; font-weight: bold; display: flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 20px; transition: 0.2s; }
        .btn-back-top:hover { background: #EFF6FF; }
        .step { display: none; }
        .step.active { display: block; }
        label { display: block; margin-top: 15px; font-weight: 600; color: #475569; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #CBD5E0; border-radius: 12px; }
        .btn-nav { background: #2563EB; color: white; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-weight: bold; margin-top: 20px; float: right; }
        .btn-prev { background: #94A3B8; float: left; }
        .btn-submit { background: #059669; color: white; border: none; padding: 12px; border-radius: 30px; width: 100%; margin-top: 20px; font-weight: bold; cursor: pointer; }
        .type-item { background: #F8FAFC; padding: 15px; border-radius: 16px; margin-bottom: 15px; border: 1px solid #E2E8F0; }
        .type-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .btn-hapus-tipe { background: #EF4444; color: white; border: none; padding: 5px 12px; border-radius: 20px; cursor: pointer; }
        .add-type-row { display: flex; gap: 10px; margin-top: 15px; align-items: flex-end; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; padding: 15px; background: #F1F5F9; border-radius: 16px; }
        .room-item { padding: 12px 5px; text-align: center; border-radius: 12px; cursor: pointer; font-weight: bold; border: 2px solid transparent; transition: 0.2s; background: #E2E8F0; color: #1E293B; }
        .room-available { background: #BBF7D0; color: #166534; }
        .room-unavailable { background: #E2E8F0; color: #94A3B8; cursor: not-allowed; }
        .room-selected { border-color: #2563EB; background: #2563EB; color: white; }
        .type-section { margin-top: 20px; border-left: 4px solid #2563EB; padding-left: 15px; }
        .error-popup { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #EF4444; color: white; padding: 12px 24px; border-radius: 30px; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; font-size: 0.9rem; text-align: center; white-space: nowrap; }
        @media (max-width: 600px) { .error-popup { white-space: normal; width: 90%; } }
    </style>
</head>
<body>
<div class="error-popup" id="errorPopup"><?= htmlspecialchars($errorMsg) ?></div>
<div class="modal-card">
    <div class="step-header">
        <button class="btn-back-top" id="btnBackTop" onclick="handleBack()">←</button>
        <h2 id="stepTitleText">Step 1: Ubah Tanggal</h2>
    </div>

    <form id="editForm" action="../../process/Resepsionis/update.php" method="POST">
        <input type="hidden" name="id_reservasi" value="<?= $reservasi['id_Reservasi'] ?>">
        <input type="hidden" name="selected_rooms" id="selectedRoomsInput">
        <input type="hidden" name="tipe_data" id="tipeDataInput">

        <!-- STEP 1: TANGGAL -->
        <div class="step active" id="step1">
            <label>Tanggal Check-In</label>
            <input type="date" name="check_in" id="check_in" value="<?= $reservasi['tanggal_Check_In'] ?>" required>
            <label>Tanggal Check-Out</label>
            <input type="date" name="check_out" id="check_out" value="<?= $reservasi['tanggal_Check_Out'] ?>" required>
            <button type="button" class="btn-nav" onclick="nextStep(2)">Lanjut &rarr;</button>
        </div>

        <!-- STEP 2: KELOLA TIPE KAMAR -->
        <div class="step" id="step2">
            <div id="tipeContainer"></div>
            <div class="add-type-row">
                <select id="newTipeSelect" style="flex:2;">
                    <option value="">-- Tambah Tipe Baru --</option>
                    <?php foreach($allTipe as $t): ?>
                        <option value="<?= $t['id_Tipe'] ?>"><?= $t['nama_tipe'] ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="newJumlah" placeholder="Jumlah" min="1" value="1" style="flex:1;">
                <button type="button" class="btn-nav" style="float:none; background:#10B981;" onclick="addTipe()">+ Tambah</button>
            </div>
            <button type="button" class="btn-nav btn-prev" onclick="prevStep()">&larr; Kembali</button>
            <button type="button" class="btn-nav" onclick="nextStep(3)">Pilih Kamar &rarr;</button>
        </div>

        <!-- STEP 3: PILIH NOMOR KAMAR -->
        <div class="step" id="step3">
            <div id="roomSelectionArea"></div>
            <button type="button" class="btn-nav btn-prev" onclick="prevStep()">&larr; Kembali</button>
            <button type="submit" class="btn-submit">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
    const detailTipe = <?= json_encode($detail_tipe) ?>;
    const allTipeList = <?= json_encode($allTipe) ?>;
    const allRooms = <?= json_encode($allRooms) ?>;
    let selectedRooms = [];

    // Fungsi showError custom
    function showError(message) {
        const popup = document.getElementById('errorPopup');
        popup.textContent = message;
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 4000);
    }

    function handleBack() {
        const activeStep = document.querySelector('.step.active').id;
        if(activeStep === 'step1') {
            window.location.href = 'index.php';
        } else if(activeStep === 'step2') {
            prevStep();
        } else if(activeStep === 'step3') {
            prevStep();
        }
    }

    function renderStep2() {
        const container = document.getElementById('tipeContainer');
        container.innerHTML = '';
        detailTipe.forEach((tipe, idx) => {
            const tipeInfo = allTipeList.find(t => t.id_Tipe === tipe.id_Tipe);
            const nama = tipeInfo ? tipeInfo.nama_tipe : tipe.nama_tipe;
            const harga = tipeInfo ? tipeInfo.harga_Per_Malam : tipe.harga_Per_Malam;
            const div = document.createElement('div');
            div.className = 'type-item';
            div.innerHTML = `
                <div class="type-item-header">
                    <strong>${nama}</strong>
                    <button type="button" class="btn-hapus-tipe" onclick="hapusTipe(${idx})">Hapus</button>
                </div>
                <label>Jumlah Kamar</label>
                <input type="number" class="jumlah-input" value="${tipe.jumlah_Kamar}" min="1" data-idx="${idx}" style="width:100px;">
                <small>Harga: Rp ${new Intl.NumberFormat('id-ID').format(harga)}/malam</small>
            `;
            container.appendChild(div);
        });
        document.querySelectorAll('.jumlah-input').forEach(inp => {
            inp.addEventListener('change', function() {
                const idx = parseInt(this.dataset.idx);
                detailTipe[idx].jumlah_Kamar = parseInt(this.value) || 1;
            });
        });
    }

    function hapusTipe(idx) {
        detailTipe.splice(idx, 1);
        renderStep2();
    }

    function addTipe() {
        const tipeId = document.getElementById('newTipeSelect').value;
        const jumlah = parseInt(document.getElementById('newJumlah').value);
        if (!tipeId) {
            showError('Pilih tipe kamar terlebih dahulu');
            return;
        }
        const tipeInfo = allTipeList.find(t => t.id_Tipe === tipeId);
        if (!tipeInfo) return;
        if (detailTipe.some(t => t.id_Tipe === tipeId)) {
            showError('Tipe kamar sudah ada dalam daftar. Edit jumlah saja.');
            return;
        }
        detailTipe.push({
            id_Tipe: tipeId,
            jumlah_Kamar: jumlah,
            nama_tipe: tipeInfo.nama_tipe,
            harga_Per_Malam: tipeInfo.harga_Per_Malam
        });
        renderStep2();
        document.getElementById('newTipeSelect').value = '';
        document.getElementById('newJumlah').value = 1;
    }

    function generateRoomGrids() {
        const area = document.getElementById('roomSelectionArea');
        area.innerHTML = '';
        selectedRooms = [];
        detailTipe.forEach(tipe => {
            const tipeId = tipe.id_Tipe;
            const qty = tipe.jumlah_Kamar;
            const tipeName = tipe.nama_tipe;
            const roomsOfType = allRooms.filter(r => r.id_Tipe === tipeId);
            const section = document.createElement('div');
            section.className = 'type-section';
            section.innerHTML = `<h4>${tipeName} (Pilih ${qty} Kamar)</h4><div class="room-grid" id="grid-${tipeId}" data-limit="${qty}"></div>`;
            area.appendChild(section);
            const grid = section.querySelector('.room-grid');
            roomsOfType.forEach(room => {
                const isAvailable = (room.status_Kamar === 'Tersedia');
                const div = document.createElement('div');
                div.className = `room-item ${isAvailable ? 'room-available' : 'room-unavailable'}`;
                div.innerText = room.nomor_Kamar;
                if(isAvailable) {
                    div.onclick = () => toggleRoom(div, room.id_Kamar, grid);
                }
                grid.appendChild(div);
            });
        });
        updateSelectedRoomsInput();
    }

    function toggleRoom(el, id, grid) {
        const limit = parseInt(grid.dataset.limit);
        const currentSelected = grid.querySelectorAll('.room-selected').length;
        if(el.classList.contains('room-selected')) {
            el.classList.remove('room-selected');
            selectedRooms = selectedRooms.filter(sid => sid !== id);
        } else {
            if(currentSelected >= limit) {
                showError(`Maksimal pilih ${limit} kamar untuk tipe ini!`);
                return;
            }
            el.classList.add('room-selected');
            selectedRooms.push(id);
        }
        updateSelectedRoomsInput();
    }

    function updateSelectedRoomsInput() {
        document.getElementById('selectedRoomsInput').value = selectedRooms.join(',');
    }

    function validateSelections() {
        let temp = {};
        detailTipe.forEach(t => { temp[t.id_Tipe] = 0; });
        selectedRooms.forEach(roomId => {
            const room = allRooms.find(r => r.id_Kamar === roomId);
            if(room) temp[room.id_Tipe] = (temp[room.id_Tipe] || 0) + 1;
        });
        for(let t of detailTipe) {
            if(temp[t.id_Tipe] !== t.jumlah_Kamar) {
                showError(`Tipe ${t.nama_tipe} harus memilih tepat ${t.jumlah_Kamar} kamar.`);
                return false;
            }
        }
        return true;
    }

    document.getElementById('editForm').addEventListener('submit', function(e) {
        if(!validateSelections()) {
            e.preventDefault();
            return false;
        }
        document.getElementById('tipeDataInput').value = JSON.stringify(detailTipe);
        return true;
    });

    function nextStep(step) {
        if(step === 2) renderStep2();
        if(step === 3) generateRoomGrids();
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');
        const titles = {1:"Step 1: Ubah Tanggal", 2:"Step 2: Kelola Tipe Kamar", 3:"Step 3: Pilih Nomor Kamar"};
        document.getElementById('stepTitleText').innerText = titles[step];
    }

    function prevStep() {
        const activeStep = document.querySelector('.step.active').id;
        if(activeStep === 'step2') nextStep(1);
        else if(activeStep === 'step3') nextStep(2);
    }

    renderStep2();

    // Tampilkan error dari session (jika ada)
    const errorMsg = "<?= addslashes($errorMsg) ?>";
    if(errorMsg) {
        showError(errorMsg);
    }
</script>
</body>
</html>