<?php
require_once '../config/config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$id = $username = $fullname = $email = $role = "";
$error = "";

// Lấy ID người dùng từ tham số GET
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);
    
    // Chuẩn bị câu lệnh select
    $sql = "SELECT username, fullname, email, role, avatar FROM users WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $username = $row['username'];
                $fullname = $row['fullname'];
                $email = $row['email'];
                $role = $row['role'];
                $avatar = $row['avatar'];
            } else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            echo "Có lỗi xảy ra. Vui lòng thử lại.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Nếu không có id hợp lệ thì trở về dashboard
    header("Location: dashboard.php");
    exit();
}

// Xử lý khi submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Validate
    if (empty($username) || empty($fullname) || empty($email)) {
        $error = "Vui lòng nhập Tên đăng nhập, Họ tên và Email.";
    } else {
        // Kiểm tra trùng username/email
        $sql_check = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ssi", $username, $email, $id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error = "Tên đăng nhập hoặc Email đã tồn tại ở tài khoản khác.";
            } else {
                // Xử lý upload avatar
                $new_avatar = $avatar;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                        $new_filename = $id . "_" . time() . "." . $ext;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], "../uploads/avatars/" . $new_filename)) {
                            if (!empty($avatar) && file_exists("../uploads/avatars/" . $avatar)) {
                                unlink("../uploads/avatars/" . $avatar);
                            }
                            $new_avatar = $new_filename;
                        }
                    } else {
                        $error = "Ảnh không hợp lệ hoặc quá lớn (>2MB).";
                    }
                }

                if (empty($error)) {
                    if (empty($password)) {
                        // Cập nhật không có password
                        $sql = "UPDATE users SET username=?, fullname=?, email=?, role=?, avatar=? WHERE id=?";
                        if ($stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($stmt, "sssisi", $username, $fullname, $email, $role, $new_avatar, $id);
                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['message'] = "Cập nhật thông tin người dùng thành công!";
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    // Cập nhật có password
                    $sql = "UPDATE users SET username=?, password=?, fullname=?, email=?, role=?, avatar=? WHERE id=?";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        mysqli_stmt_bind_param($stmt, "ssssisi", $username, $hashed_password, $fullname, $email, $role, $new_avatar, $id);
                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['message'] = "Cập nhật thông tin và mật khẩu thành công!";
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                        }
                        mysqli_stmt_close($stmt);
                    }
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
    <title>Chỉnh Sửa Người Dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-bg">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="dashboard-card">
                <h4 class="mb-4 text-center" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Chỉnh Sửa Người Dùng</h4>
                <div class="card-body p-0">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                            <div id="avatarPreviewContainer">
                                <?php if(!empty($avatar) && file_exists("../uploads/avatars/" . $avatar)): ?>
                                    <img src="../uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.1);" id="avatarPreview">
                                <?php else: ?>
                                    <div id="avatarPlaceholder" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 10px auto; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <img src="" alt="Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.1);" class="d-none" id="avatarPreview">
                                <?php endif; ?>
                            </div>
                            <div class="mt-2">
                                <label for="avatarInput" class="btn btn-outline-primary btn-sm">Thay đổi ảnh</label>
                                <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                            </div>
                            <small class="text-muted" id="fileNameDisplay">Dán ảnh hoặc chọn file (JPG, PNG, WEBP)</small>
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
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" placeholder="Để trống nếu không muốn đổi">
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Vai trò</label>
                                <select name="role" class="form-select" <?php echo ($id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <option value="0" <?php echo ($role == 0) ? 'selected' : ''; ?>>Người dùng (User)</option>
                                    <option value="1" <?php echo ($role == 1) ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                                </select>
                                <?php if($id == $_SESSION['user_id']): ?>
                                    <small class="text-danger mt-1 d-block">Bạn không thể tự thay đổi quyền của mình.</small>
                                    <input type="hidden" name="role" value="<?php echo $role; ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Xử lý xem trước ảnh và dán ảnh
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
                const file = new File([blob], "pasted_" + Date.now() + ".png", {type: items[i].type});
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
