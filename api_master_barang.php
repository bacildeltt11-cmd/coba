<?php
require_once 'koneksi_mongodb.php';
require_once 'functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'get') {
    $docs = findDocuments("master_barang", []);
    $names = [];
    foreach ($docs as $d) {
        $arr = (array)$d;
        if (!empty($arr['nama'])) {
            $names[] = $arr['nama'];
        }
    }
    
    // Jika masih kosong, kembalikan daftar default
    if (empty($names)) {
        $names = ["Alpukat","Apel","Bawang Putih","Bawang Goreng","Bombay","Brambang","Cabe","Emping","Garam","Gula","Jipan","Kacang Hijau","Kacang Tanah","Kemiri","Kentang","Kertas","Ketan","Kol","Krupuk","Kunir","Mangga","Plastik","Rempah","Salak","Sawi","Telur","Tomat","Terong","Wortel","Trasi","Kurma","Keluak","Kacang kupas"];
    }
    
    echo json_encode($names);
} elseif ($action == 'add') {
    $nama = $_POST['nama'] ?? ($_GET['nama'] ?? '');
    if (!empty($nama)) {
        $nama = sanitize_string($nama);
        $exist = findOneDocument("master_barang", ['nama' => $nama]);
        if (!$exist) {
            insertDocument("master_barang", ['nama' => $nama, 'pcs' => 0]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'exists']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nama barang required']);
    }
} elseif ($action == 'delete') {
    $nama = $_POST['nama'] ?? ($_GET['nama'] ?? '');
    if (!empty($nama)) {
        $nama = sanitize_string($nama);
        deleteDocument("master_barang", ['nama' => $nama]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nama barang required']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>

