<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once __DIR__ . '/connect.php';

    $response = [];

    $sql = "SELECT ID as id, Name as name, OrderNo as order_no FROM Category WHERE Inactive = 0 AND COALESCE(OrderNo, 0) > 0 ORDER BY COALESCE(OrderNo, 0)";
    $categories = fetchAll($sql, [], $pdo);

    $totalCategories = count($categories);
    $filteredCategories = [];

    foreach ($categories as $index => $category) {
        $categoryID = (int)$category->id;
        $category->id = $categoryID;
        $category->order_no = (int)$category->order_no;

        $category->previous_category_id = $index > 0 ? (int)$categories[$index - 1]->id : null;
        $category->next_category_id = $index < $totalCategories - 1 ? (int)$categories[$index + 1]->id : null;

        $category->required = false;
        if ($category->previous_category_id === null) {
            $category->required = true;
        }

        $sql = "SELECT LTRIM(RTRIM(Description)) name FROM KIOSK_Category WHERE CategoryID = :category_id AND COALESCE(OrderNo, 0) > 0 ORDER BY COALESCE(OrderNo, 0)";
        $params = [':category_id' => $categoryID];
        $kioskCategories = fetchAll($sql, $params, $pdo);
        $category->descriptions = $kioskCategories;

        $sql = "SELECT LTRIM(RTRIM(Notes)) name FROM KIOSK_Category WHERE CategoryID = :category_id AND Notes IS NOT NULL AND COALESCE(OrderNo, 0) > 0 ORDER BY COALESCE(OrderNo, 0)";
        $params = [':category_id' => $categoryID];
        $kioskCategories = fetchAll($sql, $params, $pdo);
        $category->notes = $kioskCategories;


        $sql = "SELECT ID as id, ItemLookupCode as item_code, Description as description, ExtendedDescription as extended_description, DepartmentID as department_id, CategoryID as category_id, SubCategoryID as subcategory_id, Price as price, Taxable as taxable, OrderNo as order_no FROM Item WHERE CategoryID = :category_id AND ItemStatus = :status AND Inactive = 0 AND COALESCE(OrderNo, 0) > 0 ORDER BY COALESCE(OrderNo, 0)";
        $params = [':category_id' => $categoryID, ':status' => 'Regular Item'];
        $items = fetchAll($sql, $params, $pdo);

        foreach ($items as $item) {
            $item->id = (int)$item->id;
            $item->department_id = (int)$item->department_id;
            $item->category_id = (int)$item->category_id;
            $item->subcategory_id = (int)$item->subcategory_id;
            $item->taxable = (int)$item->taxable;
            $item->order_no = (int)$item->order_no;
            $item->price = (float)$item->price;
        }

        if (!empty($items)) {
            $category->items = $items;
            $filteredCategories[] = $category;
        }
    }

    $response['categories'] = $categories;

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
