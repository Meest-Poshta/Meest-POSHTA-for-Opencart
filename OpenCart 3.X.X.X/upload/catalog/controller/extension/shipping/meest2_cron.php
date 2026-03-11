<?php
/**
 * Meest2 Cron Controller
 * 
 * This controller provides a unified method to update all Meest data via cron job.
 * It executes all 4 import functions: Regions, Cities, Branches, and Streets.
 * 
 * Usage: 
 * Add to crontab: 
 * 0 2 * * * curl "https://yourdomain.com/index.php?route=extension/shipping/meest2_cron/updateAll&key=YOUR_SECRET_KEY"
 * 
 * Or use wget:
 * 0 2 * * * wget -q -O /dev/null "https://yourdomain.com/index.php?route=extension/shipping/meest2_cron/updateAll&key=YOUR_SECRET_KEY"
 */
class ControllerExtensionShippingMeest2Cron extends Controller {
    
    /**
     * Security key for cron access
     * You should change this to a random string and use it in your cron job URL
     */
    private $cron_key = 'meest2_cron_secret_key_2024';
    
    /**
     * Main method to update all Meest data
     * This method executes all 4 import functions sequentially
     */
    public function updateAll() {
        // Check security key
        if (!isset($this->request->get['key']) || $this->request->get['key'] !== $this->cron_key) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => 'Invalid security key'
            ]));
            return;
        }
        
        $this->load->model('extension/shipping/meest2');
        
        $results = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => []
        ];
        
        // 1. Import Regions
        try {
            $regionResult = $this->importRegions();
            $results['results']['regions'] = $regionResult;
        } catch (Exception $e) {
            $results['results']['regions'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // 2. Import Cities
        try {
            $cityResult = $this->importCity();
            $results['results']['cities'] = $cityResult;
        } catch (Exception $e) {
            $results['results']['cities'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // 3. Import Branches
        try {
            $branchResult = $this->importBranches();
            $results['results']['branches'] = $branchResult;
        } catch (Exception $e) {
            $results['results']['branches'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // 4. Import Streets
        try {
            $streetResult = $this->importStreets();
            $results['results']['streets'] = $streetResult;
        } catch (Exception $e) {
            $results['results']['streets'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // Output results
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    /**
     * Import Regions from Meest API
     */
    private function importRegions() {
        $zoneRegionMap = [
            '3480' => 'd15e302f-60b0-11de-be1e-0030485903e8', // Черкаська область
            '3481' => 'd15e3031-60b0-11de-be1e-0030485903e8', // Чернігівська область
            '3482' => 'd15e3030-60b0-11de-be1e-0030485903e8', // Чернівецька область
            '3484' => 'd15e301b-60b0-11de-be1e-0030485903e8', // Дніпропетровська область
            '3485' => 'd15e301c-60b0-11de-be1e-0030485903e8', // Донецька область
            '3486' => 'd15e3020-60b0-11de-be1e-0030485903e8', // Івано-Франківська область
            '3487' => 'd15e302d-60b0-11de-be1e-0030485903e8', // Херсонська область
            '3488' => 'd15e302e-60b0-11de-be1e-0030485903e8', // Хмельницька область
            '3489' => 'd15e3022-60b0-11de-be1e-0030485903e8', // Кіровоградська область
            '3490' => 'd15e3021-60b0-11de-be1e-0030485903e8', // Київ
            '3492' => 'd15e3023-60b0-11de-be1e-0030485903e8', // Луганська область
            '3493' => 'd15e3024-60b0-11de-be1e-0030485903e8', // Львівська область
            '3494' => 'd15e3025-60b0-11de-be1e-0030485903e8', // Миколаївська область
            '3495' => 'd15e3026-60b0-11de-be1e-0030485903e8', // Одеська область
            '3496' => 'd15e3027-60b0-11de-be1e-0030485903e8', // Полтавська область
            '3497' => 'd15e3028-60b0-11de-be1e-0030485903e8', // Рівненська область
            '3499' => 'd15e302a-60b0-11de-be1e-0030485903e8', // Сумська область
            '3500' => 'd15e302b-60b0-11de-be1e-0030485903e8', // Тернопільська область
            '3501' => 'd15e3019-60b0-11de-be1e-0030485903e8', // Вінницька область
            '3502' => 'd15e301a-60b0-11de-be1e-0030485903e8', // Волинська область
            '3503' => 'd15e301e-60b0-11de-be1e-0030485903e8', // Закарпатська область
            '3504' => 'd15e301f-60b0-11de-be1e-0030485903e8', // Запорізька область
            '3505' => 'd15e301d-60b0-11de-be1e-0030485903e8', // Житомирська область
            '4224' => 'd15e302c-60b0-11de-be1e-0030485903e8', // Харківська область
        ];
        
        $url = 'https://api.meest.com/v3.0/openAPI/regionSearch';
        
        $data = [
            "filters" => [
                "countryID" => "c35b6195-4ea3-11de-8591-001d600938f8"
            ]
        ];
        
        $response = $this->meestApiV3($url, $data);
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['status']) || $responseData['status'] !== "OK") {
            throw new Exception('API Error: ' . json_encode($responseData, JSON_UNESCAPED_UNICODE));
        }
        
        $count = 0;
        foreach ($responseData['result'] as $regionData) {
            $regionID = $regionData['regionID'];
            $zoneID = array_search($regionID, $zoneRegionMap);
            
            $regionDataToSave = [
                'region_id' => $regionID,
                'region_name_ua' => $regionData['regionDescr']['descrUA'],
                'region_name_en' => $regionData['regionDescr']['descrEN'],
                'country_id' => $regionData['countryID'],
                'zone_id' => $zoneID
            ];
            
            $region = $this->model_extension_shipping_meest2->getRegion($regionID);
            
            if (empty($region)) {
                $this->model_extension_shipping_meest2->addRegion($regionDataToSave);
            } else {
                $this->model_extension_shipping_meest2->editRegion($regionID, $regionDataToSave);
            }
            $count++;
        }
        
        return [
            'success' => true,
            'count' => $count,
            'message' => "Regions imported successfully"
        ];
    }
    
    /**
     * Import Cities from Meest file
     */
    private function importCity() {
        $url = 'https://meest-group.com/media/location/cities.txt';
        
        $response = file_get_contents($url);
        if ($response === false) {
            throw new Exception('Unable to fetch data from URL.');
        }
        
        $response = mb_convert_encoding($response, 'UTF-8', 'Windows-1251');
        $lines = explode("\n", $response);
        
        $existingCities = $this->model_extension_shipping_meest2->getAllCities();
        
        $insertData = [];
        $updateData = [];
        
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            
            $temp = explode(';', $line);
            
            $cityData = [
                'city_id' => (string)trim($temp[0]),
                'name_ua' => (string)trim($temp[1]),
                'name_ru' => (string)trim($temp[2]),
                'type_ua' => (string)trim($temp[3]),
                'district_id' => (string)trim($temp[4]),
                'region_id' => (string)trim($temp[5]),
                'koatuu' => (string)trim($temp[7]),
                'delivery_in_city' => (int)trim($temp[9]),
            ];
            
            if (!isset($existingCities[$cityData['city_id']])) {
                $insertData[] = $cityData;
            } else {
                $updateData[] = $cityData;
            }
        }
        
        if (!empty($insertData)) {
            $this->model_extension_shipping_meest2->bulkInsertCities($insertData);
        }
        
        if (!empty($updateData)) {
            $this->model_extension_shipping_meest2->bulkUpdateCities($updateData);
        }
        
        return [
            'success' => true,
            'inserted' => count($insertData),
            'updated' => count($updateData),
            'message' => "Cities imported successfully"
        ];
    }
    
    /**
     * Import Branches from Meest API
     */
    private function importBranches() {
        $url = 'https://api.meest.com/v3.0/openAPI/branchSearch';
        
        $data = [
            "in" => true,
            "out" => true,
            "close" => false
        ];
        
        $response = $this->meestApiV3($url, $data);
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['status']) || $responseData['status'] !== "OK") {
            throw new Exception('API Error: ' . json_encode($responseData, JSON_UNESCAPED_UNICODE));
        }
        
        $this->model_extension_shipping_meest2->saveBranchesBatch($responseData['result']);
        
        return [
            'success' => true,
            'count' => count($responseData['result']),
            'message' => "Branches imported successfully"
        ];
    }
    
    /**
     * Import Streets from Meest file
     */
    private function importStreets() {
        $result = $this->model_extension_shipping_meest2->importStreets();
        
        return [
            'success' => true,
            'inserted' => $result['inserted'],
            'updated' => $result['updated'],
            'message' => "Streets imported successfully"
        ];
    }
    
    /**
     * Make API request to Meest API v3
     */
    protected function meestApiV3($url, $data, $token = null, $method = 'POST') {
        if ($token === null) {
            $token = $this->getValidMeestToken();
        }
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Token: ' . $token,
        ));
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL Error: ' . $error);
        }
        
        curl_close($ch);
        
        return $response;
    }
    
    /**
     * Get valid Meest API token
     */
    protected function getValidMeestToken() {
        $auth = $this->auth();
        
        if (is_array($auth) && isset($auth['status']) && $auth['status'] === 'error') {
            if (isset($auth['error_warning_meest'])) {
                throw new Exception($auth['error_warning_meest']);
            } else {
                throw new Exception('Problems with API authorization, please check your login and password or token');
            }
        }
        
        if (!is_string($auth) || empty($auth)) {
            throw new Exception('Invalid or empty Meest token');
        }
        
        return $auth;
    }
    
    /**
     * Authenticate with Meest API
     */
    private function auth() {
        $url = 'https://api.meest.com/v3.0/openAPI/auth';
        
        $authMode = $this->config->get('shipping_meest2_auth_mode');
        
        if ($authMode === "api_key") {
            return $this->config->get('shipping_meest2_api_key');
        } elseif ($authMode === "default") {
            if ($this->config->get('shipping_meest2_login') && $this->config->get('shipping_meest2_password')) {
                $data = [
                    'username' => $this->config->get('shipping_meest2_login'),
                    'password' => $this->config->get('shipping_meest2_password')
                ];
            } else {
                return [
                    'status' => 'error',
                    'error_warning_meest' => 'Problems with getting a token, enter your login and password or token'
                ];
            }
        } else {
            return ['status' => 'error'];
        }
        
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            
            $response = curl_exec($ch);
            
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception('cURL Error: ' . $error);
            }
            
            curl_close($ch);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error_warning_meest' => 'Token request failed: ' . $e->getMessage()
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['status']) && $responseData['status'] === 'OK') {
            return $responseData['result']['token'];
        }
        
        return $responseData;
    }
}
