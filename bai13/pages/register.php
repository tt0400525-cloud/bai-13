<?php
require_once '../config/config.php';

// Nếu đã đăng nhập thì chuyển sang dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$username = $fullname = $email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate
    if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Kiểm tra username hoặc email đã tồn tại chưa
        $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error = "Tên đăng nhập hoặc Email đã được sử dụng.";
            } else {
                // Thêm người dùng mới
                $sql = "INSERT INTO users (username, password, fullname, email) VALUES (?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, "ssss", $username, $hashed_password, $fullname, $email);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Đăng ký thành công! Vui lòng đăng nhập.";
                        // Reset form
                        $username = $fullname = $email = "";
                    } else {
                        $error = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Quản lý Người dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Đăng Ký Tài Khoản</h2>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?> <a href="login.php" class="alert-link">Đăng nhập ngay</a>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="fullname" class="form-control" required value="<?php echo htmlspecialchars($fullname); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-success">Đăng Ký</button>
            </div>
            <div class="text-center">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
