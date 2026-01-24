<?php
require_once __DIR__ . '/db_connect.php';

echo "<h2>Migrate to Categories Table (Plural)</h2>";

// Step 1: Remove foreign key constraint if it exists
echo "<h3>Step 1: Remove old foreign key constraint</h3>";
$dropFKSQL = "ALTER TABLE Inventory DROP CONSTRAINT FK_Inventory_Category";
$result = sqlsrv_query($conn, $dropFKSQL);
if ($result || (sqlsrv_errors() && strpos(sqlsrv_errors()[0]['message'], 'does not exist') !== false)) {
    echo "✓ Foreign key removed or didn't exist<br>";
}

// Step 2: Add data from Category to Categories if Categories is empty
echo "<h3>Step 2: Migrating data</h3>";
$query = "SELECT COUNT(*) as cnt FROM Categories";
$result = sqlsrv_query($conn, $query);
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

if ($row['cnt'] == 0) {
    echo "Categories table is empty, copying from Category table...<br>";
    $copySQL = "INSERT INTO Categories (Category_Name) SELECT Category_Name FROM Category";
    sqlsrv_query($conn, $copySQL);
    echo "✓ Data copied<br>";
}

// Step 3: Drop the old Category table
echo "<h3>Step 3: Dropping old Category table</h3>";
$dropTableSQL = "DROP TABLE Category";
$result = sqlsrv_query($conn, $dropTableSQL);
if ($result || (sqlsrv_errors() && strpos(sqlsrv_errors()[0]['message'], 'does not exist') !== false)) {
    echo "✓ Old Category table dropped<br>";
}

// Step 4: Create new foreign key to Categories table
echo "<h3>Step 4: Creating new foreign key</h3>";
$createFKSQL = "ALTER TABLE Inventory 
                ADD CONSTRAINT FK_Inventory_CategoryID 
                FOREIGN KEY (CategoryID) 
                REFERENCES Categories(CategoryID)";
$result = sqlsrv_query($conn, $createFKSQL);
if ($result || (sqlsrv_errors() && strpos(sqlsrv_errors()[0]['message'], 'already exists') !== false)) {
    echo "✓ Foreign key created or already exists<br>";
}

// Step 5: Verify
echo "<h3>Step 5: Verification</h3>";
$query = "SELECT COUNT(*) as cnt FROM Categories";
$result = sqlsrv_query($conn, $query);
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
echo "✓ Total Categories: " . $row['cnt'] . "<br>";

echo "<h3>✓ Migration Complete!</h3>";
echo "<p>Now go to <a href='Inventory.php'>Inventory.php</a> and refresh the page.</p>";

sqlsrv_close($conn);
?>
