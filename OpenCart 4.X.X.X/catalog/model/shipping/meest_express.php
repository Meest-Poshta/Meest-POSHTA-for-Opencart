<?php

namespace Opencart\Catalog\Model\Extension\MeestExpress\Shipping;

use Exception;
use Opencart\System\Engine\Model;

class MeestExpress extends Model
{
    private $extension = 'meest_express';

    private $poshtomatType = '23c4f6c1-b1bb-49f7-ad96-9b014206fe8e';

    private $warehouseType = array(
        '91cb8fae-6a94-4b1d-b048-dc89499e2fe5',
        '0c1b0075-cd44-49d1-bd3e-094da9645919',
        'acabaf4b-df2e-11eb-80d5-000c29800ae7',
        'ac82815e-10fe-4eb7-809a-c34be4553213',
    );

    public function getResults($typeId, $cityName, $search)
    {
        $sql = "SELECT br_id, num, street_ua as street,
            street_number, type_public_ua as type_public, `lng`, `lat` FROM "
            . DB_PREFIX . "meest_express_branch WHERE `city_ua`='$cityName'";

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
            "meest_express_branch WHERE (`city_ua` LIKE '" . $this->db->escape($search) . "%' OR `city_ru` LIKE '"
            . $this->db->escape($search) . "%' OR `city_en` LIKE '" . $this->db->escape($search) . "%')";

        if (is_array($typeId)) {
            $sql .= " AND type_id IN ('" . implode("','", $typeId) . "')";
        } else {
            $sql .= " AND type_id = '" . $this->db->escape($typeId) . "'";
        }

        $sql .= " ORDER BY `city_ua` LIMIT 20";

        return $this->db->query($sql)->rows;
    }

