<?php
require_once '../config.php';
requireRole('parent');

$user_id = $_SESSION['user_id'];

// Get trip history for parent's students
$trip_history = $conn->query("
    SELECT t.*, s.name as student_name, d.full_name as driver_name
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users d ON t.driver_id = d.id
    WHERE s.parent_id = $user_id
    ORDER BY t.trip_date DESC, t.pickup_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip History - Parent</title>
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

        <h1>Trip History</h1>

        <?php if ($trip_history->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Driver</th>
                        <th>Pickup Time</th>
                        <th>Drop-off Time</th>
                        <th>Status</th>
                        <th>Photo Proof</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $trip_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['driver_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><?php echo $trip['dropoff_time'] ?: 'N/A'; ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td>
                                <?php if ($trip['photo_proof']): ?>
                                    <a href="../uploads/<?php echo $trip['photo_proof']; ?>" target="_blank">View Photo</a>
                                <?php else: ?>
                                    No photo
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No trip history available.</p>
        <?php endif; ?>
    </div>
</body>
</html>