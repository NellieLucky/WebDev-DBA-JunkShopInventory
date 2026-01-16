<?php
// ScrapTrack Dashboard - PHP Backend Structure
// This file is ready for database integration

// Start session for user management
session_start();

// TODO: Uncomment when database is ready
// include_once 'config/database.php';
// include_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

// Get user information (placeholder)
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ExoticNellie69';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Data functions backed by SQL Server (safe defaults if queries fail)

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
    if (!$conn) return 0.0;
    $sql = "SELECT SUM(ei.Quantity * ei.PriceAtTime) AS Total
            FROM Exchanged_Items ei
            JOIN Exchange e ON ei.Exchange_ID = e.ExchangeID
            WHERE CAST(e.Exchange_Date AS DATE) = CAST(GETDATE() AS DATE)
              AND e.Exchange_Type LIKE '%COMPLETED%'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0.0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return isset($row['Total']) ? floatval($row['Total']) : 0.0;
}

function getTotalItemsInStock() {
    global $conn;
    if (!$conn) return 0;
    $sql = "SELECT SUM(Item_Quantity) AS Total FROM Inventory";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return isset($row['Total']) ? intval($row['Total']) : 0;
}

function getTodayTransactionsCount() {
    global $conn;
    if (!$conn) return 0;
    $sql = "SELECT COUNT(*) AS Total FROM Exchange
            WHERE CAST(Exchange_Date AS DATE) = CAST(GETDATE() AS DATE)";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return isset($row['Total']) ? intval($row['Total']) : 0;
}

function getMostWeightedItem() {
    global $conn;
    if (!$conn) return 0;
    $sql = "SELECT TOP 1 ISNULL(Item_Weight, 0) AS MaxWeight
            FROM Inventory
            ORDER BY Item_Weight DESC";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 0;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return isset($row['MaxWeight']) ? floatval($row['MaxWeight']) : 0;
}

function getRecentTransactions($limit = 3) {
    global $conn;
    if (!$conn) return [];
    $sql = "SELECT TOP ($limit)
                e.Exchange_Type,
                e.Exchange_Date,
                e.Total_No_Of_Items,
                c.Name AS CustomerName
            FROM Exchange e
            JOIN Customer c ON e.Customer_ID = c.CustomerID
            ORDER BY e.Exchange_Date DESC";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return [];
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $etype = strtolower((string)$row['Exchange_Type']);
        $type = (strpos($etype, 'sell') !== false || strpos($etype, 'sold') !== false) ? 'sold'
              : ((strpos($etype, 'buy') !== false || strpos($etype, 'bought') !== false) ? 'bought' : 'exchange');
        $icon = $type === 'sold' ? '💰' : ($type === 'bought' ? '📦' : '🔄');
        $out[] = [
            'type' => $type,
            'item_name' => $row['CustomerName'],
            'quantity' => ($row['Total_No_Of_Items'] !== null ? intval($row['Total_No_Of_Items']).' items' : ''),
            'time_ago' => humanizeTimeAgo($row['Exchange_Date']),
            'icon' => $icon
        ];
    }
    return $out;
}

function getWeeklyRevenueData() {
    global $conn;
    // Prepare last 7 days labels
    $labels = [];
    $start = strtotime('-6 days');
    for ($i = 0; $i < 7; $i++) {
        $labels[] = date('l', strtotime("+$i day", $start));
    }
    $revenueMap = array_fill_keys($labels, 0.0);

    if ($conn) {
        $sql = "SELECT CAST(e.Exchange_Date AS DATE) AS d,
                       SUM(ei.Quantity * ei.PriceAtTime) AS Rev
                FROM Exchanged_Items ei
                JOIN Exchange e ON ei.Exchange_ID = e.ExchangeID
                WHERE e.Exchange_Date >= DATEADD(day, -6, CAST(GETDATE() AS DATE))
                GROUP BY CAST(e.Exchange_Date AS DATE)";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $dayname = date('l', strtotime($row['d']->format('Y-m-d')));
                $revenueMap[$dayname] = floatval($row['Rev']);
            }
        }
    }

    return [
        'labels' => $labels,
        'revenue' => array_values($revenueMap),
        'expense' => array_fill(0, 7, 0) // placeholder until expense tracking exists
    ];
}

