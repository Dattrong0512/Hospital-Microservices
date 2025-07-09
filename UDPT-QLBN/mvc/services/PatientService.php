<?php
class PatientService {
    private $baseUrl;
    private $apiKey;
    private $useMockData = false;

    public function __construct() {
        // Đọc từ file cấu hình hoặc biến môi trường
        $this->baseUrl = "https://konggateway.hospitalmicroservices.live/api/v0";
        //$this->apiKey = $_ENV['PATIENT_SERVICE_API_KEY'] ?? "your-api-key-here";
    }
    
    /**
     * Lấy danh sách tất cả bệnh nhân
     */
    public function getAllPatients($page = 1, $limit = 10) {
        if ($this->useMockData) {
            return $this->getMockData('/patients', 'GET');
        }
        
        // Thêm tham số page và limit vào URL
        $url = "/patients/?page={$page}&limit={$limit}";
        return $this->sendRequest('GET', $url);
    }
    
    /**
     * Lấy thông tin chi tiết bệnh nhân theo ID
     */
    public function getPatientById($idOrIdentityCard) {
        if ($this->useMockData) {
            return $this->getMockData('/patients/' . $idOrIdentityCard, 'GET');
        }
        
        error_log("Fetching patient with ID or Identity card: $idOrIdentityCard");
        
        try {
            // First, try to get patient directly as identity_card
            $patient = $this->sendRequest('GET', '/patients/byId/' . $idOrIdentityCard);
            error_log("Successfully fetched patient by direct lookup");
            return $patient;
        } catch (Exception $e) {
            error_log("Direct patient lookup failed: " . $e->getMessage());
            
            // Thử lấy danh sách và tìm
            $allPatients = $this->getAllPatients();
            
            if (is_array($allPatients)) {
                // Duyệt trực tiếp nếu là mảng
                error_log("Searching in direct array of " . count($allPatients) . " patients");
                foreach ($allPatients as $p) {
                    if (isset($p['identity_card']) && $p['identity_card'] === $idOrIdentityCard) {
                        error_log("Found patient by identity_card match");
                        return $p;
                    }
                    if (isset($p['id']) && $p['id'] == $idOrIdentityCard) {
                        error_log("Found patient by id match");
                        return $p;
                    }
                }
            } elseif (isset($allPatients['data']) && is_array($allPatients['data'])) {
                // Nếu có cấu trúc data thì duyệt qua data
                error_log("Searching in data array of " . count($allPatients['data']) . " patients");
                foreach ($allPatients['data'] as $p) {
                    if (isset($p['identity_card']) && $p['identity_card'] === $idOrIdentityCard) {
                        error_log("Found patient by identity_card match in data array");
                        return $p;
                    }
                    if (isset($p['id']) && $p['id'] == $idOrIdentityCard) {
                        error_log("Found patient by id match in data array");
                        return $p;
                    }
                }
            }
            
            error_log("Patient not found with ID or identity_card: $idOrIdentityCard");
            throw new Exception("Không tìm thấy bệnh nhân với mã $idOrIdentityCard");
        }
    }
    
    public function getPatientByIdentityCard($identityCard) {
        if ($this->useMockData) {
            // For mock data, find in our mock data array
            $mockData = $this->getMockData('/patients', 'GET');
            if (isset($mockData['data']) && is_array($mockData['data'])) {
                foreach ($mockData['data'] as $patient) {
                    if (isset($patient['identity_card']) && $patient['identity_card'] === $identityCard) {
                        return $patient;
                    }
                }
            }
            throw new Exception("Không tìm thấy bệnh nhân với CMND/CCCD: $identityCard");
        }
        
        // Try to use the endpoint pattern matching identity_card
        try {
            error_log("Fetching patient with identity_card: $identityCard");
            return $this->sendRequest('GET', '/patients/byIdentity/' . $identityCard);
        } catch (Exception $e) {
            error_log("Error fetching by identity_card: " . $e->getMessage());
            
            // Fall back to getting all and filtering
            $allPatients = $this->getAllPatients();
            
            if (is_array($allPatients)) {
                foreach ($allPatients as $patient) {
                    if (isset($patient['identity_card']) && $patient['identity_card'] === $identityCard) {
                        return $patient;
                    }
                }
            }
            
            throw new Exception("Không tìm thấy bệnh nhân với CMND/CCCD: $identityCard");
        }
    }

