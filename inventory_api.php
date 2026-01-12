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

function getAllInventoryItems() {
    global $conn;

    $sql = "EXEC sp_GetInventory";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return ['success' => false, 'error' => sqlsrv_errors()];
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = [
            'id' => $row['ItemId'],
            'name' => $row['Item_Name'],
            'category' => $row['Category_Name'],
            'qty_type' => $row['Qty_Type'],
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

    $sql = "EXEC sp_AddInventoryItem ?, ?, ?, ?, ?, ?";
    $params = [
        $data['name'],
        $data['category_id'],
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

    $sql = "EXEC sp_UpdateInventoryItem ?, ?, ?, ?, ?, ?, ?";
    $params = [
        $data['id'],
        $data['name'],
        $data['category_id'],
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
