<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_POST['select_driver'])) {
    $student_id = (int)$_POST['student_id'];
    $driver_id = (int)$_POST['driver_id'];

    // Check if student belongs to parent
    $student_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND parent_id = $user_id");
    if ($student_check->num_rows > 0) {
        $conn->query("UPDATE students SET driver_id = $driver_id WHERE id = $student_id");
        $message = 'Driver selected successfully!';
    } else {
        $message = 'Invalid student.';
    }
}

// Get parent's students
$students = $conn->query("SELECT * FROM students WHERE parent_id = $user_id");

// Get approved drivers
$drivers = $conn->query("
    SELECT d.*, u.full_name, u.phone
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.status = 'approved'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Driver - Parent</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_student.php">Add Student</a></li>
                <li><a href="select_driver.php">Select Driver</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Select Driver for Students</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Available Drivers</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Vehicle</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($driver = $drivers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $driver['full_name']; ?></td>
                        <td><?php echo $driver['phone']; ?></td>
                        <td><?php echo $driver['vehicle_type'] . ' (' . $driver['vehicle_plate'] . ')'; ?></td>
                        <td><?php echo $driver['rating']; ?>/5</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Assign Driver to Student</h2>
        <form method="POST">
            <div class="form-group">
                <label for="student_id">Select Student:</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Choose a student</option>
                    <?php
                    $students->data_seek(0); // Reset pointer
                    while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="driver_id">Select Driver:</label>
                <select id="driver_id" name="driver_id" required>
                    <option value="">Choose a driver</option>
                    <?php
                    $drivers->data_seek(0); // Reset pointer
                    while ($driver = $drivers->fetch_assoc()): ?>
                        <option value="<?php echo $driver['user_id']; ?>"><?php echo $driver['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="select_driver">Assign Driver</button>
        </form>
    </div>
</body>
</html>