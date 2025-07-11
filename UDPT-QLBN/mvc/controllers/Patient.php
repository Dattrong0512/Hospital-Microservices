<?php
class Patient extends Controller {
    private $patientService;
    
    function __construct() {
        // Check authentication
        if (!isset($_SESSION['is_authenticated']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
            header('Location: /UDPT-QLBN/Auth/login');
            exit;
        }
        
        // Khởi tạo service
        require_once "./mvc/services/PatientService.php";
        $this->patientService = new PatientService();
    }
    
    // Thêm method index sẽ được gọi làm mặc định
    function index() {
        // Redirect to patients method
        $this->patients();
    }
    
    function patients() {
        try {
            // Gọi API để lấy danh sách bệnh nhân
            $patients = $this->patientService->getAllPatients();
            
            $this->view("pages/staff/PatientManagement", [
                "title" => "Quản lý Bệnh nhân",
                "pageTitle" => "Quản lý Bệnh nhân",
                "patients" => $patients['data'] ?? []
            ]);
        } catch (Exception $e) {
            // Xử lý lỗi
            $this->view("pages/staff/PatientManagement", [
                "title" => "Quản lý Bệnh nhân",
                "pageTitle" => "Quản lý Bệnh nhân",
                "error" => $e->getMessage(),
                "patients" => []
            ]);
        }
    }
    
    // API endpoints để xử lý các request AJAX từ client
    
    function api_getPatients() {
        header('Content-Type: application/json');
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $patients = $this->patientService->getAllPatients($page, $limit);
            echo json_encode([
                'success' => true,
                'data' => $patients['data'],
                'pagination' => [
                    'page' => $patients['page'],
                    'limit' => $patients['limit'],
                    'total' => $patients['total'],
                    'total_pages' => $patients['total_pages']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    function api_getPatient($idOrIdentityCard) {
        header('Content-Type: application/json');
        try {
            error_log("API: Getting patient details for ID/CCCD: $idOrIdentityCard");
            
            // Determine if this is an ID (numeric) or identity_card (alphanumeric)
            $isNumericId = is_numeric($idOrIdentityCard) && strlen($idOrIdentityCard) <= 5; // IDs tend to be shorter
            
            // Get patient data
            if ($isNumericId) {
                error_log("Looking up by numeric ID");
                $patient = $this->patientService->getPatientById($idOrIdentityCard);
            } else {
                error_log("Looking up by identity_card");
                try {
                    $patient = $this->patientService->getPatientByIdentityCard($idOrIdentityCard);
                } catch (Exception $e) {
                    // Fall back to treating it as an ID if identity_card lookup fails
                    error_log("Identity card lookup failed, trying as ID");
                    $patient = $this->patientService->getPatientById($idOrIdentityCard);
                }
            }
            
            error_log("Patient data received: " . json_encode([
                'type' => gettype($patient),
                'has_data' => isset($patient['id']) ? 'yes' : 'no'
            ]));
            
            if ($patient) {
                echo json_encode(['success' => true, 'data' => $patient]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bệnh nhân']);
            }
        } catch (Exception $e) {
            error_log("Error in api_getPatient: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_getPatientByIdentityCard($identityCard) {
        header('Content-Type: application/json');
        try {
            error_log("API: Getting patient by identity card: $identityCard");
            $patient = $this->patientService->getPatientByIdentityCard($identityCard);
            
            if ($patient) {
                echo json_encode(['success' => true, 'data' => $patient]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bệnh nhân với CMND/CCCD này']);
            }
        } catch (Exception $e) {
            error_log("Error in api_getPatientByIdentityCard: " . $e->getMessage());
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    function api_addPatient() {
        header('Content-Type: application/json');
    
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Kiểm tra tính hợp lệ của dữ liệu
            if (!isset($data['fullname']) || empty(trim($data['fullname']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tên bệnh nhân không được để trống']);
                return;
            }
            
            if (!isset($data['identity_card']) || empty(trim($data['identity_card']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'CMND/CCCD không được để trống']);
                return;
            }
            
            // Kiểm tra định dạng CMND/CCCD
            $identityCard = $data['identity_card'];
            if (!preg_match('/^(\d{9}|\d{12})$/', $identityCard)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'CMND/CCCD không hợp lệ (phải có 9 hoặc 12 chữ số)']);
                return;
            }
            
            // Kiểm tra CMND/CCCD đã tồn tại chưa
            error_log("Checking if identity card exists: $identityCard");
            $exists = $this->patientService->checkIdentityCardExists($identityCard);
            error_log("Identity card exists check result: " . ($exists ? "true" : "false"));
            
            if ($exists) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'CMND/CCCD này đã tồn tại trong hệ thống']);
                return;
            }
            
            $result = $this->patientService->addPatient($data);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            error_log("Error in api_addPatient: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    function api_updatePatient($identityCard) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            error_log("API: Updating patient with identity_card: $identityCard");
            error_log("Update data: " . json_encode($data));
            
            // Get the patient first to verify they exist
            $patient = $this->patientService->getPatientByIdentityCard($identityCard);
            
            if (!$patient) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy bệnh nhân']);
                return;
            }
            
            // Only allow updating specific fields
            $allowedFields = ['email', 'phone_number', 'address', 'medical_history'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            $result = $this->patientService->updatePatient($identityCard, $updateData);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            error_log("Error in api_updatePatient: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_checkIdentityCard($identityCard) {
        header('Content-Type: application/json');
        
        try {
            $exists = $this->patientService->checkIdentityCardExists($identityCard);
            echo json_encode(['success' => true, 'exists' => $exists]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}