<?php
require_once 'config.php';
require_once 'send_email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Delete any existing tokens for this user
        $conn->query("DELETE FROM password_reset_tokens WHERE user_id = $user_id");

        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires_at);

        if ($stmt->execute()) {
            // Send reset email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Traysikel/reset_password.php?token=" . $token;
            $subject = "Password Reset Request - School Transport System";
            $message = "
            <h2>Password Reset Request</h2>
            <p>You requested to reset your password. Click the link below to proceed:</p>
            <p><a href='$reset_link'>Reset Your Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            ";

            if (sendEmail($email, $subject, $message)) {
                $success = "A password reset link has been sent to your email address.";
            } else {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            $error = "An error occurred. Please try again.";
        }
    } else {
        // Don't reveal if email exists (security best practice)
        $success = "If an account with that email exists, a password reset link has been sent.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .forgot-password-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .forgot-password-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .btn-reset {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-reset:hover {
            background-color: #45a049;
        }

        .error {
            color: #d32f2f;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }

        .success {
            color: #388e3c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
            border-left: 4px solid #388e3c;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .info-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h1>Forgot Password?</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <p class="info-text">Enter your email address and we'll send you a link to reset your password.</p>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn-reset">Send Reset Link</button>
            </form>
        <?php endif; ?>

        <div class="login-link">
            <p>Remember your password? <a href="login.php">Log in here</a></p>
        </div>
    </div>
</body>
</html>
