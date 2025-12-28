<?php
// ScrapTrack Inventory - PHP Backend Structure
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
    }
    exit();
}

// Placeholder data functions (replace with database queries later)

function getAllInventoryItems() {
    // TODO: Replace with database query
    // $query = "SELECT * FROM inventory ORDER BY date_added DESC";
    
    return [
        'success' => true,
        'data' => [
            [
                'id' => 1,
                'name' => 'Glass Bottle',
                'category' => 'Babasagin',
                'qty_type' => 'Piraso',
                'quantity' => 69,
                'buying_price' => 69.00,
                'selling_price' => 69.00,
                'date_added' => '2025-12-23'
            ],
            [
                'id' => 2,
                'name' => 'White',
                'category' => 'Paper',
                'qty_type' => 'Kilo',
                'quantity' => 69,
                'buying_price' => 69.00,
                'selling_price' => 69.00,
                'date_added' => '2025-12-23'
            ],
            [
                'id' => 3,
                'name' => 'White',
                'category' => 'Paper',
                'qty_type' => 'Kilo',
                'quantity' => 69,
                'buying_price' => 69.00,
                'selling_price' => 69.00,
                'date_added' => '2025-12-23'
            ],
            [
                'id' => 4,
                'name' => 'White',
                'category' => 'Paper',
                'qty_type' => 'Kilo',
                'quantity' => 69,
                'buying_price' => 69.00,
                'selling_price' => 69.00,
                'date_added' => '2025-12-23'
            ],
            [
                'id' => 5,
                'name' => 'White',
                'category' => 'Paper',
                'qty_type' => 'Kilo',
                'quantity' => 69,
                'buying_price' => 69.00,
                'selling_price' => 69.00,
                'date_added' => '2025-12-23'
            ]
        ]
    ];
}

function addInventoryItem($data) {
    // TODO: Replace with database query
    // $query = "INSERT INTO inventory (name, category, qty_type, quantity, buying_price, selling_price, date_added, user_id) 
    //           VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    // $stmt = $conn->prepare($query);
    // $stmt->bind_param("sssdddi", $data['name'], $data['category'], $data['qty_type'], 
    //                    $data['quantity'], $data['buying_price'], $data['selling_price'], $user_id);
    // $success = $stmt->execute();
    
    return [
        'success' => true,
        'message' => 'Item added successfully!',
        'data' => [
            'id' => rand(100, 999), // Placeholder ID
            'name' => $data['name'],
            'category' => $data['category'],
            'qty_type' => $data['qty_type'],
            'quantity' => $data['quantity'],
            'buying_price' => $data['buying_price'],
            'selling_price' => $data['selling_price'],
            'date_added' => date('Y-m-d')
        ]
    ];
}

function updateInventoryItem($data) {
    // TODO: Replace with database query
    // $query = "UPDATE inventory SET name=?, category=?, qty_type=?, quantity=?, 
    //           buying_price=?, selling_price=? WHERE id=? AND user_id=?";
    // $stmt = $conn->prepare($query);
    // $stmt->bind_param("sssdddii", $data['name'], $data['category'], $data['qty_type'],
    //                    $data['quantity'], $data['buying_price'], $data['selling_price'],
    //                    $data['id'], $user_id);
    // $success = $stmt->execute();
    
    return [
        'success' => true,
        'message' => 'Item updated successfully!',
        'data' => $data
    ];
}

function deleteInventoryItem($id) {
    // TODO: Replace with database query
    // $query = "DELETE FROM inventory WHERE id=? AND user_id=?";
    // $stmt = $conn->prepare($query);
    // $stmt->bind_param("ii", $id, $user_id);
    // $success = $stmt->execute();
    
    return [
        'success' => true,
        'message' => 'Item deleted successfully!'
    ];
}

function searchInventoryItems($search) {
    // TODO: Replace with database query
    // $query = "SELECT * FROM inventory WHERE (name LIKE ? OR category LIKE ? OR qty_type LIKE ?) AND user_id=?";
    // $searchTerm = "%$search%";
    // $stmt = $conn->prepare($query);
    // $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $user_id);
    
    return [
        'success' => true,
        'data' => [] // Return filtered results
    ];
}

function getInventoryStats() {
    // TODO: Replace with database queries
    // Total items query
    // $query = "SELECT SUM(quantity) as total_items FROM inventory WHERE user_id=?";
    
    // Total weight query (only Kilo items)
    // $query = "SELECT SUM(quantity) as total_weight FROM inventory WHERE qty_type='Kilo' AND user_id=?";
    
    // Total value query
    // $query = "SELECT SUM(quantity * selling_price) as total_value FROM inventory WHERE user_id=?";
    
    return [
        'total_items' => 69,
        'total_weight' => 69,
        'total_value' => 69.00
    ];
}

$stats = getInventoryStats();
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
            <img src="logo.png" alt="ScrapTrack Logo" class="logo-icon">
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
            <img src="logo.png" alt="ScrapTrack" class="footer-logo">
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
                <p class="stat-label">Total No. of Items</p>
                <h2 class="stat-value" id="totalItems"><?php echo $stats['total_items']; ?></h2>
            </div>

            <div class="stat-card">
                <p class="stat-label">Total Weight of All Items</p>
                <h2 class="stat-value" id="totalWeight"><?php echo $stats['total_weight']; ?> kg</h2>
            </div>

            <div class="stat-card">
                <p class="stat-label">Total Value</p>
                <h2 class="stat-value" id="totalValue">₱<?php echo number_format($stats['total_value'], 2); ?></h2>
            </div>
        </section>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-header">
                <h3>Search Inventory</h3>
                <p class="search-subtitle">Find items by name, category or description</p>
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
                        <!-- Data will be loaded by JavaScript -->
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
                        <input type="text" id="itemCategory" name="category" required>
                    </div>
                    <div class="form-group">
                        <label for="qtyType">Quantity Type</label>
                        <select id="qtyType" name="qty_type" required>
                            <option value="">Select Type</option>
                            <option value="Kilo">Kilo</option>
                            <option value="Piraso">Piraso</option>
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

    <script src="Inventory.js"></script>
    <script>
        // PHP data available to JavaScript
        const phpData = {
            stats: <?php echo json_encode($stats); ?>,
            username: '<?php echo htmlspecialchars($username); ?>'
        };
        
        // Load inventory data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadInventoryFromPHP();
        });
        
        // Function to load inventory data via AJAX
        function loadInventoryFromPHP() {
            // In production, this would fetch from database via AJAX
            // fetch('Inventory.php', {
            //     method: 'POST',
            //     headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            //     body: 'action=get_all'
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         inventoryItems = data.data;
            //         renderInventoryTable();
            //     }
            // });
        }
        
        // Override the form submit to use PHP backend
        const originalHandleFormSubmit = handleFormSubmit;
        handleFormSubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('itemForm'));
            formData.append('action', currentEditId ? 'update' : 'add');
            
            // In production, send to PHP backend
            // fetch('Inventory.php', {
            //     method: 'POST',
            //     body: formData
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         showNotification(data.message, 'success');
            //         loadInventoryFromPHP();
            //         closeItemModal();
            //     }
            // });
            
            // For now, use the original function
            originalHandleFormSubmit(e);
        };
    </script>
</body>
</html>