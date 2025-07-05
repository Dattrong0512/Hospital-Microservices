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
                        <span class="user-name"><?= htmlspecialchars($_SESSION['fullname'] ?? 'User') ?></span>
                        <span class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Nhân viên') ?></span>
                    </div>
                    <i class="fas fa-caret-down dropdown-icon"></i>
                </div>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a href="/UDPT-QLBN/<?= $_SESSION['role'] ?>/profile" class="dropdown-item">
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