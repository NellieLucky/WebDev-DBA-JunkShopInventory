<?php
// ScrapTrack Transaction Records - PHP Backend
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// DB connection
require_once __DIR__ . '/db_connect.php';

// Get user information
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ExoticNellie69';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Function declarations
function getTransactionRecords() {
    global $conn;

    $sql = "SELECT
                t.TransactionID,
                t.Transaction_Date,
                t.Transaction_Type,
                t.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Transactions t
            JOIN Customer c ON t.Customer_ID = c.CustomerID
            ORDER BY t.Transaction_Date DESC";

    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to fetch transaction records'];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Calculate totals for this transaction
        $totals = calculateTransactionTotals($row['TransactionID']);

        $records[] = [
            'id' => $row['TransactionID'],
            'date' => $row['Transaction_Date'] ? $row['Transaction_Date']->format('Y-m-d') : 'N/A',
            'type' => $row['Transaction_Type'] === 'Purchase' ? 'Received' : 'Dispatched',
            'customer' => $row['CustomerName'],
            'noOfItems' => $row['Total_No_Of_Items'],
            'totalPiece' => $totals['pieces'],
            'totalKilo' => $totals['kilos'],
            'totalAmount' => $totals['amount']
        ];
    }

    return ['success' => true, 'data' => $records];
}

function calculateTransactionTotals($transactionId) {
    global $conn;

    $sql = "SELECT
                ti.Quantity,
                ti.PriceAtTime,
                i.qty_type
            FROM Transaction_Items ti
            JOIN Inventory i ON ti.Item_ID = i.ItemID
            WHERE ti.TransactionID = ?";

    $stmt = sqlsrv_query($conn, $sql, [$transactionId]);

    $pieces = 0;
    $kilos = 0;
    $amount = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $amount += ($row['Quantity'] * $row['PriceAtTime']);

        // Count based on qty_type from database
        if ($row['qty_type'] === 'by kilo') {
            $kilos += $row['Quantity'];
        } else {
            $pieces += $row['Quantity'];
        }
    }

    return [
        'pieces' => $pieces,
        'kilos' => $kilos,
        'amount' => $amount
    ];
}

function getTransactionDetail($transactionId) {
    global $conn;

    // Get transaction header
    $headerSql = "SELECT
                    t.TransactionID,
                    t.Transaction_Date,
                    t.Transaction_Type,
                    t.Total_No_Of_Items,
                    c.Name AS CustomerName,
                    m.FirstName AS EmployeeName
                FROM Transactions t
                JOIN Customer c ON t.Customer_ID = c.CustomerID
                LEFT JOIN Management m ON t.ManagementID = m.ManagementID
                WHERE t.TransactionID = ?";

    $headerStmt = sqlsrv_query($conn, $headerSql, [$transactionId]);

    if (!$headerStmt || !($header = sqlsrv_fetch_array($headerStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }

    // Get transaction items
    $itemsSql = "SELECT
                    i.Item_Name,
                    cat.Category_Name,
                    ti.Quantity,
                    ti.PriceAtTime,
                    i.qty_type,
                    (ti.Quantity * ti.PriceAtTime) as LineTotal
                FROM Transaction_Items ti
                JOIN Inventory i ON ti.Item_ID = i.ItemID
                LEFT JOIN Category cat ON i.CategoryID = cat.CategoryID
                WHERE ti.TransactionID = ?";

    $itemsStmt = sqlsrv_query($conn, $itemsSql, [$transactionId]);

    $items = [];
    $totalAmount = 0;
    $totalPieces = 0;
    $totalKilos = 0;

    while ($item = sqlsrv_fetch_array($itemsStmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'name' => $item['Item_Name'],
            'category' => $item['Category_Name'] ?: 'Uncategorized',
            'quantity' => $item['Quantity'],
            'price' => $item['PriceAtTime'],
            'qty_type' => $item['qty_type'] ?: 'by piece',
            'amount' => $item['LineTotal']
        ];

        $totalAmount += $item['LineTotal'];

        // Count based on qty_type
        if ($item['qty_type'] === 'by kilo') {
            $totalKilos += $item['Quantity'];
        } else {
            $totalPieces += $item['Quantity'];
        }
    }

    return [
        'success' => true,
        'data' => [
            'id' => $header['TransactionID'],
            'date' => $header['Transaction_Date'] ? $header['Transaction_Date']->format('Y-m-d') : 'N/A',
            'type' => $header['Transaction_Type'] === 'Purchase' ? 'Received' : 'Dispatched',
            'customer' => $header['CustomerName'],
            'employee' => $header['EmployeeName'] ?: 'N/A',
            'noOfItems' => $header['Total_No_Of_Items'],
            'totalPiece' => $totalPieces,
            'totalKilo' => $totalKilos,
            'totalAmount' => $totalAmount,
            'items' => $items
        ]
    ];
}

function searchTransactionRecords($search) {
    global $conn;

    $searchTerm = '%' . $search . '%';

    $sql = "SELECT
                t.TransactionID,
                t.Transaction_Date,
                t.Transaction_Type,
                t.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Transactions t
            JOIN Customer c ON t.Customer_ID = c.CustomerID
            WHERE c.Name LIKE ? OR CAST(t.TransactionID AS VARCHAR) LIKE ?
            ORDER BY t.Transaction_Date DESC";

    $stmt = sqlsrv_query($conn, $sql, [$searchTerm, $searchTerm]);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to search transaction records'];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $totals = calculateTransactionTotals($row['TransactionID']);

        $records[] = [
            'id' => $row['TransactionID'],
            'date' => $row['Transaction_Date'] ? $row['Transaction_Date']->format('Y-m-d') : 'N/A',
            'type' => $row['Transaction_Type'] === 'Purchase' ? 'Received' : 'Dispatched',
            'customer' => $row['CustomerName'],
            'noOfItems' => $row['Total_No_Of_Items'],
            'totalPiece' => $totals['pieces'],
            'totalKilo' => $totals['kilos'],
            'totalAmount' => $totals['amount']
        ];
    }

    return ['success' => true, 'data' => $records];
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_records':
            echo json_encode(getTransactionRecords());
            break;
        case 'get_transaction_detail':
            echo json_encode(getTransactionDetail($_POST['transaction_id']));
            break;
        case 'search_records':
            echo json_encode(searchTransactionRecords($_POST['search']));
            break;
    }
    exit();
}

