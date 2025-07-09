<?php
class AppointmentService
{
    private $baseUrl;
    private $apiKey;
    private $useMockData = false; // Sử dụng dữ liệu giả lập
    private $patientService;
    private $doctorService;
    public function __construct()
    {
        $this->baseUrl = "https://konggateway.hospitalmicroservices.live/api/v0";
        $this->apiKey = "";

        require_once "./mvc/services/PatientService.php";
        require_once "./mvc/services/DoctorService.php";
        $this->patientService = new PatientService();
        $this->doctorService = new DoctorService();
    }

    public function getAllAppointments($page = 1, $limit = 10)
    {
        error_log("=== AppointmentService::getAllAppointments START ===");
        error_log("Parameters - Page: $page, Limit: $limit");
        
        $url = "{$this->baseUrl}/appointments/?page={$page}&limit={$limit}";
        error_log("Calling API URL: $url");
        
        $result = $this->makeApiRequest("GET", $url);
        error_log("API result type: " . gettype($result));
        error_log("API result: " . print_r($result, true));
        
        return $result;
    }

    public function getAppointmentById($id)
    {
        error_log("=== AppointmentService::getAppointmentById START ===");
        error_log("Getting appointment with ID: " . $id);
        
        // Thêm trailing slash để tránh redirect
        $url = $this->baseUrl . "/appointments/" . $id;
        error_log("API URL: " . $url);
        
        $response = $this->makeApiRequest("GET", $url);
        error_log("Raw appointment data: " . json_encode($response));
        
        // Enrich appointment data với thông tin bác sĩ và bệnh nhân
        if (is_array($response)) {
            $this->enrichAppointmentData($response);
            error_log("Enriched appointment data: " . json_encode($response));
        }
        
        error_log("=== AppointmentService::getAppointmentById SUCCESS ===");
        return $response;
    }

    public function createAppointment($data)
    {
        if ($this->useMockData) {
            $appointments = $this->getMockAppointments();
            $maxId = 0;
            foreach ($appointments as $appointment) {
                if ((int)$appointment['appointment_id'] > $maxId) {
                    $maxId = (int)$appointment['appointment_id'];
                }
            }
            
            $newId = $maxId + 1;
            $newAppointment = [
                'appointment_id' => (string)$newId,
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $data['patient_id'],
                'date' => date('d-m-Y', strtotime($data['date'])),
                'started_time' => $data['started_time'],
                'status' => $data['status'],
                'description' => $data['description']
            ];
            
            return $newAppointment;
        }

        $url = "{$this->baseUrl}/appointments/";
        return $this->makeApiRequest("POST", $url, $data);
    }

    public function updateAppointment($id, $data)
    {
        if ($this->useMockData) {
            $appointments = $this->getMockAppointments();
            foreach ($appointments as &$appointment) {
                if ($appointment['appointment_id'] == $id) {
                    foreach ($data as $key => $value) {
                        if (array_key_exists($key, $appointment)) {
                            $appointment[$key] = $value;
                        }
                    }
                    
                    // Format date if included
                    if (isset($data['date'])) {
                        $appointment['date'] = date('d-m-Y', strtotime($data['date']));
                    }
                    
                    return $appointment;
                }
            }
            throw new Exception("Không tìm thấy lịch khám với ID: " . $id);
        }

        $url = "{$this->baseUrl}/appointments/{$id}";
        return $this->makeApiRequest("PUT", $url, $data);
    }

    public function deleteAppointment($id)
    {
        if ($this->useMockData) {
            return ['success' => true, 'message' => 'Đã xóa lịch khám ID: ' . $id];
        }

        $url = $this->baseUrl . "/appointments/" . $id;
        $response = $this->makeRequest("DELETE", $url);
        return $response;
    }

