<?php
class ControllerExtensionModuleMeest2 extends Controller {
    private  $poshtomatType = '23c4f6c1-b1bb-49f7-ad96-9b014206fe8e';
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
        $this->load->language('extension/shipping/meest2');
    }

    public function search(){
        $json = [];
        $this->load->model('extension/shipping/meest2');

        if (isset($this->request->get['service'])) {
            switch ($this->request->get['service']) {
                case 'postomat':
                    $typeId = $this->poshtomatType;
                    $json = $this->getBranches($typeId, $this->request->get['name']);

                    break;
                case 'warehouse':
                    $typeId = $this->warehouseType;
                    $json = $this->getBranches($typeId, $this->request->get['name']);

                    break;
                default:
                    $json[] = [
                        'br_id'      => 0,
                        'name'       => $this->language->get('text_meest2_branch_not_found'),
                        'type'       => 0,
                        'address'    => '',
                        'anyfield'   => 0
                    ];
            }
        }

        if (empty($json)) {
            $json[] = [
                'br_id'      => 0,
                'name'       => $this->language->get('text_meest2_branch_not_found'),
                'type'       => 0,
                'address'    => '',
                'anyfield'   => 0
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function searchCity(){
        $this->load->model('extension/shipping/meest2');
        $cityName = trim($this->request->get['filter_name']);

        switch ($this->request->get['service']) {
            case 'postomat':
                $typeId = $this->poshtomatType;
                break;
            case 'warehouse':
                $typeId = $this->warehouseType;
                break;
        }

        $cities = $this->getCity($typeId, $cityName);

        if (empty($cities)) {
            $cities = [[
                'place' => $this->language->get('text_meest2_city_not_found'),
                'city' => '',
                'city_id' => '',
            ]];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($cities));
    }

    public function searchCityWithDelivery()
    {
        $meest = new Meest($this->registry);
        $parameters = ['search_beginning' => trim($this->request->get['filter_name'])];
        $result = $meest->geo_localities($parameters);
        $cityWithDelivery = [];

        if (!empty($result['result'])) {
            $cityFilter = array_filter($result['result'], function ($item) {
                return $item['data']['is_delivery_in_city'] === false;
            });

            $cityWithDelivery = array_map(function ($item) {
                $data = $item['data'];
                $format = $this->language->get('responce_search_city_format_with_district');
                $regionToLower = mb_convert_case($data['reg'], MB_CASE_LOWER);
                $regionToUpperTitle = mb_convert_case($regionToLower, MB_CASE_TITLE);

                if ($data['dis'] === $data['n_ua']) {
                    $format = $this->language->get('responce_search_city_format_without_district');
                }

                return [
                    'city_id' => $data['city_id'],
                    'message' => null,
                    'address' => sprintf(
                        $format,
                        $regionToUpperTitle,
                        $data['dis'],
                        $data['n_ua']
                    ),
                ];
            }, $cityFilter);
        }

        if (empty($cityWithDelivery)) {
            $cityWithDelivery[] = [
                'city_id' => null,
                'message' => 'В цьому населеному пункті немає адресної доставки',
                'address' => '',
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array_slice($cityWithDelivery, 0, 20)));
    }

    public function searchStreets()
    {
        $meest = new Meest($this->registry);
        $parameters = [
            'city_id' => $this->request->get['city_id'],
            'search_beginning' => trim($this->request->get['filter_name'])
        ];

        $result = $meest->geo_streets($parameters);
        $streets = [];

        if (!empty($result['result'])) {
            $streets = array_map(function ($item) {
                $format = '%s %s';

                return [
                    'message' => null,
                    'street' => sprintf(
                        $format,
                        $item['t_ua'],
                        $item['ua']
                    ),
                ];
            }, $result['result']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array_slice($streets, 0, 20)));
    }
    public function index(){
        $data = $this->load->language('extension/module/meest2');

        return $this->load->view('extension/module/meest2', $data);
    }
    public function save(){
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->post['address'])) {
            $this->session->data['shipping_address']['address_1'] = $this->request->post['address'];
            $this->session->data['payment_address']['address_1'] = $this->request->post['address'];
            //Simple checkout
            $this->session->data['simple']['shipping_address']['address_1'] = $this->request->post['address'];
        }
    }
    private function place($branch){
        $place = '';

        if (!empty($branch['lat'])) {
            $lng = $branch['lat'];
            $d = floor( $lng);
            $m = floor(($lng - $d) * 60);
            $s = round(($lng - $d - $m/60) * 3600,2);
            $place = 'place/' . $d . '%C2%B0' . $m . "'" . $s . '%22N+';
            $lat = $branch['lng'];
            $d = floor( $lat);
            $m = floor(($lat - $d) * 60);
            $s = round(($lat - $d - $m/60) * 3600,2);

            $place .= $d . '%C2%B0' . $m . "'" . $s . '%22E';
        }

        return $place;
    }

    private function getBranches($typeId, $name)
    {
        $branches = $this
            ->model_extension_shipping_meest2
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
                'name'       => sprintf($name.' № %s, %s, %s',$branch['num'],$branch['street'],$branch['street_number']),
                'type'       => $branch['type_public'],
                'address'    => sprintf($name.' № %s, %s, %s',$branch['num'],$branch['street'],$branch['street_number']),
                'anyfield'   => $place
            ];
        }

        return $result;
    }

    private function getCity($typeId, $cityName)
    {
        $cities = $this->model_extension_shipping_meest2->getCity($typeId, $cityName);
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

    public function getMeestData() {
        $json = [];

        $this->load->model('extension/shipping/meest2');
        $model_name = 'model_extension_shipping_meest2';

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
            $json = $this->$model_name->getCities($filter, $search);
        } elseif ($action === 'getBranches') {
            $json = $this->$model_name->getBranches($filter, $search, $this->warehouseType);
        } elseif ($action === 'getStreets') {
            $json = $this->$model_name->getStreets($filter, $search);
        } elseif ($action === 'getPoshtomat') {
            $json = $this->$model_name->getBranches($filter, $search, $this->poshtomatType);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveUuid() {
        $enabledCalculationInCheckout = $this->config->get('meest2_calculation_in_checkout');

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

    /**
     * Розрахунок вартості доставки через API Meest
     * Використовує метод з моделі для розрахунку
     */
    public function calculateShippingCost() {
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
            $this->load->model('extension/shipping/meest2');

            // Викликаємо метод моделі для розрахунку
            $result = $this->model_extension_shipping_meest2->calculateShippingCost($this->request->post);

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

        } catch (Exception $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function getBranchesWithCoordinates() {
        $json = [];

        $this->load->model('extension/shipping/meest2');

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
            $branches = $this->model_extension_shipping_meest2->getBranchesByCity($cityId, $typeId);

            foreach ($branches as $branch) {
                $json[] = [
                    'id' => isset($branch['branch_id']) ? $branch['branch_id'] : '',
                    'description' => isset($branch['short_name']) ? $branch['short_name'] : '',
                    'address' => isset($branch['short_name']) ? $branch['short_name'] : '',
                    'latitude' => isset($branch['latitude']) ? $branch['latitude'] : null,
                    'longitude' => isset($branch['longitude']) ? $branch['longitude'] : null,
                ];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveMeestSessionData() {
        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (!isset($this->session->data['meest_data'])) {
                $this->session->data['meest_data'] = array();
            }

            if (isset($this->request->post['shipping_method'])) {
                $this->session->data['meest_data']['shipping_method'] = $this->request->post['shipping_method'];
            }

            if (isset($this->request->post['city_code'])) {
                $this->session->data['meest_data']['city_UUID'] = $this->request->post['city_code'];
            }

            if (isset($this->request->post['branch_code'])) {
                $this->session->data['meest_data']['branch_code'] = $this->request->post['branch_code'];
                if (!empty($this->request->post['branch_code'])) {
                    $this->session->data['meest_data']['address_1_UUID'] = $this->request->post['branch_code'];
                }
            }

            if (isset($this->request->post['address_code'])) {
                $this->session->data['meest_data']['address_code'] = $this->request->post['address_code'];
                if (!empty($this->request->post['address_code'])) {
                    $this->session->data['meest_data']['address_1_UUID'] = $this->request->post['address_code'];
                }
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

    public function saveShippingData() {
        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $order_id = isset($this->session->data['order_id']) ? (int)$this->session->data['order_id'] : 0;

            if ($order_id) {
                $this->load->model('extension/shipping/meest2');

                $data = array(
                    'shipping_method' => isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : '',
                    'city_code' => isset($this->request->post['city_code']) ? $this->request->post['city_code'] : '',
                    'branch_code' => isset($this->request->post['branch_code']) ? $this->request->post['branch_code'] : '',
                    'address_code' => isset($this->request->post['address_code']) ? $this->request->post['address_code'] : '',
                    'building' => isset($this->request->post['building']) ? $this->request->post['building'] : '',
                    'region_code' => isset($this->request->post['region_code']) ? $this->request->post['region_code'] : ''
                );

                $this->model_extension_shipping_meest2->saveOrderShippingData($order_id, $data);
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

        $this->load->language('extension/shipping/meest2');

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
    }

    public function afterAddOrder(&$route, &$args, &$output) {
        $order_id = $output;

        if ($order_id && isset($this->session->data['meest_data'])) {
            $shippingMethodMeest = $this->session->data['meest_data'];

            if (isset($shippingMethodMeest['shipping_method']) && strpos($shippingMethodMeest['shipping_method'], 'meest2.') === 0) {
                $this->load->model('extension/shipping/meest2');

                $data = array(
                    'shipping_method' => $shippingMethodMeest['shipping_method'],
                    'city_code' => isset($shippingMethodMeest['city_UUID']) ? $shippingMethodMeest['city_UUID'] : '',
                    'branch_code' => isset($shippingMethodMeest['branch_code']) ? $shippingMethodMeest['branch_code'] : (isset($shippingMethodMeest['address_1_UUID']) && (strpos($shippingMethodMeest['shipping_method'], 'branch') !== false || strpos($shippingMethodMeest['shipping_method'], 'postomat') !== false) ? $shippingMethodMeest['address_1_UUID'] : ''),
                    'address_code' => isset($shippingMethodMeest['address_code']) ? $shippingMethodMeest['address_code'] : (isset($shippingMethodMeest['address_1_UUID']) && (strpos($shippingMethodMeest['shipping_method'], 'door') !== false || strpos($shippingMethodMeest['shipping_method'], 'courier') !== false) ? $shippingMethodMeest['address_1_UUID'] : ''),
                    'building' => isset($shippingMethodMeest['building']) ? $shippingMethodMeest['building'] : '',
                    'region_code' => isset($shippingMethodMeest['region_code']) ? $shippingMethodMeest['region_code'] : ''
                );

                $this->model_extension_shipping_meest2->saveOrderShippingData($order_id, $data);
            }
        }
    }
}
