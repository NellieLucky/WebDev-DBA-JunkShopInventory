<?php
/**
 * DIAGNOSTIC SCRIPT - Run this to identify the issue
 * Access via: http://localhost/WebDev-DBA-JunkShopInventory/diagnose.php
 */

echo "<h1>🔍 Inventory System Diagnostic Report</h1>";
echo "<hr>";

// Step 1: Check Database Connection
echo "<h2>Step 1: Database Connection</h2>";
require_once __DIR__ . '/db_connect.php';

if ($conn) {
    echo "<p style='color: green;'><strong>✓ Database Connected Successfully</strong></p>";
    echo "<p>Server: localhost\\SQLEXPRESS</p>";
    echo "<p>Database: JSDatabase</p>";
} else {
    echo "<p style='color: red;'><strong>✗ Database Connection Failed</strong></p>";
    echo "<p>Check your db_connect.php file and SQL Server configuration</p>";
    die();
}

// Step 2: Check if Inventory table exists and has data
echo "<h2>Step 2: Inventory Table Data</h2>";

$inventorySql = "SELECT COUNT(*) as total FROM Inventory";
$inventoryStmt = sqlsrv_query($conn, $inventorySql);

if ($inventoryStmt === false) {
    echo "<p style='color: red;'><strong>✗ Inventory table query failed</strong></p>";
    echo "<pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    $row = sqlsrv_fetch_array($inventoryStmt, SQLSRV_FETCH_ASSOC);
    $count = $row['total'];
    
    if ($count > 0) {
        echo "<p style='color: green;'><strong>✓ Inventory table has $count items</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠ Inventory table is EMPTY</strong></p>";
        echo "<p>You need to add items to the inventory. Use the Inventory page to add items.</p>";
    }
}

// Step 3: Check Category table
echo "<h2>Step 3: Category Table Data</h2>";

$categorySql = "SELECT COUNT(*) as total FROM Category";
$categoryStmt = sqlsrv_query($conn, $categorySql);

if ($categoryStmt === false) {
    echo "<p style='color: red;'><strong>✗ Category table query failed</strong></p>";
    echo "<pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    $row = sqlsrv_fetch_array($categoryStmt, SQLSRV_FETCH_ASSOC);
    $count = $row['total'];
    
    if ($count > 0) {
        echo "<p style='color: green;'><strong>✓ Category table has $count categories</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠ Category table is EMPTY</strong></p>";
        echo "<p>You need to add categories first.</p>";
    }
}

// Step 4: Test the API endpoint
echo "<h2>Step 4: API Endpoint Test</h2>";

$testData = getAllInventoryItems();
if (isset($testData['success']) && $testData['success']) {
    $count = count($testData['data']);
    echo "<p style='color: green;'><strong>✓ API returns $count items successfully</strong></p>";
    
    // Show sample data
    if ($count > 0) {
        echo "<h3>Sample Data (First 3 items):</h3>";
        echo "<pre>";
        for ($i = 0; $i < min(3, $count); $i++) {
            echo "Item " . ($i+1) . ": " . $testData['data'][$i]['name'] . " (" . $testData['data'][$i]['category'] . ")\n";
        }
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ API Error</strong></p>";
    echo "<pre>";
    print_r($testData);
    echo "</pre>";
}

// Step 5: Check JavaScript paths
echo "<h2>Step 5: Frontend Files</h2>";

$requiredFiles = [
    'Inventory.html',
    'Inventory.js',
    'Inventory.css',
    'inventory_api.php'
];

foreach ($requiredFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color: green;'><strong>✓ $file exists</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ $file missing</strong></p>";
    }
}

// Function from inventory_api.php
function getAllInventoryItems() {
    global $conn;

    $sql = "SELECT i.ItemID, i.Item_Name, i.CategoryID, c.Category_Name, i.Item_Quantity, i.Item_Weight, i.Buying_Price, i.Selling_Price 
            FROM Inventory i 
            LEFT JOIN Categories c ON i.CategoryID = c.CategoryID 
            ORDER BY i.Item_Name";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return ['success' => false, 'error' => sqlsrv_errors()];
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Calculate qty_type based on Item_Weight
        $qty_type = $row['Item_Weight'] > 0 ? 'by kilo' : 'by piece';
        $data[] = [
            'id' => $row['ItemID'],
            'name' => $row['Item_Name'],
            'category_id' => $row['CategoryID'],
            'category' => $row['Category_Name'] ?? 'No Category',
            'qty_type' => $qty_type,
            'quantity' => $row['Item_Quantity'],
            'weight' => $row['Item_Weight'],
            'buying_price' => $row['Buying_Price'],
            'selling_price' => $row['Selling_Price']
        ];
    }

    return ['success' => true, 'data' => $data];
}

// Summary
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>If all checks show ✓:</strong> The system is working. Make sure you're accessing Inventory.html through a web server (http://localhost/...)</p>";
echo "<p><strong>If Category is empty:</strong> Go to Inventory page and create categories first before adding items.</p>";
echo "<p><strong>If Inventory is empty:</strong> Go to Inventory page and add items.</p>";
echo "<p><strong>If API shows error:</strong> Check the error message above and contact support.</p>";

?>
