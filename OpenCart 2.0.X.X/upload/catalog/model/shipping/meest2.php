<?php
class ModelShippingMeest2 extends Model {

    private $extension = 'meest2';

    public function getResults($typeId, $cityName, $search) {
        $sql = "SELECT br_id, num, street_ua as street,
            street_number, type_public_ua as type_public, `lng`, `lat` FROM "
            . DB_PREFIX . "meest2_branch WHERE `city_ua`='$cityName'";

        $parasits = array(
            'Відділення', 'Отделение',
            '№', 'Віділення',
            'віділення', 'відділення',
            'отделение',
            'вул', 'ул','st',
            'пров','пер',
            'шоссе','шосе',
            'просп','ave',
            'blvd','бул'
        );
        $searches = preg_split("/[\s,.]+/", $search);
        $searches = array_diff($searches, $parasits);
        $searches = array_filter($searches);
        $searches = implode('', $searches);

        if (is_numeric($searches)) {
            $sql .= " AND `num` LIKE '$searches%'";
        } else {
            $sql .= " AND (CONCAT(`street_ua`, `street_number`) LIKE '%$searches%'";
            $sql .= " OR CONCAT(`street_ru`, `street_number`) LIKE '%$searches%'";
            $sql .= " OR CONCAT(`street_en`, `street_number`) LIKE '%$searches%')";
        }

        if (is_array($typeId)) {
            $sql .= " AND type_id IN ('" . implode("','", $typeId) . "')";
        } else {
            $sql .= " AND type_id = '" . $this->db->escape($typeId) . "'";
        }

        $sql .= " ORDER BY num LIMIT 20";

        return $this->db->query($sql)->rows;
    }

    public function getCity($typeId, $search)
    {
        $sql = "SELECT DISTINCT `region_ua`, `district_ua`, `city_ua`, `city_id` FROM " . DB_PREFIX .
            "meest2_branch WHERE (`city_ua` LIKE '" . $this->db->escape($search) . "%' OR `city_ru` LIKE '"
            . $this->db->escape($search) . "%' OR `city_en` LIKE '" . $this->db->escape($search) . "%')";

        if (is_array($typeId)) {
            $sql .= " AND type_id IN ('" . implode("','", $typeId) . "')";
        } else {
            $sql .= " AND type_id = '" . $this->db->escape($typeId) . "'";
        }

        $sql .= " ORDER BY `city_ua` LIMIT 20";

        return $this->db->query($sql)->rows;
    }

