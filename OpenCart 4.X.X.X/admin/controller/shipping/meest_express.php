<?php

namespace Opencart\Admin\Controller\Extension\MeestExpress\Shipping;

use Opencart\System\Engine\Controller;

class MeestExpress extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');
        $this->model_extension_MeestExpress_shipping_meest_express->install(false);

        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $this->document->setTitle(strip_tags($this->language->get('heading_title')));
        
        // Add Select2 for searchable dropdowns
        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');
        
        $this->document->addStyle('extension/MeestExpress/admin/view/stylesheet/MeestExpress/meest_express.css');
        $this->document->addScript('extension/MeestExpress/admin/view/javascript/MeestExpress/meest_express.js');

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_meest_express', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/MeestExpress/shipping/meest_express', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        // Load all settings
        $this->loadSettings($data);

        // Load language data
        $this->loadLanguageData($data);

        // Load breadcrumbs
        $this->loadBreadcrumbs($data);

        // Load additional data
        $this->loadAdditionalData($data);

        $catalog = $this->config->get('config_secure') ? $this->config->get('config_ssl') : $this->config->get('config_url');

        $data['cron_command'] = '0 2 * * * curl -s "' . $catalog . 'index.php?route=extension/meest2/shipping/cron.updateAll"';


        // Load standard OpenCart elements
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Load view
        $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express', $data));
    }

    private function loadSettings(&$data)
    {
        // Auth settings
        if (isset($this->request->post['shipping_meest_express_auth_mode'])) {
            $data['shipping_meest_express_auth_mode'] = $this->request->post['shipping_meest_express_auth_mode'];
        } else {
            $data['shipping_meest_express_auth_mode'] = $this->config->get('shipping_meest_express_auth_mode');
        }

        if (isset($this->request->post['shipping_meest_express_login'])) {
            $data['shipping_meest_express_login'] = $this->request->post['shipping_meest_express_login'];
        } else {
            $data['shipping_meest_express_login'] = $this->config->get('shipping_meest_express_login');
        }

        if (isset($this->request->post['shipping_meest_express_password'])) {
            $data['shipping_meest_express_password'] = $this->request->post['shipping_meest_express_password'];
        } else {
            $data['shipping_meest_express_password'] = $this->config->get('shipping_meest_express_password');
        }

        if (isset($this->request->post['shipping_meest_express_api_key'])) {
            $data['shipping_meest_express_api_key'] = $this->request->post['shipping_meest_express_api_key'];
        } else {
            $data['shipping_meest_express_api_key'] = $this->config->get('shipping_meest_express_api_key');
        }

        // Sender settings
        $senderFields = [
            'sender_city', 'sender_address', 'sender_branch', 'sender_contract_id', 
            'sender_address_pick_up', 'sender', 'sender_contact_person', 'sender_region',
            'sender_contact_id', 'departure_type'
        ];

        foreach ($senderFields as $field) {
            if (isset($this->request->post['shipping_meest_express_' . $field])) {
                $data['shipping_meest_express_' . $field] = $this->request->post['shipping_meest_express_' . $field];
            } else {
                $data['shipping_meest_express_' . $field] = $this->config->get('shipping_meest_express_' . $field);
            }
        }

        // Recipient settings
        $recipientFields = [
            'recipient', 'recipient_contact_person', 'recipient_phone', 'recipient_edrpou',
            'recipient_region', 'recipient_city', 'recipient_branch', 'recipient_address',
            'recipient_street', 'recipient_house', 'recipient_flat', 'recipient_date', 'recipient_time'
        ];

        foreach ($recipientFields as $field) {
            if (isset($this->request->post['shipping_meest_express'][$field])) {
                $data['shipping_meest_express_' . $field] = $this->request->post['shipping_meest_express'][$field];
            } else {
                $data['shipping_meest_express_' . $field] = $this->config->get('shipping_meest_express_' . $field);
            }
        }

        // Basic settings
        $basicFields = [
            'cost', 'tax_class_id', 'geo_zone_id', 'calculation_in_checkout', 
            'status', 'sort_order', 'service', 'free_shipping_enabled', 'free_shipping_threshold'
        ];

        foreach ($basicFields as $field) {
            if (isset($this->request->post['shipping_meest_express_' . $field])) {
                $data['shipping_meest_express_' . $field] = $this->request->post['shipping_meest_express_' . $field];
            } else {
                $data['shipping_meest_express_' . $field] = $this->config->get('shipping_meest_express_' . $field);
            }
        }

        if (!isset($data['shipping_meest_express_service']) || !is_array($data['shipping_meest_express_service'])) {
            $data['shipping_meest_express_service'] = [];
        }
    }

    private function loadLanguageData(&$data)
    {
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_none'] = $this->language->get('text_none');

        // Entry labels
        $data['entry_cost'] = $this->language->get('entry_cost');
        $data['entry_tax_class'] = $this->language->get('entry_tax_class');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_api_key'] = $this->language->get('entry_api_key');

        // Sender entries
        $data['entry_sender'] = $this->language->get('entry_sender');
        $data['entry_sender_contact_person'] = $this->language->get('entry_sender_contact_person');
        $data['entry_sender_region'] = $this->language->get('entry_sender_region');
        $data['entry_sender_city'] = $this->language->get('entry_sender_city');
        $data['entry_sender_address'] = $this->language->get('entry_sender_address');
        $data['entry_sender_street'] = $this->language->get('entry_sender_street');
        $data['entry_branch'] = $this->language->get('entry_branch');
        $data['entry_free_shipping_enabled'] = $this->language->get('entry_free_shipping_enabled');
        $data['entry_free_shipping_threshold'] = $this->language->get('entry_free_shipping_threshold');
        $data['help_free_shipping_threshold'] = $this->language->get('help_free_shipping_threshold');
        $data['entry_sender_address_pick_up'] = $this->language->get('entry_sender_address_pick_up');

        // Recipient entries
        $data['entry_recipient'] = $this->language->get('entry_recipient');
        $data['entry_recipient_contact_person'] = $this->language->get('entry_recipient_contact_person');
        $data['entry_recipient_phone'] = $this->language->get('entry_recipient_phone');
        $data['entry_recipient_edrpou'] = $this->language->get('entry_recipient_edrpou');
        $data['entry_recipient_region'] = $this->language->get('entry_recipient_region');
        $data['entry_recipient_city'] = $this->language->get('entry_recipient_city');
        $data['entry_recipient_branch'] = $this->language->get('entry_recipient_branch');
        $data['entry_recipient_address'] = $this->language->get('entry_recipient_address');
        $data['entry_recipient_street'] = $this->language->get('entry_recipient_street');
        $data['entry_recipient_house'] = $this->language->get('entry_recipient_house');
        $data['entry_recipient_flat'] = $this->language->get('entry_recipient_flat');
        $data['entry_recipient_date'] = $this->language->get('entry_recipient_date');
        $data['entry_recipient_time'] = $this->language->get('entry_recipient_time');

        // Database entries
        $data['entry_type_of_data'] = $this->language->get('entry_type_of_data');
        $data['entry_last_updated'] = $this->language->get('entry_last_updated');
        $data['entry_amount'] = $this->language->get('entry_amount');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_action'] = $this->language->get('entry_action');

        // Buttons
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        // Contract entries
        $data['text_contract_id'] = $this->language->get('text_contract_id');
        $data['button_add_contract'] = $this->language->get('button_add_contract');
        $data['text_enter_contract_id'] = $this->language->get('text_enter_contract_id');
        $data['text_no_contracts'] = $this->language->get('text_no_contracts');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['column_contract_id'] = $this->language->get('column_contract_id');
        $data['column_date_created'] = $this->language->get('column_date_created');
        $data['column_date_updated'] = $this->language->get('column_date_updated');
        $data['column_action'] = $this->language->get('column_action');

        // Error handling
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }
    }

    private function loadBreadcrumbs(&$data)
    {
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_shipping'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/MeestExpress/shipping/meest_express', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $data['action'] = $this->url->link('extension/MeestExpress/shipping/meest_express', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
    }

    private function loadAdditionalData(&$data)
    {
        // Load tax classes
        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        // Load geo zones
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        // Services
        $data['services'] = [
            [
                'text'  => $this->language->get('text_shipping_warehouse'),
                'value' => 'warehouse'
            ],
            [
                'text'  => $this->language->get('text_shipping_postomat'),
                'value' => 'postomat'
            ],
            [
                'text'  => $this->language->get('text_shipping_courier'),
                'value' => 'courier'
            ]
        ];

        // Import URLs
        $data['importBranches'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.importBranches','user_token=' . $this->session->data['user_token'],true));
        $data['importRegions'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.importRegions', 'user_token=' . $this->session->data['user_token'], true));
        $data['importCity'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.importCity', 'user_token=' . $this->session->data['user_token'], true));
        $data['importStreets'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.importStreets', 'user_token=' . $this->session->data['user_token'], true));
        $data['addContract'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.addContract', 'user_token=' . $this->session->data['user_token'], true));
        $data['addContact'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.addContact', 'user_token=' . $this->session->data['user_token'], true));
        $data['deleteContract'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.deleteContract', 'user_token=' . $this->session->data['user_token'], true));
        $data['deleteContact'] = str_replace('&amp;','&',$this->url->link('extension/MeestExpress/shipping/meest_express.deleteContact', 'user_token=' . $this->session->data['user_token'], true));

        // AJAX URLs
        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getCitiesByRegion', 'user_token=' . $this->session->data['user_token'], true));
        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getBranchesByCity', 'user_token=' . $this->session->data['user_token'], true));
        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getStreetsByCity', 'user_token=' . $this->session->data['user_token'], true));

        // Load data from model
        $data['branch_import_info'] = $this->model_extension_MeestExpress_shipping_meest_express->getBranchTotalRecordsAndLatestDate();
        $data['regions_import_info'] = $this->model_extension_MeestExpress_shipping_meest_express->getRegionsTotalRecordsAndLatestDate();
        $data['cities_import_info'] = $this->model_extension_MeestExpress_shipping_meest_express->getCitiesTotalRecordsAndLatestDate();
        $data['streets_import_info'] = $this->model_extension_MeestExpress_shipping_meest_express->getStreetsTotalRecordsAndLatestDate();

        $data['regions'] = $this->model_extension_MeestExpress_shipping_meest_express->getRegions();
        $data['contracts'] = $this->model_extension_MeestExpress_shipping_meest_express->getContracts();
        $data['contacts'] = $this->model_extension_MeestExpress_shipping_meest_express->getContacts();

        if (isset($this->session->data['error_warning_meest'])) {
            $data['error_warning_meest'] = $this->session->data['error_warning_meest'];
            unset($this->session->data['error_warning_meest']);
        }
    }

    public function install()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');
        $this->model_extension_MeestExpress_shipping_meest_express->install(true);
        
        // Register events for adding Meest button to order list
        $this->load->model('setting/event');
        
        // Delete old events if exist
        $this->model_setting_event->deleteEventByCode('meest_express_admin_header');
        $this->model_setting_event->deleteEventByCode('meest_express_column_left');

        
        // Register event based on OpenCart version
        if (defined('VERSION') && VERSION == '4.0.0.0') {
            // For version 4.0.0.0: addEvent($code, $description, $trigger, $action)
            $this->model_setting_event->addEvent(
                'meest_express_admin_header',
                'Inject Meest Express JavaScript into admin header',
                'admin/view/sale/order_list/after',
                'extension/MeestExpress/shipping/meest_express.addMeestOrderButtons'
            );

            $this->model_setting_event->addEvent(
                'meest_express_addAssets',
                'Save Meest Express shipping data after order creation',
                'catalog/controller/common/header/before',
                'extension/MeestExpress/shipping/meest_express.addAssets'
            );

            
            $this->model_setting_event->addEvent(
                'meest_express_after_add_order',
                'Save Meest Express shipping data after order creation',
                'catalog/model/checkout/order/addOrder/after',
                'extension/MeestExpress/shipping/meest_express.afterAddOrder'
            );
        } else {
            // For other versions: addEvent($data)
            $event_data = [
                'code' => 'meest_express_admin_header',
                'description' => 'Inject Meest Express JavaScript into admin header',
                'trigger' => 'admin/view/sale/order_list/after',
                'action' => 'extension/MeestExpress/shipping/meest_express.addMeestOrderButtons',
                'status' => 1,
                'sort_order' => 999
            ];
            $this->model_setting_event->addEvent($event_data);

            $event_data_checkout = [
                'code' => 'meest_express_addAssets',
                'description' => 'Inject Meest Express JavaScript into admin header',
                'trigger' => 'catalog/controller/common/header/before',
                'action' => 'extension/MeestExpress/shipping/meest_express.addAssets',
                'status' => 1,
                'sort_order' => 999
            ];

            $this->model_setting_event->addEvent($event_data_checkout);
            
            $event_data_after_order = [
                'code' => 'meest_express_after_add_order',
                'description' => 'Save Meest Express shipping data after order creation',
                'trigger' => 'catalog/model/checkout/order/addOrder/after',
                'action' => 'extension/MeestExpress/shipping/meest_express.afterAddOrder',
                'status' => 1,
                'sort_order' => 999
            ];
            $this->model_setting_event->addEvent($event_data_after_order);
        }
        
        // Return installation data for OpenCart 4.0
        return [
            'name' => 'Meest Express Shipping',
            'version' => '1.0.0',
            'author' => 'Meest Express',
            'link' => 'https://meest.com',
            'type' => 'shipping',
            'code' => 'meest_express',
            'status' => 1,
            'sort_order' => 0
        ];
    }

    public function uninstall()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');
        $this->model_extension_MeestExpress_shipping_meest_express->uninstall();
        
        // Unregister all events
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('meest_express_admin_header');
        $this->model_setting_event->deleteEventByCode('meest_express_addAssets');
        $this->model_setting_event->deleteEventByCode('meest_express_after_add_order');
        
        return [
            'code' => 'meest_express'
        ];
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/MeestExpress/shipping/meest_express')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    // Import methods
    public function importBranches()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        try {
            $url = 'https://api.meest.com/v3.0/openAPI/branchSearch';
            $data = [
                "in" => true,
                "out" => true,
                "close" => false
            ];

            $response = $this->meestApiV3($url, $data);
            $responseData = json_decode($response, true);
            
            if (!isset($responseData['status']) || $responseData['status'] !== "OK") {
                throw new \Exception('API Error: ' . json_encode($responseData, JSON_UNESCAPED_UNICODE));
            }

            $resultData = $this->model_extension_MeestExpress_shipping_meest_express->saveBranchesBatch($responseData['result']);
            $json = ['success' => true, 'data' => $resultData];
        } catch (\Exception $e) {
            $json = ['success' => false, 'error' => $e->getMessage()];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function importRegions()
    {
        $json = [];

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

        try {
            $url = 'https://api.meest.com/v3.0/openAPI/regionSearch';
            $data = [
                "filters" => [
                    "countryID" => "c35b6195-4ea3-11de-8591-001d600938f8"
                ]
            ];

            $response = $this->meestApiV3($url, $data);
            $responseData = json_decode($response, true);

            if (isset($responseData['status']) && $responseData['status'] === "OK") {
                $this->load->model('extension/MeestExpress/shipping/meest_express');

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

                    $region = $this->model_extension_MeestExpress_shipping_meest_express->getRegion($regionID);

                    if (empty($region)) {
                        $this->model_extension_MeestExpress_shipping_meest_express->addRegion($regionDataToSave);
            } else {
                        $this->model_extension_MeestExpress_shipping_meest_express->editRegion($regionID, $regionDataToSave);
                    }
                }
            } else {
                throw new \Exception('API Error: ' . json_encode($responseData));
            }

            $json['success'] = true;
            $json['data'] = true;
        } catch (\Exception $e) {
            $json['success'] = false;
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function importCity()
    {
        $json = [];

        try {
            $url = 'https://meest-group.com/media/location/cities.txt';
            $response = file_get_contents($url);
            
            if ($response === false) {
                throw new \Exception('Unable to fetch data from URL.');
            }

            $response = mb_convert_encoding($response, 'UTF-8', 'Windows-1251');
            $lines = explode("\n", $response);

            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $existingCities = $this->model_extension_MeestExpress_shipping_meest_express->getAllCities();

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
                $this->model_extension_MeestExpress_shipping_meest_express->bulkInsertCities($insertData);
            }

            if (!empty($updateData)) {
                $this->model_extension_MeestExpress_shipping_meest_express->bulkUpdateCities($updateData);
            }

            $json['success'] = true;
            $json['data'] = true;
        } catch (\Exception $e) {
            $json['success'] = false;
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function importStreets()
    {
        $json = [];

        try {
            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $result = $this->model_extension_MeestExpress_shipping_meest_express->importStreets();
            
                $json['success'] = true;
            $json['message'] = 'Import completed successfully.';
            $json['data'] = $result;
        } catch (\Exception $e) {
            $json['success'] = false;
            $json['error'] = 'Internal Server Error: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // Contract and Contact methods
    public function addContract()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $json = [];
        
        if (isset($this->request->post['contract_id']) && $this->request->post['contract_id']) {
            $contract_id = $this->request->post['contract_id'];
            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $this->model_extension_MeestExpress_shipping_meest_express->addContract($contract_id);
            $json['success'] = $this->language->get('text_success_add');
            } else {
            $json['error'] = $this->language->get('error_contract_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteContract()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $json = [];

        if (isset($this->request->post['contract_id']) && $this->request->post['contract_id']) {
            $contract_id = $this->request->post['contract_id'];
            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $this->model_extension_MeestExpress_shipping_meest_express->deleteContract($contract_id);
            $json['success'] = $this->language->get('text_success_delete');
        } else {
            $json['error'] = $this->language->get('error_contract_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addContact()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $json = array();

        if (isset($this->request->post['phone']) && isset($this->request->post['firstname']) && isset($this->request->post['lastname']) && isset($this->request->post['middlename'])) {
            $phone = $this->request->post['phone'];
            $firstname = $this->request->post['firstname'];
            $lastname = $this->request->post['lastname'];
            $middlename = $this->request->post['middlename'];

            $this->model_extension_MeestExpress_shipping_meest_express->addContact($phone, $firstname, $lastname, $middlename);
            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('text_error_fill_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteContact()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $json = array();

        if (isset($this->request->post['contact_id'])) {
            $contact_id = $this->request->post['contact_id'];
            $this->model_extension_MeestExpress_shipping_meest_express->deleteContact($contact_id);
            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('text_error');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getContacts()
    {
        $this->load->model('extension/MeestExpress/shipping/meest_express');
        $data['contacts'] = $this->model_extension_MeestExpress_shipping_meest_express->getContacts();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getCitiesByRegion()
    {
        $json = [];

        $this->load->model('extension/MeestExpress/shipping/meest_express');
        
        if (isset($this->request->get['search']) && !empty($this->request->get['search'])) {
            // Search cities by name
            $search = $this->request->get['search'];
            $json = $this->model_extension_MeestExpress_shipping_meest_express->searchCities($search);
        } elseif (isset($this->request->get['region_id'])) {
            // Get cities by region
            $region_id = $this->request->get['region_id'];
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getCitiesByRegion($region_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStreetsByCity()
    {
        $json = [];

        if (isset($this->request->get['city_id'])) {
            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $city_id = $this->request->get['city_id'];
            $search = isset($this->request->get['search']) ? $this->request->get['search'] : '';
            
            if (!empty($search)) {
                $json = $this->model_extension_MeestExpress_shipping_meest_express->searchStreetsByCity($city_id, $search);
            } else {
                $json = $this->model_extension_MeestExpress_shipping_meest_express->getStreetsByCity($city_id);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBranchesByCity()
    {
        $json = [];
        if (isset($this->request->get['city_id'])) {
            $this->load->model('extension/MeestExpress/shipping/meest_express');
            $city_id = $this->request->get['city_id'];
            $json = $this->model_extension_MeestExpress_shipping_meest_express->getBranchesByCity($city_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // API helper methods
    private function auth()
    {
        $url = 'https://api.meest.com/v3.0/openAPI/auth';
        $authMode = $this->config->get('shipping_meest_express_auth_mode');

        if ($authMode === "api_key") {
            return $this->config->get('shipping_meest_express_api_key');
        } elseif ($authMode === "default") {
            if ($this->config->get('shipping_meest_express_login') && $this->config->get('shipping_meest_express_password')) {
                $data = [
                    'username' => $this->config->get('shipping_meest_express_login'),
                    'password' => $this->config->get('shipping_meest_express_password')
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
            $response = $this->meestApiV3($url, $data, 'manual-token-here-if-needed', 'POST');
        } catch (\Exception $e) {
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

    protected function meestApiV3($url, $data, $token = null, $method = 'POST')
    {
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
            throw new \Exception('cURL Error: ' . $error);
        }

        curl_close($ch);
        return $response;
    }

    protected function getValidMeestToken()
    {
        $auth = $this->auth();

        if (is_array($auth) && isset($auth['status']) && $auth['status'] === 'error') {
            if (isset($auth['error_warning_meest'])) {
                throw new \Exception($auth['error_warning_meest']);
            } else {
                throw new \Exception('Problems with API authorization, please check your login and password or token');
            }
        }

        if (!is_string($auth) || empty($auth)) {
            throw new \Exception('Invalid or empty Meest token');
        }

        return $auth;
    }
    
    /**
     * Event handler: Add Meest Express buttons to order list page
     * This method is called by the event system when admin header is rendered
     */
    public function addMeestOrderButtons(&$route, &$data, &$output) {
        // Check if we are on order list page by URL
        $isOrderPage = false;
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'sale/order') !== false) {
            $isOrderPage = true;
        }
        
        if (!$isOrderPage) {
            return;
        }
        
        $userToken = $this->session->data['user_token'] ?? '';
        
        // Get orders with created Meest CN documents
        $this->load->model('extension/MeestExpress/shipping/meest_express');
        $ordersWithCN = [];
        $meestOrders = [];
        
        // Query to get all orders with Meest Express shipping method
        // shipping_method contains JSON like: {"code":"meest_express.postomat","name":"..."}
        $queryAllMeest = $this->db->query("
            SELECT DISTINCT o.order_id 
            FROM `" . DB_PREFIX . "order` o
            WHERE o.shipping_method IS NOT NULL 
            AND o.shipping_method != ''
            AND (
                o.shipping_method LIKE '%meest_express%'
                OR o.shipping_method LIKE '%meest\\_express%'
            )
        ");
        
        if ($queryAllMeest && $queryAllMeest->num_rows) {
            foreach ($queryAllMeest->rows as $row) {
                $meestOrders[] = (int)$row['order_id'];
            }
        }
        
        // Query to get all orders with Meest CN
        $query = $this->db->query("
            SELECT o.order_id, o.meest_express_cn_uuid, p.uuid, p.parcel_number 
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "meest_express_parcels` p ON o.order_id = p.order_id
            WHERE o.meest_express_cn_uuid IS NOT NULL 
            AND o.meest_express_cn_uuid != ''
            AND o.meest_express_cn_uuid != 'None'
        ");
        
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $ordersWithCN[$row['order_id']] = [
                    'cn_uuid' => $row['meest_express_cn_uuid'],
                    'parcel_uuid' => $row['uuid'],
                    'parcel_number' => $row['parcel_number']
                ];
            }
        }
        
        $ordersWithCNJson = json_encode($ordersWithCN);
        $meestOrdersJson = json_encode($meestOrders);
        
        // Create script to add Meest Express buttons to order list
        $script = '
<script>
// Orders with created CN documents
var meestOrdersWithCN = ' . $ordersWithCNJson . ';
// All orders with Meest Express shipping method
var meestExpressOrders = ' . $meestOrdersJson . ';

// Get user token from current URL or PHP
function getUserToken() {
    var urlParams = new URLSearchParams(window.location.search);
    var tokenFromUrl = urlParams.get("user_token");
    var tokenFromPHP = "' . $userToken . '";
    return tokenFromUrl || tokenFromPHP || "";
}

// Wait for page to be fully loaded
setTimeout(function() {
    // Add "Meest CN List" button to the header
    var $headerButtons = $(".page-header .container-fluid .float-end");
    
    if ($headerButtons.length > 0) {
        var userToken = getUserToken();
        var meestListButton = \'<a href="index.php?route=extension/MeestExpress/shipping/meest_express|parcelList&user_token=\' + userToken + \'" class="btn btn-primary" data-bs-toggle="tooltip" title="Meest CN List" style="margin-right: 5px;"><i class="fas fa-list"></i></a>\';
        $headerButtons.prepend(meestListButton);
    }
    
    // Try different selectors for the table
    var $table = $("table.table tbody tr");
    
    if ($table.length === 0) {
        $table = $("table tbody tr");
    }
    
    if ($table.length === 0) {
        $table = $("tr");
    }
    
    // Add Meest Express button to each order row
    $table.each(function(index) {
        var $row = $(this);
        
        // Try different selectors for action cell
        var $actionCell = $row.find("td.text-end").last();
        if ($actionCell.length === 0) {
            $actionCell = $row.find("td:last-child");
        }
        if ($actionCell.length === 0) {
            $actionCell = $row.children("td").last();
        }
        
        // Get order ID from the link in action cell
        var orderId = "";
        var $viewLink = $actionCell.find("a[href*=\'order_id=\']");
        if ($viewLink.length > 0) {
            var href = $viewLink.attr("href");
            var match = href.match(/order_id=(\d+)/);
            if (match) {
                orderId = match[1];
            }
        }
        
        // Fallback: try to get from first column
        if (!orderId) {
            orderId = $row.find("td").eq(0).text().trim();
        }
        
        if (orderId && $actionCell.length > 0) {
            // Check if this order uses Meest Express shipping method
            var orderIdInt = parseInt(orderId);
            if (meestExpressOrders.indexOf(orderIdInt) === -1) {
                return; // Skip - not a Meest Express order
            }
            
            // Create dropdown button
            var userToken = getUserToken();
            var dropdownHtml = "";
            
            // Check if order has CN document created
            if (meestOrdersWithCN[orderId]) {
                // Order HAS CN - show View/Edit options
                var cnUuid = meestOrdersWithCN[orderId].cn_uuid;
                dropdownHtml = \'<div class="btn-group" style="margin-right: 5px;">\' +
                    \'<button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #EBF1FB !important; border: none !important; border-radius: 5px !important; width: 60px; height: 37px; display: inline-flex; align-items: center; justify-content: space-between; color: #0061AF !important; padding: 0 8px; cursor: pointer;">\' +
                        \'<i class="fas fa-file-alt" style="font-size: 16px; color: #0061AF !important; margin-left: 5px;"></i>\' +
                        \'<span style="display: inline-block; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 4px solid #0061AF;"></span>\' +
                    \'</button>\' +
                    \'<ul class="dropdown-menu">\' +
                        \'<li><a href="index.php?route=extension/MeestExpress/shipping/meest_express|viewParcelInfo&parcel_id=\' + cnUuid + \'&user_token=\' + userToken + \'" class="dropdown-item">View Meest CN Info</a></li>\' +
                        \'<li><a href="index.php?route=extension/MeestExpress/shipping/meest_express|orderUpdateForm&parcel_id=\' + cnUuid + \'&user_token=\' + userToken + \'" class="dropdown-item">Edit Meest CN</a></li>\' +
                    \'</ul>\' +
                \'</div>\';
            } else {
                // Order DOES NOT have CN - show Create option
                dropdownHtml = \'<div class="btn-group" style="margin-right: 5px;">\' +
                    \'<button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #EBF1FB !important; border: none !important; border-radius: 5px !important; width: 60px; height: 37px; display: inline-flex; align-items: center; justify-content: space-between; color: #0061AF !important; padding: 0 8px; cursor: pointer;">\' +
                        \'<i class="fas fa-file-alt" style="font-size: 16px; color: #0061AF !important; margin-left: 5px;"></i>\' +
                        \'<span style="display: inline-block; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 4px solid #0061AF;"></span>\' +
                    \'</button>\' +
                    \'<ul class="dropdown-menu">\' +
                        \'<li><a href="index.php?route=extension/MeestExpress/shipping/meest_express|orderForm&order_id=\' + orderId + \'&user_token=\' + userToken + \'" class="dropdown-item">Create Meest CN</a></li>\' +
                    \'</ul>\' +
                \'</div>\';
            }
            
            // Insert before the existing view button
            $actionCell.prepend(dropdownHtml);
        }
    });
}, 2000);
</script>';
        
        // Inject script into head section
        if (is_string($output) && !empty($output)) {
            if (strpos($output, '</head>') !== false) {
                $output = str_replace('</head>', $script . '</head>', $output);
            } elseif (strpos($output, '</body>') !== false) {
                $output = str_replace('</body>', $script . '</body>', $output);
            } else {
                $output .= $script;
            }
        }
    }

    
    /**
     * Create Meest Express document for order
     */
    public function createDocument() {
        $this->load->language('extension/MeestExpress/shipping/meest_express');

        $json = [];

        if (isset($this->request->get['order_id'])) {
            $order_id = (int)$this->request->get['order_id'];
            
            // Load order model
            $this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($order_id);
            
            if ($order_info) {
                // Here you would implement the actual document creation logic
                // For now, just return success
                $json['success'] = $this->language->get('text_document_created');
                $json['message'] = sprintf($this->language->get('text_document_created_for_order'), $order_id);
            } else {
                $json['error'] = $this->language->get('error_order_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_missing_order_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Check if Meest CN exists for order (used by event system)
     */
    public function checkMeestCn()
    {
        $this->load->model('sale/order');
        
        $order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
        $cn_uuid = '';
        
        if ($order_id) {
            $order_info = $this->model_sale_order->getOrder($order_id);
            if ($order_info) {
                $cn_uuid = isset($order_info['meest_express_cn_uuid']) ? $order_info['meest_express_cn_uuid'] : '';
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'cn_uuid' => $cn_uuid
        ]));
    }
    
    /**
     * Order form - create Meest CN
     */
    public function orderForm()
    {
        $this->load->language('extension/MeestExpress/shipping/meest_express');
        $this->document->setTitle($this->language->get('heading_title_order_form'));

        $this->document->addStyle('extension/MeestExpress/admin/view/stylesheet/MeestExpress/meest_express.css');
        $this->document->addScript('extension/MeestExpress/admin/view/javascript/MeestExpress/meest_express.js');

        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js');

        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');

        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $contractID = $this->config->get('shipping_meest_express_sender_contract_id');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateOrderForm()) {
            $postData = $this->request->post;
            $this->load->model('sale/order');
            $places = $postData['places'];

            $placesItems = array();

            foreach ($places as $place) {
                $height = isset($place['height']) ? $place['height'] : 0;
                $length = isset($place['length']) ? $place['length'] : 0;
                $width = isset($place['width']) ? $place['width'] : 0;
                $weight = isset($place['weight']) ? $place['weight'] : 0;
                $quantity = isset($place['quantity']) ? $place['quantity'] : 1;

                $placesItems[] = array(
                    "formatID"  => '',
                    "insurance" => isset($place['insurance']) ? $place['insurance'] : 0,
                    "height" => $height,
                    "length" => $length,
                    "quantity" => $quantity,
                    "width" => $width,
                    "weight" => $weight,
                    "volume" => ($length * $width * $height) / 100
                );
            }

            $order_info = $this->model_sale_order->getOrder($postData['order_number']);

            // Оновлюємо дані доставки в таблиці meest2_order_shipping_data
            $shipping_data = array(
                'shipping_method' => $postData['recipient_delivery_type'] === 'doors' ? 'meest2.door' : 'meest2.branch',
                'city_code' => $postData['recipient_delivery_type'] === 'doors' ? 
                    (isset($postData['recipient_city_address']) ? $postData['recipient_city_address'] : '') : 
                    (isset($postData['recipient_city']) ? $postData['recipient_city'] : ''),
                'branch_code' => $postData['recipient_delivery_type'] === 'branch' ? $postData['recipient_branch'] : '',
                'address_code' => $postData['recipient_delivery_type'] === 'doors' ? $postData['recipient_address'] : '',
                'building' => $postData['recipient_delivery_type'] === 'doors' && isset($postData['recipient_building_address']) ? $postData['recipient_building_address'] : '',
                'region_code' => ''
            );
            $this->model_extension_MeestExpress_shipping_meest_express->updateOrderShippingData($order_info['order_id'], $shipping_data);

            $senderPerson = $this->model_extension_MeestExpress_shipping_meest_express->getContact($this->config->get('shipping_meest_express_sender_contact_person'));
            $senderAddressPickUp = 0;

            if($postData['sender_delivery_type'] === 'doors'){
                $senderData = array(
                    "name" => $senderPerson['lastname'] . ' ' . $senderPerson['firstname'] . ' ' .  $senderPerson['middlename'],
                    "phone" => $senderPerson['phone'],
                    "service" => "Door",
                    "addressID" => $postData['sender_address'],
                    "cityID"    => $postData['shipping_meest_express_sender_city'],
                    "building"  => $postData['sender_building'],
                    "floor"   => $postData['sender_floor'],
                    "flat"   => $postData['sender_apartment']
                );
                $senderAddressPickUp = 1;
            }else{
                $senderData = array(
                    "name" => $senderPerson['lastname'] . ' ' . $senderPerson['firstname'] . ' ' .  $senderPerson['middlename'],
                    "phone" => $senderPerson['phone'],
                    "service" => "Branch",
                    "branchID" => $postData['sender_branch']
                );
            }

            if($postData['recipient_delivery_type'] === 'doors'){
                $recipientData = array(
                    "name" => $postData['recipient_contact_person_address'],
                    "phone" => $postData['recipient_contact_person_phone_address'],
                    "service" => "Door",
                    "addressID" => $postData['recipient_address'],
                    "cityID"    => $postData['recipient_city_address'],
                    "building"  => $postData['recipient_building_address'],
                    "floor"   => $postData['recipient_floor_address'],
                    "flat"   => $postData['recipient_apartment_address']
                );
            }else{
                $recipientData = array(
                    "name" => $postData['recipient_contact_person'],
                    "phone" => $postData['recipient_contact_person_phone'],
                    "service" => "Branch",
                    "branchID" => $postData['recipient_branch']
                );
            }

            $codAmount = isset($postData['cod_amount']) ? $postData['cod_amount'] : 0;

            $cardForCOD = [];
            if ($codAmount) {
                $cardNumber = isset($postData['card_number']) ? $postData['card_number'] : '';
                if($cardNumber) {
                    $cardForCOD = [
                        'number' => $cardNumber,
                        'ownername' => isset($postData['ownername']) ? $postData['ownername'] : '',
                        'ownermobile' => isset($postData['ownermobile']) ? $postData['ownermobile'] : '',
                    ];
                }
            }

            $deliveryPayer = isset($postData['delivery_payer']) ? $postData['delivery_payer'] : 'Sender';

            $postInfo = array(
                "parcelNumber" => isset($postData['parcel_number']) ? $postData['parcel_number'] : '',
                "sendingDate" => date('d.m.Y'),
                "contractID" => $contractID,
                "COD" => $codAmount,
                "placesItems" => $placesItems,
                "payType" => isset($postData['payment_type']) ? $postData['payment_type'] : "cash",
                "orderNumber" => $postData['order_number'],
                "receiverPay" => $deliveryPayer === 'Receiver' ? true : false,
                "info4Sticker" => true,
                "sender" => $senderData,
                "receiver" => $recipientData
            );

            if (!empty($cardForCOD)) {
                $postInfo['cardForCOD'] = $cardForCOD;
            }

            try {
                $response = $this->meestApiV3("https://api.meest.com/v3.0/openAPI/parcel", $postInfo, null, 'POST');

                $dataResponse = json_decode($response, true);
                

                $status         = isset($dataResponse['status']) ? $dataResponse['status'] : '';
                $message        = isset($dataResponse['info']['message']) ? $dataResponse['info']['message'] : '';
                $fieldName      = isset($dataResponse['info']['fieldName']) ? $dataResponse['info']['fieldName'] : '';
                $messageDetails = isset($dataResponse['info']['messageDetails']) ? $dataResponse['info']['messageDetails'] : '';
                $errorCode      = isset($dataResponse['info']['errorCode']) ? ', errorCode: ' . $dataResponse['info']['errorCode'] : '';

                if (!isset($dataResponse['status']) || $dataResponse['status'] !== "OK") {
                    $this->session->data['error_warning'] = $status . '. ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;

                    $this->response->redirect($this->url->link(
                        'extension/MeestExpress/shipping/meest_express.orderForm',
                        'order_id=' . $order_info['order_id'] . '&user_token=' . $this->session->data['user_token'],
                        true
                    ));
                    return;
                }

                $this->session->data['success'] = $this->language->get('text_success_order_form');

                $parcelID = isset($dataResponse['result']['parcelID']) ? $dataResponse['result']['parcelID'] : '';
                $this->model_extension_MeestExpress_shipping_meest_express->setMeestExpressCnUuid($order_info['order_id'], $parcelID, $contractID, $senderAddressPickUp);
                $this->model_extension_MeestExpress_shipping_meest_express->saveMeestParcelData($order_info['order_id'], $dataResponse['result'], $contractID, $senderAddressPickUp);

                $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
            } catch (Exception $e) {
                $this->session->data['error_warning'] = $e->getMessage();

                $this->response->redirect($this->url->link(
                    'extension/MeestExpress/shipping/meest_express.orderForm',
                    'order_id=' . $order_info['order_id'] . '&user_token=' . $this->session->data['user_token'],
                    true
                ));
                return;
            }
        }

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        $this->load->model('sale/order');
        $order_id = $this->request->get['order_id'];
        $order = $this->model_sale_order->getOrder($order_id);
        $this->load->model('catalog/product');

        $payer_types = array(
            array("Ref" => "Sender", "Description" => $this->language->get('text_sender')),
            array("Ref" => "Receiver", "Description" => $this->language->get('text_receiver'))
        );

        $data['references']['payer_types'] = $payer_types;

        $order_products_details = array();

        $products = $this->model_sale_order->getProducts($order_id);

        $this->load->model('catalog/product');

        foreach ($products as $product) {
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);

            if ($product_info) {
                $order_products_details[] = array(
                    'name'      => $product_info['name'],
                    'weight'    => $product_info['weight'],
                    'length'    => $product_info['length'],
                    'width'     => $product_info['width'],
                    'height'    => $product_info['height'],
                    'quantity'  => $product['quantity']
                );
            }
        }

        $data['order_products'] = $order_products_details;

        if ($order) {
            $data['order_id'] = isset($order['order_id']) ? $order['order_id'] : '';

            $data['firstname'] = isset($order['firstname']) ? $order['firstname'] : '';
            $data['lastname'] = isset($order['lastname']) ? $order['lastname'] : '';
            $data['recipient_contact_person_phone'] = isset($order['telephone']) ? $order['telephone'] : '';

            $data['shipping_firstname'] = isset($order['shipping_firstname']) ? $order['shipping_firstname'] : '';
            $data['shipping_lastname'] = isset($order['shipping_lastname']) ? $order['shipping_lastname'] : '';
            $data['shipping_address_1'] = isset($order['shipping_address_1']) ? $order['shipping_address_1'] : '';
            $data['shipping_address_2'] = isset($order['shipping_address_2']) ? $order['shipping_address_2'] : '';
            $data['shipping_city'] = isset($order['shipping_city']) ? $order['shipping_city'] : '';
            $data['shipping_zone'] = isset($order['shipping_zone']) ? $order['shipping_zone'] : '';
            $data['shipping_country'] = isset($order['shipping_country']) ? $order['shipping_country'] : '';
            $data['shipping_method'] = isset($order['shipping_method']['name']) ? $order['shipping_method']['name'] : '';
            $data['original_shipping_method_code'] = isset($order['shipping_method']['code']) ? $order['shipping_method']['code'] : '';

            $data['recipient_contact_person'] = $data['shipping_lastname'] . ' ' . $data['shipping_firstname'];
        }


        $paymentApiMeestData = $this->getPaymentApiMeestData($contractID);

        $payment_types = array();

        if (!empty($paymentApiMeestData['result']['isAvailableNoncash']) &&
            $paymentApiMeestData['result']['isAvailableNoncash'] === 'true') {
            $payment_types[] = array(
                "Ref" => "nonCash",
                "Description" => $this->language->get('text_non_сash')
            );
        }

        if (!empty($paymentApiMeestData['result']['isAvailableCash']) &&
            $paymentApiMeestData['result']['isAvailableCash'] === 'true') {
            $payment_types[] = array(
                "Ref" => "cash",
                "Description" => $this->language->get('text_cash')
            );
        }

        $data['references']['payment_types'] = $payment_types;

        $fields = [
            'sender',
            'sender_contact_person',
            'sender_region',
            'sender_city',
            'sender_contact_id',
            'departure_type',
            'sender_branch',
            'sender_address_pick_up',
            'sender_address'
        ];

        foreach ($fields as $field) {
            if (isset($this->request->post['shipping_meest_express'][$field])) {
                $data['shipping_meest_express_' . $field] = $this->request->post['shipping_meest_express'][$field];
            } else {
                $data['shipping_meest_express_' . $field] = $this->config->get('shipping_meest_express_' . $field);
            }
        }

        // Also add these for compatibility with JavaScript (meest2 names)
        $data['shipping_meest2_sender_region'] = $this->config->get('shipping_meest_express_sender_region');
        $data['shipping_meest2_sender_city'] = $this->config->get('shipping_meest_express_sender_city');
        $data['shipping_meest2_sender_branch'] = $this->config->get('shipping_meest_express_sender_branch');
        $data['shipping_meest2_sender_contact_person'] = $this->config->get('shipping_meest_express_sender_contact_person');
        $data['shipping_meest2_recipient_city'] = $this->config->get('shipping_meest_express_recipient_city');
        $data['shipping_meest2_recipient_address'] = $this->config->get('shipping_meest_express_recipient_address');

        $data['regions'] = $this->model_extension_MeestExpress_shipping_meest_express->getRegions();

        $data['contracts'] = $this->model_extension_MeestExpress_shipping_meest_express->getContracts();

        $data['contacts'] = $this->model_extension_MeestExpress_shipping_meest_express->getContacts();

        $senderPerson = $this->model_extension_MeestExpress_shipping_meest_express->getContact($this->config->get('shipping_meest_express_sender_contact_person'));

        $nameParts = array_filter([
            isset($senderPerson['lastname']) ? $senderPerson['lastname'] : '',
            isset($senderPerson['firstname']) ? $senderPerson['firstname'] : '',
            isset($senderPerson['middlename']) ? $senderPerson['middlename'] : ''
        ]);

        $data['sender_person'] = implode(' ', $nameParts);

        $data['sender_phone']  = isset($senderPerson['phone']) ? $senderPerson['phone'] : '';

        // Get sender cities and branches based on configured region and city
        $data['sender_cities'] = [];
        $data['sender_branches'] = [];
        
        $sender_region_id = $this->config->get('shipping_meest_express_sender_region');
        $sender_city_id = $this->config->get('shipping_meest_express_sender_city');
        
        if ($sender_region_id) {
            $data['sender_cities'] = $this->model_extension_MeestExpress_shipping_meest_express->getCitiesByRegion($sender_region_id);
        }
        
        if ($sender_city_id) {
            $data['sender_branches'] = $this->model_extension_MeestExpress_shipping_meest_express->getBranchesByCity($sender_city_id);
        }

        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getCitiesByRegion', 'user_token=' . $this->session->data['user_token'], true));

        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getStreetsByCity', 'user_token=' . $this->session->data['user_token'], true));

        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getBranchesByCity', 'user_token=' . $this->session->data['user_token'], true));

        $this->load->model('setting/setting');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_sale_order'),
                'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('heading_title_order_form'),
                'href' => $this->url->link('extension/MeestExpress/shipping/meest_express.orderForm', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true)
            ]
        ];
        $data['action'] = $this->url->link('extension/MeestExpress/shipping/meest_express.orderForm', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        $data['shipping_meest_express_recipient'] = $this->config->get('shipping_meest_express_recipient');
        $data['shipping_meest_express_recipient_contact_person'] = $this->config->get('shipping_meest_express_recipient_contact_person');

        $data['order_shipping_data'] = $this->model_extension_MeestExpress_shipping_meest_express->getOrderShippingData($data['order_id']);
       
        // Отримуємо назву міста з таблиці meest2_cities по UUID
        if (!empty($data['order_shipping_data']['city_code'])) {
            $city_query = $this->db->query("SELECT name_ua FROM " . DB_PREFIX . "meest_express_cities WHERE city_id = '" . $this->db->escape($data['order_shipping_data']['city_code']) . "'");
            if ($city_query->num_rows) {
                $data['order_shipping_data']['city_name'] = $city_query->row['name_ua'];
            }
        }
        
        // Get recipient city, branch, and address data from order_shipping_data
        $data['recipient_city_data'] = null;
        $data['recipient_branch_data'] = null;
        $data['recipient_address_data'] = null;
        
        if (!empty($data['order_shipping_data']['city_code'])) {
            // Get city data by code
            $city_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities WHERE city_id = '" . $this->db->escape($data['order_shipping_data']['city_code']) . "'");
            if ($city_query->num_rows) {
                $data['recipient_city_data'] = $city_query->row;
            }
        } elseif (!empty($data['shipping_city'])) {
            // Fallback: try to find city by name from order shipping data
            $city_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities WHERE name_ua LIKE '%" . $this->db->escape($data['shipping_city']) . "%' LIMIT 1");
            if ($city_query->num_rows) {
                $data['recipient_city_data'] = $city_query->row;
            }
        }
        
        if (!empty($data['order_shipping_data']['branch_code'])) {
            // Get branch data
            $branch_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_branch WHERE branch_id = '" . $this->db->escape($data['order_shipping_data']['branch_code']) . "'");
            if ($branch_query->num_rows) {
                $data['recipient_branch_data'] = $branch_query->row;
            }
        }
        
        if (!empty($data['order_shipping_data']['address_code'])) {
            // Get address/street data from address_code
            $address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets WHERE street_id = '" . $this->db->escape($data['order_shipping_data']['address_code']) . "'");
            if ($address_query->num_rows) {
                $data['recipient_address_data'] = $address_query->row;
            } else {
                // Якщо в БД немає - спробуємо отримати з API
                if (!empty($data['order_shipping_data']['city_code'])) {
                    $this->load->library('meest_express/meest');
                    $meest = new \Opencart\System\Library\MeestExpress\Meest($this->registry);
                    
                    $result = $meest->geo_streets(['city_id' => $data['order_shipping_data']['address_code']]);
                    
                    if (!empty($result['result'])) {
                        foreach ($result['result'] as $street) {
                            if ($street['street_id'] === $data['order_shipping_data']['address_code']) {
                                // Створюємо масив у форматі, схожому на запис з БД
                                $data['recipient_address_data'] = [
                                    'street_id' => $street['street_id'],
                                    'type_ua' => $street['t_ua'],
                                    'name_ua' => $street['ua']
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        } elseif (!empty($data['order_shipping_data']['branch_code']) && (strpos($data['order_shipping_data']['shipping_method'], 'door') !== false || strpos($data['order_shipping_data']['shipping_method'], 'courier') !== false)) {
            // Fallback: for courier delivery, address might be stored in branch_code
            $address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets WHERE street_id = '" . $this->db->escape($data['order_shipping_data']['branch_code']) . "'");
            if ($address_query->num_rows) {
                $data['recipient_address_data'] = $address_query->row;
            }
        }



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_order_form', $data));
    }

    protected function validateOrderForm() {
        if (!$this->user->hasPermission('modify', 'extension/MeestExpress/shipping/meest_express')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function getPaymentApiMeestData($contractID) {
        // Mock implementation - replace with actual API call if needed
        return [
            'result' => [
                'isAvailableNoncash' => 'true',
                'isAvailableCash' => 'true'
            ]
        ];
    }
    
    /**
     * Create Meest CN for order
     */
    public function createMeestCn()
    {
        // TODO: Implement CN creation logic
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
            'message' => 'CN creation will be implemented'
        ]));
    }
    
    /**
     * Get Parcel Info from Meest API
     */
    public function getParcelInfoFromAPI($parcelId) {
        $apiUrl = "https://api.meest.com/v3.0/openAPI/getParcel/" . $parcelId . "/parcelID/objectData";

        try {
            $response = $this->meestApiV3($apiUrl, array(), null, 'GET');
        } catch (Exception $e) {
            throw new \Exception("cURL error occurred: " . $e->getMessage());
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON decode error: " . json_last_error_msg());
        }

        if (!isset($data['result'])) {
            throw new \Exception("Unexpected response structure: missing 'result' key.");
        }

        return $data;
    }

    /**
     * View Parcel Info Page
     */
    public function viewParcelInfo() {
        $this->load->language('extension/MeestExpress/shipping/meest_express');

        $this->document->setTitle($this->language->get('text_parcel_info'));

        $parcel_id = $this->request->get['parcel_id'];

        try {
            $data['response'] = $this->getParcelInfoFromAPI($parcel_id);
            if($data['response']['status'] === 'error') {
                $status = isset($data['response']['status']) ? $data['response']['status'] : '';
                $message = isset($data['response']['info']['message']) ? $data['response']['info']['message'] : '';
                $fieldName = isset($data['response']['info']['fieldName']) ? $data['response']['info']['fieldName'] : '';
                $messageDetails = isset($data['response']['info']['messageDetails']) ? $data['response']['info']['messageDetails'] : '';
                $errorCode = isset($data['response']['info']['errorCode']) ? ', errorCode' . ': ' . $data['response']['info']['errorCode'] : '';
                $data['get_info_error'] = $status . ' ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;
            }
        } catch (\Exception $e) {
            $this->session->data['error'] = $e->getMessage();
           return $e->getMessage();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_sale_order'),
                'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('text_parcel_info'),
                'href' => $this->url->link('extension/MeestExpress/shipping/meest_express.viewParcelInfo', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_parcel_info', $data));
    }

    /**
     * Parcel List Page - shows all orders with Meest CN
     */
    public function parcelList() {
        $this->load->language('extension/MeestExpress/shipping/meest_express');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $sort_by = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'order_id';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';

        $data['orders'] = $this->model_extension_MeestExpress_shipping_meest_express->getOrders($page, $sort_by, $order);
        $total_orders = $this->model_extension_MeestExpress_shipping_meest_express->getTotalOrders();
        
        // Pagination
        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $total_orders,
            'page'  => $page,
            'limit' => 10,
            'url'   => $this->url->link('extension/MeestExpress/shipping/meest_express.parcelList', 'user_token=' . $this->session->data['user_token'] . '&sort=' . $sort_by . '&order=' . $order . '&page={page}')
        ]);
        
        $url_link = $this->url->link('extension/MeestExpress/shipping/meest_express.parcelList', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_link'] = $url_link;

        $data['sort'] = $sort_by;
        $data['order'] = $order;

        $data['create_register_pickup_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.createRegisterPickup', 'user_token=' . $this->session->data['user_token'], true));
        $data['available_time_slots'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getAvailableTimeSlots', 'user_token=' . $this->session->data['user_token'], true));
        $data['unregisterPickup_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.unregisterPickup', 'user_token=' . $this->session->data['user_token'], true));
        $data['get_parcel_uuids'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getParcelUUIDs', 'user_token=' . $this->session->data['user_token'], true));
        $data['get_register_ids'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getRegisterIDs', 'user_token=' . $this->session->data['user_token'], true));
        $data['get_parcel_info'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.viewParcelInfo', 'user_token=' . $this->session->data['user_token'], true));
        $data['order_update_form'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.orderUpdateForm', 'user_token=' . $this->session->data['user_token'], true));
        $data['order_form'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.orderForm', 'user_token=' . $this->session->data['user_token'], true));
        $data['update_order_statuses'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.updateOrderStatuses', 'user_token=' . $this->session->data['user_token'], true));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_parcel_list', $data));
    }
    
    /**
     * Order Update Form - Edit existing Meest CN
     */
    public function orderUpdateForm() {
        $this->load->language('extension/MeestExpress/shipping/meest_express');

        $this->document->setTitle($this->language->get('heading_title_order_update_form'));

        // Add CSS/JS for datepicker and select2
        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js');

        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');

        $this->load->model('extension/MeestExpress/shipping/meest_express');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateOrderForm()) {
            $this->load->model('sale/order');

            $postData = $this->request->post;

            $places = $postData['places'];
            $placesItems = array();

            foreach ($places as $place) {
                $height = isset($place['height']) ? $place['height'] : 0;
                $length = isset($place['length']) ? $place['length'] : 0;
                $width = isset($place['width']) ? $place['width'] : 0;

                $placesItems[] = array(
                    "formatID"  => '',
                    "insurance" => isset($place['insurance']) ? $place['insurance'] : 0,
                    "height" => $height,
                    "length" => $length,
                    "quantity" => isset($place['quantity']) ? $place['quantity'] : 1,
                    "width" => $width,
                    "weight" => isset($place['weight']) ? $place['weight'] : 0,
                    "volume" => ($length * $width * $height) / 100
                );
            }
            $senderAddressPickUp = 0;

            if($postData['sender_delivery_type'] === 'doors'){
                $senderData = array(
                    "name" => $postData['sender_person-address'],
                    "phone" => $postData['sender_phone-address'],
                    "service" => "Door",
                    "addressID" => $postData['sender_address'],
                    "cityID"    => $postData['sender_city_address'],
                    "building"  => $postData['sender_building'],
                    "floor"   => $postData['sender_floor'],
                    "flat"   => $postData['sender_apartment']
                );

                $senderAddressPickUp = 1;
            }else{
                $senderData = array(
                    "name" => $postData['sender_person'],
                    "phone" => $postData['sender_phone'],
                    "service" => "Branch",
                    "branchID" => $postData['sender_branch']
                );
            }

            if($postData['recipient_delivery_type'] === 'doors'){
                $recipientData = array(
                    "name" => $postData['recipient-address'],
                    "phone" => $postData['recipient_phone-address'],
                    "service" => "Door",
                    "addressID" => $postData['recipient_address'],
                    "cityID"    => $postData['recipient_city_address'],
                    "building"  => $postData['recipient_building_address'],
                    "floor"   => $postData['recipient_floor_address'],
                    "flat"   => $postData['recipient_apartment_address']
                );
            }else{
                $recipientData = array(
                    "name" => $postData['recipient'],
                    "phone" => $postData['recipient_phone'],
                    "service" => "Branch",
                    "branchID" => $postData['recipient_branch']
                );
            }

            $codAmount = isset($postData['cod_amount']) ? $postData['cod_amount'] : 0;

            $cardForCOD = [];
            if ($codAmount) {
                $cardNumber = isset($postData['card_number']) ? $postData['card_number'] : '';
                if($cardNumber) {
                    $cardForCOD = [
                        'number' => $cardNumber,
                        'ownername' => isset($postData['ownername']) ? $postData['ownername'] : '',
                        'ownermobile' => isset($postData['ownermobile']) ? $postData['ownermobile'] : '',
                    ];
                }
            }

            $contractID = $this->model_extension_MeestExpress_shipping_meest_express->getContractIdByUuid($postData['meest_express_cn_uuid']);
            $postInfo = array(
                "sendingDate" => date('d.m.Y'),
                "contractID"  => $contractID,
                "COD"         => isset($postData['cod_amount']) ? $postData['cod_amount'] : "0",
                "placesItems" => $placesItems,
                "payType"     => $postData['payment_type'],
                "receiverPay" => $postData['delivery_payer'] === 'Receiver' ? true : false,
                "sender"      => $senderData,
                "receiver"    => $recipientData
            );

            if (!empty($cardForCOD)) {
                $postInfo['cardForCOD'] = $cardForCOD;
            }

           try {
                $url = "https://api.meest.com/v3.0/openAPI/parcel/" . $postData['meest_express_cn_uuid'];
                $response = $this->meestApiV3($url, $postInfo, null, 'PUT');

                $dataResponse = json_decode($response, true);

                $status         = isset($dataResponse['status']) ? $dataResponse['status'] : '';
                $message        = isset($dataResponse['info']['message']) ? $dataResponse['info']['message'] : '';
                $fieldName      = isset($dataResponse['info']['fieldName']) ? $dataResponse['info']['fieldName'] : '';
                $messageDetails = isset($dataResponse['info']['messageDetails']) ? $dataResponse['info']['messageDetails'] : '';
                $errorCode      = isset($dataResponse['info']['errorCode']) ? ', errorCode: ' . $dataResponse['info']['errorCode'] : '';

                if (!isset($dataResponse['status']) || $dataResponse['status'] !== "OK") {
                    $this->session->data['get_info_for_edit_error'] = $status . '. ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;

                    $this->response->redirect($this->url->link(
                        'extension/MeestExpress/shipping/meest_express.orderUpdateForm',
                        'parcel_id=' . $postData['meest_express_cn_uuid'] . '&user_token=' . $this->session->data['user_token'],
                        true
                    ));
                    return;
                }

                $this->session->data['success'] = $this->language->get('text_success_update_order_form');
                $this->model_extension_MeestExpress_shipping_meest_express->setMeestExpressCnSenderAddressPickUp($senderAddressPickUp, $postData['meest_express_cn_uuid']);

                $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
            } catch (\Exception $e) {
                $this->session->data['get_info_for_edit_error'] = $e->getMessage();

                $this->response->redirect($this->url->link(
                    'extension/MeestExpress/shipping/meest_express.orderUpdateForm',
                    'parcel_id=' . $postData['meest_express_cn_uuid'] . '&user_token=' . $this->session->data['user_token'],
                    true
                ));
                return;
            }
        }

        try {
            $parcelInfo = $this->getParcelInfoFromAPI($this->request->get['parcel_id']);
            $data['response'] = $parcelInfo;
            $data['parcel_info'] = $parcelInfo['result'];

            if($parcelInfo['status'] === 'error') {
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');
                $status = isset($data['response']['status']) ? $data['response']['status'] : '';
                $message = isset($data['response']['info']['message']) ? $data['response']['info']['message'] : '';
                $fieldName = isset($data['response']['info']['fieldName']) ? $data['response']['info']['fieldName'] : '';
                $messageDetails = isset($data['response']['info']['messageDetails']) ? $data['response']['info']['messageDetails'] : '';
                $errorCode = isset($data['response']['info']['errorCode']) ? ', errorCode' . ': ' . $data['response']['info']['errorCode'] : '';
                $data['get_info_error'] = $status . ' ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;

                $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_update_shipment_form', $data));

                return;
            }
        } catch (\Exception $e) {
            $this->session->data['error'] = $e->getMessage();

            $data['error_meesage'] = $e->getMessage();
            $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_update_shipment_form', $data));

        }

        if (isset($this->session->data['get_info_for_edit_error'])) {
            $data['get_info_for_edit_error'] = $this->session->data['get_info_for_edit_error'];
            unset($this->session->data['get_info_for_edit_error']);
        } else {
            $data['get_info_for_edit_error'] = '';
        }

        if($data['parcel_info']['sender']['service'] === 'Door'){
            $address = $this->model_extension_MeestExpress_shipping_meest_express->getStreet($data['parcel_info']['sender']['addressID']);
            $city = $this->model_extension_MeestExpress_shipping_meest_express->getCity($address['city_id']);
            $data['sender_city_id_address'] = $address['city_id'];
            $data['sender_region_id_address'] = isset($city['region_id']) ? $city['region_id'] : 0;
        }

        if($data['parcel_info']['receiver']['service'] === 'Door'){
            $address = $this->model_extension_MeestExpress_shipping_meest_express->getStreet($data['parcel_info']['receiver']['addressID']);
            $city = $this->model_extension_MeestExpress_shipping_meest_express->getCity($address['city_id']);
            $data['receiver_city_id_address'] = $address['city_id'];
            $data['receiver_region_id_address'] = isset($city['region_id']) ? $city['region_id'] : 0;
        }
        
        // Get branch info for sender
        $data['branch_sender_info'] = [];
        if (!empty($data['parcel_info']['sender']['branchID'])) {
            $branch = $this->model_extension_MeestExpress_shipping_meest_express->getBranchById($data['parcel_info']['sender']['branchID']);
            if ($branch) {
                $data['branch_sender_info'] = $branch;
                // Get city info for sender branch
                if (!empty($branch['city_id'])) {
                    $sender_city = $this->model_extension_MeestExpress_shipping_meest_express->getCity($branch['city_id']);
                    if ($sender_city) {
                        $data['branch_sender_info']['city_name_ua'] = $sender_city['name_ua'];
                        $data['branch_sender_info']['region_id'] = $sender_city['region_id'];
                    }
                }
            }
        }
        
        // Get branch info for recipient
        $data['branch_recipient_info'] = [];
        if (!empty($data['parcel_info']['receiver']['branchID'])) {
            $branch = $this->model_extension_MeestExpress_shipping_meest_express->getBranchById($data['parcel_info']['receiver']['branchID']);
            if ($branch) {
                $data['branch_recipient_info'] = $branch;
                // Get city info for recipient branch
                if (!empty($branch['city_id'])) {
                    $recipient_city = $this->model_extension_MeestExpress_shipping_meest_express->getCity($branch['city_id']);
                    if ($recipient_city) {
                        $data['branch_recipient_info']['city_name_ua'] = $recipient_city['name_ua'];
                        $data['branch_recipient_info']['region_id'] = $recipient_city['region_id'];
                    }
                }
            }
        }

        $this->load->model('sale/order');

        $payer_types = array(
            array("Ref" => "Sender", "Description" => $this->language->get('text_sender')),
            array("Ref" => "Receiver", "Description" => $this->language->get('text_receiver'))
        );
        $data['references']['payer_types'] = $payer_types;

        $contractID = $this->model_extension_MeestExpress_shipping_meest_express->getContractIdByUuid($this->request->get['parcel_id']);
        $paymentApiMeestData = $this->getPaymentApiMeestData($contractID);

        $payment_types = array();

        if (!empty($paymentApiMeestData['result']['isAvailableNoncash']) &&
            $paymentApiMeestData['result']['isAvailableNoncash'] === 'true') {
            $payment_types[] = array(
                "Ref" => "nonCash",
                "Description" => $this->language->get('text_non_сash')
            );
        }

        if (!empty($paymentApiMeestData['result']['isAvailableCash']) &&
            $paymentApiMeestData['result']['isAvailableCash'] === 'true') {
            $payment_types[] = array(
                "Ref" => "cash",
                "Description" => $this->language->get('text_cash')
            );
        }

        $data['references']['payment_types'] = $payment_types;

        $data['regions'] = $this->model_extension_MeestExpress_shipping_meest_express->getRegions();

        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getCitiesByRegion', 'user_token=' . $this->session->data['user_token'], true));

        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getStreetsByCity', 'user_token=' . $this->session->data['user_token'], true));

        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/MeestExpress/shipping/meest_express.getBranchesByCity', 'user_token=' . $this->session->data['user_token'], true));

        $this->load->model('setting/setting');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_sale_order'),
                'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('heading_title_order_update_form'),
                'href' => $this->url->link('extension/MeestExpress/shipping/meest_express.orderUpdateForm', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];
        $data['action'] = $this->url->link('extension/MeestExpress/shipping/meest_express.orderUpdateForm', 'parcel_id=' . $this->request->get['parcel_id'] . '&user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/MeestExpress/shipping/meest_express_update_shipment_form', $data));
    }

    /**
     * Get Parcel UUIDs for selected orders
     */
    public function getParcelUUIDs() {
        $this->load->model('sale/order');
        $this->load->model('extension/MeestExpress/shipping/meest_express');

        $order_ids = $this->request->post['orders'];

        $uuids = [];

        foreach ($order_ids as $order_id) {
            $order_info = $this->model_extension_MeestExpress_shipping_meest_express->getOrderById($order_id);
            if ($order_info && isset($order_info['meest_express_cn_uuid']) && !empty($order_info['meest_express_cn_uuid'])) {
                $uuids[] = $order_info['meest_express_cn_uuid'];
            }
        }

        if (!empty($uuids)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => true, 'uuids' => $uuids]));
        } else {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => false, 'message' => 'Parcel UUID not found.']));
        }
    }
}