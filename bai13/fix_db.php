<?php
require_once 'config/config.php';

// Cài đặt charset
mysqli_set_charset($conn, "utf8mb4");

// Xóa dữ liệu cũ
mysqli_query($conn, "TRUNCATE TABLE users");

// Thêm dữ liệu mẫu mới (mật khẩu 123456)
$password = password_hash("123456", PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)");

$users = [
    ['admin', $password, 'Quản trị viên', 'admin@example.com', 1],
    ['user1', $password, 'Người dùng 1', 'user1@example.com', 0],
    ['user2', $password, 'Người dùng 2', 'user2@example.com', 0],
    ['phanthanh', $password, 'Phan Thanh', 'thanh@example.com', 0]
];

foreach ($users as $u) {
    mysqli_stmt_bind_param($stmt, "ssssi", $u[0], $u[1], $u[2], $u[3], $u[4]);
    mysqli_stmt_execute($stmt);
}

echo "Đã khôi phục dữ liệu mẫu thành công với font chữ chuẩn tiếng Việt.";
?>
