<?php
require_once '../config.php';
require_once '../includes/NotificationManager.php';
require_once '../includes/TripNotifications.php';

requireRole('driver');

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];
$message = '';

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

// Verify trip belongs to driver and is in transit
$trip_check = $conn->query("
    SELECT t.*, s.name as student_name, s.parent_id, s.id as student_id
    FROM trips t
    JOIN students s ON t.student_id = s.id
    WHERE t.id = $trip_id AND t.driver_id = $user_id AND t.status = 'in_transit'
");

if ($trip_check->num_rows == 0) {
    die("Invalid trip or trip not in transit");
}

$trip = $trip_check->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = sanitize($_POST['notes']);
    $photo_path = null;
    $dropoff_lat = (float)$_POST['dropoff_lat'];
    $dropoff_lng = (float)$_POST['dropoff_lng'];

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types)) {
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = 'dropoff_' . $trip_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_path = $file_name;
            } else {
                $message = 'Error uploading photo.';
            }
        } else {
            $message = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }
    }

    if (!$message) {
        // Update trip status
        $conn->query("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), dropoff_lat = $dropoff_lat, dropoff_lng = $dropoff_lng, photo_proof = " . ($photo_path ? "'$photo_path'" : "photo_proof") . ", notes = '$notes' WHERE id = $trip_id");

        // Send email notification to parent
        $tripNotifications->notifyDropoff($trip_id, $trip['student_id']);

        $message = 'Drop-off completed successfully!' . ($photo_path ? ' Photo uploaded.' : '');
        redirect('dashboard.php?message=' . urlencode($message));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop Off Student - Driver</title>
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

        <h1>Drop Off Student</h1>

        <div class="trip-info">
            <h2>Trip Details</h2>
            <p><strong>Student:</strong> <?php echo $trip['student_name']; ?></p>
            <p><strong>Trip Date:</strong> <?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></p>
            <p><strong>Pickup Time:</strong> <?php echo $trip['pickup_time']; ?></p>
        </div>

        <?php if ($message): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="dropoffForm">
            <input type="hidden" id="dropoff_lat" name="dropoff_lat">
            <input type="hidden" id="dropoff_lng" name="dropoff_lng">
            <div class="form-group">
                <label for="photo">Drop-off Photo Proof (Optional):</label>
                <input type="file" id="photo" name="photo" accept="image/*">
                <small>Upload a photo showing the student safely dropped off at their destination.</small>
            </div>
            <div class="form-group">
                <label for="notes">Notes (Optional):</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any additional notes about the drop-off..."></textarea>
            </div>
            <button type="submit">Complete Drop-off</button>
            <a href="dashboard.php" style="background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-left: 10px;">Cancel</a>
        </form>
    </div>

    <style>
        .trip-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .trip-info h2 {
            margin-top: 0;
            color: #333;
        }
        .trip-info p {
            margin: 5px 0;
        }
        small {
            color: #666;
            font-size: 12px;
        }
    </style>

    <script>
        document.getElementById('dropoffForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent immediate form submission
            
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser.');
                return;
            }
            
            // Get current location
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Set the coordinates in hidden fields
                    document.getElementById('dropoff_lat').value = position.coords.latitude;
                    document.getElementById('dropoff_lng').value = position.coords.longitude;
                    
                    // Now submit the form
                    document.getElementById('dropoffForm').submit();
                },
                function(error) {
                    alert('Could not get your location: ' + error.message + '. Please try again.');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
    </script>
</body>
</html>