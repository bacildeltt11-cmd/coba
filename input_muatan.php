<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    $_SESSION['id_manifest'] = $_GET['id'];
} elseif (!isset($_SESSION['id_manifest'])) {
    // Cari manifest terbaru jika tidak ada di session/GET
    $latest = findDocuments("manifest", [], ['sort' => ['_id' => -1], 'limit' => 1]);
    if (!empty($latest)) {
        $_SESSION['id_manifest'] = (string)$latest[0]->_id;
    } else {
        header("Location: input_keberangkatan.php");
        exit;
    }
}
$id_manifest = $_SESSION['id_manifest'];

// 1. MESIN UPDATE TOTAL MANUAL
if (isset($_POST['total_manual'])) {
    $total_baru = $_POST['total_manual'];
    if (is_numeric($total_baru) && $total_baru >= 0) {
        updateDocument("manifest",
            ['_id' => new MongoDB\BSON\ObjectId($id_manifest)],
            ['$set' => ['total_ton_manual' => $total_baru]]
        );
    }
    header("Location: input_muatan.php?id=" . urlencode($id_manifest));
    exit;
}

// 2. MESIN SIMPAN BARANG
if (isset($_POST['simpan_database'])) {
    $nama   = sanitize_string($_POST['nama_barang'] ?? '');
    $pcs    = filter_var($_POST['pcs_barang'] ?? 0, FILTER_VALIDATE_INT);
    $ton    = filter_var($_POST['ton_barang'] ?? 0, FILTER_VALIDATE_FLOAT);
    $vol    = sanitize_string($_POST['vol_barang'] ?? '');

    $errors = [];
    if (empty($nama)) {
        $errors[] = "Nama barang harus diisi";
    }
    if ($ton === false || $ton < 0) {
        $errors[] = "Ton harus berupa angka positif yang valid";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: input_muatan.php?id=" . urlencode($id_manifest));
        exit;
    }

    // Pastikan barang terdaftar di master_barang
    $masterObj = findOneDocument("master_barang", [
        "nama" => new MongoDB\BSON\Regex('^' . preg_quote($nama) . '$', 'i')
    ]);
    if (!$masterObj) {
        insertDocument("master_barang", ["nama" => $nama, "pcs" => 0]);
        $canonical_nama = $nama;
    } else {
        $master = (array)$masterObj;
        $canonical_nama = $master['nama'] ?? $nama;
    }

    // Insert muatan record
    insertDocument("muatan", [
        "id_manifest" => $id_manifest,
        "nama_barang" => $canonical_nama,
        "pcs" => $pcs ?: 0,
        "ton" => $ton,
        "volume" => $vol
    ]);

    header("Location: input_muatan.php?id=" . urlencode($id_manifest));
    exit;
}

// 2.5 MESIN UPDATE BARANG
if (isset($_POST['update_database'])) {
    $id_edit = $_POST['id_edit'];
    $nama   = sanitize_string($_POST['nama_barang'] ?? '');
    $pcs    = filter_var($_POST['pcs_barang'] ?? 0, FILTER_VALIDATE_INT);
    $ton    = filter_var($_POST['ton_barang'] ?? 0, FILTER_VALIDATE_FLOAT);
    $vol    = sanitize_string($_POST['vol_barang'] ?? '');

    $errors = [];
    if (empty($nama)) {
        $errors[] = "Nama barang harus diisi";
    }
    if ($ton === false || $ton < 0) {
        $errors[] = "Ton harus berupa angka positif yang valid";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: input_muatan.php?id=" . urlencode($id_manifest));
        exit;
    }

    // Update muatan record
    updateDocument("muatan",
        ['_id' => new MongoDB\BSON\ObjectId($id_edit)],
        ['$set' => [
            "nama_barang" => $nama,
            "pcs" => $pcs ?: 0,
            "ton" => $ton,
            "volume" => $vol
        ]]
    );

    header("Location: input_muatan.php?id=" . urlencode($id_manifest));
    exit;
}

// 3. MESIN HAPUS BARANG
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    deleteDocument("muatan", ['_id' => new MongoDB\BSON\ObjectId($id_hapus)]);
    header("Location: input_muatan.php?id=" . urlencode($id_manifest));
    exit;
}

