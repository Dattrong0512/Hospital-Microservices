<?php
class Staff extends Controller {
    private $baseUrl = '/UDPT-QLBN';


    public function __construct() {
        // Khởi tạo session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Kiểm tra đăng nhập trước
        if (!isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
            // Nếu chưa đăng nhập, chuyển đến trang đăng nhập
            header("Location: {$this->baseUrl}/Auth/login?redirect=" . urlencode("{$this->baseUrl}/Staff/dashboard"));
            exit;
        }
        
        // Check if user has proper role - sử dụng strtolower để không phân biệt hoa/thường
        if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'staff') {
            // Nếu không phải vai trò staff, chuyển đến trang lỗi
            header("Location: {$this->baseUrl}/Error/forbidden");
            exit;
        }
    }
    
    public function dashboard() {
        $data = [
            'username' => $_SESSION['username'] ?? 'Unknown',
            'fullname' => $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Unknown',
            'pageTitle' => 'Dashboard - Nhân viên',
        ];
        
        $this->view("pages/staff/Dashboard", $data);
    }
    
    public function patientManagement() {
        $data = [
            'username' => $_SESSION['username'] ?? 'Unknown',
            'fullname' => $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Unknown',
            'pageTitle' => 'Quản lý bệnh nhân',
        ];
        
        $this->view("pages/staff/PatientManagement", $data);
    }
    
    public function appointmentManagement() {
        $data = [
            'username' => $_SESSION['username'] ?? 'Unknown',
            'fullname' => $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Unknown',
            'pageTitle' => 'Quản lý lịch hẹn khám',
        ];
        
        $this->view("pages/staff/AppointmentManagement", $data);
    }
}