    public function getCities($region_id, $search = '')
    {
        $sql = "SELECT
            c.`city_id` AS id,
            c.`type_ua` AS type,
            c.`name_ua` AS name,
            r.`region_name_ua` AS region,
            d.`district_ua` AS district
        FROM `" . DB_PREFIX . "meest_express_cities` c
        LEFT JOIN `" . DB_PREFIX . "meest_express_regions` r ON c.`region_id` = r.`region_id`
        LEFT JOIN `" . DB_PREFIX . "meest_express_district` d ON d.`district_id` = c.`district_id`
        WHERE 1";

        if ($region_id) {
            $region_query = $this->db->query("SELECT `region_id`
                                      FROM `" . DB_PREFIX . "meest_express_regions`
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

        $sql .= " GROUP BY c.`city_id`";

        $sql .= " ORDER BY
            CASE
                WHEN c.`name_ua` LIKE '" . $this->db->escape($search) . "' THEN 1
                WHEN c.`name_ua` LIKE '" . $this->db->escape($search) . "%' THEN 2
                ELSE 3
            END,
            CHAR_LENGTH(c.`name_ua`),
            c.`name_ua`";

        $sql .= " LIMIT 50";

        $results = $this->db->query($sql)->rows;

        // Форматируем данные для автокомплита
        $formatted = array();
        foreach ($results as $result) {
            $description = $result['type'] . ' ' . $result['name'];
            if (!empty($result['region'])) {
                $description .= ', ' . $result['region'];
            }

            $formatted[] = array(
                'id' => $result['id'],
                'description' => $description,
                'name' => $result['name'],
                'type' => $result['type'],
                'region' => $result['region'],
                'district' => $result['district']
            );
        }

        return $formatted;
    }

    public function getBranches($city_id, $search = '', $types = null)
    {
        $city_id = urldecode($city_id);
        $search = urldecode($search);

        $sql = "SELECT `branch_id` AS id,
                   `short_name` AS description,
                   `address_more_information`
            FROM `" . DB_PREFIX . "meest_express_branch`
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

    public function getStreets($city_id, $search = '')
    {
        $city_id = urldecode($city_id);
        $search = urldecode($search);

        $sql = "SELECT `street_id` AS id, CONCAT(`type_ua`, ' ', `name_ua`) AS description, CONCAT(`type_ua`, ' ', `name_ua`) AS full_description
            FROM `" . DB_PREFIX . "meest_express_streets`
            WHERE `city_id` = '" . $this->db->escape($city_id) . "'";

        if ($search) {
            $sql .= " AND (`name_ua` LIKE '" . $this->db->escape($search) . "%' OR `name_ru` LIKE '" . $this->db->escape($search) . "%')";
        }

        $sql .= " ORDER BY `name_ua` LIMIT 50";

        return $this->db->query($sql)->rows;
    }

    public function getQuote($address)
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
                                    WHERE geo_zone_id = '" . (int)$this->config->get('free_geo_zone_id') . "' 
                                    AND country_id = '" . (int)$address['country_id'] . "' 
                                    AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('shipping_meest_express_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $image = 'catalog/MeestExpress/meest_logo.svg';
        $image = $this->config->get('config_url') . 'image/' . $image;

        $image_html = "<img style='width:70px' src='$image' alt='{$this->language->get('text_title')}' title='{$this->language->get('text_title')}' />";
        $image_html_service = "<img style='width:20px' src='$image' alt='' title='' />";

        if ($this->cart->getSubTotal() < $this->config->get('free_total')) {
            $status = false;
        }

        if (!$status) {
            return [];
        }

        $method_data = [];
        $quote_data = [];

        $meestSessionData = isset($this->session->data['meest_data']) ? $this->session->data['meest_data'] : [];
        $cityUUID       = isset($meestSessionData['city_UUID']) ? $meestSessionData['city_UUID'] : '';
        $address1UUID   = isset($meestSessionData['address_1_UUID']) ? $meestSessionData['address_1_UUID'] : '';

        $shippingMethod = isset($meestSessionData['shipping_method']) ? $meestSessionData['shipping_method'] : '';

        $shipping_meest_service = $this->config->get('shipping_meest_express_service');

        $checkoutCode = (isset($this->request->get['route']) ? $this->request->get['route'] : '') === 'checkout/shipping_method';

        if ($shipping_meest_service) {
            $length = count($shipping_meest_service);
            $costs = $this->config->get('shipping_meest_express_cost');
            $errorMessage = '';
            $receiverMethod = str_replace('meest_express.', '', $shippingMethod);
            $enabledCalculationInCheckout = $this->config->get('shipping_meest_express_calculation_in_checkout');
            $data = [];

            if ($cityUUID && $address1UUID && $enabledCalculationInCheckout) {
                $cart_data = $this->prepareApiData($cityUUID, $address1UUID, $shippingMethod);

                $api_url = 'https://api.meest.com/v3.0/openAPI/calculate';
                $api_response = $this->callMeestAPIv3($api_url, $cart_data);

                if ($api_response) {
                    if (isset($api_response['status']) && $api_response['status'] === 'error') {
                        $message        = isset($api_response['info']['message']) ? $api_response['info']['message'] : '';
                        $messageDetails = isset($api_response['info']['messageDetails']) ? $api_response['info']['messageDetails'] : '';
                        $messageForShowMeestMethods = $this->language->get('text_error_shipping_checkout');
                        $errorMessage = trim($message . ' ' . $messageDetails . "<br>" . $messageForShowMeestMethods);

                        if ($errorMessage === '') {
                            $errorMessage = 'Error calculating shipping.';
                        }

                        unset($this->session->data['meest_data']['address_1_UUID']);

                    } elseif (isset($api_response['result']['costServices'])) {
                        $costs[$receiverMethod] = $api_response['result']['costServices'];
                        $costs['def'] = $api_response['result']['costServices'];
                    } else {
                        $errorMessage = 'Incorrect response from the delivery service.';
                        foreach ($shipping_meest_service as $service) {
                            $costs[$service] = 0;
                        }
                    }
                } else {
                    $errorMessage = 'No response from shipping API.';
                    foreach ($shipping_meest_service as $service) {
                        $costs[$service] = 0;
                    }
                }
            }

            // Check free shipping
            $freeEnabled = (bool)$this->config->get('shipping_meest_express_free_shipping_enabled');
            $freeThreshold = (float)$this->config->get('shipping_meest_express_free_shipping_threshold');
            $isFreeShipping = false;

            if ($freeEnabled && $freeThreshold > 0) {
                $orderTotalBase = (float)$this->cart->getSubTotal();
                $sessionCurrency = $this->session->data['currency'] ?? $this->config->get('config_currency');
                $baseCurrency = $this->config->get('config_currency');
                $orderTotalInSessionCurrency = $orderTotalBase;

                if ($baseCurrency !== $sessionCurrency) {
                    $orderTotalInSessionCurrency = $this->currency->convert($orderTotalBase, $baseCurrency, $sessionCurrency);
                }

                $orderTotalUAH = ($sessionCurrency === 'UAH') ? $orderTotalInSessionCurrency : $this->currency->convert($orderTotalInSessionCurrency, $sessionCurrency, 'UAH');

                if ($orderTotalUAH >= $freeThreshold) {
                    $isFreeShipping = true;
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
                $serviceCost = $isFreeShipping ? 0 : (!empty($costs[$service]) ? $costs[$service] : 0);

                // Calculate cost with tax
                $costWithTax = $this->tax->calculate(
                    $serviceCost,
                    (int)$this->config->get('shipping_meest_express_tax_class_id'),
                    $this->config->get('config_tax')
                );

                // Format price
                $formattedPrice = empty($serviceCost)
                    ? ''
                    : $this->currency->format($costWithTax, $this->session->data['currency']);

                // Create price HTML with class for JavaScript updates

                if($this->config->get('shipping_meest_express_customer_shipping_pay')){
                    $priceHtml = '<span class="meest-express-shipping-price" style="color:red" id="meest2-price-' . $service
                        . '" data-service="' . $service . '" data-cost="' . $serviceCost . '" 
                    data-cost-with-tax="' . $costWithTax . '">'.$this->language->get('text_meest2_customer_shipping_pay').'!('.$formattedPrice.')</span>';
                    $serviceCost = 0;
                    $costWithTax = 0;
                } else {
                    $priceHtml = '<span class="meest-express-shipping-price" id="meest2-price-' . $service . '" 
                    data-service="' . $service . '" data-cost="' . $serviceCost . '"
                     data-cost-with-tax="' . $costWithTax . '">' . $formattedPrice . '</span>';
                }
                $quote_data[$service] = [
                    'code'         => 'meest_express.' . $service,
                    'name'        => $image_html_service . " Meest: " . $this->language->get('text_title_' . $service),
                    'cost'         => $serviceCost,
                    'tax_class_id' => (int)$this->config->get('shipping_meest_express_tax_class_id'),
                    'text'         => $priceHtml,
                    'error' => $errorData
                ];

            }

            if($checkoutCode && !empty($errorMessage)) {
                $method_data = [
                    'code' => $this->extension,
                    'name' => $image_html . $this->language->get('text_title'),
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get('shipping_meest_express_sort_order'),
                    'error' => $errorMessage
                ];
            } else {
                $method_data = [
                    'code' => $this->extension,
                    'name' => $image_html . $this->language->get('text_title'),
                    'quote' => $quote_data,
                    'sort_order' => $this->config->get('shipping_meest_express_sort_order'),
                    'error' => false
                ];
            }
        }

        return $method_data;
    }

    public function callMeestAPIv3($url, $data)
    {
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

    public function prepareApiData($recipientCityUUID, $recipientAddress1UUID, $shippingMethod)
    {
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

        $receiverMethod = str_replace('meest_express.', '', $shippingMethod);
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
                'branchID' => $this->config->get('shipping_meest_express_sender_branch'),
                'cityID'   => $this->config->get('shipping_meest_express_sender_city'),
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

    private function auth()
    {
        $url = 'https://api.meest.com/v3.0/openAPI/auth';

        $authMode = $this->config->get('shipping_meest_express_auth_mode');
        if($authMode === "api_key"){
            return $this->config->get('shipping_meest_express_api_key');
        } elseif ($authMode === "default"){
            if($this->config->get('shipping_meest_express_login') && $this->config->get('shipping_meest_express_password')){
                $data = json_encode([
                    'username' => $this->config->get('shipping_meest_express_login'),
                    'password' => $this->config->get('shipping_meest_express_password')
                ]);
            } else {
                $this->session->data['error_warning_meest'] = 'Problems with getting a token, enter your login and password';
                $this->response->redirect($this->url->link('extension/MeestExpress/shipping/meest_express', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
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

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['status'] === 'OK') {
            return $responseData['result']['token'];
        } else {
            return $responseData;
        }
    }

    public function getShippingMethod()
    {
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

    public function getMeestExpressCities($region = '', $search = '')
    {
        $regionId = $this->getRegionIdByZone($region);

        if (!$regionId) {
            $sql = "
                SELECT `name_ua`, `type_ua`
                FROM `" . DB_PREFIX . "meest_express_cities`
                WHERE `type_ua` = 'місто'
            ";
        } else {
            $escaped_region = $this->db->escape($regionId);
            $sql = "
                SELECT `name_ua`, `type_ua`
                FROM `" . DB_PREFIX . "meest_express_cities`
                WHERE `region_id` = '" . $escaped_region . "'
            ";
        }

        $sql .= " ORDER BY `name_ua`";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getBranchesByCityMeest($city_name, $types)
    {
        if ($types == 'postomat') {
            $types = $this->poshtomatType;
        } else {
            $types = $this->warehouseType;
        }

        $sql = "
            SELECT `short_name`, `branch_type`
            FROM `" . DB_PREFIX . "meest_express_branch`
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

    public function getRegionIdByZone($zone_id)
    {
        $zone_id_escaped = $this->db->escape($zone_id);

        $query = $this->db->query("
            SELECT `region_id`
            FROM `" . DB_PREFIX . "meest_express_regions`
            WHERE `zone_id` = '" . $zone_id_escaped . "'
            LIMIT 1
        ");

        if (isset($query->row['region_id'])) {
            return $query->row['region_id'];
        } else {
            return null;
        }
    }

    public function getRegions()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_regions ORDER BY region_name_ua ASC");
        return $query->rows;
    }

    public function getCitiesByRegion($region_id)
    {
        $query = $this->db->query("
            SELECT DISTINCT city_id, name_ua, name_ru, type_ua, district_id, region_id, koatuu, delivery_in_city 
            FROM " . DB_PREFIX . "meest_express_cities 
            WHERE region_id = '" . $this->db->escape($region_id) . "' 
            GROUP BY city_id
            ORDER BY name_ua ASC
        ");
        return $query->rows;
    }

    // ========== МЕТОДЫ ДЛЯ CRON ИМПОРТА ==========

    public function getRegion(string $region_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "meest_express_regions` WHERE `region_id` = '" . $this->db->escape($region_id) . "'");
        return $query->row;
    }

    public function addRegion($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_regions` SET
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
        ");
    }

    public function editRegion($region_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest_express_regions` SET
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
            WHERE `region_id` = '" . $this->db->escape($region_id) . "'
        ");
    }

    public function getAllCities() {
        $query = $this->db->query("SELECT city_id FROM " . DB_PREFIX . "meest_express_cities");
        $cities = [];
        foreach ($query->rows as $row) {
            $cities[$row['city_id']] = $row['city_id'];
        }
        return $cities;
    }

    public function bulkInsertCities(array $data): void {
        if (empty($data)) return;

        $values = [];
        foreach ($data as $row) {
            $values[] = "('" . $this->db->escape($row['city_id']) . "', '" . $this->db->escape($row['name_ua']) . "', '" . $this->db->escape($row['name_ru']) . "', '" . $this->db->escape($row['type_ua']) . "', '" . $this->db->escape($row['district_id']) . "', '" . $this->db->escape($row['region_id']) . "', '" . $this->db->escape($row['koatuu']) . "', '" . (int)$row['delivery_in_city'] . "')";
        }

        $sql = "INSERT INTO `" . DB_PREFIX . "meest_express_cities` (`city_id`, `name_ua`, `name_ru`, `type_ua`, `district_id`, `region_id`, `koatuu`, `delivery_in_city`)
            VALUES " . implode(',', $values);
        $this->db->query($sql);
    }

    public function bulkUpdateCities($data) {
        foreach ($data as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "meest_express_cities SET
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

    public function saveBranchesBatch(array $branches): bool {
        if (empty($branches)) {
            return false;
        }

        // OpenCart 4.0 использует $this->db->query() как и раньше
        foreach ($branches as $branch) {
            if (!isset($branch['id'])) {
                continue;
            }

            $branch_id = (int)$branch['id'];
            $name = $this->db->escape($branch['name'] ?? '');
            $city = $this->db->escape($branch['city'] ?? '');
            $address = $this->db->escape($branch['address'] ?? '');
            $latitude = isset($branch['latitude']) ? (float)$branch['latitude'] : 0;
            $longitude = isset($branch['longitude']) ? (float)$branch['longitude'] : 0;
            $updated_at = date('Y-m-d H:i:s');

            // Проверка — есть ли уже такое отделение
            $query = $this->db->query("SELECT branch_id FROM `" . DB_PREFIX . "novapost_branch` WHERE `branch_id` = '" . $branch_id . "' LIMIT 1");

            if ($query->num_rows) {
                // Обновляем данные
                $this->db->query("
                UPDATE `" . DB_PREFIX . "novapost_branch`
                SET 
                    `name` = '" . $name . "',
                    `city` = '" . $city . "',
                    `address` = '" . $address . "',
                    `latitude` = '" . $latitude . "',
                    `longitude` = '" . $longitude . "',
                    `updated_at` = '" . $updated_at . "'
                WHERE `branch_id` = '" . $branch_id . "'
            ");
            } else {
                // Вставляем новое отделение
                $this->db->query("
                INSERT INTO `" . DB_PREFIX . "novapost_branch`
                SET 
                    `branch_id` = '" . $branch_id . "',
                    `name` = '" . $name . "',
                    `city` = '" . $city . "',
                    `address` = '" . $address . "',
                    `latitude` = '" . $latitude . "',
                    `longitude` = '" . $longitude . "',
                    `created_at` = '" . $updated_at . "',
                    `updated_at` = '" . $updated_at . "'
            ");
            }
        }

        return true;
    }


    public function importStreets(): bool {
        $url = 'https://meest-group.com/media/location/streets.txt';
        $handle = @fopen($url, 'r');
        if (!$handle) return false;

        $existingStreetIds = $this->getAllStreetIds();
        $existingStreetIds = array_flip($existingStreetIds);

        $insertData = [];
        $updateData = [];

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1251');
            if ($line === '') continue;

            $fields = explode(';', $line);
            if (count($fields) < 12) continue;

            $addressData = [
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
            ];

            if (!isset($existingStreetIds[$addressData['street_id']])) {
                $insertData[] = $addressData;
            } else {
                $updateData[] = $addressData;
            }

            if (count($insertData) >= 1000) {
                $this->bulkInsertStreets($insertData);
                $insertData = [];
            }
            if (count($updateData) >= 1000) {
                $this->bulkUpdateStreets($updateData);
                $updateData = [];
            }
        }

        fclose($handle);

        if (!empty($insertData)) $this->bulkInsertStreets($insertData);
        if (!empty($updateData)) $this->bulkUpdateStreets($updateData);

        return true;
    }

    public function getAllStreetIds(): array {
        $query = $this->db->query("SELECT `street_id` FROM `" . DB_PREFIX . "meest_express_streets`");
        return array_column($query->rows, 'street_id');
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
            $this->db->query("INSERT INTO " . DB_PREFIX . "meest_express_streets (
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
            $this->db->query("UPDATE " . DB_PREFIX . "meest_express_streets SET
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
     * Розрахунок вартості доставки через API Meest
     *
     * @param array $params Параметри для розрахунку
     * @return array|false Результат розрахунку або false при помилці
     */
    public function calculateShippingCost($params) {
        try {
            // Формуємо масив даних для API
            $calculationData = $this->prepareCalculationData($params);


            // Викликаємо API для розрахунку
            $apiUrl = 'https://api.meest.com/v3.0/openAPI/calculate';
            $response = $this->callMeestAPIv3(
                $apiUrl,
                json_encode($calculationData, JSON_UNESCAPED_UNICODE)
            );




            if ($response && isset($response['status']) && $response['status'] === 'OK') {
                return $response['result'];
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Підготовка масиву даних для API calculate
     *
     * @param array $params Вхідні параметри
     * @return array Масив даних для відправки на API
     */
    public function prepareCalculationData($params) {
        // Дата відправки (поточна дата або з параметрів)
        $sendingDate = isset($params['sending_date'])
            ? $params['sending_date']
            : date('d.m.Y');

        // ID контракту з конфігурації
        $contractID = isset($params['sender_contract_id'])
            ? $params['sender_contract_id']
            : $this->config->get('shipping_meest_express_sender_contract_id');

        // Промокод (опціонально)
        $promocode = isset($params['promocode']) ? $params['promocode'] : '';

        // Дані відправника
        $sender = $this->prepareSenderData($params);

        // Дані отримувача
        $receiver = $this->prepareReceiverData($params);

        // Місця (товари)
        $placesItems = $this->preparePlacesItems($params);

        // Спеціальні умови (опціонально)
        $specConditionsItems = array();
        if (isset($params['spec_conditions']) && is_array($params['spec_conditions'])) {
            foreach ($params['spec_conditions'] as $conditionID) {
                $specConditionsItems[] = array('conditionID' => $conditionID);
            }
        }

        // Формуємо фінальний масив
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

        // Додаємо опціональні поля
        if (!empty($promocode)) {
            $data['promocode'] = $promocode;
        }

        if (!empty($specConditionsItems)) {
            $data['specConditionsItems'] = $specConditionsItems;
        }

        return $data;
    }

    /**
     * Підготовка даних відправника
     *
     * @param array $params Вхідні параметри
     * @return array Дані відправника
     */
    private function prepareSenderData($params) {
        $sender = array();

        // Тип сервісу відправника (Branch або Door)
        $senderService = isset($params['sender_service'])
            ? $params['sender_service']
            : 'Branch';

        $sender['service'] = $senderService;

        if ($senderService === 'Door') {
            $sender['addressID'] = isset($params['sender_address_id'])
                ? $params['sender_address_id']
                : $this->config->get('shipping_meest_express_sender_address');

            if (isset($params['sender_building'])) {
                $sender['building'] = $params['sender_building'];
            }

            if (isset($params['sender_floor'])) {
                $sender['floor'] = (int)$params['sender_floor'];
            }

            if (isset($params['sender_flat'])) {
                $sender['flat'] = $params['sender_flat'];
            }

        } else {
            // Відправка з відділення
            $sender['branchID'] = isset($params['sender_branch_id'])
                ? $params['sender_branch_id']
                : $this->config->get('shipping_meest_express_sender_branch');
        }

        $sender['name'] = 'test';
        $sender['phone'] = '';

        return $sender;
    }

    /**
     * Підготовка даних отримувача
     *
     * @param array $params Вхідні параметри
     * @return array Дані отримувача
     */
    private function prepareReceiverData($params) {
        $receiver = array();

        // Тип сервісу отримувача (Branch або Door)
        if (isset($params['receiver_service'])) {
            $receiver['service'] = $params['receiver_service'];
        }



        if (isset($params['receiver_service']) && $params['receiver_service'] === 'Door') {
            // Доставка до дверей отримувача
            if (isset($params['receiver_address_id'])) {
                $receiver['addressID'] = $params['receiver_address_id'];
            }

            if (isset($params['receiver_city_id'])) {
                $receiver['cityID'] = $params['receiver_city_id'];
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

    /**
     * Підготовка місць (товарів) для відправки
     *
     * @param array $params Вхідні параметри
     * @return array Масив місць
     */
    private function preparePlacesItems($params) {
        $placesItems = array();

        // Якщо передано готові місця
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

                // Розраховуємо об'єм (length * width * height) / 5000 для м³
                $item['volume'] = ($item['length'] * $item['width'] * $item['height']) / 5000;

                // Додаткові параметри (для шин тощо)
                if (isset($place['wheels'])) {
                    $item['wheels'] = $place['wheels'];
                }

                $placesItems[] = $item;
            }
        } else {
            // Формуємо місця з товарів кошика
            $products = $this->cart->getProducts();

            $totalQuantity = 0;
            $totalWeight = 0;
            $maxLength = 0;
            $maxWidth = 0;
            $maxHeight = 0;

            foreach ($products as $product) {
                // Конвертуємо розміри в float
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

            // Використовуємо фінальні значення як float
            $finalLength = $maxLength > 0 ? (float)$maxLength : 10;
            $finalWidth = $maxWidth > 0 ? (float)$maxWidth : 10;
            $finalHeight = $maxHeight > 0 ? (float)$maxHeight : 10;
            $finalWeight = $totalWeight > 0 ? (float)$totalWeight : 1;

            // Розраховуємо об'єм в м³ (розміри в см, тому ділимо на 5000)
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

    /**
     * Получение отделений города с координатами для отображения на карте
     *
     * @param string $cityId UUID города
     * @param string|array $typeId UUID типа отделения (или массив UUID)
     * @return array Массив отделений с координатами
     */
    public function getBranchesByCity($cityId, $typeId) {
        $sql = "SELECT 
                    branch_id,
                    short_name,
                    address_descr_ua,
                    latitude,
                    longitude,
                    city_ua,
                    working_hours
                FROM " . DB_PREFIX . "meest_express_branch 
                WHERE city_id = '" . $this->db->escape($cityId) . "'";

        if (is_array($typeId)) {
            $sql .= " AND branch_type_id IN ('" . implode("','", array_map([$this->db, 'escape'], $typeId)) . "')";
        } else {
            $sql .= " AND branch_type_id = '" . $this->db->escape($typeId) . "'";
        }

        $sql .= " AND latitude IS NOT NULL AND longitude IS NOT NULL";
        $sql .= " ORDER BY short_name";

        return $this->db->query($sql)->rows;
    }

    public function saveOrderShippingData($order_id, $data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_order_shipping_data` SET
            order_id = '" . (int)$order_id . "',
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            city_code = '" . $this->db->escape($data['city_code']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            address_code = '" . $this->db->escape($data['address_code']) . "',
            building = '" . $this->db->escape(isset($data['building']) ? $data['building'] : '') . "',
            region_code = '" . $this->db->escape($data['region_code']) . "',
            customer_pay = ".(int)$this->config->get('shipping_meest_express_customer_shipping_pay')."

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
