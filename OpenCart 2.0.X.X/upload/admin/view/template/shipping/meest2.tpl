<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-8">
                    <div class="title-wrapper">
                        <h1 id="heading-title"><?php echo $heading_title; ?></h1>
                    </div>
                    <div>
                        <ul class="breadcrumb custom-breadcrumb">
                            <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-4 text-right">
                    <button type="submit" form="form-meest" class="btn btn-icon-save" title="<?php echo $button_save; ?>" data-toggle="tooltip" data-original-title="Save">
                        <i class="fa fa-save"></i>
                    </button>
                    <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default">
                        <i class="fa fa-reply"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid meest2-body">
        <?php if (isset($error_warning_meest)) { ?>
            <div class="alert alert-danger alert-dismissible"><i
                        class="fa fa-exclamation-circle"></i> <?php echo $error_warning_meest; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>
        <?php if ($error_warning) { ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>
        <div class="meest2-page-header">
            <h1><?php echo $text_edit; ?></h1>
        </div>
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-setting" data-toggle="tab"><?php echo $tab_setting; ?></a></li>
            <li><a href="#tab-database" data-toggle="tab"><?php echo $tab_database; ?></a></li>
            <li><a href="#tab-sender" data-toggle="tab"><?php echo $tab_sender; ?></a></li>
            <li><a href="#tab-contract" data-toggle="tab"><?php echo $tab_contract; ?></a></li>
            <li><a href="#tab-contact" data-toggle="tab"><?php echo $tab_contact; ?></a></li>
            <li><a href="#tab-help" data-toggle="tab"><?php echo $tab_help; ?></a></li>
        </ul>
        <div class="panel panel-default">
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-meest"
                      class="form-horizontal">
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-setting">
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label"
                                       for="input-tax-class"><?php echo $entry_tax_class; ?></label>
                                <div class="col-sm-10">
                                    <select name="meest2_tax_class_id" id="input-tax-class" class="form-control">
                                        <option value="0"><?php echo $text_none; ?></option>
                                        <?php foreach ($tax_classes as $tax_class) { ?>
                                            <?php if ($tax_class['tax_class_id'] == $meest2_tax_class_id) { ?>
                                                <option value="<?php echo $tax_class['tax_class_id']; ?>"
                                                        selected="selected"><?php echo $tax_class['title']; ?></option>
                                            <?php } else { ?>
                                                <option value="<?php echo $tax_class['tax_class_id']; ?>"><?php echo $tax_class['title']; ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-form-label"
                                       for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
                                <div class="col-sm-10">
                                    <select name="meest2_geo_zone_id" id="input-geo-zone" class="form-control">
                                        <option value="0"><?php echo $text_all_zones; ?></option>
                                        <?php foreach ($geo_zones as $geo_zone) { ?>
                                            <?php if ($geo_zone['geo_zone_id'] == $meest2_geo_zone_id) { ?>
                                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"
                                                        selected="selected"><?php echo $geo_zone['name']; ?></option>
                                            <?php } else { ?>
                                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-form-label"><?php echo $entry_service; ?></label>
                                <div class="col-sm-10">
                                    <div class="delivery-method-box p-4">
                                        <?php foreach ($services as $service) { ?>
                                            <div class="form-row align-items-center delivery-method-line mb-3">
                                                <div class="col-md-6 d-flex align-items-center gap-2">
                                                    <?php if (in_array($service['value'], $meest2_service)) { ?>
                                                        <input type="checkbox" name="meest2_service[]"
                                                               value="<?php echo $service['value']; ?>" class="form-check-input styled-checkbox" checked="checked"/>
                                                        <label class="meest2-label"><?php echo $service['text']; ?></label>
                                                    <?php } else { ?>
                                                        <input type="checkbox" name="meest2_service[]"
                                                               value="<?php echo $service['value']; ?>" class="form-check-input styled-checkbox"/>
                                                        <label class="meest2-label"><?php echo $service['text']; ?></label>
                                                    <?php } ?>
                                                </div>
                                                <label class="meest2-div-text"><?php echo $entry_cost; ?></label>
                                                <div class="meest2-div-input">
                                                    <input type="text" name="meest2_cost[<?php echo $service['value']; ?>]"
                                                           value="<?php echo isset($meest2_cost[$service['value']])?$meest2_cost[$service['value']]:''; ?>"
                                                           class="form-control meest2-input-cost" placeholder="<?php echo $entry_cost; ?>">
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="meest2-select-unselect">
                                        <button type="button" onclick="$(this).closest('.form-group').find(':checkbox').prop('checked', true);" class="btn btn-link p-0"><?php echo $text_select_all; ?></button> /
                                        <button type="button" onclick="$(this).closest('.form-group').find(':checkbox').prop('checked', false);" class="btn btn-link p-0"><?php echo $text_unselect_all; ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="input-calculation-in-checkout">
                                    <?php echo $calculation_in_checkout; ?>
                                </label>
                                <div class="col-sm-10">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                id="input-calculation-in-checkout"
                                                name="meest2_calculation_in_checkout"
                                                value="1"
                                                id="input-calculation-in-checkout"
                                                class="custom-control-input"
                                        <?php echo ($meest2_calculation_in_checkout) ? 'checked="checked"' : ''; ?>
                                        />
                                        <label class="custom-control-label" for="input-calculation-in-checkout"></label>
                                        <div class="meest2-info-box">
                                            <div class="meest2-calculate-info"><?php echo $calculation_in_checkout_info; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-form-label"
                                       for="input-status"><?php echo $entry_status; ?></label>
                                <div class="col-sm-10">
                                    <select name="meest2_status" id="input-status" class="form-control">
                                        <?php if ($meest2_status) { ?>
                                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                        <option value="0"><?php echo $text_disabled; ?></option>
                                        <?php } else { ?>
                                        <option value="1"><?php echo $text_enabled; ?></option>
                                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-form-label"
                                       for="input-auth-mode"><?php echo $entry_auth_mode; ?></label>
                                <div class="col-sm-10">
                                    <select name="meest2_auth_mode" id="input-auth-mode" class="form-control">
                                        <option value="default"
                                        <?php if ($meest2_auth_mode == 'default') { ?>
                                        selected="selected" <?php } ?>>Default (Login/Password)
                                        </option>
                                        <option value="api_key"
                                        <?php if ($meest2_auth_mode == 'api_key') { ?>
                                        selected="selected" <?php } ?>>API Key
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div id="auth-default" class="form-group"
                            <?php if ($meest2_auth_mode != 'default') { ?>
                            style="display: none;" <?php } ?>>
                            <label class="col-sm-2 col-form-label" for="input-login"><?php echo $entry_login; ?></label>
                            <div class="col-sm-10 mb-3">
                                <input type="text" name="meest2_login"
                                       value="<?php echo $meest2_login; ?>"
                                       placeholder="<?php echo $entry_login; ?>" id="input-login" class="form-control" style="margin-bottom: 16px;"/>
                            </div>
                            <label class="col-sm-2 col-form-label"
                                   for="input-password"><?php echo $entry_password; ?></label>
                                <div class="col-sm-10 mb-3">
                                    <input type="password" name="meest2_password"
                                           value="<?php echo $meest2_password; ?>"
                                           placeholder="<?php echo $entry_password; ?>"
                                           id="input-password" class="form-control"/>
                                </div>
                        </div>
                        <div id="auth-api-key" class="form-group"
                        <?php if ($meest2_auth_mode != 'api_key') { ?>
                        style="display: none;" <?php } ?>>
                        <label class="col-sm-2 col-form-label" for="input-api-key"><?php echo $entry_api_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="meest2_api_key"
                                   value="<?php echo $meest2_api_key; ?>"
                                   placeholder="<?php echo $entry_api_key; ?>"
                                   id="input-api-key" class="form-control"/>
                            <div class="meest2-info-box">
                                <div class="meest2-info-title">Для отримання ключа (token) виконайте наступні кроки:</div>
                                <ul class="meest2-info-list">
                                    <li>Зареєструйтесь в Особистому кабінеті за посиланням
                                        <a href="https://24.meest.com" target="_blank">https://24.meest.com</a>.
                                    </li>
                                    <li>Натисніть в боковому меню на кнопку Налаштування.</li>
                                    <li>Перейдіть на вкладку API інтеграції.</li>
                                    <li>Натисніть кнопку Укласти договір і в результаті Ви отримаєте token.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 col-form-label"
                               for="input-sort-order"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="meest2_sort_order"
                                   value="<?php echo $meest2_sort_order; ?>"
                                   placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order"
                                   class="form-control"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 col-form-label"
                               for="input-free-shipping-enabled"><?php echo $entry_free_shipping_enabled; ?></label>
                        <div class="col-sm-10">
                            <select name="meest2_free_shipping_enabled" id="input-free-shipping-enabled" class="form-control">
                                <?php if ($meest2_free_shipping_enabled) { ?>
                                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                    <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                    <option value="1"><?php echo $text_enabled; ?></option>
                                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 col-form-label"
                               for="input-free-shipping-threshold"><?php echo $entry_free_shipping_threshold; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="meest2_free_shipping_threshold"
                                   value="<?php echo $meest2_free_shipping_threshold; ?>"
                                   placeholder="<?php echo $entry_free_shipping_threshold; ?>" id="input-free-shipping-threshold"
                                   class="form-control"/>
                        </div>
                    </div>
            </div>
            <div class="tab-pane" id="tab-database">
                <div class="custom-table-wrapper">
                    <div class="custom-table-row header">
                        <div><?php echo $entry_type_of_data; ?></div>
                        <div><?php echo $entry_last_updated; ?></div>
                        <div><?php echo $entry_amount; ?></div>
                        <div><?php echo $entry_description; ?></div>
                        <div><?php echo $entry_action; ?></div>
                    </div>
                    <div class="custom-table-row">
                        <div>Region</div>
                        <div><?php echo $regions_import_info['latest_update_date']; ?></div>
                        <div><?php echo $regions_import_info['total_records']; ?></div>
                        <div>It will be updated regions list of the postal company. The action will not affect the
                            standard regions
                        </div>
                        <div>
                            <a onclick="updateRegions()" id="button-update_region" data-toggle="tooltip" title="Refresh" class="btn btn-icon">
                                <i class="fa fa-refresh"></i>
                            </a>
                        </div>
                    </div>

                    <div class="custom-table-row">
                        <div>City</div>
                        <div><?php echo $cities_import_info['latest_update_date']; ?></div>
                        <div><?php echo $cities_import_info['total_records']; ?></div>
                        <div>It will be updated cities in which can be delivered by the postal company</div>
                        <div><a onclick="updateCities()" id="button-update_cities" data-toggle="tooltip" title="Refresh" class="btn btn-icon">
                                <i class="fa fa-refresh"></i>
                            </a>
                        </div>
                    </div>
                    <div class="custom-table-row">
                        <div>Branch</div>
                        <div><?php echo $branch_import_info['latest_update_date']; ?></div>
                        <div><?php echo $branch_import_info['total_records']; ?></div>
                        <div>It will be updated branches of the postal company</div>
                        <div>
                            <a id="button-update_branches" class="btn btn-icon" data-toggle="tooltip" title="Update Branch Database"
                               onclick="return confirm('This operation may take three hours. Are you impressed?') ? updateBranches() : false;">
                                <i class="fa fa-refresh"></i>
                            </a>
                        </div>
                    </div>
                    <div class="custom-table-row">
                        <div>Streets</div>
                        <div><?php echo $streets_import_info['latest_update_date']; ?></div>
                        <div><?php echo $streets_import_info['total_records']; ?></div>
                        <div>Enable/Disable online street search for address delivery</div>
                        <div><a onclick="updateStreets()" id="button-update_address" data-toggle="tooltip" title="Refresh" class="btn btn-icon">
                                <i class="fa fa-refresh"></i>
                            </a>
                        </div>
                    </div>
                </div>


                <div class="custom-table">
                    <div class="alert alert-info" style="white-space: nowrap; overflow: auto; margin-bottom: 0;">
                        <strong style="margin-right: 6px;">Cron command:</strong>
                        <code style="white-space: nowrap;"><?php echo $cron_command; ?></code>
                    </div>
                </div>


            </div>
            <div id="custom-loader" style="display: none;">
                <div class="loader-background"></div>
                <div class="loader-content">
                    <div class="spinner"></div>
                    <p id="loader-text">Loading...</p>
                </div>
            </div>
            <div class="tab-pane" id="tab-sender">
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-sender-contract-id"><?php echo $entry_contract_id; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_contract_id" id="input-sender-contract-id"
                                class="form-control">
                            <option value="">select contract</option>
                            <?php foreach ($contracts as $contract) { ?>
                                <option value="<?php echo $contract['contractID']; ?>"
                                    <?php if ($contract['contractID'] == $meest2_sender_contract_id) { ?>
                                    selected<?php } ?>>
                                    <?php echo $contract['contractID']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-sender-contact-person"><?php echo $entry_sender_contact_person; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_contact_person" id="input-sender-contact-person"
                                class="form-control">
                            <option value=""><?php echo $text_select; ?></option>
                            <?php foreach ($contacts as $contact) { ?>
                                <option value="<?php echo $contact['id']; ?>"
                                    <?php if ($contact['id'] == $meest2_sender_contact_person) { ?>selected<?php } ?>>
                                        <?php echo $contact['firstname']; ?> <?php echo $contact['lastname']; ?> <?php echo $contact['middlename']; ?>
                                        - <?php echo $contact['phone']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-sender-region"><?php echo $entry_sender_region; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_region" id="input-sender-region" class="form-control">
                            <option value=""><?php echo $text_select; ?></option>
                            <?php foreach ($regions as $region) { ?>
                                <option value="<?php echo $region['region_id']; ?>"
                                    <?php if ($region['region_id'] == $meest2_sender_region) { ?>selected<?php } ?>>
                                        <?php echo $region['region_name_ua']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-sender-city"><?php echo $entry_sender_city; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_city" id="input-sender-city" class="form-control">
                            <option value=""><?php echo $text_select; ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-sender-address"><?php echo $entry_sender_street; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_address" id="input-sender-address" class="form-control">
                            <option value=""><?php echo $text_select; ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-sender-branch"><?php echo $entry_branch; ?></label>
                    <div class="col-sm-10">
                        <select name="meest2_sender_branch" id="input-sender-branch" class="form-control">
                            <option value=""><?php echo $text_select; ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="tab-contract">
                <div class="form-group">
                    <label class="col-sm-2 col-form-label"
                           for="input-contract-id"><?php echo $text_contract_id; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="contract_id" id="input-contract-id" class="form-control"
                               placeholder="<?php echo $text_enter_contract_id; ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="button" id="button-add-contract"
                                class="btn btn-primary"><?php echo $button_add_contract; ?></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-contracts-table">
                        <thead>
                        <tr>
                            <th><?php echo $column_contract_id; ?></th>
                            <th><?php echo $column_date_created; ?></th>
                            <th><?php echo $column_date_updated; ?></th>
                            <th><?php echo $column_action; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($contracts) { ?>
                            <?php foreach ($contracts as $contract) { ?>
                                <tr>
                                    <td><?php echo $contract['contractID']; ?></td>
                                    <td><?php echo $contract['creatеd_at']; ?></td>
                                    <td><?php echo $contract['updated_at']; ?></td>
                                    <td>
                                        <button type="button" data-contract-id="<?php echo $contract['contractID']; ?>"
                                                class="btn-outline-blue btn-delete-contract"><?php echo $button_delete; ?></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                        <tr>
                            <td colspan="4" class="text-center"><?php echo $text_no_contracts; ?></td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane" id="tab-contact">
                <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-phone"><?php echo $text_phone; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="phone" id="input-phone" class="form-control"
                               placeholder="<?php echo $text_enter_phone; ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-firstname"><?php echo $text_firstname; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="firstname" id="input-firstname" class="form-control"
                               placeholder="<?php echo $text_enter_firstname; ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-lastname"><?php echo $text_lastname; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="lastname" id="input-lastname" class="form-control"
                               placeholder="<?php echo $text_enter_lastname; ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-middlename"><?php echo $text_middlename; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="middlename" id="input-middlename" class="form-control"
                               placeholder="<?php echo $text_enter_middlename; ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="button" id="button-add-contact"
                                class="btn btn-primary"><?php echo $button_add_contact; ?></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="custom-contracts-table">
                        <thead>
                        <tr>
                            <th><?php echo $column_phone; ?></th>
                            <th><?php echo $column_firstname; ?></th>
                            <th><?php echo $column_lastname; ?></th>
                            <th><?php echo $column_middlename; ?></th>
                            <th><?php echo $column_action; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($contacts) { ?>
                            <?php foreach ($contacts as $contact) { ?>
                                <tr>
                                    <td><?php echo $contact['phone']; ?></td>
                                    <td><?php echo $contact['firstname']; ?></td>
                                    <td><?php echo $contact['lastname']; ?></td>
                                    <td><?php echo $contact['middlename']; ?></td>
                                    <td>
                                        <button type="button" data-contact-id="<?php echo $contact['id']; ?>"
                                                class="btn-outline-blue btn-delete-contract"><?php echo $button_delete; ?></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center">no contacts</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane" id="tab-help">
                <div><?php echo $help_text_meest; ?></div>
                <div><?php echo $help_text_about; ?></div>
            </div>
        </div>
        </form>
    </div>
</div>
</div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    window.meest2Urls = {
        importBranches: "<?php echo $importBranches; ?>",
        importRegions: "<?php echo $importRegions; ?>",
        importCity: "<?php echo $importCity; ?>",
        importStreets: "<?php echo $importStreets; ?>",
        addContract: "<?php echo $addContract; ?>",
        deleteContract: "<?php echo $deleteContract; ?>",
        addContact: "<?php echo $addContact; ?>",
        deleteContact: "<?php echo $deleteContact; ?>",
        ajax_get_cities_url: "<?php echo $ajax_get_cities_url; ?>",
        ajax_get_addresses_url: "<?php echo $ajax_get_addresses_url; ?>",
        ajax_get_branches_url: "<?php echo $ajax_get_branches_url; ?>",
        shipping_meest2_sender_region: "<?php echo $meest2_sender_region; ?>",
        shipping_meest2_sender_city: "<?php echo $meest2_sender_city; ?>",
        shipping_meest2_sender_address: "<?php echo $meest2_sender_address; ?>",
        shipping_meest2_sender_branch: "<?php echo $meest2_sender_branch; ?>",
        text_update_success: "<?php echo $text_update_success; ?>",
        text_select: "<?php echo $text_select; ?>",
        text_error_contract_id: "<?php echo $text_error_contract_id; ?>",
        text_error_fill_fields: "<?php echo $text_error_fill_fields; ?>",
        text_confirm_delete: "<?php echo $text_confirm_delete; ?>"
    };
</script>

<?php echo $footer; ?>
