<?php
class Medicine extends Controller
{
    private $medicineService;
    
    function __construct()
    {
        // Kiểm tra quyền truy cập
        if (!isset($_SESSION['is_authenticated']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
            header('Location: /UDPT-QLBN/Auth/login');
            exit;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Khởi tạo service
        require_once "./mvc/services/MedicineService.php";
        $this->medicineService = new MedicineService();
    }

    function index()
    {
        $this->medicineManagement();
    }

    function medicineManagement()
    {
        try {
            error_log("Accessing medicine management");
            $this->view("pages/staff/MedicineManagement", [
                "title" => "Quản lý thuốc",
                "pageTitle" => "Quản lý thuốc"
            ]);
        } catch (Exception $e) {
            error_log("Error in medicine management: " . $e->getMessage());
            throw $e;
        }
    }

    // API proxies - chuyển tiếp request tới service backend
    function api_getAllMedicines()
    {
        header('Content-Type: application/json');
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            $medicines = $this->medicineService->getAllMedicines($page, $limit);
            echo json_encode($medicines);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    function api_getMedicine($id)
    {
        header('Content-Type: application/json');
        try {
            $medicine = $this->medicineService->getMedicineById($id);
            
            // ✅ Đảm bảo response có format chuẩn
            if ($medicine) {
                echo json_encode([
                    'success' => true,
                    'data' => $medicine,
                    'message' => 'Medicine retrieved successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Medicine not found',
                    'data' => null
                ]);
            }
        } catch (Exception $e) {
            error_log("Error in api_getMedicine: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    function api_getMedicineById($id)
    {
        return $this->api_getMedicine($id);
    }
    
    function api_searchMedicines($searchTerm = null)
    {
        header('Content-Type: application/json');
        
        $startTime = microtime(true);
        
        error_log("🔍 [SEARCH_API] ==================== START ====================");
        error_log("🔍 [SEARCH_API] Timestamp: " . date('Y-m-d H:i:s'));
        error_log("🔍 [SEARCH_API] Called with term: " . ($searchTerm ?? 'null'));
        error_log("🔍 [SEARCH_API] GET params: " . json_encode($_GET));
        error_log("🔍 [SEARCH_API] POST params: " . json_encode($_POST));
        error_log("🔍 [SEARCH_API] REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        error_log("🔍 [SEARCH_API] QUERY_STRING: " . $_SERVER['QUERY_STRING']);
        
        try {
            // ✅ IMPROVED: Get search term từ nhiều nguồn
            $finalSearchTerm = '';
            
            if ($searchTerm) {
                $finalSearchTerm = $searchTerm;
                error_log("🔍 [SEARCH_API] Using URL parameter: '{$finalSearchTerm}'");
            } elseif (isset($_GET['query']) && !empty($_GET['query'])) {
                $finalSearchTerm = $_GET['query'];
                error_log("🔍 [SEARCH_API] Using GET query: '{$finalSearchTerm}'");
            } else {
                error_log("🔍 [SEARCH_API] No search term provided");
                echo json_encode([
                    'data' => [],
                    'page' => 1,
                    'limit' => 10,
                    'total' => 0,
                    'total_pages' => 0,
                    'message' => 'No search term provided'
                ]);
                return;
            }
            
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            error_log("🔍 [SEARCH_API] Final params: term='{$finalSearchTerm}', page={$page}, limit={$limit}");
            
            // ✅ CALL SERVICE với debug
            error_log("🔍 [SEARCH_API] Calling MedicineService...");
            $serviceStartTime = microtime(true);
            
            $medicines = $this->medicineService->searchMedicines($finalSearchTerm, $page, $limit);
            
            $serviceEndTime = microtime(true);
            $serviceTime = ($serviceEndTime - $serviceStartTime) * 1000;
            
            error_log("🔍 [SEARCH_API] Service call completed in {$serviceTime}ms");
            error_log("🔍 [SEARCH_API] Service result type: " . gettype($medicines));
            error_log("🔍 [SEARCH_API] Service result keys: " . json_encode(array_keys($medicines)));
            
            if (isset($medicines['data'])) {
                error_log("🔍 [SEARCH_API] Data count: " . count($medicines['data']));
                if (!empty($medicines['data'])) {
                    error_log("🔍 [SEARCH_API] First item: " . json_encode($medicines['data'][0]));
                }
            }
            
            // ✅ OUTPUT với timing info
            $totalTime = (microtime(true) - $startTime) * 1000;
            $medicines['debug_info'] = [
                'search_term' => $finalSearchTerm,
                'execution_time_ms' => round($totalTime, 2),
                'service_time_ms' => round($serviceTime, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("🔍 [SEARCH_API] Total execution time: {$totalTime}ms");
            error_log("🔍 [SEARCH_API] Response ready, sending JSON...");
            
            echo json_encode($medicines);
            
            error_log("🔍 [SEARCH_API] ==================== END ====================");
            
        } catch (Exception $e) {
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            error_log("❌ [SEARCH_API] EXCEPTION after {$totalTime}ms:");
            error_log("❌ [SEARCH_API] Message: " . $e->getMessage());
            error_log("❌ [SEARCH_API] File: " . $e->getFile());
            error_log("❌ [SEARCH_API] Line: " . $e->getLine());
            error_log("❌ [SEARCH_API] Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage(),
                'debug_info' => [
                    'execution_time_ms' => round($totalTime, 2),
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
            error_log("❌ [SEARCH_API] ==================== ERROR END ====================");
        }
    }

    function api_searchMedicinesByName($searchTerm)
    {
        return $this->api_searchMedicines($searchTerm);
    }
    
    function api_getNearExpiryMedicines()
    {
        header('Content-Type: application/json');
        try {
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            
            $medicines = $this->medicineService->getNearExpiryMedicines($page, $limit);
            echo json_encode($medicines);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    function api_createMedicine()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $result = $this->medicineService->createMedicine($data);
            http_response_code(201);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    function api_updateMedicine($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $result = $this->medicineService->updateMedicine($id, $data);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    function api_deleteMedicine($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        try {
            $result = $this->medicineService->deleteMedicine($id);
            echo json_encode(['message' => 'Medicine deleted successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}