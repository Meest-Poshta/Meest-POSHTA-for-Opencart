<?php

namespace Opencart\Catalog\Controller\Extension\MeestExpress\Shipping;

use Opencart\System\Engine\Controller;

class MeestExpress extends Controller
{
    private $poshtomatType = '23c4f6c1-b1bb-49f7-ad96-9b014206fe8e';
    private $warehouseType = array(
        '91cb8fae-6a94-4b1d-b048-dc89499e2fe5',
        '0c1b0075-cd44-49d1-bd3e-094da9645919',
        'acabaf4b-df2e-11eb-80d5-000c29800ae7',
        'ac82815e-10fe-4eb7-809a-c34be4553213',
        'ac1ec893-efdc-4e92-9f50-400d1ffcafc6',
        '302d4b0d-1802-11ef-80c5-000c2961d091',
    );

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/MeestExpress/shipping/meest_express');
    }

    public function index()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');

        // Pass all language variables explicitly
        $data['text_title'] = $this->language->get('text_title');
        $data['text_description'] = $this->language->get('text_description');
        $data['text_country'] = $this->language->get('text_country');
        $data['text_region_state'] = $this->language->get('text_region_state');
        $data['text_city'] = $this->language->get('text_city');
        $data['text_address'] = $this->language->get('text_address');
        $data['text_address_input'] = $this->language->get('text_address_input');
        $data['text_address_input_placeholder'] = $this->language->get('text_address_input_placeholder');
        $data['text_address_details'] = $this->language->get('text_address_details');
        $data['text_address_placeholder'] = $this->language->get('text_address_placeholder');
        $data['text_warehouse'] = $this->language->get('text_warehouse');
        $data['text_postomat'] = $this->language->get('text_postomat');
        $data['text_street'] = $this->language->get('text_street');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_searching'] = $this->language->get('text_searching');
        $data['text_fill_required_fields'] = $this->language->get('text_fill_required_fields');

        return $this->load->view('extension/MeestExpress/shipping/meest_express', $data);
    }

    public function save()
    {
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $address = isset($this->request->post['address']) ? $this->request->post['address'] : '';
            $house = isset($this->request->post['house']) ? $this->request->post['house'] : '';
            $flat = isset($this->request->post['flat']) ? $this->request->post['flat'] : '';

            $full_address = $address;
            if (!empty($house)) {
                $full_address .= ' ' . $this->language->get('text_meest_express_house') . ' ' . $house;
            }
            if (!empty($flat)) {
                $full_address .= ' ' . $this->language->get('text_meest_express_flat') . ' ' . $flat;
            }

            $this->session->data['shipping_address']['address_1'] = $full_address;

            if (isset($this->session->data['simple']['shipping_address'])) {
                $this->session->data['simple']['shipping_address']['address_1'] = $full_address;
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'status' => 'success',
                'session' => $this->session->data['shipping_address']['address_1']
            ]));
        }
    }

    private function place($branch)
    {
        $place = '';

        if (!empty($branch['lat'])) {
            $lng = $branch['lat'];
            $d = floor($lng);
            $m = floor(($lng - $d) * 60);
            $s = round(($lng - $d - $m/60) * 3600, 2);
            $place = 'place/' . $d . '%C2%B0' . $m . "'" . $s . '%22N+';
            $lat = $branch['lng'];
            $d = floor($lat);
            $m = floor(($lat - $d) * 60);
            $s = round(($lat - $d - $m/60) * 3600, 2);

            $place .= $d . '%C2%B0' . $m . "'" . $s . '%22E';
        }

        return $place;
    }

    private function getBranchesOld($typeId, $name)
    {
        $branches = $this
            ->model_extension_MeestExpress_shipping_meest_express
            ->getResults(
                $typeId,
                $this->request->get['city_name'],
                trim($this->request->get['filter_name'])
            );
        $name = mb_convert_case($name, MB_CASE_TITLE);
        $result = [];

        foreach ($branches as $branch) {
            $place = $this->place($branch);
            $result[] = [
                'br_id'      => $branch['br_id'],
                'name'       => sprintf($name.' № %s, %s, %s', $branch['num'], $branch['street'], $branch['street_number']),
                'type'       => $branch['type_public'],
                'address'    => sprintf($name.' № %s, %s, %s', $branch['num'], $branch['street'], $branch['street_number']),
                'anyfield'   => $place
            ];
        }

        return $result;
    }

    private function getCity($typeId, $cityName)
    {
        $cities = $this->model_extension_MeestExpress_shipping_meest_express->getCity($typeId, $cityName);
        $result = [];

        foreach ($cities as $city) {
            $regionToLower = mb_convert_case($city['region_ua'], MB_CASE_LOWER);
            $regionToUpperTitle = mb_convert_case($regionToLower, MB_CASE_TITLE);
            $strFormat = $this->language->get('responce_search_city_format_with_district');

            if ($city['district_ua'] === $city['city_ua']) {
                $strFormat = $this->language->get('responce_search_city_format_without_district');
            }

            $result[] = [
                'place' => sprintf(
                    $strFormat,
                    $regionToUpperTitle,
                    $city['district_ua'],
                    $city['city_ua']
                ),
                'city' => $city['city_ua'],
                'city_id' => $city['city_id'],
            ];
        }

        return $result;
    }

    public function getMeestData()
    {
        $json = [];

        $this->load->model('extension/MeestExpress/shipping/meest_express');

        if (isset($this->request->post['action'])) {
            $action = $this->request->post['action'];
        } else {
            $action = '';
        }

        if (isset($this->request->post['filter'])) {
            $filter = trim($this->request->post['filter']);
        } else {
            $filter = '';
        }

        if (isset($this->request->post['search'])) {
            $search = trim($this->request->post['search']);
        } else {
            $search = '';
        }

        if ($action === 'getCities') {
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getCities($filter, $search);
        } elseif ($action === 'getBranches') {
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getBranches($filter, $search, $this->warehouseType);
        } elseif ($action === 'getStreets') {
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getStreets($filter, $search);
        } elseif ($action === 'getPoshtomat') {
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getBranches($filter, $search, $this->poshtomatType);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveUuid()
    {
        $enabledCalculationInCheckout = $this->config->get('shipping_meest_express_calculation_in_checkout');

        if ($enabledCalculationInCheckout) {
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $this->session->data['meest_data']['city_UUID'] = isset($this->request->post['cityUuid']) ? $this->request->post['cityUuid'] : '';
                $this->session->data['meest_data']['address_1_UUID'] = isset($this->request->post['addressUuid']) ? $this->request->post['addressUuid'] : '';
                $this->session->data['meest_data']['shipping_method'] = isset($this->request->post['shippingMethod']) ? $this->request->post['shippingMethod'] : '';
            }
        } else {
            unset($this->session->data['meest_data']['city_UUID']);
            unset($this->session->data['meest_data']['address_1_UUID']);
            unset($this->session->data['meest_data']['shipping_method']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'status' => 'success',
        ]));
    }

    public function getRegions()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $regions = $this->model_extension_MeestExpress_shipping_meest_express->getRegions();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($regions));
    }

    public function getCitiesByRegion()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $region_id = isset($this->request->get['region_id']) ? $this->request->get['region_id'] : '';

        $cities = [];
        if ($region_id) {
            $cities = $this->model_extension_MeestExpress_shipping_meest_express->getCitiesByRegion($region_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($cities));
    }

    public function getBranches()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $city_id = isset($this->request->get['city_id']) ? $this->request->get['city_id'] : '';
        $search = isset($this->request->get['search']) ? $this->request->get['search'] : '';
        $type = isset($this->request->get['type']) ? $this->request->get['type'] : 'warehouse';

        $branches = [];
        if ($city_id) {
            // Branch types UUIDs
            $warehouseTypes = [
                '91cb8fae-6a94-4b1d-b048-dc89499e2fe5',
                '0c1b0075-cd44-49d1-bd3e-094da9645919',
                'acabaf4b-df2e-11eb-80d5-000c29800ae7',
                'ac82815e-10fe-4eb7-809a-c34be4553213',
            ];
            $poshtomatType = '23c4f6c1-b1bb-49f7-ad96-9b014206fe8e';

            $types = ($type === 'postomat') ? $poshtomatType : $warehouseTypes;

            $branches = $this->model_extension_MeestExpress_shipping_meest_express->getBranches($city_id, $search, $types);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($branches));
    }

    public function getStreets()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $city_id = isset($this->request->get['city_id']) ? $this->request->get['city_id'] : '';
        $search = isset($this->request->get['search']) ? $this->request->get['search'] : '';

        $streets = [];
        if ($city_id) {
            $streets = $this->model_extension_MeestExpress_shipping_meest_express->getStreets($city_id, $search);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($streets));
    }

    public function getAddresses()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $city_id = isset($this->request->get['city_id']) ? $this->request->get['city_id'] : '';
        $search = isset($this->request->get['search']) ? $this->request->get['search'] : '';

        $addresses = [];
        if ($city_id) {
            $addresses = $this->model_extension_MeestExpress_shipping_meest_express->getStreets($city_id, $search);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($addresses));
    }

    public function saveMeestData()
    {

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->session->data['meest_data'] = [
                'region_id' => isset($this->request->post['region_id']) ? $this->request->post['region_id'] : '',
                'region_name' => isset($this->request->post['region_name']) ? $this->request->post['region_name'] : '',
                'city_id' => isset($this->request->post['city_id']) ? $this->request->post['city_id'] : '',
                'city_name' => isset($this->request->post['city_name']) ? $this->request->post['city_name'] : '',
                'city_UUID' => isset($this->request->post['city_id']) ? $this->request->post['city_id'] : '',
                'address' => isset($this->request->post['address']) ? $this->request->post['full_address'] : '',
                'address_id' => isset($this->request->post['address_id']) ? $this->request->post['address_id'] : '',
                'address_1_UUID' => isset($this->request->post['address_id']) ? $this->request->post['address_id'] : '',
                'shipping_method' => isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : ''
            ];

            // Also save to shipping_address for compatibility
            if (isset($this->session->data['shipping_address'])) {
                $this->session->data['shipping_address']['address_1'] = isset($this->request->post['full_address']) ? $this->request->post['full_address'] : '';
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'status' => 'success',
                'message' => 'Data saved successfully'
            ]));
        }
    }

    public function getBranchesWithCoordinates()
    {
        $json = [];

        $this->load->model('extension/MeestExpress/shipping/meest_express');

        if (isset($this->request->post['city_id']) && isset($this->request->post['type'])) {
            $cityId = $this->request->post['city_id'];
            $type = $this->request->post['type'];

            // Determine typeId based on type
            if ($type === 'postomat') {
                $typeId = $this->poshtomatType;
            } else {
                $typeId = $this->warehouseType;
            }

            // Get branches from database
            $branches = $this->model_extension_MeestExpress_shipping_meest_express->getBranchesByCity($cityId, $typeId);

            foreach ($branches as $branch) {
                $json[] = [
                    'id' => $branch['branch_id'] ?? '',
                    'description' => $branch['short_name'] ?? '',
                    'address' => $branch['short_name'] ?? '',
                    'latitude' => $branch['latitude'] ?? null,
                    'longitude' => $branch['longitude'] ?? null,
                ];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function calculateShippingCost()
    {
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json = [
                'success' => false,
                'error' => 'Invalid request method'
            ];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
            return;
        }

        try {
            $this->load->model('extension/MeestExpress/shipping/meest_express');

            // Get parameters from POST request
            $params = [];

            // Sender data
            if (isset($this->request->post['sender_contract_id'])) {
                $params['sender_contract_id'] = $this->request->post['sender_contract_id'];
            }
            if (isset($this->request->post['sender_branch_id'])) {
                $params['sender_branch_id'] = $this->request->post['sender_branch_id'];
            }
            if (isset($this->request->post['sender_city_id'])) {
                $params['sender_city_id'] = $this->request->post['sender_city_id'];
            }
            if (isset($this->request->post['sender_service'])) {
                $params['sender_service'] = $this->request->post['sender_service'];
            }

            // Receiver data
            if (isset($this->request->post['receiver_country_id'])) {
                $params['receiver_country_id'] = $this->request->post['receiver_country_id'];
            }
            if (isset($this->request->post['receiver_city_id'])) {
                $params['receiver_city_id'] = $this->request->post['receiver_city_id'];
            }
            if (isset($this->request->post['receiver_service'])) {
                $params['receiver_service'] = $this->request->post['receiver_service'];
            }
            if (isset($this->request->post['receiver_branch_id'])) {
                $params['receiver_branch_id'] = $this->request->post['receiver_branch_id'];
            }
            if (isset($this->request->post['receiver_address_id'])) {
                $params['receiver_address_id'] = $this->request->post['receiver_address_id'];
            }

            // Additional parameters
            if (isset($this->request->post['cod_amount'])) {
                $params['cod_amount'] = (float)$this->request->post['cod_amount'];
            }
            if (isset($this->request->post['insurance'])) {
                $params['insurance'] = (float)$this->request->post['insurance'];
            }

            // If insurance not provided - calculate order total in UAH
            if (!isset($params['insurance']) || (float)$params['insurance'] <= 0) {
                $orderTotalBase = (float)$this->cart->getSubTotal();
                $sessionCurrency = $this->session->data['currency'] ?? $this->config->get('config_currency');
                $baseCurrency = $this->config->get('config_currency');

                $orderTotalInSessionCurrency = $orderTotalBase;
                if ($baseCurrency !== $sessionCurrency) {
                    $orderTotalInSessionCurrency = $this->currency->convert($orderTotalBase, $baseCurrency, $sessionCurrency);
                }

                if ($sessionCurrency === 'UAH') {
                    $params['insurance'] = (float)$orderTotalInSessionCurrency;
                } else {
                    $params['insurance'] = (float)$this->currency->convert($orderTotalInSessionCurrency, $sessionCurrency, 'UAH');
                }
            }

            // Places (if provided)
            if (isset($this->request->post['places']) && is_array($this->request->post['places'])) {
                $params['places'] = $this->request->post['places'];
            }

            // Check free shipping
            $freeEnabled = (bool)$this->config->get('shipping_meest_express_free_shipping_enabled');
            $freeThreshold = (float)$this->config->get('shipping_meest_express_free_shipping_threshold');

            $orderTotalBase = (float)$this->cart->getSubTotal();
            $sessionCurrency = $this->session->data['currency'] ?? $this->config->get('config_currency');
            $baseCurrency = $this->config->get('config_currency');
            $orderTotalInSessionCurrency = $orderTotalBase;
            if ($baseCurrency !== $sessionCurrency) {
                $orderTotalInSessionCurrency = $this->currency->convert($orderTotalBase, $baseCurrency, $sessionCurrency);
            }
            $orderTotalUAH = ($sessionCurrency === 'UAH') ? $orderTotalInSessionCurrency : $this->currency->convert($orderTotalInSessionCurrency, $sessionCurrency, 'UAH');

            if ($freeEnabled && $freeThreshold > 0 && $orderTotalUAH >= $freeThreshold) {
                $json = [
                    'success' => true,
                    'data' => ['costServices' => 0]
                ];
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
                return;
            }

            // Call model method for calculation
            $result = $this->model_extension_MeestExpress_shipping_meest_express->calculateShippingCost($params);

            if ($result) {
                $json = [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                $json = [
                    'success' => false,
                    'error' => 'Failed to calculate shipping cost'
                ];
            }

        } catch (\Exception $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function saveMeestSessionData(): void {
        $json = ['success' => false];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (!isset($this->session->data['meest_data'])) {
                $this->session->data['meest_data'] = [];
            }

            if (isset($this->request->post['shipping_method'])) {
                $this->session->data['meest_data']['shipping_method'] = $this->request->post['shipping_method'];
            }

            if (isset($this->request->post['city_code'])) {
                $this->session->data['meest_data']['city_UUID'] = $this->request->post['city_code'];
            }

            if (isset($this->request->post['branch_code']) && !empty($this->request->post['branch_code'])) {
                $this->session->data['meest_data']['address_1_UUID'] = $this->request->post['branch_code'];
            }

            if (isset($this->request->post['address_code']) && !empty($this->request->post['address_code'])) {
                $this->session->data['meest_data']['address_1_UUID'] = $this->request->post['address_code'];
            }

            if (isset($this->request->post['region_code'])) {
                $this->session->data['meest_data']['region_code'] = $this->request->post['region_code'];
            }

            if (isset($this->request->post['building'])) {
                $this->session->data['meest_data']['building'] = $this->request->post['building'];
            }

            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveShippingData(): void {
        $json = ['success' => false];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $order_id = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;

            if ($order_id) {
                $this->load->model('extension/MeestExpress/shipping/meest_express');

                $data = [
                    'shipping_method' => isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : '',
                    'city_code' => isset($this->request->post['city_code']) ? $this->request->post['city_code'] : '',
                    'branch_code' => isset($this->request->post['branch_code']) ? $this->request->post['branch_code'] : '',
                    'address_code' => isset($this->request->post['address_code']) ? $this->request->post['address_code'] : '',
                    'building' => isset($this->request->post['building']) ? $this->request->post['building'] : '',
                    'region_code' => isset($this->request->post['region_code']) ? $this->request->post['region_code'] : ''
                ];

                $this->model_extension_meestexpress_shipping_meest_express->saveOrderShippingData($order_id, $data);
                $json['success'] = true;
            } else {
                $json['error'] = 'Order ID not found';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addAssets(&$route, &$data) {

        $current_route = isset($this->request->get['route']) ? $this->request->get['route'] : '';

        $allowed_routes = array(
            'checkout/checkout',
            'checkout/simplecheckout',
            'checkout/quick_checkout',
            'checkout/ajax_quick_checkout',
            'checkout/uni_checkout',
            'extension/checkout/checkout',
            'extension/d_quickcheckout/confirm',
            'extension/d_ajax_checkout/checkout',
            'extension/checkout/oct_fastorder',
            'journal3/checkout',
            'checkout/buy',
            'extension/quickcheckout/checkout'
        );
        
        if (!in_array($current_route, $allowed_routes)) {
            return;
        }

        $this->load->language('extension/MeestExpress/shipping/meest_express');

        $this->document->addStyle(
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            'stylesheet'
        );

        $this->document->addScript(
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            'header'
        );

        $this->document->addScript('extension/MeestExpress/catalog/view/javascript/meest2/checkout.js?v=' . time());

        $this->document->addStyle('extension/MeestExpress/catalog/view/javascript/meest2/checkout.css');
    }

    public function afterAddOrder(&$route, &$args, &$output) {
        $order_id = $output;
        if ($order_id && isset($this->session->data['meest_data'])) {

            $shippingMethodMeest = $this->session->data['meest_data'];

            if (isset($shippingMethodMeest['shipping_method']) && strpos($shippingMethodMeest['shipping_method'], 'meest_express.') === 0) {

                $this->load->model('extension/MeestExpress/shipping/meest_express');

                $data = array(
                    'shipping_method' => $shippingMethodMeest['shipping_method'],
                    'city_code' => isset($shippingMethodMeest['city_UUID']) ? $shippingMethodMeest['city_UUID'] : '',
                    'branch_code' => isset($shippingMethodMeest['address_1_UUID']) && (strpos($shippingMethodMeest['shipping_method'], 'warehouse') !== false || strpos($shippingMethodMeest['shipping_method'], 'postomat') !== false) ? $shippingMethodMeest['address_1_UUID'] : '',
                    'address_code' => isset($shippingMethodMeest['address_1_UUID']) && (strpos($shippingMethodMeest['shipping_method'], 'courier') !== false || strpos($shippingMethodMeest['shipping_method'], 'door') !== false) ? $shippingMethodMeest['address_1_UUID'] : '',
                    'building' => isset($shippingMethodMeest['building']) ? $shippingMethodMeest['building'] : '',
                    'region_code' => isset($shippingMethodMeest['region_code']) ? $shippingMethodMeest['region_code'] : ''
                );

                $this->model_extension_MeestExpress_shipping_meest_express->saveOrderShippingData($order_id, $data);
            }
        }
    }
}
