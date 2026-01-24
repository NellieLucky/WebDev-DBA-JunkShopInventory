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

// Main function to get transaction records
function getTransactionRecords() {
    global $conn;
    
    // Based on your schema, we need to join:
    // Transaction_Items -> Transactions -> Customer -> Inventory
    
    $sql = "SELECT TOP 100
                t.TransactionID,
                t.Transaction_Date,
                t.Transaction_Type,
                t.Total_No_Of_Items,
                c.Name AS CustomerName,
                SUM(ti.Quantity) as TotalQuantity,
                SUM(ti.Quantity * ti.PriceAtTime) as TotalAmount
            FROM Transaction_Items ti
            JOIN Transactions t ON ti.TransactionID = t.TransactionID
            JOIN Customer c ON t.Customer_ID = c.CustomerID
            GROUP BY t.TransactionID, t.Transaction_Date, t.Transaction_Type, 
                     t.Total_No_Of_Items, c.Name
            ORDER BY t.Transaction_Date DESC";

    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        $errors = sqlsrv_errors();
        error_log("SQL Error: " . print_r($errors, true));
        return ['success' => false, 'error' => 'Failed to fetch transaction records. Check database connection.'];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Calculate pieces vs kilos for this transaction
        $totals = calculateTransactionTotals($row['TransactionID']);

        $records[] = [
            'id' => $row['TransactionID'],
            'date' => $row['Transaction_Date'] ? $row['Transaction_Date']->format('Y-m-d') : 'N/A',
            'type' => $row['Transaction_Type'],
            'customer' => $row['CustomerName'],
            'noOfItems' => $row['Total_No_Of_Items'] ?: $row['TotalQuantity'],
            'totalPiece' => $totals['pieces'],
            'totalKilo' => $totals['kilos'],
            'totalAmount' => $row['TotalAmount'] ?: $totals['amount']
        ];
    }

    return ['success' => true, 'data' => $records];
}

