<?php
require_once '../config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $fullname = $email = $avatar = "";
$error = "";
$success = "";

// Lấy thông tin người dùng hiện tại
$sql = "SELECT username, fullname, email, avatar FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $username = $row['username'];
            $fullname = $row['fullname'];
            $email = $row['email'];
            $avatar = $row['avatar'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Xử lý khi submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate
    if (empty($fullname) || empty($email)) {
        $error = "Vui lòng nhập Họ tên và Email.";
    } else {
        // Kiểm tra trùng email (không tính email của chính user này)
        $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error = "Email đã được sử dụng bởi tài khoản khác.";
            } else {
                // Xử lý upload avatar
                $avatar_updated = false;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                    $filename = $_FILES['avatar']['name'];
                    $filetype = $_FILES['avatar']['type'];
                    $filesize = $_FILES['avatar']['size'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Kiểm tra định dạng và kích thước (vd < 2MB)
                    if (!in_array($ext, $allowed)) {
                        $error = "Chỉ chấp nhận các định dạng JPG, JPEG, PNG, GIF, WEBP.";
                    } elseif ($filesize > 2 * 1024 * 1024) {
                        $error = "Kích thước ảnh không được vượt quá 2MB.";
                    } else {
                        // Tạo tên file mới để tránh trùng
                        $new_filename = $user_id . "_" . time() . "." . $ext;
                        $upload_path = "../uploads/avatars/" . $new_filename;

                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            // Xóa avatar cũ nếu có
                            if (!empty($avatar) && file_exists("../uploads/avatars/" . $avatar)) {
                                unlink("../uploads/avatars/" . $avatar);
                            }
                            $avatar = $new_filename;
                            $avatar_updated = true;
                        } else {
                            $error = "Có lỗi xảy ra khi tải ảnh lên.";
                        }
                    }
                }

                if (empty($error)) {
                    // Cập nhật thông tin
                    if (empty($password)) {
                        // Không đổi mật khẩu
                        $sql_update = "UPDATE users SET fullname=?, email=?, avatar=? WHERE id=?";
                        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
                            mysqli_stmt_bind_param($stmt_update, "sssi", $fullname, $email, $avatar, $user_id);
                            if (mysqli_stmt_execute($stmt_update)) {
                                $success = "Cập nhật hồ sơ thành công!";
                                $_SESSION['fullname'] = $fullname; // Cập nhật session
                            } else {
                                $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                            }
                            mysqli_stmt_close($stmt_update);
                        }
                    } else {
                        // Có đổi mật khẩu
                        $sql_update = "UPDATE users SET fullname=?, email=?, avatar=?, password=? WHERE id=?";
                        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            mysqli_stmt_bind_param($stmt_update, "ssssi", $fullname, $email, $avatar, $hashed_password, $user_id);
                            if (mysqli_stmt_execute($stmt_update)) {
                                $success = "Cập nhật hồ sơ và mật khẩu thành công!";
                                $_SESSION['fullname'] = $fullname; // Cập nhật session
                            } else {
                                $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                            }
                            mysqli_stmt_close($stmt_update);
                        }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Cá Nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-bg">

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-users-cog"></i> Quản Lý Hệ Thống</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong></span>
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="dashboard-card">
                <h4 class="mb-4 text-center" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">Hồ Sơ Của Bạn</h4>
                <div class="card-body p-0">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                            <div id="avatarPreviewContainer" class="profile-avatar-container">
                                <?php if(!empty($avatar) && file_exists("../uploads/avatars/" . $avatar)): ?>
                                    <img src="../uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 5px solid var(--primary); box-shadow: 0 8px 25px rgba(0,0,0,0.15);" id="avatarPreview">
                                <?php else: ?>
                                    <div id="avatarPlaceholder" style="width: 200px; height: 200px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 80px; margin: 0 auto 20px auto; border: 5px solid white; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <img src="" alt="Avatar" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 5px solid var(--primary); box-shadow: 0 8px 25px rgba(0,0,0,0.15);" class="d-none" id="avatarPreview">
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="avatarInput" class="btn btn-outline-primary btn-sm"><i class="fas fa-camera"></i> Đổi ảnh đại diện</label>
                                <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                            </div>
                            <small class="text-muted d-block mt-2" id="fileNameDisplay">Hỗ trợ JPG, PNG, GIF, WEBP (Tối đa 2MB)</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                                <small class="text-muted">Không thể thay đổi tên đăng nhập.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="fullname" class="form-control" required value="<?php echo htmlspecialchars($fullname); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" placeholder="Để trống nếu không muốn đổi">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Hiển thị tên file và xem trước ảnh khi người dùng chọn
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        if(e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    // Tính năng dán ảnh từ clipboard
    window.addEventListener('paste', e => {
        const items = e.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                
                // Tạo một File object từ blob để gán vào input
                const file = new File([blob], "pasted_image_" + Date.now() + ".png", {type: items[i].type});
                
                // Gán vào input file (sử dụng DataTransfer)
                const container = new DataTransfer();
                container.items.add(file);
                document.getElementById('avatarInput').files = container.files;
                
                handleFile(file);
            }
        }
    });

    function handleFile(file) {
        document.getElementById('fileNameDisplay').textContent = "Đã chọn: " + file.name;
        document.getElementById('fileNameDisplay').className = "text-success d-block mt-2 fw-bold";

        // Xử lý xem trước ảnh
        const reader = new FileReader();
        reader.onload = function(event) {
            const previewImg = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            
            previewImg.src = event.target.result;
            previewImg.classList.remove('d-none');
            
            if (placeholder) {
                placeholder.classList.add('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
</script>
</body>
</html>
