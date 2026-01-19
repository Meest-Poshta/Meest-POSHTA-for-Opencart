<?php
class ModelExtensionShippingMeest2 extends Model {
    const PLUGIN_VERSION = '1.5.0';

    public function install($typeInstall = true) {
        $this->load->model('setting/setting');

        $current_version = $this->config->get('shipping_meest2_version');
        if ($current_version === null || $typeInstall) {
            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX ."meest2_branch`");
            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest2_branch` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `branch_id` VARCHAR(40) NOT NULL,                   -- branchID
                  `branch_no` INT(6) DEFAULT NULL,                    -- branchNo
                  `branch_number` VARCHAR(20) DEFAULT NULL,           -- branchNumber
                  `branch_type` VARCHAR(50) DEFAULT NULL,             -- branchType (например, ОВ, ППВ и т.п.)
                  `is_branch_open` TINYINT(1) DEFAULT NULL,           -- isBranchOpen (1/0)
                  `is_branch_closed` TINYINT(1) DEFAULT NULL,         -- isBranchClosed
                  `branch_type_id` VARCHAR(40) DEFAULT NULL,          -- branchTypeID
                  `branch_type_descr` VARCHAR(256) DEFAULT NULL,      -- branchTypeDescr
                  `branch_type_id_client` VARCHAR(40) DEFAULT NULL,   -- branchTypeIDClient
                  `client_type_subdivision` VARCHAR(256) DEFAULT NULL,-- ClientTypeSubdivision
                  `client_type_subdivision_id` VARCHAR(40) DEFAULT NULL,-- ClientTypeSubdivisionID
                  `short_name` VARCHAR(256) DEFAULT NULL,             -- ShortName
                  `full_name` VARCHAR(512) DEFAULT NULL,              -- FullName
                  `branch_descr_ua` VARCHAR(256) DEFAULT NULL,        -- branchDescr.descrUA
                  `branch_descr_loc` VARCHAR(256) DEFAULT NULL,       -- branchDescr.descrLoc
                  `branch_descr_search_ua` VARCHAR(256) DEFAULT NULL, -- branchDescr.descrSearchUA
                  `branch_descr_search_loc` VARCHAR(256) DEFAULT NULL,-- branchDescr.descrSearchLoc
                
                  `address_id` VARCHAR(40) DEFAULT NULL,              -- addressID
                  `address_descr_ua` VARCHAR(256) DEFAULT NULL,       -- addressDescr.descrUA
                  `address_descr_ru` VARCHAR(256) DEFAULT NULL,       -- addressDescr.descrRU
                  `address_descr_en` VARCHAR(256) DEFAULT NULL,       -- addressDescr.descrEN
                  `address_descr_loc` VARCHAR(256) DEFAULT NULL,      -- addressDescr.descrLoc
                  `address_more_information` VARCHAR(256) DEFAULT NULL, -- addressMoreInformation
                  
                  `city_id` VARCHAR(40) DEFAULT NULL,                 -- cityID
                  `city_ua` VARCHAR(256) DEFAULT NULL,                -- cityDescr.descrUA
                  `city_ru` VARCHAR(256) DEFAULT NULL,                -- cityDescr.descrRU
                  `city_en` VARCHAR(256) DEFAULT NULL,                -- cityDescr.descrEN
                  `city_loc` VARCHAR(256) DEFAULT NULL,               -- cityDescr.descrLoc
                  
                  `district_id` VARCHAR(40) DEFAULT NULL,             -- districtID
                  `district_ua` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrUA
                  `district_ru` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrRU
                  `district_en` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrEN
                  `district_loc` VARCHAR(256) DEFAULT NULL,           -- districtDescr.descrLoc
                  
                  `region_id` VARCHAR(40) DEFAULT NULL,               -- regionID
                  `region_ua` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrUA
                  `region_ru` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrRU
                  `region_en` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrEN
                  `region_loc` VARCHAR(256) DEFAULT NULL,             -- regionDescr.descrLoc
                  
                  `working_hours` VARCHAR(256) DEFAULT NULL,          -- workingHours
                  `street_number` VARCHAR(10) DEFAULT NULL,           -- building
                  `zip` VARCHAR(10) DEFAULT NULL,                     -- zipCode
                  `latitude` DECIMAL(10,6) DEFAULT NULL,              -- latitude
                  `longitude` DECIMAL(10,6) DEFAULT NULL,             -- longitude
                  
                  `branch_work_time` TEXT DEFAULT NULL,              -- JSON encoded branchWorkTime array
                  `phone` VARCHAR(50) DEFAULT NULL,                   -- phone
                  `address` VARCHAR(256) DEFAULT NULL,                -- address
                  `payment_types` VARCHAR(256) DEFAULT NULL,          -- paymentTypes
                  `branch_limits` TEXT DEFAULT NULL,                  -- JSON encoded branchLimits
                  `localization` VARCHAR(10) DEFAULT NULL,            -- Localization
                  `payment_methods` TEXT DEFAULT NULL,                -- JSON encoded paymentMethods array
                  `customer_identification` TEXT DEFAULT NULL,        -- JSON encoded customerIdentification array
                  `partner_services` TEXT DEFAULT NULL,               -- JSON encoded PartnerServices array
                  `services` TEXT DEFAULT NULL,                       -- JSON encoded Services array
                  
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `branch_id_unique` (`branch_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest2_regions`");
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest2_regions` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `region_id` VARCHAR(36) NOT NULL,
                    `region_name_ua` VARCHAR(100) NOT NULL,
                    `region_name_en` VARCHAR(100) NOT NULL,
                    `country_id` VARCHAR(36) NOT NULL,
                    `zone_id` INT(11) DEFAULT NULL,
                    `creatеd_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8');

            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX ."meest2_cities`");
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest2_cities` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `city_id` VARCHAR(36) NOT NULL,
                    `name_ua` VARCHAR(100) NOT NULL,
                    `name_ru` VARCHAR(100) NOT NULL,
                    `type_ua` VARCHAR(50),
                    `district_id` VARCHAR(36),
                    `region_id` VARCHAR(36),
                    `koatuu` VARCHAR(20),
                    `delivery_in_city` TINYINT(1),
                    `creatеd_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (`city_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8');

            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX ."meest2_streets`");
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest2_streets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `street_id` VARCHAR(36) NOT NULL,
                    `type_ua` VARCHAR(50),
                    `type_ru` VARCHAR(50),
                    `name_ua` VARCHAR(100),
                    `name_ru` VARCHAR(100),
                    `city_id` VARCHAR(36),
                    `region_id` VARCHAR(36),
                    `district_ua` VARCHAR(100),
                    `district_ru` VARCHAR(100),
                    `region_ua` VARCHAR(100),
                    `region_ru` VARCHAR(100),
                    `postal_code` VARCHAR(10),
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (`street_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8');


            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest2_contracts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `contractID` CHAR(36) NOT NULL,
                    `creatеd_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest2_contacts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `phone` VARCHAR(20) NOT NULL,
                    `firstname` VARCHAR(50) NOT NULL,
                    `lastname` VARCHAR(50) NOT NULL,
                    `middlename` VARCHAR(50) DEFAULT NULL,
                    `creatеd_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


            $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'meest2_cn_uuid';");
            if (!$column->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `meest2_cn_uuid` VARCHAR(100) AFTER `order_id`;");
            }

            $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'meest2_contractID';");
            if (!$column->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `meest2_contractID` VARCHAR(100) AFTER `meest2_cn_uuid`;");
            }

            $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'meest2_registerID';");
            if (!$column->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `meest2_registerID` VARCHAR(100) AFTER `meest2_contractID`;");
            }

            $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'meest2_sender_address_pick_up';");
            if (!$column->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `meest2_sender_address_pick_up` TINYINT(1) NOT NULL DEFAULT 0 AFTER `order_id`;");
            }

            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest2_order_shipping_data` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `order_id` INT(11) NOT NULL,
                  `shipping_method` VARCHAR(100) NOT NULL,
                  `city_code` VARCHAR(40) DEFAULT NULL,
                  `branch_code` VARCHAR(40) DEFAULT NULL,
                  `address_code` VARCHAR(40) DEFAULT NULL,
                  `building` VARCHAR(100) DEFAULT NULL,
                  `region_code` VARCHAR(40) DEFAULT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `order_id_unique` (`order_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

            $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "meest2_order_shipping_data` LIKE 'building';");
            if (!$column->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "meest2_order_shipping_data` ADD `building` VARCHAR(100) DEFAULT NULL AFTER `address_code`;");
            }

            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest2_parcels` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `order_id` INT(11) NOT NULL,
                  `uuid` VARCHAR(100) NOT NULL,
                  `contractID` VARCHAR(100) NOT NULL,
                  `parcel_number` VARCHAR(50) NOT NULL,
                  `barcode` VARCHAR(50) NOT NULL,
                  `sender_address_pick_up` TINYINT(1) NOT NULL DEFAULT 0,
                  `registerID` VARCHAR(100),
                  `recipient_city` VARCHAR(100) DEFAULT NULL,
                  `recipient_branch` VARCHAR(100) DEFAULT NULL,
                  `recipient_street` VARCHAR(255) DEFAULT NULL,
                  `recipient_building` VARCHAR(50) DEFAULT NULL,
                  `recipient_floor` VARCHAR(10) DEFAULT NULL,
                  `recipient_apartment` VARCHAR(50) DEFAULT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `order_id` (`order_id`),
                  KEY `uuid` (`uuid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");
        }

        if ($current_version !== self::PLUGIN_VERSION) {
            $this->migrate($current_version, $typeInstall);

            $this->model_setting_setting->editSetting('shipping_meest2_version', [
                'shipping_meest2_version' => self::PLUGIN_VERSION
            ]);
        }
    }

    public function migrate($from_version = null, $typeInstall) {
        // if (version_compare((string)$from_version, self::PLUGIN_VERSION, '<')) {
        // }
        // if (version_compare((string)$from_version, '1.3.0', '<')) { ... }
    }


	public function truncateTable() {
		$this->db->query("TRUNCATE `" . DB_PREFIX . "meest2_branch`");
	}

//	public function addBranch($data) {
//		if ($data) {
//			$query = "INSERT INTO `" . DB_PREFIX . "meest2_branch` (
//				br_id 	     ,
//				num 	     ,
//				type_id 	 ,
//				type_public_ua,
//				type_public_ru,
//				type_public_en,
//				region_id 	 ,
//				region_ua 	 ,
//				region_ru 	 ,
//				region_en 	 ,
//				district_id  ,
//				district_ua  ,
//				district_ru  ,
//				district_en  ,
//				city_id 	 ,
//				city_ua      ,
//				city_ru      ,
//				city_en      ,
//				street_id    ,
//				street_ua    ,
//				street_ru    ,
//				street_en    ,
//				street_number,
//				zip 	     ,
//				lng          ,
//				lat
//			)  VALUES " . implode(',', $data);
//
//			$this->db->query($query);
//		}
//	}

	public function prepare($sql) {
        array_walk($sql, array($this->db,'escape'));
        return "('" . implode("','",$sql) . "')";
	}

//	public function getBranches($data = array()) {
//
//        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_branch");
//
//        return $query->rows;
//	}

//	public function updateBranch($br_id,$data) {
//		$query = "UPDATE `" . DB_PREFIX ."meest2_branch` SET ";
//
//		foreach ($data as $field => $val) {
//			$query .= "`" . $field . "` = '" . $this->db->escape(str_replace("'","\'",$val)) . "',";
//		}
//
//		$query = trim($query,',') . " WHERE br_id = '" .$this->db->escape($br_id) . "'";
//		$result = $this->db->query($query);
//	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest2_branch`;");
    }