    public function getQuote($address) {


        $data = $this->load->language('shipping/meest2');

        $this->document->addStyle(
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            'stylesheet'
        );

        $this->document->addScript(
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            'header'
        );
        $this->document->addScript('catalog/view/javascript/meest2/checkout.js?v=' . time());
        $this->document->addStyle('catalog/view/javascript/meest2/checkout.css');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('free_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('meest2_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if (!$status) {
            return array();
        }

        $image = 'image/catalog/meest2/meest_logo.svg';
        $base = $this->config->get('config_ssl') ? $this->config->get('config_ssl') : $this->config->get('config_url');
        $image = $base . $image;

        $image_html = '<img style="width:70px" src="' . $image . '" alt="' . $this->language->get('text_title') . '" title="'. $this->language->get('text_title') .'">';
        $image_html_service = "<img style='width:20px' src='$image' alt='' title=''/>";

        $method_data = array();
        $quote_data = array();

        $meestSessionData = isset($this->session->data['meest_data']) ? $this->session->data['meest_data'] : array();
        $cityUUID = isset($meestSessionData['city_UUID']) ? $meestSessionData['city_UUID'] : '';
        $address1UUID = isset($meestSessionData['address_1_UUID']) ? $meestSessionData['address_1_UUID'] : '';
        $shippingMethod = isset($meestSessionData['shipping_method']) ? $meestSessionData['shipping_method'] : '';

        $shipping_meest_service = $this->config->get('meest2_service');
        $checkoutCode = (isset($this->request->get['route']) ? $this->request->get['route'] : '') === 'checkout/shipping_method';

        if ($shipping_meest_service) {
            $errorMessage = '';
            $receiverMethod = str_replace('meest2.', '', $shippingMethod);

            $length = count($shipping_meest_service);
            $costs = $this->config->get('meest2_cost');
            $enabledCalculationInCheckout = $this->config->get('meest2_calculation_in_checkout');

            $freeEnabled = (bool)$this->config->get('meest2_free_shipping_enabled');
            $freeThreshold = (float)$this->config->get('meest2_free_shipping_threshold');
            $orderTotalBase = (float)$this->cart->getSubTotal();

            // Convert order total to UAH
            $sessionCurrency = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
            $baseCurrency = $this->config->get('config_currency');
            $orderTotalInSessionCurrency = $orderTotalBase;
            if ($baseCurrency !== $sessionCurrency) {
                $orderTotalInSessionCurrency = $this->currency->convert($orderTotalBase, $baseCurrency, $sessionCurrency);
            }
            if ($sessionCurrency === 'UAH') {
                $orderTotalUAH = $orderTotalInSessionCurrency;
            } else {
                $orderTotalUAH = $this->currency->convert($orderTotalInSessionCurrency, $sessionCurrency, 'UAH');
            }

            $isFreeShipping = $freeEnabled && $freeThreshold > 0 && $orderTotalUAH >= $freeThreshold;

            // Calculate cost for each service
            if ($cityUUID && $enabledCalculationInCheckout) {
                $serviceMapping = array(
                    'warehouse' => 'Branch',
                    'postomat'  => 'Branch',
                    'courier'   => 'Door'
                );

                foreach ($shipping_meest_service as $service) {
                    if ($isFreeShipping) {
                        $costs[$service] = 0;
                        continue;
                    }
                    $receiverService = isset($serviceMapping[$service]) ? $serviceMapping[$service] : 'Branch';

                    $params = array(
                        'sender_contract_id' => $this->config->get('meest2_sender_contract_id'),
                        'sender_branch_id' => $this->config->get('meest2_sender_branch'),
                        'sender_city_id' => $this->config->get('meest2_sender_city'),
                        'sender_service' => 'Branch',
                        'receiver_country_id' => 'c35b6167-4ea3-11de-8591-001d600938f8',
                        'receiver_city_id' => $cityUUID,
                        'receiver_service' => $receiverService,
                        'insurance' => $orderTotalUAH,
                    );

                    if (($service === 'warehouse' || $service === 'postomat') && $address1UUID) {
                        $params['receiver_branch_id'] = $address1UUID;
                    }

                    if ($service === 'courier' && $address1UUID) {
                        $params['receiver_address_id'] = $address1UUID;
                    }

                    $result = $this->calculateShippingCost($params);

                    if ($result && isset($result['costServices'])) {
                        $costInUAH = (float)$result['costServices'];
                        $costs[$service] = $this->currency->convert($costInUAH, 'UAH', $this->config->get('config_currency'));
                    } else {
                        if (!isset($costs[$service])) {
                            $costs[$service] = 0;
                        }
                    }
                }
            }

            foreach ($shipping_meest_service as $key => $service) {
                if ($checkoutCode) {
                    $errorData = false;
                } else {
                    $errorData = ($receiverMethod === $service && !empty($errorMessage)) ? $errorMessage : false;
                }
                if($service == 'courier'){
                    $isFreeShipping = 0;
                }
                // Переконуємось що вартість завжди числова
                $serviceBaseCost = $isFreeShipping ? 0.0 : (float)(isset($costs[$service]) ? $costs[$service] : 0);

                $costWithTax = $this->tax->calculate(
                    $serviceBaseCost,
                    $this->config->get('meest2_tax_class_id'),
                    $this->config->get('config_tax')
                );

                $formattedPrice = ($costWithTax <= 0)
                    ? 0
                    : $this->currency->format($costWithTax, $this->session->data['currency']);

                $priceHtml = '<span class="meest2-shipping-price" id="meest2-price-' . $service . '" data-service="' . $service . '" data-cost="' . $serviceBaseCost . '" data-cost-with-tax="' . $costWithTax . '">' . $formattedPrice . '</span>';

                $quote_data[$service] = array(
                    'code'         => 'meest2.' . $service,
                    'title'        => $image_html_service . " Meest: " . $this->language->get('text_title_' . $service),
                    'cost'         => $serviceBaseCost,
                    'tax_class_id' => $this->config->get('meest2_tax_class_id'),
                    'text'         => $priceHtml,
                    'error'        => $errorData
                );

            }

            if ($checkoutCode && !empty($errorMessage)) {
                $method_data = array(
                    'code' => $this->extension,
                    'title' => $image_html . $this->language->get('text_title'),
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get('meest2_sort_order'),
                    'error' => $errorMessage
                );
            } else {
                $method_data = array(
                    'code' => $this->extension,
                    'title' => $image_html . $this->language->get('text_title'),
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get('meest2_sort_order'),
                    'error' => false
                );
            }
        }
        return $method_data;
    }

    public function getCities($region_id, $search = '')
    {
        $sql = "SELECT
            c.`city_id` AS id,
            c.`type_ua` AS type,
            c.`name_ua` AS name,
            r.`region_name_ua` AS region,
            d.`district_ua` AS district
        FROM `" . DB_PREFIX . "meest2_cities` c
        LEFT JOIN `" . DB_PREFIX . "meest2_regions` r ON c.`region_id` = r.`region_id`
        LEFT JOIN `" . DB_PREFIX . "meest2_district` d ON d.`district_id` = c.`district_id`
        WHERE 1";

        if ($region_id) {
            $region_query = $this->db->query("SELECT `region_id` 
                                      FROM `" . DB_PREFIX . "meest2_regions` 
                                      WHERE `zone_id` = '" . (int)$region_id . "'");
            if ($region_query->num_rows) {
                $region_id = $region_query->row['region_id'];
                $sql .= " AND c.`region_id` = '" . $this->db->escape($region_id) . "'";
            }
        }

        $search = urldecode($search);
        if ($search) {
            $sql .= " AND (c.`name_ua` LIKE '" . $this->db->escape($search) . "%' OR c.`name_ru` LIKE '" . $this->db->escape($search) . "%')";
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN c.`name_ua` LIKE '" . $this->db->escape($search) . "' THEN 1 
                WHEN c.`name_ua` LIKE '" . $this->db->escape($search) . "%' THEN 2 
                ELSE 3 
            END,
            CHAR_LENGTH(c.`name_ua`),
            c.`name_ua`";

        $sql .= " LIMIT 50";

        return $this->db->query($sql)->rows;
    }

    public function getBranches($city_id, $search = '', $types) {
        $city_id = urldecode($city_id);
        $search = urldecode($search);

        $sql = "SELECT `branch_id` AS id,
                   `short_name` AS description,
                   `address_more_information`
            FROM `" . DB_PREFIX . "meest2_branch`
            WHERE `city_id` = '" . $this->db->escape($city_id) . "'";

        if (!empty($types)) {
            if (is_array($types)) {
                $escaped_types = array_map([$this->db, 'escape'], $types);
                $sql .= " AND `branch_type_id` IN ('" . implode("','", $escaped_types) . "')";
            } else {
                $sql .= " AND `branch_type_id` = '" . $this->db->escape($types) . "'";
            }
        }

        if ($search) {
            $sql .= " AND (`short_name` LIKE '%" . $this->db->escape($search) . "%'
                 OR `address` LIKE '%" . $this->db->escape($search) . "%')";
        }

        $sql .= " ORDER BY `short_name` LIMIT 50";

        return $this->db->query($sql)->rows;
    }

    public function getStreets($city_id, $search = '') {
        $city_id = urldecode($city_id);
        $search = urldecode($search);

        $sql = "SELECT `street_id` AS id, CONCAT(`type_ua`, ' ', `name_ua`) AS description, CONCAT(`type_ua`, ' ', `name_ua`) AS full_description 
            FROM `" . DB_PREFIX . "meest2_streets` 
            WHERE `city_id` = '" . $this->db->escape($city_id) . "'";

        if ($search) {
            $sql .= " AND (`name_ua` LIKE '" . $this->db->escape($search) . "%' OR `name_ru` LIKE '" . $this->db->escape($search) . "%')";
        }

        $sql .= " ORDER BY `name_ua` LIMIT 50";

        return $this->db->query($sql)->rows;
    }

    public function callMeestAPIv3($url, $data) {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "Content-Type: application/json",
                'token: ' . $this->auth()
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }

    public function prepareApiData($recipientCityUUID, $recipientAddress1UUID, $shippingMethod) {
        $products = $this->cart->getProducts();

        $totalQuantity = 0;
        $totalWeight   = 0;
        $maxLength     = 0;
        $maxWidth      = 0;
        $maxHeight     = 0;

        foreach ($products as $product) {
            $totalQuantity += $product['quantity'];
            $totalWeight   += $product['weight'];

            if ($product['length'] > $maxLength) {
                $maxLength = $product['length'];
            }
            if ($product['width'] > $maxWidth) {
                $maxWidth = $product['width'];
            }
            if ($product['height'] > $maxHeight) {
                $maxHeight = $product['height'];
            }
        }

        $placesItems = [
            'quantity'  => $totalQuantity,
            'weight'    => $totalWeight,
            'insurance' => 0,
            'length'    => $maxLength,
            'width'     => $maxWidth,
            'height'    => $maxHeight,
        ];

        $receiverMethod = str_replace('meest2.', '', $shippingMethod);
        $capitalizedMethod = ucfirst($receiverMethod);
        if ($capitalizedMethod === 'Postomat') {
            $capitalizedMethod = 'Branch';
        } elseif ($capitalizedMethod === 'Courier') {
            $capitalizedMethod = 'Door';
            $recipientAddress1UUID = '';
        } else {
            $capitalizedMethod = 'Branch';
        }

        $data = [
            'sendingDate' => '',
            'contractID'  => '',
            'sender'      => [
                'branchID' => $this->config->get('meest2_sender_branch'),
                'cityID'   => $this->config->get('meest2_sender_city'),
                'service'  => "Branch"
            ],
            'receiver'    => [
                'branchID'  => $recipientAddress1UUID,
                'cityID'    => $recipientCityUUID,
                'service'   => $capitalizedMethod
            ],
            'placesItems' => [$placesItems]
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function auth() {
        $url = 'https://api.meest.com/v3.0/openAPI/auth';

        $authMode = $this->config->get('meest2_auth_mode');
        if($authMode === "api_key"){
            return $this->config->get('meest2_api_key');
        } elseif ($authMode === "default"){
            if($this->config->get('meest2_login') && $this->config->get('meest2_password')){
                $data = json_encode([
                    'username' => $this->config->get('meest2_login'),
                    'password' => $this->config->get('meest2_password')
                ]);
            } else {
                $this->session->data['error_warning_meest'] = 'Problems with getting a token, enter your login and password';
                $this->response->redirect($this->url->link('shipping/meest2', 'token=' . $this->session->data['token'] . '&type=shipping', true));
            }
        } else {
            $responseData['status']  = 'error';

            return $responseData;
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $responseData = json_decode($response, true);

        $status = isset($responseData['status']) ? $responseData['status'] : null;
        $message = isset($responseData['info']['message']) ? $responseData['info']['message'] : null;

        if ($responseData['status'] === 'OK') {
            return $responseData['result']['token'];
        } else {
            return $responseData;
        }
    }

    public function getShippingMethod() {
        if (!empty($this->request->post['shipping_method']) && is_string($this->request->post['shipping_method'])) {
            $data = explode('.', $this->request->post['shipping_method']);
        } elseif (!empty($this->request->post['shipping']) && is_string($this->request->post['shipping'])) {
            $data = explode('.', $this->request->post['shipping']);
        } elseif (isset($this->session->data['shipping_method'], $this->session->data['shipping_method']['code']) && is_string($this->session->data['shipping_method']['code'])) {
            $data = explode('.', $this->session->data['shipping_method']['code']);
        } else {
            $data = array('', '');
        }

        return array (
            'method'     => $data[0],
            'sub_method' => $data[1]
        );
    }

    public function getMeest2Cities($region = '', $search = '') {

        $regionId = $this->getRegionIdByZone($region);

        if (!$regionId) {
//            $sql = "SELECT `name_ua`, `type_ua` FROM `" . DB_PREFIX . "meest2_cities` WHERE 1";
            $sql = "
                SELECT `name_ua`, `type_ua`
                FROM `" . DB_PREFIX . "meest2_cities`
                WHERE `type_ua` = 'місто'
            ";
        } else {
            $escaped_region = $this->db->escape($regionId);
            $sql = "
                SELECT `name_ua`, `type_ua`
                FROM `" . DB_PREFIX . "meest2_cities`
                WHERE `region_id` = '" . $escaped_region . "'
            ";
        }

        $sql .= " ORDER BY `name_ua`";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getBranchesByCityMeest($city_name, $types) {

        if ($types == 'postomat') {
            $types = $this->poshtomatType;
        } else {
            $types = $this->warehouseType;
        }

        $sql = "
            SELECT `short_name`, `branch_type`
            FROM `" . DB_PREFIX . "meest2_branch`
            WHERE `city_ua` = '" . $this->db->escape($city_name) . "'
        ";

        if (!empty($types)) {
            if (is_array($types)) {
                $escaped_types = array_map([$this->db, 'escape'], $types);
                $sql .= " AND `branch_type_id` IN ('" . implode("','", $escaped_types) . "')";
            } else {

                $sql .= " AND `branch_type_id` = '" . $this->db->escape($types) . "'";
            }
        }

        $sql .= " ORDER BY `branch_number`";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getRegionIdByZone($zone_id) {
        $zone_id_escaped = $this->db->escape($zone_id);

        $query = $this->db->query("
            SELECT `region_id`
            FROM `" . DB_PREFIX . "meest2_regions`
            WHERE `zone_id` = '" . $zone_id_escaped . "'
            LIMIT 1
        ");

        if (isset($query->row['region_id'])) {
            return $query->row['region_id'];
        } else {
            return null;
        }
    }

    // ========== МЕТОДЫ ДЛЯ CRON ИМПОРТА ==========

    public function getRegion($region_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_regions WHERE region_id = '" . $this->db->escape($region_id) . "'");
        return $query->row;
    }

    public function addRegion($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest2_regions` SET
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
        ");
    }

    public function editRegion($region_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest2_regions` SET
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
            WHERE `region_id` = '" . $this->db->escape($region_id) . "'
        ");
    }

    public function getAllCities() {
        $query = $this->db->query("SELECT city_id FROM " . DB_PREFIX . "meest2_cities");
        $cities = [];
        foreach ($query->rows as $row) {
            $cities[$row['city_id']] = $row['city_id'];
        }
        return $cities;
    }

    public function bulkInsertCities($data) {
        if (!$data) return;
        $values = array();
        foreach ($data as $row) {
            $values[] = "('" . $this->db->escape($row['city_id']) . "', '" . $this->db->escape($row['name_ua']) . "', '" . $this->db->escape($row['name_ru']) . "', '" . $this->db->escape($row['type_ua']) . "', '" . $this->db->escape($row['district_id']) . "', '" . $this->db->escape($row['region_id']) . "', '" . $this->db->escape($row['koatuu']) . "', '" . (int)$row['delivery_in_city'] . "')";
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_cities (city_id, name_ua, name_ru, type_ua, district_id, region_id, koatuu, delivery_in_city) VALUES " . implode(',', $values));
    }

    public function bulkUpdateCities($data) {
        foreach ($data as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "meest2_cities SET
            name_ua = '" . $this->db->escape($row['name_ua']) . "',
            name_ru = '" . $this->db->escape($row['name_ru']) . "',
            type_ua = '" . $this->db->escape($row['type_ua']) . "',
            district_id = '" . $this->db->escape($row['district_id']) . "',
            region_id = '" . $this->db->escape($row['region_id']) . "',
            koatuu = '" . $this->db->escape($row['koatuu']) . "',
            delivery_in_city = '" . (int)$row['delivery_in_city'] . "'
            WHERE city_id = '" . $this->db->escape($row['city_id']) . "'
        ");
        }
    }

    public function saveBranchesBatch($branches) {
        $columns = [
            'branch_id',
            'branch_no',
            'branch_number',
            'branch_type',
            'is_branch_open',
            'is_branch_closed',
            'branch_type_id',
            'branch_type_descr',
            'branch_type_id_client',
            'client_type_subdivision',
            'client_type_subdivision_id',
            'short_name',
            'full_name',
            'branch_descr_ua',
            'branch_descr_loc',
            'branch_descr_search_ua',
            'branch_descr_search_loc',
            'address_id',
            'address_descr_ua',
            'address_descr_ru',
            'address_descr_en',
            'address_descr_loc',
            'address_more_information',
            'city_id',
            'city_ua',
            'city_ru',
            'city_en',
            'city_loc',
            'district_id',
            'district_ua',
            'district_ru',
            'district_en',
            'district_loc',
            'region_id',
            'region_ua',
            'region_ru',
            'region_en',
            'region_loc',
            'working_hours',
            'street_number',
            'zip',
            'latitude',
            'longitude',
            'branch_work_time',
            'phone',
            'address',
            'payment_types',
            'branch_limits',
            'localization',
            'payment_methods',
            'customer_identification',
            'partner_services',
            'services'
        ];

        $chunks = array_chunk($branches, 100);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $branchData) {
                $dataToSave = [
                    'branch_id'                 => $branchData['branchID'] ?? null,
                    'branch_no'                 => $branchData['branchNo'] ?? null,
                    'branch_number'             => $branchData['branchNumber'] ?? null,
                    'branch_type'               => $branchData['branchType'] ?? null,
                    'is_branch_open'            => isset($branchData['isBranchOpen']) ? (int)$branchData['isBranchOpen'] : null,
                    'is_branch_closed'          => isset($branchData['isBranchClosed']) ? (int)$branchData['isBranchClosed'] : null,
                    'branch_type_id'            => $branchData['branchTypeID'] ?? null,
                    'branch_type_descr'         => $branchData['branchTypeDescr'] ?? null,
                    'branch_type_id_client'     => $branchData['branchTypeIDClient'] ?? null,
                    'client_type_subdivision'   => $branchData['ClientTypeSubdivision'] ?? null,
                    'client_type_subdivision_id'=> $branchData['ClientTypeSubdivisionID'] ?? null,
                    'short_name'                => $branchData['ShortName'] ?? null,
                    'full_name'                 => $branchData['FullName'] ?? null,
                    'branch_descr_ua'           => $branchData['branchDescr']['descrUA'] ?? null,
                    'branch_descr_loc'          => $branchData['branchDescr']['descrLoc'] ?? null,
                    'branch_descr_search_ua'    => $branchData['branchDescr']['descrSearchUA'] ?? null,
                    'branch_descr_search_loc'   => $branchData['branchDescr']['descrSearchLoc'] ?? null,
                    'address_id'                => $branchData['addressID'] ?? null,
                    'address_descr_ua'          => $branchData['addressDescr']['descrUA'] ?? null,
                    'address_descr_ru'          => $branchData['addressDescr']['descrRU'] ?? null,
                    'address_descr_en'          => $branchData['addressDescr']['descrEN'] ?? null,
                    'address_descr_loc'         => $branchData['addressDescr']['descrLoc'] ?? null,
                    'address_more_information'  => $branchData['addressMoreInformation'] ?? null,
                    'city_id'                   => $branchData['cityID'] ?? null,
                    'city_ua'                   => $branchData['cityDescr']['descrUA'] ?? null,
                    'city_ru'                   => $branchData['cityDescr']['descrRU'] ?? null,
                    'city_en'                   => $branchData['cityDescr']['descrEN'] ?? null,
                    'city_loc'                  => $branchData['cityDescr']['descrLoc'] ?? null,
                    'district_id'               => $branchData['districtID'] ?? null,
                    'district_ua'               => $branchData['districtDescr']['descrUA'] ?? null,
                    'district_ru'               => $branchData['districtDescr']['descrRU'] ?? null,
                    'district_en'               => $branchData['districtDescr']['descrEN'] ?? null,
                    'district_loc'              => $branchData['districtDescr']['descrLoc'] ?? null,
                    'region_id'                 => $branchData['regionID'] ?? null,
                    'region_ua'                 => $branchData['regionDescr']['descrUA'] ?? null,
                    'region_ru'                 => $branchData['regionDescr']['descrRU'] ?? null,
                    'region_en'                 => $branchData['regionDescr']['descrEN'] ?? null,
                    'region_loc'                => $branchData['regionDescr']['descrLoc'] ?? null,
                    'working_hours'             => $branchData['workingHours'] ?? null,
                    'street_number'             => $branchData['building'] ?? null,
                    'zip'                       => $branchData['zipCode'] ?? null,
                    'latitude'                  => isset($branchData['latitude']) ? (float)$branchData['latitude'] : null,
                    'longitude'                 => isset($branchData['longitude']) ? (float)$branchData['longitude'] : null,
                    'branch_work_time'          => isset($branchData['branchWorkTime']) ? json_encode($branchData['branchWorkTime'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'phone'                     => $branchData['phone'] ?? null,
                    'address'                   => $branchData['address'] ?? null,
                    'payment_types'             => $branchData['paymentTypes'] ?? null,
                    'branch_limits'             => isset($branchData['branchLimits']) ? json_encode($branchData['branchLimits'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'localization'              => $branchData['Localization'] ?? null,
                    'payment_methods'           => isset($branchData['paymentMethods']) ? json_encode($branchData['paymentMethods'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'customer_identification'   => isset($branchData['customerIdentification']) ? json_encode($branchData['customerIdentification'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'partner_services'          => isset($branchData['PartnerServices']) ? json_encode($branchData['PartnerServices'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'services'                  => isset($branchData['Services']) ? json_encode($branchData['Services'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ];

                $row = [];
                foreach ($columns as $col) {
                    $row[] = isset($dataToSave[$col]) ? "'" . $this->db->escape($dataToSave[$col]) . "'" : "NULL";
                }
                $rows[] = "(" . implode(",", $row) . ")";
            }

            if (!empty($rows)) {
                $columnsList = "`" . implode("`, `", $columns) . "`";
                $updateColumns = array_map(function ($col) {
                    return "`$col` = VALUES(`$col`)";
                }, array_diff($columns, ['branch_id']));

                $sql = "INSERT INTO `" . DB_PREFIX . "meest2_branch` ($columnsList)
                VALUES " . implode(", ", $rows) . "
                ON DUPLICATE KEY UPDATE " . implode(", ", $updateColumns);

                $this->db->query($sql);
            }
        }

        return true;
    }

    public function importStreets() {
        $url = 'https://meest-group.com/media/location/streets.txt';
        $handle = @fopen($url, 'r');
        if (!$handle) return false;

        $existingStreetIds = $this->getAllStreetIds();
        $existingStreetIds = array_flip($existingStreetIds);

        $insertData = array();
        $updateData = array();

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1251');
            if (empty($line)) continue;
            $fields = explode(';', $line);
            if (count($fields) < 12) continue;

            $addressData = array(
                'street_id' => trim($fields[0]),
                'type_ua' => trim($fields[1]),
                'type_ru' => trim($fields[2]),
                'name_ua' => trim($fields[3]),
                'name_ru' => trim($fields[4]),
                'city_id' => trim($fields[5]),
                'region_id' => trim($fields[6]),
                'district_ua' => trim($fields[7]),
                'district_ru' => trim($fields[8]),
                'region_ua' => trim($fields[9]),
                'region_ru' => trim($fields[10]),
                'postal_code' => trim($fields[11])
            );

            if (!isset($existingStreetIds[$addressData['street_id']])) {
                $insertData[] = $addressData;
            } else {
                $updateData[] = $addressData;
            }

            if (count($insertData) >= 1000) {
                $this->bulkInsertStreets($insertData);
                $insertData = array();
            }
            if (count($updateData) >= 1000) {
                $this->bulkUpdateStreets($updateData);
                $updateData = array();
            }
        }

        fclose($handle);

        if (!empty($insertData)) $this->bulkInsertStreets($insertData);
        if (!empty($updateData)) $this->bulkUpdateStreets($updateData);

        return true;
    }

    public function getAllStreetIds() {
        $query = $this->db->query("SELECT street_id FROM " . DB_PREFIX . "meest2_streets");
        $result = array();
        foreach ($query->rows as $row) {
            $result[] = $row['street_id'];
        }
        return $result;
    }

    public function bulkInsertStreets($data) {
        $values = [];
        foreach ($data as $row) {
            $values[] = "(
            '" . $this->db->escape($row['street_id']) . "',
            '" . $this->db->escape($row['type_ua']) . "',
            '" . $this->db->escape($row['type_ru']) . "',
            '" . $this->db->escape($row['name_ua']) . "',
            '" . $this->db->escape($row['name_ru']) . "',
            '" . $this->db->escape($row['city_id']) . "',
            '" . $this->db->escape($row['region_id']) . "',
            '" . $this->db->escape($row['district_ua']) . "',
            '" . $this->db->escape($row['district_ru']) . "',
            '" . $this->db->escape($row['region_ua']) . "',
            '" . $this->db->escape($row['region_ru']) . "',
            '" . $this->db->escape($row['postal_code']) . "'
        )";
        }

        if (!empty($values)) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_streets (
            street_id,
            type_ua,
            type_ru,
            name_ua,
            name_ru,
            city_id,
            region_id,
            district_ua,
            district_ru,
            region_ua,
            region_ru,
            postal_code
        ) VALUES " . implode(', ', $values));
        }
    }

    public function bulkUpdateStreets($data) {
        foreach ($data as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "meest2_streets SET
                type_ua = '" . $this->db->escape($row['type_ua']) . "',
                type_ru = '" . $this->db->escape($row['type_ru']) . "',
                name_ua = '" . $this->db->escape($row['name_ua']) . "',
                name_ru = '" . $this->db->escape($row['name_ru']) . "',
                city_id = '" . $this->db->escape($row['city_id']) . "',
                region_id = '" . $this->db->escape($row['region_id']) . "',
                district_ua = '" . $this->db->escape($row['district_ua']) . "',
                district_ru = '" . $this->db->escape($row['district_ru']) . "',
                region_ua = '" . $this->db->escape($row['region_ua']) . "',
                region_ru = '" . $this->db->escape($row['region_ru']) . "',
                postal_code = '" . $this->db->escape($row['postal_code']) . "'
                WHERE street_id = '" . $this->db->escape($row['street_id']) . "'
            ");
        }
    }

    /**
     * Calculate shipping cost via Meest API
     */
    public function calculateShippingCost($params) {
        try {
            $calculationData = $this->prepareCalculationData($params);
            $apiUrl = 'https://api.meest.com/v3.0/openAPI/calculate';
            $response = $this->callMeestAPIv3(
                $apiUrl,
                json_encode($calculationData, JSON_UNESCAPED_UNICODE)
            );

            if ($response && isset($response['status']) && $response['status'] === 'OK') {
                return $response['result'];
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    public function prepareCalculationData($params) {
        $sendingDate = isset($params['sending_date'])
            ? $params['sending_date']
            : date('d.m.Y');

        $contractID = isset($params['sender_contract_id'])
            ? $params['sender_contract_id']
            : $this->config->get('meest2_sender_contract_id');

        $promocode = isset($params['promocode']) ? $params['promocode'] : '';

        $sender = $this->prepareSenderData($params);
        $receiver = $this->prepareReceiverData($params);
        $placesItems = $this->preparePlacesItems($params);

        $specConditionsItems = array();
        if (isset($params['spec_conditions']) && is_array($params['spec_conditions'])) {
            foreach ($params['spec_conditions'] as $conditionID) {
                $specConditionsItems[] = array('conditionID' => $conditionID);
            }
        }

        $data = array(
            'sendingDate' => $sendingDate,
            'contractID' => $contractID,
            'notation' => '',
            'payType' => 'cash',
            'receiverPay' => false,
            'sender' => $sender,
            'receiver' => $receiver,
            'placesItems' => $placesItems
        );

        if (!empty($promocode)) {
            $data['promocode'] = $promocode;
        }

        if (!empty($specConditionsItems)) {
            $data['specConditionsItems'] = $specConditionsItems;
        }

        return $data;
    }

    private function prepareSenderData($params) {
        $sender = array();

        $senderService = isset($params['sender_service'])
            ? $params['sender_service']
            : 'Branch';

        $sender['service'] = $senderService;

        if ($senderService === 'Door') {
            $sender['addressID'] = isset($params['sender_address_id'])
                ? $params['sender_address_id']
                : $this->config->get('meest2_sender_address');
        } else {
            $sender['branchID'] = isset($params['sender_branch_id'])
                ? $params['sender_branch_id']
                : $this->config->get('meest2_sender_branch');
        }

        $sender['name'] = 'test';
        $sender['phone'] = '';

        return $sender;
    }

    private function prepareReceiverData($params) {
        $receiver = array();

        // ID міста отримувача (обов'язково для всіх типів доставки)
        if (isset($params['receiver_city_id'])) {
            $receiver['cityID'] = $params['receiver_city_id'];
        }

        // Тип сервісу отримувача (Branch або Door)
        if (isset($params['receiver_service'])) {
            $receiver['service'] = $params['receiver_service'];
        }

        if (isset($params['receiver_service']) && $params['receiver_service'] === 'Door') {
            // Доставка до дверей отримувача
            if (isset($params['receiver_address_id'])) {
                $receiver['addressID'] = $params['receiver_address_id'];
            }

            if (isset($params['receiver_building'])) {
                $receiver['building'] = $params['receiver_building'];
            }

            if (isset($params['receiver_floor'])) {
                $receiver['floor'] = (int)$params['receiver_floor'];
            }

            if (isset($params['receiver_flat'])) {
                $receiver['flat'] = $params['receiver_flat'];
            }

        } else {
            // Доставка до відділення
            if (isset($params['receiver_branch_id'])) {
                $receiver['branchID'] = $params['receiver_branch_id'];
            }
        }

        $receiver['name'] = 'test';
        $receiver['phone'] = '';

        return $receiver;
    }

    private function preparePlacesItems($params) {
        $placesItems = array();

        if (isset($params['places']) && is_array($params['places'])) {
            foreach ($params['places'] as $place) {
                $item = array(
                    'quantity' => isset($place['quantity']) ? (int)$place['quantity'] : 1,
                    'weight' => isset($place['weight']) ? (float)$place['weight'] : 0,
                    'insurance' => isset($place['insurance']) ? (float)$place['insurance'] : 0,
                    'length' => isset($place['length']) ? (float)$place['length'] : 0,
                    'width' => isset($place['width']) ? (float)$place['width'] : 0,
                    'height' => isset($place['height']) ? (float)$place['height'] : 0,
                );

                $item['volume'] = ($item['length'] * $item['width'] * $item['height']) / 5000;
                $placesItems[] = $item;
            }
        } else {
            $products = $this->cart->getProducts();

            $totalQuantity = 0;
            $totalWeight = 0;
            $maxLength = 0;
            $maxWidth = 0;
            $maxHeight = 0;

            foreach ($products as $product) {
                $productLength = (float)$product['length'];
                $productWidth = (float)$product['width'];
                $productHeight = (float)$product['height'];
                $productWeight = (float)$product['weight'];

                $totalQuantity += $product['quantity'];
                $totalWeight += $productWeight * $product['quantity'];

                if ($productLength > $maxLength) {
                    $maxLength = $productLength;
                }
                if ($productWidth > $maxWidth) {
                    $maxWidth = $productWidth;
                }
                if ($productHeight > $maxHeight) {
                    $maxHeight = $productHeight;
                }
            }

            $finalLength = $maxLength > 0 ? (float)$maxLength : 10;
            $finalWidth = $maxWidth > 0 ? (float)$maxWidth : 10;
            $finalHeight = $maxHeight > 0 ? (float)$maxHeight : 10;
            $finalWeight = $totalWeight > 0 ? (float)$totalWeight : 1;

            $calculatedVolume = ($finalLength * $finalWidth * $finalHeight) / 5000;
            $finalVolume = $calculatedVolume > 0.001 ? $calculatedVolume : 0.001;

            $placesItems[] = array(
                'quantity' => $totalQuantity > 0 ? $totalQuantity : 1,
                'weight' => $finalWeight,
                'insurance' => isset($params['insurance']) ? (float)$params['insurance'] : 0,
                'length' => $finalLength,
                'width' => $finalWidth,
                'height' => $finalHeight,
                'volume' => $finalVolume
            );
        }

        return $placesItems;
    }

    public function getBranchesByCity($cityId, $typeId) {
        $sql = "SELECT `branch_id`, 
                       `short_name`, 
                       `address`,
                       `latitude`, 
                       `longitude`
                FROM `" . DB_PREFIX . "meest2_branch`
                WHERE `city_id` = '" . $this->db->escape($cityId) . "'";

        if (!empty($typeId)) {
            if (is_array($typeId)) {
                $escaped_types = array_map(array($this->db, 'escape'), $typeId);
                $sql .= " AND `branch_type_id` IN ('" . implode("','", $escaped_types) . "')";
            } else {
                $sql .= " AND `branch_type_id` = '" . $this->db->escape($typeId) . "'";
            }
        }

        $sql .= " ORDER BY `short_name`";

        return $this->db->query($sql)->rows;
    }

    public function saveOrderShippingData($order_id, $data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest2_order_shipping_data` SET
            order_id = '" . (int)$order_id . "',
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            city_code = '" . $this->db->escape($data['city_code']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            address_code = '" . $this->db->escape($data['address_code']) . "',
            building = '" . $this->db->escape(isset($data['building']) ? $data['building'] : '') . "',
            region_code = '" . $this->db->escape($data['region_code']) . "'
            ON DUPLICATE KEY UPDATE
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            city_code = '" . $this->db->escape($data['city_code']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            address_code = '" . $this->db->escape($data['address_code']) . "',
            building = '" . $this->db->escape(isset($data['building']) ? $data['building'] : '') . "',
            region_code = '" . $this->db->escape($data['region_code']) . "'
        ");
    }

}
