<?php
// ScrapTrack Account Settings Page + AJAX Backend
session_start();
require_once __DIR__ . '/db_connect.php';

// Demo fallback user if session not set
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Arnel Gante';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

function getAccount($userId) {
    global $conn;
    $sql = "SELECT ManagementID, FirstName, MiddleName, LastName, Position, Email FROM Management WHERE ManagementID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$userId]);
    if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        return ['success' => false, 'error' => 'Account not found'];
    }
    $nameParts = array_filter([$row['FirstName'], $row['LastName']]);
    $fullName = implode(' ', $nameParts);
    return [
        'success' => true,
        'data' => [
            'id' => $row['ManagementID'],
            'name' => $fullName,
            'email' => $row['Email'],
            'type' => $row['Position'] ?: 'Administrator',
        ]
    ];
}

function updateAccount($userId, $name, $email, $password) {
    global $conn;

    // Parse name into first/last (simple split)
    $first = $name;
    $last = '';
    if (strpos($name, ' ') !== false) {
        $parts = preg_split('/\s+/', trim($name));
        $first = array_shift($parts);
        $last = implode(' ', $parts);
    }

    // Update name + email
    $updateSql = "UPDATE Management SET FirstName = ?, LastName = ?, Email = ? WHERE ManagementID = ?";
    $updateStmt = sqlsrv_query($conn, $updateSql, [$first, $last, $email, $userId]);
    if (!$updateStmt) {
        return ['success' => false, 'error' => 'Failed to update name/email'];
    }

    // Optionally update password
    if ($password !== null && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pwdSql = "UPDATE Management SET PasswordHash = ? WHERE ManagementID = ?";
        $pwdStmt = sqlsrv_query($conn, $pwdSql, [$hash, $userId]);
        if (!$pwdStmt) {
            return ['success' => false, 'error' => 'Failed to update password'];
        }
    }

    return ['success' => true];
}

// AJAX handlers
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        case 'get_account':
            echo json_encode(getAccount($user_id));
            break;
        case 'update_account':
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            echo json_encode(updateAccount($user_id, $name, $email, $password));
            break;
    }
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - ScrapTrack</title>
    <link rel="stylesheet" href="AccountSettings.css">
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
                <a href="EmployeeManagement.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Employee Management</span>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-label">Settings</p>
                <a href="AccountSettings.php" class="nav-item active">
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
        <header class="header settings-header">
            <div>
                <h1>Account Settings</h1>
                <p class="subtitle">Manage your account</p>
            </div>
            <button class="edit-btn" id="editBtn">Edit</button>
        </header>

        <!-- Profile Card -->
        <section class="profile-section">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="Logos-Icons/3.png" alt="Avatar">
                </div>
                <div class="profile-info">
                    <div class="info-row"><span class="label">Name:</span> <span class="value" id="infoName">-</span></div>
                    <div class="info-row"><span class="label">Email:</span> <span class="value" id="infoEmail">-</span></div>
                    <div class="info-row"><span class="label">Type:</span> <span class="value" id="infoType">-</span></div>
                </div>
            </div>
        </section>

        <!-- Edit Form -->
        <section class="form-section">
            <div class="form-row">
                <label class="form-label">Name:</label>
                <input class="form-input" id="nameInput" type="text" placeholder="Your Name" disabled>
            </div>
            <div class="form-row">
                <label class="form-label">Email:</label>
                <input class="form-input" id="emailInput" type="email" placeholder="you@example.com" disabled>
            </div>
            <div class="form-row">
                <label class="form-label">Password:</label>
                <input class="form-input" id="passwordInput" type="password" placeholder="naka encrypt na ********" disabled>
            </div>
            <div class="form-actions">
                <button class="save-btn" id="saveBtn" disabled>Save</button>
            </div>
        </section>
    </main>

    <script src="AccountSettings.js"></script>
    <script src="user-session.js"></script>
    <script src="logout-dialog.js"></script>
</body>
</html>