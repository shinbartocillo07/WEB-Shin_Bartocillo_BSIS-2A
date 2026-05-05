<?php
require_once 'config.php';

// Check if image column exists
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'image'");
if ($result->num_rows > 0) {
    echo "<div style='padding: 20px; background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; color: #2e7d32; font-weight: bold;'>";
    echo "✓ Image column exists in students table!";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background-color: #ffebee; border: 1px solid #d32f2f; border-radius: 5px; color: #c62828; font-weight: bold;'>";
    echo "✗ Image column does not exist in students table!";
    echo "</div>";
}

$conn->close();
?>