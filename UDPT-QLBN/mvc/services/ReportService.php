<?php
class ReportService
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "https://konggateway.hospitalmicroservices.live/api/v0/Report";
    }

    public function getMonthlyPatientStatistics($year)
    {
        $url = "{$this->baseUrl}/monthly-patient-statistics?year={$year}";
        return $this->makeApiRequest("GET", $url);
    }

    public function getMonthlyPrescriptionStatistics($year, $month)
    {
        $url = "{$this->baseUrl}/monthly-prescription-statistics?year={$year}&month={$month}";
        return $this->makeApiRequest("GET", $url);
    }

    private function makeApiRequest($method, $url, $data = null)
    {
        $curl = curl_init();
        $accessToken = $_SESSION['access_token'] ?? null;

        $headers = [
            'Content-Type: application/json'
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

        throw new Exception("Report API Error: Không thể refresh token hoặc lỗi không xác định.");
    }
}
?>