    public function getRegion($region_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_regions WHERE region_id = '" . $this->db->escape($region_id) . "'");

        return $query->row;
    }

    public function getRegions() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_regions ORDER BY region_name_ua");

        return $query->rows;
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

    public function getCity($city_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_cities WHERE city_id = '" . $this->db->escape($city_id) . "'");

        return $query->row;
    }

//    public function getCities() {
//        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_cities ORDER BY name_ua");
//
//        return $query->rows;
//    }

    public function addCity($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_cities SET 
            city_id = '" . $this->db->escape($data['city_id']) . "',
            name_ua = '" . $this->db->escape($data['name_ua']) . "',
            name_ru = '" . $this->db->escape($data['name_ru']) . "',
            type_ua = '" . $this->db->escape($data['type_ua']) . "',
            district_id = '" . $this->db->escape($data['district_id']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            koatuu = '" . $this->db->escape($data['koatuu']) . "',
            delivery_in_city = '" . (int)$data['delivery_in_city'] . "'
        ");
    }

    public function editCity($city_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "meest2_cities SET 
            name_ua = '" . $this->db->escape($data['name_ua']) . "',
            name_ru = '" . $this->db->escape($data['name_ru']) . "',
            type_ua = '" . $this->db->escape($data['type_ua']) . "',
            district_id = '" . $this->db->escape($data['district_id']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            koatuu = '" . $this->db->escape($data['koatuu']) . "',
            delivery_in_city = '" . (int)$data['delivery_in_city'] . "'
        WHERE city_id = '" . $this->db->escape($city_id) . "'
        ");
    }