// Ambil data edit jika ada parameter ?edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    try {
        $edit_obj = findOneDocument("muatan", ['_id' => new MongoDB\BSON\ObjectId($id_edit)]);
        if ($edit_obj) $edit_data = (array)$edit_obj;
    } catch (\Throwable $e) {}
}

// Ambil data header untuk ditampilkan
$h_obj = findOneDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id_manifest)]);
$h = $h_obj ? (array)$h_obj : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Muatan - CV. MANUNGGAL</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header-info { background: var(--light-blue); border-left: 5px solid var(--primary-blue); padding: 18px; border-radius: 8px; margin-bottom: 25px; }
        .header-info-inner { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .header-row { display: grid; grid-template-columns: 140px 10px auto; gap: 5px; line-height: 1.6; font-size: 14px; }
        .header-row span:first-child { font-weight: 600; color: var(--text-color); }
        .master-box { margin-bottom: 25px; padding: 20px; background: #fff8e1; border-radius: 10px; border-left: 5px solid #ff9800; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .master-box strong { font-size: 16px; color: #e65100; }
        .master-box input { padding: 12px; font-size: 16px; border-radius: 8px; border: 2px solid #ddd; width: 300px; }
        .master-box button { padding: 12px 20px; font-size: 16px; border-radius: 8px; }
        .row-input { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 15px; background: #f9fbff; padding: 20px; border-radius: 12px; align-items: end; }
        .field { display: flex; flex-direction: column; position: relative; }
        .field label { font-size: 16px; font-weight: bold; color: var(--text-color); margin-bottom: 5px; }
        .form-control, #search-box {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 12px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus, #search-box:focus {
            border-color: var(--primary-blue);
            outline: none;
            box-shadow: 0 0 5px rgba(10, 77, 191, 0.3);
        }
        .dropdown-list { position: absolute; top: 100%; left: 0; right: 0; background: var(--white); border: 1px solid var(--border-color); max-height: 200px; overflow-y: auto; z-index: 1000; display: none; border-radius: 0 0 8px 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .dropdown-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; font-size: 14px; }
        .dropdown-item:hover { background: var(--light-blue); }
        .btn-del-list { color: var(--danger); font-weight: bold; border: 1px solid var(--danger); padding: 0 6px; border-radius: 4px; font-size: 11px; }
        .nav-actions { margin-top: 35px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .table-custom th:nth-child(1) { width: 10%; }
        .table-custom th:nth-child(2) { width: 25%; }
        .table-custom th:nth-child(3) { width: 15%; }
        .table-custom th:nth-child(4) { width: 15%; }
        .table-custom th:nth-child(5) { width: 15%; }
        .table-custom th:nth-child(6) { width: 20%; }
        .table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; }

        @media (max-width: 768px) {
            .header-row { grid-template-columns: 95px 6px 1fr !important; font-size: 13px !important; }
            .row-input { grid-template-columns: 1fr 1fr !important; }
            .row-input .field:nth-child(1) { grid-column: 1 / -1 !important; }
            .row-input .field:nth-child(4) { grid-column: 1 / -1 !important; }
            .row-input > div:last-child { grid-column: 1 / -1 !important; }
        }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'top_nav.php'; ?>
        <?php if(isset($_SESSION['success'])){ echo '<div class="alert alert-success" style="padding:12px 20px; background:#d4edda; color:#155724; border-radius:8px; margin:20px; border:1px solid #c3e6cb;">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); } if(isset($_SESSION['error'])){ echo '<div class="alert alert-danger" style="padding:12px 20px; background:#f8d7da; color:#721c24; border-radius:8px; margin:20px; border:1px solid #f5c6cb;">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); } ?>
        <div class="content-wrapper">
            <div class="container">

                <div class="header-info">
                    <div class="header-info-inner">
                        <div class="header-row">
                            <span>Nama Kapal</span><span>:</span><span><?= e($h['kapal'] ?? '-') ?></span>
                            <span>Tanggal</span><span>:</span><span><?= tgl_indo($h['tanggal'] ?? '') ?></span>
                            <span>Tujuan</span><span>:</span><span><?= e($h['tujuan'] ?? '-') ?></span>
                            <span>Jenis</span><span>:</span><span><?= e($h['jenis'] ?? '-') ?></span>
                            <span>Nopol</span><span>:</span><span><?= e($h['nopol'] ?? '-') ?></span>
                            <span>Jam</span><span>:</span><span><?= e($h['jam'] ?? '-') ?> WIB</span>
                        </div>
                        <div>
                            <a href="input_keberangkatan.php?id=<?= e($id_manifest) ?>" class="btn btn-sm btn-secondary" style="background: var(--primary-blue); color: white;">✏️ Edit Keberangkatan</a>
                        </div>
                    </div>
                </div>

                <div class="master-box">
                    <strong>Daftarkan Barang Baru:</strong>
                    <input id="inputBaru" placeholder="Ketik nama barang..." style="max-width: 300px; width: 100%;">
                    <button onclick="tambahKeMaster()" style="background: orange; color: white; border: none; cursor: pointer; padding: 0 15px; border-radius: 8px;">+ Masukkan History</button>
                </div>

                <div class="form-scroll-wrapper">
                <form method="POST" autocomplete="off">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_edit" value="<?= e($_GET['edit']) ?>">
                    <?php endif; ?>
                    <div class="row-input">
                        <div class="field">
                            <label>Nama Barang (Ketik/Pilih)</label>
                            <input type="text" id="search-box" name="nama_barang" placeholder="🔍 Ketik nama barang..."
                                   onfocus="tampilkanSemua()" onkeyup="filterBarang()"
                                   value="<?= $edit_data ? e($edit_data['nama_barang'] ?? '') : '' ?>" required>
                            <div id="dropdownBarang" class="dropdown-list"></div>
                        </div>
                        <div class="field"><label>PCS</label><input type="number" name="pcs_barang" class="form-control" value="<?= $edit_data ? e($edit_data['pcs'] ?? '') : '' ?>" placeholder="0"></div>
                        <div class="field"><label>Ton</label><input type="number" step="0.01" name="ton_barang" class="form-control" value="<?= $edit_data ? e($edit_data['ton'] ?? '') : '' ?>" placeholder="0.00" required></div>
                        <div class="field"><label>Volume</label><input type="text" name="vol_barang" class="form-control" value="<?= $edit_data ? e($edit_data['volume'] ?? '') : '' ?>" placeholder="-"></div>

                        <div style="display: flex; gap: 5px; align-items: flex-end;">
                            <?php if ($edit_data): ?>
                                <button type="submit" name="update_database" class="btn btn-primary" style="width: 100%;">Update</button>
                                <a href="input_muatan.php?id=<?= e($id_manifest) ?>" class="btn btn-secondary">Batal</a>
                            <?php else: ?>
                                <button type="submit" name="simpan_database" class="btn btn-primary" style="width: 100%;">Tambah</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                </div>

                <div class="table-scroll-hint"><span class="hint-icon">👈</span> Geser tabel ke samping untuk melihat data lengkap <span class="hint-icon">👉</span></div>
                <div class="table-wrapper">
                <table class="table-custom">
                    <thead><tr><th>NO</th><th>NAMA BARANG</th><th>PCS</th><th>TON</th><th>VOLUME</th><th>AKSI</th></tr></thead>
                    <tbody>
                        <?php
                        $no = 1; $total_sist = 0;
                        $res = findDocuments("muatan", ["id_manifest" => $id_manifest]);
                        foreach($res as $m_obj){
                            $m = (array)$m_obj;
                            $total_sist += (float)($m['ton'] ?? 0);
                            $id_muatan = (string)$m['_id'];
                            $vol_tampil = isset($m['volume']) && $m['volume'] !== '' ? $m['volume'] : '-';
                            $edit_link = "?id=" . urlencode($id_manifest) . "&edit=" . urlencode($id_muatan);
                            $hapus_link = "?id=" . urlencode($id_manifest) . "&hapus=" . urlencode($id_muatan);
                            $edit_btn = "<a href='$edit_link' class='btn-pill btn-pill-edit'>✏️ Edit</a>";
                            $hapus_btn = "<a href='$hapus_link' class='btn-pill btn-pill-del' onclick='return confirm(\"Hapus item ini?\")'>🗑️ Hapus</a>";
                            echo "<tr><td>$no</td><td style='text-align:left; padding-left:15px; font-weight:600;'>" . e($m['nama_barang']) . "</td><td>" . e($m['pcs'] ?? 0) . "</td><td><strong>" . e($m['ton']) . "</strong></td><td>$vol_tampil</td><td><div class='action-btn-group'>$edit_btn $hapus_btn</div></td></tr>";
                            $no++;
                        }
                        if ($no === 1) {
                            echo "<tr><td colspan='6' class='no-data' style='padding:25px;color:#94a3b8;'>Belum ada muatan. Tambahkan barang melalui form di atas.</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: center;">TOTAL TON</td>
                            <td colspan="3">
                                <form method="POST">
                                    <input type="text" name="total_manual"
                                           style="width: 100px; text-align: center; font-weight: bold; border: 2px solid #0a4dbf; border-radius: 8px; padding: 6px;"
                                           value="<?= (isset($h['total_ton_manual']) && $h['total_ton_manual'] != '0' && $h['total_ton_manual'] != '') ? e($h['total_ton_manual']) : e($total_sist); ?>"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                </div>

                <div class="nav-actions">
                    <a href="dashboard.php" style="color:var(--gray); text-decoration:none; font-weight: bold;">← Kembali</a>
                    <a href="preview_manifest.php?id=<?= e($id_manifest) ?>" class="btn btn-primary">Lihat Manifest (Cetak) →</a>
                </div>
            </div>

<script>
let masterBrg = [];

async function loadMaster() {
    try {
        let resp = await fetch('api_master_barang.php?action=get');
        masterBrg = await resp.json();
    } catch (e) {
        console.error("Gagal load master barang", e);
    }
}

function renderDropdown(data) {
    let list = document.getElementById("dropdownBarang");
    list.innerHTML = "";
    if(data.length > 0) {
        list.style.display = "block";
        data.sort().forEach((item) => {
            list.innerHTML += `
                <div class="dropdown-item">
                    <span onclick="setPilihan('${item.replace(/'/g, "\\'")}')" style="flex:1">${item}</span>
                    <span class="btn-del-list" onclick="hapusHistory(event, '${item.replace(/'/g, "\\'")}')">X</span>
                </div>`;
        });
    } else {
        list.style.display = "none";
    }
}

function filterBarang() {
    let key = document.getElementById("search-box").value.toLowerCase();
    let filtered = masterBrg.filter(i => i.toLowerCase().includes(key));
    renderDropdown(filtered);
}

function tampilkanSemua() { renderDropdown(masterBrg); }

function setPilihan(val) {
    document.getElementById("search-box").value = val;
    document.getElementById("dropdownBarang").style.display = "none";
}

async function tambahKeMaster() {
    let baru = document.getElementById("inputBaru").value.trim();
    if(baru) {
        let formData = new FormData();
        formData.append('nama', baru);
        formData.append('action', 'add');

        let resp = await fetch('api_master_barang.php', {
            method: 'POST',
            body: formData
        });
        let res = await resp.json();
        if(res.status == 'success') {
            await loadMaster();
            document.getElementById("inputBaru").value = "";
            alert("Berhasil disimpan ke Database!");
            tampilkanSemua();
        } else {
            alert("Barang sudah ada dalam daftar.");
        }
    }
}

async function hapusHistory(e, item) {
    e.stopPropagation();
    if(confirm(`Hapus "${item}" dari history Database?`)) {
        let formData = new FormData();
        formData.append('nama', item);
        formData.append('action', 'delete');

        let resp = await fetch('api_master_barang.php', {
            method: 'POST',
            body: formData
        });
        let res = await resp.json();
        if(res.status == 'success') {
            await loadMaster();
            filterBarang();
        } else {
            alert('Gagal menghapus: ' + (res.message || 'unknown error'));
        }
    }
}

loadMaster();

window.onclick = function(e) {
    if (!e.target.matches('#search-box')) {
        document.getElementById("dropdownBarang").style.display = "none";
    }
}
</script>
        </div>
    </div>
</div>
</body>
</html>

