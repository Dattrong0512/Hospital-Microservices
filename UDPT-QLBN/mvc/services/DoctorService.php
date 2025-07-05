<?php
class DoctorService {
    private $baseUrl;
    private $useMockData = false; // Tắt hoàn toàn mock data

    public function __construct() {
        $this->baseUrl = "http://localhost:5003/api/v0";
    }
    
    /**
     * Lấy danh sách tất cả bác sĩ
     */
    public function getAllDoctors() {
        return $this->sendRequest('GET', '/doctors/');
    }
    
    /**
     * Lấy thông tin bác sĩ theo ID
     */
    public function getDoctorById($doctorId) {
        error_log("DEBUG DoctorService: Getting doctor by ID: " . $doctorId);
        
        $data = ['doctor_id' => $doctorId];
        return $this->sendRequest('POST', '/doctors/byID', $data);
    }
    
    /**
     * Lấy thông tin bác sĩ theo CMND/CCCD
     */
    public function getDoctorByIdentityCard($identityCard) {
        return $this->sendRequest('GET', '/doctors/' . $identityCard);
    }
    
    /**
     * Lấy danh sách bác sĩ theo khoa
     */
    public function getDoctorsByDepartment($department, $page = 1, $limit = 10) {
        $data = ['department' => $department];
        $url = "/doctors/byDepartment?page={$page}&limit={$limit}";
        return $this->sendRequest('POST', $url, $data);
    }
    
    /**
     * Lấy danh sách bác sĩ có sẵn (dùng cho appointment)
     */
    public function getAvailableDoctors($department, $excludeIds = null, $page = 1, $limit = 10) {
        $data = [
            'doctor_department' => $department,
            'exclude_ids' => $excludeIds
        ];
        
        $url = "/doctors/available?page={$page}&limit={$limit}";
        return $this->sendRequest('POST', $url, $data);
    }
    
    /**
     * Tạo bác sĩ mới
     */
    public function createDoctor($doctorData) {
        return $this->sendRequest('POST', '/doctors/', $doctorData);
    }
    
    /**
     * Cập nhật thông tin bác sĩ
     */
    public function updateDoctor($identityCard, $doctorData) {
        return $this->sendRequest('PUT', '/doctors/' . $identityCard, $doctorData);
    }
    
    /**
     * Hàm chung để gửi request đến API
     */
    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        error_log("Doctor API Request: $method $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        
        if ($data) {
            $jsonData = json_encode($data);
            error_log("Doctor API Request Payload: $jsonData");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        error_log("Doctor API Response Code: $httpCode");
        error_log("Doctor API Final URL: $finalUrl");
        error_log("Doctor API Response Length: " . strlen($response));
        error_log("Doctor API Response Preview: " . substr($response, 0, 300));
        
        curl_close($ch);
        
        if ($curlErrno !== 0) {
            error_log("Doctor API CURL Error: " . $curlError);
            throw new Exception("Không thể kết nối đến Doctor API server: " . $curlError);
        }
        
        if ($httpCode >= 400) {
            error_log("Doctor API Error Response: $response");
            throw new Exception("Doctor API Error: HTTP $httpCode");
        }
        
        if (empty($response)) {
            throw new Exception("Doctor API trả về response rỗng");
        }
        
        // Kiểm tra nếu response là HTML redirect
        if (stripos($response, '<!doctype html>') === 0 || stripos($response, '<html') !== false) {
            error_log("Doctor API returned HTML redirect instead of JSON");
            throw new Exception("Doctor API trả về trang redirect thay vì JSON data");
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Doctor API JSON Parse Error: " . json_last_error_msg());
            error_log("Raw response: " . $response);
            throw new Exception('Doctor API JSON Parse Error: ' . json_last_error_msg());
        }
        
        error_log("Doctor API Success: " . json_encode($responseData));
        return $responseData;
    }
}