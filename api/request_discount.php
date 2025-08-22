<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'message' => 'An error occurred',
    'data' => []
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($response);
    exit();
}

try {
    require_once __DIR__ . '/connect.php';

    $json = file_get_contents('php://input');

    if (empty($json)) throw new Exception('No data received', 406);

    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());
    if (!$kioskRegNo) throw new Exception('KioskRegNo is required');
    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required', 406);

    $referenceNo = $data['ReferenceNo'];
    $action = $data['Action'] ?? null;
    $name = $data['name'] ?? null;
    $id_number = $data['id_number'] ?? null;
    $discount_code = $data['discount_code'] ?? null;

    $message = null;

    $pdo->beginTransaction();

    if ($action === 'RequestDiscount') {
        $hasError = false;

        if (!$name) {
            $response['data'][]['name'] = 'Enter name of the card holder';
            $hasError = true;
        }
        if (!$id_number) {
            $response['data'][]['id_number'] = 'Enter discount ID number';
            $hasError = true;
        } elseif (!preg_match('/^\d+$/', $id_number)) {
            $response['data'][]['id_number'] = 'ID number must be numeric only';
            $hasError = true;
        }


        if ($hasError) {
            http_response_code(406);
            throw new Exception('Invalid input data');
        }

        $sql = "select * from KIOSK_DiscountRequests WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'pending' AND discount_id = :id_number AND name = :name";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo,
            ':name' => $name,
            ':id_number' => $id_number
        ];
        $result = fetch($sql, $params, $pdo);

        if ($result) throw new Exception('A discount request for this ID has already been submitted and is pending approval. Please wait for processing or contact the cashier for assistance.', 406);

        $sql = "UPDATE KIOSK_DiscountRequests SET status = 'rejected', rejected_at = GETDATE() WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status <> 'rejected'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':referenceNo' => $referenceNo, ':kioskRegNo' => $kioskRegNo]);

        $sql = "UPDATE KIOSK_TransactionItem SET DiscountCode = '' WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ]);

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

        $message = 'Discount request sent. Awaiting approval.';
        http_response_code(201);
    } elseif ($action === 'Discount') {
        if (!$discount_code) {
            $response['data'][]['discount_code'] = 'Discount code is required.';
            throw new Exception('discount code is required', 406);
        }

        $sql = "SELECT * FROM KIOSK_DiscountRequests WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'accepted' AND discount_code = :discount_code";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo,
            ':discount_code' => $discount_code
        ];
        $result = fetch($sql, $params, $pdo);

        if (!$result) {
            $response['data'][]['discount_code'] = 'Discount code is invalid or expired.';
            throw new Exception('Discount code is invalid or expired.', 406);
        }

        $sql = "UPDATE KIOSK_DiscountRequests SET status = 'used', used_at = GETDATE() WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'accepted' AND discount_code = :discount_code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo,
            ':discount_code' => $discount_code
        ]);

        $sql = "UPDATE KIOSK_TransactionItem SET DiscountCode = :discount_type WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':discount_type' => $result->discount_type,
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ]);

        $sql = "SELECT * FROM ReasonCode WHERE Code = :reasonCode";
        $params = [':reasonCode' => $result->discount_type];
        $reason = fetch($sql, $params, $pdo);
        if (!$reason) throw new Exception('Invalid discount type.', 406);

        $message = $reason->Description
            ? $reason->Description . ' has been successfully applied.'
            : 'Discount has been successfully applied.';

        http_response_code(200);
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
        $message = 'Discount removed. You may proceed with your transaction.';
        http_response_code(200);
    }

    $totals = recomputeRegister($kioskRegNo, $referenceNo, $pdo);

    $pdo->commit();

    $response['success'] = true;
    $response['data'] = $totals;
    $response['message'] = $message;
} catch (\Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
