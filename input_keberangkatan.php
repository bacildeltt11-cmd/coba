<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$edit_id = $_GET['id'] ?? null;
$edit_data = null;

if ($edit_id) {
    try {
        $obj = findOneDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($edit_id)]);
        if ($obj) $edit_data = (array)$obj;
    } catch (\Throwable $e) {}
}

// Jika tombol "Simpan" diklik
if (isset($_POST['simpan'])) {
    $kapal   = sanitize_string($_POST['kapal'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $tujuan  = sanitize_string($_POST['tujuan'] ?? '');
    $jenis   = sanitize_string($_POST['jenis'] ?? '');
    $nopol   = sanitize_string($_POST['nopol'] ?? '');
    $jam     = $_POST['jam'] ?? '';

    $errors = [];
    if (empty($kapal)) $errors[] = "Nama kapal harus diisi";
    if (empty($tanggal) || !validate_date($tanggal)) $errors[] = "Format tanggal tidak valid";
    if (empty($tujuan)) $errors[] = "Tujuan harus diisi";
    if (empty($jenis)) $errors[] = "Jenis kendaraan harus diisi";
    if (empty($nopol)) $errors[] = "Nomor polisi harus diisi";
    if (empty($jam)) $errors[] = "Jam berangkat harus diisi";

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: " . $_SERVER['PHP_SELF'] . ($edit_id ? "?id=$edit_id" : ''));
        exit;
    }

    $data = [
        "kapal" => $kapal,
        "tanggal" => $tanggal,
        "tujuan" => $tujuan,
        "jenis" => $jenis,
        "nopol" => $nopol,
        "jam" => $jam
    ];

    $cek_nopol = findOneDocument("master_nopol", ["nopol" => $nopol]);
    if (!$cek_nopol) {
        insertDocument("master_nopol", ["nopol" => $nopol]);
    }

    if ($edit_id) {
        updateDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($edit_id)], $data);
        $id_manifest = $edit_id;
    } else {
        $id_manifest = insertDocument("manifest", $data);
    }

    if ($id_manifest) {
        $_SESSION['id_manifest'] = $id_manifest;
        header("Location: input_muatan.php?id=" . urlencode($id_manifest));
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
}

// Handle tambah master dari modal (?modal=kapal/jenis/nopol)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_kapal'])) {
        $nama = sanitize_string($_POST['nama'] ?? '');
        if ($nama) {
            insertDocument("master_kapal", ["nama" => $nama]);
            $_SESSION['success'] = 'Kapal baru berhasil ditambahkan';
        }
        $url = 'input_keberangkatan.php' . ($edit_id ? '?id=' . urlencode($edit_id) : '');
        header("Location: $url");
        exit;
    }
    if (isset($_POST['add_jenis'])) {
        $kode = sanitize_string($_POST['kode'] ?? '');
        if ($kode) {
            insertDocument("master_jenis", ["kode" => $kode]);
            $_SESSION['success'] = 'Jenis kendaraan baru berhasil ditambahkan';
        }
        $url = 'input_keberangkatan.php' . ($edit_id ? '?id=' . urlencode($edit_id) : '');
        header("Location: $url");
        exit;
    }
    if (isset($_POST['add_nopol'])) {
        $nopol = sanitize_string($_POST['nopol'] ?? '');
        if ($nopol) {
            insertDocument("master_nopol", ["nopol" => $nopol]);
            $_SESSION['success'] = 'Nomor polisi baru berhasil ditambahkan';
        }
        $url = 'input_keberangkatan.php' . ($edit_id ? '?id=' . urlencode($edit_id) : '');
        header("Location: $url");
        exit;
    }
}

$master_kapal = findDocuments("master_kapal", []);
$kapals = [];
foreach($master_kapal as $k) {
    $arr = (array)$k;
    if (!empty($arr['nama'])) $kapals[] = $arr['nama'];
}
if(empty($kapals)) { $kapals = ['KM. DHARMA FERRY II', 'KM. DHARMA FERRY III', 'KM. KIRANA']; }

$master_jenis = findDocuments("master_jenis", []);
$jeniss = [];
foreach($master_jenis as $j) {
    $arr = (array)$j;
    if (!empty($arr['kode'])) $jeniss[] = $arr['kode'];
}
if(empty($jeniss)) { $jeniss = ['TB', 'TS', 'LCT']; }

