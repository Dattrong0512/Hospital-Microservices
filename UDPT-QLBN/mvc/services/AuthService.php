<?php

require_once 'mvc/services/DoctorService.php';  
class AuthService {
    private $baseUrl;
    private $accessToken;
    private $refreshToken;
    private $decodedToken;
    private $doctorsApiUrl;
    private $doctorService;
    

    public function __construct() {
        // Khởi tạo session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Cấu hình endpoint API xác thực
        $this->baseUrl = "https://konggateway.hospitalmicroservices.live/api/v0";

        // ✅ THÊM: Khởi tạo DoctorService
        $this->doctorService = new DoctorService();
        
        
        // Log thông tin session và cookie để debug
        error_log("SESSION in constructor: " . json_encode($_SESSION));
        error_log("COOKIES in constructor: " . json_encode($_COOKIE));
        
        // Ưu tiên lấy token từ session, sau đó mới đến cookie
        $this->accessToken = $_SESSION['access_token'] ?? $_COOKIE['ACCESS_TOKEN'] ?? null;
        $this->refreshToken = $_SESSION['refresh_token'] ?? $_COOKIE['REFRESH_TOKEN'] ?? null;
        
        // Log token đã lấy
        if ($this->accessToken) {
            error_log("Token found in constructor. Length: " . strlen($this->accessToken));
            error_log("Token source: " . (isset($_SESSION['access_token']) ? 'SESSION' : (isset($_COOKIE['ACCESS_TOKEN']) ? 'COOKIE' : 'UNKNOWN')));
        } else {
            error_log("No token found in constructor");
        }
        
        // Giải mã token nếu có
        if ($this->accessToken) {
            $this->decodedToken = $this->decodeJWT($this->accessToken);
            error_log("Token decoded in constructor: " . ($this->decodedToken ? 'SUCCESS' : 'FAILED'));
            
            if ($this->decodedToken) {
                error_log("Decoded token role: " . ($this->getUserRole() ?? 'null'));
                error_log("Decoded token username: " . ($this->getUsername() ?? 'null'));
                
                // Đảm bảo lưu token vào session
                $_SESSION['access_token'] = $this->accessToken;
                if ($this->refreshToken) {
                    $_SESSION['refresh_token'] = $this->refreshToken;
                }
            }
        }
    }
    
