<?php
class Appointment extends Controller
{
    private $appointmentService;
    private $patientService;
    private $doctorService;
    
    function __construct()
    {
        // Kiểm tra quyền truy cập
        if (!isset($_SESSION['is_authenticated']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
            error_log("Access denied. Role: " . ($_SESSION['role'] ?? 'none'));
            error_log("Is authenticated: " . (isset($_SESSION['is_authenticated']) ? 'yes' : 'no'));
            header('Location: /UDPT-QLBN/Auth/login');
            exit;
        }
        
        // Khởi tạo services
        require_once "./mvc/services/AppointmentService.php";
        $this->appointmentService = new AppointmentService();
        
        require_once "./mvc/services/PatientService.php";
        $this->patientService = new PatientService();
        
        // Nếu có DoctorService, cần import
        if (file_exists("./mvc/services/DoctorService.php")) {
            require_once "./mvc/services/DoctorService.php";
            $this->doctorService = new DoctorService();
        }
    }

    function index()
    {
        $this->appointmentManagement();
    }

    function appointmentManagement()
    {
        $this->view("pages/staff/AppointmentManagement", [
            "title" => "Quản lý lịch khám",
            "pageTitle" => "Quản lý lịch khám"
        ]);
    }

    // API endpoints
    function api_getAllAppointments()
    {
        header('Content-Type: application/json');
        
        error_log("=== DEBUG api_getAllAppointments START ===");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("GET parameters: " . print_r($_GET, true));
        
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            
            // ✅ THÊM: Xử lý filters từ query parameters
            $filters = [];
            
            // Lấy status filter
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            // Lấy date filters và chuyển đổi format nếu cần
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                $filters['start_date'] = $this->formatDateForAPI($_GET['start_date']);
            }
            
            if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                $filters['end_date'] = $this->formatDateForAPI($_GET['end_date']);
            }
            
            error_log("Processed filters: " . json_encode($filters));
            
            // ✅ SỬA: Gọi method với filters
            if (!empty($filters)) {
                $result = $this->appointmentService->getAllAppointmentsWithFilters($page, $limit, $filters);
            } else {
                $result = $this->appointmentService->getAllAppointments($page, $limit);
            }
            
            error_log("Raw result type: " . gettype($result));
            error_log("Raw result: " . print_r($result, true));
                
            // Kiểm tra và format response
            if (is_array($result) && !empty($result)) {
                if (isset($result['data']) && is_array($result['data'])) {
                    error_log("Result is structured format with " . count($result['data']) . " items");
                    
                    foreach ($result['data'] as $index => &$appointment) {
                        error_log("Enriching appointment $index");
                        $this->appointmentService->enrichAppointmentData($appointment);
                    }
                    
                    $response = [
                        'success' => true,
                        'data' => $result['data'],
                        'pagination' => [
                            'page' => (int)($result['page'] ?? $page),
                            'limit' => (int)($result['limit'] ?? $limit),
                            'total' => (int)($result['total'] ?? count($result['data'])),
                            'total_pages' => (int)($result['total_pages'] ?? 1)
                        ],
                        'filters' => $filters // ✅ THÊM: Trả về filters để frontend biết
                    ];
                } else {
                    error_log("Result is legacy format with " . count($result) . " items");
                    
                    foreach ($result as $index => &$appointment) {
                        error_log("Enriching legacy appointment $index");
                        $this->appointmentService->enrichAppointmentData($appointment);
                    }
                    
                    $response = [
                        'success' => true,
                        'data' => $result,
                        'pagination' => [
                            'page' => (int)$page,
                            'limit' => (int)$limit,
                            'total' => count($result),
                            'total_pages' => (int)ceil(count($result) / $limit)
                        ],
                        'filters' => $filters
                    ];
                }
            } else {
                error_log("Result is empty or invalid");
                $response = [
                    'success' => false,
                    'data' => [],
                    'message' => 'No data available or service error',
                    'filters' => $filters
                ];
            }
            
            error_log("Final response keys: " . implode(', ', array_keys($response)));
            
