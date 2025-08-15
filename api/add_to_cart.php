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
    if (empty($data['KioskRegNo'])) throw new Exception('KioskRegNo is required');
    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required');
    if (empty($data['cart'])) throw new Exception('Cart is empty');

    $kioskRegNo = $data['KioskRegNo'];
    $referenceNo = $data['ReferenceNo'];

    $sql = "DELETE from KIOSK_TransactionItem WHERE ReferenceNo = :referenceNo and KioskRegNo = :kioskRegNo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':referenceNo' => $referenceNo, ':kioskRegNo' => $kioskRegNo]);

    $sql = "INSERT INTO [KIOSK_TransactionItem] 
        ([KioskRegNo], [ReferenceNo], [ItemLookupCode], [Description], [Quantity], [UnitPriceSold], [ExtendedAmt], [OriginalPrice], [OriginalExtendedAmt], [Taxable], [DateTime], [DiscountCode], [LineDiscount]) 
        VALUES 
        (:kioskRegNo, :referenceNo, :itemLookupCode, :description, :quantity, :unitPriceSold, :extendedAmt, :originalPrice, :originalExtendedAmt, :taxable, GETDATE(), '', 0)";

    $stmt = $pdo->prepare($sql);

    foreach ($data['cart'] as $item) {
        $stmt->execute([
            'kioskRegNo' => $kioskRegNo,
            'referenceNo' => $referenceNo,
            'itemLookupCode' => $item['item_code'],
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unitPriceSold' => $item['price'],
            'extendedAmt' => $item['total'],
            'originalPrice' => $item['price'],
            'originalExtendedAmt' => $item['total'],
            'taxable' => $item['taxable']
        ]);
    }
    $totals = recomputeRegister($kioskRegNo, $referenceNo, $pdo);

    $response = $totals;
    $response['message'] = 'Item added to cart successfully';

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
