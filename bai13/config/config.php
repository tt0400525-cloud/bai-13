<?php
// Thông tin cấu hình cơ sở dữ liệu
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bai12_crud';

// Kết nối đến cơ sở dữ liệu
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}

// Cấu hình charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Khởi tạo session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
