<?php
/*
 * Forgot Password Functionality with OTP via Gmail
 *
 * To configure Gmail SMTP:
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Generate an App Password: https://support.google.com/accounts/answer/185833
 * 3. Replace 'your-email@gmail.com' with your Gmail address
 * 4. Replace 'your-app-password' with the generated app password
 *
 * Database: Ensure PasswordResetTokens table is created (see add_reset_table.sql)
 */

session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Include PHPMailer
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists in Management table
        $sql = "{call sp_CheckUserEmail(?, ?)}";
        $exists = false;
        $params = [$email, [&$exists, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT]];
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt && sqlsrv_execute($stmt)) {
            if ($exists) {
                // Generate 6-digit OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

                // Insert into PasswordResetTokens table
                $insertSql = "{call sp_InsertPasswordResetToken(?, ?)}";
                $insertParams = [$email, $otp];
                $insertStmt = sqlsrv_prepare($conn, $insertSql, $insertParams);

                if ($insertStmt && sqlsrv_execute($insertStmt)) {
                    // Send email with OTP
                    $mail = new PHPMailer(true);

                    try {
                        // Server settings - Configure with your Gmail credentials
                        // Note: Enable 2FA on Gmail and generate an App Password
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'cha.caramoan@gmail.com'; // Replace with your Gmail
                        $mail->Password = 'yptzbhzwyvdqijdz'; // Replace with app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('cha.caramoan@gmail.com', 'ScrapTrack Support');
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset OTP - ScrapTrack';
                        $mail->Body = "
                            <h2>Password Reset Request</h2>
                            <p>You requested a password reset for your ScrapTrack account.</p>
                            <p>Your OTP is: <strong>$otp</strong></p>
                            <p>This OTP will expire in 5 minutes.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                        ";
                        $mail->AltBody = "Password Reset OTP: $otp\nThis OTP will expire in 5 minutes.";

                        $mail->send();
                        $_SESSION['reset_email'] = $email;
                        $message = 'OTP has been sent to your email address.';
                    } catch (Exception $e) {
                        $error = 'Failed to send email. Please try again later.';
                        error_log("Mail error: " . $mail->ErrorInfo);
                    }
                } else {
                    $error = 'Failed to generate reset token. Please try again.';
                }
            } else {
                $error = 'No account found with this email address.';
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
    <title>ScrapTrack - Forgot Password</title>
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
                    <h1>Forgot Password</h1>
                    <p class="description-text">Enter the email associated with your account. We will send you a reset token to reset your password.</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="error-message" style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                <div class="success-message" style="color: green; margin-bottom: 10px; text-align: center;">
                    <?php echo htmlspecialchars($message); ?>
                    <br><a href="reset-password.php" style="color: blue; text-decoration: underline;">Click here to reset your password</a>
                </div>
                <?php endif; ?>

                <form action="forgot-password.php" method="POST">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-footer-split">
                        <button type="submit" class="signin-btn">Send Mail</button>
                        <a href="Login.php" class="resend-link">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>