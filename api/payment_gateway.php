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
    if (!$kioskRegNo) throw new Exception('KioskRegNo is required');
    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required');
    if (empty($data['Status'])) throw new Exception('Status is required and must be "pay" or "checking"');

    $referenceNo = $data['ReferenceNo'];
    $status = $data['Status'];

    if ($status == 'pay') {
        if (empty($data['PaymentType'])) throw new Exception('PaymentType is required');
        $paymentType = $data['PaymentType'];
        // CreditDebit
        // GenericMerchantQR-qr:qrph
        // ZeroPayment
        $subtotal = $data['subtotal'] ?? 0;
        $service_charge = $data['service_charge'] ?? 0;
        $total = $data['total'] ?? 0;
        $CustomerName = '';
        $IDNumber = '';

        $sql = "SELECT * FROM KIOSK_DiscountRequests WHERE ReferenceNo = :referenceNo and register_no = :kioskRegNo AND status = 'used'";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ];
        $result = fetch($sql, $params, $pdo);
        if ($result) {
            $CustomerName = $result->name;
            $IDNumber = $result->discount_id;
        }

        $sql = "SELECT * FROM KIOSK_TransactionHeader WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ];
        $result = fetch($sql, $params, $pdo);

        if ($result) {
            $sql = "UPDATE KIOSK_TransactionHeader SET CustomerName = :CustomerName, IDNumber = :IDNumber, SubTotal = :subtotal, ServiceCharge = :service_charge, TotalDue = :total, PaymentType = :paymentType WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':CustomerName' => $CustomerName,
                ':IDNumber' => $IDNumber,
                ':referenceNo' => $referenceNo,
                ':kioskRegNo' => $kioskRegNo,
                ':subtotal' => $subtotal,
                ':service_charge' => $service_charge,
                ':total' => $total,
                ':paymentType' => $paymentType
            ]);
        }

        $sql = "INSERT INTO [KIOSK_TransactionHeader] 
            ([KioskRegNo], [ReferenceNo], [CustomerName], [IDNumber], [SubTotal], [ServiceCharge], [TotalDue], [PaymentType], [DateTime])
            VALUES
            (:kioskRegNo, :referenceNo, :CustomerName, :IDNumber, :subtotal, :service_charge, :total, :paymentType, GETDATE())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kioskRegNo' => $kioskRegNo,
            ':referenceNo' => $referenceNo,
            ':subtotal' => $subtotal,
            ':service_charge' => $service_charge,
            ':total' => $total,
            ':paymentType' => $paymentType,
            ':CustomerName' => $CustomerName,
            ':IDNumber' => $IDNumber
        ]);

        $sql = "select * from KIOSK_TransactionStatus where ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ];
        $result = fetch($sql, $params, $pdo);

        $sql = "select * from KIOSK_TransactionStatus where ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ];
        $result = fetch($sql, $params, $pdo);

        if ($result) {
            $sql = "UPDATE KIOSK_TransactionStatus SET Status = 'pending', Datetime = GETDATE() WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':referenceNo' => $referenceNo, ':kioskRegNo' => $kioskRegNo]);
        } else {
            $sql = "INSERT INTO KIOSK_TransactionStatus (KioskRegNo, ReferenceNo, DateTime, Status) VALUES (:kioskRegNo, :referenceNo, GETDATE(), 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':kioskRegNo' => $kioskRegNo, ':referenceNo' => $referenceNo]);
        }
    } else {
        $sql = "SELECT * FROM KIOSK_TransactionStatus WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
        $params = [
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ];
        $result = fetch($sql, $params, $pdo);

        if (!$result) {
            $response['status'] = 'error';
            $response['message'] = 'Transaction not found';
        } else {
            $status = $result->Status;

            if ($status === 'pending') {
                $response['status'] = $status;
                $response['message'] = 'Transaction is being processed. Please wait...';
            } elseif ($status === 'success') {
                $response['status'] = 'success';
                $response['message'] = 'Your transaction has been completed successfully.';
            } else {
                $response['status'] = 'error';
                $response['message'] = $result->Status;
            }
        }
    }

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
