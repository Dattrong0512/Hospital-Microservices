<?php
class PrescriptionService {
    private $baseUrl;

    public function __construct() {
        $this->baseUrl = "http://localhost:5006/api/v0";
        require_once "./mvc/services/MedicineService.php";
        $this->medicineService = new MedicineService();
        require_once "./mvc/services/PatientService.php";
        $this->patientService = new PatientService();
    }
    
    public function getAllPrescriptions($page = 1, $limit = 10) {
        $url = "/prescriptions/?page={$page}&limit={$limit}";
        return $this->sendRequest('GET', $url);
    }
    
    public function getPrescriptionById($prescriptionId) {
        error_log("Fetching prescription with ID: $prescriptionId");
        return $this->sendRequest('GET', '/prescriptions/' . $prescriptionId);
    }
    
    public function createPrescription($prescriptionData) {
        if (!isset($prescriptionData['status']) || empty($prescriptionData['status'])) {
            $prescriptionData['status'] = 'Chưa lấy';
        }
        
        error_log("💊 [CREATE_PRESCRIPTION] Creating with data: " . json_encode($prescriptionData));
        return $this->sendRequest('POST', '/prescriptions/', $prescriptionData);
    }
    
    public function updatePrescription($prescriptionId, $prescriptionData) {
        try {
            error_log("Updating prescription with ID: $prescriptionId");
            error_log("Update data: " . json_encode($prescriptionData));
            
            $result = $this->sendRequest('PUT', '/prescriptions/' . $prescriptionId, $prescriptionData);
            error_log("Update result: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("Error updating prescription: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getPrescriptionDetails($prescriptionId, $page = 1, $limit = 10) {
        $url = "/prescription-details/{$prescriptionId}?page={$page}&limit={$limit}";
        error_log("Fetching prescription details: $url");
        return $this->sendRequest('GET', $url);
    }
    
    public function addPrescriptionDetail($detailData) {
        error_log("💊 [ADD_DETAIL] Adding prescription detail: " . json_encode($detailData));
        
        // ✅ VALIDATE input
        if (!isset($detailData['prescription_id']) || !isset($detailData['medicine_id']) || !isset($detailData['amount'])) {
            throw new Exception("Missing required fields: prescription_id, medicine_id, amount");
        }
        
        // ✅ SỬA: Endpoint chính xác
        return $this->sendRequest('POST', '/prescription-details/', $detailData);
    }
    
    public function updatePrescriptionDetail($detailData) {
        error_log("Updating prescription detail: " . json_encode($detailData));
        return $this->sendRequest('PUT', '/prescription-details/', $detailData);
    }
    
    public function deletePrescriptionDetail($prescriptionId, $medicineId) {
        $data = [
            'prescription_id' => $prescriptionId,
            'medicine_id' => $medicineId
        ];
        
        error_log("Deleting prescription detail: " . json_encode($data));
        return $this->sendRequest('DELETE', '/prescription-details/', $data);
    }
    
    public function getPrescriptionByAppointmentId($appointmentId) {
        try {
            error_log("💊 [SERVICE] Getting prescription by appointment ID: $appointmentId");
            
            // ✅ SỬA: Gọi trực tiếp endpoint Python API
            $url = "/prescriptions/{$appointmentId}";  // Python route: /<int:appointment_id>
            
            $result = $this->sendRequest('GET', $url);
            
            error_log("💊 [SERVICE] Prescription found for appointment: $appointmentId");
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ [SERVICE] Error getting prescription by appointment ID: " . $e->getMessage());
            
            // ✅ SỬA: Return null thay vì throw exception khi không tìm thấy
            if (strpos($e->getMessage(), 'Prescription not found') !== false || 
                strpos($e->getMessage(), 'HTTP Error 404') !== false) {
                return null;
            }
            
            throw $e;
        }
    }

    public function getPrescriptionDetailsWithMedicines($appointmentId, $page = 1, $limit = 10) {
        try {
            error_log("💊 [SERVICE] Getting prescription details for appointment ID: $appointmentId");
            
            // STEP 1: Get prescription by appointment ID
            $prescription = $this->getPrescriptionByAppointmentId($appointmentId);
            
            if (!$prescription) {
                return [
                    'data' => [],
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'total_pages' => 0,
                    'message' => 'Không tìm thấy đơn thuốc cho cuộc hẹn này'
                ];
            }
            
            $prescriptionId = $prescription['prescription_id'];
            error_log("💊 [SERVICE] Found prescription ID: $prescriptionId for appointment: $appointmentId");
            
            // STEP 2: Get prescription details using prescription ID
            $result = $this->getPrescriptionDetails($prescriptionId, $page, $limit);
            
            // ✅ SỬA: Enhance với medicine names từ MedicineService
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as &$detail) {
                    if (isset($detail['medicine_id'])) {
                        try {
                            error_log("💊 [SERVICE] Getting medicine info for ID: {$detail['medicine_id']}");
                            
                            $medicineInfo = $this->medicineService->getMedicineById($detail['medicine_id']);
                            
                            // ✅ THÊM: Debug response structure
                            error_log("🔍 [DEBUG] Medicine API response type: " . gettype($medicineInfo));
                            error_log("🔍 [DEBUG] Medicine API response: " . json_encode($medicineInfo));
                            
                            if (is_array($medicineInfo)) {
                                error_log("🔍 [DEBUG] Medicine response keys: " . json_encode(array_keys($medicineInfo)));
                            }
                            
                            if ($medicineInfo && !isset($medicineInfo['error'])) {
                                // ✅ SỬA: Check structure và handle different response formats
                                if (isset($medicineInfo['name'])) {
                                    // Direct response format
                                    $detail['medicine_name'] = $medicineInfo['name'];
                                    $detail['medicine_unit'] = $medicineInfo['unit'] ?? 'viên';
                                    $detail['medicine_price'] = $medicineInfo['price'] ?? 0;
                                    $detail['medicine_stock'] = $medicineInfo['amount'] ?? 0;
                                } else if (isset($medicineInfo['data']) && isset($medicineInfo['data']['name'])) {
                                    // Wrapped response format
                                    $medicine = $medicineInfo['data'];
                                    $detail['medicine_name'] = $medicine['name'];
                                    $detail['medicine_unit'] = $medicine['unit'] ?? 'viên';
                                    $detail['medicine_price'] = $medicine['price'] ?? 0;
                                    $detail['medicine_stock'] = $medicine['amount'] ?? 0;
                                } else {
                                    // ✅ SỬA: Log all available keys for debugging
                                    error_log("⚠️ [DEBUG] Medicine response doesn't have expected structure");
                                    if (is_array($medicineInfo)) {
                                        error_log("Available keys: " . implode(', ', array_keys($medicineInfo)));
                                    }
                                    $detail['medicine_name'] = "Thuốc ID: " . $detail['medicine_id'];
                                    $detail['medicine_unit'] = 'viên';
                                }
                                
                                error_log("✅ [SERVICE] Enhanced medicine {$detail['medicine_id']} with name: {$detail['medicine_name']}");
                            } else {
                                $detail['medicine_name'] = "Thuốc ID: " . $detail['medicine_id'];
                                $detail['medicine_unit'] = 'viên';
                                error_log("⚠️ [SERVICE] Could not get medicine info for ID {$detail['medicine_id']}");
                            }
                        } catch (Exception $e) {
                            error_log("❌ [SERVICE] Error getting medicine info for ID {$detail['medicine_id']}: " . $e->getMessage());
                            $detail['medicine_name'] = "Thuốc ID: " . $detail['medicine_id'];
                            $detail['medicine_unit'] = 'viên';
                        }
                    } else {
                        $detail['medicine_name'] = "Chưa xác định";
                        $detail['medicine_unit'] = 'viên';
                    }
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ [SERVICE] Error getting prescription details: " . $e->getMessage());
            
            return [
                'data' => [],
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'total_pages' => 0,
                'message' => 'Không thể tải chi tiết đơn thuốc: ' . $e->getMessage()
            ];
        }
    }
// ✅ THÊM: Helper method to get medicine information
    private function getMedicineInfo($medicineId) {
        try {
            // ✅ Call Medicine API to get medicine details
            $medicineUrl = "http://localhost:5006/api/v0/medicines/{$medicineId}";
            
            $ch = curl_init($medicineUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $medicineData = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $medicineData;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("⚠️ [SERVICE] Error getting medicine info: " . $e->getMessage());
            return null;
        }
    }
    
    // ✅ SỬA: Enhanced sendRequest - loại bỏ fallback mock
    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        error_log("💊 [API_REQUEST] $method $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        
        if ($data) {
            $jsonData = json_encode($data);
            error_log("💊 [API_REQUEST] Request body: $jsonData");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        $info = curl_getinfo($ch);
        error_log("💊 [API_REQUEST] HTTP code: $httpCode");
        error_log("💊 [API_REQUEST] Total time: " . $info['total_time'] . " seconds");
        
        if ($error) {
            error_log("❌ [API_REQUEST] CURL Error: $error");
            curl_close($ch);
            throw new Exception("Không thể kết nối đến Prescription API: $error");
        }
        
        curl_close($ch);
        
        error_log("💊 [API_REQUEST] Raw response (first 300 chars): " . substr($response, 0, 300));
        
        // Check for HTML response
        if (is_string($response) && preg_match('/^\s*</', $response)) {
            error_log("❌ [API_REQUEST] HTML response received");
            throw new Exception('Prescription API trả về HTML thay vì JSON');
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ [API_REQUEST] JSON Parse Error: " . json_last_error_msg());
            error_log("Raw response: " . $response);
            throw new Exception('Lỗi parse JSON từ Prescription API: ' . json_last_error_msg());
        }
        
        // Check HTTP error codes
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']) ? $responseData['error'] : "HTTP Error $httpCode";
            error_log("❌ [API_REQUEST] HTTP Error: $errorMessage");
            throw new Exception("Prescription API Error: $errorMessage");
        }
        
        error_log("✅ [API_REQUEST] Success");
        return $responseData;
    }

    public function enhanceAppointmentWithPatientInfo($appointmentData) {
        try {
            if (!isset($appointmentData['patient_id'])) {
                error_log("⚠️ [ENHANCE] No patient_id found in appointment data");
                return $appointmentData;
            }
            
            $patientId = $appointmentData['patient_id'];
            error_log("👤 [ENHANCE] Getting patient info for ID: $patientId");
            
            // ✅ SỬA: Gọi PatientService để lấy thông tin đầy đủ
            $patientInfo = $this->patientService->getPatientById($patientId);
            
            if ($patientInfo && !isset($patientInfo['error'])) {
                // ✅ SỬA: Merge thông tin patient vào appointment kể cả identity_card
                $appointmentData['patient_gender'] = $patientInfo['gender'] ?? $appointmentData['patient_gender'] ?? '-';
                $appointmentData['patient_birthdate'] = $patientInfo['birth_date'] ?? $appointmentData['patient_birthdate'] ?? '-';
                $appointmentData['patient_medical_history'] = $patientInfo['medical_history'] ?? 'Không có';
                $appointmentData['patient_address'] = $patientInfo['address'] ?? 'Không có';
                $appointmentData['patient_email'] = $patientInfo['email'] ?? 'Không có';
                $appointmentData['patient_identity_card'] = $patientInfo['identity_card'] ?? 'Không có';
                
                error_log("✅ [ENHANCE] Enhanced appointment with patient info");
                error_log("👤 [ENHANCE] Patient gender: " . $appointmentData['patient_gender']);
                error_log("👤 [ENHANCE] Patient birthdate: " . $appointmentData['patient_birthdate']);
                error_log("👤 [ENHANCE] Patient identity_card: " . $appointmentData['patient_identity_card']);
                error_log("👤 [ENHANCE] Medical history: " . substr($appointmentData['patient_medical_history'], 0, 50) . "...");
            } else {
                error_log("⚠️ [ENHANCE] Could not get patient info for ID: $patientId");
            }
            
            return $appointmentData;
            
        } catch (Exception $e) {
            error_log("❌ [ENHANCE] Error enhancing appointment with patient info: " . $e->getMessage());
            return $appointmentData;
        }
    }

    // ✅ SỬA: Enhanced method để update patient medical history
    public function updatePatientMedicalHistory($identityCard, $medicalHistory) {
    try {
        error_log("👤 [UPDATE_HISTORY] Updating medical history for patient identity_card: $identityCard");
        
        $updateData = [
            'medical_history' => $medicalHistory
        ];
        
        // ✅ SỬA: Gọi PatientService với identity_card thay vì patient_id
        $result = $this->patientService->updatePatient($identityCard, $updateData);
        
        if ($result && !isset($result['error'])) {
            error_log("✅ [UPDATE_HISTORY] Medical history updated successfully");
            return $result;
        } else {
            throw new Exception("Failed to update medical history");
        }
        
    } catch (Exception $e) {
        error_log("❌ [UPDATE_HISTORY] Error updating medical history: " . $e->getMessage());
        throw $e;
    }
}
}
?>