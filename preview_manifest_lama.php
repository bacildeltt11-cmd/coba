<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    header("Location: data.php");
    exit;
}
$id_manifest = $_GET['id'];

// Ambil data header kapal berdasarkan ID di URL
$h_obj = findOneDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id_manifest)]);
$h = $h_obj ? (array)$h_obj : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Manifest - CV. MANUNGGAL</title>
    <link rel="stylesheet" href="style.css">
<style>
@page { size: A4 portrait; margin: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg-color) !important; margin: 0; padding: 0; }
.kertas { background: #fff; padding: 40px; max-width: 850px; margin: 0 auto 30px auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); border: 1px solid #ddd; }
.header-box { background: #0a4dbf; color: #fff; padding: 26px; text-align: center; border-radius: 12px; margin-bottom: 25px; }
.header-box h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 2px; }
.header-box h2 { margin: 6px 0 0; font-size: 13px; color: #ffcc80; font-weight: normal; letter-spacing: 3px; }
.info { line-height: 2; margin-bottom: 25px; background: #fff; padding: 20px; border-left: 6px solid #0a4dbf; border-radius: 10px; border: 1px solid #eee; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th { background: #0a4dbf; color: #fff; border: 1px solid #000; padding: 10px; font-size: 12px; }
th, td { border: 1px solid #000; padding: 9px; text-align: center; font-size: 13px; }
.total-row td { font-weight: bold; background: #fff3e0; }
.footer { margin-top: 50px; display: flex; justify-content: flex-end; }
.footer-content { text-align: center; }
.hormat { margin: 15px 0 65px 0; }
.actions { margin-top: 30px; display: flex; justify-content: center; gap: 12px; padding-bottom: 40px; flex-wrap: wrap; }
.btn-action-modern { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; font-size: 14px; font-weight: 700; border-radius: 10px; text-decoration: none; border: none; cursor: pointer; }
.btn-act-back { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.btn-act-print { background: #eff6ff; color: #1d4ed8; border: 1.5px solid #93c5fd; }
.btn-act-pdf { background: #fff; color: #dc2626; border: 1.5px solid #fca5a5; }

@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .actions, .sidebar, .btn-toggle, .top-nav, .mobile-bottom-nav, .table-scroll-hint { display:none !important; }
    body { background: #fff !important; padding: 0; }
    .main-content { padding: 0 !important; margin: 0 !important; }
    .kertas { box-shadow: none; border: none; padding: 20px; width: 100%; max-width: 100%; }
    .header-box { background: #0a4dbf !important; color: #fff !important; }
    th { background: #0a4dbf !important; color: #fff !important; }
    .total-row td { background: #fff3e0 !important; }
}
</style>
</head>
<body>

<div class="layout-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'top_nav.php'; ?>
        <div class="content-wrapper">

        <div class="kertas">
            <div class="header-box">
                <h1>CV. MANUNGGAL</h1>
                <h2>CARGO MANIFEST</h2>
            </div>

            <div class="info">
                <div style="display:grid;grid-template-columns:max-content max-content 1fr;gap:5px 10px">
                    <div>Nama Kapal</div><div>:</div><div><?= e($h['kapal'] ?? '-') ?></div>
                    <div>Tanggal</div><div>:</div><div><?= tgl_indo($h['tanggal'] ?? '') ?></div>
                    <div>Tujuan</div><div>:</div><div><?= e($h['tujuan'] ?? '-') ?></div>
                    <div>Jenis</div><div>:</div><div><?= e($h['jenis'] ?? '-') ?></div>
                    <div>Nopol</div><div>:</div><div><?= e($h['nopol'] ?? '-') ?></div>
                    <div>Jam</div><div>:</div><div><?= e($h['jam'] ?? '-') ?> WIB</div>
                </div>
            </div>

            <div class="table-scroll-hint"><span class="hint-icon">👈</span> Geser tabel ke samping untuk melihat data lengkap <span class="hint-icon">👉</span></div>
            <div class="table-wrapper">
            <table>
            <thead>
            <tr>
                <th>NO</th>
                <th>NAMA BARANG</th>
                <th>PCS</th>
                <th>TON</th>
                <th>VOLUME</th>
            </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_ton = 0;
                $muatan_docs = findDocuments("muatan", ["id_manifest" => $id_manifest]);
                foreach($muatan_docs as $row_obj){
                    $row = (array)$row_obj;
                    $total_ton += (float)($row['ton'] ?? 0);
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td style="text-align:left; padding-left:12px; font-weight:600;"><?= e($row['nama_barang']) ?></td>
                    <td><?= e($row['pcs'] ?? 0) ?></td>
                    <td><strong><?= e($row['ton']) ?></strong></td>
                    <td><?= isset($row['volume']) && $row['volume'] !== '' ? e($row['volume']) : "-" ?></td>
                </tr>
                <?php }
                if ($no === 1) {
                    echo "<tr><td colspan='5' class='no-data' style='padding:20px;color:#94a3b8;'>Belum ada muatan tercatat.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td>± <?php 
                    $total_ton_display = (isset($h['total_ton_manual']) && $h['total_ton_manual'] != '0' && $h['total_ton_manual'] != '') ? $h['total_ton_manual'] : $total_ton;
                    echo $total_ton_display; 
                ?> Ton</td>
                <td></td>
            </tr>
            </tfoot>
            </table>
            </div>

            <div class="footer">
                <div class="footer-content">
                    <div>Semarang, <?= tgl_indo($h['tanggal'] ?? '') ?></div>
                    <div class="hormat">Hormat Kami,</div>
                    <strong>CV. MANUNGGAL</strong>
                </div>
            </div>

            <div class="actions">
                <a href="data.php" class="btn-action-modern btn-act-back">
                    ← Kembali ke Riwayat
                </a>
                <button type="button" class="btn-action-modern btn-act-print" onclick="window.print()">
                    🖨️ Cetak (Browser)
                </button>
                <a href="cetak_pdf.php?id=<?= e($id_manifest) ?>" class="btn-action-modern btn-act-pdf" target="_blank">
                    📄 Download PDF
                </a>
            </div>
        </div>

        </div>
    </div>
</div>

</body>
</html>

