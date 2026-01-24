<?php
// Dashboard.php - PHP Backend
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle AJAX requests for dashboard data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Get actual data from database
$dashboard_data = [
    'today_revenue' => getTodayRevenue(),
    'total_items' => getTotalItemsInStock(),
    'today_transactions' => getTodayTransactionsCount(),
    'most_weighted_item' => getMostWeightedItem(),
    'net_profit' => getNetProfit(),
    'recent_transactions' => getRecentTransactions(5),
    'weekly_revenue' => getWeeklyRevenueData(),
    'top_items' => getTopItemsBySale(),
    'inventory_weight' => getInventoryByWeight()
];


    
    echo json_encode($dashboard_data);
    exit();
}

// Customer modal handling
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $type = trim($_POST['customer_type'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $message = 'Customer name is required.';
    } else {
        $sql = "{call sp_AddCustomer(?, ?, ?, ?)}";
        $params = [$name, $type ?: null, $contact ?: null, $address ?: null];
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt && sqlsrv_execute($stmt)) {
            $message = 'Customer added successfully!';
        } else {
            $message = 'Failed to add customer. Please try again.';
        }
    }
}

// Get user info for initial page load
$username = $_SESSION['username'] ?? 'ExoticNellie69';

// Data functions (keep these as in your original)
function humanizeTimeAgo($datetime) {
    if (!$datetime) return '';
    try {
        $ts = is_object($datetime) ? $datetime->getTimestamp() : strtotime($datetime);
        $diff = time() - $ts;
        if ($diff < 60) return 'just now';
        $units = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute'
        ];
        foreach ($units as $sec => $name) {
            if ($diff >= $sec) {
                $val = floor($diff / $sec);
                return $val . ' ' . $name . ($val > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    } catch (Throwable $e) {
        return '';
    }
}

function getTodayRevenue() {
    global $conn;

    $sql = "
        SELECT SUM(ti.LineTotal) AS Total
        FROM dbo.vw_TransactionItems ti
        JOIN dbo.vw_TransactionSummary ts
            ON ti.TransactionID = ts.TransactionID
        WHERE CAST(ts.Transaction_Date AS DATE) = CAST(GETDATE() AS DATE)
          AND ts.Transaction_Type LIKE '%COMPLETED%'
    ";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['Total'] ?? 0;
}

function getTotalItemsInStock() {
    global $conn;
    $sql = "{CALL dbo.sp_GetInventoryStats}";
    $stmt = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['TotalItems'] ?? 0;
}

function getTodayTransactionsCount() {
    global $conn;

    $sql = "
        SELECT COUNT(*) AS Total
        FROM dbo.vw_TransactionSummary
        WHERE CAST(Transaction_Date AS DATE) = CAST(GETDATE() AS DATE)
    ";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['Total'] ?? 0;
}


function getMostWeightedItem() {
    global $conn;

    $sql = "SELECT MAX(Item_Weight) AS MaxWeight FROM dbo.vw_InventoryOverview";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['MaxWeight'] ?? 0;
}

function getRecentTransactions($limit = 5) {
    global $conn;

    $sql = "{CALL dbo.sp_GetAllTransactions}";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) return [];

    $out = [];
    $count = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($count++ >= $limit) break;

        $out[] = [
            'type' => strtolower($row['Transaction_Type']),
            'item_name' => $row['CustomerName'],
            'quantity' => $row['Total_No_Of_Items'] . ' items',
            'time_ago' => humanizeTimeAgo($row['Transaction_Date']),
            'icon' => '🔄'
        ];
    }

    return $out;
}

function getWeeklyRevenueData() {
    global $conn;

    $sql = "{CALL dbo.sp_GetWeeklyRevenue}";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return ['labels' => [], 'revenue' => [], 'expense' => []];
    }

    $labels = [];
    $revenue = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $labels[] = $row['TransactionDate'];   // adjust if named differently
        $revenue[] = $row['TotalRevenue'];
    }

    return [
        'labels' => $labels,
        'revenue' => $revenue,
        'expense' => array_fill(0, count($labels), 0)
    ];
}


function getTopItemsBySale() {
    global $conn;

    $sql = "{CALL dbo.sp_GetTop5Sales}";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) return [];

    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'name' => $row['Item_Name'],
            'quantity' => ($row['Quantity'] ?? 0) . ' pcs',
            'price' => '₱' . number_format($row['LineTotal'] ?? 0, 2),
            'icon' => '📦'
        ];
    }
    return $items;
}


function getInventoryByWeight() {
    global $conn;
    $sql = "{CALL dbo.sp_GetInventoryByWeightASC}";
    $stmt = sqlsrv_query($conn, $sql);

    $items = [];
    $total = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'name' => $row['Item_Name'],
            'weight' => $row['Item_Weight']
        ];
        $total += $row['Item_Weight'];
    }

    return ['total' => $total, 'items' => $items];
}

