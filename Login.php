<?php
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: Dashboard.php");
    exit();
}

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Query the Management table
        $sql = "SELECT ManagementID, FirstName, LastName, Email, PasswordHash FROM Management WHERE Email = ?";
        $params = [$email];
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt && sqlsrv_execute($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row && password_verify($password, $row['PasswordHash'])) {
                // Login successful
                $_SESSION['user_id'] = $row['ManagementID'];
                $_SESSION['username'] = $row['FirstName'] . ' ' . $row['LastName'];
                $_SESSION['email'] = $row['Email'];
                header("Location: Dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScrapTrack - Login</title>
    <link rel="stylesheet" href="Login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="container">
        <div class="left-panel">
            <div class="overlay">
                <img src="Logos-Icons\2.png" alt="ScrapTrack Logo" class="logo">
            </div>
        </div>

        <div class="right-panel">
            <div class="login-wrapper">
                <div class="header-text">
                    <h1>Welcome to <br> ScrapTrack</h1>
                    <p>Track. Manage. Organize.</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="error-message" style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form action="Login.php" method="POST">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="actions">
                        <a href="forgot-password.html" class="forgot-pass">Forgot Password?</a>
                    </div>

                    <button type="submit" class="signin-btn">Sign In</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>