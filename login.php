<?php
// This is the login page - it checks username and password

require_once 'config.php'; // Get the database connection and helper functions

$error = ''; // This will hold any error messages

// Check if the user submitted the login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the username and password from the form
    $username = sanitize($_POST['username']); // Clean the username
    $password = $_POST['password']; // Get the password (don't clean it yet)

    // Look for this username in the users table
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // Put the username in the query safely
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if we found exactly one user
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc(); // Get the user data

        // Check if the password is correct
        if (verifyPassword($password, $user['password'])) {
            // Password is correct! Save user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Track this login (optional - for security)
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $conn->query("INSERT INTO user_logins (user_id, ip_address, user_agent) VALUES ({$user['id']}, '$ip', '" . $conn->real_escape_string($user_agent) . "')");

            // Send the user to the right dashboard based on their role
            switch ($user['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'parent':
                    redirect('parent/dashboard.php');
                    break;
                case 'driver':
                    redirect('driver/dashboard.php');
                    break;
            }
        } else {
            $error = 'Invalid password'; // Password was wrong
        }
    } else {
        $error = 'User not found'; // Username doesn't exist
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Transport System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>School Service Transportation System</h1>
        <div class="login-form">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register</a></p>
            <p>Forgot your password? <a href="forgot_password.php">Reset it here</a></p>
        </div>
    </div>
</body>
</html>