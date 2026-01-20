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
                e.ExchangeID,
                e.Exchange_Date,
                e.Exchange_Type,
                e.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Exchange e
            JOIN Customer c ON e.Customer_ID = c.CustomerID
            ORDER BY e.Exchange_Date DESC";

    $stmt = sqlsrv_query($conn, $sql);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to fetch transaction records'];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Calculate totals for this transaction
        $totals = calculateTransactionTotals($row['ExchangeID']);

        $records[] = [
            'id' => $row['ExchangeID'],
            'date' => $row['Exchange_Date'] ? $row['Exchange_Date']->format('Y-m-d') : 'N/A',
            'type' => strpos($row['Exchange_Type'], 'Purchase') !== false ? 'Received' : 'Dispatched',
            'customer' => $row['CustomerName'],
            'noOfItems' => $row['Total_No_Of_Items'],
            'totalPiece' => $totals['pieces'],
            'totalKilo' => $totals['kilos'],
            'totalAmount' => $totals['amount']
        ];
    }

    return ['success' => true, 'data' => $records];
}

function calculateTransactionTotals($exchangeId) {
    global $conn;

    $sql = "SELECT
                ei.Quantity,
                ei.PriceAtTime,
                i.Item_Weight,
                cat.Category_Name
            FROM Exchanged_Items ei
            JOIN Inventory i ON ei.Item_ID = i.ItemID
            LEFT JOIN Categories cat ON i.CategoryID = cat.CategoryID
            WHERE ei.Exchange_ID = ?";

    $stmt = sqlsrv_query($conn, $sql, [$exchangeId]);

    $pieces = 0;
    $kilos = 0;
    $amount = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $amount += ($row['Quantity'] * $row['PriceAtTime']);

        // Determine if it's counted by piece or kilo based on category
        $weightCategories = ['Paper', 'Metals', 'Plastics'];
        if ($row['Category_Name'] && in_array($row['Category_Name'], $weightCategories)) {
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
                    e.ExchangeID,
                    e.Exchange_Date,
                    e.Exchange_Type,
                    e.Total_No_Of_Items,
                    c.Name AS CustomerName,
                    m.FirstName AS EmployeeName
                FROM Exchange e
                JOIN Customer c ON e.Customer_ID = c.CustomerID
                LEFT JOIN Management m ON e.Employee_ID = m.ManagementID
                WHERE e.ExchangeID = ?";

    $headerStmt = sqlsrv_query($conn, $headerSql, [$transactionId]);

    if (!$headerStmt || !($header = sqlsrv_fetch_array($headerStmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }

    // Get transaction items
    $itemsSql = "SELECT
                    i.Item_Name,
                    cat.Category_Name,
                    ei.Quantity,
                    ei.PriceAtTime,
                    (ei.Quantity * ei.PriceAtTime) as LineTotal
                FROM Exchanged_Items ei
                JOIN Inventory i ON ei.Item_ID = i.ItemID
                LEFT JOIN Categories cat ON i.CategoryID = cat.CategoryID
                WHERE ei.Exchange_ID = ?";

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
            'amount' => $item['LineTotal']
        ];

        $totalAmount += $item['LineTotal'];

        // Categorize by weight vs pieces
        $weightCategories = ['Paper', 'Metals', 'Plastics'];
        if ($item['Category_Name'] && in_array($item['Category_Name'], $weightCategories)) {
            $totalKilos += $item['Quantity'];
        } else {
            $totalPieces += $item['Quantity'];
        }
    }

    return [
        'success' => true,
        'data' => [
            'id' => $header['ExchangeID'],
            'date' => $header['Exchange_Date'] ? $header['Exchange_Date']->format('Y-m-d') : 'N/A',
            'type' => strpos($header['Exchange_Type'], 'Purchase') !== false ? 'Received' : 'Dispatched',
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
                e.ExchangeID,
                e.Exchange_Date,
                e.Exchange_Type,
                e.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Exchange e
            JOIN Customer c ON e.Customer_ID = c.CustomerID
            WHERE c.Name LIKE ? OR CAST(e.ExchangeID AS VARCHAR) LIKE ?
            ORDER BY e.Exchange_Date DESC";

    $stmt = sqlsrv_query($conn, $sql, [$searchTerm, $searchTerm]);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to search transaction records'];
    }

    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $totals = calculateTransactionTotals($row['ExchangeID']);

        $records[] = [
            'id' => $row['ExchangeID'],
            'date' => $row['Exchange_Date'] ? $row['Exchange_Date']->format('Y-m-d') : 'N/A',
            'type' => strpos($row['Exchange_Type'], 'Purchase') !== false ? 'Received' : 'Dispatched',
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
            <button class="filter-btn" id="filterBtn">Filter</button>
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

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Filter Transactions</h2>
                <button class="close-btn" id="filterModalCloseBtn" title="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="detail-item">
                        <span class="detail-label">Type</span>
                        <select id="filterType" style="padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="all">All</option>
                            <option value="received">Received</option>
                            <option value="dispatched">Dispatched</option>
                        </select>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Quick Presets</span>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <button class="add-btn" id="presetTodayBtn" type="button">Today</button>
                            <button class="add-btn" id="presetWeekBtn" type="button">This Week</button>
                            <button class="add-btn" id="presetMonthBtn" type="button">This Month</button>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Custom Month</span>
                        <select id="filterMonth" style="padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">Select Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Custom Year</span>
                        <select id="filterYear" style="padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">Select Year</option>
                            <?php
                                $currentYear = (int)date('Y');
                                for ($y = $currentYear - 10; $y <= $currentYear + 2; $y++) {
                                    echo '<option value="' . $y . '">' . $y . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Start Date</span>
                        <input type="date" id="filterStartDate" style="padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;" />
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">End Date</span>
                        <input type="date" id="filterEndDate" style="padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;" />
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn-print" id="filterModalApplyBtn" type="button">Apply Filters</button>
                    <button class="btn-close" id="filterModalClearBtn" type="button">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <script src="TransactionRecords.js"></script>
</body>
</html>
