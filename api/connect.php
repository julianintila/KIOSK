<?php

$config = require __DIR__ . '/../config.php';

$serverName   = $config['db_server'];
$databaseName = $config['db_name'];
$username     = $config['db_user'];
$password     = $config['db_pass'];
$kioskRegNo   = $config['register_no'] ?? null;

$pdo = new PDO("sqlsrv:Server=$serverName;Database=$databaseName", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function fetchAll($sql, $params = [], $pdo)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}
function fetch($sql, $params = [], $pdo)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_OBJ);
}

function recomputeRegister($kioskRegNo, $referenceNo, $pdo)
{
    $totals = [];

    $sql = "EXEC Proc_RecomputeRegister_Kiosk :kioskRegNo, :referenceNo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':kioskRegNo' => $kioskRegNo,
        ':referenceNo' => $referenceNo
    ]);

    $sql = "SELECT ISNULL(SUM(ExtendedAmt), 0) total, ISNULL(SUM(LineDiscount), 0) discount, ISNULL(SUM(OriginalPrice), 0) gross 
            FROM [KIOSK_TransactionItem] 
            WHERE KioskRegNo = :kioskRegNo AND ReferenceNo = :referenceNo";
    $params = [
        ':kioskRegNo' => $kioskRegNo,
        ':referenceNo' => $referenceNo
    ];
    $register = fetch($sql, $params, $pdo);

    $totals['total'] = (float) (round($register->total, 2) ?? 0);
    $totals['subtotal'] = (float) (round($register->gross, 2) ?? 0);
    $totals['discount'] = (float) (round($register->discount, 1) ?? 0);

    $sql = "EXEC Proc_ComputeServiceCharge_Kiosk :kioskRegNo, :referenceNo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':kioskRegNo' => $kioskRegNo,
        ':referenceNo' => $referenceNo
    ]);

    $sql = "SELECT * FROM [RegisterServiceCharge_Kiosk] WHERE RegisterNo = :kioskRegNo";
    $params = [':kioskRegNo' => $kioskRegNo];
    $serviceCharge = fetch($sql, $params, $pdo);

    $totals['service_charge'] = (float) (round($serviceCharge->Amount, 2) ?? 0);

    $totals['total'] = (float) (round($totals['total'] + $totals['service_charge'], 2) ?? 0);
    return $totals;
}
