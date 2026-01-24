<?php
// ScrapTrack Employee Management - PHP Backend
// This file handles CRUD operations for employee management

// Start session for user management
session_start();

// Check if user is logged in (uncomment for production)
// if (!isset($_SESSION['user_id'])) {
//     header("Location: Login.php");
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
            echo json_encode(addEmployee($_POST));
            break;
        case 'update':
            echo json_encode(updateEmployee($_POST));
            break;
        case 'delete':
            echo json_encode(deleteEmployee($_POST['id']));
            break;
        case 'get_all':
            echo json_encode(getAllEmployees());
            break;
        case 'get_by_id':
            echo json_encode(getEmployeeById($_POST['id']));
            break;
        case 'search':
            echo json_encode(searchEmployees($_POST['search']));
            break;
    }
    exit();
}

// Get All Employees
function getAllEmployees() {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $sql = "SELECT ManagementID, FirstName, MiddleName, LastName, Position, 
            Contact_Number, Email, GETDATE() as DateStarted 
            FROM Management 
            ORDER BY FirstName ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Query failed: ' . print_r(sqlsrv_errors(), true)];
    }
    
    $employees = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Format date if it exists
        if (isset($row['DateStarted']) && $row['DateStarted'] instanceof DateTime) {
            $row['DateStarted'] = $row['DateStarted']->format('Y-m-d');
        }
        $employees[] = $row;
    }
    
    return ['success' => true, 'employees' => $employees];
}

// Get Employee by ID
function getEmployeeById($id) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $sql = "SELECT ManagementID, FirstName, MiddleName, LastName, Position, 
            Contact_Number, Email 
            FROM Management 
            WHERE ManagementID = ?";
    
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Query failed'];
    }
    
    $employee = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if ($employee) {
        return ['success' => true, 'employee' => $employee];
    }
    
    return ['success' => false, 'message' => 'Employee not found'];
}

// Add Employee
function addEmployee($data) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($data['firstName']) || empty($data['lastName']) || 
        empty($data['email']) || empty($data['password'])) {
        return ['success' => false, 'message' => 'Please fill in all required fields'];
    }
    
    // Check if email already exists
    $checkSql = "SELECT COUNT(*) as count FROM Management WHERE Email = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, array($data['email']));
    $checkResult = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
    
    if ($checkResult['count'] > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO Management (FirstName, MiddleName, LastName, Position, 
            Contact_Number, Email, PasswordHash) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = array(
        $data['firstName'],
        $data['middleName'] ?: null,
        $data['lastName'],
        $data['position'] ?? null,
        $data['contactNumber'] ?? null,
        $data['email'],
        $hashedPassword
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Failed to add employee: ' . print_r(sqlsrv_errors(), true)];
    }
    
    return ['success' => true, 'message' => 'Employee registered successfully'];
}

// Update Employee
function updateEmployee($data) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($data['id']) || empty($data['firstName']) || 
        empty($data['lastName']) || empty($data['email'])) {
        return ['success' => false, 'message' => 'Please fill in all required fields'];
    }
    
    // Check if email already exists for another user
    $checkSql = "SELECT COUNT(*) as count FROM Management WHERE Email = ? AND ManagementID != ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, array($data['email'], $data['id']));
    $checkResult = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
    
    if ($checkResult['count'] > 0) {
        return ['success' => false, 'message' => 'Email already exists for another employee'];
    }
    
    // Build update query
    if (!empty($data['password'])) {
        // Update with new password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE Management SET 
                FirstName = ?, MiddleName = ?, LastName = ?, 
                Position = ?, Contact_Number = ?, Email = ?, PasswordHash = ?
                WHERE ManagementID = ?";
        
        $params = array(
            $data['firstName'],
            $data['middleName'] ?: null,
            $data['lastName'],
            $data['position'] ?? null,
            $data['contactNumber'] ?? null,
            $data['email'],
            $hashedPassword,
            $data['id']
        );
    } else {
        // Update without changing password
        $sql = "UPDATE Management SET 
                FirstName = ?, MiddleName = ?, LastName = ?, 
                Position = ?, Contact_Number = ?, Email = ?
                WHERE ManagementID = ?";
        
        $params = array(
            $data['firstName'],
            $data['middleName'] ?: null,
            $data['lastName'],
            $data['position'] ?? null,
            $data['contactNumber'] ?? null,
            $data['email'],
            $data['id']
        );
    }
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Failed to update employee: ' . print_r(sqlsrv_errors(), true)];
    }
    
    return ['success' => true, 'message' => 'Employee updated successfully'];
}

