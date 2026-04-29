<?php
require_once '../config.php';
require_once '../includes/NotificationManager.php';
require_once '../includes/TripNotifications.php';

requireRole('driver');

$user_id = $_SESSION['user_id'];
$message = '';

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

// Handle trip creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_trip'])) {
    $student_id = (int)$_POST['student_id'];
    $trip_date = sanitize($_POST['trip_date']);
    $pickup_time = sanitize($_POST['pickup_time']);
    $pickup_lat = (float)$_POST['pickup_lat'];
    $pickup_lng = (float)$_POST['pickup_lng'];
    $dropoff_lat = (float)$_POST['dropoff_lat'];
    $dropoff_lng = (float)$_POST['dropoff_lng'];

    // Verify student belongs to this driver
    $student_check = $conn->query("SELECT id FROM students WHERE id = $student_id AND driver_id = $user_id");
    if ($student_check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO trips (driver_id, student_id, trip_date, pickup_time, pickup_lat, pickup_lng, dropoff_lat, dropoff_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdddd", $user_id, $student_id, $trip_date, $pickup_time, $pickup_lat, $pickup_lng, $dropoff_lat, $dropoff_lng);
        if ($stmt->execute()) {
            $trip_id = $stmt->insert_id;
            // Send notification to driver about new trip
            $tripNotifications->notifyDriverNewTrip($trip_id, $user_id);
            $message = 'Trip created successfully!';
        }
    } else {
        $message = 'Invalid student selection.';
    }
}

// Get assigned students
$students = $conn->query("SELECT * FROM students WHERE driver_id = $user_id");

// Get upcoming trips
$upcoming_trips = $conn->query("
    SELECT t.*, s.name as student_name, s.address
    FROM trips t
    JOIN students s ON t.student_id = s.id
    WHERE t.driver_id = $user_id AND t.trip_date >= CURDATE() AND t.status NOT IN ('dropped_off', 'completed')
    ORDER BY t.trip_date, t.pickup_time
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips - Driver</title>
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

        <h1>Manage Trips</h1>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Create New Trip</h2>
        <form method="POST" class="trip-form">
            <div class="form-group">
                <label for="student_id">Select Student:</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Choose a student</option>
                    <?php
                    $students->data_seek(0);
                    while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['address']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="trip_date">Trip Date:</label>
                <input type="date" id="trip_date" name="trip_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="pickup_time">Pickup Time:</label>
                <input type="time" id="pickup_time" name="pickup_time" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_lat">Pickup Latitude:</label>
                    <input type="number" step="0.000001" id="pickup_lat" name="pickup_lat" placeholder="15.075" required>
                </div>
                <div class="form-group">
                    <label for="pickup_lng">Pickup Longitude:</label>
                    <input type="number" step="0.000001" id="pickup_lng" name="pickup_lng" placeholder="120.65" required>
                </div>
            </div>
            <div class="form-group geo-actions">
                <button type="button" id="pickup_geolocate">Use current pickup location</button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="dropoff_lat">Drop-off Latitude:</label>
                    <input type="number" step="0.000001" id="dropoff_lat" name="dropoff_lat" placeholder="15.08" required>
                </div>
                <div class="form-group">
                    <label for="dropoff_lng">Drop-off Longitude:</label>
                    <input type="number" step="0.000001" id="dropoff_lng" name="dropoff_lng" placeholder="120.66" required>
                </div>
            </div>
            <div class="form-group geo-actions">
                <button type="button" id="dropoff_geolocate">Use current drop-off location</button>
            </div>
            <button type="submit" name="create_trip">Create Trip</button>
        </form>

        <h2>Upcoming Trips</h2>
        <?php if ($upcoming_trips->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Pickup Time</th>
                        <th>Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $upcoming_trips->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                            <td><?php echo $trip['student_name']; ?></td>
                            <td><?php echo $trip['pickup_time']; ?></td>
                            <td><?php echo substr($trip['address'], 0, 50) . (strlen($trip['address']) > 50 ? '...' : ''); ?></td>
                            <td><span class="status-<?php echo $trip['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trip['status'])); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No upcoming trips scheduled.</p>
        <?php endif; ?>
    </div>

    <style>
        .trip-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .geo-actions {
            margin: 10px 0 20px;
        }
        .geo-actions button {
            padding: 10px 15px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .geo-actions button:hover {
            background: #0056b3;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
        }
    </style>
    <script>
let activeTripId = null;

<?php
$active = $conn->query("
    SELECT id FROM trips 
    WHERE driver_id = $user_id 
    AND status IN ('picked_up','in_transit')
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();
?>

activeTripId = <?php echo $active['id'] ?? 'null'; ?>;

function sendLocation() {
    if (!activeTripId) return;

    navigator.geolocation.getCurrentPosition(function(position) {
        fetch("update_location.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body:
                "trip_id=" + activeTripId +
                "&lat=" + position.coords.latitude +
                "&lng=" + position.coords.longitude
        });
    });
}

function setLocationFields(prefix, coords) {
    document.getElementById(prefix + '_lat').value = coords.latitude.toFixed(6);
    document.getElementById(prefix + '_lng').value = coords.longitude.toFixed(6);
}

function locate(prefix) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            setLocationFields(prefix, position.coords);
        },
        function(error) {
            alert('Could not get location: ' + error.message);
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
}

window.addEventListener('DOMContentLoaded', function() {
    const pickupButton = document.getElementById('pickup_geolocate');
    const dropoffButton = document.getElementById('dropoff_geolocate');

    if (pickupButton) {
        pickupButton.addEventListener('click', function() {
            locate('pickup');
        });
    }
    if (dropoffButton) {
        dropoffButton.addEventListener('click', function() {
            locate('dropoff');
        });
    }

    if (navigator.geolocation) {
        const pickupLat = document.getElementById('pickup_lat').value;
        const pickupLng = document.getElementById('pickup_lng').value;
        if (!pickupLat || !pickupLng) {
            locate('pickup');
        }
    }
});

setInterval(sendLocation, 5000);
</script>
</body>
</html>