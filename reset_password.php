<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$token_valid = false;

if ($token) {
    // Validate token
    $stmt = $conn->prepare("SELECT user_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valid = true;
    } else {
        $error = "Invalid or expired password reset token.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Get user_id from token
        $stmt = $conn->prepare("SELECT user_id FROM password_reset_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $token_data = $result->fetch_assoc();
        $user_id = $token_data['user_id'];

        // Update password
        $hashed_password = hashPassword($password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            // Delete token after successful reset
            $conn->query("DELETE FROM password_reset_tokens WHERE token = '$token'");
            $success = "Your password has been reset successfully. You can now log in with your new password.";
            $token_valid = false; // Hide the form after successful reset
        } else {
            $error = "An error occurred while resetting your password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reset-password-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        .reset-password-container h1 {
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

        .btn-submit {
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

        .btn-submit:hover {
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

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <h1>Reset Password</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <div class="login-link">
                <p><a href="login.php">Go to Login</a></p>
            </div>
        <?php elseif ($token_valid): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">Reset Password</button>
                <div class="password-requirements">
                    <strong>Password Requirements:</strong><br>
                    • At least 6 characters long<br>
                    • Both passwords must match
                </div>
            </form>
        <?php else: ?>
            <div class="error">
                The password reset link is invalid or has expired. Please request a new one.
            </div>
            <div class="login-link">
                <p><a href="forgot_password.php">Request New Password Reset</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
