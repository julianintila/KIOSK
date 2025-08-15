<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

try {
    require_once __DIR__ . '/connect.php';

    $response = [];

    $json = file_get_contents('php://input');

    if (empty($json)) throw new Exception('No data received');

    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());
    if (empty($data['kioskRegNo'])) throw new Exception('KioskRegNo is required');
    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required');

    $kioskRegNo = $data['kioskRegNo'];
    $referenceNo = $data['ReferenceNo'];
    $action = $data['action'];
    $name = $data['name'];
    $id_number = $data['id_number'];
    $discount_code = $data['discount_code'];

    if ($action === 'RequestDiscount') {
        if (empty($name)) throw new Exception('Enter name.');
        if (empty($id_number)) throw new Exception('Enter ID number.');
        if (!preg_match('/^\d+$/', $id_number)) throw new Exception('ID number must be numbers only.');

        $sql = "select * from KIOSK_DiscountRequests WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'pending' AND discount_id = :id_number AND name = :name";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo,
            ':name' => $name,
            ':id_number' => $id_number
        ];
        $result = fetch($sql, $params, $pdo);

        if ($result) throw new Exception('A discount request for this ID has already been submitted and is pending approval. Please wait for processing or contact the cashier for assistance.');

        $sql = "UPDATE KIOSK_DiscountRequests SET status = 'rejected', rejected_at = GETDATE() WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':referenceNo' => $referenceNo, ':kioskRegNo' => $kioskRegNo]);

        $sql = "INSERT INTO [KIOSK_DiscountRequests] 
            (name, discount_id, register_no, datetime, status, ReferenceNo) 
            VALUES 
            (:name, :id_number, :kioskRegNo, GETDATE(), 'pending', :referenceNo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kioskRegNo' => $kioskRegNo,
            ':referenceNo' => $referenceNo,
            ':name' => $name,
            ':id_number' => $id_number
        ]);

        $response['message'] = 'Discount request sent. Awaiting approval.';
    } elseif ($action === 'Discount') {

        $sql = "UPDATE KIOSK_DiscountRequests SET status = 'used', used_at = GETDATE() WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'pending' AND discount_id = :id_number AND name = :name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo,
            ':name' => $name,
            ':id_number' => $id_number
        ]);

        $response['message'] = 'Discount has been successfully applied.';
    } else {
        $sql = "DELETE from KIOSK_DiscountRequests WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':referenceNo' => $referenceNo, ':kioskRegNo' => $kioskRegNo]);

        $sql = "UPDATE KIOSK_TransactionItem SET DiscountCode = '' WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ]);
        $response['message'] = 'Discount removed. You may proceed with your transaction.';
    }

    $totals = recomputeRegister($kioskRegNo, $referenceNo, $pdo);

    $response = $totals;

    http_response_code(200);
    echo json_encode($response);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}
