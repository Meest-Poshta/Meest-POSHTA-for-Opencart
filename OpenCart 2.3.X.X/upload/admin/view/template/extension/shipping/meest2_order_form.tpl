<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="row">
                <h1 class="meest-heading"><?php echo $heading_title; ?>  <?php echo $text_create_cn; ?></h1>
                <div class="pull-right">
                    <button type="submit" form="form-meest-cn" id="button-save-cn"><?php echo $button_save_cn; ?></button>
                </div>
            </div>
            <div class="row">
                <ul class="breadcrumb">
                    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-meest-cn" class="form-horizontal">
            <?php if ($error_warning) { ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            </div>
            <?php } ?>
            <input type="hidden" name="sender_delivery_type" id="sender_delivery_type" value="branch">
            <input type="hidden" name="recipient_delivery_type" id="recipient_delivery_type" value="branch">
            <input type="hidden" name="order_number" value="<?php echo $order_id; ?>" id="input-order_number"/>
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title"><?php echo $text_sender; ?></h3>
                        <div class="btn-group pull-right" data-toggle="buttons">
                            <label class="btn btn-default btn-sm active" data-toggle="tooltip" title="">
                                <input type="radio" name="sender_address_type" value="branch">
                                <span class="btn-text"><?php echo $text_branch; ?></span>
                            </label>
                            <label class="btn btn-default btn-sm"
                                   data-toggle="tooltip"
                                   title=""
                                <?php if ($meest2_sender_address_pick_up != 1) { ?> style="display: none;" <?php } ?>
                            >
                                <input type="radio" name="sender_address_type" value="doors">
                                <span class="btn-text"><?php echo $text_doors; ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-body" id="sender_branch_delivery">
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-contact-person"><?php echo $entry_sender_contact_person; ?></label>
                                <div class="col-sm-9">
                                    <select name="meest2_sender_contact_person" id="input-sender-contact-person" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($contacts as $contact) { ?>
                                        <option value="<?php echo $contact['id']; ?>" <?php if ($contact['id'] == $meest2_sender_contact_person) { ?>selected<?php } ?>>
                                        <?php echo $contact['firstname']; ?> <?php echo $contact['lastname']; ?> <?php echo $contact['middlename']; ?> - <?php echo $contact['phone']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-region"><?php echo $entry_sender_region; ?></label>
                                <div class="col-sm-9">
                                    <select name="meest2_sender_region" id="input-sender-region" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($regions as $region) { ?>
                                        <option value="<?php echo $region['region_id']; ?>" <?php if ($region['region_id'] == $meest2_sender_region) { ?>selected<?php } ?>>
                                        <?php echo $region['region_name_ua']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-city"><?php echo $entry_sender_city; ?></label>
                                <div class="col-sm-9">
                                    <select name="meest2_sender_city" id="input-sender-city" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-branch"><?php echo $entry_branch; ?></label>
                                <div class="col-sm-9">
                                    <select name="sender_branch" id="input-sender-branch" class="form-control selectpicker show-tick custom-select2" data-live-search="true">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body" id="sender_address_delivery" style="display: none;">
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-contact-person"><?php echo $entry_sender_contact_person; ?></label>
                                <div class="col-sm-9">
                                    <select name="meest2_sender_contact_person" id="input-sender-contact-person" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($contacts as $contact) { ?>
                                        <option value="<?php echo $contact['id']; ?>" <?php if ($contact['id'] == $meest2_sender_contact_person) { ?>selected<?php } ?>>
                                        <?php echo $contact['firstname']; ?> <?php echo $contact['lastname']; ?> <?php echo $contact['middlename']; ?> - <?php echo $contact['phone']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-region-address"><?php echo $entry_sender_region; ?></label>
                                <div class="col-sm-9">
                                    <select name="sender-region-address" id="input-sender-region-address" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($regions as $region) { ?>
                                        <option value="<?php echo $region['region_id']; ?>" <?php if ($region['region_id'] == $meest2_sender_region) { ?>selected<?php } ?>>
                                        <?php echo $region['region_name_ua']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-city-address"><?php echo $entry_sender_city; ?></label>
                                <div class="col-sm-9">
                                    <select name="sender_city_address" id="input-sender-city-address" class="form-control select2 custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-address"><?php echo $text_address; ?></label>
                                <div class="col-sm-9">
                                    <select name="sender_address" id="input-sender-address" class="form-control select2 custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-building"><?php echo $text_building; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="sender_building" id="input-sender-building" class="custom-input-70" placeholder="<?php echo $text_building; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-floor"><?php echo $text_floor; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="sender_floor" id="input-sender-floor" class="custom-input-70" placeholder="<?php echo $text_floor; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-sender-apartment"><?php echo $text_apartment; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="sender_apartment" id="input-sender-apartment" class="custom-input-70" placeholder="<?php echo $text_apartment; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title"><?php echo $text_recipient; ?></h3>
                        <div class="btn-group pull-right" data-toggle="buttons">
                            <label class="btn btn-default btn-sm active" data-toggle="tooltip" title="">
                                <input type="radio" name="recipient_address_type" value="branch">
                                <span class="btn-text"><?php echo $text_branch; ?></span>
                            </label>
                            <label class="btn btn-default btn-sm"
                                   data-toggle="tooltip"
                                   title="">
                                <input type="radio" name="recipient_address_type" value="doors">
                                <span class="btn-text"><?php echo $text_doors; ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <div class="recipient-info-container">
                                    <h4 class="recipient-info-heading">
                                        <?php echo $recipient_shipping_address_data; ?>
                                    </h4>
                                    <p class="recipient-info-alert">
                                        <?php echo $recipient_info_to_form; ?>
                                    </p>

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong><?php echo $text_method; ?>:</strong> <?php echo $shipping_method; ?>
                                        </div>
                                        <div class="col-sm-4">
                                            <strong><?php echo $text_address; ?>:</strong> <?php echo $shipping_address_1; ?>
                                        </div>
                                        <div class="col-sm-4">
                                            <strong><?php echo $entry_region; ?>:</strong> <?php echo $shipping_zone; ?>
                                        </div>
                                        <div class="col-sm-4">
                                            <strong><?php echo $text_country; ?>:</strong><?php echo $shipping_country; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body" id="recipient_branch_delivery">
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_contact_person"><?php echo $entry_contact_person; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_contact_person" value="<?php echo $recipient_contact_person; ?>" placeholder="<?php echo $entry_contact_person; ?>" id="input-recipient_contact_person" class="custom-input-70" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_contact_person_phone"><?php echo $entry_phone; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_contact_person_phone" value="<?php echo $recipient_contact_person_phone; ?>" placeholder="<?php echo $entry_phone; ?>" id="input-recipient_contact_person_phone" class="custom-input-70" />
                                </div>
                            </div>
                            <?php /* Закоментовано поле регіону */ ?>
                            <?php /* <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_region"><?php echo $entry_region; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_region" id="input-recipient_region" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($regions as $region) { ?>
                                        <option value="<?php echo $region['region_id']; ?>">
                                            <?php echo $region['region_name_ua']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div> */ ?>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_city"><?php echo $entry_city; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_city" id="input-recipient_city" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_branch" data-toggle="tooltip" data-placement="right" title="<?php echo $recipient_branch_name; ?>"><?php echo $entry_branch; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_branch" id="input-recipient_branch" class="form-control selectpicker show-tick custom-select2" data-live-search="true">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body" id="recipient_address_delivery" style="display: none;">
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_contact_person_address"><?php echo $entry_contact_person; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_contact_person_address" value="<?php echo $recipient_contact_person; ?>" placeholder="<?php echo $entry_contact_person; ?>" id="input-recipient_contact_person_address" class="custom-input-70" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_contact_person_phone_address"><?php echo $entry_phone; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_contact_person_phone_address" value="<?php echo $recipient_contact_person_phone; ?>" placeholder="<?php echo $entry_phone; ?>" id="input-recipient_contact_person_phone_address" class="custom-input-70" />
                                </div>
                            </div>
                            <?php /* Закоментовано поле регіону */ ?>
                            <?php /* <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_region_address"><?php echo $entry_region; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_region_address" id="input-recipient_region_address" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php foreach ($regions as $region) { ?>
                                        <option value="<?php echo $region['region_id']; ?>">
                                            <?php echo $region['region_name_ua']; ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
            </div> */ ?>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient_city_address"><?php echo $entry_city; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_city_address" id="input-recipient_city_address" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient-address"><?php echo $text_address; ?></label>
                                <div class="col-sm-9">
                                    <select name="recipient_address" id="input-recipient-address" class="form-control custom-select2">
                                        <option value=""><?php echo $text_select; ?></option>
                                        <?php if (!empty($order_shipping_data['address_code'])) { ?>
                                        <option value="<?php echo $order_shipping_data['address_code']; ?>" selected><?php echo isset($order_shipping_data['address_name']) ? $order_shipping_data['address_name'] : $order_shipping_data['address_code']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient-building-address">Будинок/Квартира</label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_building_address" id="input-recipient-building-address" class="custom-input-70" placeholder="Будинок/Квартира" value="<?php echo isset($order_shipping_data['building']) ? $order_shipping_data['building'] : ''; ?>">
                                </div>
                            </div>
                            <?php /* Приховані поля для поверху та квартири
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient-floor-address"><?php echo $text_floor; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_floor_address" id="input-recipient-floor-address" class="custom-input-70" placeholder="<?php echo $text_floor; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-recipient-apartment-address"><?php echo $text_apartment; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" name="recipient_apartment_address" id="input-recipient-apartment-address" class="custom-input-70" placeholder="<?php echo $text_apartment; ?>">
                                </div>
                            </div>
                            */ ?>
                        </div>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title"><?php echo $text_departure_options; ?></h3>
                    </div>
                    <div class="panel panel-default">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <div class="departure-info-container">
                                    <h4 class="departure-info-heading">
                                        <?php echo $recipient_product_information; ?>
                                    </h4>
                                    <p class="departure-info-paragraph">
                                        <?php echo $recipient_product_detailed_information; ?>
                                    </p>
                                    <table class="table table-bordered table-no-margin">
                                        <thead>
                                        <tr>
                                            <th><?php echo $text_name; ?></th>
                                            <th><?php echo $entry_weight; ?></th>
                                            <th><?php echo $text_dimensions; ?></th>
                                            <th><?php echo $entry_quantity; ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($order_products as $product) { ?>
                                        <tr>
                                            <td><?php echo $product['name']; ?></td>
                                            <td><?php echo sprintf('%.2f', (float)$product['weight']); ?></td>
                                            <td>
                                                <?php echo sprintf('%.2f', (float)$product['length']); ?> x
                                                <?php echo sprintf('%.2f', (float)$product['width']); ?> x
                                                <?php echo sprintf('%.2f', (float)$product['height']); ?>
                                            </td>
                                            <td><?php echo $product['quantity']; ?></td>

                                        </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div id="places-container">
                                <div class="place-item" data-index="0">
                                    <legend><?php echo $text_seat; ?> 1</legend>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_weight; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="0.01" name="places[0][weight]" value="" placeholder="<?php echo $entry_weight; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_kg; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_length; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="0.01" name="places[0][length]" value="" placeholder="<?php echo $entry_length; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_cm; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_width; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="0.01" name="places[0][width]" value="" placeholder="<?php echo $entry_width; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_cm; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_height; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="0.01" name="places[0][height]" placeholder="<?php echo $entry_height; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_cm; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_quantity; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="1" name="places[0][quantity]" value="" placeholder="<?php echo $entry_quantity; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_pc; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label"><?php echo $entry_insurance; ?></label>
                                        <div class="col-sm-9">
                                            <div class="input-group input-group-70">
                                                <input type="text" step="0.01" name="places[0][insurance]" value="" placeholder="<?php echo $entry_insurance; ?>" class="custom-input-70" required/>
                                                <span class="input-group-addon addon-blue"><?php echo $text_uah; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-9 col-sm-offset-3 text-right">
                                            <button type="button" class="btn btn-danger remove-place" style="display:none;"><?php echo $text_delete_seat; ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="btn-container">
                                <button type="button" class="btn btn-success pull-right" id="add-place"><?php echo $text_add_a_seat; ?></button>
                                <div class="form-group">
                                    <div class="col-sm-9 col-sm-offset-3 text-right">
                                        <button type="button" class="btn btn-danger" id="remove-place" style="display:none;"><?php echo $text_delete_seat; ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title"><?php echo $text_payment; ?></h3>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-delivery_payer"><?php echo $entry_delivery_payer; ?></label>
                                <div class="col-sm-9">
                                    <div class="btn-group" data-toggle="buttons">
                                        <?php $first = true; ?>
                                        <?php foreach ($references['payer_types'] as $v) { ?>
                                            <?php if ($v['Ref'] == 'ThirdPerson' && !$sender_options['CanPayTheThirdPerson']) { ?>
                                                <label class="btn btn-default disabled">
                                                    <input type="radio" name="delivery_payer" value="<?php echo $v['Ref']; ?>" id="input-delivery_payer-<?php echo $v['Ref']; ?>"><?php echo $v['Description']; ?>
                                                </label>
                                            <?php } elseif ($v['Ref'] == 'Sender' && $first) { ?>
                                                <label class="btn btn-default active">
                                                    <input type="radio" name="delivery_payer" value="<?php echo $v['Ref']; ?>" id="input-delivery_payer-<?php echo $v['Ref']; ?>" checked><?php echo $v['Description']; ?>
                                                </label>
                                                <?php $first = false; ?>
                                            <?php } else { ?>
                                                <label class="btn btn-default">
                                                    <input type="radio" name="delivery_payer" value="<?php echo $v['Ref']; ?>" id="input-delivery_payer-<?php echo $v['Ref']; ?>"><?php echo $v['Description']; ?>
                                                </label>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="input-payment_type"><?php echo $entry_payment_type; ?></label>
                                <div class="col-sm-9">
                                    <div class="btn-group" data-toggle="buttons">
                                        <?php $first_payment = true; ?>
                                        <?php foreach ($references['payment_types'] as $v) { ?>
                                            <?php if ($first_payment) { ?>
                                                <label class="btn btn-default active">
                                                    <input type="radio" name="payment_type" value="<?php echo $v['Ref']; ?>" id="input-payment_type-<?php echo $v['Ref']; ?>" checked><?php echo $v['Description']; ?>
                                                </label>
                                                <?php $first_payment = false; ?>
                                            <?php } else { ?>
                                                <label class="btn btn-default">
                                                    <input type="radio" name="payment_type" value="<?php echo $v['Ref']; ?>" id="input-payment_type-<?php echo $v['Ref']; ?>"><?php echo $v['Description']; ?>
                                                </label>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" for="toggle-cod"><?php echo $text_cod; ?></label>
                                <div class="col-sm-9">
                                    <label class="switch">
                                        <input type="checkbox" id="toggle-cod" name="cod_toggle">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="cod-amount-container" style="display: none;">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label" for="input-cod-amount"><?php echo $text_cod_amount; ?></label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="text" id="input-cod-amount" name="cod_amount" placeholder="<?php echo $text_enter_cod_amount; ?>" class="custom-input-70" />
                                            <span class="input-group-addon addon-blue"><?php echo $text_uah; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group card-number-container">
                                    <label class="col-sm-3 control-label" for="input-card-number"><?php echo $text_card_number; ?></label>
                                    <div class="col-sm-9">
                                        <input type="text" id="input-card-number" name="card_number" placeholder="<?php echo $text_enter_card_number; ?>" class="custom-input-70" />
                                    </div>
                                </div>
                                <div class="form-group ownername-container">
                                    <label class="col-sm-3 control-label" for="input-ownername"><?php echo $text_ownername; ?></label>
                                    <div class="col-sm-9">
                                        <input type="text" id="input-ownername" name="ownername" placeholder="<?php echo $text_enter_ownername; ?>" class="custom-input-70" value="<?php echo $sender_person; ?>"/>
                                    </div>
                                </div>
                                <div class="form-group ownermobile-container">
                                    <label class="col-sm-3 control-label" for="input-ownermobile"><?php echo $text_ownermobile; ?></label>
                                    <div class="col-sm-9">
                                        <input type="text" id="input-ownermobile" name="ownermobile" placeholder="<?php echo $text_enter_ownermobile; ?>" class="custom-input-70" value="<?php echo $sender_phone; ?>"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel-heading clearfix">
                        <h3 class="panel-title"><?php echo $text_additionally; ?></h3>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-group parcel-number">
                                <label class="col-sm-3 control-label" for="input-parcel-number"><?php echo $text_parcel_number; ?></label>
                                <div class="col-sm-9">
                                    <input type="text" id="input-parcel-number" name="parcel_number" value="" class="custom-input-70" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var meest2OrderFormUrls = {
        ajax_get_cities_url: '<?php echo $ajax_get_cities_url; ?>',
        ajax_get_addresses_url: '<?php echo $ajax_get_addresses_url; ?>',
        ajax_get_branches_url: '<?php echo $ajax_get_branches_url; ?>',
        ajax_search_cities_url: '<?php echo $ajax_search_cities_url; ?>',
        shipping_meest2_sender_region: '<?php echo $meest2_sender_region; ?>',
        shipping_meest2_sender_city: '<?php echo $meest2_sender_city; ?>',
        shipping_meest2_sender_address: '<?php echo $meest2_sender_address; ?>',
        shipping_meest2_sender_branch: '<?php echo $meest2_sender_branch; ?>',
        shipping_meest2_recipient_city: '',
        shipping_meest2_recipient_address: '',
        order_shipping_data: <?php echo json_encode($order_shipping_data); ?>,
        text_select: '<?php echo $text_select; ?>',
        text_fill_required_fields: '<?php echo $text_fill_required_fields; ?>',
        text_seat: '<?php echo $text_seat; ?>',
        text_kg: '<?php echo $text_kg; ?>',
        text_cm: '<?php echo $text_cm; ?>',
        text_pc: '<?php echo $text_pc; ?>',
        text_uah: '<?php echo $text_uah; ?>',
        text_delete_seat: '<?php echo $text_delete_seat; ?>',
        entry_weight: '<?php echo $entry_weight; ?>',
        entry_length: '<?php echo $entry_length; ?>',
        entry_width: '<?php echo $entry_width; ?>',
        entry_height: '<?php echo $entry_height; ?>',
        entry_quantity: '<?php echo $entry_quantity; ?>',
        entry_insurance: '<?php echo $entry_insurance; ?>'
    };
</script>

<?php echo $footer; ?>
