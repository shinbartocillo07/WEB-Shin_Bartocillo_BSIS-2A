<?php
require_once 'config.php';

if (isLoggedIn()) {
    switch (getUserRole()) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'parent':
            redirect('parent/dashboard.php');
            break;
        case 'driver':
            redirect('driver/dashboard.php');
            break;
        default:
            redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>