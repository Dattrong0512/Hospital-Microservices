<?php
require_once 'mvc/services/AuthService.php';

class Auth extends Controller {
    private $userModel;
    private $baseUrl = '/UDPT-QLBN';
    private $authService;

    public function __construct() {
        $this->userModel = $this->model("UserModel");
        $this->authService = new AuthService();
    }
    
    /**
     * Hiển thị trang đăng nhập
     */
    public function login() {
        // Kiểm tra nếu đã đăng nhập thì chuyển hướng đến trang phù hợp
        if ($this->authService->isLoggedIn()) {
            $role = $this->authService->getUserRole();
            $this->redirectBasedOnRole($role);
            exit;
        }
        
        // Lấy URL chuyển hướng từ query parameter (nếu có)
        $data = [];
        if (isset($_GET['redirect'])) {
            $data['redirect'] = $_GET['redirect'];
        }
        
        // Hiển thị form đăng nhập
        $this->view("pages/auth/Login", $data);
    }
    
    /**
     * Xử lý đăng nhập
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            try {
                // Log thông tin đăng nhập (chỉ username, không log mật khẩu)
                error_log("[AUTH] Attempting login for user: $username");
                
                // Gọi API đăng nhập qua AuthService
                $result = $this->authService->login($username, $password);
                
                // Log kết quả (bỏ qua thông tin nhạy cảm)
                error_log("[AUTH] Login result status: " . (isset($result['accessToken']) ? 'Success' : 'Failed'));
                
                // Kiểm tra kết quả
                if (isset($result['accessToken'])) {
                    // Lấy thông tin người dùng từ JWT
                    $userInfo = $this->authService->getUserInfo();
                    $userRole = $this->authService->getUserRole();
                    
                    // Log thông tin người dùng
                    error_log("[AUTH] User info: " . json_encode([
                        'id' => $userInfo['sub'] ?? 'null',
                        'name' => $userInfo['name'] ?? 'null',
                        'role' => $userRole ?? 'null'
                    ]));
                    
                    // Cookie chỉ có thể check ở request tiếp theo, không nên hiển thị warning không chính xác
                    error_log("[AUTH] Note: Cookie from backend will be available in next request");
                    
                    // Lưu thông tin vào session
                    $_SESSION['is_authenticated'] = true;
                    $_SESSION['user_id'] = $userInfo['sub'] ?? null;
                    $_SESSION['username'] = $userInfo['name'] ?? $username;
                    $_SESSION['role'] = $userRole;
                    
                    // Thêm debug log trước khi redirect
                    error_log("[AUTH] Session after login: " . json_encode($_SESSION));
                    
                    // Chuyển hướng người dùng
                    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                        error_log("[AUTH] Redirecting to: " . $_POST['redirect']);
                        header("Location: " . $_POST['redirect']);
                    } else {
                        error_log("[AUTH] No redirect specified, using role-based redirect");
                        $this->redirectBasedOnRole($userRole);
                    }
                    exit;
                } else {
                    error_log("[AUTH] Login failed: No token in response");
                    throw new Exception('Đăng nhập không thành công');
                }
            } catch (Exception $e) {
                // Log lỗi
                error_log("[AUTH] Login error: " . $e->getMessage());
                
                // Xử lý lỗi đăng nhập
                $data = [
                    'error' => $e->getMessage(),
                    'username' => $username // Giữ lại username đã nhập
                ];
                
                if (isset($_POST['redirect'])) {
                    $data['redirect'] = $_POST['redirect'];
                }
                
                $this->view("pages/auth/Login", $data);
            }
        } else {
            // Nếu không phải POST request, chuyển hướng đến trang đăng nhập
            error_log("[AUTH] Non-POST request to authenticate, redirecting to login");
            header("Location: {$this->baseUrl}/auth/login");
            exit;
        }
    }
    
    /**
     * Đăng xuất
     */
    public function logout() {
        error_log("[AUTH] Logging out user: " . ($_SESSION['username'] ?? 'Unknown'));
        error_log("[AUTH] Session before logout: " . json_encode($_SESSION));
        error_log("[AUTH] Cookies before logout: " . json_encode($_COOKIE));
        
        // Gọi API đăng xuất qua AuthService
        $this->authService->logout();
        
        // Xóa session
        session_unset();
        session_destroy();
        
        // Log sau khi đăng xuất
        error_log("[AUTH] Logout complete, redirecting to login page");
        
        // Chuyển hướng về trang đăng nhập
        header("Location: {$this->baseUrl}/auth/login");
        exit;  
    }

    // public function authenticate() {
    //     if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //         $username = $_POST['username'] ?? '';
    //         $password = $_POST['password'] ?? '';
            
