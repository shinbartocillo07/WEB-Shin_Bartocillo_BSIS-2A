<?php
require_once 'config.php';

// Create password_reset_tokens table
$sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<div style='padding: 20px; background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; color: #2e7d32; font-weight: bold;'>";
    echo "✓ Password reset tokens table created successfully!";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background-color: #ffebee; border: 1px solid #d32f2f; border-radius: 5px; color: #c62828; font-weight: bold;'>";
    echo "✗ Error creating table: " . $conn->error;
    echo "</div>";
}

$conn->close();
?>
