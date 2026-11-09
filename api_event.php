<?php
ob_start();
error_reporting(0);

require_once 'koneksi_mongodb.php';
require_once 'functions.php';

header('Content-Type: application/json; charset=utf-8');

function sendJson($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendJson(['error' => 'ID required'], 400);
        }

        try {
            $event = findOneDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id)]);
            if ($event) {
                $arr = (array)$event;
                sendJson([
                    'success' => true,
                    'data' => [
                        'id' => (string)$arr['_id'],
                        'kapal' => $arr['kapal'] ?? '',
                        'tanggal' => $arr['tanggal'] ?? '',
                        'tujuan' => $arr['tujuan'] ?? '',
                        'jenis' => $arr['jenis'] ?? '',
                        'nopol' => $arr['nopol'] ?? '',
                        'jam' => $arr['jam'] ?? '',
                        'status' => $arr['status'] ?? 'Menunggu'
                    ]
                ]);
            } else {
                sendJson(['error' => 'Event not found'], 404);
            }
        } catch (Exception $e) {
            sendJson(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $action = $input['action'] ?? '';

        if ($action === 'add_event' || $action === 'edit_event') {
            $isEdit = ($action === 'edit_event');
            $id = $isEdit ? ($input['event_id'] ?? null) : null;

            if ($isEdit && !$id) {
                sendJson(['error' => 'Event ID required'], 400);
            }

            $kapal = sanitize_string($input['kapal'] ?? '');
            $tanggal = $input['tanggal'] ?? '';
            $tujuan = sanitize_string($input['tujuan'] ?? '');
            $jenis = sanitize_string($input['jenis'] ?? '');
            $nopol = sanitize_string($input['nopol'] ?? '');
            $jam = $input['jam'] ?? '';

            $errors = [];
            if (empty($kapal)) $errors[] = "Nama kapal harus diisi";
            if (empty($tanggal) || !validate_date($tanggal)) $errors[] = "Format tanggal tidak valid";
            if (empty($tujuan)) $errors[] = "Tujuan harus diisi";
            if (empty($jenis)) $errors[] = "Jenis kendaraan harus diisi";
            if (empty($nopol)) $errors[] = "Nomor polisi harus diisi";
            if (empty($jam)) $errors[] = "Jam berangkat harus diisi";

            if (empty($errors)) {
                $data = [
                    "kapal" => $kapal,
                    "tanggal" => $tanggal,
                    "tujuan" => $tujuan,
                    "jenis" => $jenis,
                    "nopol" => $nopol,
                    "jam" => $jam
                ];

                if ($isEdit) {
                    $result = updateDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($id)], $data);
                    sendJson(['success' => true, 'message' => 'Jadwal berhasil diperbarui']);
                } else {
                    $new_id = insertDocument("manifest", $data);
                    if ($new_id) {
                        sendJson(['success' => true, 'message' => 'Jadwal berhasil ditambahkan', 'id' => $new_id]);
                    } else {
                        sendJson(['error' => 'Gagal menambahkan jadwal'], 500);
                    }
                }
            } else {
                sendJson(['error' => implode('<br>', $errors)], 400);
            }
        }

        if ($action === 'delete_event') {
            $event_id = $input['event_id'] ?? null;
            if (!$event_id) {
                sendJson(['error' => 'Event ID required'], 400);
            }
            $result = deleteDocument("manifest", ['_id' => new MongoDB\BSON\ObjectId($event_id)]);
            deleteDocument("muatan", ['id_manifest' => $event_id]);
            sendJson(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
        }
        break;

    default:
        sendJson(['error' => 'Method not allowed'], 405);
}
?>

