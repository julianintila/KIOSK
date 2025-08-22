<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
  'success' => false,
  'message' => 'An error occurred',
  'data' => [],
];

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['GET', 'POST']) === false) {
  http_response_code(405);
  echo json_encode($response);
  exit();
}

try {
  require_once __DIR__ . '/connect.php';

  if ($method === 'GET') {

    $sql = "select * from KIOSK_DiscountRequests WHERE status = 'pending' ORDER BY datetime";
    $params = [];
    $pending = fetchAll($sql, $params, $pdo);

    $sql = "select Accountnumber account_number, MiddleInitial middle_initial from Customer WHERE ISNULL(MiddleInitial, '') <> '' AND MiddleInitial LIKE '%SP %' ORDER BY MiddleInitial";
    $params = [];
    $middleInitials = fetchAll($sql, $params, $pdo);

    $sql = "SELECT * FROM (
    SELECT a.Code code, UPPER(LTRIM(RTRIM(REPLACE(
        CASE 
          WHEN PATINDEX('%[0-9]%[%]%', Description) = 1 THEN SUBSTRING(Description, CHARINDEX(' ', Description)+1, LEN(Description))
          ELSE Description
        END, 
        '#', ''
      )))) AS description
    FROM ReasonCodeKiosk a JOIN ReasonCode b ON a.Code = b.Code
  ) a ORDER BY description";
    $params = [];
    $discounts = fetchAll($sql, $params, $pdo);

    $response['data']['pending_discount_requests'] = $pending ?: [];
    $response['data']['customers'] = $middleInitials ?: [];
    $response['data']['discounts'] = $discounts ?: [];

    $response['success'] = true;
    $response['message'] = 'Pending discount requests fetched successfully';
  }

  $pdo->beginTransaction();

  if ($method === 'POST') {
    $json = file_get_contents('php://input');

    if (empty($json)) throw new Exception('No data received', 400);

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON: ' . json_last_error_msg());

    if (empty($data['action'])) throw new Exception('Action is required', 400);

    if (!in_array($data['action'], ['reject', 'generate_code'])) throw new Exception('Invalid action', 400);

    $action = $data['action'];

    if ($action === 'reject') {
      if (empty($data['id'])) throw new Exception('ID is required for rejection', 400);
      $id = $data['id'];

      $sql = "SELECT * FROM KIOSK_DiscountRequests WHERE id = :id AND status = 'pending'";
      $params = [':id' => $id];
      $request = fetch($sql, $params, $pdo);
      if (!$request) throw new Exception('Discount request not found or already processed', 406);

      $sql = "UPDATE KIOSK_DiscountRequests SET status = 'rejected', rejected_at = GETDATE() WHERE id = :id";
      $stmt = $pdo->prepare($sql);
      $params = [':id' => $id];
      $result = $stmt->execute($params);

      if (!$result) throw new Exception('Failed to reject discount request', 500);

      $response['success'] = true;
      $response['message'] = 'Discount request rejected successfully';
    }

    if ($action === 'generate_code') {
      if (empty($data['id'])) throw new Exception('ID is required for code generation', 400);
      if (empty($data['discount_type'])) throw new Exception('Discount type is required for code generation', 400);
      if (empty($data['customer']) && strtolower($data['discount_type']) === 'sp') {
        throw new Exception('Customer is required for SP discount type', 400);
      }

      $discountType = $data['discount_type'];
      $id = $data['id'];
      $customer = isset($data['customer']) ? $data['customer'] : '';

      $sql = "SELECT * FROM KIOSK_DiscountRequests WHERE id = :id AND status = 'pending'";
      $params = [':id' => $id];
      $request = fetch($sql, $params, $pdo);
      if (!$request) throw new Exception('Discount request not found or already processed', 406);

      $sql = "SELECT * FROM ReasonCodeKiosk WHERE Code = :code";
      $params = [':code' => $discountType];
      $discount = fetch($sql, $params, $pdo);
      if (!$discount) throw new Exception('Invalid discount type', 406);

      $code = '';

      if ($customer) {
        $code = preg_replace('/[^A-Za-z0-9 ]/', '', $customer);
      } else {
        do {
          $randomNumber = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
          $code = $discount->Prefix . '-' . $randomNumber;

          $sql = "SELECT * FROM KIOSK_DiscountRequests WHERE discount_code = :code";
          $params = [':code' => $code];
          $existingCode = fetch($sql, $params, $pdo);
        } while ($existingCode);
      }

      $sql = "UPDATE KIOSK_DiscountRequests SET status = 'rejected', rejected_at = GETDATE() WHERE referenceNo = :referenceNo AND register_no = :kioskRegNo AND status = 'accepted'";

      $stmt = $pdo->prepare($sql);
      $params = [
        ':referenceNo' => $request->ReferenceNo,
        ':kioskRegNo' => $request->register_no,
      ];
      $stmt->execute($params);

      $sql = "UPDATE KIOSK_DiscountRequests SET status = 'accepted', discount_code = :code, discount_type = :discount_type, accepted_at = GETDATE() WHERE id = :id";

      $stmt = $pdo->prepare($sql);
      $params = [
        ':id' => $id,
        ':code' => $code,
        ':discount_type' => $discountType,
      ];

      $result = $stmt->execute($params);
      if (!$result) throw new Exception('Failed to generate discount code', 500);

      $response['success'] = true;
      $response['message'] = 'Discount code generated successfully';
      $response['data']['code'] = $code;
    }
    $pdo->commit();
  }
} catch (Exception $e) {
  if ($pdo && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code($e->getCode() ?: 500);
  $response['message'] = $e->getMessage();
  $response['success'] = false;
}

echo json_encode($response);
exit();
