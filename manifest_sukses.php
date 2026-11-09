<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_manifest = $_GET['id'] ?? ($_SESSION['id_manifest'] ?? null);
$h = null;
$total_ton = 0;
$muatan_count = 0;

if ($id_manifest) {
    try {
        $h_obj = findOneDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id_manifest)]);
        if ($h_obj) {
            $h = (array)$h_obj;
            $muatan_docs = findDocuments("muatan", ["id_manifest" => $id_manifest]);
            $muatan_count = count($muatan_docs);
            foreach ($muatan_docs as $m) {
                $total_ton += (float)(((array)$m)['ton'] ?? 0);
            }
        }
    } catch (\Throwable $e) {}
}

$total_display = (isset($h['total_ton_manual']) && $h['total_ton_manual'] != '0' && $h['total_ton_manual'] != '') 
    ? $h['total_ton_manual'] 
    : number_format($total_ton, 2);

unset($_SESSION['id_manifest']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selesai - CV. MANUNGGAL</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #e6f0ff, #ffffff); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px 16px; box-sizing: border-box; }
        .card-sukses { background: #fff; padding: 36px 30px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); text-align: center; width: 100%; max-width: 480px; box-sizing: border-box; }
        .icon { font-size: 54px; color: #10b981; margin-bottom: 14px; line-height: 1; }
        h2 { color: #0a4dbf; margin-bottom: 8px; font-size: 22px; font-weight: 800; }
        p.subtitle { color: #64748b; margin-bottom: 22px; line-height: 1.5; font-size: 14px; }
        .summary-box { background: #f8fafc; border-radius: 12px; padding: 18px 20px; text-align: left; margin-bottom: 24px; border-left: 4px solid var(--primary-blue); font-size: 14px; line-height: 1.8; }
        .summary-box strong { color: #1e293b; }
        .btn-group { display: flex; flex-direction: column; gap: 10px; }
        .btn-full { width: 100%; box-sizing: border-box; padding: 13px; font-weight: 700; border-radius: 10px; }
    </style>
</head>
<body>

<div class="card-sukses">
    <div class="icon">✓</div>
    <h2>Data Tersimpan!</h2>
    <p class="subtitle">Manifest Cargo telah berhasil diproses dan disimpan ke dalam database <strong>bacil</strong>.</p>
    
    <?php if ($h): ?>
    <div class="summary-box">
        <div><strong>Kapal:</strong> <?= e($h['kapal'] ?? '-') ?></div>
        <div><strong>Tanggal:</strong> <?= tgl_indo($h['tanggal'] ?? '') ?></div>
        <div><strong>Nopol:</strong> <?= e($h['nopol'] ?? '-') ?></div>
        <div><strong>Tujuan:</strong> <?= e($h['tujuan'] ?? '-') ?></div>
        <div><strong>Total Muatan:</strong> ± <?= e($total_display) ?> Ton (<?= $muatan_count ?> Item)</div>
    </div>
    <?php endif; ?>

    <div class="btn-group">
        <?php if ($id_manifest): ?>
        <a href="preview_manifest_lama.php?id=<?= urlencode($id_manifest) ?>" class="btn btn-primary btn-full">📄 Lihat / Cetak Dokumen</a>
        <?php endif; ?>
        <a href="input_keberangkatan.php" class="btn btn-success btn-full">📝 Input Manifest Baru</a>
        <a href="data.php" class="btn btn-secondary btn-full">📜 Lihat Riwayat Arsip</a>
        <a href="dashboard.php" style="color:var(--gray);text-decoration:none;font-size:13px;margin-top:4px;">← Kembali ke Dashboard</a>
    </div>
</div>

</body>
</html>