    /**
     * Thêm bệnh nhân mới
     */
    public function addPatient($patientData) {
        if ($this->useMockData) {
            return $this->getMockData('/patients', 'POST', $patientData);
        }

        return $this->sendRequest('POST', '/patients/', $patientData);
    }
    
    /**
     * Cập nhật thông tin bệnh nhân
     */
    public function updatePatient($identityCard, $patientData) {
        if ($this->useMockData) {
            return $this->getMockData('/patients/' . $identityCard, 'PUT', $patientData);
        }

        try {
            error_log("Updating patient with identity_card: $identityCard");
            error_log("Update data: " . json_encode($patientData));
            
            // Send PUT request to update patient
            $result = $this->sendRequest('PUT', '/patients/' . $identityCard, $patientData);
            error_log("Update result: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("Error updating patient: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Hàm chung để gửi request đến API
     */
    
    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $accessToken = $_SESSION['access_token'] ?? null;
        error_log("API Request: $method $url");

        $tryCount = 0;
        do {
            $tryCount++;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $headers = ['Content-Type: application/json'];
            if ($accessToken) {
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }
            if ($data) {
                $jsonData = json_encode($data);
                error_log("Request Payload: $jsonData");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                curl_close($ch);
                throw new Exception("CURL Error: " . curl_error($ch));
            }
            curl_close($ch);

            if ($httpCode === 401 && $tryCount === 1) {
                require_once 'mvc/services/AuthService.php';
                $authService = new AuthService();
                $authService->refreshToken();
                $accessToken = $_SESSION['access_token'] ?? null;
                continue;
            }

            if ($httpCode >= 400) {
                throw new Exception("Patient API Error: HTTP $httpCode");
            }

            if (is_string($response) && substr(trim($response), 0, 1) === '<') {
                throw new Exception('API trả về dữ liệu không hợp lệ (HTML thay vì JSON)');
            }

            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Lỗi parse JSON: ' . json_last_error_msg());
            }

            return $responseData;
        } while ($tryCount < 2);

        throw new Exception("Patient API Error: Không thể refresh token hoặc lỗi không xác định.");
    }

    // Thêm phương thức để lấy dữ liệu mẫu
    private function getMockData($endpoint, $method, $requestData = null) {
        // Dữ liệu mẫu cho danh sách bệnh nhân
        if ($endpoint === '/patients' && $method === 'GET') {
            return [
                'success' => true,
                'data' => [
                    [
                        'id' => 'BN001',
                        'name' => 'Nguyễn Văn A',
                        'email' => 'nguyenvana@example.com',
                        'phone' => '0901234567',
                        'gender' => 'Nam',
                        'dob' => '1990-05-15',
                        'address' => 'Quận 1, TP.HCM'
                    ],
                    [
                        'id' => 'BN002',
                        'name' => 'Trần Thị B',
                        'email' => 'tranthib@example.com',
                        'phone' => '0912345678',
                        'gender' => 'Nữ',
                        'dob' => '1985-10-20',
                        'address' => 'Quận 3, TP.HCM'
                    ],
                    [
                        'id' => 'BN003',
                        'name' => 'Lê Văn C',
                        'email' => 'levanc@example.com',
                        'phone' => '0987654321',
                        'gender' => 'Nam',
                        'dob' => '1978-12-05',
                        'address' => 'Quận 7, TP.HCM'
                    ],
                ]
            ];
        }
        
        // Dữ liệu mẫu cho chi tiết bệnh nhân
        if (strpos($endpoint, '/patients/') === 0 && $method === 'GET') {
            $id = substr($endpoint, strlen('/patients/'));
            
            $patients = [
                'BN001' => [
                    'id' => 'BN001',
                    'name' => 'Nguyễn Văn A',
                    'email' => 'nguyenvana@example.com',
                    'phone' => '0901234567',
                    'gender' => 'Nam',
                    'dob' => '1990-05-15',
                    'address' => 'Quận 1, TP.HCM',
                    'medical_history' => 'Tiểu đường Type 2',
                    'allergies' => 'Không có',
                    'blood_type' => 'A+'
                ],
                'BN002' => [
                    'id' => 'BN002',
                    'name' => 'Trần Thị B',
                    'email' => 'tranthib@example.com',
                    'phone' => '0912345678',
                    'gender' => 'Nữ',
                    'dob' => '1985-10-20',
                    'address' => 'Quận 3, TP.HCM',
                    'medical_history' => 'Cao huyết áp',
                    'allergies' => 'Penicillin',
                    'blood_type' => 'O'
                ],
                'BN003' => [
                    'id' => 'BN003',
                    'name' => 'Lê Văn C',
                    'email' => 'levanc@example.com',
                    'phone' => '0987654321',
                    'gender' => 'Nam',
                    'dob' => '1978-12-05',
                    'address' => 'Quận 7, TP.HCM',
                    'medical_history' => 'Không có',
                    'allergies' => 'Hải sản',
                    'blood_type' => 'AB'
                ]
            ];
            
            if (isset($patients[$id])) {
                return $patients[$id];
            }
            
            throw new Exception("Không tìm thấy bệnh nhân với ID: " . $id);
        }
        
        // Thêm bệnh nhân mới
        if ($endpoint === '/patients' && $method === 'POST') {
            if (!isset($requestData['name']) || !isset($requestData['email'])) {
                throw new Exception("Thiếu thông tin bệnh nhân");
            }
            
            return [
                'success' => true,
                'data' => array_merge(
                    ['id' => 'BN' . rand(100, 999)],
                    $requestData
                )
            ];
        }
        
        // Cập nhật bệnh nhân
        if (strpos($endpoint, '/patients/') === 0 && $method === 'PUT') {
            return [
                'success' => true,
                'data' => array_merge(
                    ['id' => substr($endpoint, strlen('/patients/'))],
                    $requestData
                ),
                'message' => 'Cập nhật thông tin bệnh nhân thành công'
            ];
        }
        
        // Xóa bệnh nhân
        if (strpos($endpoint, '/patients/') === 0 && $method === 'DELETE') {
            return [
                'success' => true,
                'message' => 'Xóa bệnh nhân thành công'
            ];
        }
        
        throw new Exception("Endpoint không hợp lệ: " . $endpoint);
    }

    public function checkIdentityCardExists($identityCard) {
        if ($this->useMockData) {
            // For mock data, find in our mock data array
            $mockData = $this->getMockData('/patients', 'GET');
            if (isset($mockData['data']) && is_array($mockData['data'])) {
                foreach ($mockData['data'] as $patient) {
                    if (isset($patient['identity_card']) && $patient['identity_card'] === $identityCard) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        try {
            // Lấy danh sách tất cả bệnh nhân
            $allPatients = $this->getAllPatients();
            
            // Kiểm tra cấu trúc dữ liệu trả về
            if (is_array($allPatients)) {
                // Nếu là mảng trực tiếp
                foreach ($allPatients as $patient) {
                    if (isset($patient['identity_card']) && $patient['identity_card'] === $identityCard) {
                        error_log("Found duplicate identity_card: $identityCard");
                        return true;
                    }
                }
            } else if (isset($allPatients['data']) && is_array($allPatients['data'])) {
                // Nếu có cấu trúc data
                foreach ($allPatients['data'] as $patient) {
                    if (isset($patient['identity_card']) && $patient['identity_card'] === $identityCard) {
                        error_log("Found duplicate identity_card: $identityCard");
                        return true;
                    }
                }
            }
            
            // Không tìm thấy trùng
            error_log("No duplicate found for identity_card: $identityCard");
            return false;
        } catch (Exception $e) {
            error_log("Error checking identity_card: " . $e->getMessage());
            return false;
        }
    }
}