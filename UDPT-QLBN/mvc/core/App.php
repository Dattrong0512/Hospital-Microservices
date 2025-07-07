<?php
class App
{
    protected $controller = "Auth";
    protected $action = "login";
    protected $params = [];
    
    // Public routes that don't require authentication
    protected $publicRoutes = [
        'Auth' => ['login', 'authenticate', 'register', 'forgotPassword', 'resetPassword']
    ];

    // Routes with role-based access
    protected $roleRoutes = [
        'staff' => ['Staff','Appointment', 'Patient', 'Medicine','Prescription'],
        'doctor' => ['Doctor', 'Patient', 'Appointment', 'Prescription', 'Medicine']
    ];

    function __construct()
    {
        $urlData = $this->UrlProcess();

        // Lấy các phần của URL (controller, action) và query params
        $urlParts = $urlData['urlParts'] ?? [];
        $queryParams = $urlData['queryParams'] ?? [];

        // Xử lý controller
        $controllerName = $this->controller; // Lưu tên controller dạng string
        if (!empty($urlParts) && file_exists("./mvc/controllers/" . $urlParts[0] . ".php")) {
            $controllerName = $urlParts[0];
            unset($urlParts[0]);
        }
        
        // Require file controller
        require_once "./mvc/controllers/" . $controllerName . ".php";
        
        // Check authentication before proceeding
        $requireAuth = true;
        
        // Check if route is public
        if (isset($this->publicRoutes[$controllerName])) {
            $action = isset($urlParts[1]) ? $urlParts[1] : $this->action;
            if (in_array($action, $this->publicRoutes[$controllerName])) {
                $requireAuth = false;
            }
        }
        
        // If authentication required but user not authenticated, redirect to login
        if ($requireAuth && !isset($_SESSION['is_authenticated'])) {
            // Store original URL for redirection after login
            if ($controllerName != 'Auth') {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            
            // Redirect to login
            $controllerName = 'Auth';
            $this->action = 'login';
            $urlParts = [];
            
            // Make sure to include Auth controller if we've switched
            if (!class_exists('Auth')) {
                require_once "./mvc/controllers/Auth.php";
            }
        }
        // If authenticated, check role-based access
        else if ($requireAuth && isset($_SESSION['role'])) {
            $hasAccess = false;
            $userRole = $_SESSION['role'];
            
            // Check if user's role has access to this controller
            if (isset($this->roleRoutes[$userRole]) && in_array($controllerName, $this->roleRoutes[$userRole])) {
                $hasAccess = true;
            }
            
            // If no access and not in Auth controller, redirect to appropriate dashboard
            if (!$hasAccess && $controllerName != 'Auth') {
                switch($userRole) {
                    case 'user':
                        header("Location: /UDPT-QLBN/User/dashboard");
                        exit;
                    case 'staff':
                        header("Location: /UDPT-QLBN/Staff/dashboard");
                        exit;
                    case 'doctor':
                        header("Location: /UDPT-QLBN/Doctor/dashboard");
                        exit;
                    default:
                        header("Location: /UDPT-QLBN/Auth/login");
                        exit;
                }
            }
        }
        
        // Khởi tạo controller
        $this->controller = new $controllerName();

        // Xử lý action
        if (isset($urlParts[1])) {
            if (method_exists($this->controller, $urlParts[1])) {
                $this->action = $urlParts[1];
            }
            unset($urlParts[1]);
        }

        $this->params = array_values($urlParts ?: []);
        
        call_user_func_array([$this->controller, $this->action], $this->params);
    }

    // Xử lý URL và trả về các phần của URL và query params
    function UrlProcess()
    {
        if(isset($_GET["url"])) {
            // Xử lý URL
            $url = rtrim($_GET["url"], "/");
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $urlParts = explode("/", $url);
            
            // Lấy query params từ $_GET ngoại trừ 'url'
            $queryParams = $_GET;
            unset($queryParams['url']);
            
            return [
                'urlParts' => $urlParts,
                'queryParams' => $queryParams
            ];
        }
        
        return [
            'urlParts' => [],
            'queryParams' => $_GET
        ];
    }
}