<?php
// Simple Inventory Page - Connected to Database

// Include database connection
require_once 'db_connect.php';

// Check connection
if (!$conn) {
    die("Database connection failed: " . db_last_error());
}

// Function to get all inventory items
function getInventory() {
    global $conn;
    
    $sql = "SELECT i.ItemID, i.Item_Name, c.Category_Name, i.Item_Quantity, i.Item_Weight, i.Buying_Price, i.Selling_Price 
            FROM Inventory i 
            LEFT JOIN Category c ON i.CategoryID = c.CategoryID 
            ORDER BY i.Item_Name";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        return false;
    }
    
    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
    
    return $items;
}

// Get inventory data
$inventory = getInventory();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Connected to Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inventory Management System</h1>
        
        <?php if ($inventory === false): ?>
            <div class="status error">
                Error loading inventory: <?php echo db_last_error(); ?>
            </div>
        <?php elseif (empty($inventory)): ?>
            <div class="status success">
                Database connected successfully! No inventory items found.
            </div>
        <?php else: ?>
            <div class="status success">
                Database connected successfully! Found <?php echo count($inventory); ?> inventory items.
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Weight (kg)</th>
                        <th>Buying Price</th>
                        <th>Selling Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['ItemID']); ?></td>
                            <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                            <td><?php echo htmlspecialchars($item['Category_Name'] ?? 'No Category'); ?></td>
                            <td><?php echo htmlspecialchars($item['Item_Quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['Item_Weight'] ?? 0); ?></td>
                            <td>₱<?php echo number_format($item['Buying_Price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['Selling_Price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>