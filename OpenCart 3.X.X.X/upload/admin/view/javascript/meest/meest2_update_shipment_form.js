$(document).ready(function() {
    $('#input-sender-city, #input-sender-address, #input-sender-city-address, #input-sender-branch, #input-recipient_city_address, #input-recipient_address, #input-recipient_city, #input-recipient-branch').select2({
        placeholder: window.meest2UpdateShipmentFormUrls.text_select,
        allowClear: true,
        width: '100%'
    });

    function populateCities(region_id, citySelect, defaultCity) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_cities_url,
            type: 'get',
            data: { region_id: region_id },
            dataType: 'json',
            success: function(data) {
                citySelect.empty();
                citySelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, city) {
                    citySelect.append('<option value="' + city.city_id + '">' + city.name_ua + '</option>');
                });
                citySelect.val(defaultCity).trigger('change');
            }
        });
    }

    function populateBranches(city_id, branchSelect, defaultBranch) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_branches_url,
            type: 'get',
            data: { city_id: city_id },
            dataType: 'json',
            success: function(data) {
                branchSelect.empty();
                branchSelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, branch) {
                    branchSelect.append('<option value="' + branch.branch_id + '">' + branch.short_name + ' (' + branch.address_more_information + ') ' + '</option>');
                });
                branchSelect.val(defaultBranch).trigger('change');
            }
        });
    }

    function populateAddresses(city_id) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_addresses_url,
            type: 'get',
            data: { city_id: city_id },
            dataType: 'json',
            success: function(data) {
                var addressSelect = $('#input-sender-address');
                addressSelect.empty();
                addressSelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, address) {
                    addressSelect.append('<option value="' + address.street_id + '">' + address.type_ua + ' ' + address.name_ua + '</option>');
                });
                addressSelect.select2({
                    placeholder: window.meest2UpdateShipmentFormUrls.text_select,
                    allowClear: true,
                    width: '100%'
                }).val(window.meest2UpdateShipmentFormUrls.parcel_info.sender.addressID).trigger('change');
            }
        });
    }

    function populateCitiesAddress(region_id) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_cities_url,
            type: 'get',
            data: { region_id: region_id },
            dataType: 'json',
            success: function(data) {
                var citySelect = $('#input-sender-city-address');
                citySelect.empty();
                citySelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, city) {
                    citySelect.append('<option value="' + city.city_id + '">' + city.name_ua + '</option>');
                });
                citySelect.val(window.meest2UpdateShipmentFormUrls.sender_city_id_address).trigger('change');
            }
        });
    }

    function populateRecipientAddresses(city_id) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_addresses_url,
            type: 'get',
            data: { city_id: city_id },
            dataType: 'json',
            success: function(data) {
                var addressSelect = $('#input-recipient-address');
                addressSelect.empty();
                addressSelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, address) {
                    addressSelect.append('<option value="' + address.street_id + '">' + address.type_ua + ' ' + address.name_ua + '</option>');
                });
                addressSelect.select2({
                    placeholder: window.meest2UpdateShipmentFormUrls.text_select,
                    allowClear: true,
                    width: '100%'
                }).val(window.meest2UpdateShipmentFormUrls.parcel_info.receiver.addressID).trigger('change');
            }
        });
    }

    function populateRecipientCitiesAddress(region_id) {
        $.ajax({
            url: window.meest2UpdateShipmentFormUrls.ajax_get_cities_url,
            type: 'get',
            data: { region_id: region_id },
            dataType: 'json',
            success: function(data) {
                var citySelect = $('#input-recipient_city_address');
                citySelect.empty();
                citySelect.append('<option value="">' + window.meest2UpdateShipmentFormUrls.text_select + '</option>');
                $.each(data, function(index, city) {
                    citySelect.append('<option value="' + city.city_id + '">' + city.name_ua + '</option>');
                });
                citySelect.val(window.meest2UpdateShipmentFormUrls.receiver_city_id_address).trigger('change');
            }
        });
    }

    $('#input-sender-region').change(function() {
        populateCities($(this).val(), $('#input-sender-city'), window.meest2UpdateShipmentFormUrls.branch_sender_info.city_id);
    });
    $('#input-sender-city').change(function() {
        populateBranches($(this).val(), $('#input-sender-branch'), window.meest2UpdateShipmentFormUrls.parcel_info.sender.branchID);
    });
    $('#input-sender-region-address').change(function() {
        populateCitiesAddress($(this).val());
    });
    $('#input-sender-city-address').change(function() {
        populateAddresses($(this).val());
    });

    $('#input-recipient_region').change(function() {
        populateCities($(this).val(), $('#input-recipient_city'), window.meest2UpdateShipmentFormUrls.branch_recipient_info.city_id);
    });
    $('#input-recipient_city').change(function() {
        populateBranches($(this).val(), $('#input-recipient-branch'), window.meest2UpdateShipmentFormUrls.parcel_info.receiver.branchID);
    });
    $('#input-recipient_region_address').change(function() {
        populateRecipientCitiesAddress($(this).val());
    });
    $('#input-recipient_city_address').change(function() {
        populateRecipientAddresses($(this).val());
    });

    if ($('#input-recipient_region').val()) {
        populateCities($('#input-recipient_region').val(), $('#input-recipient_city'), window.meest2UpdateShipmentFormUrls.branch_recipient_info.city_id);
    }
    if ($('#input-recipient_region_address').val()) {
        populateRecipientCitiesAddress($('#input-recipient_region_address').val());
    }
    if ($('#input-sender-region').val()) {
        populateCities($('#input-sender-region').val(), $('#input-sender-city'), window.meest2UpdateShipmentFormUrls.branch_sender_info.city_id);
    }
    if ($('#input-sender-region-address').val()) {
        populateCitiesAddress($('#input-sender-region-address').val());
    }
    if ($('#input-sender-city').val()) {
        populateBranches($('#input-sender-city').val(), $('#input-sender-branch'), window.meest2UpdateShipmentFormUrls.parcel_info.sender.branchID);
    }
    if ($('#input-sender-city-address').val()) {
        populateAddresses($('#input-sender-city-address').val());
    }
    if ($('#input-recipient_city').val()) {
        populateBranches($('#input-recipient_city').val(), $('#input-recipient-branch'), window.meest2UpdateShipmentFormUrls.parcel_info.receiver.branchID);
    }
    if ($('#input-recipient_city_address').val()) {
        populateRecipientAddresses($('#input-recipient_city_address').val());
    }

    var codValue = window.meest2UpdateShipmentFormUrls.parcel_info.COD;
    if (codValue && codValue != '0') {
        $('#toggle-cod').prop('checked', true);
        $('#input-cod-amount').val(codValue);
        $('#input-card-number').val(window.meest2UpdateShipmentFormUrls.parcel_info.cardForCODNumber);
        $('#input-ownername').val(window.meest2UpdateShipmentFormUrls.parcel_info.cardForCODOwnername);
        $('#input-ownermobile').val(window.meest2UpdateShipmentFormUrls.parcel_info.cardForCODOwnermobile || '');
        $('.cod-amount-container').show();
    }
    $('#toggle-cod').change(function() {
        if ($(this).is(':checked')) {
            $('.cod-amount-container').slideDown();
        } else {
            $('.cod-amount-container').slideUp();
            $('#input-cod-amount').val('');
        }
    });

    $('input[name="recipient_address_type"]').change(function() {
        var selectedType = $(this).val();
        $('#recipient_delivery_type').val(selectedType);
        $('#recipient_department_delivery').hide();
        $('#recipient_address_delivery').hide();
        if (selectedType === 'department') {
            $('#recipient_department_delivery').show();
        } else if (selectedType === 'doors') {
            $('#recipient_address_delivery').show();
        }
    });

    $('input[name="sender_address_type"]').change(function() {
        var selectedType = $(this).val();
        $('#sender_delivery_type').val(selectedType);
        $('#sender_department_delivery').hide();
        $('#sender_address_delivery').hide();
        if (selectedType === 'department') {
            $('#sender_department_delivery').show();
        } else if (selectedType === 'doors') {
            $('#sender_address_delivery').show();
        }
    });

    function validateForm() {
        let isValid = true;
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        $('input[type="text"]:visible, select:visible').each(function() {
            const value = $(this).val();
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback" style="color: red;">Обов\'язкове поле</div>');
                isValid = false;
            }
        });

        if ($('input[name="sender_address_type"]:checked').val() === 'doors' && $('#input-sender-address:visible').val().trim() === '') {
            $('#input-sender-address').addClass('is-invalid');
            $('#input-sender-address').after('<div class="invalid-feedback" style="color: red;">Обов\'язкове поле</div>');
            isValid = false;
        }

        return isValid;
    }

    $('#form-meest-cn').on('submit', function(event) {
        let isValid = true;

        $('#form-meest-cn input, #form-meest-cn select').each(function() {
            const skipIds = [
                'input-parcel-number',
                'input-recipient-building-address',
                'input-recipient-floor-address',
                'input-recipient-apartment-address',
                'input-sender-building',
                'input-sender-floor',
                'input-sender-apartment',
                'input-card-number',
                'input-ownername',
                'input-ownermobile'
            ];

            if (skipIds.includes(this.id)) {
                return;
            }

            if ($(this).is(':visible') && ($(this).val() === '' || $(this).val() === null)) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }

        });

        if (!isValid) {
            event.preventDefault();
            alert(window.meest2UpdateShipmentFormUrls.text_fill_required_fields);
        }
        // event.preventDefault();
        // event.stopPropagation();

        // if (!validateForm()) {
        //     alert(window.meest2UpdateShipmentFormUrls.text_fill_required_fields);
        // } else {
        //     this.submit();
        // }
    });

    $('input[type="text"], select').on('input change', function() {
        const value = $(this).val();
        if (value && value.trim() !== '') {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    let placeIndex = window.meest2UpdateShipmentFormUrls.place_index;

    document.getElementById('add-place').addEventListener('click', function() {
        const container = document.getElementById('places-container');
        const newPlace = document.createElement('div');
        newPlace.classList.add('place-item');
        newPlace.dataset.index = placeIndex;
        newPlace.innerHTML = `
            <legend class="seat-legend">${window.meest2UpdateShipmentFormUrls.text_seat}</legend>
            <div class="form-group">
                <label class="col-sm-3 control-label">${window.meest2UpdateShipmentFormUrls.entry_weight}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][weight]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_weight}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_kg}</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">${window.meest2UpdateShipmentFormUrls.entry_length}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][length]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_length}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_cm}</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">${window.meest2UpdateShipmentFormUrls.entry_width}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][width]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_width}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_cm}</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">${window.meest2UpdateShipmentFormUrls.entry_height}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][height]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_height}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_cm}</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">${window.meest2UpdateShipmentFormUrls.entry_quantity}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][quantity]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_quantity}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_pc}</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label text-left">${window.meest2UpdateShipmentFormUrls.entry_insurance}</label>
                <div class="col-sm-9 text-right">
                    <div class="input-group input-group-70">
                        <input type="text" name="places[${placeIndex}][insurance]" value="" placeholder="${window.meest2UpdateShipmentFormUrls.entry_insurance}" class="form-control custom-input-70"/>
                        <span class="input-group-addon addon-blue">${window.meest2UpdateShipmentFormUrls.text_uah}</span>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-danger remove-place pull-right">${window.meest2UpdateShipmentFormUrls.text_delete_seat}</button>
        `;
        container.appendChild(newPlace);
        placeIndex++;
        document.querySelectorAll('.remove-place').forEach(button => {
            button.style.display = 'inline-block';
        });
    });

    document.getElementById('places-container').addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-place')) {
            const placeItem = event.target.closest('.place-item');
            placeItem.remove();
            const items = this.querySelectorAll('.place-item');
            items.forEach((item, index) => {
                item.dataset.index = index;
                item.querySelector('legend').textContent = `${window.meest2UpdateShipmentFormUrls.text_seat} ${index + 1}`;
                item.querySelectorAll('input').forEach(input => {
                    const nameMatch = input.name.match(/places\[\d+\](\[.*\])/);
                    if (nameMatch) {
                        input.name = `places[${index}]${nameMatch[1]}`;
                    }
                });
            });
            if (this.children.length === 1) {
                this.querySelector('.remove-place').style.display = 'none';
            }
        }
    });
});
