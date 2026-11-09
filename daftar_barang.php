<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle tambah barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_barang'])) {
    $nama = sanitize_string($_POST['nama_barang'] ?? '');
    $pcs = intval($_POST['pcs'] ?? 0);
    if (!empty($nama)) {
        $exist = findOneDocument("master_barang", ['nama' => $nama]);
        if (!$exist) {
            insertDocument("master_barang", ['nama' => $nama, 'pcs' => $pcs]);
            $_SESSION['success_msg'] = 'Daftar barang berhasil ditambahkan';
        } else {
            $_SESSION['error'] = 'Barang sudah terdaftar.';
        }
    } else {
        $_SESSION['error'] = 'Nama barang tidak boleh kosong.';
    }
    header("Location: daftar_barang.php");
    exit;
}

// Handle hapus barang
if (isset($_GET['hapus'])) {
    $nama = sanitize_string($_GET['hapus']);
    if (!empty($nama)) {
        deleteDocument("master_barang", ['nama' => $nama]);
        $_SESSION['success_msg'] = 'Barang berhasil dihapus dari daftar.';
    }
    header("Location: daftar_barang.php");
    exit;
}

// Handle update pcs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_barang'])) {
    $nama = sanitize_string($_POST['nama_barang'] ?? '');
    $pcs  = intval($_POST['pcs'] ?? 0);
    if (!empty($nama)) {
        $existing = findOneDocument("master_barang", ['nama' => $nama]);
        if ($existing) {
            updateDocument("master_barang", ['nama' => $nama], ['$set' => ['pcs' => $pcs]]);
            $_SESSION['success_msg'] = "PCS untuk barang '$nama' berhasil diubah.";
        } else {
            $_SESSION['error'] = "Barang tidak ditemukan untuk diedit.";
        }
    } else {
        $_SESSION['error'] = "Nama barang tidak boleh kosong.";
    }
    header("Location: daftar_barang.php");
    exit;
}

// Ambil data master barang
$docs = findDocuments("master_barang", [], ['sort' => ['nama' => 1]]);
$barang_list = [];
foreach ($docs as $d) {
    $arr = (array)$d;
    if (!empty($arr['nama'])) {
        $barang_list[] = ['nama' => $arr['nama'], 'pcs' => $arr['pcs'] ?? 0];
    }
}