    //         try {
    //             // Log thông tin đăng nhập (chỉ username, không log mật khẩu)
    //             error_log("[AUTH] Attempting login for user: $username");
                
    //             // USE MOCK AUTH INSTEAD OF REAL API
    //             // Comment out the real API call
    //             // $result = $this->authService->login($username, $password);
                
    //             // Use mock authentication instead
    //             $result = $this->authService->mockLogin($username, $password);
                
    //             // Log kết quả (bỏ qua thông tin nhạy cảm)
    //             error_log("[AUTH] Login result status: " . (isset($result['accessToken']) ? 'Success' : 'Failed'));
                
    //             // Kiểm tra kết quả
    //             if (isset($result['accessToken'])) {
    //                 // Lấy thông tin người dùng từ JWT
    //                 $userInfo = $this->authService->getUserInfo();
    //                 $userRole = $this->authService->getUserRole();
                    
    //                 // Log thông tin người dùng
    //                 error_log("[AUTH] User info: " . json_encode([
    //                     'id' => $userInfo['sub'] ?? 'null',
    //                     'name' => $userInfo['name'] ?? 'null',
    //                     'role' => $userRole ?? 'null'
    //                 ]));
                    
    //                 // Cookie chỉ có thể check ở request tiếp theo, không nên hiển thị warning không chính xác
    //                 error_log("[AUTH] Note: Cookie from backend will be available in next request");
                    
    //                 // Lưu thông tin vào session
    //                 $_SESSION['is_authenticated'] = true;
    //                 $_SESSION['user_id'] = $userInfo['sub'] ?? null;
    //                 $_SESSION['username'] = $userInfo['name'] ?? $username;
    //                 $_SESSION['role'] = $userRole;
                    
    //                 // Thêm debug log trước khi redirect
    //                 error_log("[AUTH] Session after login: " . json_encode($_SESSION));
                    
    //                 // Chuyển hướng người dùng
    //                 if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
    //                     error_log("[AUTH] Redirecting to: " . $_POST['redirect']);
    //                     header("Location: " . $_POST['redirect']);
    //                 } else {
    //                     error_log("[AUTH] No redirect specified, using role-based redirect");
    //                     $this->redirectBasedOnRole($userRole);
    //                 }
    //                 exit;
    //             } else {
    //                 error_log("[AUTH] Login failed: No token in response");
    //                 throw new Exception('Đăng nhập không thành công');
    //             }
    //         } catch (Exception $e) {
    //             // Log lỗi
    //             error_log("[AUTH] Login error: " . $e->getMessage());
                
    //             // Xử lý lỗi đăng nhập
    //             $data = [
    //                 'error' => $e->getMessage(),
    //                 'username' => $username // Giữ lại username đã nhập
    //             ];
                
    //             if (isset($_POST['redirect'])) {
    //                 $data['redirect'] = $_POST['redirect'];
    //             }
                
    //             $this->view("pages/auth/Login", $data);
    //         }
    //     } else {
    //         // Nếu không phải POST request, chuyển hướng đến trang đăng nhập
    //         error_log("[AUTH] Non-POST request to authenticate, redirecting to login");
    //         header("Location: {$this->baseUrl}/auth/login");
    //         exit;
    //     }
    // }
    
