<?php
// Transaction API for AJAX operations

require_once __DIR__ . '/db_connect.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_inventory':
            echo json_encode(getInventoryForTransaction());
            break;
        case 'get_customers':
            echo json_encode(getCustomers());
            break;
        case 'create_transaction':
            echo json_encode(createTransaction($_POST));
            break;
        case 'add_transaction_item':
            echo json_encode(addTransactionItem($_POST));
            break;
        case 'complete_transaction':
            echo json_encode(completeTransaction($_POST['transaction_id']));
            break;
        case 'cancel_transaction':
            echo json_encode(cancelTransaction($_POST['transaction_id']));
            break;
    }
    exit();
}

function getInventoryForTransaction() {
    global $conn;

    $sql = "SELECT i.ItemID, i.Item_Name, c.Category_Name, i.Item_Quantity, i.Buying_Price, i.Selling_Price
            FROM Inventory i
            LEFT JOIN Categories c ON i.CategoryID = c.CategoryID
            ORDER BY i.Item_Name";

    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to fetch inventory'];
    }

    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'id' => $row['ItemID'],
            'name' => $row['Item_Name'],
            'category' => $row['Category_Name'] ?: 'Uncategorized',
            'quantity' => $row['Item_Quantity'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price']
        ];
    }

    return ['success' => true, 'data' => $items];
}

function getCustomers() {
    global $conn;

    $sql = "SELECT CustomerID, Name, Customer_Type, Contact_Number FROM Customer ORDER BY Name";
    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to fetch customers'];
    }

    $customers = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $customers[] = [
            'id' => $row['CustomerID'],
            'name' => $row['Name'],
            'type' => $row['Customer_Type'],
            'contact' => $row['Contact_Number']
        ];
    }

    return ['success' => true, 'data' => $customers];
}

function createTransaction($data) {
    global $conn;

    $customerName = trim($data['customer_name']);
    $operationType = $data['operation_type'];

    // First, find or create customer
    $customerId = getOrCreateCustomer($customerName);
    if (!$customerId) {
        return ['success' => false, 'error' => 'Failed to create/find customer'];
    }

    // Get current user ID (placeholder for now)
    $employeeId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

    // Map operation type to exchange type
    $exchangeType = ($operationType === 'receiving') ? 'Purchase' : 'Sale';

    // Create exchange directly
    $insertSql = "INSERT INTO Exchange (Customer_ID, Employee_ID, Exchange_Type, Total_No_Of_Items, Exchange_Date)
                  OUTPUT INSERTED.ExchangeID
                  VALUES (?, ?, ?, 0, GETDATE())";

    $insertStmt = sqlsrv_query($conn, $insertSql, [$customerId, $employeeId, $exchangeType]);

    if (!$insertStmt || !($row = sqlsrv_fetch_array($insertStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Failed to create transaction'];
    }

    return ['success' => true, 'transaction_id' => $row['ExchangeID']];
}

function getOrCreateCustomer($customerName) {
    global $conn;

    // Try to find existing customer (case-insensitive)
    $sql = "SELECT CustomerID FROM Customer WHERE LOWER(Name) = LOWER(?)";
    $stmt = sqlsrv_query($conn, $sql, [$customerName]);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row['CustomerID'];
    }

    // Create new customer
    $insertSql = "INSERT INTO Customer (Name) OUTPUT INSERTED.CustomerID VALUES (?)";
    $insertStmt = sqlsrv_query($conn, $insertSql, [$customerName]);

    if ($insertStmt && $row = sqlsrv_fetch_array($insertStmt, SQLSRV_FETCH_ASSOC)) {
        return $row['CustomerID'];
    }

    return false;
}

function addTransactionItem($data) {
    global $conn;

    $transactionId = $data['transaction_id'];
    $itemId = $data['item_id'];
    $quantity = $data['quantity'];
    $operationType = $data['operation_type'];

    // Get item details
    $itemSql = "SELECT Buying_Price, Selling_Price, Item_Quantity FROM Inventory WHERE ItemID = ?";
    $itemStmt = sqlsrv_query($conn, $itemSql, [$itemId]);

    if (!$itemStmt || !($itemRow = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Item not found'];
    }

    // Get exchange type
    $exchangeSql = "SELECT Exchange_Type FROM Exchange WHERE ExchangeID = ?";
    $exchangeStmt = sqlsrv_query($conn, $exchangeSql, [$transactionId]);

    if (!$exchangeStmt || !($exchangeRow = sqlsrv_fetch_array($exchangeStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }

    $price = ($operationType === 'receiving') ? $itemRow['Buying_Price'] : $itemRow['Selling_Price'];
    $currentStock = $itemRow['Item_Quantity'];
    $exchangeType = $exchangeRow['Exchange_Type'];

    // Validate stock for dispatching
    if ($operationType === 'dispatching' && $quantity > $currentStock) {
        return ['success' => false, 'error' => 'Insufficient stock. Available: ' . $currentStock];
    }

    // Update inventory stock
    $stockAdjustment = ($exchangeType === 'Purchase') ? $quantity : -$quantity;
    $updateStockSql = "UPDATE Inventory SET Item_Quantity = Item_Quantity + ? WHERE ItemID = ?";
    $updateStmt = sqlsrv_query($conn, $updateStockSql, [$stockAdjustment, $itemId]);

    if (!$updateStmt) {
        return ['success' => false, 'error' => 'Failed to update inventory stock'];
    }

    // Insert into exchanged_items
    $insertSql = "INSERT INTO Exchanged_Items (Exchange_ID, Item_ID, Quantity, PriceAtTime)
                  VALUES (?, ?, ?, ?)";
    $insertStmt = sqlsrv_query($conn, $insertSql, [$transactionId, $itemId, $quantity, $price]);

    if (!$insertStmt) {
        return ['success' => false, 'error' => 'Failed to add item to transaction'];
    }

    // Update exchange total
    $updateTotalSql = "UPDATE Exchange SET Total_No_Of_Items = Total_No_Of_Items + ? WHERE ExchangeID = ?";
    $updateTotalStmt = sqlsrv_query($conn, $updateTotalSql, [$quantity, $transactionId]);

    if (!$updateTotalStmt) {
        return ['success' => false, 'error' => 'Failed to update transaction total'];
    }

    return [
        'success' => true,
        'item_details' => [
            'price' => $price,
            'amount' => $price * $quantity
        ]
    ];
}

function completeTransaction($transactionId) {
    global $conn;

    // Check if transaction has any items
    $checkSql = "SELECT COUNT(*) as item_count FROM Exchanged_Items WHERE Exchange_ID = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, [$transactionId]);

    if (!$checkStmt || !($checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Failed to check transaction items'];
    }

    if ($checkRow['item_count'] == 0) {
        return ['success' => false, 'error' => 'Cannot complete an empty transaction'];
    }

    // Mark transaction as completed
    $updateSql = "UPDATE Exchange SET Exchange_Type = Exchange_Type + ' - COMPLETED' WHERE ExchangeID = ?";
    $updateStmt = sqlsrv_query($conn, $updateSql, [$transactionId]);

    if (!$updateStmt) {
        return ['success' => false, 'error' => 'Failed to complete transaction'];
    }

    return ['success' => true];
}

function cancelTransaction($transactionId) {
    global $conn;

    $sql = "EXEC sp_CancelExchange @ExchangeID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$transactionId]);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to cancel transaction'];
    }

    return ['success' => true];
}
?>