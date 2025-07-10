<div class="sidebar">
    <div class="sidebar-content">
        <ul class="nav-list">
            <?php 
            $current_url = $_SERVER['REQUEST_URI'];
            ?>
            
            <li class="nav-item <?php echo (strpos($current_url, 'dashboard') !== false) ? 'active' : ''; ?>">
                <a href="/UDPT-QLBN/staff/dashboard" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Tổng quan</span>
                </a>
            </li>
            <li class="nav-item <?php echo (strpos($current_url, 'Patient') !== false) ? 'active' : ''; ?>">
                <a href="/UDPT-QLBN/Patient/index" class="nav-link">
                    <i class="fas fa-user-injured"></i>
                    <span>Quản lý bệnh nhân</span>
                </a>
            </li>
            <li class="nav-item <?php echo (strpos($current_url, 'Appointment') !== false) ? 'active' : ''; ?>">
                <a href="/UDPT-QLBN/Appointment/index" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Quản lý lịch khám</span>
                </a>
            </li>
            <li class="nav-item <?php echo (strpos($current_url, 'Medicine') !== false) ? 'active' : ''; ?>">
                <a href="/UDPT-QLBN/Medicine/index" class="nav-link">
                    <i class="fas fa-prescription"></i>
                    <span>Quản lý thuốc</span>
                </a>
            </li>
        </ul>
    </div>
</div>