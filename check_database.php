<?php
require_once 'config.php';

// Check database and table structure
echo "<h2>Database Information</h2>";
echo "<p>Database: " . DB_NAME . "</p>";

// Check if students table exists
$result = $conn->query("SHOW TABLES LIKE 'students'");
if ($result->num_rows > 0) {
    echo "<p>✓ Students table exists</p>";

    // Show columns in students table
    echo "<h3>Students Table Columns:</h3>";
    $result = $conn->query("DESCRIBE students");
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . " - " . ($row['Default'] ? "Default: " . $row['Default'] : 'No Default') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✗ Students table does not exist</p>";
}

$conn->close();
?>