            echo json_encode($response);
            error_log("=== DEBUG api_getAllAppointments SUCCESS ===");
            
        } catch (Exception $e) {
            error_log("=== DEBUG api_getAllAppointments ERROR ===");
            error_log("Exception message: " . $e->getMessage());
            
            $errorResponse = [
                'success' => false, 
                'data' => [],
                'message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode($errorResponse);
        }
    }

    
    // Hàm bổ sung thông tin chi tiết
    private function enrichAppointmentData(&$appointment) {
        $this->appointmentService->enrichAppointmentData($appointment);
    }

    function api_getAppointment($id)
    {
        header('Content-Type: application/json');
        
        error_log("=== API getAppointment START ===");
        error_log("Appointment ID: " . $id);
        
        try {
            $appointment = $this->appointmentService->getAppointmentById($id);
            error_log("Service result: " . json_encode($appointment));
            
            if ($appointment) {
                $response = [
                    'success' => true,
                    'data' => $appointment
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Không tìm thấy lịch khám với ID: ' . $id
                ];
            }
            
            error_log("Final response: " . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode($errorResponse);
        }
    }

    // Các API khác giữ nguyên...
    function api_createAppointment()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $result = $this->appointmentService->createAppointment($data);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_updateAppointment($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $result = $this->appointmentService->updateAppointment($id, $data);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_getAvailableDoctors()
    {
        header('Content-Type: application/json');
        
        error_log("=== API getAvailableDoctors START ===");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("Raw request data: " . file_get_contents('php://input'));
        
        try {
            // Lấy data từ POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $requestBody = file_get_contents('php://input');
                error_log("Raw POST data: " . $requestBody);
                $data = json_decode($requestBody, true);
            } else {
                $data = [
                    'doctor_department' => $_GET['doctor_department'] ?? '',
                    'date' => $_GET['date'] ?? date('Y-m-d'),
                    'started_time' => $_GET['started_time'] ?? ''
                ];
            }
            
            error_log("Parsed request parameters: " . json_encode($data));
            
            if (empty($data['doctor_department'])) {
                throw new Exception('Vui lòng chọn khoa');
            }
            
            if (empty($data['date']) && empty($data['appointment_date'])) {
                throw new Exception('Vui lòng chọn ngày khám');
            }
            
            if (empty($data['started_time'])) {
                throw new Exception('Vui lòng chọn giờ khám');
            }
            
            // Chuyển đổi giữa 'date' và 'appointment_date' để đảm bảo tương thích
            if (!isset($data['appointment_date']) && isset($data['date'])) {
                $data['appointment_date'] = $data['date'];
            }
            
            $result = $this->appointmentService->getAvailableDoctors($data);
            error_log("Service result: " . json_encode($result));
            
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_searchPatients()
    {
        header('Content-Type: application/json');
        try {
            $searchTerm = $_GET['q'] ?? '';
            $patients = $this->patientService->searchPatients($searchTerm);
            echo json_encode(['success' => true, 'data' => $patients]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_deleteAppointment($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $result = $this->appointmentService->deleteAppointment($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_getPatientAppointments($identity_card)
    {
        header('Content-Type: application/json');
        try {
            $appointments = $this->appointmentService->getAppointmentsByPatientIdentity($identity_card);
            echo json_encode(['success' => true, 'data' => $appointments]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    function api_getAppointmentsByDoctor($doctor_id)
    {
        header('Content-Type: application/json');
        
        // Debug: Log request parameters
        error_log("=== DEBUG api_getAppointmentsByDoctor START ===");
        error_log("Doctor ID: " . $doctor_id);
        error_log("GET parameters: " . print_r($_GET, true));
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Xây dựng bộ lọc từ query parameters - với tên khớp với backend
            $filters = [
                'status' => $_GET['status'] ?? '',
                'start_date' => $_GET['started_date'] ?? '',
                'end_date' => $_GET['ended_date'] ?? ''
            ];
            
            error_log("Calling appointmentService->getAppointmentsByDoctorId with filters: " . json_encode($filters));
            
            $result = $this->appointmentService->getAppointmentsByDoctorId($doctor_id, $page, $limit, $filters);
            
            $response = [
                'success' => true,
                'data' => $result['data'] ?? [],
                'pagination' => [
                    'page' => (int)($result['page'] ?? $page),
                    'limit' => (int)($result['limit'] ?? $limit),
                    'total' => (int)($result['total'] ?? count($result['data'] ?? [])),
                    'total_pages' => (int)($result['total_pages'] ?? 1)
                ]
            ];
            
            error_log("Final response: " . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("=== DEBUG api_getAppointmentsByDoctor ERROR ===");
            error_log("Exception message: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode($errorResponse);
        }
    }

}