    /**
     * Kiểm tra xem API có hoạt động không
     */
    public function checkApiStatus() {
        try {
            $response = $this->sendRequest('GET', '/Hello', null, false);
            return $response;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Đăng ký tài khoản bác sĩ
     */
    public function registerDoctor($userData) {
        // Gọi trực tiếp API Flask Python của Doctor Service
        $doctorApiUrl = "https://konggateway.hospitalmicroservices.live/api/v0/doctors/";
        error_log("[AuthService][registerDoctor] Sending POST to: $doctorApiUrl");
        error_log("[AuthService][registerDoctor] Data: " . json_encode($userData));
        $ch = curl_init($doctorApiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        error_log("[AuthService][registerDoctor] HTTP code: $httpCode");
        error_log("[AuthService][registerDoctor] Response: $response");
        if ($curlError) {
            error_log("[AuthService][registerDoctor] CURL error: $curlError");
            return ['success' => false, 'message' => 'Lỗi kết nối: ' . $curlError];
        }
        if ($httpCode === 201 || $httpCode === 200) {
            return ['success' => true, 'data' => json_decode($response, true)];
        } else {
            return ['success' => false, 'message' => $response];
        }
    }
    
    /**
     * Đăng ký tài khoản nhân viên
     */
    public function registerStaff($userData) {
        return $this->sendRequest('POST', '/account/register/user/staff', $userData, false);
    }
    
    /**
     * Đăng nhập
     */
    public function login($username, $password) {
        $loginData = [
            'username' => $username,
            'password' => $password
        ];
        
        // Log request
        error_log("[LOGIN] Sending login request to: {$this->baseUrl}/account/login");
        
        // Gửi request và nhận response với header và cookie
        $response = $this->sendRequest('POST', '/account/login', $loginData, false);
        
        // Kiểm tra cả trường accessToken và jwtToken (API có thể trả về một trong hai)
        if (isset($response['jwtToken']) || isset($response['accessToken'])) {
            // Lấy token từ response - ưu tiên jwtToken nếu có
            $accessToken = $response['jwtToken'] ?? $response['accessToken'];
            $refreshToken = $response['refreshToken'] ?? null;
            
            error_log("[LOGIN] Token received. Length: " . strlen($accessToken));
            
            // Lưu token vào biến và session
            $this->accessToken = $accessToken;
            $this->refreshToken = $refreshToken;
            $_SESSION['access_token'] = $accessToken;
            $_SESSION['refresh_token'] = $refreshToken;
            
            // Cookie đã được backend tự động set qua Set-Cookie header
            // Không cần thiết lập cookie thủ công nữa 
            // Chỉ cần log để xác nhận
            error_log("[LOGIN] Cookie status - ACCESS_TOKEN: " . (isset($_COOKIE['ACCESS_TOKEN']) ? 'Exists' : 'Not set yet (will be available in next request)'));
            
            // Giải mã token để lấy thông tin người dùng
            $this->decodedToken = $this->decodeJWT($this->accessToken);
            
            if ($this->decodedToken) {
                error_log("[LOGIN] Token decoded successfully");
                error_log("[LOGIN] User role: " . ($this->getUserRole() ?? 'null'));
                error_log("[LOGIN] Username: " . ($this->getUsername() ?? 'null'));
                
                // Lưu thông tin vào session
                $_SESSION['user_info'] = $this->getUserInfo();
                $_SESSION['is_authenticated'] = true;
                $_SESSION['role'] = $this->getUserRole();
                $_SESSION['username'] = $this->getUsername();
            } else {
                error_log("[LOGIN] Failed to decode token");
            }
            
            // Chuẩn hóa kết quả trả về
            $response['accessToken'] = $accessToken;
        } else {
            error_log("[LOGIN] Failed, no token received");
        }
        
        return $response;
    }
    
    /**
     * Làm mới token
     */
    public function refreshToken() {
            if (!$this->refreshToken) {
            throw new Exception('Không có refresh token');
        }
        
        $data = ['refreshToken' => $this->refreshToken];
        $response = $this->sendRequest('POST', '/account/refreshToken', $data, false);
        
        // Kiểm tra cả hai trường jwtToken và accessToken
        if (isset($response['jwtToken']) || isset($response['accessToken'])) {
            // Lấy token từ response - ưu tiên jwtToken nếu có
            $accessToken = $response['jwtToken'] ?? $response['accessToken'];
            $refreshToken = $response['refreshToken'] ?? null;
            
            // Cập nhật biến trong bộ nhớ
            $this->accessToken = $accessToken;
            if ($refreshToken) {
                $this->refreshToken = $refreshToken;
            }
            
            // Set cookie nếu backend không làm điều này
            if (!isset($_COOKIE['ACCESS_TOKEN']) || $_COOKIE['ACCESS_TOKEN'] !== $accessToken) {
                setcookie('ACCESS_TOKEN', $accessToken, time() + 86400, '/');
            }
            
            if ($refreshToken && (!isset($_COOKIE['REFRESH_TOKEN']) || $_COOKIE['REFRESH_TOKEN'] !== $refreshToken)) {
                setcookie('REFRESH_TOKEN', $refreshToken, time() + 604800, '/');
            }
            
            // Giải mã token mới
            $this->decodedToken = $this->decodeJWT($this->accessToken);
            
            // Cập nhật thông tin session
            $_SESSION['user_info'] = $this->getUserInfo();
            $_SESSION['role'] = $this->getUserRole();
            $_SESSION['username'] = $this->getUsername();
            
            // Chuẩn hóa kết quả trả về
            $response['accessToken'] = $accessToken;
        }
        
        return $response;
    }
    
    /**
     * Đăng xuất
     */
    public function logout() {
        error_log("[LOGOUT] Starting logout process");
        error_log("[LOGOUT] SESSION before logout: " . json_encode($_SESSION));
        error_log("[LOGOUT] COOKIES before logout: " . json_encode($_COOKIE));
        
        // Xóa thông tin lưu trong session
        unset($_SESSION['user_info']);
        unset($_SESSION['is_authenticated']);
        unset($_SESSION['role']);
        unset($_SESSION['username']);
        unset($_SESSION['access_token']);
        unset($_SESSION['refresh_token']);
        
        // Xóa cookie
        if (isset($_COOKIE['ACCESS_TOKEN'])) {
            $cookieCleared = setcookie('ACCESS_TOKEN', '', time() - 3600, '/');
            error_log("[LOGOUT] ACCESS_TOKEN cookie cleared: " . ($cookieCleared ? "SUCCESS" : "FAILED"));
        }
        
        if (isset($_COOKIE['REFRESH_TOKEN'])) {
            $refreshCleared = setcookie('REFRESH_TOKEN', '', time() - 3600, '/');
            error_log("[LOGOUT] REFRESH_TOKEN cookie cleared: " . ($refreshCleared ? "SUCCESS" : "FAILED"));
        }
        
        // Xóa các biến trong bộ nhớ
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->decodedToken = null;
        
        error_log("[LOGOUT] All credentials cleared from memory");
        error_log("[LOGOUT] SESSION after logout: " . json_encode($_SESSION));
        
        return ['success' => true, 'message' => 'Đã đăng xuất'];
    }
    
    /**
     * Kiểm tra đã đăng nhập chưa
     */
    public function isLoggedIn() {
        error_log("[AUTH CHECK] Starting isLoggedIn check");
        error_log("[AUTH CHECK] accessToken in memory: " . ($this->accessToken ? "EXISTS" : "NULL"));
        error_log("[AUTH CHECK] COOKIES: " . json_encode($_COOKIE));
        error_log("[AUTH CHECK] SESSION: " . json_encode($_SESSION));
        
        // 1. Kiểm tra cookie từ backend trước (ưu tiên)
        if (isset($_COOKIE['ACCESS_TOKEN']) && !empty($_COOKIE['ACCESS_TOKEN'])) {
            error_log("[AUTH CHECK] Found cookie from backend");
            
            // Chỉ cần giải mã nếu token trong bộ nhớ khác với cookie
            if ($this->accessToken !== $_COOKIE['ACCESS_TOKEN']) {
                $this->accessToken = $_COOKIE['ACCESS_TOKEN'];
                $this->decodedToken = $this->decodeJWT($this->accessToken);
            }
            
            if ($this->decodedToken && !$this->isTokenExpired()) {
                // Đồng bộ với session để đảm bảo nhất quán
                $_SESSION['access_token'] = $this->accessToken;
                $_SESSION['is_authenticated'] = true;
                $_SESSION['role'] = $this->getUserRole();
                $_SESSION['username'] = $this->getUsername();
                
                error_log("[AUTH CHECK] Valid token in backend cookie, user is logged in");
                return true;
            }
        }
        
        // 2. Kiểm tra token trong bộ nhớ
        if ($this->accessToken !== null) {
            if (!$this->isTokenExpired()) {
                error_log("[AUTH CHECK] Valid token in memory, user is logged in");
                return true;
            }
            error_log("[AUTH CHECK] Token in memory expired");
        }
        
        // 3. Kiểm tra session như backup
        if (isset($_SESSION['access_token']) && !empty($_SESSION['access_token'])) {
            // Chỉ giải mã nếu token khác với bộ nhớ
            if ($this->accessToken !== $_SESSION['access_token']) {
                $this->accessToken = $_SESSION['access_token'];
                $this->decodedToken = $this->decodeJWT($this->accessToken);
            }
            
            if ($this->decodedToken && !$this->isTokenExpired()) {
                error_log("[AUTH CHECK] Valid token in session, user is logged in");
                return true;
            }
        }
        
        error_log("[AUTH CHECK] No valid authentication found");
        return false;
    }
    
    /**
     * Lấy token hiện tại
     */
    public function getAccessToken() {
        return $this->accessToken;
    }
    
    /**
     * Lấy tên người dùng từ token
     */
    public function getUsername() {
        if (!$this->decodedToken) {
            return null;
        }
        
        return $this->decodedToken['name'] ?? null;
    }
    
    /**
     * Giải mã JWT token để lấy thông tin
     * @param string $jwt JWT token cần giải mã
     * @return array|null Dữ liệu đã giải mã hoặc null nếu lỗi
     */
    public function decodeJWT($jwt) {
        try {
            $tokenParts = explode('.', $jwt);
            
            // JWT token cần có 3 phần: header, payload, signature
            if (count($tokenParts) != 3) {
                error_log("Invalid JWT format: missing parts");
                return null;
            }
            
            // Lấy phần payload (phần thứ 2)
            $payload = $tokenParts[1];
            
            // Thực hiện base64url decode đúng cách
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $payload = base64_decode($payload);
            
            if ($payload === false) {
                error_log("Failed to decode JWT payload");
                return null;
            }
            
            // Chuyển JSON thành mảng associative
            $payloadData = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Failed to parse JWT payload JSON: " . json_last_error_msg());
                return null;
            }
            
            error_log("JWT payload successfully decoded: " . json_encode(array_keys($payloadData)));
            return $payloadData;
        }
        catch (Exception $e) {
            error_log("Exception in decodeJWT: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lấy thông tin người dùng từ token đã giải mã
     * @return array|null Thông tin người dùng hoặc null
     */
    public function getUserInfo() {
        return $this->decodedToken;
    }
    
    /**
     * Lấy vai trò của người dùng từ token
     * @return string|null Vai trò người dùng hoặc null
     */
    public function getUserRole() {
        if (!$this->decodedToken) {
            return null;
        }
        
        // Kiểm tra cả "role" và "Role" (nhiều API trả về khác nhau)
        $role = $this->decodedToken['role'] ?? $this->decodedToken['Role'] ?? null;
        
        // Luôn chuẩn hóa vai trò sang chữ thường để dễ so sánh
        return $role ? strtolower($role) : null;
    }
    
    /**
     * Kiểm tra xem token có hết hạn không
     * @return bool True nếu token đã hết hạn
     */
    public function isTokenExpired() {
        if (!$this->decodedToken || !isset($this->decodedToken['exp'])) {
            error_log("[TOKEN] No token or missing expiration");
            return true;
        }
        
        $expTime = $this->decodedToken['exp'];
        $currentTime = time();
        $timeLeft = $expTime - $currentTime;
        
        $isExpired = $currentTime >= $expTime;
        error_log("[TOKEN] Token expires at: " . date('Y-m-d H:i:s', $expTime) . 
                ", current time: " . date('Y-m-d H:i:s', $currentTime) . 
                ", time left: " . $timeLeft . "s, expired: " . ($isExpired ? "YES" : "NO"));
        
        return $isExpired;
    }
    
    /**
     * Gửi request API
     */
    private function sendRequest($method, $endpoint, $data = null, $useAuth = true) {
        $url = $this->baseUrl . $endpoint;
        error_log("Sending $method request to: $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Tận dụng cookie từ backend - thêm 2 options quan trọng
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');  // Cho phép nhận cookie từ server
        curl_setopt($ch, CURLOPT_COOKIEJAR, '');   // Lưu cookie server gửi đến
        curl_setopt($ch, CURLOPT_HEADER, true);    // Lấy cả header response
        
        $headers = ['Content-Type: application/json'];
        
        // Nếu có access token thì thêm authorization header
        if ($useAuth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
        // Thêm data nếu cần
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            error_log("Request data: " . $jsonData);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Thực hiện request
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        error_log("Response HTTP code: $httpCode");
        
        // Tách header và body
        $responseHeader = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        // Xử lý Set-Cookie header từ response - thêm log chi tiết hơn
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $responseHeader, $matches);
        
        if (!empty($matches[1])) {
            // Log chi tiết các cookie được backend đặt
            foreach ($matches[1] as $cookie) {
                $cookieParts = explode('=', $cookie, 2);
                if (count($cookieParts) == 2) {
                    $cookieName = $cookieParts[0];
                    error_log("[API] Backend is setting cookie: $cookieName");
                }
            }
            error_log("[API] Backend cookies will be available in next request");
        } else {
            error_log("[API] Backend did not send any Set-Cookie headers");
            
            // Có thể dùng heuristic để xem token có trong response không
            if ((isset($responseData['jwtToken']) || isset($responseData['accessToken'])) && $endpoint === '/account/login') {
                error_log("[API] Token found in response but no Set-Cookie - PHP will handle cookies in next request");
            }
        }
        
        // Kiểm tra lỗi
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            error_log("CURL Error: $error");
            curl_close($ch);
            throw new Exception('Lỗi kết nối API: ' . $error);
        }
        
        curl_close($ch);
        
        // Phân tích body
        if (empty($responseBody)) {
            error_log("Empty response from API");
            throw new Exception('API trả về dữ liệu trống');
        }
        
        // Kiểm tra nếu response bắt đầu bằng "<" - có thể là HTML
        if (substr(trim($responseBody), 0, 1) === '<') {
            error_log("API returned HTML instead of JSON");
            throw new Exception('API trả về dữ liệu không hợp lệ (HTML thay vì JSON)');
        }
        
        $responseData = json_decode($responseBody, true);
        
        // Kiểm tra xem có parse được JSON không
        if (strpos($responseBody, 'Invalid username') !== false || 
            strpos($responseBody, 'password') !== false) {
            throw new Exception('Tên đăng nhập hoặc mật khẩu không chính xác');
        }
        
        return $responseData;
    }

    public function mockLogin($username, $password) {
        error_log("[MOCK AUTH] Using mock login for user: $username");

        // Mock doctor credentials - only use this for development
        $mockUsers = [
            'doctor1' => [
                'password' => 'doctor123',
                'name' => 'Dr. John Smith',
                'role' => 'doctor',
                'sub' => 'doc_12345'
            ]
        ];

        // Check if user exists and password matches
        if (!isset($mockUsers[$username]) || $mockUsers[$username]['password'] !== $password) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        // Create mock JWT token
        $userInfo = $mockUsers[$username];
        $now = time();
        $payload = [
            'sub' => $userInfo['sub'],
            'name' => $userInfo['name'],
            'role' => $userInfo['role'],
            'iat' => $now,
            'exp' => $now + 3600 // Token valid for 1 hour
        ];

        // Create simple base64 encoded token (not secure, just for development)
        $header = base64_encode(json_encode(['alg' => 'mock', 'typ' => 'JWT']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = base64_encode('mocksignature');
        $mockToken = "$header.$payloadEncoded.$signature";

        // Set tokens
        $this->accessToken = $mockToken;
        $this->refreshToken = 'mock_refresh_token';
        $this->decodedToken = $payload;

        // Store in session
        $_SESSION['access_token'] = $mockToken;
        $_SESSION['refresh_token'] = 'mock_refresh_token';
        $_SESSION['is_authenticated'] = true;
        $_SESSION['user_info'] = $payload;
        $_SESSION['role'] = $userInfo['role'];
        $_SESSION['username'] = $userInfo['name'];

        return [
            'success' => true,
            'accessToken' => $mockToken,
            'refreshToken' => 'mock_refresh_token',
            'user' => [
                'username' => $username,
                'name' => $userInfo['name'],
                'role' => $userInfo['role']
            ]
        ];
    }

    
}