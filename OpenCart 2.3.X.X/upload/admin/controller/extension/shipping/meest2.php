<?php
class ControllerExtensionShippingMeest2 extends Controller {

	private $error = [];

	public function index()	 {

        $this->load->model('extension/shipping/meest2');

        $this->model_extension_shipping_meest2->install(false);

		$data = $this->load->language('extension/shipping/meest2');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

        $this->document->addStyle('view/stylesheet/meest/meest2.css');
        $this->document->addScript('view/javascript/meest/meest2.js');

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('meest2', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/shipping/meest2', 'token=' . $this->session->data['token'] . '&type=shipping', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit']      = $this->language->get('text_edit');
		$data['text_enabled']   = $this->language->get('text_enabled');
		$data['text_disabled']  = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none']      = $this->language->get('text_none');

		$data['entry_cost']       = $this->language->get('entry_cost');
		$data['entry_tax_class']  = $this->language->get('entry_tax_class');
		$data['entry_geo_zone']   = $this->language->get('entry_geo_zone');
		$data['entry_status']     = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_free_shipping_enabled']  = $this->language->get('entry_free_shipping_enabled');
		$data['entry_free_shipping_threshold'] = $this->language->get('entry_free_shipping_threshold');

		$data['button_save']   = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

        $data['calculation_in_checkout'] = $this->language->get('calculation_in_checkout');
        $data['calculation_in_checkout_info'] = $this->language->get('calculation_in_checkout_info');

        if (isset($this->request->post['meest2_login'])) {
            $data['meest2_login'] = $this->request->post['meest2_login'];
        } else {
            $data['meest2_login'] = $this->config->get('meest2_login');
        }
        if (isset($this->request->post['meest2_password'])) {
            $data['meest2_password'] = $this->request->post['meest2_password'];
        } else {
            $data['meest2_password'] = $this->config->get('meest2_password');
        }
        if (isset($this->request->post['meest2_api_key'])) {
            $data['meest2_api_key'] = $this->request->post['meest2_api_key'];
        } else {
            $data['meest2_api_key'] = $this->config->get('meest2_api_key');
        }

        if (isset($this->request->post['meest2_auth_mode'])) {
            $data['meest2_auth_mode'] = $this->request->post['meest2_auth_mode'];
        } else {
            $data['meest2_auth_mode'] = $this->config->get('meest2_auth_mode');
        }

        if (isset($this->request->post['meest2_sender_city'])) {
            $data['meest2_sender_city'] = $this->request->post['meest2_sender_city'];
        } else {
            $data['meest2_sender_city'] = $this->config->get('meest2_sender_city');
        }

        if (isset($this->request->post['meest2_sender_address'])) {
            $data['meest2_sender_address'] = $this->request->post['meest2_sender_address'];
        } else {
            $data['meest2_sender_address'] = $this->config->get('meest2_sender_address');
        }

        if (isset($this->request->post['meest2_sender_branch'])) {
            $data['meest2_sender_branch'] = $this->request->post['meest2_sender_branch'];
        } else {
            $data['meest2_sender_branch'] = $this->config->get('meest2_sender_branch');
        }

        if (isset($this->request->post['meest2_sender_contract_id'])) {
            $data['meest2_sender_contract_id'] = $this->request->post['meest2_sender_contract_id'];
        } else {
            $data['meest2_sender_contract_id'] = $this->config->get('meest2_sender_contract_id');
        }

        if (isset($this->request->post['meest2_sender_address_pick_up'])) {
            $data['meest2_sender_address_pick_up'] = $this->request->post['meest2_sender_address_pick_up'];
        } else {
            $data['meest2_sender_address_pick_up'] = $this->config->get('meest2_sender_address_pick_up');
        }

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

        $fields = [
            'recipient',
            'recipient_contact_person',
            'recipient_phone',
            'recipient_edrpou',
            'recipient_region',
            'recipient_city',
            'recipient_branch',
            'recipient_address',
            'recipient_street',
            'recipient_house',
            'recipient_flat',
            'recipient_date',
            'recipient_time'
        ];

        foreach ($fields as $field) {
            if (isset($this->request->post['meest2'][$field])) {
                $data['meest2_' . $field] = $this->request->post['meest2'][$field];
            } else {
                $data['meest2_' . $field] = $this->config->get('meest2_' . $field);
            }
        }

        $fields = [
            'sender',
            'sender_contact_person',
            'sender_region',
            'sender_city',
            'sender_contact_id',
            'departure_type'
        ];

        foreach ($fields as $field) {
            if (isset($this->request->post['meest2'][$field])) {
                $data['meest2_' . $field] = $this->request->post['meest2'][$field];
            } else {
                $data['meest2_' . $field] = $this->config->get('meest2_' . $field);
            }
        }

	   $data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
			],
			[
				'text' => $this->language->get('text_shipping'),
				'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true)
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/shipping/meest2', 'token=' . $this->session->data['token'], true)
			]
		];

		$data['action'] = $this->url->link('extension/shipping/meest2', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true);

		if (isset($this->request->post['meest2_cost'])) {
			$data['meest2_cost'] = $this->request->post['meest2_cost'];
		} elseif ($this->config->has('meest2_cost')) {
			$data['meest2_cost'] = $this->config->get('meest2_cost');
		} else {
			$data['meest2_cost'] = array();
		}

		if (isset($this->request->post['meest2_tax_class_id'])) {
			$data['meest2_tax_class_id'] = $this->request->post['meest2_tax_class_id'];
		} else {
			$data['meest2_tax_class_id'] = $this->config->get('meest2_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['meest2_geo_zone_id'])) {
			$data['meest2_geo_zone_id'] = $this->request->post['meest2_geo_zone_id'];
		} else {
			$data['meest2_geo_zone_id'] = $this->config->get('meest2_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['meest2_calculation_in_checkout'])) {
            $data['meest2_calculation_in_checkout'] = $this->request->post['meest2_calculation_in_checkout'];
        } else {
            $data['meest2_calculation_in_checkout'] = $this->config->get('meest2_calculation_in_checkout');
        }

		if (isset($this->request->post['meest2_status'])) {
			$data['meest2_status'] = $this->request->post['meest2_status'];
		} else {
			$data['meest2_status'] = $this->config->get('meest2_status');
		}

		if (isset($this->request->post['meest2_sort_order'])) {
			$data['meest2_sort_order'] = $this->request->post['meest2_sort_order'];
		} else {
			$data['meest2_sort_order'] = $this->config->get('meest2_sort_order');
		}

		if (isset($this->request->post['meest2_free_shipping_enabled'])) {
			$data['meest2_free_shipping_enabled'] = $this->request->post['meest2_free_shipping_enabled'];
		} else {
			$data['meest2_free_shipping_enabled'] = $this->config->get('meest2_free_shipping_enabled');
		}

		if (isset($this->request->post['meest2_free_shipping_threshold'])) {
			$data['meest2_free_shipping_threshold'] = $this->request->post['meest2_free_shipping_threshold'];
		} else {
			$data['meest2_free_shipping_threshold'] = $this->config->get('meest2_free_shipping_threshold');
		}

		if (isset($this->request->post['meest2_service'])) {
			$data['meest2_service'] = $this->request->post['meest2_service'];
		} elseif ($this->config->has('meest2_service')) {
			$data['meest2_service'] = $this->config->get('meest2_service');
		} else {
			$data['meest2_service'] = [];
		}

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
		$data['branches'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/branches','token=' . $this->session->data['token'],true));
		$data['branchesUpdate'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/branchesUpdate','token=' . $this->session->data['token'],true));

        $data['importBranches'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/importBranches','token=' . $this->session->data['token'],true));
        $data['importRegions'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/importRegions', 'token=' . $this->session->data['token'], true));
        $data['importCity'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/importCity', 'token=' . $this->session->data['token'], true));
        $data['importStreets'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/importStreets', 'token=' . $this->session->data['token'], true));
        $data['addContract'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/addContract', 'token=' . $this->session->data['token'], true));
        $data['addContact'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/addContact', 'token=' . $this->session->data['token'], true));
        $data['deleteContract'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/deleteContract', 'token=' . $this->session->data['token'], true));
        $data['deleteContact'] = str_replace('&amp;','&',$this->url->link('extension/shipping/meest2/deleteContact', 'token=' . $this->session->data['token'], true));
        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getCitiesByRegion', 'token=' . $this->session->data['token'], true));
        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getBranchesByCity', 'token=' . $this->session->data['token'], true));
        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getStreetsByCity', 'token=' . $this->session->data['token'], true));

//        $this->load->model('extension/shipping/meest2');

        $data['branch_import_info'] = $this->model_extension_shipping_meest2->getBranchTotalRecordsAndLatestDate();
        $data['regions_import_info'] = $this->model_extension_shipping_meest2->getRegionsTotalRecordsAndLatestDate();
        $data['cities_import_info'] = $this->model_extension_shipping_meest2->getCitiesTotalRecordsAndLatestDate();
        $data['streets_import_info'] = $this->model_extension_shipping_meest2->getStreetsTotalRecordsAndLatestDate();

        $data['regions'] = $this->model_extension_shipping_meest2->getRegions();
//        $data['cities'] = $this->model_extension_shipping_meest2->getCities();//[];
//        $data['addresses'] = $this->model_extension_shipping_meest2->getStreets();//[];
//        $data['branches'] = $this->model_extension_shipping_meest2->getBranches();//[];

        $data['contracts'] = $this->model_extension_shipping_meest2->getContracts();

        $data['contacts'] = $this->model_extension_shipping_meest2->getContacts();

        if (isset($this->session->data['error_warning_meest'])) {
            $data['error_warning_meest'] = $this->session->data['error_warning_meest'];
            unset($this->session->data['error_warning_meest']);
        } else {
            $data['error_warning_meest'] = null;
        }

        $domain = $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG;

        $data['cron_command'] = '0 2 * * * curl -s "' . $domain . 'index.php?route=extension/shipping/meest2_cron/updateAll"';


        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/meest2', $data));
	}

	public function install() {

		$this->load->model('extension/event');

		        $this->model_extension_event->addEvent(
            'meest2_header_assets',
            'catalog/controller/common/header/before',
            'extension/module/meest2/addAssets'
        );
        
        $this->model_extension_event->addEvent(
            'meest2_after_add_order',
            'catalog/model/checkout/order/addOrder/after',
            'extension/module/meest2/afterAddOrder'
        );

        $this->load->model('extension/shipping/meest2');
		$this->model_extension_shipping_meest2->install(true);
	}

	public function uninstall() {

		$this->load->model('extension/event');
        $this->model_extension_event->deleteEventByCode('meest2_header_assets');
        $this->model_extension_event->deleteEventByCode('meest2_after_add_order');

	}

    public function importBranches() {
        $this->load->model('extension/shipping/meest2');

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
                throw new Exception('API Error: ' . json_encode($responseData, JSON_UNESCAPED_UNICODE));
            }

            $resultData = $this->model_extension_shipping_meest2->saveBranchesBatch($responseData['result']);

            $json = ['success' => true, 'data' => $resultData];
        } catch (Exception $e) {
            $json = ['success' => false, 'error' => $e->getMessage()];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/meest2')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

    public function importRegions() {

        $json = [];

        // Фіксований масив зіставлень zone_id та region_id
        $zoneRegionMap = [
            '3480' => 'd15e302f-60b0-11de-be1e-0030485903e8', // Черкаська область
            '3481' => 'd15e3031-60b0-11de-be1e-0030485903e8', // Чернігівська область
             '3482' => 'd15e3030-60b0-11de-be1e-0030485903e8', // Чернівецька область
             // '3483' => 'd15e3029-60b0-11de-be1e-0030485903e8', // Крим
             '3484' => 'd15e301b-60b0-11de-be1e-0030485903e8', // Дніпропетровська область
             '3485' => 'd15e301c-60b0-11de-be1e-0030485903e8', // Донецька область
             '3486' => 'd15e3020-60b0-11de-be1e-0030485903e8', // Івано-Франківська область
             '3487' => 'd15e302d-60b0-11de-be1e-0030485903e8', // Херсонська область
             '3488' => 'd15e302e-60b0-11de-be1e-0030485903e8', // Хмельницька область
             '3489' => 'd15e3022-60b0-11de-be1e-0030485903e8', // Кіровоградська область
             '3490' => 'd15e3021-60b0-11de-be1e-0030485903e8', // Київ
             // '3491' => 'd15e3023-60b0-11de-be1e-0030485903e8', // Київська область
             '3492' => 'd15e3023-60b0-11de-be1e-0030485903e8', // Луганська область
             '3493' => 'd15e3024-60b0-11de-be1e-0030485903e8', // Львівська область
             '3494' => 'd15e3025-60b0-11de-be1e-0030485903e8', // Миколаївська область
             '3495' => 'd15e3026-60b0-11de-be1e-0030485903e8', // Одеська область
             '3496' => 'd15e3027-60b0-11de-be1e-0030485903e8', // Полтавська область
             '3497' => 'd15e3028-60b0-11de-be1e-0030485903e8', // Рівненська область
             // '3498' => 'd15e3029-60b0-11de-be1e-0030485903e8', // Севастополь
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
                $this->load->model('extension/shipping/meest2');
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
                }

            } else {
                throw new Exception('API Error: ' . json_encode($responseData));
            }

            $json['success'] = true;
            $json['data'] = true;
        } catch (Exception $e) {
            $json['success'] = false;
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function importCity() {
        $json = [];

        try {
            $url = 'https://meest-group.com/media/location/cities.txt';

            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception('Unable to fetch data from URL.');
            }

            $response = mb_convert_encoding($response, 'UTF-8', 'Windows-1251');
            $lines = explode("\n", $response);

            $this->load->model('extension/shipping/meest2');

            // Загружаем все существующие города из базы данных одним запросом
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

            $json['success'] = true;
            $json['data'] = true;
        } catch (Exception $e) {
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
            $this->load->model('extension/shipping/meest2');

            $result = $this->model_extension_shipping_meest2->importStreets();

            $json['success'] = true;
            $json['message'] = 'Import completed successfully.';
            $json['data'] = $result;
        } catch (InvalidURLException $e) {
            http_response_code(400);
            $json['success'] = false;
            $json['error'] = 'Invalid URL: ' . $e->getMessage();
        } catch (DataFetchException $e) {
            http_response_code(502);
            $json['success'] = false;
            $json['error'] = 'Error while retrieving data: ' . $e->getMessage();
        } catch (Exception $e) {
            http_response_code(500);
            $json['success'] = false;
            $json['error'] = 'Internal Server Error: ' . $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }



    public function addContract() {
        $this->load->language('extension/shipping/meest2');
        $json = [];
        if (isset($this->request->post['contract_id']) && $this->request->post['contract_id']) {
            $contract_id = $this->request->post['contract_id'];

            $this->load->model('extension/shipping/meest2');
            $this->model_extension_shipping_meest2->addContract($contract_id);

            $json['success'] = $this->language->get('text_success_add');
        } else {
            $json['error'] = $this->language->get('error_contract_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteContract() {
        $this->load->language('extension/shipping/meest2');
        $json = [];

        if (isset($this->request->post['contract_id']) && $this->request->post['contract_id']) {
            $contract_id = $this->request->post['contract_id'];

            $this->load->model('extension/shipping/meest2');
            $this->model_extension_shipping_meest2->deleteContract($contract_id);

            $json['success'] = $this->language->get('text_success_delete');
        } else {
            $json['error'] = $this->language->get('error_contract_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addContact() {
        $this->load->language('extension/shipping/meest2');
        $this->load->model('extension/shipping/meest2');

        $json = array();

        if (isset($this->request->post['phone']) && isset($this->request->post['firstname']) && isset($this->request->post['lastname']) && isset($this->request->post['middlename'])) {
            $phone = $this->request->post['phone'];
            $firstname = $this->request->post['firstname'];
            $lastname = $this->request->post['lastname'];
            $middlename = $this->request->post['middlename'];

            $this->model_extension_shipping_meest2->addContact($phone, $firstname, $lastname, $middlename);

            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('text_error_fill_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteContact() {
        $this->load->language('extension/shipping/meest2');
        $this->load->model('extension/shipping/meest2');

        $json = array();

        if (isset($this->request->post['contact_id'])) {
            $contact_id = $this->request->post['contact_id'];

            $this->model_extension_shipping_meest2->deleteContact($contact_id);

            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('text_error');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getContacts() {
        $this->load->model('extension/shipping/meest2');

        $data['contacts'] = $this->model_extension_shipping_meest2->getContacts();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getCitiesByRegion() {
        $json = [];

        if (isset($this->request->get['region_id'])) {
            $region_id = $this->request->get['region_id'];
            $this->load->model('extension/shipping/meest2');

            $json = $this->model_extension_shipping_meest2->getCitiesByRegion($region_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function searchCities() {
        $json = [];

        if (isset($this->request->get['search'])) {
            $this->load->model('extension/shipping/meest2');
            $json = $this->model_extension_shipping_meest2->searchCities($this->request->get['search']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStreetsByCity() {
        $json = [];

        if (isset($this->request->get['city_id'])) {
            $this->load->model('extension/shipping/meest2');

            $city_id = $this->request->get['city_id'];
            $json = $this->model_extension_shipping_meest2->getStreetsByCity($city_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getBranchesByCity() {
        $json = [];
        if (isset($this->request->get['city_id'])) {
            $this->load->model('extension/shipping/meest2');
            $city_id = $this->request->get['city_id'];
            $json = $this->model_extension_shipping_meest2->getBranchesByCity($city_id);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function orderForm() {
//        $this->load->language('extension/shipping/meest2');
        $data = $this->load->language('extension/shipping/meest2');

        $this->document->setTitle($this->language->get('heading_title_order_form'));

        $this->document->addStyle('view/stylesheet/meest/meest2_order_form.css');
        $this->document->addScript('view/javascript/meest/meest2_order_form.js');

        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js');

        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');

        $this->load->model('extension/shipping/meest2');

        $contractID = $this->config->get('meest2_sender_contract_id');
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
//                    "placeWeight" => $weight * $quantity,
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
                'building' => $postData['recipient_delivery_type'] === 'doors' ? 
                    (isset($postData['recipient_building_address']) ? $postData['recipient_building_address'] : '') : '',
                'region_code' => ''
            );
            $this->model_extension_shipping_meest2->updateOrderShippingData($order_info['order_id'], $shipping_data);



            $senderPerson = $this->model_extension_shipping_meest2->getContact($this->config->get('meest2_sender_contact_person'));
//            $senderPerson = $senderPerson[0];
            $senderAddressPickUp = 0;

            if($postData['sender_delivery_type'] === 'doors'){
                $senderData = array(
                    "name" => $senderPerson['lastname'] . ' ' . $senderPerson['firstname'] . ' ' .  $senderPerson['middlename'],
                    "phone" => $senderPerson['phone'],
                    "service" => "Door",
                    "addressID" => $postData['sender_address'],
                    "cityID"    => $postData['meest2_sender_city'],
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
//            date_default_timezone_set('Europe/Kiev');

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

                $status = isset($dataResponse['status']) ? $dataResponse['status'] : '';
                $message = isset($dataResponse['info']['message']) ? $dataResponse['info']['message'] : '';
                $fieldName = isset($dataResponse['info']['fieldName']) ? $dataResponse['info']['fieldName'] : '';
                $messageDetails = isset($dataResponse['info']['messageDetails']) ? $dataResponse['info']['messageDetails'] : '';
                $errorCode = isset($dataResponse['info']['errorCode']) ? ', errorCode: ' . $dataResponse['info']['errorCode'] : '';

                if (!isset($dataResponse['status']) || $dataResponse['status'] !== "OK") {
                    $this->session->data['error_warning'] = $status . '. ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;

                    $this->response->redirect($this->url->link(
                        'extension/shipping/meest2/orderForm',
                        'order_id=' . $order_info['order_id'] . '&token=' . $this->session->data['token'],
                        true
                    ));
                    return;
                }

                $this->session->data['success'] = $this->language->get('text_success_order_form');

                $parcelID = isset($dataResponse['result']['parcelID']) ? $dataResponse['result']['parcelID'] : '';
                $this->model_extension_shipping_meest2->setMeest2CnUuid($order_info['order_id'], $parcelID, $contractID, $senderAddressPickUp);
                $this->model_extension_shipping_meest2->saveMeestParcelData($order_info['order_id'], $dataResponse['result'], $contractID, $senderAddressPickUp);

                $this->response->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'], true));
            } catch (Exception $e) {
                $this->session->data['error_warning'] = $e->getMessage();

                $this->response->redirect($this->url->link(
                    'extension/shipping/meest2/orderForm',
                    'order_id=' . $order_info['order_id'] . '&token=' . $this->session->data['token'],
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
//        $data['order_id'] = $order_id;
        $order = $this->model_sale_order->getOrder($order_id);
//        $this->load->model('catalog/product');
//
//        $products = $this->model_sale_order->getOrderProducts($order_id);
//
//        $total_volume = 0;
//        $total_weight = 0;
//        foreach ($products as $product) {
//
//            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
//
//            if ($product_info) {
//                $length = isset($product_info['length']) ? $product_info['length'] : 0;
//                $width = isset($product_info['width']) ? $product_info['width'] : 0;
//                $height = isset($product_info['height']) ? $product_info['height'] : 0;
//
//                $volume = ($length * $width * $height) / 10000;
//
//                $total_volume += $volume * $product['quantity'];
//
//                $total_weight += $product_info['weight'] * $product['quantity'];
//            }
//        }
//
//        $data['total_weight'] = $total_weight;
//        $data['total_volume'] = $total_volume;
        $payer_types = array(
            array("Ref" => "Sender", "Description" => $this->language->get('text_sender')),
            array("Ref" => "Receiver", "Description" => $this->language->get('text_receiver'))
        );

        $data['references']['payer_types'] = $payer_types;

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

        // Если нет доступных типов оплаты, добавляем значение по умолчанию
        if (empty($payment_types)) {
            $payment_types[] = array(
                "Ref" => "cash",
                "Description" => $this->language->get('text_cash')
            );
        }

        $data['references']['payment_types'] = $payment_types;

        $order_products_details = array();

        $products = $this->model_sale_order->getOrderProducts($order_id);

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
//            $data['invoice_no'] = isset($order['invoice_no']) ? $order['invoice_no'] : '';
//            $data['invoice_prefix'] = isset($order['invoice_prefix']) ? $order['invoice_prefix'] : '';
//            $data['store_id'] = isset($order['store_id']) ? $order['store_id'] : '';
//            $data['store_name'] = isset($order['store_name']) ? $order['store_name'] : '';
//            $data['store_url'] = isset($order['store_url']) ? $order['store_url'] : '';
//            $data['customer_id'] = isset($order['customer_id']) ? $order['customer_id'] : '';
//            $data['customer'] = isset($order['customer']) ? $order['customer'] : '';
//            $data['customer_group_id'] = isset($order['customer_group_id']) ? $order['customer_group_id'] : '';
            $data['firstname'] = isset($order['firstname']) ? $order['firstname'] : '';
            $data['lastname'] = isset($order['lastname']) ? $order['lastname'] : '';
//            $data['email'] = isset($order['email']) ? $order['email'] : '';
            $data['recipient_contact_person_phone'] = isset($order['telephone']) ? $order['telephone'] : '';
//            $data['custom_field'] = isset($order['custom_field']) ? $order['custom_field'] : array();
//
//            $data['payment_firstname'] = isset($order['payment_firstname']) ? $order['payment_firstname'] : '';
//            $data['payment_lastname'] = isset($order['payment_lastname']) ? $order['payment_lastname'] : '';
//            $data['payment_company'] = isset($order['payment_company']) ? $order['payment_company'] : '';
//            $data['payment_address_1'] = isset($order['payment_address_1']) ? $order['payment_address_1'] : '';
//            $data['payment_address_2'] = isset($order['payment_address_2']) ? $order['payment_address_2'] : '';
//            $data['payment_postcode'] = isset($order['payment_postcode']) ? $order['payment_postcode'] : '';
//            $data['payment_city'] = isset($order['payment_city']) ? $order['payment_city'] : '';
//            $data['payment_zone_id'] = isset($order['payment_zone_id']) ? $order['payment_zone_id'] : '';
//            $data['payment_zone'] = isset($order['payment_zone']) ? $order['payment_zone'] : '';
//            $data['payment_zone_code'] = isset($order['payment_zone_code']) ? $order['payment_zone_code'] : '';
//            $data['payment_country_id'] = isset($order['payment_country_id']) ? $order['payment_country_id'] : '';
//            $data['payment_country'] = isset($order['payment_country']) ? $order['payment_country'] : '';
//            $data['payment_iso_code_2'] = isset($order['payment_iso_code_2']) ? $order['payment_iso_code_2'] : '';
//            $data['payment_iso_code_3'] = isset($order['payment_iso_code_3']) ? $order['payment_iso_code_3'] : '';
//            $data['payment_address_format'] = isset($order['payment_address_format']) ? $order['payment_address_format'] : '';
//            $data['payment_custom_field'] = isset($order['payment_custom_field']) ? $order['payment_custom_field'] : array();
//            $data['payment_method'] = isset($order['payment_method']) ? $order['payment_method'] : '';
//            $data['payment_code'] = isset($order['payment_code']) ? $order['payment_code'] : '';
//
            $data['shipping_firstname'] = isset($order['shipping_firstname']) ? $order['shipping_firstname'] : '';
            $data['shipping_lastname'] = isset($order['shipping_lastname']) ? $order['shipping_lastname'] : '';
//            $data['shipping_company'] = isset($order['shipping_company']) ? $order['shipping_company'] : '';
            $data['shipping_address_1'] = isset($order['shipping_address_1']) ? $order['shipping_address_1'] : '';
//            $data['shipping_address_2'] = isset($order['shipping_address_2']) ? $order['shipping_address_2'] : '';
//
//            $data['shipping_postcode'] = isset($order['shipping_postcode']) ? $order['shipping_postcode'] : '';
//            $data['shipping_city'] = isset($order['shipping_city']) ? $order['shipping_city'] : '';
//            $data['shipping_zone_id'] = isset($order['shipping_zone_id']) ? $order['shipping_zone_id'] : '';
            $data['shipping_zone'] = isset($order['shipping_zone']) ? $order['shipping_zone'] : '';
//            $data['shipping_zone_code'] = isset($order['shipping_zone_code']) ? $order['shipping_zone_code'] : '';
//            $data['shipping_country_id'] = isset($order['shipping_country_id']) ? $order['shipping_country_id'] : '';
            $data['shipping_country'] = isset($order['shipping_country']) ? $order['shipping_country'] : '';
//            $data['shipping_iso_code_2'] = isset($order['shipping_iso_code_2']) ? $order['shipping_iso_code_2'] : '';
//            $data['shipping_iso_code_3'] = isset($order['shipping_iso_code_3']) ? $order['shipping_iso_code_3'] : '';
//            $data['shipping_address_format'] = isset($order['shipping_address_format']) ? $order['shipping_address_format'] : '';
//            $data['shipping_custom_field'] = isset($order['shipping_custom_field']) ? $order['shipping_custom_field'] : array();
            $data['shipping_method'] = isset($order['shipping_method']) ? $order['shipping_method'] : '';
//            $data['shipping_code'] = isset($order['shipping_code']) ? $order['shipping_code'] : '';
//
//            $data['comment'] = isset($order['comment']) ? $order['comment'] : '';
//            $data['total'] = isset($order['total']) ? $order['total'] : '';
//            $data['reward'] = isset($order['reward']) ? $order['reward'] : '';
//            $data['order_status_id'] = isset($order['order_status_id']) ? $order['order_status_id'] : '';
//            $data['order_status'] = isset($order['order_status']) ? $order['order_status'] : '';
//            $data['affiliate_id'] = isset($order['affiliate_id']) ? $order['affiliate_id'] : '';
//            $data['affiliate_firstname'] = isset($order['affiliate_firstname']) ? $order['affiliate_firstname'] : '';
//            $data['affiliate_lastname'] = isset($order['affiliate_lastname']) ? $order['affiliate_lastname'] : '';
//            $data['commission'] = isset($order['commission']) ? $order['commission'] : '';
//            $data['language_id'] = isset($order['language_id']) ? $order['language_id'] : '';
//            $data['language_code'] = isset($order['language_code']) ? $order['language_code'] : '';
//            $data['currency_id'] = isset($order['currency_id']) ? $order['currency_id'] : '';
//            $data['currency_code'] = isset($order['currency_code']) ? $order['currency_code'] : '';
//            $data['currency_value'] = isset($order['currency_value']) ? $order['currency_value'] : '';
//            $data['ip'] = isset($order['ip']) ? $order['ip'] : '';
//            $data['forwarded_ip'] = isset($order['forwarded_ip']) ? $order['forwarded_ip'] : '';
//            $data['user_agent'] = isset($order['user_agent']) ? $order['user_agent'] : '';
//            $data['accept_language'] = isset($order['accept_language']) ? $order['accept_language'] : '';
//            $data['date_added'] = isset($order['date_added']) ? $order['date_added'] : '';
//            $data['date_modified'] = isset($order['date_modified']) ? $order['date_modified'] : '';
//            $data['shipping_firstname'] = isset($order['shipping_firstname']) ? $order['shipping_firstname'] : '';
            $data['recipient_contact_person'] = $data['shipping_lastname'] . ' ' . $data['shipping_firstname'];
        }

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
            if (isset($this->request->post['meest2'][$field])) {
                $data['meest2_' . $field] = $this->request->post['meest2'][$field];
            } else {
                $data['meest2_' . $field] = $this->config->get('meest2_' . $field);
            }
        }

        $data['regions'] = $this->model_extension_shipping_meest2->getRegions();
//        $data['cities'] = $this->model_extension_shipping_meest2->getCities();//[];
//        $data['addresses'] = $this->model_extension_shipping_meest2->getStreets();//[];

//        $data['contracts'] = $this->model_extension_shipping_meest2->getContracts();

        $data['contacts'] = $this->model_extension_shipping_meest2->getContacts();

        $senderPerson = $this->model_extension_shipping_meest2->getContact($this->config->get('meest2_sender_contact_person'));

        $nameParts = array_filter([
            isset($senderPerson['lastname']) ? $senderPerson['lastname'] : '',
            isset($senderPerson['firstname']) ? $senderPerson['firstname'] : '',
            isset($senderPerson['middlename']) ? $senderPerson['middlename'] : ''
        ]);

        $data['sender_person'] = implode(' ', $nameParts);

        $data['sender_phone']  = isset($senderPerson['phone']) ? $senderPerson['phone'] : '';

        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getCitiesByRegion', 'token=' . $this->session->data['token'], true));
        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getStreetsByCity', 'token=' . $this->session->data['token'], true));
        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getBranchesByCity', 'token=' . $this->session->data['token'], true));
        $data['ajax_search_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/searchCities', 'token=' . $this->session->data['token'], true));

        $this->load->model('setting/setting');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
            ],
            [
                'text' => $this->language->get('text_sale_order'),
                'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('heading_title_order_form'),
                'href' => $this->url->link('extension/shipping/meest2/orderForm', 'token=' . $this->session->data['token'], true)
            ]
        ];
        $data['action'] = $this->url->link('extension/shipping/meest2/orderForm', 'token=' . $this->session->data['token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'token=' . $this->session->data['token'], true);

        $data['meest2_recipient'] = $this->config->get('meest2_recipient');
        $data['meest2_recipient_contact_person'] = $this->config->get('meest2_recipient_contact_person');

        $data['order_shipping_data'] = $this->model_extension_shipping_meest2->getOrderShippingData($data['order_id']);

        // Якщо address_name порожня, але є address_code - спробуємо отримати назву
        if (empty($data['order_shipping_data']['address_name']) && !empty($data['order_shipping_data']['address_code'])) {
            // Спочатку перевіряємо в таблиці meest2_streets
            $street_query = $this->db->query("SELECT name_ua, type_ua FROM " . DB_PREFIX . "meest2_streets WHERE street_id = '" . $this->db->escape($data['order_shipping_data']['address_code']) . "'");
            if ($street_query->num_rows) {
                $data['order_shipping_data']['address_name'] = trim($street_query->row['type_ua'] . ' ' . $street_query->row['name_ua']);
            } else {
                // Якщо в БД немає - спробуємо отримати з API
                if (!empty($data['order_shipping_data']['city_code'])) {
                    require_once(DIR_SYSTEM . 'library/meest.php');
                    $meest = new Meest($this->registry);
                    
                    $result = $meest->geo_streets(['city_id' => $data['order_shipping_data']['city_code']]);
                    
                    if (!empty($result['result'])) {
                        foreach ($result['result'] as $street) {
                            if ($street['street_id'] === $data['order_shipping_data']['address_code']) {
                                $data['order_shipping_data']['address_name'] = trim($street['t_ua'] . ' ' . $street['ua']);
                                break;
                            }
                        }
                    }
                }
                
                if (empty($data['order_shipping_data']['address_name'])) {
                    $data['order_shipping_data']['address_name'] = $data['order_shipping_data']['address_code'];
                }
            }
        }
        
        $data['recipient_branch_name'] = '';
        if (!empty($data['order_shipping_data'])) {
            // Отримуємо назву міста з таблиці meest2_cities по UUID
            if (!empty($data['order_shipping_data']['city_code'])) {
                $city_query = $this->db->query("SELECT name_ua FROM " . DB_PREFIX . "meest2_cities WHERE city_id = '" . $this->db->escape($data['order_shipping_data']['city_code']) . "'");
                if ($city_query->num_rows) {
                    $data['order_shipping_data']['city_name'] = $city_query->row['name_ua'];
                }
            }

            // Отримуємо назву відділення
            if (!empty($data['order_shipping_data']['branch_code'])) {
                $branch_query = $this->db->query("SELECT short_name, address_more_information FROM " . DB_PREFIX . "meest2_branch WHERE branch_id = '" . $this->db->escape($data['order_shipping_data']['branch_code']) . "'");
                if ($branch_query->num_rows) {
                    $data['recipient_branch_name'] = $branch_query->row['short_name'] . ' (' . $branch_query->row['address_more_information'] . ')';
                    $data['order_shipping_data']['branch_name'] = $data['recipient_branch_name'];
                }
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/meest2_order_form', $data));
    }

    protected function validateOrderForm() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/meest2')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function auth()
    {
        $url = 'https://api.meest.com/v3.0/openAPI/auth';

        $authMode = $this->config->get('meest2_auth_mode');

        if ($authMode === "api_key") {
            return $this->config->get('meest2_api_key');
        } elseif ($authMode === "default") {
            if ($this->config->get('meest2_login') && $this->config->get('meest2_password')) {
                $data = [
                    'username' => $this->config->get('meest2_login'),
                    'password' => $this->config->get('meest2_password')
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
            $response = $this->meestApiV3($url, $data, 'manual-token-here-if-needed', 'POST'); // токен не нужен, просто передаём null
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

    public function orderUpdateForm() {
        $data = $this->load->language('extension/shipping/meest2');

        $this->document->setTitle($this->language->get('heading_title_order_update_form'));

        $this->document->addStyle('view/stylesheet/meest/meest2_update_shipment_form.css');
        $this->document->addScript('view/javascript/meest/meest2_update_shipment_form.js');

        $this->document->addStyle('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js');

        $this->document->addStyle('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->document->addScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');

        $this->load->model('extension/shipping/meest2');

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

//            date_default_timezone_set('Europe/Kiev');
            $contractID = $this->model_extension_shipping_meest2->getContractIdByUuid($postData['meest2_cn_uuid']);
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
                $url = "https://api.meest.com/v3.0/openAPI/parcel/" . $postData['meest2_cn_uuid'];
                $response = $this->meestApiV3($url, $postInfo, null, 'PUT');

                $dataResponse = json_decode($response, true);

                $status = isset($dataResponse['status']) ? $dataResponse['status'] : '';
                $message = isset($dataResponse['info']['message']) ? $dataResponse['info']['message'] : '';
                $fieldName = isset($dataResponse['info']['fieldName']) ? $dataResponse['info']['fieldName'] : '';
                $messageDetails = isset($dataResponse['info']['messageDetails']) ? $dataResponse['info']['messageDetails'] : '';
                $errorCode = isset($dataResponse['info']['errorCode']) ? ', errorCode: ' . $dataResponse['info']['errorCode'] : '';

                if (!isset($dataResponse['status']) || $dataResponse['status'] !== "OK") {
                    $this->session->data['get_info_for_edit_error'] = $status . '. ' . $message . ' ' . $fieldName . ', ' . $messageDetails . ' ' . $errorCode;

                    $this->response->redirect($this->url->link(
                        'extension/shipping/meest2/orderUpdateForm',
                        'parcel_id=' . $postData['meest2_cn_uuid'] . '&token=' . $this->session->data['token'],
                        true
                    ));
                    return;
                }

                $this->session->data['success'] = $this->language->get('text_success_update_order_form');
                $this->model_extension_shipping_meest2->setMeest2CnSenderAddressPickUp($senderAddressPickUp, $postData['meest2_cn_uuid']);

                $this->response->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'], true));
            } catch (Exception $e) {
                $this->session->data['get_info_for_edit_error'] = $e->getMessage();

                $this->response->redirect($this->url->link(
                    'extension/shipping/meest2/orderUpdateForm',
                    'parcel_id=' . $postData['meest2_cn_uuid'] . '&token=' . $this->session->data['token'],
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

                $this->response->setOutput($this->load->view('extension/shipping/meest2_update_shipment_form', $data));

                return;
            }
        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();

            $data['error_meesage'] = $e->getMessage();
            $this->response->setOutput($this->load->view('extension/shipping/meest2_update_shipment_form', $data));

        }
        if (isset($this->session->data['get_info_for_edit_error'])) {
            $data['get_info_for_edit_error'] = $this->session->data['get_info_for_edit_error'];
            unset($this->session->data['get_info_for_edit_error']);
        } else {
            $data['get_info_for_edit_error'] = '';
        }
        $data['sender_region_id_address'] = 0;
        $data['receiver_region_id_address'] = 0;
        if($data['parcel_info']['sender']['service'] === 'Door'){
            $address = $this->model_extension_shipping_meest2->getStreet($data['parcel_info']['sender']['addressID']);
            $city = $this->model_extension_shipping_meest2->getCity($address['city_id']);
            $data['sender_city_id_address'] = $address['city_id'];
            $data['sender_region_id_address'] = isset($city['region_id']) ? $city['region_id'] : 0;
        }

        if($data['parcel_info']['receiver']['service'] === 'Door'){
            $address = $this->model_extension_shipping_meest2->getStreet($data['parcel_info']['receiver']['addressID']);
            $city = $this->model_extension_shipping_meest2->getCity($address['city_id']);
            $data['receiver_city_id_address'] = $address['city_id'];
            $data['receiver_region_id_address'] = isset($city['region_id']) ? $city['region_id'] : 0;
        }
        $data['branch_sender_info'] = $this->model_extension_shipping_meest2->getBranchById($data['parcel_info']['sender']['branchID']);
        $data['branch_recipient_info'] = $this->model_extension_shipping_meest2->getBranchById($data['parcel_info']['receiver']['branchID']);

        $this->load->model('sale/order');
        $order_id = $this->request->get['parcel_id'];

        $payer_types = array(
            array("Ref" => "Sender", "Description" => $this->language->get('text_sender')),
            array("Ref" => "Receiver", "Description" => $this->language->get('text_receiver'))
        );
        $data['references']['payer_types'] = $payer_types;

        $contractID = $this->model_extension_shipping_meest2->getContractIdByUuid($this->request->get['parcel_id']);
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

        $data['regions'] = $this->model_extension_shipping_meest2->getRegions();
//        $data['cities'] = $this->model_extension_shipping_meest2->getCities();//[];

        $data['ajax_get_cities_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getCitiesByRegion', 'token=' . $this->session->data['token'], true));

        $data['ajax_get_addresses_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getStreetsByCity', 'token=' . $this->session->data['token'], true));

        $data['ajax_get_branches_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getBranchesByCity', 'token=' . $this->session->data['token'], true));

        $this->load->model('setting/setting');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
            ],
            [
                'text' => $this->language->get('text_sale_order'),
                'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&type=shipping', true)
            ],
            [
                'text' => $this->language->get('heading_title_order_update_form'),
                'href' => $this->url->link('extension/shipping/meest2/orderUpdateForm', 'token=' . $this->session->data['token'], true)
            ]
        ];

        $data['action'] = $this->url->link('extension/shipping/meest2/orderUpdateForm', 'token=' . $this->session->data['token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'token=' . $this->session->data['token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/meest2_update_shipment_form', $data));
    }

    public function getParcelInfoFromAPI($parcelId)
    {
        $apiUrl = "https://api.meest.com/v3.0/openAPI/getParcel/" . $parcelId . "/parcelID/objectData";

        try {
            $response = $this->meestApiV3($apiUrl, array(), null, 'GET');
        } catch (Exception $e) {
            throw new Exception("cURL error occurred: " . $e->getMessage());
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }

        if (!isset($data['result'])) {
            throw new Exception("Unexpected response structure: missing 'result' key.");
        }

        return $data;
    }


    public function getParcelInfo() {

        $data = $this->load->language('extension/shipping/meest2');

        $this->document->setTitle($this->language->get('text_parcel_info'));

        $this->document->addStyle('view/stylesheet/meest/meest2_get_parcel_info.css');

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
        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
           return $e->getMessage();
        }

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
            ],
            [
                'text' => $this->language->get('text_shipping'),
                'href' => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&type=shipping', true)
            ]
        ];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/meest2_parcel_info', $data));
    }

    public function parcelList() {
        $data = $this->load->language('extension/shipping/meest2');

        $this->document->setTitle($this->language->get('text_meest_orders_list'));

        $this->document->addStyle('view/stylesheet/meest/meest2_parcel_list.css');

        $this->load->model('extension/shipping/meest2');

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $sort_by = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'order_id';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';

        $data['orders'] = $this->model_extension_shipping_meest2->getOrders($page, $sort_by, $order);

        $data['order_ids'] = array_map(function($order) {
            return array(
                'order_id'          => $order['order_id'],
                'meest2_contractID' => $order['meest2_contractID'],
                'meest2_registerID' => $order['meest2_registerID']
            );
        }, $data['orders']);

        $total_orders = $this->model_extension_shipping_meest2->getTotalOrders();
        $pagination = new Pagination();
        $pagination->total = $total_orders;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('extension/shipping/meest2/parcelList', 'token=' . $this->session->data['token'] . '&page={page}&sort=' . $sort_by . '&order=' . $order);
        $url_link = $this->url->link('extension/shipping/meest2/parcelList', 'token=' . $this->session->data['token'], true);
        $data['url_link'] = $url_link;

        $data['pagination'] = $pagination->render();
        $data['sort'] = $sort_by;
        $data['order'] = $order;

        $data['create_register_pickup_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/createRegisterPickup', 'token=' . $this->session->data['token'], true));
        $data['get_order_date_info'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getOrderDateInfo', 'token=' . $this->session->data['token'], true));
        $data['available_time_slots'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getAvailableTimeSlots', 'token=' . $this->session->data['token'], true));
        $data['unregisterPickup_url'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/unregisterPickup', 'token=' . $this->session->data['token'], true));
        $data['get_parcel_uuids'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getParcelUUIDs', 'token=' . $this->session->data['token'], true));
        $data['get_register_ids'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getRegisterIDs', 'token=' . $this->session->data['token'], true));
        $data['get_parcel_info'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/getParcelInfo', 'token=' . $this->session->data['token'], true));
        $data['order_update_form'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/orderUpdateForm', 'token=' . $this->session->data['token'], true));
        $data['order_form'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/orderForm', 'token=' . $this->session->data['token'], true));
        $data['update_order_statuses'] = str_replace('&amp;', '&', $this->url->link('extension/shipping/meest2/updateOrderStatuses', 'token=' . $this->session->data['token'], true));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/meest2_parcel_list', $data));
    }


    public function createRegisterPickup() {

        $postData = $this->request->post;

        $this->load->model('extension/shipping/meest2');
        $orderData = $this->model_extension_shipping_meest2->getOrderById($postData['order_id']);
        $parcelId = $orderData['meest2_cn_uuid'];

        try {
            $parcelInfo = $this->getParcelInfoFromAPI($parcelId);
        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            return $e->getMessage();
        }


        $dateObject = new DateTime($postData['date']);
        $formattedDate = $dateObject->format('d.m.Y');
        $registerData = [
            'notation' => '',
            'contractID' => $orderData['meest2_contractID'],
            'payType' => $parcelInfo['result']['payType'],
            'receiverPay' => $parcelInfo['result']['receiverPay'],
            'expectedPickUpDate' => [
                'date' => $formattedDate,
                'timeFrom' => $postData['time_from'],
                'timeTo' => $postData['time_to'],
            ],
            'sender' => [
                'name' => $parcelInfo['result']['sender']['name'],
                'phone' => $parcelInfo['result']['sender']['phone'],
                'addressID' => $parcelInfo['result']['sender']['addressID'],
                'building' => $parcelInfo['result']['sender']['building'],
                'floor' => $parcelInfo['result']['sender']['floor'],
                'flat' => $parcelInfo['result']['sender']['flat'],
            ],
            'parcelsItems' => [
                [
                    'parcelID' => $parcelId
                ]
            ]
        ];

        try {
            $response = $this->meestApiV3("https://api.meest.com/v3.0/openAPI/registerPickup", $registerData, null, 'POST');

            $responseData = json_decode($response, true);

            if (isset($responseData['result']['registerID'])) {
                $this->model_extension_shipping_meest2->setMeest2RegisterID($postData['order_id'], $responseData['result']['registerID']);
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($response));
        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(array('error' => $e->getMessage())));
        }
    }

    public function getAvailableTimeSlots() {
        try {
            $orderId = $this->request->get['order_id'];

            $this->load->model('extension/shipping/meest2');
            $orderData = $this->model_extension_shipping_meest2->getOrderById($orderId);

            if (!$orderData || empty($orderData['meest2_cn_uuid'])) {
                throw new Exception("Order or parcel ID not found.");
            }

            $parcelId = $orderData['meest2_cn_uuid'];

            try {
                $parcelInfo = $this->getParcelInfoFromAPI($parcelId);
            } catch (Exception $e) {
                $this->session->data['error'] = $e->getMessage();
                return $e->getMessage();
            }

            if (empty($parcelInfo['result']['sender']['addressID'])) {
                throw new Exception("Sender address information missing in parcel data.");
            }

            $senderAddressID = $parcelInfo['result']['sender']['addressID'];

            $url = "https://api.meest.com/v3.0/openAPI/availableTimeSlots/?streetID=$senderAddressID&type=pickup";

            $response = $this->meestApiV3($url, array(), null, 'GET');

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON decode error: " . json_last_error_msg());
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput($response);

        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function unregisterPickup() {
        $registerId = $this->request->post['register_id'];
        $orderId = $this->request->post['order_id'];

        if (!$registerId) {
            $json = [
                'success' => false,
                'error' => 'Call ID missing.'
            ];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));

            return;
        }

        try {
            $response = $this->meestApiV3("https://api.meest.com/v3.0/openAPI/registerPickup/" . $registerId, array(), null, 'DELETE');
            $responseData = json_decode($response, true);

            if (isset($responseData['status']) && $responseData['status'] === 'OK') {
                $json = array(
                    'success' => true,
                    'message' => 'The call was successfully removed.'
                );

                $this->load->model('extension/shipping/meest2');
                $this->model_extension_shipping_meest2->unsetMeest2RegisterID($orderId);
            } else {
                $json = array(
                    'success' => false,
                    'error' => isset($responseData['errorMessage']) ? $responseData['errorMessage'] : 'Failed to delete call.'
                );
            }

        } catch (Exception $e) {
            $json = array(
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getParcelUUIDs() {
        $this->load->model('sale/order');

        $order_ids = $this->request->post['orders'];

        $uuids = [];
        $this->load->model('extension/shipping/meest2');

        foreach ($order_ids as $order_id) {

            $order_info = $this->model_extension_shipping_meest2->getOrderById($order_id);
            if ($order_info && isset($order_info['meest2_cn_uuid'])) {
                $uuids[] = $order_info['meest2_cn_uuid'];
            }
        }

        if (!empty($uuids)) {
            $this->response->setOutput(json_encode(['success' => true, 'uuids' => $uuids]));
        } else {
            $this->response->setOutput(json_encode(['success' => false, 'message' => 'Parcel UUID not found.']));
        }
    }

    public function getRegisterIDs() {
        $this->load->model('sale/order');

        $order_ids = $this->request->post['orders'];
        $register_ids = [];
        $this->load->model('extension/shipping/meest2');

        foreach ($order_ids as $order_id) {
            $order_info = $this->model_extension_shipping_meest2->getOrderById($order_id);
            if ($order_info && isset($order_info['meest2_registerID'])) {
                $register_ids[] = $order_info['meest2_registerID'];
            }
        }

        if (!empty($register_ids)) {
            $this->response->setOutput(json_encode(['success' => true, 'register_ids' => $register_ids]));
        } else {
            $this->response->setOutput(json_encode(['success' => false, 'message' => 'RegisterID not found.']));
        }
    }

    /**
     * Retrieves and updates order statuses based on tracking information from an external API.
     *
     * This method performs the following steps:
     * 1. Retrieves the 'order_ids' parameter from the GET request.
     * 2. If no 'order_ids' are provided, returns a JSON response with success set to false.
     * 3. Loads the orders from the model using the provided order IDs.
     * 4. If no orders are found, returns a JSON response where each order ID is mapped to a default message
     *    indicating that the Meest shipment has not been created yet.
     * 5. Extracts the barcodes from the orders and builds a comma-separated string.
     * 6. Calls an external tracking API using the barcode string to obtain tracking data.
     * 7. Processes the tracking data by mapping each result's event code to a predefined status message.
     *    The event code mapping is as follows:
     *      - 101                => "Створено"              (Created)
     *      - 606, 808           => "Надіслано"             (Shipped)
     *      - 1213, 1214, 1215, 1216, 1217 => "Доставлено"    (Delivered)
     *      - 1315               => "Видано кур'єру"        (Issued to courier)
     *      - 1620               => "Відмова"               (Refused)
     *      - 1621               => "Переадресація"         (Readdressed)
     *      - 1622               => "Отримано"              (Received)
     *      - 1623, 1825         => "Повернення"            (Returned)
     *      - 2200, 2215, 2225   => "Митне оформлення"      (Customs clearance)
     *    If an event code is not found in the mapping, the status from the API response ('eventDescr.descrUA') is used.
     * 8. For each order, the method assigns the updated status based on the corresponding barcode; if no matching
     *    status is found, it defaults to "Створено" (Created).
     * 9. Finally, it returns a JSON response with success set to true and a 'data' array that maps order IDs to their updated statuses.
     *
     * Note: Some status messages remain in Ukrainian. You may want to translate them to English if needed.
     *
     * @return void Outputs a JSON response containing the updated statuses.
     */
    public function updateOrderStatuses() {
        $orderIdsParam = isset($this->request->get['order_ids']) ? $this->request->get['order_ids'] : '';

        if (!$orderIdsParam) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => false]));
            return;
        }
        $this->load->model('extension/shipping/meest2');
        $orders = $this->model_extension_shipping_meest2->getOrdersByIds($orderIdsParam);
        if (empty($orders)) {
            $updatedStatusesDefault = [];
            foreach ($orderIdsParam as $value) {
                $updatedStatusesDefault[$value] = "Status is empty";
            }

            $output = [
                'success' => true,
                'data'    => $updatedStatusesDefault
            ];

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($output, JSON_UNESCAPED_UNICODE));

            return;
        }

        $eventCodeMapping = [
            101  => 'Створено',
            606  => 'Надіслано',
            808  => 'Надіслано',
            1213 => 'Доставлено',
            1214 => 'Доставлено',
            1215 => 'Доставлено',
            1216 => 'Доставлено',
            1217 => 'Доставлено',
            1315 => 'Видано кур\'єру',
            1620 => 'Відмова',
            1621 => 'Переадресація',
            1622 => 'Отримано',
            1623 => 'Повернення',
            1825 => 'Повернення',
            2200 => 'Митне оформлення',
            2215 => 'Митне оформлення',
            2225 => 'Митне оформлення'
        ];

        $parcelBarcode = array_column($orders, 'barcode');
        $trackingString = implode(',', $parcelBarcode);

        $trackingData = $this->getTrackingData($trackingString);

        $parcelToStatus = [];
        if (isset($trackingData['status']) && strtoupper($trackingData['status']) == "OK" && !empty($trackingData['result'])) {
            foreach ($trackingData['result'] as $resultItem) {
                if (isset($resultItem['parcelNumber']) && isset($resultItem['eventDescr']['descrUA'])) {
                    $eventCode = (int)$resultItem['eventCode'];
                    if (isset($eventCodeMapping[$eventCode])) {
                        $parcelToStatus[$resultItem['parcelNumber']] = $eventCodeMapping[$eventCode];
                    } else {
                        $parcelToStatus[$resultItem['parcelNumber']] = $resultItem['eventDescr']['descrUA'];
                    }
                }
            }
        }

        $updatedStatuses = [];
        foreach ($orders as $order) {
            $parcelNumber = isset($order['barcode']) ? $order['barcode'] : null;
            $updatedStatus = ($parcelNumber && isset($parcelToStatus[$parcelNumber])) ? $parcelToStatus[$parcelNumber] : 'Створено';
            $updatedStatuses[$order['order_id']] = $updatedStatus;
        }

        foreach ($orderIdsParam as $keyValue) {
            if (!isset($updatedStatuses[$keyValue])) {
                $updatedStatuses[$keyValue] = "Status is empty";
            }
        }

        $output = [
            'success' => true,
            'data'    => $updatedStatuses
        ];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($output, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Example function for requesting data from the TrackingAll API.
     */

    /**
     * Retrieves tracking information from the API for one or more tracking numbers.
     *
     * @param string $trackNumbers A string containing one or more tracking numbers, separated by commas.
     * @return array The API response as an associative array.
     */

    private function getTrackingData($trackNumbers)
    {
        $apiUrl = "https://api.meest.com/v3.0/openAPI/trackingAll/" . $trackNumbers;

        try {
            $response = $this->meestApiV3($apiUrl, array(), null, 'GET');
            return json_decode($response, true);
        } catch (Exception $e) {
            return [
                "status" => "error",
                "info" => [
                    "message" => $e->getMessage(),
                    "messageDetails" => ""
                ]
            ];
        }
    }

    private function getPaymentApiMeestData($meest2OrderContractID)
    {
        $url = "https://api.meest.com/v3.0/openAPI/contractClientInfo/$meest2OrderContractID";

        try {
            $response = $this->meestApiV3($url, array(), null, 'GET');

            return json_decode($response, true);
        } catch (Exception $e) {
            return false;
        }
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
            throw new Exception('cURL Error: ' . $error);
        }

        curl_close($ch);

        return $response;
    }

    protected function getValidMeestToken()
    {
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
}

