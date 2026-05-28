<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: ../public/login.php");
    exit;
}
// Resepsionis saja yang boleh akses halaman reservasi
if($_SESSION['user']['role'] != 'resepsionis') {
    header("Location: ../admin/laporan.php");
    exit;
}

require_once '../../config/database.php';
$pegawai = $conn->query("SELECT id_Pegawai, nama_Pegawai FROM pegawai")->fetchAll();
$tipe = $conn->query("SELECT id_Tipe, nama_tipe FROM tipekamar")->fetchAll();
$pembayaran = $conn->query("SELECT id_Pembayaran, metode_Pembayaran FROM pembayaran")->fetchAll();
$kamar_data = $conn->query("SELECT id_Kamar, nomor_Kamar, id_Tipe, status_Kamar FROM kamar")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reservasi Wizard - Hotel Khatulistiwa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F0F7FF; padding: 20px; }
        .card { background: white; max-width: 800px; margin: 0 auto; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-top: 6px solid #2563EB; position: relative; }
        .step { display: none; }
        .step.active { display: block; }
        .step-header { border-bottom: 2px solid #F1F5F9; padding-bottom: 10px; margin-bottom: 25px; color: #1E3A8A; font-weight: bold; font-size: 1.2rem; margin-left: 35px; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #475569; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #CBD5E0; border-radius: 8px; box-sizing: border-box; }
        .btn-nav { background: #2563EB; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 20px; float: right; }
        .btn-prev { background: #94A3B8; float: left; }
        .btn-back-top { background: none; border: none; color: #2563EB; cursor: pointer; font-size: 1.2rem; font-weight: bold; display: flex; align-items: center; gap: 5px; padding: 5px 10px; border-radius: 20px; transition: 0.2s; position: absolute; top: 30px; left: 20px; }
        .btn-back-top:hover { background: #EFF6FF; }
        
        /* Multi-Type Selector Style */
        .type-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px; background: #F8FAFC; padding: 10px; border-radius: 8px; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 10px; padding: 15px; background: #F1F5F9; border-radius: 10px; }
        .room-item { padding: 15px 5px; text-align: center; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.8rem; border: 2px solid transparent; transition: 0.2s; }
        .room-available { background: #BBF7D0; color: #166534; }
        .room-unavailable { background: #E2E8F0; color: #94A3B8; cursor: not-allowed; }
        .room-selected { border-color: #2563EB; background: #2563EB; color: white; }
        .type-section { margin-top: 25px; border-left: 4px solid #2563EB; padding-left: 15px; }
        /* Custom error popup */
        .error-popup { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #EF4444; color: white; padding: 12px 24px; border-radius: 30px; z-index: 2000; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; font-size: 0.9rem; text-align: center; white-space: nowrap; }
        @media (max-width: 600px) { .error-popup { white-space: normal; width: 90%; } }
    </style>
</head>
<body>
<div class="error-popup" id="errorPopup"></div>
<div class="card">
    <button class="btn-back-top" id="btnBackTop" onclick="handleBack()">←</button>
    <div class="step-header" id="stepTitle">Langkah 1: Data Pelanggan</div>

    <form action="../../process/Resepsionis/insert.php" method="POST" id="resForm">
        <!-- STEP 1: DATA PELANGGAN -->
        <div class="step active" id="step1">
            <label>NAMA PELANGGAN</label>
            <input type="text" name="nama_pelanggan" id="nama_pelanggan" required>
            <label>EMAIL</label>
            <input type="email" name="Email" id="Email" required>
            <label>NO HANDPHONE</label>
            <input type="text" name="nomorhp_Pelanggan" id="nomorhp" required>
            <label>RESEPSIONIS MELAYANI</label>
            <select name="id_Pegawai" id="id_pegawai" required>
                <?php foreach($pegawai as $pg): ?>
                    <option value="<?= $pg['id_Pegawai'] ?>"><?= $pg['nama_Pegawai'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn-nav" onclick="nextStep(2)">Lanjut &rarr;</button>
        </div>

        <!-- STEP 2: MULTI TIPE & PEMBAYARAN -->
        <div class="step" id="step2">
            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;"><label>CHECK-IN</label><input type="date" name="check_in" id="check_in" required></div>
                <div style="flex: 1;"><label>CHECK-OUT</label><input type="date" name="check_out" id="check_out" required></div>
            </div>

            <label>PILIH TIPE KAMAR & JUMLAH</label>
            <div id="typeContainer">
                <div class="type-row">
                    <div style="flex: 2;">
                        <select name="id_tipe[]" class="tipe-select" required>
                            <option value="">-- Pilih Tipe --</option>
                            <?php foreach($tipe as $t): ?><option value="<?= $t['id_Tipe'] ?>"><?= $t['nama_tipe'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <input type="number" name="jumlah_kamar[]" class="jumlah-input" min="1" value="1" required>
                    </div>
                    <button type="button" style="background:#EF4444; color:white; border:none; padding:10px; border-radius:5px;" onclick="this.parentElement.remove()">X</button>
                </div>
            </div>
            <button type="button" onclick="addTypeRow()" style="margin-top:10px; background:#10B981; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">+ Tambah Tipe Lain</button>

            <div style="display: flex; gap: 15px; margin-top:20px;">
                <div style="flex: 1;">
                    <label>METODE PEMBAYARAN</label>
                    <select name="id_pembayaran" id="id_pembayaran">
                        <?php foreach($pembayaran as $pb): ?><option value="<?= $pb['id_Pembayaran'] ?>"><?= $pb['metode_Pembayaran'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>STATUS</label>
                    <select name="status" id="status">
                        <option value="Belum">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn-nav btn-prev" onclick="prevStep()">&larr; Kembali</button>
            <button type="button" class="btn-nav" onclick="nextStep(3)">Pilih Nomor Kamar &rarr;</button>
        </div>

        <!-- STEP 3: VISUAL ROOM PICKER -->
        <div class="step" id="step3">
            <div id="roomSelectionArea"></div>
            <input type="hidden" name="selected_rooms" id="selectedRoomsInput">
            <button type="button" class="btn-nav btn-prev" onclick="prevStep()">&larr; Kembali</button>
            <button type="submit" class="btn-nav" style="background:#059669;">Selesaikan Reservasi</button>
        </div>
    </form>
</div>

<script>
    const allRooms = <?= json_encode($kamar_data) ?>;
    const tipeKamarMap = <?= json_encode($tipe) ?>;
    let selectedRooms = [];
    
    // ======================= VALIDASI STEP 1 =======================
    function validateStep1() {
        let nama = document.getElementById('nama_pelanggan').value.trim();
        let email = document.getElementById('Email').value.trim();
        let nomorhp = document.getElementById('nomorhp').value.trim();
        let pegawai = document.getElementById('id_pegawai').value;
        
        if(nama === '') {
            showError('Nama pelanggan harus diisi');
            return false;
        }
        if(email === '') {
            showError('Email harus diisi');
            return false;
        }
        if(!email.includes('@') || !email.includes('.')) {
            showError('Format email tidak valid');
            return false;
        }
        if(nomorhp === '') {
            showError('Nomor handphone harus diisi');
            return false;
        }
        if(pegawai === '') {
            showError('Pilih resepsionis yang melayani');
            return false;
        }
        return true;
    }

    // ======================= VALIDASI STEP 2 =======================
    function validateStep2() {
        let check_in = document.getElementById('check_in').value;
        let check_out = document.getElementById('check_out').value;
        
        if(check_in === '') {
            showError('Tanggal check-in harus diisi');
            return false;
        }
        if(check_out === '') {
            showError('Tanggal check-out harus diisi');
            return false;
        }
        if(new Date(check_in) >= new Date(check_out)) {
            showError('Tanggal check-out harus setelah tanggal check-in');
            return false;
        }
        
        // Validasi tipe kamar: pastikan minimal satu baris tipe dipilih dan tidak ada select yang kosong
        const typeRows = document.querySelectorAll('.type-row');
        if(typeRows.length === 0) {
            showError('Minimal pilih satu tipe kamar');
            return false;
        }
        
        let valid = true;
        typeRows.forEach(row => {
            const select = row.querySelector('.tipe-select');
            if(select.value === '') {
                showError('Semua baris tipe kamar harus dipilih jenisnya');
                valid = false;
            }
            const jumlah = parseInt(row.querySelector('.jumlah-input').value);
            if(isNaN(jumlah) || jumlah < 1) {
                showError('Jumlah kamar minimal 1');
                valid = false;
            }
        });
        if(!valid) return false;
        
        return true;
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

    function showError(message) {
        const popup = document.getElementById('errorPopup');
        popup.textContent = message;
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.display = 'none';
        }, 4000);
    }

    function addTypeRow() {
        const container = document.getElementById('typeContainer');
        const originalRow = container.children[0];
        const newRow = originalRow.cloneNode(true);
        newRow.querySelector('.jumlah-input').value = 1;
        // reset select value ke default
        newRow.querySelector('.tipe-select').value = '';
        container.appendChild(newRow);
    }

    // Fungsi navigasi dengan validasi
    function nextStep(step) {
        if(step === 2) {
            if(!validateStep1()) return;
        }
        if(step === 3) {
            if(!validateStep2()) return;
        }
        
        document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');
        const titles = ["", "Langkah 1: Data Pelanggan", "Langkah 2: Detail Pemesanan", "Langkah 3: Pilih Nomor Kamar"];
        document.getElementById('stepTitle').innerText = titles[step];
        
        if(step === 3) generateRoomGrids();
    }

    function prevStep() {
        const activeStep = document.querySelector('.step.active').id;
        if(activeStep === 'step2') nextStep(1);
        else if(activeStep === 'step3') nextStep(2);
    }

    function generateRoomGrids() {
        const area = document.getElementById('roomSelectionArea');
        area.innerHTML = '';
        selectedRooms = [];
        
        const selectedTypes = document.querySelectorAll('.type-row');
        selectedTypes.forEach(row => {
            const tipeId = row.querySelector('.tipe-select').value;
            const qty = parseInt(row.querySelector('.jumlah-input').value);
            const tipeName = Array.from(row.querySelector('.tipe-select').options).find(o => o.value == tipeId).text;
            
            if(!tipeId) return; // skip jika tidak terpilih (sudah divalidasi sebelumnya)
            
            const section = document.createElement('div');
            section.className = 'type-section';
            section.innerHTML = `<h4>${tipeName} (Pilih ${qty} Kamar)</h4><div class="room-grid" id="grid-${tipeId}" data-limit="${qty}"></div>`;
            area.appendChild(section);
            
            const grid = section.querySelector('.room-grid');
            allRooms.filter(r => r.id_Tipe == tipeId).forEach(room => {
                const div = document.createElement('div');
                div.className = 'room-item ' + (room.status_Kamar === 'Tersedia' ? 'room-available' : 'room-unavailable');
                div.innerText = room.nomor_Kamar;
                if(room.status_Kamar === 'Tersedia') {
                    div.onclick = () => toggleRoom(div, room.id_Kamar, grid);
                }
                grid.appendChild(div);
            });
        });
    }

    function toggleRoom(el, id, grid) {
        const limit = parseInt(grid.dataset.limit);
        const currentSelectedInGrid = grid.querySelectorAll('.room-selected').length;

        if(el.classList.contains('room-selected')) {
            el.classList.remove('room-selected');
            selectedRooms = selectedRooms.filter(sid => sid !== id);
        } else {
            if(currentSelectedInGrid >= limit) {
                showError(`Maksimal pilih ${limit} kamar untuk tipe ini!`);
                return;
            }
            el.classList.add('room-selected');
            selectedRooms.push(id);
        }
        document.getElementById('selectedRoomsInput').value = selectedRooms.join(',');
    }
</script>
</body>
</html>