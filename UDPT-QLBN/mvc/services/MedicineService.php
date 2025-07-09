<?php
class MedicineService
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = "https://konggateway.hospitalmicroservices.live/api/v0"; 
    }

    public function getAllMedicines($page = 1, $limit = 10)
    {
        $url = "{$this->apiBaseUrl}/medicines/?page={$page}&limit={$limit}";
        return $this->makeApiRequest("GET", $url);
    }

    public function getMedicineById($id)
    {
        $url = "{$this->apiBaseUrl}/medicines/{$id}";
        return $this->makeApiRequest("GET", $url);
    }

    public function searchMedicines($keyword, $page = 1, $limit = 10)
    {
        $startTime = microtime(true);
        
        error_log("🔍 [SEARCH_SERVICE] ================== START ==================");
        error_log("🔍 [SEARCH_SERVICE] Input params:");
        error_log("  - keyword: '{$keyword}'");
        error_log("  - page: {$page}");
        error_log("  - limit: {$limit}");
        error_log("  - keyword length: " . strlen($keyword));
        error_log("  - keyword type: " . gettype($keyword));
        
        try {
            // ✅ BUILD URL với debug
            $encodedKeyword = urlencode($keyword);
            $baseUrl = "{$this->apiBaseUrl}/medicines/{$encodedKeyword}";
            $url = "{$baseUrl}?page={$page}&limit={$limit}";
            
            error_log("🔍 [SEARCH_SERVICE] URL building:");
            error_log("  - Base API URL: {$this->apiBaseUrl}");
            error_log("  - Original keyword: '{$keyword}'");
            error_log("  - Encoded keyword: '{$encodedKeyword}'");
            error_log("  - Base URL: {$baseUrl}");
            error_log("  - Final URL: {$url}");
            
            // ✅ CALL API với enhanced debug
            error_log("🔍 [SEARCH_SERVICE] Making API request...");
            $apiStartTime = microtime(true);
            
            $result = $this->makeApiRequest("GET", $url);
            
            $apiEndTime = microtime(true);
            $apiTime = ($apiEndTime - $apiStartTime) * 1000;
            
            error_log("🔍 [SEARCH_SERVICE] API call completed in {$apiTime}ms");
            error_log("🔍 [SEARCH_SERVICE] Raw result type: " . gettype($result));
            
            if (is_array($result)) {
                error_log("🔍 [SEARCH_SERVICE] Result keys: " . json_encode(array_keys($result)));
                error_log("🔍 [SEARCH_SERVICE] Result structure:");
                
                if (isset($result['data'])) {
                    error_log("  - Has 'data' key: YES");
                    error_log("  - Data type: " . gettype($result['data']));
                    error_log("  - Data count: " . count($result['data']));
                } else {
                    error_log("  - Has 'data' key: NO");
                    error_log("  - Is direct array: " . (is_array($result) ? 'YES' : 'NO'));
                    if (is_array($result)) {
                        error_log("  - Direct array count: " . count($result));
                    }
                }
            }
            
            // ✅ PROCESS RESULT với debug
            if ($result && isset($result['data'])) {
                error_log("🔍 [SEARCH_SERVICE] ✅ Structured response detected");
                $finalResult = $result;
            } else if ($result && is_array($result)) {
                error_log("🔍 [SEARCH_SERVICE] ✅ Direct array response, wrapping with pagination");
                $finalResult = [
                    'data' => $result,
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($result),
                    'total_pages' => max(1, ceil(count($result) / $limit))
                ];
            } else {
                error_log("🔍 [SEARCH_SERVICE] ⚠️ Empty or invalid response, returning empty result");
                $finalResult = [
                    'data' => [],
                    'page' => $page,
                    'limit' => $limit,
                    'total' => 0,
                    'total_pages' => 0
                ];
            }
            
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            error_log("🔍 [SEARCH_SERVICE] Final result:");
            error_log("  - Data count: " . count($finalResult['data']));
            error_log("  - Page: " . $finalResult['page']);
            error_log("  - Total: " . $finalResult['total']);
            error_log("  - Execution time: {$totalTime}ms");
            
            if (!empty($finalResult['data'])) {
                error_log("  - First item preview: " . json_encode($finalResult['data'][0]));
            }
            
            error_log("🔍 [SEARCH_SERVICE] ================== SUCCESS ==================");
            
            return $finalResult;
            
        } catch (Exception $e) {
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            error_log("❌ [SEARCH_SERVICE] EXCEPTION after {$totalTime}ms:");
            error_log("  - Message: " . $e->getMessage());
            error_log("  - File: " . $e->getFile());
            error_log("  - Line: " . $e->getLine());
            
            error_log("❌ [SEARCH_SERVICE] ================== ERROR ==================");
            
            throw new Exception("Lỗi tìm kiếm thuốc: " . $e->getMessage());
        }
    }

    public function getNearExpiryMedicines($page = 1, $limit = 10)
    {
        $url = "{$this->apiBaseUrl}/medicines/near-expiry?page={$page}&limit={$limit}";
        
        error_log("⚠️ [NEAR_EXPIRY] Calling Python API: " . $url);
        return $this->makeApiRequest("GET", $url);
    }

    public function createMedicine($data)
    {
        $url = "{$this->apiBaseUrl}/medicines/";
        return $this->makeApiRequest("POST", $url, $data);
    }

    public function updateMedicine($id, $data)
    {
        $url = "{$this->apiBaseUrl}/medicines/{$id}";
        return $this->makeApiRequest("PUT", $url, $data);
    }

    public function deleteMedicine($id)
    {
        $url = "{$this->apiBaseUrl}/medicines/{$id}";
        return $this->makeApiRequest("DELETE", $url);
    }

    private function makeApiRequest($method, $url, $data = null)
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $accessToken = $_SESSION['access_token'] ?? null;
        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $tryCount = 0;
        do {
            $tryCount++;
            $curl = curl_init();

            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
            ];

            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }

            curl_setopt_array($curl, $options);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($err) {
                throw new Exception("cURL Error: " . $err);
            }

            $decoded = json_decode($response, true);

            if ($httpCode === 401 && $tryCount === 1) {
                // Token hết hạn, tự động refresh
                require_once 'mvc/services/AuthService.php';
                $authService = new AuthService();
                $authService->refreshToken();
                $accessToken = $_SESSION['access_token'] ?? null;
                if ($accessToken) {
                    $headers[] = 'Authorization: Bearer ' . $accessToken;
                }
                continue;
            }

            if ($decoded && !isset($decoded['error'])) {
                if (!isset($decoded['data']) && is_array($decoded)) {
                    $result = [
                        'data' => $decoded,
                        'page' => $_GET['page'] ?? 1,
                        'limit' => $_GET['limit'] ?? 10,
                        'total' => count($decoded),
                        'total_pages' => ceil(count($decoded) / ($_GET['limit'] ?? 10))
                    ];
                    return $result;
                }
            }

            if ($httpCode >= 400) {
                $errorMessage = isset($decoded['error']) ? $decoded['error'] : "API Error (HTTP {$httpCode})";
                throw new Exception($errorMessage);
            }

            return $decoded;
        } while ($tryCount < 2);

        throw new Exception("Medicine API Error: Không thể refresh token hoặc lỗi không xác định.");
    }
}