function getTopItemsBySale($limit = 5) {
    global $conn;
    if (!$conn) return [];
    $sql = "SELECT TOP ($limit)
                i.Item_Name AS Name,
                SUM(ei.Quantity) AS TotalQty,
                SUM(ei.Quantity * ei.PriceAtTime) AS TotalSales
            FROM Exchanged_Items ei
            JOIN Inventory i ON ei.Item_ID = i.ItemID
            JOIN Exchange e ON ei.Exchange_ID = e.ExchangeID
            WHERE e.Exchange_Type LIKE '%COMPLETED%'
            GROUP BY i.Item_Name
            ORDER BY TotalSales DESC";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return [];
    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = [
            'name' => $row['Name'],
            'quantity' => intval($row['TotalQty']).' pcs',
            'price' => '₱'.number_format(floatval($row['TotalSales']), 2),
            'icon' => '📦'
        ];
    }
    return $items;
}

function getInventoryByWeight($limit = 5) {
    global $conn;
    if (!$conn) return ['total' => 0, 'items' => []];
    $sqlTotal = "SELECT SUM(ISNULL(Item_Weight,0)) AS TotalWeight FROM Inventory";
    $stmtTotal = sqlsrv_query($conn, $sqlTotal);
    $total = 0.0;
    if ($stmtTotal !== false) {
        $rowT = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
        $total = isset($rowT['TotalWeight']) ? floatval($rowT['TotalWeight']) : 0.0;
    }

    $sqlTop = "SELECT TOP ($limit) Item_Name, SUM(ISNULL(Item_Weight,0)) AS W
               FROM Inventory
               GROUP BY Item_Name
               ORDER BY W DESC";
    $stmtTop = sqlsrv_query($conn, $sqlTop);
    $colors = ['#E8B4F5', '#7DD3FC', '#FDE047', '#60A5FA', '#A78BFA'];
    $items = [];
    $i = 0;
    if ($stmtTop !== false && $total > 0) {
        while ($row = sqlsrv_fetch_array($stmtTop, SQLSRV_FETCH_ASSOC)) {
            $pct = ($row['W'] > 0) ? (floatval($row['W']) / $total) * 100.0 : 0.0;
            $items[] = [
                'name' => $row['Item_Name'],
                'percentage' => round($pct, 1),
                'color' => $colors[$i % count($colors)]
            ];
            $i++;
        }
    }
    return ['total' => round($total, 2), 'items' => $items];
}

function getNetProfit() {
    global $conn;
    if (!$conn) return '₱0.00';
    $sql = "SELECT SUM(ei.Quantity * (ei.PriceAtTime - i.Buying_Price)) AS Profit
            FROM Exchanged_Items ei
            JOIN Inventory i ON ei.Item_ID = i.ItemID
            JOIN Exchange e ON ei.Exchange_ID = e.ExchangeID
            WHERE e.Exchange_Type LIKE '%COMPLETED%'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return '₱0.00';
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $profit = isset($row['Profit']) ? floatval($row['Profit']) : 0.0;
    return '₱'.number_format($profit, 2);
}

// Get all dashboard data
$dashboard_data = [
    'username' => $username,
    'today_revenue' => getTodayRevenue(),
    'total_items' => getTotalItemsInStock(),
    'today_transactions' => getTodayTransactionsCount(),
    'most_weighted_item' => getMostWeightedItem(),
    'recent_transactions' => getRecentTransactions(),
    'weekly_revenue' => getWeeklyRevenueData(),
    'top_items' => getTopItemsBySale(),
    'inventory_weight' => getInventoryByWeight(),
    'net_profit' => getNetProfit()
];

