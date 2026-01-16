<?php
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['reset_email'] ?? '';
    $otp = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($email)) {
        $error = 'Session expired. Please request a new password reset.';
    } elseif (empty($otp) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Check if OTP is valid and not expired
        $sql = "{call sp_ValidateOTP(?, ?, ?, ?)}";
        $tokenID = 0;
        $isValid = false;
        $params = [$email, $otp, [&$tokenID, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT], [&$isValid, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT]];
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt && sqlsrv_execute($stmt)) {
            if ($isValid) {
                // OTP is valid, update password
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateSql = "{call sp_UpdateUserPassword(?, ?)}";
                $updateParams = [$email, $passwordHash];
                $updateStmt = sqlsrv_prepare($conn, $updateSql, $updateParams);

                if ($updateStmt && sqlsrv_execute($updateStmt)) {
                    // Mark token as used
                    $markUsedSql = "{call sp_MarkTokenUsed(?)}";
                    $markUsedParams = [$tokenID];
                    $markUsedStmt = sqlsrv_prepare($conn, $markUsedSql, $markUsedParams);
                    sqlsrv_execute($markUsedStmt);

                    unset($_SESSION['reset_email']);
                    $message = 'Password has been reset successfully. You can now login with your new password.';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Invalid or expired OTP.';
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
    <title>ScrapTrack - Reset Password</title>
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
                    <h1>Reset Password</h1>
                    <p class="description-text">Enter the OTP sent to your email and your new password.</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="error-message" style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                <div class="success-message" style="color: green; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($message); ?>
                    <br><a href="Login.php">Go to Login</a>
                </div>
                <?php else: ?>

                <form action="reset-password.php" method="POST">
                    <div class="input-group">
                        <label for="otp">OTP</label>
                        <input type="text" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
                    </div>

                    <div class="input-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>

                    <button type="submit" class="signin-btn">Reset Password</button>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>