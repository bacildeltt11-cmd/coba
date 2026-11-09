<?php
if (defined('KONEKSI_MONGODB_LOADED')) {
    return;
}
define('KONEKSI_MONGODB_LOADED', true);

require_once 'config.php';

try {
    $client = new MongoDB\Driver\Manager($mongodb_uri);
    $client->executeCommand($database, new MongoDB\Driver\Command(['ping' => 1]));
} catch (\Throwable $e) {
    die("Koneksi MongoDB gagal: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function insertDocument($collection, $document) {
    global $client, $database;
    $bulk = new MongoDB\Driver\BulkWrite;
    
    if (!isset($document['_id'])) {
        $document['_id'] = new MongoDB\BSON\ObjectId;
    }
    
    $bulk->insert($document);
    $client->executeBulkWrite("{$database}.{$collection}", $bulk);
    
    return (string) $document['_id'];
}

function updateDocument($collection, $filter, $update) {
    global $client, $database;
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update($filter, $update, ['multi' => false, 'upsert' => false]);
    $result = $client->executeBulkWrite("{$database}.{$collection}", $bulk);
    return $result->getModifiedCount();
}

function deleteDocument($collection, $filter) {
    global $client, $database;
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete($filter);
    $result = $client->executeBulkWrite("{$database}.{$collection}", $bulk);
    return $result->getDeletedCount();
}

function findDocuments($collection, $filter = [], $options = []) {
    global $client, $database;
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $client->executeQuery("{$database}.{$collection}", $query);
    return $cursor->toArray();
}

function findOneDocument($collection, $filter, $options = []) {
    global $client, $database;
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $client->executeQuery("{$database}.{$collection}", $query);
    $arr = $cursor->toArray();
    return count($arr) > 0 ? $arr[0] : null;
}

function aggregate($collection, $pipeline) {
    global $client, $database;
    $command = new MongoDB\Driver\Command([
        'aggregate' => $collection,
        'pipeline' => $pipeline,
        'cursor' => new stdClass()
    ]);
    $cursor = $client->executeCommand($database, $command);
    return $cursor->toArray();
}

function countDocuments($collection, $filter = []) {
    global $client, $database;
    $query = empty($filter) ? new stdClass() : $filter;
    $command = new MongoDB\Driver\Command([
        'count' => $collection,
        'query' => $query
    ]);
    $cursor = $client->executeCommand($database, $command);
    $result = $cursor->toArray();
    return $result[0]->n ?? 0;
}
?>

