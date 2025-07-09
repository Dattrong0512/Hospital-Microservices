<?php
class StaffService {
    private $baseUrl;

    public function __construct() {
        $this->baseUrl = "https://konggateway.hospitalmicroservices.live/api/v0";
    }

    /**
     * Lấy thông tin nhân viên theo ID
     */
    public function getStaffById($staffId) {
        $data = ['staff_id' => $staffId];
        return $this->sendRequest('POST', '/staff/byID', $data);
    }

    /**
     * Hàm chung để gửi request đến API
     */
    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $accessToken = $_SESSION['access_token'] ?? null;

        $tryCount = 0;
        do {
            $tryCount++;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            $headers = ['Content-Type: application/json', 'Accept: application/json'];
            if ($accessToken) {
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }
            if ($data) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            if ($curlErrno !== 0) {
                throw new Exception("Không thể kết nối đến Staff API server: " . $curlError);
            }
            if ($httpCode === 401 && $tryCount === 1) {
                // Token hết hạn, gọi AuthService để refresh
                require_once 'mvc/services/AuthService.php';
                $authService = new AuthService();
                $authService->refreshToken();
                $accessToken = $_SESSION['access_token'] ?? null; // Lấy lại token mới
                continue; // Thử lại request
            }
            if ($httpCode >= 400) {
                throw new Exception("Staff API Error: HTTP $httpCode");
            }
            if (empty($response)) {
                throw new Exception("Staff API trả về response rỗng");
            }
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Staff API JSON Parse Error: ' . json_last_error_msg());
            }
            return $responseData;
        } while ($tryCount < 2);

        throw new Exception("Staff API Error: Không thể refresh token hoặc lỗi không xác định.");
    }
}
