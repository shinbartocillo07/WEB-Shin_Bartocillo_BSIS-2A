<?php
require_once '../config.php';
requireRole('driver');

$user_id = $_SESSION['user_id'];

// Check if driver is approved
$driver_check = $conn->query("SELECT status FROM drivers WHERE user_id = $user_id");
$driver_status = $driver_check->fetch_assoc()['status'];

if ($driver_status !== 'approved') {
    echo "<div class='container'><h1>Account Pending Approval</h1><p>Your driver account is still pending admin approval.</p><a href='../logout.php'>Logout</a></div>";
    exit();
}

// Get assigned students
$assigned_students = $conn->query("
    SELECT s.*, u.full_name as parent_name, u.phone as parent_phone
    FROM students s
    JOIN users u ON s.parent_id = u.id
    WHERE s.driver_id = $user_id
");

// Get today's trips
$today_trips = $conn->query("
    SELECT t.*, s.name as student_name, s.address, u.full_name as parent_name, u.phone as parent_phone
    FROM trips t
    JOIN students s ON t.student_id = s.id
    JOIN users u ON s.parent_id = u.id
    WHERE t.driver_id = $user_id AND t.trip_date = CURDATE()
    ORDER BY t.pickup_time
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - School Transport System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_trips.php">Manage Trips</a></li>
                <li><a href="trip_history.php">Trip History</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Driver Dashboard</h1>

        <h2>Assigned Students</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Address</th>
                    <th>Parent</th>
                    <th>Parent Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $assigned_students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['grade']; ?></td>
                        <td><?php echo $student['address']; ?></td>
                        <td><?php echo $student['parent_name']; ?></td>
                        <td><?php echo $student['parent_phone']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Today's Trips</h2>
        <?php if ($today_trips->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Pickup Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $today_trips->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                            <td>
                                <?php if ($trip['status'] == 'scheduled'): ?>
                                    <a href="update_trip.php?trip_id=<?php echo $trip['id']; ?>&action=arrived">Arrived</a> |
                                    <a href="update_trip.php?trip_id=<?php echo $trip['id']; ?>&action=start">Start Trip</a>
                                <?php elseif ($trip['status'] == 'picked_up'): ?>
                                    <a href="update_trip.php?trip_id=<?php echo $trip['id']; ?>&action=start">Start Trip</a>
                                <?php elseif ($trip['status'] == 'in_transit'): ?>
                                    <a href="dropoff_trip.php?trip_id=<?php echo $trip['id']; ?>">Drop Off</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No trips scheduled for today.</p>
        <?php endif; ?>
    </div>
</body>
</html>