$master_nopol = findDocuments("master_nopol", []);
$nopols = [];
foreach($master_nopol as $n) {
    $arr = (array)$n;
    if (!empty($arr['nopol'])) $nopols[] = $arr['nopol'];
}
if(empty($nopols)) { $nopols = ['H 8454 QQ', 'H 1316 PH', 'H 1370 TA', 'H 8470 QQ', 'H 9773 BQ', 'H 8211 BA', 'AA 8519 OF', 'BA 9937 FU', 'H 9564 CF', 'B 9326 WAK']; }

$button_label = $edit_id ? "Simpan Perubahan" : "Selanjutnya →";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargo Manifest - Keberangkatan</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { display: flex; gap: 30px; }
        .form-container { width: 60%; position: relative; }
        .header-right { width: 40%; background: linear-gradient(160deg, var(--primary-blue), var(--dark-blue)); border-radius: 14px; padding: 30px; color: var(--white); }
        .info-box { margin-top: 22px; background: rgba(255,255,255,0.18); padding: 15px; border-radius: 10px; font-size: 14px; line-height: 1.6; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.55);
            display: flex; align-items: center; justify-content: center; z-index: 1000;
            backdrop-filter: blur(5px);
        }
        .modal {
            background: #fff; padding: 0; border-radius: 20px; width: 440px;
            max-width: 92vw; box-shadow: 0 24px 80px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(135deg, #0a4dbf, #003b8e);
            padding: 22px 28px; text-align: center;
        }
        .modal-header h4 { margin: 0; font-size: 17px; font-weight: 700; color: #fff; }
        .modal-body { padding: 28px; }
        .modal-footer { display: flex; gap: 10px; padding: 0 28px 22px; flex-direction: column; }
        .btn-modal-submit {
            width: 100%; padding: 13px; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #0a4dbf 0%, #1565c0 50%, #1e88e5 100%);
            cursor: pointer;
        }
        .modal-close {
            display: block; text-align: center; padding: 10px; color: #718096;
            text-decoration: none; font-size: 14px; font-weight: 600;
            border-radius: 10px; background: #f1f5f9;
        }
        .btn-tambah {
            background: linear-gradient(135deg, #0a4dbf, #1e88e5); color: #fff;
            padding: 6px 14px; border-radius: 8px; font-size: 18px; font-weight: 700;
            border: none; cursor: pointer; line-height: 1.2; min-width: 40px; text-align: center;
        }
        .select-group { display: flex; align-items: center; gap: 8px; }
        .select-group select { flex: 1; }

        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .form-container, .header-right { width: 100%; }
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
                <!-- FORM INPUT DI SEBELAH KIRI -->
                <div class="form-container">
                    <h3 class="header-title"><?= $edit_id ? "Edit Data Keberangkatan" : "Data Keberangkatan" ?></h3>

                    <form method="POST" action="">
                        <?php if ($edit_id): ?>
                            <input type="hidden" name="id" value="<?= e($edit_id) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Nama Kapal</label>
                            <div class="select-group">
                                <select name="kapal" class="form-control" required>
                                    <?php foreach($kapals as $k):
                                        $selected = ($edit_data && ($edit_data['kapal'] ?? '') == $k) ? 'selected' : '';
                                    ?>
                                        <option value="<?= e($k) ?>" <?= $selected ?>><?= e($k) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="?modal=kapal<?= $edit_id ? "&id=" . e($edit_id) : '' ?>" class="btn-tambah" title="Tambah Kapal Baru">+</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Keberangkatan</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= e($edit_data['tanggal'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tujuan Keberangkatan</label>
                            <input type="text" name="tujuan" class="form-control" value="<?= $edit_data ? e($edit_data['tujuan'] ?? '') : 'Semarang - Ketapang' ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kendaraan</label>
                            <div class="select-group">
                                <select name="jenis" class="form-control" required>
                                    <?php foreach($jeniss as $j):
                                        $selected = ($edit_data && ($edit_data['jenis'] ?? '') == $j) ? 'selected' : '';
                                    ?>
                                        <option value="<?= e($j) ?>" <?= $selected ?>><?= e($j) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="?modal=jenis<?= $edit_id ? "&id=" . e($edit_id) : '' ?>" class="btn-tambah" title="Tambah Jenis Baru">+</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nomor Polisi (Nopol)</label>
                            <div class="select-group">
                                <select name="nopol" class="form-control" required>
                                    <?php foreach($nopols as $n):
                                        $selected = ($edit_data && ($edit_data['nopol'] ?? '') == $n) ? 'selected' : '';
                                    ?>
                                        <option value="<?= e($n) ?>" <?= $selected ?>><?= e($n) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="?modal=nopol<?= $edit_id ? "&id=" . e($edit_id) : '' ?>" class="btn-tambah" title="Tambah Nopol Baru">+</a>
                            </div>
                            <small style="color: var(--gray); font-size: 12px; display: block; margin-top: 4px;">Pilih nomor polisi dari daftar atau tambah yang baru.</small>
                        </div>
                        <div class="form-group">
                            <label>Jam Berangkat</label>
                            <input type="time" name="jam" class="form-control" value="<?= $edit_data ? e($edit_data['jam'] ?? '') : date('H:i') ?>" required>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                            <button type="submit" name="simpan" class="btn btn-primary" style="flex: 1; min-width: 140px;">
                                <?= $button_label ?>
                            </button>
                            <?php if($edit_id): ?>
                                <a href="input_muatan.php?id=<?= urlencode($edit_id) ?>" class="btn btn-secondary" style="min-width: 100px;">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- KOTAK BIRU INFO DI SEBELAH KANAN -->
                <div class="header-right">
                    <h1>CARGO MANIFEST</h1>
                    <h2>Data Keberangkatan</h2>
                    <div class="info-box">
                        <strong>Petunjuk:</strong><br>
                        • Pastikan data keberangkatan diisi lengkap<br>
                        • Nomor polisi harus sesuai STNK<br>
                        • Jam berangkat sesuai jadwal pelabuhan<br>
                        • Pastikan kembali agar data tidak salah saat menginput<br> 
                        • Manifest CV. MANUNGGAL Pelabuhan (Database: bacil)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kapal -->
<div id="modal-kapal" class="modal-overlay" style="display:none;" onclick="if (event.target === this) this.style.display='none'">
    <div class="modal" onclick="event.stopImmediatePropagation()">
        <div class="modal-header">
            <h4>🚢 Tambah Kapal Baru</h4>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Kapal</label>
                    <input type="text" name="nama" class="form-control" placeholder="Contoh: KM. DHARMA FERRY IV" required>
                </div>
                <div class="modal-footer" style="padding-top: 10px; margin-top: 10px;">
                    <button type="submit" name="add_kapal" class="btn-modal-submit">Simpan Kapal</button>
                    <a href="javascript:void(0)" onclick="document.getElementById('modal-kapal').style.display='none'" class="modal-close">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Jenis -->
<div id="modal-jenis" class="modal-overlay" style="display:none;" onclick="if (event.target === this) this.style.display='none'">
    <div class="modal" onclick="event.stopImmediatePropagation()">
        <div class="modal-header">
            <h4>🚚 Tambah Jenis Kendaraan Baru</h4>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Kode Jenis</label>
                    <input type="text" name="kode" class="form-control" placeholder="Contoh: TB / TS / LCT" required>
                </div>
                <div class="modal-footer" style="padding-top: 10px; margin-top: 10px;">
                    <button type="submit" name="add_jenis" class="btn-modal-submit">Simpan Jenis</button>
                    <a href="javascript:void(0)" onclick="document.getElementById('modal-jenis').style.display='none'" class="modal-close">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nopol -->
<div id="modal-nopol" class="modal-overlay" style="display:none;" onclick="if (event.target === this) this.style.display='none'">
    <div class="modal" onclick="event.stopImmediatePropagation()">
        <div class="modal-header">
            <h4>🚛 Tambah Nomor Polisi Baru</h4>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nomor Polisi (Nopol)</label>
                    <input type="text" name="nopol" class="form-control" placeholder="Contoh: H 1234 AB" required>
                </div>
                <div class="modal-footer" style="padding-top: 10px; margin-top: 10px;">
                    <button type="submit" name="add_nopol" class="btn-modal-submit">Simpan Nopol</button>
                    <a href="javascript:void(0)" onclick="document.getElementById('modal-nopol').style.display='none'" class="modal-close">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const m = params.get('modal');
    if (m === 'kapal') {
        document.getElementById('modal-kapal').style.display = 'flex';
    } else if (m === 'jenis') {
        document.getElementById('modal-jenis').style.display = 'flex';
    } else if (m === 'nopol') {
        document.getElementById('modal-nopol').style.display = 'flex';
    }
});
</script>
</body>
</html>

