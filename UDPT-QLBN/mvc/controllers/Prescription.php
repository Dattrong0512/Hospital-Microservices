<?php
// filepath: j:\xampp\htdocs\UDPT-QLBN\mvc\controllers\Prescription.php
class Prescription extends Controller
{
    private $prescriptionService;
    
    function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }   
        // Kiểm tra quyền truy cập - CHO PHÉP STAFF VÀ DOCTOR
        if (!isset($_SESSION['is_authenticated']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
            error_log("Prescription access denied. Role: " . ($_SESSION['role'] ?? 'none'));
            header('Location: /UDPT-QLBN/Auth/login');
            exit;
        }
        
        error_log("Prescription controller accessed by role: " . $_SESSION['role']);
        
        // Khởi tạo services
        require_once "./mvc/services/PrescriptionService.php";
        $this->prescriptionService = new PrescriptionService();
    }

    // VIEW METHODS
    function index()
    {
        $this->view("pages/doctor/PrescriptionManagement", [
            "title" => "Quản lý đơn thuốc",
            "flatpickr_loaded" => true
        ]);
    }

    function viewPrescription($appointmentId = null)
    {
        if (!$appointmentId) {
            header('Location: /UDPT-QLBN/Prescription');
            exit;
        }

        $this->view("pages/doctor/ViewPrescription", [
            "title" => "Chi tiết đơn thuốc",
            "appointment_id" => $appointmentId,
            "flatpickr_loaded" => true
        ]);
    }

    // API METHODS
    
    /**
     * API: Lấy tất cả đơn thuốc
     */
    function api_getAllPrescriptions()
    {
        header('Content-Type: application/json');
        
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            error_log("Getting all prescriptions - page: $page, limit: $limit");
            
            $prescriptions = $this->prescriptionService->getAllPrescriptions($page, $limit);
            echo json_encode($prescriptions);
            
        } catch (Exception $e) {
            error_log("Error in api_getAllPrescriptions: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Lấy đơn thuốc theo ID
     */
    function api_getPrescription($prescriptionId)
    {
        header('Content-Type: application/json');
        
        try {
            error_log("Getting prescription with ID: $prescriptionId");
            
            $prescription = $this->prescriptionService->getPrescriptionById($prescriptionId);
            
            if ($prescription) {
                echo json_encode(['success' => true, 'data' => $prescription]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn thuốc']);
            }
            
        } catch (Exception $e) {
            error_log("Error in api_getPrescription: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Tạo đơn thuốc mới
     */
    function api_createPrescription()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }
            
            // Validate required fields
            $requiredFields = ['appointment_id', 'no_days'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
                    return;
                }
            }
            
            error_log("Creating prescription with data: " . json_encode($input));
            
            $result = $this->prescriptionService->createPrescription($input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("Error in api_createPrescription: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Cập nhật đơn thuốc
     */
    function api_updatePrescription($prescriptionId)
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }
            
            error_log("Updating prescription $prescriptionId with data: " . json_encode($input));
            
            $result = $this->prescriptionService->updatePrescription($prescriptionId, $input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("Error in api_updatePrescription: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Lấy chi tiết đơn thuốc
     */
    function api_getPrescriptionDetailsByAppointment($appointmentId)
    {
        header('Content-Type: application/json');
        
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            error_log("💊 [GET_DETAILS] Getting prescription details for appointment ID: $appointmentId");
            
            // ✅ SỬA: Gọi method đúng với appointment ID
            $details = $this->prescriptionService->getPrescriptionDetailsWithMedicines($appointmentId, $page, $limit);
            
            echo json_encode(['success' => true, 'data' => $details]);
            
        } catch (Exception $e) {
            error_log("❌ [GET_DETAILS] Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Thêm thuốc vào đơn thuốc
     */
    function api_addPrescriptionDetail()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }
            
            // ✅ ENHANCED validation
            $requiredFields = ['prescription_id', 'medicine_id', 'amount'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
                    return;
                }
            }
            
            // ✅ VALIDATE data types
            if (!is_numeric($input['prescription_id']) || $input['prescription_id'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'prescription_id không hợp lệ']);
                return;
            }
            
            if (!is_numeric($input['medicine_id']) || $input['medicine_id'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'medicine_id không hợp lệ']);
                return;
            }
            
            if (!is_numeric($input['amount']) || $input['amount'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'amount phải là số dương']);
                return;
            }
            
            error_log("💊 [ADD_DETAIL] Adding prescription detail: " . json_encode($input));
            
            $result = $this->prescriptionService->addPrescriptionDetail($input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("❌ [ADD_DETAIL] Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Cập nhật chi tiết thuốc trong đơn
     */
    function api_updatePrescriptionDetail()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }
            
            // Validate required fields
            $requiredFields = ['prescription_id', 'medicine_id'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
                    return;
                }
            }
            
            error_log("Updating prescription detail: " . json_encode($input));
            
            $result = $this->prescriptionService->updatePrescriptionDetail($input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("Error in api_updatePrescriptionDetail: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Xóa thuốc khỏi đơn thuốc
     */
    function api_deletePrescriptionDetail()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['prescription_id']) || !isset($input['medicine_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Thiếu prescription_id hoặc medicine_id']);
                return;
            }
            
            $prescriptionId = $input['prescription_id'];
            $medicineId = $input['medicine_id'];
            
            error_log("Deleting prescription detail: prescription_id=$prescriptionId, medicine_id=$medicineId");
            
            $result = $this->prescriptionService->deletePrescriptionDetail($prescriptionId, $medicineId);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            error_log("Error in api_deletePrescriptionDetail: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Lấy đơn thuốc theo appointment ID
     */
    function api_getPrescriptionByAppointment($appointmentId)
    {
        header('Content-Type: application/json');
        
        try {
            error_log("Getting prescription for appointment: $appointmentId");
            
            $prescription = $this->prescriptionService->getPrescriptionByAppointmentId($appointmentId);
            
            if ($prescription) {
                echo json_encode(['success' => true, 'data' => $prescription]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Chưa có đơn thuốc cho cuộc hẹn này']);
            }
            
        } catch (Exception $e) {
            error_log("Error in api_getPrescriptionByAppointment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}