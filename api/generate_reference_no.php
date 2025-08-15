<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit();
}

try {
    require_once __DIR__ . '/connect.php';
    $queue_prefix = $config['queue_prefix'];

    $response = [];

    $kioskRegNo = $_GET['KioskRegNo'] ?? null;

    if (!$kioskRegNo) throw new Exception('KioskRegNo is required');

    $sql = "SELECT MAX(CAST(SUBSTRING(ReferenceNo, 2, LEN(ReferenceNo) - 1) AS INT)) AS nextRef FROM KIOSK_TransactionItem WHERE KioskRegNo = :kioskRegNo";
    $params = [':kioskRegNo' => $kioskRegNo];
    $result = fetch($sql, $params, $pdo);

    $ref = $result->nextRef ? $result->nextRef + 1 : 1;

    $formattedRef = str_pad($ref, 7, '0', STR_PAD_LEFT);
    $formattedRef = $queue_prefix . $formattedRef;

    http_response_code(200);
    echo json_encode(['ReferenceNo' => $formattedRef]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}
