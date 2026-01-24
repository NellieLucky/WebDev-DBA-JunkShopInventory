<?php
// User API - Fetch current user data from database
session_start();

require_once __DIR__ . '/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated', 'has_session' => false]);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$user_id = $_SESSION['user_id'];

if ($action === 'get_current_user') {
    // Fetch user data from Management table
    $query = "SELECT ManagementID, FirstName, MiddleName, LastName, Email, Position FROM Management WHERE ManagementID = ?";
    $params = [$user_id];
    $stmt = sqlsrv_prepare($conn, $query, $params);

    if ($stmt === false || !sqlsrv_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error', 'user_id' => $user_id]);
        exit();
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($row) {
        // Format the name
        $firstName = $row['FirstName'] ?? '';
        $lastName = $row['LastName'] ?? '';
        $fullName = trim("$firstName $lastName");

        // Update session with latest data
        $_SESSION['username'] = $fullName;
        $_SESSION['email'] = $row['Email'] ?? '';
        $_SESSION['position'] = $row['Position'] ?? '';

        echo json_encode([
            'success' => true,
            'username' => $fullName,
            'email' => $row['Email'],
            'position' => $row['Position'],
            'user_id' => $user_id
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found', 'user_id' => $user_id]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

// Don't close connection - let db_connect.php manage it
?>
