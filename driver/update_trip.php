<?php
require_once '../config.php';
require_once '../includes/NotificationManager.php';
require_once '../includes/TripNotifications.php';

requireRole('driver');

$user_id = $_SESSION['user_id'];
$trip_id = (int)$_GET['trip_id'];
$action = $_GET['action'];

// Initialize notification system
$notificationManager = new NotificationManager($conn);
$tripNotifications = new TripNotifications($conn, $notificationManager);

// Verify trip belongs to driver
$trip_check = $conn->query("SELECT t.*, s.parent_id, s.id as student_id FROM trips t JOIN students s ON t.student_id = s.id WHERE t.id = $trip_id AND t.driver_id = $user_id");
if ($trip_check->num_rows == 0) {
    die("Invalid trip");
}

$trip = $trip_check->fetch_assoc();
$parent_id = $trip['parent_id'];
$student_id = $trip['student_id'];

$message = '';

if ($action == 'arrived') {
    $conn->query("UPDATE trips SET status = 'picked_up', pickup_time = NOW() WHERE id = $trip_id");
    // Send email notification to parent
    $tripNotifications->notifyPickup($trip_id, $user_id, $student_id);
    $message = 'Pickup marked as arrived';
} elseif ($action == 'start') {
    $conn->query("UPDATE trips SET status = 'in_transit' WHERE id = $trip_id");
    // Send email notification that trip is in transit
    $tripNotifications->notifyInTransit($trip_id, $student_id);
    $message = 'Trip started';
} elseif ($action == 'dropped_off') {
    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = 'trip_' . $trip_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo_path = $file_name;
        }
    }

    $notes = sanitize($_POST['notes'] ?? '');
    $conn->query("UPDATE trips SET status = 'dropped_off', dropoff_time = NOW(), photo_proof = " . ($photo_path ? "'$photo_path'" : "photo_proof") . ", notes = '$notes' WHERE id = $trip_id");

    // Send email notification that student was dropped off
    $tripNotifications->notifyDropoff($trip_id, $student_id);
    $message = 'Drop-off completed' . ($photo_path ? ' with photo proof' : '');
}

redirect('dashboard.php?message=' . urlencode($message));
?>