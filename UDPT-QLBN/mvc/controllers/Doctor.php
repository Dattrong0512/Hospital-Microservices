<?php
class Doctor extends Controller {
    
    private $baseUrl = '/UDPT-QLBN';
    
    public function __construct() {
        // Khởi tạo session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Debug session để xác định vấn đề
        error_log("[DOCTOR] Session data at constructor: " . json_encode($_SESSION));
        
        // Kiểm tra đăng nhập trước
        if (!isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
            // Nếu chưa đăng nhập, chuyển đến trang đăng nhập với redirect
            error_log("[DOCTOR] Authentication check failed, redirecting to login");
            header("Location: {$this->baseUrl}/Auth/login?redirect=" . urlencode("{$this->baseUrl}/Doctor/dashboard"));
            exit;
        }
        
        // Check if user has proper role - sử dụng strtolower để không phân biệt hoa/thường
        if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'doctor') {
            // Thêm debug để xác định vấn đề
            error_log("[DOCTOR] Role check failed - Role: " . ($_SESSION['role'] ?? 'not set'));
            // Nếu không phải vai trò doctor, chuyển đến trang lỗi
            header("Location: {$this->baseUrl}/Error/forbidden");
            exit;
        }
        
        // Log khi truy cập thành công
        error_log("[DOCTOR] Doctor controller accessed successfully by: " . ($_SESSION['username'] ?? 'unknown'));
    }
    
    public function dashboard() {
        error_log("[DOCTOR] Loading dashboard view for: " . ($_SESSION['username'] ?? 'unknown'));
        
        // Cập nhật dữ liệu truyền vào view, sử dụng cùng cấu trúc với Staff
        $data = [
            'username' => $_SESSION['username'] ?? 'Dr. Unknown',
            'fullname' => $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Dr. Unknown',
            'pageTitle' => 'Dashboard - Bác sĩ',
        ];
        
        // Debug trước khi render view
        error_log("[DOCTOR] Rendering dashboard with data: " . json_encode($data));
        
        // Thêm header/footer nếu cần
        $this->view("pages/doctor/Dashboard", $data);
    }
}