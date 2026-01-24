<?php
// ScrapTrack Inventory - PHP Backend Structure
// This file is ready for database integration

// Start session for user management
session_start();

// Check if user is logged in (placeholder)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// DB connection 
require_once __DIR__ . '/db_connect.php';

// Get user information
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ExoticNellie69';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Handle AJAX requests for CRUD operations
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add':
            echo json_encode(addInventoryItem($_POST));
            break;
        case 'update':
            echo json_encode(updateInventoryItem($_POST));
            break;
        case 'delete':
            echo json_encode(deleteInventoryItem($_POST['id']));
            break;
        case 'get_all':
            echo json_encode(getAllInventoryItems());
            break;
        case 'search':
            echo json_encode(searchInventoryItems($_POST['search']));
            break;
        case 'get_categories':
            echo json_encode(['success' => true, 'data' => getCategories()]);
            break;
    }
    exit();
}

function getInventoryStats() {
    global $conn;

    $stmt = sqlsrv_query($conn, "EXEC sp_GetInventoryStats");
    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

$stats = getInventoryStats();

function getCategories() {
    global $conn;

    if (!$conn) {
        return []; // prevents fatal error
    }

    // Use direct query to get categories
    $sql = "SELECT CategoryID, Category_Name FROM Categories ORDER BY Category_Name";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        // Log error for debugging
        error_log("getCategories query failed: " . print_r(sqlsrv_errors(), true));
        return [];
    }

    $cats = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cats[] = $row;
    }

    return $cats;
}

$categories = getCategories();

// Get inventory data for initial display
function getAllInventoryItems() {
    global $conn;

    // Try direct query first
    $sql = "SELECT i.ItemID, i.Item_Name, i.CategoryID, c.Category_Name, i.Item_Quantity, i.Item_Weight, i.Buying_Price, i.Selling_Price 
            FROM Inventory i 
            LEFT JOIN Categories c ON i.CategoryID = c.CategoryID 
            ORDER BY i.Item_Name";
    
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        // Fallback to stored procedure
        $sql = "EXEC sp_GetInventory";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt === false) {
            return ['success' => false, 'error' => sqlsrv_errors()];
        }
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $qty_type = $row['Item_Weight'] > 0 ? 'Kilo' : 'Piraso';
        $data[] = [
            'id' => $row['ItemID'] ?? $row['ItemId'],
            'name' => $row['Item_Name'],
            'category_id' => $row['CategoryID'],
            'category' => $row['Category_Name'],
            'qty_type' => $qty_type,
            'quantity' => $row['Item_Quantity'],
            'weight' => $row['Item_Weight'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price']
        ];
    }

    return ['success' => true, 'data' => $data];
}

$inventoryData = getAllInventoryItems();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - ScrapTrack</title>
    <link rel="stylesheet" href="Inventory.css">
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
                <a href="Inventory.php" class="nav-item active">
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
            <div>
                <h1>Inventory</h1>
                <p class="subtitle">Manage inventory and their categories</p>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">Total No. of Items (By Piece)</p>
                <h2 class="stat-value" id="TotalItems"><?php echo $stats['TotalItems']; ?></h2>
            </div>

            <div class="stat-card">
                <p class="stat-label">Total No. of Items (By Weight)</p>
                <h2 class="stat-value" id="TotalWeight"><?php echo $stats['TotalWeight']; ?> kg</h2>
            </div>

            <div class="stat-card">
                <p class="stat-label">Total Selling Price</p>
                <h2 class="stat-value" id="TotalSellingPrice">₱<?php echo number_format($stats['TotalSellingPrice'], 2); ?></h2>
            </div>
        </section>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-header">
                <h3>Search Inventory</h3>
                <p class="search-subtitle">Find items by name, category or quantity type</p>
            </div>
            
            <div class="search-bar-container">
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search Inventory...." class="search-input">
                </div>
                <button class="filter-btn" id="filterBtn">
                    <span class="filter-icon">🔽</span>
                </button>
            </div>
        </section>

        <!-- Inventory Items -->
        <section class="inventory-section">
            <div class="inventory-header">
                <div>
                    <h3>Inventory Items</h3>
                    <p class="items-count" id="itemsCount">69 items found</p>
                </div>
                <button class="add-item-btn" id="addItemBtn">
                    <span class="plus-icon">+</span>
                    Add Item
                </button>
            </div>

            <!-- Inventory Table -->
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>CATEGORY</th>
                            <th>QTY TYPE</th>
                            <th>QUANTITY</th>
                            <th>BUYING PRICE</th>
                            <th>SELLING PRICE</th>
                            <th>DATE ADDED</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <?php if ($inventoryData['success'] && !empty($inventoryData['data'])): ?>
                            <?php foreach ($inventoryData['data'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($item['qty_type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']) . ($item['qty_type'] === 'Kilo' ? ' kg' : ' pieces'); ?></td>
                                    <td style="text-align: left;">₱<?php echo number_format($item['buying_price'], 2); ?></td>
                                    <td style="text-align: left;">₱<?php echo number_format($item['selling_price'], 2); ?></td>
                                    <td><?php echo date('Y-m-d'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit-btn" onclick="editItem(<?php echo $item['id']; ?>)" title="Edit">
                                                <span>✏️</span>
                                            </button>
                                            <button class="action-btn delete-btn" onclick="deleteItem(<?php echo $item['id']; ?>)" title="Delete">
                                                <span>🗑️</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">
                                    <?php echo $inventoryData['success'] ? 'No items found' : 'Error loading inventory'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add/Edit Item Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Item</h2>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <form id="itemForm">
                <input type="hidden" id="itemId" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="itemCategory">Category</label>
                        <select id="itemCategory" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['CategoryID']) ?>"><?= htmlspecialchars($cat['Category_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="qtyType">Quantity Type</label>
                        <select id="qtyType" name="qty_type" required>
                            <option value="">Select Type</option>
                            <option value="by kilo">by kilo</option>
                            <option value="by piece">by piece</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label for="buyingPrice">Buying Price</label>
                        <input type="number" id="buyingPrice" name="buying_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="sellingPrice">Selling Price</label>
                        <input type="number" id="sellingPrice" name="selling_price" step="0.01" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-save">Save Item</button>
                </div>
            </form>
        </div>
    </div>

<script src="Inventory.js"></script><script src="user-session.js"></script><script src="logout-dialog.js"></script>
</body>
</html>