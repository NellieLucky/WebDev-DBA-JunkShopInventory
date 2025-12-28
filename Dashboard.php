<?php
// ScrapTrack Dashboard - PHP Backend Structure
// This file is ready for database integration

// Start session for user management
session_start();

// TODO: Uncomment when database is ready
// include_once 'config/database.php';
// include_once 'includes/functions.php';

// Check if user is logged in (placeholder)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Get user information (placeholder)
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ExoticNellie69';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Placeholder data functions (replace with database queries later)

function getTodayRevenue() {
    // TODO: Replace with database query
    // $query = "SELECT SUM(amount) as total FROM transactions WHERE DATE(created_at) = CURDATE() AND type = 'sale'";
    return 69.00;
}

function getTotalItemsInStock() {
    // TODO: Replace with database query
    // $query = "SELECT SUM(quantity) as total FROM inventory WHERE quantity > 0";
    return 89;
}

function getTodayTransactionsCount() {
    // TODO: Replace with database query
    // $query = "SELECT COUNT(*) as total FROM transactions WHERE DATE(created_at) = CURDATE()";
    return 2;
}

function getMostWeightedItem() {
    // TODO: Replace with database query
    // $query = "SELECT MAX(weight) as max_weight FROM inventory";
    return 65;
}

function getRecentTransactions($limit = 3) {
    // TODO: Replace with database query
    // $query = "SELECT * FROM transactions ORDER BY created_at DESC LIMIT $limit";
    
    return [
        [
            'type' => 'bought',
            'item_name' => 'Tin Cans',
            'quantity' => '10 pcs',
            'time_ago' => '5 minutes ago',
            'icon' => '📦'
        ],
        [
            'type' => 'sold',
            'item_name' => 'Paper Boxes',
            'quantity' => '23 kg',
            'time_ago' => '5 minutes ago',
            'icon' => '💰'
        ],
        [
            'type' => 'customer',
            'item_name' => 'Christine Candasan',
            'quantity' => '',
            'time_ago' => '5 minutes ago',
            'icon' => '👤'
        ]
    ];
}

function getWeeklyRevenueData() {
    // TODO: Replace with database query
    // $query = "SELECT DATE(created_at) as date, SUM(amount) as revenue 
    //           FROM transactions WHERE type = 'sale' 
    //           AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    //           GROUP BY DATE(created_at)";
    
    return [
        'labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        'revenue' => [6000, 6500, 3000, 5000, 4500, 5500, 5000],
        'expense' => [4000, 4500, 2000, 5500, 3000, 6000, 14000]
    ];
}

function getTopItemsBySale($limit = 5) {
    // TODO: Replace with database query
    // $query = "SELECT item_name, SUM(quantity) as total_qty, SUM(total_amount) as total_sales
    //           FROM transaction_items GROUP BY item_name ORDER BY total_sales DESC LIMIT $limit";
    
    return [
        ['name' => 'Tin Cans', 'quantity' => '438 pcs', 'price' => '₱6,942.00', 'icon' => '🥫'],
        ['name' => 'White Paper', 'quantity' => '294 kg', 'price' => '₱4,708.00', 'icon' => '📄'],
        ['name' => 'Metal', 'quantity' => '203 kg', 'price' => '₱4,671.00', 'icon' => '🔩'],
        ['name' => 'Cardboard', 'quantity' => '171 kg', 'price' => '₱2,056.00', 'icon' => '📦'],
        ['name' => 'Plastic Bottles', 'quantity' => '644 pcs', 'price' => '₱2,903.00', 'icon' => '🍾']
    ];
}

function getInventoryByWeight($limit = 5) {
    // TODO: Replace with database query
    // $query = "SELECT item_name, SUM(weight) as total_weight 
    //           FROM inventory GROUP BY item_name ORDER BY total_weight DESC LIMIT $limit";
    
    return [
        'total' => 1325,
        'items' => [
            ['name' => 'White Paper', 'percentage' => 30.2, 'color' => '#E8B4F5'],
            ['name' => 'Cardboard Box', 'percentage' => 22.6, 'color' => '#7DD3FC'],
            ['name' => 'Tin Cans', 'percentage' => 18.9, 'color' => '#FDE047'],
            ['name' => 'Plastic Bottles', 'percentage' => 15.1, 'color' => '#60A5FA'],
            ['name' => 'Metals', 'percentage' => 13.2, 'color' => '#A78BFA']
        ]
    ];
}

function getNetProfit() {
    // TODO: Replace with database query
    // $query = "SELECT 
    //           (SELECT SUM(amount) FROM transactions WHERE type = 'sale') - 
    //           (SELECT SUM(amount) FROM transactions WHERE type = 'purchase') as net_profit";
    
    return '₱67,676,767.00';
}

// Get all dashboard data
$dashboard_data = [
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