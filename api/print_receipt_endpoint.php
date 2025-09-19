<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/print_receipt.php';

$response = [
    'success' => false,
    'message' => 'An errot occurred',
    'data' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($response);
    exit();
}

try {
    $json = file_get_contents('php://input');
    if (empty($json)) throw new Exception('No data received');

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());

    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required');
    if (empty($data['KioskRegNo'])) throw new Exception('KioskRegNo is required');

    $result = printReceipt($data['ReferenceNo'], $data['KioskRegNo']);

    if (!is_array($result) || !isset($result['success']) || !$result['success']) {
        throw new Exception('Failed to generate receipt');
    }

    $response['success'] = true;
    $response['message'] = 'Receipt generated successfully';
    $response['data'] = [
        'filepath' => $result['filepath']
    ];
    http_response_code(200);
} catch (Exception $e) {
    error_log("Receipt generation error: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