    public function getStreet($street_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_streets WHERE street_id = '" . $this->db->escape($street_id) . "'");
        return $query->row;
    }

//    public function getStreets() {
//        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_streets ORDER BY name_ua");
//        return $query->rows;
//    }

    public function addStreet($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_streets SET 
            street_id = '" . $this->db->escape($data['street_id']) . "',
            type_ua = '" . $this->db->escape($data['type_ua']) . "',
            type_ru = '" . $this->db->escape($data['type_ru']) . "',
            name_ua = '" . $this->db->escape($data['name_ua']) . "',
            name_ru = '" . $this->db->escape($data['name_ru']) . "',
            city_id = '" . $this->db->escape($data['city_id']) . "'
            ");
    }

    public function editStreet($street_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "meest2_streets SET 
            type_ua = '" . $this->db->escape($data['type_ua']) . "',
            type_ru = '" . $this->db->escape($data['type_ru']) . "',
            name_ua = '" . $this->db->escape($data['name_ua']) . "',
            name_ru = '" . $this->db->escape($data['name_ru']) . "',
            city_id = '" . $this->db->escape($data['city_id']) . "' 
            WHERE street_id = '" . $this->db->escape($street_id) . "'
            ");
    }


    public function getContracts() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_contracts");
        return $query->rows;
    }

    public function addContract($contract_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_contracts SET contractID = '" . $this->db->escape($contract_id) . "'");
    }

    public function deleteContract($contract_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "meest2_contracts WHERE contractID = '" . $this->db->escape($contract_id) . "'");
    }

    public function addContact($phone, $firstname, $lastname, $middlename) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_contacts
           SET phone = '" . $this->db->escape($phone) . "',
           firstname = '" . $this->db->escape($firstname) . "',
           lastname = '" . $this->db->escape($lastname) . "',
           middlename = '" . $this->db->escape($middlename) . "'
        ");
    }

    public function deleteContact($contact_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "meest2_contacts WHERE id = '" . (int)$contact_id . "'");
    }

    public function getContacts() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_contacts");

        return $query->rows;
    }

    public function getCitiesByRegion($region_id) {

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_cities WHERE region_id = '" . $this->db->escape($region_id) . "'");

        return $query->rows;
    }

    public function searchCities($search) {
        $sql = "SELECT c.*, r.region_name_ua FROM " . DB_PREFIX . "meest2_cities c 
                LEFT JOIN " . DB_PREFIX . "meest2_regions r ON c.region_id = r.region_id
                WHERE c.name_ua LIKE '%" . $this->db->escape($search) . "%' 
                   OR c.name_ru LIKE '%" . $this->db->escape($search) . "%' 
                LIMIT 20";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getStreetsByCity($city_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_streets WHERE city_id = '" . $this->db->escape($city_id) . "' ORDER BY name_ua");
        return $query->rows;
    }

    public function getBranchesByCity($city_id) {

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_branch WHERE city_id = '" . $this->db->escape($city_id) . "' ORDER BY city_ua");

        return $query->rows;
    }

    public function setMeest2CnUuid($order_id, $uuid, $contractID, $senderAddressPickUp) {

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_cn_uuid` = '" . $this->db->escape($uuid) . "' WHERE `order_id` = '" . (int)$order_id . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_contractID` = '" . $this->db->escape($contractID) . "' WHERE `order_id` = '" . (int)$order_id . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_sender_address_pick_up` = '" . $this->db->escape($senderAddressPickUp) . "' WHERE `order_id` = '" . (int)$order_id . "'");

    }

    public function setMeest2CnSenderAddressPickUp($senderAddressPickUp, $meest2CnUuid)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_sender_address_pick_up` = '" . $this->db->escape($senderAddressPickUp) . "' WHERE `meest2_cn_uuid` = '" . $meest2CnUuid . "'");
    }

    public function getMeest2CnUuid($order_id) {
        $query = $this->db->query("SELECT `meest2_cn_uuid` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");

        if ($query->num_rows) {
            return $query->row['meest2_cn_uuid'];
        } else {
            return null;
        }
    }

    public function getContact($contact_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_contacts WHERE id = '" . $this->db->escape($contact_id) . "' ORDER BY id");

        return $query->row;
    }

    public function getBranchById($br_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest2_branch WHERE branch_id = '" . $this->db->escape($br_id) . "'");

        if ($query->num_rows) {
            return $query->row;
        } else {
            return null;
        }
    }

    public function getOrders($page = 1, $sort_by = 'order_id', $order = 'ASC') {
        $limit = 10;
        $start = ($page - 1) * $limit;

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` 
                               WHERE shipping_code IN ('meest2.warehouse', 'meest2.courier', 'meest2.postomat') 
                               ORDER BY " . $this->db->escape($sort_by) . " " . $this->db->escape($order) . " 
                               LIMIT " . (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getTotalOrders() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "order` 
                               WHERE shipping_code IN ('meest2.warehouse', 'meest2.courier', 'meest2.postomat')");
        return $query->row['total'];
    }

    public function getOrderById($order_id) {
//        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND shipping_code = 'meest2.courier'");
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND shipping_code IN ('meest2.warehouse', 'meest2.courier', 'meest2.postomat')");

        return $query->row;
    }

    public function setMeest2RegisterID($order_id, $registerID) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_registerID` = '" . $this->db->escape($registerID) . "' WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function unsetMeest2RegisterID($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `meest2_registerID` = NULL WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function getBranchTotalRecordsAndLatestDate() {

        $query = $this->db->query("SELECT COUNT(*) AS total_records, MAX(updated_at) AS latest_update_date FROM `" . DB_PREFIX . "meest2_branch`");

        return [
            'total_records' => $query->row['total_records'],
            'latest_update_date' => $query->row['latest_update_date']
        ];
    }

    public function getRegionsTotalRecordsAndLatestDate() {

        $query = $this->db->query("SELECT COUNT(*) AS total_records, MAX(updated_at) AS latest_update_date FROM `" . DB_PREFIX . "meest2_regions`");

        return [
            'total_records' => $query->row['total_records'],
            'latest_update_date' => $query->row['latest_update_date']
        ];
    }

    public function getCitiesTotalRecordsAndLatestDate() {

        $query = $this->db->query("SELECT COUNT(*) AS total_records, MAX(updated_at) AS latest_update_date FROM `" . DB_PREFIX . "meest2_cities`");

        return [
            'total_records' => $query->row['total_records'],
            'latest_update_date' => $query->row['latest_update_date']
        ];
    }

    public function getStreetsTotalRecordsAndLatestDate() {

        $query = $this->db->query("SELECT COUNT(*) AS total_records, MAX(updated_at) AS latest_update_date FROM `" . DB_PREFIX . "meest2_streets`");

        return [
            'total_records' => $query->row['total_records'],
            'latest_update_date' => $query->row['latest_update_date']
        ];
    }

    public function getAllStreets() {
        $query = $this->db->query("SELECT street_id FROM " . DB_PREFIX . "meest2_streets");
        $streets = [];
        foreach ($query->rows as $row) {
            $streets[$row['street_id']] = $row['street_id'];
        }
        return $streets;
    }

    public function importStreets()
    {
        $url = 'https://meest-group.com/media/location/streets.txt';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL.');
        }

        $handle = @fopen($url, 'r');
        if (!$handle) {
            throw new Exception('The file at the specified URL could not be opened.');
        }

        $existingStreetIds = $this->getAllStreetIds();
        $existingStreetIds = array_flip($existingStreetIds);

        $insertData = [];
        $updateData = [];
        $insertCount = 0;
        $updateCount = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1251');

            if (empty($line)) {
                continue;
            }

            $fields = explode(';', $line);

            if (count($fields) < 12) {
                continue;
            }

            $addressData = [
                'street_id'       => trim($fields[0]),
                'type_ua'         => trim($fields[1]),
                'type_ru'         => trim($fields[2]),
                'name_ua'         => trim($fields[3]),
                'name_ru'         => trim($fields[4]),
                'city_id'         => trim($fields[5]),
                'region_id'       => trim($fields[6]),
                'district_ua'     => trim($fields[7]),
                'district_ru'     => trim($fields[8]),
                'region_ua'       => trim($fields[9]),
                'region_ru'       => trim($fields[10]),
                'postal_code'     => trim($fields[11])
            ];

            if (!isset($existingStreetIds[$addressData['street_id']])) {
                $insertData[] = $addressData;
            } else {
                $updateData[] = $addressData;
            }

            if (count($insertData) >= 1000) {
                $this->processBatchInsert($insertData);
                $insertCount += count($insertData);
                $insertData = [];
            }

            if (count($updateData) >= 1000) {
                $this->processBatchUpdate($updateData);
                $updateCount += count($updateData);
                $updateData = [];
            }
        }

        fclose($handle);

        if (!empty($insertData)) {
            $this->processBatchInsert($insertData);
            $insertCount += count($insertData);
        }

        if (!empty($updateData)) {
            $this->processBatchUpdate($updateData);
            $updateCount += count($updateData);
        }

        return [
            'inserted' => $insertCount,
            'updated'  => $updateCount,
        ];
    }

    private function getAllStreetIds()
    {
        $query = $this->db->query("SELECT street_id FROM " . DB_PREFIX . "meest2_streets");
        return array_column($query->rows, 'street_id');
    }

    private function processBatchInsert($data)
    {
        if (!empty($data)) {
            $this->bulkInsertStreets($data);
        }
    }

    private function processBatchUpdate($data)
    {
        if (!empty($data)) {
            $this->bulkUpdateStreets($data);
        }
    }

    public function bulkInsertStreets($data)
    {
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

    public function bulkUpdateStreets($data)
    {
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


    public function getAllCities() {
        $query = $this->db->query("SELECT city_id FROM " . DB_PREFIX . "meest2_cities");
        $cities = [];
        foreach ($query->rows as $row) {
            $cities[$row['city_id']] = $row['city_id'];
        }
        return $cities;
    }

    public function bulkInsertCities($data) {
        $values = [];
        foreach ($data as $row) {
            $values[] = "('" . $this->db->escape($row['city_id']) . "', '" . $this->db->escape($row['name_ua']) . "', '" . $this->db->escape($row['name_ru']) . "', '" . $this->db->escape($row['type_ua']) . "', '" . $this->db->escape($row['district_id']) . "', '" . $this->db->escape($row['region_id']) . "', '" . $this->db->escape($row['koatuu']) . "', '" . (int)$row['delivery_in_city'] . "')";
        }
        if (!empty($values)) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "meest2_cities (city_id, name_ua, name_ru, type_ua, district_id, region_id, koatuu, delivery_in_city) VALUES " . implode(', ', $values));
        }
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

    public function getOrdersByIds($orderIds) {
        $orderIds = array_map('intval', $orderIds);
        $orderIdsStr = implode(',', $orderIds);

        $query = $this->db->query("SELECT order_id, barcode FROM `" . DB_PREFIX . "meest2_parcels`
                                       WHERE order_id IN (" . $orderIdsStr . ")
                                       AND uuid IS NOT NULL
                                       AND uuid <> ''");

        return $query->rows;
    }

    public function saveMeestParcelData($orderId, $parcel, $contractID, $senderAddressPickUp) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest2_parcels` SET
            order_id = '" . (int)$orderId . "',
            uuid = '" . $this->db->escape($parcel['parcelID']) . "',
            parcel_number = '" . $this->db->escape($parcel['parcelNumber']) . "',
            barcode = '" . $this->db->escape($parcel['barCode']) . "',
            contractID =  '" . $this->db->escape($contractID) . "',
            sender_address_pick_up =  '" . $this->db->escape($senderAddressPickUp) . "'"
        );

        return $this->db->getLastId();
    }

    public function saveBranchesBatch($branches) {

        $columns = [
            'branch_id',
//            'branch_id_ref',
            'branch_no',
//            'branch_code',
            'branch_number',
            'branch_type',
            'is_branch_open',
            'is_branch_closed',
            'branch_type_id',
            'branch_type_descr',
//            'branch_type_app',
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
                    'branch_id'                 => isset($branchData['branchID']) ? $branchData['branchID'] : null,
                    'branch_no'                 => isset($branchData['branchNo']) ? $branchData['branchNo'] : null,
                    'branch_number'             => isset($branchData['branchNumber']) ? $branchData['branchNumber'] : null,
                    'branch_type'               => isset($branchData['branchType']) ? $branchData['branchType'] : null,
                    'is_branch_open'            => isset($branchData['isBranchOpen']) ? (int)$branchData['isBranchOpen'] : null,
                    'is_branch_closed'          => isset($branchData['isBranchClosed']) ? (int)$branchData['isBranchClosed'] : null,
                    'branch_type_id'            => isset($branchData['branchTypeID']) ? $branchData['branchTypeID'] : null,
                    'branch_type_descr'         => isset($branchData['branchTypeDescr']) ? $branchData['branchTypeDescr'] : null,
                    'branch_type_id_client'     => isset($branchData['branchTypeIDClient']) ? $branchData['branchTypeIDClient'] : null,
                    'client_type_subdivision'   => isset($branchData['ClientTypeSubdivision']) ? $branchData['ClientTypeSubdivision'] : null,
                    'client_type_subdivision_id'=> isset($branchData['ClientTypeSubdivisionID']) ? $branchData['ClientTypeSubdivisionID'] : null,
                    'short_name'                => isset($branchData['ShortName']) ? $branchData['ShortName'] : null,
                    'full_name'                 => isset($branchData['FullName']) ? $branchData['FullName'] : null,
                    'branch_descr_ua'           => isset($branchData['branchDescr']['descrUA']) ? $branchData['branchDescr']['descrUA'] : null,
                    'branch_descr_loc'          => isset($branchData['branchDescr']['descrLoc']) ? $branchData['branchDescr']['descrLoc'] : null,
                    'branch_descr_search_ua'    => isset($branchData['branchDescr']['descrSearchUA']) ? $branchData['branchDescr']['descrSearchUA'] : null,
                    'branch_descr_search_loc'   => isset($branchData['branchDescr']['descrSearchLoc']) ? $branchData['branchDescr']['descrSearchLoc'] : null,
                    'address_id'                => isset($branchData['addressID']) ? $branchData['addressID'] : null,
                    'address_descr_ua'          => isset($branchData['addressDescr']['descrUA']) ? $branchData['addressDescr']['descrUA'] : null,
                    'address_descr_ru'          => isset($branchData['addressDescr']['descrRU']) ? $branchData['addressDescr']['descrRU'] : null,
                    'address_descr_en'          => isset($branchData['addressDescr']['descrEN']) ? $branchData['addressDescr']['descrEN'] : null,
                    'address_descr_loc'         => isset($branchData['addressDescr']['descrLoc']) ? $branchData['addressDescr']['descrLoc'] : null,
                    'address_more_information'  => isset($branchData['addressMoreInformation']) ? $branchData['addressMoreInformation'] : null,
                    'city_id'                   => isset($branchData['cityID']) ? $branchData['cityID'] : null,
                    'city_ua'                   => isset($branchData['cityDescr']['descrUA']) ? $branchData['cityDescr']['descrUA'] : null,
                    'city_ru'                   => isset($branchData['cityDescr']['descrRU']) ? $branchData['cityDescr']['descrRU'] : null,
                    'city_en'                   => isset($branchData['cityDescr']['descrEN']) ? $branchData['cityDescr']['descrEN'] : null,
                    'city_loc'                  => isset($branchData['cityDescr']['descrLoc']) ? $branchData['cityDescr']['descrLoc'] : null,
                    'district_id'               => isset($branchData['districtID']) ? $branchData['districtID'] : null,
                    'district_ua'               => isset($branchData['districtDescr']['descrUA']) ? $branchData['districtDescr']['descrUA'] : null,
                    'district_ru'               => isset($branchData['districtDescr']['descrRU']) ? $branchData['districtDescr']['descrRU'] : null,
                    'district_en'               => isset($branchData['districtDescr']['descrEN']) ? $branchData['districtDescr']['descrEN'] : null,
                    'district_loc'              => isset($branchData['districtDescr']['descrLoc']) ? $branchData['districtDescr']['descrLoc'] : null,
                    'region_id'                 => isset($branchData['regionID']) ? $branchData['regionID'] : null,
                    'region_ua'                 => isset($branchData['regionDescr']['descrUA']) ? $branchData['regionDescr']['descrUA'] : null,
                    'region_ru'                 => isset($branchData['regionDescr']['descrRU']) ? $branchData['regionDescr']['descrRU'] : null,
                    'region_en'                 => isset($branchData['regionDescr']['descrEN']) ? $branchData['regionDescr']['descrEN'] : null,
                    'region_loc'                => isset($branchData['regionDescr']['descrLoc']) ? $branchData['regionDescr']['descrLoc'] : null,
                    'working_hours'             => isset($branchData['workingHours']) ? $branchData['workingHours'] : null,
                    'street_number'             => isset($branchData['building']) ? $branchData['building'] : null,
                    'zip'                       => isset($branchData['zipCode']) ? $branchData['zipCode'] : null,
                    'latitude'                  => isset($branchData['latitude']) ? (float)$branchData['latitude'] : null,
                    'longitude'                 => isset($branchData['longitude']) ? (float)$branchData['longitude'] : null,
                    'branch_work_time'          => isset($branchData['branchWorkTime']) ? json_encode($branchData['branchWorkTime'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'phone'                     => isset($branchData['phone']) ? $branchData['phone'] : null,
                    'address'                   => isset($branchData['address']) ? $branchData['address'] : null,
                    'payment_types'             => isset($branchData['paymentTypes']) ? $branchData['paymentTypes'] : null,
                    'branch_limits'             => isset($branchData['branchLimits']) ? json_encode($branchData['branchLimits'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'localization'              => isset($branchData['Localization']) ? $branchData['Localization'] : null,
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

    public function getContractIdByUuid($uuid) {
        $sql = "SELECT contractId FROM `" . DB_PREFIX . "meest2_parcels` WHERE `uuid` = '" . $this->db->escape($uuid) . "' LIMIT 1";
        $query = $this->db->query($sql);

        return isset($query->row['contractId']) ? $query->row['contractId'] : false;
    }

    public function getOrderShippingData($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "meest2_order_shipping_data` WHERE order_id = '" . (int)$order_id . "'");
        return $query->row;
    }

    public function updateOrderShippingData($order_id, $data) {
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
            region_code = '" . $this->db->escape($data['region_code']) . "',
            updated_at = NOW()");
    }

}