// Jika database baru kosong, isi default items
if (empty($barang_list)) {
    $default_names = ["Alpukat","Apel","Bawang Putih","Bawang Goreng","Bombay","Brambang","Cabe","Emping","Garam","Gula","Jipan","Kacang Hijau","Kacang Tanah","Kemiri","Kentang","Kertas","Ketan","Kol","Krupuk","Kunir","Mangga","Plastik","Rempah","Salak","Sawi","Telur","Tomat","Terong","Wortel","Trasi","Kurma","Keluak","Kacang kupas"];
    foreach ($default_names as $name) {
        insertDocument("master_barang", ['nama' => $name, 'pcs' => 0]);
        $barang_list[] = ['nama' => $name, 'pcs' => 0];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Barang - CV. MANUNGGAL</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container-barang { background: #fff; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); margin-top: 20px; }
        .success-popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%); background: #d4edda; color: #155724; padding: 22px 35px; border-radius: 14px; box-shadow: 0 15px 40px rgba(0,0,0,0.25); z-index: 9999; text-align: center; font-size: 17px; font-weight: 700; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
        .modal { background: #fff; padding: 25px; border-radius: 14px; width: 400px; max-width: 90%; }
        .modal-header h4 { margin: 0 0 15px 0; color: #0a4dbf; font-size: 18px; }
    </style>
</head>
<body>
<div class="layout-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'top_nav.php'; ?>
        <div class="content-wrapper">

        <h3 class="header-title" style="margin-bottom:10px;">📦 Master Barang (Database: bacil)</h3>
        <p style="color:#666; margin-bottom:20px;">Kelola stok dan daftar barang cargo.</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" style="padding:12px; border-radius:8px; margin-bottom:16px;">
                <?= e($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success" style="padding:12px; border-radius:8px; margin-bottom:16px;">
                <?= e($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <div class="container-barang">
            <h4 style="margin-bottom:15px; color:#0a4dbf;">➕ Tambah Barang Baru</h4>
            <form method="POST" style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
                <input type="text" name="nama_barang" class="form-control" placeholder="Nama Barang Baru" style="flex:2; min-width:180px;" required>
                <input type="number" name="pcs" class="form-control" placeholder="PCS (angka)" min="0" style="flex:1; min-width:100px;" value="0">
                <button type="submit" name="add_barang" class="btn btn-primary" style="min-width:120px;">+ Tambah</button>
            </form>

            <div style="margin-bottom: 16px;">
                <input type="text" id="search-barang" class="form-control" placeholder="🔍 Cari nama barang di daftar..." style="max-width:320px;">
            </div>

            <?php if (!empty($barang_list)): ?>
            <div class="table-wrapper">
            <table class="table-custom" id="barang-table">
                <thead>
                    <tr>
                        <th style="width:10%;">NO</th>
                        <th style="text-align:left; padding-left:20px;">NAMA BARANG</th>
                        <th style="width:20%;">PCS (STOK)</th>
                        <th style="width:20%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($barang_list as $item): ?>
                    <tr data-name="<?= strtolower(e($item['nama'])) ?>">
                        <td><?= $no++; ?></td>
                        <td style="text-align:left; padding-left:20px; font-weight:600;"><?= e($item['nama']) ?></td>
                        <td><span style="background:#e0f2fe; padding:4px 10px; border-radius:6px; font-weight:700; color:#0369a1;"><?= e($item['pcs']) ?></span></td>
                        <td>
                            <div class="action-btn-group">
                                <a href="javascript:void(0);" class="btn-pill btn-pill-edit edit" data-nama="<?= e($item['nama']) ?>" data-pcs="<?= e($item['pcs']) ?>" title="Edit PCS">✏️ Edit</a>
                                <a href="?hapus=<?= urlencode($item['nama']) ?>" class="btn-pill btn-pill-del del" title="Hapus Barang" onclick="return confirm('Hapus \'<?= e($item['nama']) ?>\' dari daftar?')">🗑️ Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p style="color:#888; padding:20px; text-align:center;">Belum ada daftar barang.</p>
            <?php endif; ?>
        </div>

        <!-- Modal Edit PCS -->
        <div id="edit_modal" class="modal-overlay" style="display:none;" onclick="if (event.target === this) this.style.display='none'">
            <div class="modal" onclick="event.stopImmediatePropagation()">
                <div class="modal-header">
                    <h4>✏️ Edit Stok Barang</h4>
                </div>
                <div class="modal-body">
                    <form id="edit_form" method="POST">
                        <div class="form-group">
                            <label>Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" placeholder="Nama Barang" value="" required readonly style="background:#f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label>Jumlah PCS</label>
                            <input type="number" name="pcs" class="form-control" placeholder="PCS (angka)" min="0" required value="">
                        </div>
                        <div style="display:flex; gap:10px; margin-top:20px;">
                            <button type="submit" name="update_barang" class="btn btn-primary" style="flex:1;">Simpan Perubahan</button>
                            <button type="button" id="edit_close" class="btn btn-secondary">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var nama = this.getAttribute('data-nama');
        var pcs = this.getAttribute('data-pcs');
        var modal = document.getElementById('edit_modal');
        var form = document.getElementById('edit_form');
        if (!modal || !form) return;
        form.querySelector('input[name="nama_barang"]').value = nama;
        form.querySelector('input[name="pcs"]').value = pcs;
        modal.style.display = 'flex';
    });
});

var closeBtn = document.getElementById('edit_close');
if (closeBtn) {
    closeBtn.addEventListener('click', function(){
        document.getElementById('edit_modal').style.display = 'none';
    });
}

document.getElementById('search-barang')?.addEventListener('input', function(e){
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#barang-table tbody tr');
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if(name && name.includes(searchTerm)) { row.style.display = ''; }
        else { row.style.display = 'none'; }
    });
});
</script>
</body>
</html>
