<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

function printReceipt($referenceNo, $kioskRegNo)
{
    try {
        error_log("Starting receipt generation for Reference: $referenceNo, KioskRegNo: $kioskRegNo");
        global $pdo;

        if (!extension_loaded('mbstring')) {
            throw new Exception("mbstring extension is required for receipt printing");
        }


        $sql = "SELECT 
            th.*, 
            ts.Status as PaymentStatus,
            CONVERT(varchar, th.DateTime, 120) as FormattedDate
        FROM KIOSK_TransactionHeader th
        LEFT JOIN KIOSK_TransactionStatus ts ON th.ReferenceNo = ts.ReferenceNo AND th.KioskRegNo = ts.KioskRegNo
        WHERE th.ReferenceNo = :referenceNo AND th.KioskRegNo = :kioskRegNo";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            error_log("Transaction header not found for Reference: $referenceNo, KioskRegNo: $kioskRegNo");
            throw new Exception("Transaction not found");
        }
        error_log("Transaction header found: " . json_encode($header));


        $sql = "SELECT * FROM KIOSK_TransactionItem WHERE ReferenceNo = :referenceNo AND KioskRegNo = :kioskRegNo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':referenceNo' => $referenceNo,
            ':kioskRegNo' => $kioskRegNo
        ]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            error_log("No items found for Reference: $referenceNo, KioskRegNo: $kioskRegNo");
            throw new Exception("No items found for this transaction");
        }
        error_log("Found " . count($items) . " items for the transaction");


        $config = require __DIR__ . '/../config.php';
        $printerName = $config['printer_names'][0] ?? 'POS80';

        error_log("Attempting to connect to printer: $printerName");

        try {
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);
        } catch (Exception $e) {
            error_log("Failed to initialize printer: " . $e->getMessage());
            throw new Exception("Failed to initialize printer: " . $e->getMessage());
        }
        error_log("Printer initialized successfully");

        try {

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            try {
                $logo = EscposImage::load(__DIR__ . '/../images/logo/namelogo.png');
                $printer->bitImage($logo);
            } catch (Exception $e) {
                error_log("Failed to print logo: " . $e->getMessage());
            }
            $printer->text("\n\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $dateTime = new DateTime($header['FormattedDate']);
            $printer->text(sprintf(
                "%02d %02d %d        %d\n",
                $dateTime->format('m'),
                $dateTime->format('d'),
                $dateTime->format('Y'),
                $dateTime->format('H')
            ));
            $printer->text(sprintf("%s\n", $dateTime->format('H:i')));
            $printer->text("\n");

            // Items with quantity
            $printer->text("ITEM             QTY\n");

            foreach ($items as $item) {
                if (!isset($item['Description']) || !isset($item['Quantity'])) {
                    error_log("Invalid item data: " . json_encode($item));
                    continue;
                }
                $itemName = $item['Description'];
                $qty = $item['Quantity'];

                try {

                    $printer->text(sprintf("%-20s %d\n", $itemName, $qty));
                } catch (Exception $e) {
                    error_log("Failed to print item: " . json_encode($item) . ", Error: " . $e->getMessage());
                    throw new Exception("Failed to print item: " . $e->getMessage());
                }
            }

            $printer->cut();

            return [
                'success' => true,
                'message' => 'Receipt printed successfully'
            ];
        } finally {
            $printer->close();
        }
    } catch (Exception $e) {
        error_log("Print error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