    public function getAvailableDoctors($data)
    {
        error_log("=== AppointmentService::getAvailableDoctors START ===");
        error_log("Input parameters: " . json_encode($data));

        if ($this->useMockData) {
            return $this->getMockDoctors($data['doctor_department'] ?? '');
        }

        // Chuyển đổi tên tham số cho khớp với API Python
        $body = [
            'doctor_department' => $data['doctor_department'] ?? '',
            'appointment_date' => $data['appointment_date'] ?? $data['date'] ?? '', // Hỗ trợ cả hai tên tham số
            'started_time' => $data['started_time'] ?? ''
        ];
        
        $url = "{$this->baseUrl}/appointments/availableDoctors";
        error_log("API URL: $url");
        error_log("Final request body: " . json_encode($body, JSON_PRETTY_PRINT));
        
        try {
            // Thử dumping Ra JSON để kiểm tra
            $jsonData = json_encode($body);
            error_log("JSON data being sent: " . $jsonData);
            
            // Gọi API với phương thức POST và body JSON
            $result = $this->makeApiRequest("POST", $url, $body);
            error_log("API result: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("API error: " . $e->getMessage());
            // Trả về mảng trống thay vì throw exception
            return [];
        }
    }

    public function getAppointmentsByPatientIdentity($identity_card)
    {
        if ($this->useMockData) {
            $appointments = $this->getMockAppointments();
            $patients = $this->getMockPatients();
            $result = [];
            
            // Tìm patient_id từ identity_card
            $patientId = null;
            foreach ($patients as $patient) {
                if ($patient['identity_card'] === $identity_card) {
                    $patientId = $patient['patient_id'];
                    break;
                }
            }
            
            if ($patientId) {
                foreach ($appointments as $appointment) {
                    if ($appointment['patient_id'] == $patientId) {
                        $result[] = $appointment;
                    }
                }
            }
            
            return $result;
        }

        $url = $this->baseUrl . "/appointments/byPatientIdentity/" . $identity_card;
        $response = $this->makeRequest("GET", $url);
        return $response;
    }

    protected function makeApiRequest($method, $url, $data = null)
    {
        $curl = curl_init();
        $accessToken = $_SESSION['access_token'] ?? null;
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json"
        ];
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }
        $tryCount = 0;
        do {
            $tryCount++;
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if (($method === 'POST' || $method === 'PUT') && $data !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($err) {
                throw new Exception("Lỗi kết nối API: " . $err);
            }

            if ($httpCode === 401 && $tryCount === 1) {
                require_once 'mvc/services/AuthService.php';
                $authService = new AuthService();
                $authService->refreshToken();
                $accessToken = $_SESSION['access_token'] ?? null;
                if ($accessToken) {
                    $headers[] = 'Authorization: Bearer ' . $accessToken;
                }
                continue;
            }

            if ($httpCode >= 400) {
                throw new Exception("Lỗi từ API: HTTP $httpCode");
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Lỗi phân tích dữ liệu JSON");
            }

            return $data;
        } while ($tryCount < 2);

        throw new Exception("Appointment API Error: Không thể refresh token hoặc lỗi không xác định.");
    }

    

