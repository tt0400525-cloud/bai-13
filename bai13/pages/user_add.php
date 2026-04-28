<?php
require_once '../config/config.php';

// Kiểm tra đăng nhập và quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$username = $fullname = $email = $role = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc.";
    } else {
        $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error = "Tên đăng nhập hoặc Email đã tồn tại.";
            } else {
                // Xử lý upload avatar
                $avatar = NULL;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                        $new_filename = "new_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], "../uploads/avatars/" . $new_filename)) {
                            $avatar = $new_filename;
                        }
                    }
                }

                $sql = "INSERT INTO users (username, password, fullname, email, role, avatar) VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, "ssssis", $username, $hashed_password, $fullname, $email, $role, $avatar);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['message'] = "Thêm người dùng mới thành công!";
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Người Dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-bg">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="dashboard-card">
                <h4 class="mb-4 text-center" style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Thêm Người Dùng Mới</h4>
                <div class="card-body p-0">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                            <div id="avatarPreviewContainer">
                                <div id="avatarPlaceholder" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--success) 0%, #059669 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 10px auto; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <img src="" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--success); box-shadow: 0 4px 10px rgba(0,0,0,0.1);" class="d-none" id="avatarPreview">
                            </div>
                            <div class="mt-2">
                                <label for="avatarInput" class="btn btn-outline-success btn-sm">Thêm ảnh đại diện</label>
                                <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                            </div>
                            <small class="text-muted" id="fileNameDisplay">Dán ảnh hoặc chọn file</small>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên đăng nhập *</label>
                                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="fullname" class="form-control" required value="<?php echo htmlspecialchars($fullname); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-select">
                                <option value="0" <?php echo ($role === '0') ? 'selected' : ''; ?>>Người dùng (User)</option>
                                <option value="1" <?php echo ($role === '1') ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-success">Lưu thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const avatarInput = document.getElementById('avatarInput');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');

    avatarInput.addEventListener('change', function(e) {
        if(this.files.length > 0) handleFile(this.files[0]);
    });

    window.addEventListener('paste', e => {
        const items = e.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                const file = new File([blob], "new_user_" + Date.now() + ".png", {type: items[i].type});
                const container = new DataTransfer();
                container.items.add(file);
                avatarInput.files = container.files;
                handleFile(file);
            }
        }
    });

    function handleFile(file) {
        fileNameDisplay.textContent = "Đã chọn: " + file.name;
        fileNameDisplay.classList.add('text-success', 'fw-bold');
        const reader = new FileReader();
        reader.onload = e => {
            avatarPreview.src = e.target.result;
            avatarPreview.classList.remove('d-none');
            if(avatarPlaceholder) avatarPlaceholder.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    }
</script>
</body>
</html>
