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

    if (empty($json)) throw new Exception('No data received');

    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());
    if (!$kioskRegNo) throw new Exception('KioskRegNo is required', 500);
    if (empty($data['ReferenceNo'])) throw new Exception('ReferenceNo is required', 406);
    if (empty($data['cart'])) throw new Exception('Cart is empty', 406);

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

    $response['success'] = true;
    $response['data'] = $totals;
    $response['message'] = 'Item added to cart successfully';
    http_response_code(200);
} catch (\Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
    $response['data'] = [];
}

echo json_encode($response);
exit();
