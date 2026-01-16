<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db_connect.php';

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action']);
    exit;
}

header('Content-Type: application/json');

switch ($_POST['action']) {
    case 'get_all':
        echo json_encode(getAllInventoryItems());
        break;
    case 'add':
        echo json_encode(addInventoryItem($_POST));
        break;
    case 'update':
        echo json_encode(updateInventoryItem($_POST));
        break;
    case 'delete':
        echo json_encode(deleteInventoryItem($_POST['id']));
        break;
    case 'search':
        echo json_encode(searchInventoryItems($_POST));
        break;
}
exit;

function getOrCreateCategory($categoryName) {
    global $conn;

    // First, try to find existing category (case-insensitive)
    $sql = "SELECT CategoryID FROM Categories WHERE LOWER(Category_Name) = LOWER(?)";
    $stmt = sqlsrv_query($conn, $sql, [$categoryName]);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row['CategoryID'];
    }

    // If not found, insert new category and get the ID
    $insertSql = "INSERT INTO Categories (Category_Name) OUTPUT INSERTED.CategoryID VALUES (?)";
    $insertStmt = sqlsrv_query($conn, $insertSql, [$categoryName]);

    if ($insertStmt && $row = sqlsrv_fetch_array($insertStmt, SQLSRV_FETCH_ASSOC)) {
        return $row['CategoryID'];
    }

    return false;
}

function getAllInventoryItems() {
    global $conn;

    $sql = "SELECT i.ItemID, i.Item_Name, i.CategoryID, c.Category_Name, i.Item_Quantity, i.Item_Weight, i.Buying_Price, i.Selling_Price 
            FROM Inventory i 
            LEFT JOIN Categories c ON i.CategoryID = c.CategoryID 
            ORDER BY i.Item_Name";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return ['success' => false, 'error' => sqlsrv_errors()];
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $qty_type = $row['Item_Weight'] > 0 ? 'Kilo' : 'Piraso';
        $data[] = [
            'id' => $row['ItemID'],
            'name' => $row['Item_Name'],
            'category_id' => $row['CategoryID'],
            'category' => $row['Category_Name'] ?? 'No Category',
            'qty_type' => $qty_type,
            'quantity' => $row['Item_Quantity'],
            'weight' => $row['Item_Weight'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price']
        ];
    }

    return ['success' => true, 'data' => $data];
}

function addInventoryItem($data) {
    global $conn;

    // Decide weight based on quantity type
    $weight = ($data['qty_type'] === 'Kilo') ? $data['quantity'] : 0;

    // Handle category: find existing or create new
    $categoryName = trim($data['category_name']);
    $categoryId = getOrCreateCategory($categoryName);

    if (!$categoryId) {
        return [
            'success' => false,
            'message' => 'Failed to process category'
        ];
    }

    $sql = "EXEC sp_AddInventoryItem ?, ?, ?, ?, ?, ?";
    $params = [
        $data['name'],
        $categoryId,
        $data['quantity'],
        $weight,
        $data['buying_price'],
        $data['selling_price']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $error = sqlsrv_errors()[0];
        return [
            'success' => false,
            'message' => $error['message']
        ];
    }

    return ['success' => true, 'message' => 'Item added successfully'];
}
function updateInventoryItem($data) {
    global $conn;

    // Handle category: find existing or create new
    $categoryName = trim($data['category_name']);
    $categoryId = getOrCreateCategory($categoryName);

    if (!$categoryId) {
        return [
            'success' => false,
            'message' => 'Failed to process category'
        ];
    }

    $sql = "EXEC sp_UpdateInventoryItem ?, ?, ?, ?, ?, ?, ?";
    $params = [
        $data['id'],
        $data['name'],
        $categoryId,
        $data['quantity'],
        $data['weight'],
        $data['buying_price'],
        $data['selling_price']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return ['success' => false, 'error' => sqlsrv_errors()];
    }

    return ['success' => true, 'message' => 'Item updated successfully'];
}

function deleteInventoryItem($id) {
    global $conn;

    $sql = "EXEC sp_DeleteInventoryItem ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);

    if ($stmt === false) {
        return ['success' => false, 'error' => sqlsrv_errors()];
    }

    return ['success' => true, 'message' => 'Item deleted successfully'];
}

function searchInventoryItems($data) {
    global $conn;

    $sql = "EXEC sp_SearchInventoryItems ?, ?, ?, ?";

    $params = [
        $data['search_term'] ?? null,
        $data['category_id'] ?? null,
        $data['min_price'] ?? null,
        $data['max_price'] ?? null
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        return [
            'success' => false,
            'error' => sqlsrv_errors()
        ];
    }

    $results = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['ItemID'],
            'name' => $row['Item_Name'],
            'category' => $row['Category_Name'],
            'quantity' => $row['Item_Quantity'],
            'weight' => $row['Item_Weight'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price'],
            'last_updated' => $row['Previous_Update']
        ];
    }

    return [
        'success' => true,
        'data' => $results
    ];
}