    /**
     * Hiển thị thông tin tài khoản
     */
    public function profile() {
        // Kiểm tra đăng nhập
        if (!$this->authService->isLoggedIn()) {
            // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
            $_GET['redirect'] = "{$this->baseUrl}/auth/profile";
            header("Location: {$this->baseUrl}/auth/login?redirect=" . urlencode("{$this->baseUrl}/auth/profile"));
            exit;
        }
        
        // Lấy thông tin từ JWT
        $userInfo = $this->authService->getUserInfo();
        $roleInfo = $this->authService->getUserRole();
        
        // Kiểm tra token hết hạn
        $isExpired = $this->authService->isTokenExpired();
        if ($isExpired) {
            try {
                // Thử làm mới token
                $this->authService->refreshToken();
                $userInfo = $this->authService->getUserInfo(); // Lấy lại thông tin mới
            } catch (Exception $e) {
                // Nếu không thể làm mới, chuyển hướng đến trang đăng nhập
                $_SESSION['error'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
                header("Location: {$this->baseUrl}/auth/login?redirect=" . urlencode("{$this->baseUrl}/auth/profile"));
                exit;
            }
        }
        
        // Chuẩn bị dữ liệu để hiển thị
        $data = [
            'title' => 'Thông tin tài khoản',
            'user_info' => $userInfo,
            'role' => $roleInfo,
            'is_expired' => $isExpired
        ];
        
        // Hiển thị trang thông tin
        $this->view("pages/auth/Profile", $data);
    }
    
    /**
     * API endpoint để làm mới token
     */
    public function refreshToken() {
        header('Content-Type: application/json');
        
        try {
            error_log("[AUTH] Checking if token refresh is needed");
            // Kiểm tra nếu token gần hết hạn hoặc đã hết hạn
            if ($this->authService->isTokenExpired()) {
                error_log("[AUTH] Token is expired, attempting refresh");
                // Thực hiện làm mới token
                $result = $this->authService->refreshToken();
                
                if (isset($result['accessToken'])) {
                    error_log("[AUTH] Token refreshed successfully");
                    echo json_encode(['success' => true, 'message' => 'Token đã được làm mới']);
                } else {
                    error_log("[AUTH] Token refresh failed: No token in response");
                    echo json_encode(['success' => false, 'message' => 'Không thể làm mới token']);
                }
            } else {
                error_log("[AUTH] Token is still valid, no refresh needed");
                echo json_encode(['success' => true, 'message' => 'Token vẫn còn hiệu lực']);
            }
        } catch (Exception $e) {
            error_log("[AUTH] Token refresh error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function register() {
        // Kiểm tra nếu đã đăng nhập thì chuyển hướng
        if ($this->authService->isLoggedIn()) {
            $role = $this->authService->getUserRole();
            $this->redirectBasedOnRole($role);
            exit;
        }
        
        // Hiển thị form đăng ký
        $this->view("pages/auth/Register");
    }

    public function registerDoctor() {
        // Chỉ cho phép POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("[REGISTER_DOCTOR] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
            header("Location: /UDPT-QLBN/Auth/register");
            exit;
        }

        try {
            error_log("[REGISTER_DOCTOR] Starting registration process");
            
            // ✅ VALIDATE: Kiểm tra required fields
            $requiredFields = [
                'fullname' => 'Họ và tên',
                'gender' => 'Giới tính', 
                'birth_date' => 'Ngày sinh',
                'identity_card' => 'CCCD/CMND',
                'email' => 'Email',
                'phone_number' => 'Số điện thoại',
                'department' => 'Khoa',
                'username' => 'Tên đăng nhập',
                'password' => 'Mật khẩu',
                'confirm_password' => 'Xác nhận mật khẩu'
            ];
            
            $missingFields = [];
            foreach ($requiredFields as $field => $label) {
                if (empty($_POST[$field])) {
                    $missingFields[] = $label;
                }
            }
            
            if (!empty($missingFields)) {
                throw new Exception('Vui lòng điền đầy đủ thông tin: ' . implode(', ', $missingFields));
            }
            
            // ✅ VALIDATE: Password match
            if ($_POST['password'] !== $_POST['confirm_password']) {
                throw new Exception('Mật khẩu xác nhận không khớp');
            }
            
            // ✅ VALIDATE: Password strength
            $password = $_POST['password'];
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                throw new Exception('Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số');
            }
            
            // ✅ VALIDATE: Email format
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email không hợp lệ');
            }
            
            // ✅ VALIDATE: Phone number
            if (!preg_match('/^[0-9]{10,11}$/', $_POST['phone_number'])) {
                throw new Exception('Số điện thoại phải có 10-11 chữ số');
            }
            
            // ✅ VALIDATE: Identity card
            if (!preg_match('/^[0-9]{9,12}$/', $_POST['identity_card'])) {
                throw new Exception('CCCD/CMND phải có 9-12 chữ số');
            }
            
            // ✅ VALIDATE: Username format
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $_POST['username'])) {
                throw new Exception('Tên đăng nhập phải có 3-20 ký tự, chỉ chữ cái, số và dấu gạch dưới');
            }
            
            // ✅ VALIDATE: Department
            $allowedDepartments = [
                'Khoa Nội', 
                'Khoa Ngoại', 
                'Khoa Nhi', 
                'Khoa Hồi sức cấp cứu', 
                'Chấn thương chỉnh hình'
            ];
            if (!in_array($_POST['department'], $allowedDepartments)) {
                throw new Exception('Khoa không hợp lệ');
            }
            