// If this is an AJAX request, return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($dashboard_data);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ScrapTrack</title>
    <link rel="stylesheet" href="Dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="logo.png" alt="ScrapTrack Logo" class="logo-icon">
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
            <img src="logo.png" alt="ScrapTrack" class="footer-logo">
            <span class="footer-text">ScrapTrack</span>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <h1>Welcome Back, <span class="username"><?php echo htmlspecialchars($username); ?></span></h1>
            <p class="subtitle">Here's what's happening in your junk shop today</p>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Today's Revenue</p>
                        <h2 class="stat-value">₱<?php echo number_format($dashboard_data['today_revenue'], 2); ?></h2>
                        <p class="stat-sub">Total Revenue</p>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Total No. of Items</p>
                        <h2 class="stat-value"><?php echo $dashboard_data['total_items']; ?></h2>
                        <p class="stat-sub">In Stock</p>
                    </div>
                    <div class="stat-icon">📦</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Transactions Today</p>
                        <h2 class="stat-value"><?php echo $dashboard_data['today_transactions']; ?></h2>
                        <p class="stat-sub">Total Transactions</p>
                    </div>
                    <div class="stat-icon">💳</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <p class="stat-label">Most Weighted Item</p>
                        <h2 class="stat-value"><?php echo $dashboard_data['most_weighted_item']; ?> kg</h2>
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
                    <button class="btn-view">View More</button>
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
                    <button class="action-btn">
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
                <div class="transaction-list">
                    <?php foreach ($dashboard_data['recent_transactions'] as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-icon"><?php echo $transaction['icon']; ?></div>
                        <div class="transaction-info">
                            <p class="transaction-title"><?php echo ucfirst($transaction['type']); ?> <?php echo $transaction['type'] === 'customer' ? '' : 'Item'; ?></p>
                            <p class="transaction-detail"><?php echo htmlspecialchars($transaction['item_name']); ?><?php echo $transaction['quantity'] ? ' • ' . $transaction['quantity'] : ''; ?></p>
                        </div>
                        <span class="transaction-time"><?php echo $transaction['time_ago']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Weekly Transactions Chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h3>Weekly Transactions</h3>
                    <span class="total-badge">45 Total</span>
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
                    <select class="filter-select">
                        <option>This Day (December 23, 2025)</option>
                        <option>This Week</option>
                        <option>This Month</option>
                        <option>This Year</option>
                    </select>
                    <select class="filter-select">
                        <option>Highest to Lowest</option>
                        <option>Lowest to Highest</option>
                    </select>
                </div>
            </div>

            <!-- Net Profit Chart -->
            <div class="profit-card">
                <div class="card-header">
                    <h3>Net Profit</h3>
                    <h2 class="profit-value"><?php echo $dashboard_data['net_profit']; ?></h2>
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
                        <button class="btn-view">View All</button>
                    </div>
                    <div class="items-list">
                        <?php foreach ($dashboard_data['top_items'] as $item): ?>
                        <div class="item-row">
                            <div class="item-info">
                                <span class="item-icon"><?php echo $item['icon']; ?></span>
                                <div>
                                    <p class="item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="item-detail"><?php echo $item['quantity']; ?></p>
                                </div>
                            </div>
                            <span class="item-price"><?php echo $item['price']; ?></span>
                        </div>
                        <?php endforeach; ?>
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
                                <p class="pie-value"><?php echo number_format($dashboard_data['inventory_weight']['total']); ?> kg</p>
                            </div>
                        </div>
                        <div class="inventory-legend">
                            <?php foreach ($dashboard_data['inventory_weight']['items'] as $item): ?>
                            <div class="legend-item">
                                <span class="legend-color" style="background: <?php echo $item['color']; ?>;"></span>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <script src="Dashboard.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const dashboardData = <?php echo json_encode($dashboard_data); ?>;
        console.log('Dashboard data loaded:', dashboardData);
    </script>
</body>
</html>