<?php
require_once 'config/config.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_id'])) {
    // Nếu đã đăng nhập, chuyển hướng đến dashboard
    header("Location: pages/dashboard.php");
    exit();
} else {
    // Nếu chưa đăng nhập, chuyển hướng đến trang đăng nhập
    header("Location: pages/login.php");
    exit();
}
?>