            // ✅ VALIDATE: Birth date and age
            $birthDate = $_POST['birth_date'];
            $birthDateTime = DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$birthDateTime || $birthDateTime->format('Y-m-d') !== $birthDate) {
                throw new Exception('Ngày sinh không hợp lệ');
            }
            
            // Check age (must be at least 18)
            $today = new DateTime();
            $age = $today->diff($birthDateTime)->y;
            if ($age < 18) {
                throw new Exception('Bác sĩ phải từ 18 tuổi trở lên');
            }
            if ($age > 100) {
                throw new Exception('Ngày sinh không hợp lệ');
            }
            
            // ✅ PREPARE: Chuẩn bị dữ liệu để gửi API
            $doctorData = [
                'fullname' => trim($_POST['fullname']),
                'gender' => $_POST['gender'],
                'birth_date' => $_POST['birth_date'],
                'identity_card' => trim($_POST['identity_card']),
                'email' => trim(strtolower($_POST['email'])),
                'phone_number' => trim($_POST['phone_number']),
                'department' => $_POST['department'],
                'username' => trim(strtolower($_POST['username'])),
                'password' => $_POST['password']
            ];
            error_log("[REGISTER_DOCTOR] Attempting to register: " . $_POST['username']);
            error_log("[REGISTER_DOCTOR] Department: " . $_POST['department']);
            // ✅ API CALL: Gọi AuthService để đăng ký
            $result = $this->authService->registerDoctor($doctorData);
            error_log("[REGISTER_DOCTOR] Registration result: " . json_encode($result));
            // ✅ SUCCESS: Kiểm tra kết quả thành công
            if (isset($result['success']) && $result['success'] === true) {
                $data = [
                    'success' => 'Đăng ký bác sĩ thành công! Vui lòng đăng nhập để sử dụng hệ thống.',
                    'show_login_link' => true,
                    'registered_username' => $doctorData['username']
                ];
                error_log("[REGISTER_DOCTOR] Registration successful for: " . $doctorData['username']);
                $this->view("pages/auth/Register", $data);
                return;
            }
            // ✅ ERROR: Xử lý lỗi từ API
            $errorMessage = $result['message'] ?? 'Đăng ký thất bại. Vui lòng thử lại.';
            error_log("[REGISTER_DOCTOR] Registration failed: " . $errorMessage);
            throw new Exception($errorMessage);
        } catch (Exception $e) {
            error_log("[REGISTER_DOCTOR] Registration error: " . $e->getMessage());
            // ✅ ERROR DISPLAY: Hiển thị lỗi và giữ lại dữ liệu
            $data = [
                'error' => $e->getMessage(),
                'form_data' => $_POST // Giữ lại dữ liệu đã nhập (trừ password)
            ];
            unset($data['form_data']['password']);
            unset($data['form_data']['confirm_password']);
            $this->view("pages/auth/Register", $data);
        }
    }
    
    /**
     * Điều hướng người dùng dựa trên vai trò
     */
    private function redirectBasedOnRole($role) {
    // Debug thông tin vai trò để tìm lỗi
        error_log("Redirecting user with role: " . ($role ?? 'null'));
        
        // Kiểm tra vai trò có null hay không
        if (empty($role)) {
            // Nếu không có vai trò, chuyển đến trang mặc định
            error_log("No role detected, redirecting to home");
            header("Location: {$this->baseUrl}/Home");
            exit;
        }
        
        // Chuẩn hóa role sang chữ thường
        $role = strtolower($role);
        
        // Kiểm tra vai trò để chuyển hướng phù hợp
        switch($role) {
            case 'doctor':
                error_log("Redirecting to doctor dashboard");
                header("Location: {$this->baseUrl}/Doctor/dashboard");
                break;
            case 'staff':
                error_log("Redirecting to staff dashboard");
                header("Location: {$this->baseUrl}/Staff/dashboard");
                break;
            default:
                error_log("Unknown role: $role, redirecting to home");
                header("Location: {$this->baseUrl}/Home");
                break;
        }
        exit; // Đảm bảo dừng thực thi sau khi redirect
    }
    
    /**
     * Kiểm tra quyền truy cập
     * Phương thức này có thể được gọi từ các controller khác
     */
    public function checkPermission($requiredRole) {
        if (!$this->authService->isLoggedIn()) {
            // Lưu URL hiện tại để chuyển hướng sau khi đăng nhập
            $currentUrl = $_SERVER['REQUEST_URI'];
            header("Location: {$this->baseUrl}/auth/login?redirect=" . urlencode($currentUrl));
            exit;
        }
        
        $userRole = $this->authService->getUserRole();
        
        // Nếu token hết hạn, thử làm mới
        if ($this->authService->isTokenExpired()) {
            try {
                $this->authService->refreshToken();
                $userRole = $this->authService->getUserRole();
            } catch (Exception $e) {
                // Nếu không thể làm mới, chuyển hướng đến trang đăng nhập
                $_SESSION['error'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
                $currentUrl = $_SERVER['REQUEST_URI'];
                header("Location: {$this->baseUrl}/auth/login?redirect=" . urlencode($currentUrl));
                exit;
            }
        }
        
        // Kiểm tra quyền
        if (strtolower($userRole) !== strtolower($requiredRole)) {
            // Không có quyền truy cập
            header("Location: {$this->baseUrl}/error/forbidden");
            exit;
        }
        
        return true;
    }
}