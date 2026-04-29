<?php
require_once '../config.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];

// Get stats
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
$stats['total_users'] = $result->fetch_assoc()['total_users'];

$result = $conn->query("SELECT COUNT(*) as total_students FROM students");
$stats['total_students'] = $result->fetch_assoc()['total_students'];

$result = $conn->query("SELECT COUNT(*) as active_trips FROM trips WHERE status IN ('picked_up', 'in_transit')");
$stats['active_trips'] = $result->fetch_assoc()['active_trips'];

$result = $conn->query("SELECT COUNT(*) as pending_drivers FROM drivers WHERE status = 'pending'");
$stats['pending_drivers'] = $result->fetch_assoc()['pending_drivers'];

$result = $conn->query("SELECT COUNT(*) as today_logins FROM user_logins WHERE DATE(login_time) = CURDATE()");
$stats['today_logins'] = $result->fetch_assoc()['today_logins'];

$result = $conn->query("SELECT COUNT(*) as pending_payments FROM payments WHERE status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['pending_payments'];

$result = $conn->query("SELECT SUM(amount) as total_collected FROM payments WHERE status = 'paid'");
$stats['total_collected'] = $result->fetch_assoc()['total_collected'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60"> <!-- Auto-refresh every 60 seconds -->
    <title>Admin Dashboard - School Transport System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_drivers.php">Manage Drivers</a></li>
                <li><a href="manage_students.php">Manage Students</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../generate_payments.php">Generate Payments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>

        <h1>Admin Dashboard</h1>
        <div class="last-updated">
            <small>Last updated: <?php echo date('M d, Y H:i:s'); ?> (Auto-refreshes every minute)</small>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Students</h3>
                <p><?php echo $stats['total_students']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Trips</h3>
                <p><?php echo $stats['active_trips']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Today's Logins</h3>
                <p><?php echo $stats['today_logins']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Payments</h3>
                <p><?php echo $stats['pending_payments']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Collected</h3>
                <p>$<?php echo number_format($stats['total_collected'], 0); ?></p>
            </div>
        </div>

        <h2>System Activities</h2>
        
        <!-- Recent Account Registrations -->
        <div class="activity-section">
            <h3>👤 Recent Account Registrations</h3>
            <?php
            $recent_registrations = $conn->query("
                SELECT id, username, full_name, role, email, created_at
                FROM users 
                WHERE role != 'admin'
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            ?>
            
            <?php if ($recent_registrations->num_rows > 0): ?>
                <div class="activity-list">
                    <?php while ($user = $recent_registrations->fetch_assoc()): ?>
                        <div class="activity-card">
                            <div class="activity-icon">
                                <?php 
                                switch($user['role']) {
                                    case 'parent': echo '👨‍👩‍👧‍👦'; break;
                                    case 'driver': echo '🚗'; break;
                                    default: echo '👤';
                                }
                                ?>
                            </div>
                            <div class="activity-details">
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> 
                                <span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span><br>
                                <small><?php echo htmlspecialchars($user['username']); ?> • <?php echo htmlspecialchars($user['email']); ?></small>
                            </div>
                            <div class="activity-time">
                                <small><?php echo date('M d, H:i', strtotime($user['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-activity">No recent registrations</p>
            <?php endif; ?>
        </div>

        <!-- Recent Logins -->
        <div class="activity-section">
            <h3>🔐 Recent Logins</h3>
            <?php
            $recent_logins = $conn->query("
                SELECT ul.login_time, u.username, u.full_name, u.role, ul.ip_address
                FROM user_logins ul
                JOIN users u ON ul.user_id = u.id
                ORDER BY ul.login_time DESC 
                LIMIT 8
            ");
            ?>
            
            <?php if ($recent_logins->num_rows > 0): ?>
                <div class="activity-list">
                    <?php while ($login = $recent_logins->fetch_assoc()): ?>
                        <div class="activity-card">
                            <div class="activity-icon">🔑</div>
                            <div class="activity-details">
                                <strong><?php echo htmlspecialchars($login['full_name']); ?></strong> 
                                <span class="role-badge role-<?php echo $login['role']; ?>"><?php echo ucfirst($login['role']); ?></span><br>
                                <small><?php echo htmlspecialchars($login['username']); ?> • <?php echo $login['ip_address']; ?></small>
                            </div>
                            <div class="activity-time">
                                <small><?php echo date('M d, H:i', strtotime($login['login_time'])); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-activity">No recent logins</p>
            <?php endif; ?>
        </div>

        <!-- Recent Payments -->
        <div class="activity-section">
            <h3>💳 Recent Payments</h3>
            <?php
            $recent_payments = $conn->query("
                SELECT p.*, u.full_name as parent_name, s.name as student_name
                FROM payments p
                JOIN users u ON p.parent_id = u.id
                JOIN students s ON p.student_id = s.id
                ORDER BY 
                    CASE 
                        WHEN p.status = 'paid' THEN 1
                        WHEN p.status = 'pending' THEN 2
                        WHEN p.status = 'overdue' THEN 3
                    END, p.created_at DESC
                LIMIT 5
            ");
            ?>
            
            <?php if ($recent_payments->num_rows > 0): ?>
                <div class="activity-list">
                    <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                        <div class="activity-card">
                            <div class="activity-icon">
                                <?php 
                                switch($payment['status']) {
                                    case 'paid': echo '✅'; break;
                                    case 'pending': echo '⏳'; break;
                                    case 'overdue': echo '⚠️'; break;
                                }
                                ?>
                            </div>
                            <div class="activity-details">
                                <strong>$<?php echo number_format($payment['amount'], 2); ?></strong> 
                                <span class="status-badge status-<?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span><br>
                                <small><?php echo htmlspecialchars($payment['parent_name']); ?> • <?php echo htmlspecialchars($payment['student_name']); ?></small>
                            </div>
                            <div class="activity-time">
                                <small><?php echo $payment['paid_date'] ? date('M d', strtotime($payment['paid_date'])) : date('M d', strtotime($payment['due_date'])); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-activity">No recent payments</p>
            <?php endif; ?>
        </div>
        
        <!-- Active Trips Section -->
        <div class="active-trips-section">
            <h3>🚗 Active Trips</h3>
            <?php
            $active_trips = $conn->query("
                SELECT t.*, s.name as student_name, d.full_name as driver_name, 
                       TIMESTAMPDIFF(MINUTE, t.pickup_time, NOW()) as minutes_elapsed
                FROM trips t
                JOIN students s ON t.student_id = s.id
                JOIN users d ON t.driver_id = d.id
                WHERE t.status IN ('picked_up', 'in_transit', 'dropped_off')
                AND t.trip_date = CURDATE()
                ORDER BY 
                    CASE 
                        WHEN t.status = 'picked_up' THEN 1
                        WHEN t.status = 'in_transit' THEN 2
                        WHEN t.status = 'dropped_off' THEN 3
                    END, t.pickup_time DESC
                LIMIT 5
            ");
            ?>
            
            <?php if ($active_trips->num_rows > 0): ?>
                <div class="active-trips-list">
                    <?php while ($trip = $active_trips->fetch_assoc()): ?>
                        <div class="trip-activity-card">
                            <div class="trip-status">
                                <span class="status-indicator status-<?php echo $trip['status']; ?>">
                                    <?php 
                                    switch($trip['status']) {
                                        case 'picked_up': echo '🟢 Picked Up'; break;
                                        case 'in_transit': echo '🔵 In Transit'; break;
                                        case 'dropped_off': echo '✅ Dropped Off'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="trip-details">
                                <strong><?php echo htmlspecialchars($trip['student_name']); ?></strong><br>
                                <small>Driver: <?php echo htmlspecialchars($trip['driver_name']); ?></small><br>
                                <small>Pickup: <?php echo $trip['pickup_time']; ?> 
                                <?php if ($trip['status'] == 'dropped_off' && $trip['dropoff_time']): ?>
                                    | Dropped: <?php echo date('H:i', strtotime($trip['dropoff_time'])); ?>
                                <?php endif; ?>
                                </small>
                            </div>
                            <div class="trip-time">
                                <?php if ($trip['status'] == 'in_transit'): ?>
                                    <span class="time-elapsed"><?php echo $trip['minutes_elapsed']; ?>m ago</span>
                                <?php elseif ($trip['status'] == 'picked_up'): ?>
                                    <span class="time-elapsed"><?php echo $trip['minutes_elapsed']; ?>m ago</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-active-trips">No active trips at the moment</p>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications -->
        <div class="recent-notifications-section">
            <h3>📢 Recent Notifications</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 10");
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr><td>{$row['message']}</td><td>" . date('M d, H:i', strtotime($row['created_at'])) . "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No recent notifications</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #555;
        }
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        /* Active Trips Section */
        .active-trips-section, .recent-notifications-section {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .active-trips-section h3, .recent-notifications-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        .active-trips-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .trip-activity-card {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .trip-status {
            flex: 0 0 120px;
        }
        .status-indicator {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-picked_up { background: #d4edda; color: #155724; }
        .status-in_transit { background: #cce5ff; color: #004085; }
        .status-dropped_off { background: #d1ecf1; color: #0c5460; }
        .trip-details {
            flex: 1;
            margin: 0 15px;
        }
        .trip-details strong {
            color: #333;
        }
        .trip-details small {
            color: #666;
        }
        .trip-time {
            flex: 0 0 80px;
            text-align: right;
        }
        .time-elapsed {
            background: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .no-active-trips {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        .last-updated {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
        }
        .last-updated small {
            font-size: 12px;
        }
        
        /* Activity Sections */
        .activity-section {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .activity-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .activity-card {
            display: flex;
            align-items: center;
            background: white;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .activity-icon {
            font-size: 18px;
            margin-right: 12px;
            min-width: 25px;
        }
        .activity-details {
            flex: 1;
            font-size: 14px;
        }
        .activity-details strong {
            color: #333;
            display: block;
            margin-bottom: 2px;
        }
        .activity-details small {
            color: #666;
        }
        .activity-time {
            text-align: right;
            min-width: 70px;
        }
        .activity-time small {
            color: #666;
            font-size: 11px;
        }
        .role-badge, .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .role-parent { background: #e7f3ff; color: #0066cc; }
        .role-driver { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .no-activity {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</body>
</html>