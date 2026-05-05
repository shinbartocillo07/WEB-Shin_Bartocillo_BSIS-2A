<?php
require_once 'config.php';

// Add image column to students table if it doesn't exist
$sql = "ALTER TABLE students ADD COLUMN image VARCHAR(255) AFTER schedule";

if ($conn->query($sql) === TRUE) {
    echo "<div style='padding: 20px; background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; color: #2e7d32; font-weight: bold;'>";
    echo "✓ Image column added to students table successfully!";
    echo "</div>";
} else {
    if (strpos($conn->error, "Duplicate column") !== false) {
        echo "<div style='padding: 20px; background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; color: #2e7d32; font-weight: bold;'>";
        echo "✓ Image column already exists in students table!";
        echo "</div>";
    } else {
        echo "<div style='padding: 20px; background-color: #ffebee; border: 1px solid #d32f2f; border-radius: 5px; color: #c62828; font-weight: bold;'>";
        echo "✗ Error: " . $conn->error;
        echo "</div>";
    }
}

$conn->close();
?>