    public function enrichAppointmentData(&$appointment)
    {
        try {
            error_log("=== Enriching appointment START ===");
            error_log("Original appointment: " . json_encode($appointment));
            
            // Lấy thông tin bệnh nhân
            if (isset($appointment['patient_id']) && !isset($appointment['patient_name'])) {
                error_log("Getting patient info for ID: " . $appointment['patient_id']);
                try {
                    $patient = $this->patientService->getPatientById($appointment['patient_id']);
                    if ($patient && is_array($patient)) {
                        $appointment['patient_name'] = $patient['fullname'] ?? 'Không xác định';
                        $appointment['patient_phone'] = $patient['phone_number'] ?? '';
                        $appointment['patient_identity'] = $patient['identity_card'] ?? '';
                        $appointment['patient_birth_date'] = $patient['birth_date'] ?? '';
                        error_log("Patient enriched: " . $appointment['patient_name']);
                    }
                } catch (Exception $e) {
                    error_log("Patient service error: " . $e->getMessage());
                    $appointment['patient_name'] = 'Không xác định';
                }
            }
            
            // Lấy thông tin bác sĩ
            if (isset($appointment['doctor_id']) && !isset($appointment['doctor_name'])) {
                error_log("Getting doctor info for ID: " . $appointment['doctor_id']);
                try {
                    $doctor = $this->doctorService->getDoctorById($appointment['doctor_id']);
                    if ($doctor && is_array($doctor)) {
                        $appointment['doctor_name'] = $doctor['fullname'] ?? 'Không xác định';
                        $appointment['doctor_department'] = $doctor['department'] ?? '';
                        $appointment['doctor_email'] = $doctor['email'] ?? '';
                        $appointment['doctor_phone'] = $doctor['phone_number'] ?? '';
                        error_log("Doctor enriched: " . $appointment['doctor_name']);
                    }
                } catch (Exception $e) {
                    error_log("Doctor service error: " . $e->getMessage());
                    $appointment['doctor_name'] = 'Không xác định';
                }
            }
            
            // Format time if needed
            if (isset($appointment['started_time'])) {
                $appointment['started_time'] = $this->formatTimeTo24Hour($appointment['started_time']);
            }
            
            error_log("=== Enriching appointment SUCCESS ===");
            error_log("Final appointment: " . json_encode($appointment));
            
        } catch (Exception $e) {
            error_log("Error enriching appointment data: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Thiết lập giá trị mặc định nếu có lỗi
            $appointment['patient_name'] = $appointment['patient_name'] ?? 'Không xác định';
            $appointment['doctor_name'] = $appointment['doctor_name'] ?? 'Không xác định';
            $appointment['doctor_department'] = $appointment['doctor_department'] ?? '';
        }
    }

    private function formatTimeTo24Hour($timeStr)
    {
        // Nếu đã là định dạng 24 giờ, trả về nguyên bản
        if (strpos($timeStr, 'CH') === false && strpos($timeStr, 'SA') === false) {
            return $timeStr;
        }
        
        // Xử lý định dạng giờ có CH/SA
        list($time, $period) = explode(' ', $timeStr);
        list($hours, $minutes, $seconds) = array_pad(explode(':', $time), 3, '00');
        
        $hours = (int)$hours;
        
        if ($period === 'CH' && $hours < 12) {
            $hours += 12;
        }
        if ($period === 'SA' && $hours === 12) {
            $hours = 0;
        }
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getAppointmentsByDoctorId($doctor_id, $page = 1, $limit = 10, $filters = [])
    {
        error_log("=== AppointmentService::getAppointmentsByDoctorId START ===");
        error_log("Doctor ID: $doctor_id, Page: $page, Limit: $limit");
        error_log("Filters: " . json_encode($filters));
        
        // Xây dựng URL với các tham số lọc - điều chỉnh theo đúng parameter của backend
        $url = "{$this->baseUrl}/appointments/byDoctorId/{$doctor_id}?page={$page}&limit={$limit}";
        
        // Thêm các tham số lọc nếu có - điều chỉnh theo đúng parameter của backend
        if (!empty($filters)) {
            if (isset($filters['status']) && !empty($filters['status'])) {
                $url .= "&status=" . urlencode($filters['status']);
            }
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $url .= "&started_date=" . urlencode($filters['start_date']);
            }
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $url .= "&ended_date=" . urlencode($filters['end_date']);
            }
        }
        
        error_log("API URL: $url");
        
        try {
            $response = $this->makeApiRequest("GET", $url);
            error_log("Raw response data: " . json_encode($response));
            
            // Phân tích response
            if (is_array($response)) {
                // Nếu đã có định dạng phân trang chuẩn
                if (isset($response['data']) && is_array($response['data'])) {
                    // Bổ sung thông tin cho từng appointment
                    foreach ($response['data'] as &$appointment) {
                        $this->enrichAppointmentData($appointment);
                    }
                    
                    error_log("Returning structured data with " . count($response['data']) . " appointments");
                    return $response;
                } 
                // Nếu response là array đơn giản
                else {
                    // Bổ sung thông tin cho từng appointment
                    foreach ($response as &$appointment) {
                        $this->enrichAppointmentData($appointment);
                    }
                    
                    // Tạo cấu trúc phân trang
                    $result = [
                        'data' => $response,
                        'page' => $page,
                        'limit' => $limit,
                        'total' => count($response),
                        'total_pages' => ceil(count($response) / $limit)
                    ];
                    
                    error_log("Returning restructured data with " . count($response) . " appointments");
                    return $result;
                }
            }
            
            error_log("=== AppointmentService::getAppointmentsByDoctorId SUCCESS ===");
            return ['data' => [], 'page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0];
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            throw new Exception("Không thể lấy danh sách lịch khám: " . $e->getMessage());
        }
    }

    public function getAllAppointmentsWithFilters($page = 1, $limit = 10, $filters = [])
    {
        error_log("=== AppointmentService::getAllAppointmentsWithFilters START ===");
        error_log("Parameters - Page: $page, Limit: $limit");
        error_log("Filters: " . json_encode($filters));
        
        // Xây dựng URL với filters
        $url = "{$this->baseUrl}/appointments/?page={$page}&limit={$limit}";
        
        // Thêm các filter parameters
        if (!empty($filters)) {
            if (isset($filters['status']) && !empty($filters['status'])) {
                $url .= "&status=" . urlencode($filters['status']);
            }
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $url .= "&started_date=" . urlencode($filters['start_date']);
            }
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $url .= "&ended_date=" . urlencode($filters['end_date']);
            }
        }
        
        error_log("API URL with filters: $url");
        
        $result = $this->makeApiRequest("GET", $url);
        
        // Enrich data nếu có
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as &$appointment) {
                $this->enrichAppointmentData($appointment);
            }
        } else if (is_array($result)) {
            foreach ($result as &$appointment) {
                $this->enrichAppointmentData($appointment);
            }
        }
        
        error_log("=== AppointmentService::getAllAppointmentsWithFilters SUCCESS ===");
        return $result;
    }
    
}