// If not an AJAX request, serve the HTML page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Records - ScrapTrack</title>
    <link rel="stylesheet" href="TransactionRecords.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="Logos-Icons/3.png" alt="ScrapTrack Logo" class="logo-icon">
            <span class="logo-text">ScrapTrack</span>
        </div>

        <nav class="nav-menu">
            <div class="nav-section">
                <p class="nav-label">General</p>
                <a href="Dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="Inventory.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Inventory</span>
                </a>
                <a href="Transaction.php" class="nav-item">
                    <span class="nav-icon">💳</span>
                    <span>Transaction</span>
                </a>
                <a href="TransactionRecords.php" class="nav-item active">
                    <span class="nav-icon">📋</span>
                    <span>Transaction Records</span>
                </a>
                <a href="EmployeeManagement.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Employee Management</span>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-label">Settings</p>
                <a href="AccountSettings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Account Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>Log Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Transaction Records</h1>
                <p>View and manage all transaction history</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    <div class="user-avatar">👤</div>
                </div>
            </div>
        </header>

        <!-- Search and Actions -->
        <section class="search-section">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Search Transaction....." class="search-input">
            </div>
            <button class="filter-btn" id="filterBtn">
                <span class="filter-icon">🔽</span>
            </button>
            <button class="add-btn" id="addBtn">Add</button>
        </section>

        <!-- Transaction Records Table -->
        <section class="records-section">
            <div class="table-container">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>TRANSACTION ID</th>
                            <th>DATE</th>
                            <th>TYPE</th>
                            <th>NO. OF ITEMS</th>
                            <th>TOTAL QUANTITY<br>(item by piece)</th>
                            <th>TOTAL QUANTITY<br>(item by kilo)</th>
                            <th>TOTAL AMOUNT</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTableBody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">
                                Loading transaction records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Transaction Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <span class="close-btn" onclick="closeDetailModal()">&times;</span>
            </div>

            <div class="modal-body">
                <div class="transaction-summary">
                    <div class="summary-row">
                        <span class="label">Transaction ID:</span>
                        <span class="value" id="detailId">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Date:</span>
                        <span class="value" id="detailDate">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Type:</span>
                        <span class="value" id="detailType">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Customer:</span>
                        <span class="value" id="detailCustomer">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Employee:</span>
                        <span class="value" id="detailEmployee">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">No. of Items:</span>
                        <span class="value" id="detailItems">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Total Pieces:</span>
                        <span class="value" id="detailPiece">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Total Kilos:</span>
                        <span class="value" id="detailKilo">-</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Total Amount:</span>
                        <span class="value" id="detailAmount">-</span>
                    </div>
                </div>

                <div class="items-section">
                    <h3>Items in Transaction</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>ITEM</th>
                                <th>CATEGORY</th>
                                <th>QTY TYPE</th>
                                <th>QUANTITY</th>
                                <th>PRICE</th>
                                <th>AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsBody">
                            <!-- Items will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="modal-actions">
                    <button class="btn-print" onclick="printTransaction()">Print Invoice</button>
                    <button class="btn-close" onclick="closeDetailModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="TransactionRecords.js"></script>
</body>
</html>
