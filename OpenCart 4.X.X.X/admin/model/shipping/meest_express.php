<?php

namespace Opencart\Admin\Model\Extension\MeestExpress\Shipping;

use Opencart\System\Engine\Model;

class MeestExpress extends Model
{
    const PLUGIN_VERSION = '1.5.0';

    public function install($typeInstall = true)
    {
        $this->load->model('setting/setting');

        $current_version = $this->config->get('shipping_meest_express_version');
        if ($current_version === null || $typeInstall) {
            $this->createTables();
        }

        if ($current_version !== self::PLUGIN_VERSION) {
            $this->migrate($typeInstall, $current_version);

            $this->model_setting_setting->editSetting('shipping_meest_express_version', [
                'shipping_meest_express_version' => self::PLUGIN_VERSION
            ]);
        }
    }

    public function uninstall()
    {
        // Drop tables if needed
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_branch`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_regions`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_cities`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_streets`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_contracts`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_contacts`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_order_shipping_data`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_parcels`");
    }

    private function createTables()
    {
        // Add meest_express_cn_uuid column to orders table if it doesn't exist
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'meest_express_cn_uuid'");
        if (!$query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `meest_express_cn_uuid` VARCHAR(100) NULL AFTER `tracking`");
        }
        
        // Create branch table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_branch`");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_branch` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `branch_id` VARCHAR(40) NOT NULL,
              `branch_no` INT(6) DEFAULT NULL,
              `branch_number` VARCHAR(20) DEFAULT NULL,
              `branch_type` VARCHAR(50) DEFAULT NULL,
              `is_branch_open` TINYINT(1) DEFAULT NULL,
              `is_branch_closed` TINYINT(1) DEFAULT NULL,
              `branch_type_id` VARCHAR(40) DEFAULT NULL,
              `branch_type_descr` VARCHAR(256) DEFAULT NULL,
              `branch_type_id_client` VARCHAR(40) DEFAULT NULL,
              `client_type_subdivision` VARCHAR(256) DEFAULT NULL,
              `client_type_subdivision_id` VARCHAR(40) DEFAULT NULL,
              `short_name` VARCHAR(256) DEFAULT NULL,
              `full_name` VARCHAR(512) DEFAULT NULL,
              `branch_descr_ua` VARCHAR(256) DEFAULT NULL,
              `branch_descr_loc` VARCHAR(256) DEFAULT NULL,
              `branch_descr_search_ua` VARCHAR(256) DEFAULT NULL,
              `branch_descr_search_loc` VARCHAR(256) DEFAULT NULL,
              `address_id` VARCHAR(40) DEFAULT NULL,
              `address_descr_ua` VARCHAR(256) DEFAULT NULL,
              `address_descr_ru` VARCHAR(256) DEFAULT NULL,
              `address_descr_en` VARCHAR(256) DEFAULT NULL,
              `address_descr_loc` VARCHAR(256) DEFAULT NULL,
              `address_more_information` VARCHAR(256) DEFAULT NULL,
              `city_id` VARCHAR(40) DEFAULT NULL,
              `city_ua` VARCHAR(256) DEFAULT NULL,
              `city_ru` VARCHAR(256) DEFAULT NULL,
              `city_en` VARCHAR(256) DEFAULT NULL,
              `city_loc` VARCHAR(256) DEFAULT NULL,
              `district_id` VARCHAR(40) DEFAULT NULL,
              `district_ua` VARCHAR(256) DEFAULT NULL,
              `district_ru` VARCHAR(256) DEFAULT NULL,
              `district_en` VARCHAR(256) DEFAULT NULL,
              `district_loc` VARCHAR(256) DEFAULT NULL,
              `region_id` VARCHAR(40) DEFAULT NULL,
              `region_ua` VARCHAR(256) DEFAULT NULL,
              `region_ru` VARCHAR(256) DEFAULT NULL,
              `region_en` VARCHAR(256) DEFAULT NULL,
              `region_loc` VARCHAR(256) DEFAULT NULL,
              `working_hours` VARCHAR(256) DEFAULT NULL,
              `street_number` VARCHAR(10) DEFAULT NULL,
              `zip` VARCHAR(10) DEFAULT NULL,
              `latitude` DECIMAL(10,6) DEFAULT NULL,
              `longitude` DECIMAL(10,6) DEFAULT NULL,
              `branch_work_time` TEXT DEFAULT NULL,
              `phone` VARCHAR(50) DEFAULT NULL,
              `address` VARCHAR(256) DEFAULT NULL,
              `payment_types` VARCHAR(256) DEFAULT NULL,
              `branch_limits` TEXT DEFAULT NULL,
              `localization` VARCHAR(10) DEFAULT NULL,
              `payment_methods` TEXT DEFAULT NULL,
              `customer_identification` TEXT DEFAULT NULL,
              `partner_services` TEXT DEFAULT NULL,
              `services` TEXT DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `branch_id_unique` (`branch_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX ."meest_express_district`");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_district` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  
                  `district_id` VARCHAR(40) DEFAULT NULL,             -- districtID
                  `district_ua` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrUA
                  `district_ru` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrRU
                  `district_en` VARCHAR(256) DEFAULT NULL,            -- districtDescr.descrEN
                  
                  `region_id` VARCHAR(40) DEFAULT NULL,               -- regionID
                  `region_ua` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrUA
                  `region_ru` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrRU
                  `region_en` VARCHAR(256) DEFAULT NULL,              -- regionDescr.descrEN
                  
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `district_id_unique` (`district_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        // Create regions table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_regions`");
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest_express_regions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `region_id` VARCHAR(36) NOT NULL,
                `region_name_ua` VARCHAR(100) NOT NULL,
                `region_name_en` VARCHAR(100) NOT NULL,
                `country_id` VARCHAR(36) NOT NULL,
                `zone_id` INT(11) DEFAULT NULL,
                `creatеd_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8');

        // Create cities table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_cities`");
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest_express_cities` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `city_id` VARCHAR(36) NOT NULL,
                `name_ua` VARCHAR(100) NOT NULL,
                `name_ru` VARCHAR(100) NOT NULL,
                `type_ua` VARCHAR(50),
                `district_id` VARCHAR(36),
                `region_id` VARCHAR(36),
                `koatuu` VARCHAR(20),
                `delivery_in_city` TINYINT(1),
                `creatеd_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL,
                INDEX (`city_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8');

        // Create streets table
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "meest_express_streets`");
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . 'meest_express_streets` (
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
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL,
                INDEX (`street_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8');

        // Create contracts table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_contracts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `contractID` CHAR(36) NOT NULL,
                `creatеd_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        // Create contacts table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_contacts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `phone` VARCHAR(20) NOT NULL,
                `firstname` VARCHAR(50) NOT NULL,
                `lastname` VARCHAR(50) NOT NULL,
                `middlename` VARCHAR(50) DEFAULT NULL,
                `creatеd_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        // Add columns to order table
        $this->addOrderColumns();

        // Create order shipping data table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_order_shipping_data` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `order_id` INT(11) NOT NULL,
                  `shipping_method` VARCHAR(100) NOT NULL,
                  `city_code` VARCHAR(40) DEFAULT NULL,
                  `branch_code` VARCHAR(40) DEFAULT NULL,
                  `address_code` VARCHAR(40) DEFAULT NULL,
                  `region_code` VARCHAR(40) DEFAULT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `order_id_unique` (`order_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        $column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "meest_express_order_shipping_data` LIKE 'building';");
        if (!$column->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "meest_express_order_shipping_data` ADD `building` VARCHAR(100) DEFAULT NULL AFTER `address_code`;");
        }

        // Create parcels table
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "meest_express_parcels` (
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
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` DATETIME NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `order_id` (`order_id`),
                  KEY `uuid` (`uuid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");
    }

    private function addOrderColumns()
    {
        $columns = [
            'meest_express_cn_uuid' => 'VARCHAR(100) AFTER `order_id`',
            'meest_express_contractID' => 'VARCHAR(100) AFTER `meest_express_cn_uuid`',
            'meest_express_registerID' => 'VARCHAR(100) AFTER `meest_express_contractID`',
            'meest_express_sender_address_pick_up' => 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `order_id`'
        ];

        foreach ($columns as $column => $definition) {
            $check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE '$column'");
            if (!$check->num_rows) {
                $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `$column` $definition;");
            }
        }
    }

    public function migrate($typeInstall, $from_version = null)
    {
        // Migration logic if needed
    }

    // Basic methods - остальные методы будут добавлены отдельно
    public function getBranches($data = array())
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_branch");
        return $query->rows;
    }

    public function getRegions()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_regions ORDER BY region_name_ua");
        return $query->rows;
    }

    public function getCities()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities ORDER BY name_ua");
        return $query->rows;
    }

    public function getStreets()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets ORDER BY name_ua");
        return $query->rows;
    }

