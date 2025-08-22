<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'message' => 'An error occurred',
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode($response);
    exit();
}

try {
    require_once __DIR__ . '/connect.php';
    $queue_prefix = $config['queue_prefix'] ?? null;

    if (!$queue_prefix) throw new Exception('Queue prefix is not set in the configuration', 500);
    if (!$kioskRegNo) throw new Exception('KioskRegNo is required', 500);

    $sql = "SELECT MAX(CAST(SUBSTRING(ReferenceNo, 2, LEN(ReferenceNo) - 1) AS INT)) AS nextRef FROM KIOSK_TransactionItem WHERE KioskRegNo = :kioskRegNo";
    $params = [':kioskRegNo' => $kioskRegNo];
    $result = fetch($sql, $params, $pdo);

    $ref = $result->nextRef ? $result->nextRef + 1 : 1;

    $formattedRef = str_pad($ref, 7, '0', STR_PAD_LEFT);
    $formattedRef = $queue_prefix . $formattedRef;

    http_response_code(200);
    $response = [
        'success' => true,
        'data' => [
            'referenceNo' => $formattedRef
        ],
        'message' => 'Reference number generated successfully.'
    ];
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
    $response['success'] = false;
}

echo json_encode($response);
exit();
