<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle hapus manifest
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    try {
        deleteDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id_hapus)]);
        deleteDocument("muatan", ['id_manifest' => $id_hapus]);
        $_SESSION['success'] = 'Manifest berhasil dihapus.';
    } catch (\Throwable $e) {
        $_SESSION['error'] = 'Gagal menghapus manifest.';
    }
    header("Location: data.php");
    exit;
}

// Initialize filter array
$filter = [];

// Sanitize and apply filters
if (!empty($_GET['kapal'])) {
    $kapal = sanitize_string($_GET['kapal']);
    $filter['kapal'] = new MongoDB\BSON\Regex($kapal, 'i');
}
if (!empty($_GET['nopol'])) {
    $nopol = sanitize_string($_GET['nopol']);
    $nopol_escaped = preg_quote($nopol, '/');
    $filter['nopol'] = new MongoDB\BSON\Regex($nopol_escaped, 'i');
}
if (!empty($_GET['tgl_mulai']) && !empty($_GET['tgl_selesai'])) {
    if (validate_date($_GET['tgl_mulai']) && validate_date($_GET['tgl_selesai'])) {
        $filter['tanggal'] = ['$gte' => $_GET['tgl_mulai'], '$lte' => $_GET['tgl_selesai']];
    }
} elseif (!empty($_GET['tgl_mulai'])) {
    if (validate_date($_GET['tgl_mulai'])) {
        $filter['tanggal'] = ['$gte' => $_GET['tgl_mulai']];
    }
} elseif (!empty($_GET['tgl_selesai'])) {
    if (validate_date($_GET['tgl_selesai'])) {
        $filter['tanggal'] = ['$lte' => $_GET['tgl_selesai']];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Manifest - CV. MANUNGGAL</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .no-data { padding: 40px; color: #999; text-align: center; }
        .badge-status {
            display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
        }
        .badge-selesai { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-menunggu { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
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
    <h3 class="header-title">📜 Riwayat Manifest Cargo</h3>
    
    <div class="filter-card" style="background: #f8fafc; padding: 18px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--border-color);">
        <form method="GET" class="filter-form" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label>Nama Kapal</label>
                <input type="text" name="kapal" class="form-control" placeholder="Cari kapal..." value="<?= e($_GET['kapal'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label>Nomor Polisi</label>
                <input type="text" name="nopol" class="form-control" placeholder="Cari nopol..." value="<?= e($_GET['nopol'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 140px;">
                <label>Dari Tanggal</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= e($_GET['tgl_mulai'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 140px;">
                <label>Sampai Tanggal</label>
                <input type="date" name="tgl_selesai" class="form-control" value="<?= e($_GET['tgl_selesai'] ?? '') ?>">
            </div>
            <div class="filter-actions" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="min-width: 90px;">🔍 Filter</button>
                <a href="data.php" class="btn btn-secondary" style="min-width: 70px;">Reset</a>
            </div>
        </form>
    </div>
    
    <div class="table-scroll-hint"><span class="hint-icon">👈</span> Geser tabel ke samping untuk melihat data lengkap <span class="hint-icon">👉</span></div>
    <div class="table-wrapper">
    <table class="table-custom">
        <thead>
            <tr>
                <th>NO</th>
                <th>TANGGAL</th>
                <th>NAMA KAPAL</th>
                <th>NOPOL</th>
                <th>TUJUAN</th>
                <th>JAM</th>
                <th>STATUS</th>
                <th>AKSI</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $docs = findDocuments("manifest", $filter, ['sort' => ['_id' => -1]]);
            
            if(count($docs) > 0) {
                foreach($docs as $row_obj) {
                    $row = (array)$row_obj;
                    $id_str = (string)$row['_id'];
                    $is_selesai = (isset($row['status']) && $row['status'] === 'Selesai');
                    $status_badge = $is_selesai 
                        ? '<span class="badge-status badge-selesai">✅ Selesai</span>' 
                        : '<span class="badge-status badge-menunggu">⏳ Menunggu</span>';
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= tgl_indo($row['tanggal'] ?? ''); ?></td>
                <td><strong><?= e($row['kapal'] ?? ''); ?></strong></td>
                <td><span style="background:#f1f5f9; padding:3px 8px; border-radius:6px; font-weight:600;"><?= e($row['nopol'] ?? ''); ?></span></td>
                <td><?= e($row['tujuan'] ?? ''); ?></td>
                <td><?= e($row['jam'] ?? ''); ?></td>
                <td><?= $status_badge; ?></td>
                <td>
                    <div class="action-btn-group">
                        <a href="preview_manifest_lama.php?id=<?= e($id_str); ?>" class="btn-pill btn-pill-view">📄 Lihat</a>
                        <a href="input_muatan.php?id=<?= e($id_str); ?>" class="btn-pill btn-pill-edit">✏️ Muatan</a>
                        <a href="?hapus=<?= e($id_str); ?>" class="btn-pill btn-pill-del" onclick="return confirm('Yakin ingin menghapus manifest ini beserta seluruh muatannya?')">🗑️</a>
                    </div>
                </td>
            </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='8' class='no-data' style='padding:30px; color:#64748b; text-align:center;'>Belum ada data manifest tersimpan di database bacil.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>

        </div>
    </div>
</div>

</body>
</html>
