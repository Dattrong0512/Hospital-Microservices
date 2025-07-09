<?php
// Debug session
error_log('[HEADER] Session: ' . json_encode($_SESSION));
$displayFullname = $_SESSION['fullname'] ?? 'User';
$staff = null;
$doctor = null;

if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    if ($role === 'doctor' && isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../../services/DoctorService.php';
            $doctorService = new DoctorService();
            $doctor = $doctorService->getDoctorById($_SESSION['user_id']);
            if (isset($doctor['fullname'])) {
                $displayFullname = $doctor['fullname'];
            }
        } catch (Exception $e) {
            $displayFullname = $_SESSION['username'] ?? 'User';
        }
    } elseif ($role === 'staff' && isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../../services/StaffService.php';
            $staffService = new StaffService();
            $staff = $staffService->getStaffById($_SESSION['user_id']);
            if (isset($staff['fullname'])) {
                $displayFullname = $staff['fullname'];
            }
        } catch (Exception $e) {
            $displayFullname = $_SESSION['username'] ?? 'User';
        }
    } else {
        $displayFullname = $_SESSION['username'] ?? 'User';
    }
} else {
    $displayFullname = $_SESSION['username'] ?? 'User';
}
// Hiển thị tên vai trò tiếng Việt
$displayRole = 'Nhân viên';
if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    if ($role === 'doctor') $displayRole = 'Bác sĩ';
    else if ($role === 'staff') $displayRole = 'Nhân viên';
    else $displayRole = ucfirst($role);
}
?>
<header class="hospital-header">
    <div class="header-logo">
        <!-- Thêm tiền tố BASE_URL vào src -->
        <img src="/UDPT-QLBN/public/asset/logo.jpg" alt="Logo" class="header-logo-img">
        <h1>Hệ thống Quản lý Bệnh viện ABC</h1>
    </div>
    
    <div class="header-user">
        <?php if(isset($_SESSION['is_authenticated'])): ?>
            <div class="user-profile dropdown">
                <div class="dropdown-toggle" onclick="toggleUserMenu()">
                    <span class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </span>
                    <div class="user-details">
                        <span class="user-name" id="headerFullName"><?php echo htmlspecialchars($displayFullname); ?></span>
                        <span class="user-role" id="headerRole">
                            <?php
                            $role = $_SESSION['role'] ?? '';
                            if (strtolower($role) === 'doctor') echo 'Bác sĩ';
                            else if (strtolower($role) === 'staff') echo 'Nhân viên';
                            else echo htmlspecialchars($role);
                            ?>
                        </span>
                    </div>
                    <i class="fas fa-caret-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="fas fa-user-cog"></i> Thông tin cá nhân
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/UDPT-QLBN/Auth/logout" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="/UDPT-QLBN/Auth/login" class="login-button">Đăng nhập</a>
        <?php endif; ?>
    </div>
</header>

<!-- Modal Thông tin Bác sĩ/Nhân viên -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="profileModalLabel">Thông tin tài khoản</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <?php if ($doctor): ?>
        <ul class="list-group">
          <li class="list-group-item"><b>Họ tên:</b> <?= htmlspecialchars($doctor['fullname'] ?? '') ?></li>
          <li class="list-group-item"><b>Giới tính:</b> <?= htmlspecialchars($doctor['gender'] ?? '') ?></li>
          <li class="list-group-item"><b>Ngày sinh:</b> <?= htmlspecialchars($doctor['birth_date'] ?? '') ?></li>
          <li class="list-group-item"><b>Khoa:</b> <?= htmlspecialchars($doctor['department'] ?? '') ?></li>
          <li class="list-group-item"><b>Email:</b> <?= htmlspecialchars($doctor['email'] ?? '') ?></li>
          <li class="list-group-item"><b>Số điện thoại:</b> <?= htmlspecialchars($doctor['phone_number'] ?? '') ?></li>
          <li class="list-group-item"><b>CCCD:</b> <?= htmlspecialchars($doctor['identity_card'] ?? '') ?></li>
          <li class="list-group-item"><b>Tên đăng nhập:</b> <?= htmlspecialchars($doctor['username'] ?? '') ?></li>
        </ul>
        <?php elseif ($staff): ?>
        <ul class="list-group">
          <li class="list-group-item"><b>Họ tên:</b> <?= htmlspecialchars($staff['fullname'] ?? '') ?></li>
          <li class="list-group-item"><b>Giới tính:</b> <?= htmlspecialchars($staff['gender'] ?? '') ?></li>
          <li class="list-group-item"><b>Ngày sinh:</b> <?= htmlspecialchars($staff['birth_date'] ?? '') ?></li>
          <li class="list-group-item"><b>Email:</b> <?= htmlspecialchars($staff['email'] ?? '') ?></li>
          <li class="list-group-item"><b>Số điện thoại:</b> <?= htmlspecialchars($staff['phone_number'] ?? '') ?></li>
          <li class="list-group-item"><b>CCCD:</b> <?= htmlspecialchars($staff['identity_card'] ?? '') ?></li>
          <li class="list-group-item"><b>Tên đăng nhập:</b> <?= htmlspecialchars($staff['username'] ?? '') ?></li>
        </ul>
        <?php else: ?>
        <div class="alert alert-danger">Không tìm thấy thông tin tài khoản.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function toggleUserMenu() {
    const menu = document.getElementById('userDropdownMenu');
    menu.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    const menu = document.getElementById('userDropdownMenu');
    
    if (dropdown && !dropdown.contains(event.target) && menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }
});
</script>