function getNetProfit() {
    return getTodayRevenue();
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ScrapTrack</title>
    <link rel="stylesheet" href="Dashboard.css">
    <!-- Load Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="Dashboard.php" class="nav-item active">
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
                <a href="TransactionRecords.php" class="nav-item">
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
            <h1>Welcome Back, <span class="username" id="username"><?php echo htmlspecialchars($username); ?></span></h1>
            <p class="subtitle">Here's what's happening in your junk shop today</p>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Today's Revenue</p>
                        <h2 class="stat-value" id="today-revenue">₱0.00</h2>
                        <p class="stat-sub">Total Revenue</p>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Total No. of Items</p>
                        <h2 class="stat-value" id="total-items">0</h2>
                        <p class="stat-sub">In Stock</p>
                    </div>
                    <div class="stat-icon">📦</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Transactions Today</p>
                        <h2 class="stat-value" id="today-transactions">0</h2>
                        <p class="stat-sub">Total Transactions</p>
                    </div>
                    <div class="stat-icon">💳</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Most Weighted Item</p>
                        <h2 class="stat-value" id="most-weighted">0 kg</h2>
                        <p class="stat-sub">In Stock</p>
                    </div>
                    <div class="stat-icon">⚖️</div>
                </div>
            </div>
        </section>

        <!-- Charts and Actions Row -->
        <section class="content-row">
            <!-- Revenue Chart -->
            <div class="chart-card large">
                <div class="card-header">
                    <h3>Revenue & Expense Trend (7 days)</h3>
                    <button class="btn-view" onclick="window.location.href='TransactionRecords.php'">View More</button>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="actions-card">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <button class="action-btn" onclick="window.location.href='Transaction.php'">
                        <span class="action-icon">➕</span>
                        <span>New Transaction</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='Inventory.php?action=add'">
                        <span class="action-icon">📝</span>
                        <span>Add Item</span>
                    </button>
                    <button class="action-btn" onclick="openCustomerModal()">
                        <span class="action-icon">👤</span>
                        <span>New Customer</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='EmployeeManagement.php?action=add'">
                        <span class="action-icon">👷</span>
                        <span>New Employee</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Recent Transactions and Weekly Chart -->
        <section class="content-row">
            <!-- Recent Transactions -->
            <div class="transactions-card">
                <div class="card-header">
                    <h3>Recent Transactions</h3>
                    <button class="btn-view" onclick="window.location.href='TransactionRecords.php'">View All</button>
                </div>
                <div class="transaction-list" id="recent-transactions">
                    <!-- Will be populated by JavaScript -->
                    <div class="loading">Loading transactions...</div>
                </div>
            </div>

            <!-- Weekly Transactions Chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h3>Weekly Transactions</h3>
                    <span class="total-badge" id="weekly-total">0 Total</span>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Bottom Section with Charts -->
        <section class="bottom-section">
            <div class="section-header">
                <h2>Here's what the charts talks about</h2>
                <div class="filters">
                    <select class="filter-select" id="timeFilter">
                        <option value="day">This Day</option>
                        <option value="week" selected>This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                    <select class="filter-select" id="sortFilter">
                        <option value="desc">Highest to Lowest</option>
                        <option value="asc">Lowest to Highest</option>
                    </select>
                </div>
            </div>

            <!-- Net Profit Chart -->
            <div class="profit-card">
                <div class="card-header">
                    <h3>Net Profit</h3>
                    <h2 class="profit-value" id="net-profit">₱0.00</h2>
                </div>
                <div class="chart-container large">
                    <canvas id="profitChart"></canvas>
                </div>
            </div>

            <!-- Bottom Row -->
            <section class="content-row">
                <!-- Top 5 Items -->
                <div class="top-items-card">
                    <div class="card-header">
                        <h3>Top 5 Items by Sale</h3>
                        <button class="btn-view" onclick="window.location.href='TransactionRecords.php'">View All</button>
                    </div>
                    <div class="items-list" id="top-items">
                        <!-- Will be populated by JavaScript -->
                        <div class="loading">Loading top items...</div>
                    </div>
                </div>

                <!-- Inventory by Weight -->
                <div class="inventory-card">
                    <div class="card-header">
                        <h3>Inventory by Weight (Top 5)</h3>
                        <button class="btn-view" onclick="window.location.href='Inventory.php'">View All</button>
                    </div>
                    <div class="inventory-content">
                        <div class="pie-chart">
                            <canvas id="inventoryPie"></canvas>
                            <div class="pie-center">
                                <p class="pie-label">Total:</p>
                                <p class="pie-value" id="inventory-total">0 kg</p>
                            </div>
                        </div>
                        <div class="inventory-legend" id="inventory-legend">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <!-- Customer Modal -->
    <div id="customerModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Customer</h2>
                <span class="close" onclick="closeCustomerModal()">&times;</span>
            </div>
            <form method="POST" action="Dashboard.php">
                <div class="modal-body">
                    <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="customer_name">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_type">Customer Type</label>
                        <select id="customer_type" name="customer_type">
                            <option value="">Select Type</option>
                            <option value="Regular">Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="Retail">Retail</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCustomerModal()">Cancel</button>
                    <button type="submit" name="add_customer" class="btn-submit">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="Dashboard.js"></script>
</body>
</html>