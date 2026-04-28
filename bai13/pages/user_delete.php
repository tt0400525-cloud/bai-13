<?php
require_once '../config/config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: dashboard.php");
    exit();
}

// Xử lý xóa
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);
    
    // Không cho phép tài khoản đang đăng nhập tự xóa chính mình
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Bạn không thể xóa tài khoản của chính mình!";
        header("Location: dashboard.php");
        exit();
    }
    
    // Thực hiện truy vấn xóa
    $sql = "DELETE FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Đã xóa người dùng thành công!";
        } else {
            $_SESSION['error'] = "Không thể xóa người dùng. Vui lòng thử lại.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $_SESSION['error'] = "ID người dùng không hợp lệ.";
}

mysqli_close($conn);
header("Location: dashboard.php");
exit();
?>