    // Region methods
    public function getRegion($region_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_regions WHERE region_id = '" . $this->db->escape($region_id) . "'");
        return $query->row;
    }

    public function addRegion($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_regions` SET
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
        ");
    }

    public function editRegion($region_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest_express_regions` SET
            `region_name_ua` = '" . $this->db->escape($data['region_name_ua']) . "',
            `region_name_en` = '" . $this->db->escape($data['region_name_en']) . "',
            `country_id` = '" . $this->db->escape($data['country_id']) . "',
            `zone_id` = " . ($data['zone_id'] ? (int)$data['zone_id'] : "NULL") . "
            WHERE `region_id` = '" . $this->db->escape($region_id) . "'
        ");
    }

    // Branch methods
    public function getBranch($branch_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "meest_express_branch` WHERE branch_id = '" . $this->db->escape($branch_id) . "'");
        return $query->row;
    }

    public function getBranchById($br_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_branch WHERE branch_id = '" . $this->db->escape($br_id) . "'");
        return $query->num_rows ? $query->row : null;
    }

    public function addBranch($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_branch` SET
            branch_id = '" . $this->db->escape($data['branch_id']) . "',
            branch_no = '" . (int)$data['branch_no'] . "',
            branch_number = '" . $this->db->escape($data['branch_number']) . "',
            branch_type = '" . $this->db->escape($data['branch_type']) . "',
            is_branch_open = '" . ($data['is_branch_open'] ? 1 : 0) . "',
            is_branch_closed = '" . ($data['is_branch_closed'] ? 1 : 0) . "',
            branch_type_id = '" . $this->db->escape($data['branch_type_id']) . "',
            branch_type_descr = '" . $this->db->escape($data['branch_type_descr']) . "',
            branch_type_id_client = '" . $this->db->escape($data['branch_type_id_client']) . "',
            client_type_subdivision = '" . $this->db->escape($data['client_type_subdivision']) . "',
            client_type_subdivision_id = '" . $this->db->escape($data['client_type_subdivision_id']) . "',
            short_name = '" . $this->db->escape($data['short_name']) . "',
            full_name = '" . $this->db->escape($data['full_name']) . "',
            branch_descr_ua = '" . $this->db->escape($data['branch_descr_ua']) . "',
            branch_descr_loc = '" . $this->db->escape($data['branch_descr_loc']) . "',
            branch_descr_search_ua = '" . $this->db->escape($data['branch_descr_search_ua']) . "',
            branch_descr_search_loc = '" . $this->db->escape($data['branch_descr_search_loc']) . "',
            address_id = '" . $this->db->escape($data['address_id']) . "',
            address_descr_ua = '" . $this->db->escape($data['address_descr_ua']) . "',
            address_descr_ru = '" . $this->db->escape($data['address_descr_ru']) . "',
            address_descr_en = '" . $this->db->escape($data['address_descr_en']) . "',
            address_descr_loc = '" . $this->db->escape($data['address_descr_loc']) . "',
            address_more_information = '" . $this->db->escape($data['address_more_information']) . "',
            city_id = '" . $this->db->escape($data['city_id']) . "',
            city_ua = '" . $this->db->escape($data['city_ua']) . "',
            city_ru = '" . $this->db->escape($data['city_ru']) . "',
            city_en = '" . $this->db->escape($data['city_en']) . "',
            city_loc = '" . $this->db->escape($data['city_loc']) . "',
            district_id = '" . $this->db->escape($data['district_id']) . "',
            district_ua = '" . $this->db->escape($data['district_ua']) . "',
            district_ru = '" . $this->db->escape($data['district_ru']) . "',
            district_en = '" . $this->db->escape($data['district_en']) . "',
            district_loc = '" . $this->db->escape($data['district_loc']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            region_ua = '" . $this->db->escape($data['region_ua']) . "',
            region_ru = '" . $this->db->escape($data['region_ru']) . "',
            region_en = '" . $this->db->escape($data['region_en']) . "',
            region_loc = '" . $this->db->escape($data['region_loc']) . "',
            working_hours = '" . $this->db->escape($data['working_hours']) . "',
            street_number = '" . $this->db->escape($data['street_number']) . "',
            zip = '" . $this->db->escape($data['zip']) . "',
            latitude = '" . (float)$data['latitude'] . "',
            longitude = '" . (float)$data['longitude'] . "',
            branch_work_time = '" . $this->db->escape(json_encode($data['branch_work_time'], JSON_UNESCAPED_UNICODE)) . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            payment_types = '" . $this->db->escape($data['payment_types']) . "',
            branch_limits = '" . $this->db->escape(json_encode($data['branch_limits'], JSON_UNESCAPED_UNICODE)) . "',
            localization = '" . $this->db->escape($data['localization']) . "',
            payment_methods = '" . $this->db->escape(json_encode($data['payment_methods'])) . "',
            customer_identification = '" . $this->db->escape(json_encode($data['customer_identification'])) . "',
            partner_services = '" . $this->db->escape(json_encode($data['partner_services'])) . "',
            services = '" . $this->db->escape(json_encode($data['services'])) . "'
        ");
    }

    public function editBranch($branch_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest_express_branch` SET
            branch_no = '" . (int)$data['branch_no'] . "',
            branch_number = '" . $this->db->escape($data['branch_number']) . "',
            branch_type = '" . $this->db->escape($data['branch_type']) . "',
            is_branch_open = '" . ($data['is_branch_open'] ? 1 : 0) . "',
            is_branch_closed = '" . ($data['is_branch_closed'] ? 1 : 0) . "',
            branch_type_id = '" . $this->db->escape($data['branch_type_id']) . "',
            branch_type_descr = '" . $this->db->escape($data['branch_type_descr']) . "',
            branch_type_id_client = '" . $this->db->escape($data['branch_type_id_client']) . "',
            client_type_subdivision = '" . $this->db->escape($data['client_type_subdivision']) . "',
            client_type_subdivision_id = '" . $this->db->escape($data['client_type_subdivision_id']) . "',
            short_name = '" . $this->db->escape($data['short_name']) . "',
            full_name = '" . $this->db->escape($data['full_name']) . "',
            branch_descr_ua = '" . $this->db->escape($data['branch_descr_ua']) . "',
            branch_descr_loc = '" . $this->db->escape($data['branch_descr_loc']) . "',
            branch_descr_search_ua = '" . $this->db->escape($data['branch_descr_search_ua']) . "',
            branch_descr_search_loc = '" . $this->db->escape($data['branch_descr_search_loc']) . "',
            address_id = '" . $this->db->escape($data['address_id']) . "',
            address_descr_ua = '" . $this->db->escape($data['address_descr_ua']) . "',
            address_descr_ru = '" . $this->db->escape($data['address_descr_ru']) . "',
            address_descr_en = '" . $this->db->escape($data['address_descr_en']) . "',
            address_descr_loc = '" . $this->db->escape($data['address_descr_loc']) . "',
            address_more_information = '" . $this->db->escape($data['address_more_information']) . "',
            city_id = '" . $this->db->escape($data['city_id']) . "',
            city_ua = '" . $this->db->escape($data['city_ua']) . "',
            city_ru = '" . $this->db->escape($data['city_ru']) . "',
            city_en = '" . $this->db->escape($data['city_en']) . "',
            city_loc = '" . $this->db->escape($data['city_loc']) . "',
            district_id = '" . $this->db->escape($data['district_id']) . "',
            district_ua = '" . $this->db->escape($data['district_ua']) . "',
            district_ru = '" . $this->db->escape($data['district_ru']) . "',
            district_en = '" . $this->db->escape($data['district_en']) . "',
            district_loc = '" . $this->db->escape($data['district_loc']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            region_ua = '" . $this->db->escape($data['region_ua']) . "',
            region_ru = '" . $this->db->escape($data['region_ru']) . "',
            region_en = '" . $this->db->escape($data['region_en']) . "',
            region_loc = '" . $this->db->escape($data['region_loc']) . "',
            working_hours = '" . $this->db->escape($data['working_hours']) . "',
            street_number = '" . $this->db->escape($data['street_number']) . "',
            zip = '" . $this->db->escape($data['zip']) . "',
            latitude = '" . (float)$data['latitude'] . "',
            longitude = '" . (float)$data['longitude'] . "',
            branch_work_time = '" . $this->db->escape(json_encode($data['branch_work_time'], JSON_UNESCAPED_UNICODE)) . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            payment_types = '" . $this->db->escape($data['payment_types']) . "',
            branch_limits = '" . $this->db->escape(json_encode($data['branch_limits'], JSON_UNESCAPED_UNICODE)) . "',
            localization = '" . $this->db->escape($data['localization']) . "',
            payment_methods = '" . $this->db->escape(json_encode($data['payment_methods'])) . "',
            customer_identification = '" . $this->db->escape(json_encode($data['customer_identification'])) . "',
            partner_services = '" . $this->db->escape(json_encode($data['partner_services'])) . "',
            services = '" . $this->db->escape(json_encode($data['services'])) . "'
            WHERE branch_id = '" . $this->db->escape($branch_id) . "'
        ");
    }

    public function saveBranchesBatch($branches)
    {
        // Check if table exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "meest_express_branch'");
        if (!$tableCheck->num_rows) {
            $this->install(true);
        }

        $columns = [
            'branch_id', 'branch_no', 'branch_number', 'branch_type',
            'is_branch_open', 'is_branch_closed', 'branch_type_id',
            'branch_type_descr', 'branch_type_id_client', 'client_type_subdivision',
            'client_type_subdivision_id', 'short_name', 'full_name',
            'branch_descr_ua', 'branch_descr_loc', 'branch_descr_search_ua',
            'branch_descr_search_loc', 'address_id', 'address_descr_ua',
            'address_descr_ru', 'address_descr_en', 'address_descr_loc',
            'address_more_information', 'city_id', 'city_ua', 'city_ru',
            'city_en', 'city_loc', 'district_id', 'district_ua', 'district_ru',
            'district_en', 'district_loc', 'region_id', 'region_ua', 'region_ru',
            'region_en', 'region_loc', 'working_hours', 'street_number', 'zip',
            'latitude', 'longitude', 'branch_work_time', 'phone', 'address',
            'payment_types', 'branch_limits', 'localization', 'payment_methods',
            'customer_identification', 'partner_services', 'services'
        ];

        $chunks = array_chunk($branches, 100);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $branchData) {
                $dataToSave = [
                    'branch_id' => $branchData['branchID'] ?? null,
                    'branch_no' => $branchData['branchNo'] ?? null,
                    'branch_number' => $branchData['branchNumber'] ?? null,
                    'branch_type' => $branchData['branchType'] ?? null,
                    'is_branch_open' => isset($branchData['isBranchOpen']) ? (int)$branchData['isBranchOpen'] : null,
                    'is_branch_closed' => isset($branchData['isBranchClosed']) ? (int)$branchData['isBranchClosed'] : null,
                    'branch_type_id' => $branchData['branchTypeID'] ?? null,
                    'branch_type_descr' => $branchData['branchTypeDescr'] ?? null,
                    'branch_type_id_client' => $branchData['branchTypeIDClient'] ?? null,
                    'client_type_subdivision' => $branchData['ClientTypeSubdivision'] ?? null,
                    'client_type_subdivision_id' => $branchData['ClientTypeSubdivisionID'] ?? null,
                    'short_name' => $branchData['ShortName'] ?? null,
                    'full_name' => $branchData['FullName'] ?? null,
                    'branch_descr_ua' => $branchData['branchDescr']['descrUA'] ?? null,
                    'branch_descr_loc' => $branchData['branchDescr']['descrLoc'] ?? null,
                    'branch_descr_search_ua' => $branchData['branchDescr']['descrSearchUA'] ?? null,
                    'branch_descr_search_loc' => $branchData['branchDescr']['descrSearchLoc'] ?? null,
                    'address_id' => $branchData['addressID'] ?? null,
                    'address_descr_ua' => $branchData['addressDescr']['descrUA'] ?? null,
                    'address_descr_ru' => $branchData['addressDescr']['descrRU'] ?? null,
                    'address_descr_en' => $branchData['addressDescr']['descrEN'] ?? null,
                    'address_descr_loc' => $branchData['addressDescr']['descrLoc'] ?? null,
                    'address_more_information' => $branchData['addressMoreInformation'] ?? null,
                    'city_id' => $branchData['cityID'] ?? null,
                    'city_ua' => $branchData['cityDescr']['descrUA'] ?? null,
                    'city_ru' => $branchData['cityDescr']['descrRU'] ?? null,
                    'city_en' => $branchData['cityDescr']['descrEN'] ?? null,
                    'city_loc' => $branchData['cityDescr']['descrLoc'] ?? null,
                    'district_id' => $branchData['districtID'] ?? null,
                    'district_ua' => $branchData['districtDescr']['descrUA'] ?? null,
                    'district_ru' => $branchData['districtDescr']['descrRU'] ?? null,
                    'district_en' => $branchData['districtDescr']['descrEN'] ?? null,
                    'district_loc' => $branchData['districtDescr']['descrLoc'] ?? null,
                    'region_id' => $branchData['regionID'] ?? null,
                    'region_ua' => $branchData['regionDescr']['descrUA'] ?? null,
                    'region_ru' => $branchData['regionDescr']['descrRU'] ?? null,
                    'region_en' => $branchData['regionDescr']['descrEN'] ?? null,
                    'region_loc' => $branchData['regionDescr']['descrLoc'] ?? null,
                    'working_hours' => $branchData['workingHours'] ?? null,
                    'street_number' => $branchData['building'] ?? null,
                    'zip' => $branchData['zipCode'] ?? null,
                    'latitude' => isset($branchData['latitude']) ? (float)$branchData['latitude'] : null,
                    'longitude' => isset($branchData['longitude']) ? (float)$branchData['longitude'] : null,
                    'branch_work_time' => isset($branchData['branchWorkTime']) ? json_encode($branchData['branchWorkTime'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'phone' => $branchData['phone'] ?? null,
                    'address' => $branchData['address'] ?? null,
                    'payment_types' => $branchData['paymentTypes'] ?? null,
                    'branch_limits' => isset($branchData['branchLimits']) ? json_encode($branchData['branchLimits'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'localization' => $branchData['Localization'] ?? null,
                    'payment_methods' => isset($branchData['paymentMethods']) ? json_encode($branchData['paymentMethods'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'customer_identification' => isset($branchData['customerIdentification']) ? json_encode($branchData['customerIdentification'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'partner_services' => isset($branchData['PartnerServices']) ? json_encode($branchData['PartnerServices'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'services' => isset($branchData['Services']) ? json_encode($branchData['Services'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
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

                $sql = "INSERT INTO `" . DB_PREFIX . "meest_express_branch` ($columnsList)
                VALUES " . implode(", ", $rows) . "
                ON DUPLICATE KEY UPDATE " . implode(", ", $updateColumns);

                $this->db->query($sql);
            }
        }

        return true;
    }

    // City methods
    public function getCity($city_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities WHERE city_id = '" . $this->db->escape($city_id) . "'");
        return $query->row;
    }

    public function getAllCities()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities ORDER BY name_ua");
        return $query->rows;
    }

    public function addCity($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_cities` SET
            `city_id` = '" . $this->db->escape($data['city_id']) . "',
            `name_ua` = '" . $this->db->escape($data['name_ua']) . "',
            `name_ru` = '" . $this->db->escape($data['name_ru']) . "',
            `type_ua` = '" . $this->db->escape($data['type_ua']) . "',
            `district_id` = '" . $this->db->escape($data['district_id']) . "',
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `koatuu` = '" . $this->db->escape($data['koatuu']) . "',
            `delivery_in_city` = '" . (isset($data['delivery_in_city']) ? (int)$data['delivery_in_city'] : 0) . "'
        ");
    }

    public function editCity($city_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest_express_cities` SET
            `name_ua` = '" . $this->db->escape($data['name_ua']) . "',
            `name_ru` = '" . $this->db->escape($data['name_ru']) . "',
            `type_ua` = '" . $this->db->escape($data['type_ua']) . "',
            `district_id` = '" . $this->db->escape($data['district_id']) . "',
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `koatuu` = '" . $this->db->escape($data['koatuu']) . "',
            `delivery_in_city` = '" . (isset($data['delivery_in_city']) ? (int)$data['delivery_in_city'] : 0) . "'
            WHERE `city_id` = '" . $this->db->escape($city_id) . "'
        ");
    }

    public function bulkInsertCities($cities)
    {
        // Check if table exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "meest_express_cities'");
        if (!$tableCheck->num_rows) {
            $this->install(true);
        }

        $chunks = array_chunk($cities, 100);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $cityData) {
                $rows[] = "(
                    '" . $this->db->escape($cityData['city_id']) . "',
                    '" . $this->db->escape($cityData['name_ua']) . "',
                    '" . $this->db->escape($cityData['name_ru']) . "',
                    '" . $this->db->escape($cityData['type_ua']) . "',
                    '" . $this->db->escape($cityData['district_id']) . "',
                    '" . $this->db->escape($cityData['region_id']) . "',
                    '" . $this->db->escape($cityData['koatuu']) . "',
                    " . (isset($cityData['delivery_in_city']) ? (int)$cityData['delivery_in_city'] : 0) . "
                )";
            }

            if (!empty($rows)) {
                $sql = "INSERT INTO `" . DB_PREFIX . "meest_express_cities` 
                    (`city_id`, `name_ua`, `name_ru`, `type_ua`, `district_id`, `region_id`, `koatuu`, `delivery_in_city`)
                    VALUES " . implode(", ", $rows) . "
                    ON DUPLICATE KEY UPDATE
                    `name_ua` = VALUES(`name_ua`),
                    `name_ru` = VALUES(`name_ru`),
                    `type_ua` = VALUES(`type_ua`),
                    `district_id` = VALUES(`district_id`),
                    `region_id` = VALUES(`region_id`),
                    `koatuu` = VALUES(`koatuu`),
                    `delivery_in_city` = VALUES(`delivery_in_city`)";

                $this->db->query($sql);
            }
        }

        return true;
    }
    public function saveDistricts($data)
    {
        if(!empty($data)){
            foreach ($data as $datum) {
                $this->saveDistrict($datum);
            }
            $result['success'] = true;
            $result['data'] = true;

            return $result;
        }
    }
    public function saveDistrict($branchData) {
        $dataToSave = [
            'district_id'               => $branchData['districtID'] ?? null,
            'district_ua'               => isset($branchData['districtDescr']['descrUA']) ? $branchData['districtDescr']['descrUA'] : null,
            'district_ru'               => isset($branchData['districtDescr']['descrRU']) ? $branchData['districtDescr']['descrRU'] : null,
            'district_en'               => isset($branchData['districtDescr']['descrEN']) ? $branchData['districtDescr']['descrEN'] : null,
            'district_loc'              => isset($branchData['districtDescr']['descrLoc']) ? $branchData['districtDescr']['descrLoc'] : null,
            'region_id'                 => $branchData['regionID'] ?? null,
            'region_ua'                 => isset($branchData['regionDescr']['descrUA']) ? $branchData['regionDescr']['descrUA'] : null,
            'region_ru'                 => isset($branchData['regionDescr']['descrRU']) ? $branchData['regionDescr']['descrRU'] : null,
            'region_en'                 => isset($branchData['regionDescr']['descrEN']) ? $branchData['regionDescr']['descrEN'] : null,
            'region_loc'                => isset($branchData['regionDescr']['descrLoc']) ? $branchData['regionDescr']['descrLoc'] : null,
        ];

        $existingBranch = $this->getDistrict($dataToSave['district_id']);
        if (empty($existingBranch)) {
            return  $this->addDistrict($dataToSave);
        } else {
            return   $this->editDistrict($dataToSave['district_id'], $dataToSave);
        }

    }
    public function getDistrict($district_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_district WHERE district_id = '" . $this->db->escape($district_id) . "'");

        return $query->row;
    }
    public function addDistrict($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "meest_express_district SET
            district_id = '" . $this->db->escape($data['district_id']) . "',
            district_ua = '" . $this->db->escape($data['district_ua']) . "',
            district_ru = '" . $this->db->escape($data['district_ru']) . "',
            district_en = '" . $this->db->escape($data['district_en']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            region_ua = '" . $this->db->escape($data['region_ua']) . "',
            region_ru = '" . $this->db->escape($data['region_ru']) . "',
            region_en = '" . $this->db->escape($data['region_en']) . "'
        ");
    }

    public function editDistrict($district_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "meest_express_district SET
             district_id = '" . $this->db->escape($data['district_id']) . "',
            district_ua = '" . $this->db->escape($data['district_ua']) . "',
            district_ru = '" . $this->db->escape($data['district_ru']) . "',
            district_en = '" . $this->db->escape($data['district_en']) . "',
            region_id = '" . $this->db->escape($data['region_id']) . "',
            region_ua = '" . $this->db->escape($data['region_ua']) . "',
            region_ru = '" . $this->db->escape($data['region_ru']) . "',
            region_en = '" . $this->db->escape($data['region_en']) . "'
        WHERE district_id = '" . $this->db->escape($district_id) . "'
        ");
    }
    public function bulkUpdateCities($cities)
    {
        foreach ($cities as $cityData) {
            $this->editCity($cityData['city_id'], $cityData);
        }
        return true;
    }

    // Street methods
    public function getStreet($street_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets WHERE street_id = '" . $this->db->escape($street_id) . "'");
        return $query->row;
    }

    public function addStreet($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_streets` SET
            `street_id` = '" . $this->db->escape($data['street_id']) . "',
            `type_ua` = '" . $this->db->escape($data['type_ua']) . "',
            `type_ru` = '" . $this->db->escape($data['type_ru']) . "',
            `name_ua` = '" . $this->db->escape($data['name_ua']) . "',
            `name_ru` = '" . $this->db->escape($data['name_ru']) . "',
            `city_id` = '" . $this->db->escape($data['city_id']) . "',
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `district_ua` = '" . $this->db->escape($data['district_ua']) . "',
            `district_ru` = '" . $this->db->escape($data['district_ru']) . "',
            `region_ua` = '" . $this->db->escape($data['region_ua']) . "',
            `region_ru` = '" . $this->db->escape($data['region_ru']) . "',
            `postal_code` = '" . $this->db->escape($data['postal_code']) . "'
        ");
    }

    public function editStreet($street_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "meest_express_streets` SET
            `type_ua` = '" . $this->db->escape($data['type_ua']) . "',
            `type_ru` = '" . $this->db->escape($data['type_ru']) . "',
            `name_ua` = '" . $this->db->escape($data['name_ua']) . "',
            `name_ru` = '" . $this->db->escape($data['name_ru']) . "',
            `city_id` = '" . $this->db->escape($data['city_id']) . "',
            `region_id` = '" . $this->db->escape($data['region_id']) . "',
            `district_ua` = '" . $this->db->escape($data['district_ua']) . "',
            `district_ru` = '" . $this->db->escape($data['district_ru']) . "',
            `region_ua` = '" . $this->db->escape($data['region_ua']) . "',
            `region_ru` = '" . $this->db->escape($data['region_ru']) . "',
            `postal_code` = '" . $this->db->escape($data['postal_code']) . "'
            WHERE `street_id` = '" . $this->db->escape($street_id) . "'
        ");
    }

    public function getAllStreets()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets ORDER BY name_ua");
        return $query->rows;
    }

    public function getAllStreetIds()
    {
        $query = $this->db->query("SELECT street_id FROM " . DB_PREFIX . "meest_express_streets");
        return array_column($query->rows, 'street_id');
    }

    public function bulkInsertStreets($streets)
    {
        // Check if table exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "meest_express_streets'");
        if (!$tableCheck->num_rows) {
            $this->install(true);
        }

        $chunks = array_chunk($streets, 100);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $streetData) {
                $rows[] = "(
                    '" . $this->db->escape($streetData['street_id']) . "',
                    '" . $this->db->escape($streetData['type_ua']) . "',
                    '" . $this->db->escape($streetData['type_ru']) . "',
                    '" . $this->db->escape($streetData['name_ua']) . "',
                    '" . $this->db->escape($streetData['name_ru']) . "',
                    '" . $this->db->escape($streetData['city_id']) . "',
                    '" . $this->db->escape($streetData['region_id']) . "',
                    '" . $this->db->escape($streetData['district_ua']) . "',
                    '" . $this->db->escape($streetData['district_ru']) . "',
                    '" . $this->db->escape($streetData['region_ua']) . "',
                    '" . $this->db->escape($streetData['region_ru']) . "',
                    '" . $this->db->escape($streetData['postal_code']) . "'
                )";
            }

            if (!empty($rows)) {
                $sql = "INSERT INTO `" . DB_PREFIX . "meest_express_streets` 
                    (`street_id`, `type_ua`, `type_ru`, `name_ua`, `name_ru`, `city_id`, `region_id`, `district_ua`, `district_ru`, `region_ua`, `region_ru`, `postal_code`)
                    VALUES " . implode(", ", $rows) . "
                    ON DUPLICATE KEY UPDATE
                    `type_ua` = VALUES(`type_ua`),
                    `type_ru` = VALUES(`type_ru`),
                    `name_ua` = VALUES(`name_ua`),
                    `name_ru` = VALUES(`name_ru`),
                    `city_id` = VALUES(`city_id`),
                    `region_id` = VALUES(`region_id`),
                    `district_ua` = VALUES(`district_ua`),
                    `district_ru` = VALUES(`district_ru`),
                    `region_ua` = VALUES(`region_ua`),
                    `region_ru` = VALUES(`region_ru`),
                    `postal_code` = VALUES(`postal_code`)";

                $this->db->query($sql);
            }
        }

        return true;
    }

    public function bulkUpdateStreets($streets)
    {
        foreach ($streets as $streetData) {
            $this->editStreet($streetData['street_id'], $streetData);
        }
        return true;
    }

    public function importStreets()
    {
        $url = 'https://meest-group.com/media/location/streets.txt';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL.');
        }

        $handle = @fopen($url, 'r');
        if (!$handle) {
            throw new \Exception('The file at the specified URL could not be opened.');
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

            $addressData = array(
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
            );

            if (!isset($existingStreetIds[$addressData['street_id']])) {
                $insertData[] = $addressData;
            } else {
                $updateData[] = $addressData;
            }

            if (count($insertData) >= 1000) {
                $this->bulkInsertStreets($insertData);
                $insertCount += count($insertData);
                $insertData = [];
            }

            if (count($updateData) >= 1000) {
                $this->processBatchUpdateStreets($updateData);
                $updateCount += count($updateData);
                $updateData = [];
            }
        }

        fclose($handle);

        // Process remaining data
        if (!empty($insertData)) {
            $this->bulkInsertStreets($insertData);
            $insertCount += count($insertData);
        }

        if (!empty($updateData)) {
            $this->processBatchUpdateStreets($updateData);
            $updateCount += count($updateData);
        }

        return [
            'inserted' => $insertCount,
            'updated'  => $updateCount,
        ];
    }

    private function importStreetsBatch($city_id, $streets)
    {
        // Check if table exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "meest_express_streets'");
        if (!$tableCheck->num_rows) {
            $this->install(true);
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "meest_express_streets` WHERE city_id = '" . $this->db->escape($city_id) . "'");

        $chunks = array_chunk($streets, 100);

        foreach ($chunks as $chunk) {
            $rows = [];

            foreach ($chunk as $streetData) {
                $rows[] = "(
                    '" . $this->db->escape($streetData['street_id']) . "',
                    '" . $this->db->escape($streetData['type_ua']) . "',
                    '" . $this->db->escape($streetData['type_ru']) . "',
                    '" . $this->db->escape($streetData['name_ua']) . "',
                    '" . $this->db->escape($streetData['name_ru']) . "',
                    '" . $this->db->escape($city_id) . "',
                    '" . $this->db->escape($streetData['region_id']) . "',
                    '" . $this->db->escape($streetData['district_ua']) . "',
                    '" . $this->db->escape($streetData['district_ru']) . "',
                    '" . $this->db->escape($streetData['region_ua']) . "',
                    '" . $this->db->escape($streetData['region_ru']) . "',
                    '" . $this->db->escape($streetData['postal_code']) . "'
                )";
            }

            if (!empty($rows)) {
                $sql = "INSERT INTO `" . DB_PREFIX . "meest_express_streets` 
                    (`street_id`, `type_ua`, `type_ru`, `name_ua`, `name_ru`, `city_id`, `region_id`, `district_ua`, `district_ru`, `region_ua`, `region_ru`, `postal_code`)
                    VALUES " . implode(", ", $rows);

                $this->db->query($sql);
            }
        }

        return true;
    }

    // Contract methods
    public function getContracts()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_contracts ORDER BY creatеd_at DESC");
        return $query->rows;
    }

    public function addContract($contractID)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_contracts` SET contractID = '" . $this->db->escape($contractID) . "'");
    }

    public function deleteContract($contract_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "meest_express_contracts` WHERE id = '" . (int)$contract_id . "'");
    }

    // Contact methods
    public function addContact($phone, $firstname, $lastname, $middlename)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_contacts` SET
            phone = '" . $this->db->escape($phone) . "',
            firstname = '" . $this->db->escape($firstname) . "',
            lastname = '" . $this->db->escape($lastname) . "',
            middlename = '" . $this->db->escape($middlename) . "'
        ");
    }

    public function deleteContact($contact_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "meest_express_contacts` WHERE id = '" . (int)$contact_id . "'");
    }

    public function getContacts()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_contacts ORDER BY creatеd_at DESC");
        return $query->rows;
    }

    public function getContact($contact_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_contacts WHERE id = '" . (int)$contact_id . "'");
        return $query->row;
    }

    // Search methods
    public function getCitiesByRegion($region_id) {

        $query = $this->db->query("SELECT c.*, CONCAT(
            c.name_ua,
            IFNULL(CONCAT(' (', d.district_ua, ' р-н)'), '')
    ) AS name_ua 
    FROM " . DB_PREFIX . "meest_express_cities c
        LEFT JOIN " . DB_PREFIX . "meest_express_district  d ON (c.district_id = d.district_id)
        WHERE c.region_id = '" . $this->db->escape($region_id) . "'");

        return $query->rows;
    }
    public function searchCities($search)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_cities WHERE name_ua LIKE '%" . $this->db->escape($search) . "%' ORDER BY name_ua LIMIT 50");
        return $query->rows;
    }


    public function getStreetsByCity($city_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets WHERE city_id = '" . $this->db->escape($city_id) . "' ORDER BY name_ua");
        return $query->rows;
    }

    public function searchStreetsByCity($city_id, $search)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_streets WHERE city_id = '" . $this->db->escape($city_id) . "' AND (street_name_ua LIKE '%" . $this->db->escape($search) . "%' OR name_ua LIKE '%" . $this->db->escape($search) . "%') ORDER BY name_ua LIMIT 50");
        return $query->rows;
    }


    public function getBranchesByCity($city_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "meest_express_branch WHERE city_id = '" . $this->db->escape($city_id) . "' ORDER BY short_name");
        return $query->rows;
    }

    // Order methods
    public function setMeestExpressCnUuid($order_id, $uuid, $contractID, $senderAddressPickUp)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_cn_uuid = '" . $this->db->escape($uuid) . "' WHERE order_id = '" . (int)$order_id . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_contractID = '" . $this->db->escape($contractID) . "' WHERE order_id = '" . (int)$order_id . "'");
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_sender_address_pick_up = '" . $this->db->escape($senderAddressPickUp) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function setMeestExpressCnSenderAddressPickUp($order_id, $pickup)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_sender_address_pick_up = '" . (int)$pickup . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function getMeestExpressCnUuid($order_id)
    {
        $query = $this->db->query("SELECT meest_express_cn_uuid FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
        return $query->row['meest_express_cn_uuid'] ?? '';
    }

    public function getOrders($page = 1, $sort_by = 'order_id', $order = 'ASC')
    {
        $limit = 10;
        $start = ($page - 1) * $limit;
        
        $sql = "SELECT o.*, 
                CONCAT(o.firstname, ' ', o.lastname) AS customer, 
                os.name AS status,
                o.meest_express_cn_uuid as meest_express_cn_uuid,
                o.meest_express_contractID as meest_express_contractID,
                o.meest_express_sender_address_pick_up as meest_express_sender_address_pick_up,
                o.meest_express_registerID as meest_express_registerID
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) 
                WHERE o.meest_express_cn_uuid IS NOT NULL 
                AND o.meest_express_cn_uuid != ''
                AND o.meest_express_cn_uuid != 'None'";

        $sql .= " ORDER BY o." . $this->db->escape($sort_by) . " " . ($order == 'DESC' ? 'DESC' : 'ASC');
        $sql .= " LIMIT " . (int)$start . "," . (int)$limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalOrders()
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o 
                WHERE o.meest_express_cn_uuid IS NOT NULL 
                AND o.meest_express_cn_uuid != ''
                AND o.meest_express_cn_uuid != 'None'";

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getOrderById($order_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
        return $query->row;
    }

    public function getOrdersByIds($order_ids)
    {
        if (empty($order_ids)) {
            return array();
        }

        $ids = array_map('intval', $order_ids);
        $sql = "SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id IN (" . implode(',', $ids) . ")";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function setMeestExpressRegisterID($order_id, $register_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_registerID = '" . $this->db->escape($register_id) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function unsetMeestExpressRegisterID($order_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET meest_express_registerID = '' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function saveMeestParcelData($orderId, $parcel, $contractID, $senderAddressPickUp) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_parcels` SET
            order_id = '" . (int)$orderId . "',
            uuid = '" . $this->db->escape($parcel['parcelID']) . "',
            parcel_number = '" . $this->db->escape($parcel['parcelNumber']) . "',
            barcode = '" . $this->db->escape($parcel['barCode']) . "',
            contractID =  '" . $this->db->escape($contractID) . "',
            sender_address_pick_up =  '" . $this->db->escape($senderAddressPickUp) . "'"
        );
        return $this->db->getLastId();
    }

    public function getContractIdByUuid($uuid)
    {
        $query = $this->db->query("SELECT contractID FROM `" . DB_PREFIX . "meest_express_contracts` WHERE contractID = '" . $this->db->escape($uuid) . "'");
        return $query->row['contractID'] ?? '';
    }

    // Statistics methods
    public function getBranchTotalRecordsAndLatestDate()
    {
        $query = $this->db->query("SELECT COUNT(*) as total, MAX(COALESCE(updated_at, created_at)) as latest_date FROM `" . DB_PREFIX . "meest_express_branch`");
        return $query->row;
    }

    public function getRegionsTotalRecordsAndLatestDate()
    {
        $query = $this->db->query("SELECT COUNT(*) as total, MAX(COALESCE(updated_at, creatеd_at)) as latest_date FROM `" . DB_PREFIX . "meest_express_regions`");
        return $query->row;
    }
    public function getDistrictTotalRecordsAndLatestDate() {

        $query = $this->db->query("SELECT COUNT(*) AS total_records, MAX(updated_at) AS latest_update_date FROM `" . DB_PREFIX . "meest_express_district`");

        return [
            'total_records' => $query->row['total_records'],
            'latest_update_date' => $query->row['latest_update_date']
        ];
    }
    public function getCitiesTotalRecordsAndLatestDate()
    {
        $query = $this->db->query("SELECT COUNT(*) as total, MAX(COALESCE(updated_at, creatеd_at)) as latest_date FROM `" . DB_PREFIX . "meest_express_cities`");
        return $query->row;
    }

    public function getStreetsTotalRecordsAndLatestDate()
    {
        $query = $this->db->query("SELECT COUNT(*) as total, MAX(COALESCE(updated_at, created_at)) as latest_date FROM `" . DB_PREFIX . "meest_express_streets`");
        return $query->row;
    }

    private function processBatchUpdateStreets($data)
    {
        if (!empty($data)) {
            $this->bulkUpdateStreets($data);
        }
    }

    public function getOrderShippingData($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "meest_express_order_shipping_data` WHERE order_id = '" . (int)$order_id . "'");
        return $query->row;
    }

    public function updateOrderShippingData($order_id, $data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "meest_express_order_shipping_data` SET
            order_id = '" . (int)$order_id . "',
            shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
            city_code = '" . $this->db->escape($data['city_code']) . "',
            branch_code = '" . $this->db->escape($data['branch_code']) . "',
            address_code = '" . $this->db->escape($data['address_code']) . "',
            building = '" . $this->db->escape(isset($data['building']) ? $data['building'] : '') . "',
            region_code = '" . $this->db->escape($data['region_code']) . "',
            created_at = NOW(),
            updated_at = NOW()
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