// Delete Employee
function deleteEmployee($id) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    if (empty($id)) {
        return ['success' => false, 'message' => 'Invalid employee ID'];
    }
    
    // Check if employee exists
    $checkSql = "SELECT COUNT(*) as count FROM Management WHERE ManagementID = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, array($id));
    $checkResult = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
    
    if ($checkResult['count'] == 0) {
        return ['success' => false, 'message' => 'Employee not found'];
    }
    
    // Check if employee has related records (optional - prevent deletion if needed)
    // You may want to check Exchange, Inventory_Audit_Log tables for related records
    
    $sql = "DELETE FROM Management WHERE ManagementID = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id));
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Failed to delete employee: ' . print_r(sqlsrv_errors(), true)];
    }
    
    return ['success' => true, 'message' => 'Employee deleted successfully'];
}

// Search Employees
function searchEmployees($searchTerm) {
    global $conn;
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $searchTerm = '%' . $searchTerm . '%';
    
    $sql = "SELECT ManagementID, FirstName, MiddleName, LastName, Position, 
            Contact_Number, Email, GETDATE() as DateStarted 
            FROM Management 
            WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR Position LIKE ?
            ORDER BY FirstName ASC";
    
    $params = array($searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Query failed'];
    }
    
    $employees = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (isset($row['DateStarted']) && $row['DateStarted'] instanceof DateTime) {
            $row['DateStarted'] = $row['DateStarted']->format('Y-m-d');
        }
        $employees[] = $row;
    }
    
    return ['success' => true, 'employees' => $employees];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - ScrapTrack</title>
    <link rel="stylesheet" href="EmployeeManagement.css">
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
                <a href="TransactionRecords.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Transaction Records</span>
                </a>
                <a href="EmployeeManagement.php" class="nav-item active">
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
                <h1>Employee Management</h1>
                <p class="subtitle">Manage your employees'</p>
            </div>
        </header>

        <!-- Search Section -->
        <section class="search-section">
            <div class="search-header">
                <div class="search-header-text">
                    <h3>Search Employee</h3>
                    <p class="search-subtitle">Find employees by name, category or description</p>
                </div>
                <button class="add-employee-btn" id="addEmployeeBtn">
                    Add
                </button>
            </div>
            
            <div class="search-bar-container">
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search Employee..." class="search-input">
                </div>
                <button class="filter-btn" id="filterBtn">
                    <span class="filter-icon">🔽</span>
                </button>
            </div>
        </section>

        <!-- Employee Table Section -->
        <section class="employee-section">
            <div class="table-container">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>EMPLOYEE</th>
                            <th>DATE STARTED</th>
                            <th>EMAIL</th>
                            <th>ROLE</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Add/Edit Employee Modal -->
    <div class="modal" id="employeeModal">
        <div class="modal-content register-modal">
            <div class="modal-header">
                <h2 id="modalTitle">Register Employee</h2>
            </div>
            <form id="employeeForm">
                <div class="modal-body">
                    <input type="hidden" id="employeeId" name="employeeId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required placeholder="First Name...">
                        </div>
                        <div class="form-group">
                            <label for="middleName">Middle Name</label>
                            <input type="text" id="middleName" name="middleName" placeholder="Middle Name...">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required placeholder="Last Name...">
                        </div>
                        <div class="form-group">
                            <label for="position">Position</label>
                            <select id="position" name="position" required>
                                <option value="">Select Position...</option>
                                <option value="Admin">Admin</option>
                                <option value="Finance Auditor">Finance Auditor</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactNumber">Contact Number</label>
                            <input type="text" id="contactNumber" name="contactNumber" placeholder="Contact Number...">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required placeholder="Email Address...">
                        </div>
                    </div>
                    
                    <div class="form-row" id="passwordGroup">
                        <div class="form-group">
                            <label for="password">Initial Password</label>
                            <input type="password" id="password" name="password" placeholder="Initial Password...">
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Initial Password</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-clear" onclick="clearForm()">Clear</button>
                    <button type="submit" class="btn-register">Register</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal delete-modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-body">
                <div class="delete-icon">⚠️</div>
                <h3>Delete Employee?</h3>
                <p>Are you sure you want to delete this employee? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script src="EmployeeManagement.js"></script>
    <script src="user-session.js"></script>
    <script src="logout-dialog.js"></script>
</body>
</html>
