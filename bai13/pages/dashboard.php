<?php
require_once '../config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy danh sách người dùng
$sql = "SELECT id, username, fullname, email, role, avatar, created_at FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản lý Người dùng</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-bg">

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-users-cog"></i> Quản Lý Hệ Thống</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong> (<?php echo $_SESSION['role'] == 1 ? 'Admin' : 'User'; ?>)</span>
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>
</nav>

<div class="container dashboard-container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Danh sách Người dùng</h2>
        </div>
        <div class="col-md-6 text-end">
            <!-- Chỉ Admin mới được thêm người dùng từ Dashboard -->
            <?php if($_SESSION['role'] == 1): ?>
            <a href="user_add.php" class="btn btn-success"><i class="fas fa-plus"></i> Thêm Người Dùng</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Avatar</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <?php if($_SESSION['role'] == 1): ?>
                            <th class="text-center" style="width: 150px;">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php 
                                $stt = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                            ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td>
                                        <?php if(!empty($row['avatar']) && file_exists("../uploads/avatars/" . $row['avatar'])): ?>
                                            <img src="../uploads/avatars/<?php echo htmlspecialchars($row['avatar']); ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <?php if($row['role'] == 1): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    
                                    <?php if($_SESSION['role'] == 1): ?>
                                    <td class="text-center action-btns">
                                        <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Sửa"><i class="fas fa-edit"></i></a>
                                        
                                        <!-- Không cho phép tự xóa chính mình -->
                                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="user_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');" title="Xóa"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">Không có dữ liệu người dùng</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
