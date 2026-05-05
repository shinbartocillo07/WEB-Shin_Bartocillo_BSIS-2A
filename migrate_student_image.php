<?php
// Database migration script to add image column to students table
// Run this once to update your database schema

require_once __DIR__ . '/config.php';

echo "Running Student Image Column Migration...\n";
echo "==================================================\n\n";

// Check if image column exists in students table
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'image'");
$column_exists = $result->num_rows > 0;

if (!$column_exists) {
    // Add the image column
    $sql = "ALTER TABLE students ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER schedule";
    if ($conn->query($sql)) {
        echo "✓ Successfully added 'image' column to students table!\n";
    } else {
        echo "✗ Failed to add 'image' column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'image' column already exists in students table\n";
}

echo "\n==================================================\n";
echo "Migration complete!\n";

$conn->close();
?>