function calculateTransactionTotals($transactionId) {
    global $conn;

    // Need to join with Inventory to check if items are by weight
    $sql = "SELECT
                ti.Quantity,
                ti.PriceAtTime,
                i.Item_Weight,
                i.Item_Quantity,
                i.Item_Category
            FROM Transaction_Items ti
            LEFT JOIN Inventory i ON ti.Item_ID = i.ItemID
            WHERE ti.TransactionID = ?";

    $stmt = sqlsrv_query($conn, $sql, [$transactionId]);

    $pieces = 0;
    $kilos = 0;
    $amount = 0;

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $quantity = $row['Quantity'] ?: 0;
            $price = $row['PriceAtTime'] ?: 0;
            
            $amount += ($quantity * $price);
            
            // Determine if item is sold by kilo or piece
            // Check Item_Weight field - if > 0, it's by kilo
            if ($row['Item_Weight'] && $row['Item_Weight'] > 0) {
                $kilos += ($quantity * $row['Item_Weight']);
            } else {
                $pieces += $quantity;
            }
        }
    }

    return [
        'pieces' => $pieces,
        'kilos' => round($kilos, 2),
        'amount' => round($amount, 2)
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
                    m.FirstName + ' ' + m.LastName AS EmployeeName
                FROM Transactions t
                LEFT JOIN Customer c ON t.Customer_ID = c.CustomerID
                LEFT JOIN Management m ON t.ManagementID = m.ManagementID
                WHERE t.TransactionID = ?";

    $headerStmt = sqlsrv_query($conn, $headerSql, [$transactionId]);

    if (!$headerStmt || !($header = sqlsrv_fetch_array($headerStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }

    // Get transaction items with inventory details
    $itemsSql = "SELECT
                    ti.TransactionItemID,
                    ti.Item_ID,
                    ti.Quantity,
                    ti.PriceAtTime,
                    i.Item_Name,
                    i.Item_Category,
                    i.Item_Weight,
                    i.Item_Quantity as StockQuantity,
                    (ti.Quantity * ti.PriceAtTime) as LineTotal
                FROM Transaction_Items ti
                LEFT JOIN Inventory i ON ti.Item_ID = i.ItemID
                WHERE ti.TransactionID = ?";

    $itemsStmt = sqlsrv_query($conn, $itemsSql, [$transactionId]);

    $items = [];
    $totalAmount = 0;
    $totalPieces = 0;
    $totalKilos = 0;

    if ($itemsStmt) {
        while ($item = sqlsrv_fetch_array($itemsStmt, SQLSRV_FETCH_ASSOC)) {
            $quantity = $item['Quantity'] ?: 0;
            $price = $item['PriceAtTime'] ?: 0;
            $weight = $item['Item_Weight'] ?: 0;
            $lineTotal = $quantity * $price;
            
            // Determine quantity type
            $qtyType = 'Piece';
            if ($weight > 0) {
                $qtyType = 'Kilo';
                $totalKilos += ($quantity * $weight);
            } else {
                $totalPieces += $quantity;
            }
            
            $items[] = [
                'id' => $item['TransactionItemID'],
                'item_id' => $item['Item_ID'],
                'name' => $item['Item_Name'] ?: 'Item #' . $item['Item_ID'],
                'category' => $item['Item_Category'] ?: 'Uncategorized',
                'quantity' => $quantity,
                'price' => $price,
                'qty_type' => $qtyType,
                'weight' => $weight,
                'stock' => $item['StockQuantity'],
                'amount' => $lineTotal
            ];

            $totalAmount += $lineTotal;
        }
    }

    return [
        'success' => true,
        'data' => [
            'id' => $header['TransactionID'],
            'date' => $header['Transaction_Date'] ? $header['Transaction_Date']->format('Y-m-d H:i:s') : 'N/A',
            'type' => $header['Transaction_Type'],
            'customer' => $header['CustomerName'] ?: 'Walk-in Customer',
            'employee' => $header['EmployeeName'] ?: 'N/A',
            'noOfItems' => $header['Total_No_Of_Items'] ?: count($items),
            'totalPiece' => $totalPieces,
            'totalKilo' => round($totalKilos, 2),
            'totalAmount' => round($totalAmount, 2),
            'items' => $items
        ]
    ];
}

function searchTransactionRecords($search) {
    global $conn;

    $searchTerm = '%' . $search . '%';

    // Search by transaction ID, customer name, or transaction type
    $sql = "SELECT DISTINCT
                t.TransactionID,
                t.Transaction_Date,
                t.Transaction_Type,
                t.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Transactions t
            LEFT JOIN Customer c ON t.Customer_ID = c.CustomerID
            LEFT JOIN Transaction_Items ti ON t.TransactionID = ti.TransactionID
            WHERE c.Name LIKE ? 
               OR CAST(t.TransactionID AS VARCHAR) LIKE ?
               OR t.Transaction_Type LIKE ?
               OR EXISTS (
                   SELECT 1 FROM Transaction_Items ti2 
                   JOIN Inventory i ON ti2.Item_ID = i.ItemID
                   WHERE ti2.TransactionID = t.TransactionID 
                   AND i.Item_Name LIKE ?
               )
            ORDER BY t.Transaction_Date DESC";

    $stmt = sqlsrv_query($conn, $sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);

    if (!$stmt) {
        $errors = sqlsrv_errors();
        return ['success' => false, 'error' => 'Search failed: ' . ($errors[0]['message'] ?? 'Unknown error')];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $totals = calculateTransactionTotals($row['TransactionID']);

        $records[] = [
            'id' => $row['TransactionID'],
            'date' => $row['Transaction_Date'] ? $row['Transaction_Date']->format('Y-m-d') : 'N/A',
            'type' => $row['Transaction_Type'],
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
    
    try {
        switch ($_POST['action']) {
            case 'get_records':
                echo json_encode(getTransactionRecords());
                break;
            case 'get_transaction_detail':
                if (!isset($_POST['transaction_id']) || empty($_POST['transaction_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Transaction ID is required']);
                    break;
                }
                echo json_encode(getTransactionDetail($_POST['transaction_id']));
                break;
            case 'search_records':
                $search = $_POST['search'] ?? '';
                echo json_encode(searchTransactionRecords($search));
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("Exception in AJAX handler: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
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
    <style>
        /* Loading and Error States */
        .loading, .empty, .error {
            padding: 40px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .error {
            color: #f56565;
        }
        
        .empty {
            color: #a0aec0;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2d3748;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #a0aec0;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-btn:hover {
            color: #718096;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .items-section {
            margin: 24px 0;
        }
        
        .items-section h3 {
            margin-bottom: 16px;
            color: #2d3748;
            font-size: 18px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #f7fafc;
            padding: 12px 16px;
            text-align: left;
            color: #4a5568;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-print, .btn-close {
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .btn-print {
            background: #4299e1;
            color: white;
        }
        
        .btn-print:hover {
            background: #3182ce;
        }
        
        .btn-close {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-close:hover {
            background: #cbd5e0;
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        }
        
        .notification.success {
            background: #48bb78;
        }
        
        .notification.error {
            background: #f56565;
        }
        
        .notification.info {
            background: #4299e1;
        }
        
        .notification.warning {
            background: #ed8936;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
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

        <div class="sidebar-footer">
            <img src="Logos-Icons/3.png" alt="ScrapTrack" class="footer-logo">
            <span class="footer-text">ScrapTrack</span>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div>
                <h1>Transaction Records</h1>
                <p class="subtitle">View and manage all transaction history</p>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                <div class="user-avatar">👤</div>
            </div>
        </header>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-bar-container">
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search by ID, customer, or item..." class="search-input">
                </div>
                <button class="filter-btn" id="filterBtn" title="Filter Options">
                    <span class="filter-icon">🔽</span>
                </button>
                <button class="add-btn" id="addBtn" title="Add New Transaction">Add</button>
            </div>
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
                            <th>CUSTOMER</th>
                            <th>ITEMS</th>
                            <th>PIECES</th>
                            <th>KILOS</th>
                            <th>AMOUNT</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTableBody">
                        <tr>
                            <td colspan="9" class="loading">Loading transaction records...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Transaction Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <button class="close-btn" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value" id="detailId">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time:</span>
                        <span class="detail-value" id="detailDate">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value" id="detailType">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value" id="detailCustomer">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Employee:</span>
                        <span class="detail-value" id="detailEmployee">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Items:</span>
                        <span class="detail-value" id="detailItems">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Pieces:</span>
                        <span class="detail-value" id="detailPiece">- pcs</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Kilos:</span>
                        <span class="detail-value" id="detailKilo">- kg</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value" id="detailAmount">₱0.00</span>
                    </div>
                </div>

                <div class="items-section">
                    <h3>Transaction Items</h3>
                    <div class="table-container">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>ITEM</th>
                                    <th>CATEGORY</th>
                                    <th>TYPE</th>
                                    <th>QUANTITY</th>
                                    <th>PRICE</th>
                                    <th>AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemsBody">
                                <tr>
                                    <td colspan="6" class="empty">Loading items...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn-print" onclick="printTransaction()">
                        <span class="print-icon">🖨️</span> Print Invoice
                    </button>
                    <button class="btn-close" onclick="closeDetailModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="TransactionRecords.js"></script>
</body>
</html>