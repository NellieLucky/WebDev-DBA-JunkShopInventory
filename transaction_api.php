<?php
// Transaction API for AJAX operations

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them

session_start(); // Add session start for user_id
require_once __DIR__ . '/db_connect.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
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
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

function getInventoryForTransaction() {
    global $conn;

    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    // Removed caching to ensure fresh data and faster response
    $sql = "SELECT i.ItemID, i.Item_Name, c.Category_Name, i.Item_Quantity, i.Buying_Price, i.Selling_Price, i.qty_type
            FROM Inventory i
            LEFT JOIN Categories c ON i.CategoryID = c.CategoryID
            ORDER BY i.Item_Name";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = 'Failed to fetch inventory';
        if ($errors) {
            $errorMsg .= ': ' . $errors[0]['message'];
        }
        return ['success' => false, 'error' => $errorMsg];
    }

    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'id' => $row['ItemID'],
            'name' => $row['Item_Name'],
            'category' => $row['Category_Name'] ?: 'Uncategorized',
            'quantity' => $row['Item_Quantity'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price'],
            'qty_type' => $row['qty_type'] ?? 'by piece'
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

    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    $customerName = trim($data['customer_name']);
    $operationType = $data['operation_type'];

    // First, find or create customer
    $customerId = getOrCreateCustomer($customerName);
    if (!$customerId) {
        return ['success' => false, 'error' => 'Failed to create/find customer'];
    }

    // Get current user ID (Management/Employee ID)
    $managementId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

    // Map operation type to transaction type
    $transactionType = ($operationType === 'receiving') ? 'Purchase' : 'Sale';

    // Create transaction directly
    $insertSql = "INSERT INTO Transactions (Customer_ID, ManagementID, Transaction_Type, Total_No_Of_Items, Transaction_Date)
                  OUTPUT INSERTED.TransactionID
                  VALUES (?, ?, ?, 0, GETDATE())";

    $insertStmt = sqlsrv_query($conn, $insertSql, [$customerId, $managementId, $transactionType]);

    if ($insertStmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = 'Failed to create transaction';
        if ($errors) {
            $errorMsg .= ': ' . $errors[0]['message'];
            // Check if table doesn't exist
            if (strpos($errors[0]['message'], 'Invalid object name') !== false || 
                stripos($errors[0]['message'], 'Transactions') !== false) {
                $errorMsg .= ' (The Transactions table does not exist. Please run create_transactions_tables.sql to create it.)';
            }
        }
        return ['success' => false, 'error' => $errorMsg];
    }

    if (!$insertStmt) {
        return ['success' => false, 'error' => 'Failed to create transaction: Query returned false'];
    }

    $row = sqlsrv_fetch_array($insertStmt, SQLSRV_FETCH_ASSOC);
    if (!$row) {
        return ['success' => false, 'error' => 'Failed to create transaction: Could not get transaction ID'];
    }

    return ['success' => true, 'transaction_id' => $row['TransactionID']];
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

    // Get transaction type
    $transactionSql = "SELECT Transaction_Type FROM Transactions WHERE TransactionID = ?";
    $transactionStmt = sqlsrv_query($conn, $transactionSql, [$transactionId]);

    if ($transactionStmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = 'Transaction not found';
        if ($errors) {
            $errorMsg .= ': ' . $errors[0]['message'];
            if (strpos($errors[0]['message'], 'Invalid object name') !== false) {
                $errorMsg .= ' (The Transactions table does not exist. Please run create_transactions_tables.sql to create it.)';
            }
        }
        return ['success' => false, 'error' => $errorMsg];
    }

    if (!$transactionStmt || !($transactionRow = sqlsrv_fetch_array($transactionStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }

    $price = ($operationType === 'receiving') ? $itemRow['Buying_Price'] : $itemRow['Selling_Price'];
    $currentStock = $itemRow['Item_Quantity'];
    $transactionType = $transactionRow['Transaction_Type'];

    // Validate stock for dispatching
    if ($operationType === 'dispatching' && $quantity > $currentStock) {
        return ['success' => false, 'error' => 'Insufficient stock. Available: ' . $currentStock];
    }

    // Update inventory stock
    $stockAdjustment = ($transactionType === 'Purchase') ? $quantity : -$quantity;
    $updateStockSql = "UPDATE Inventory SET Item_Quantity = Item_Quantity + ? WHERE ItemID = ?";
    $updateStmt = sqlsrv_query($conn, $updateStockSql, [$stockAdjustment, $itemId]);

    if (!$updateStmt) {
        return ['success' => false, 'error' => 'Failed to update inventory stock'];
    }

    // Insert into Transaction_Items
    $insertSql = "INSERT INTO Transaction_Items (TransactionID, Item_ID, Quantity, PriceAtTime)
                  VALUES (?, ?, ?, ?)";
    $insertStmt = sqlsrv_query($conn, $insertSql, [$transactionId, $itemId, $quantity, $price]);

    if ($insertStmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = 'Failed to add item to transaction';
        if ($errors) {
            $errorMsg .= ': ' . $errors[0]['message'];
            if (strpos($errors[0]['message'], 'Invalid object name') !== false) {
                $errorMsg .= ' (The Transaction_Items table does not exist. Please run create_transactions_tables.sql to create it.)';
            }
        }
        return ['success' => false, 'error' => $errorMsg];
    }

    // Update transaction total
    $updateTotalSql = "UPDATE Transactions SET Total_No_Of_Items = Total_No_Of_Items + ? WHERE TransactionID = ?";
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
    $checkSql = "SELECT COUNT(*) as item_count FROM Transaction_Items WHERE TransactionID = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, [$transactionId]);

    if (!$checkStmt || !($checkRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Failed to check transaction items'];
    }

    if ($checkRow['item_count'] == 0) {
        return ['success' => false, 'error' => 'Cannot complete an empty transaction'];
    }

    // Mark transaction as completed
    $updateSql = "UPDATE Transactions SET Transaction_Type = Transaction_Type + ' - COMPLETED' WHERE TransactionID = ?";
    $updateStmt = sqlsrv_query($conn, $updateSql, [$transactionId]);

    if (!$updateStmt) {
        return ['success' => false, 'error' => 'Failed to complete transaction'];
    }

    return ['success' => true];
}

function cancelTransaction($transactionId) {
    global $conn;

    // Delete transaction items first (if any)
    $deleteItemsSql = "DELETE FROM Transaction_Items WHERE TransactionID = ?";
    sqlsrv_query($conn, $deleteItemsSql, [$transactionId]);

    // Delete transaction
    $deleteSql = "DELETE FROM Transactions WHERE TransactionID = ?";
    $stmt = sqlsrv_query($conn, $deleteSql, [$transactionId]);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to cancel transaction'];
    }

    return ['success' => true];
}
?>