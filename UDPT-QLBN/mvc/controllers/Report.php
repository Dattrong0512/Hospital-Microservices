<?php
class Report extends Controller
{
    private $reportService;
    
    function __construct()
    {
        // Kiểm tra quyền truy cập
        if (!isset($_SESSION['is_authenticated']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
            header('Location: /UDPT-QLBN/Auth/login');
            exit;
        }
        
        // Khởi tạo service
        require_once "./mvc/services/ReportService.php";
        $this->reportService = new ReportService();
    }

    function index()
    {
        $this->dashboard();
    }

    function dashboard()
    {
        $this->view("pages/staff/Dashboard", [
            "title" => "Dashboard Báo cáo",
            "pageTitle" => "Dashboard Báo cáo"
        ]);
    }

    // API endpoints for dashboard data
    function api_getMonthlyPatientStatistics($year)
    {
        header('Content-Type: application/json');
        try {
            $data = $this->reportService->getMonthlyPatientStatistics($year);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_getMonthlyPrescriptionStatistics($year, $month)
    {
        header('Content-Type: application/json');
        try {
            $data = $this->reportService->getMonthlyPrescriptionStatistics($year, $month);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function api_getTotalMedicines()
    {
        header('Content-Type: application/json');
        try {
            require_once "./mvc/services/MedicineService.php";
            $medicineService = new MedicineService();
            $medicines = $medicineService->getAllMedicines(1, 1000); // Fetch a large limit to get all medicines
            $totalMedicines = isset($medicines['total']) ? $medicines['total'] : count($medicines['data']);
            echo json_encode(['success' => true, 'total' => $totalMedicines]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
