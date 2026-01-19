
    // Автоматичне підставлення даних з order_shipping_data
    if (config.order_shipping_data && config.order_shipping_data.city_code) {
        var shippingMethod = config.order_shipping_data.shipping_method;
        var cityCode = config.order_shipping_data.city_code;
        var cityName = config.order_shipping_data.city_name || cityCode;
        var branchCode = config.order_shipping_data.branch_code;
        var addressCode = config.order_shipping_data.address_code;

        console.log('Order Shipping Data:', config.order_shipping_data);
        console.log('Shipping Method:', shippingMethod);
        console.log('Address Code:', addressCode);
        console.log('Branch Code:', branchCode);

        // Визначаємо тип доставки (branch або doors)
        if (shippingMethod === 'meest2.warehouse' || shippingMethod === 'meest2.postomat' || shippingMethod === 'meest2.branch') {
            var $branchRadio = $('input[name="recipient_address_type"][value="branch"]');
            $branchRadio.prop('checked', true);
            $branchRadio.parent('label').addClass('active');
            $('input[name="recipient_address_type"][value="doors"]').parent('label').removeClass('active');
            $branchRadio.trigger('change');

            if (cityCode) {
                var $citySelect = $('#input-recipient_city');
                var newOption = new Option(cityName, cityCode, true, true);
                $citySelect.append(newOption);
                if (branchCode) {
                    $citySelect.data('preselect-branch', branchCode);
                }
                $citySelect.trigger('change');
            }
        } else if (shippingMethod === 'meest2.courier' || shippingMethod === 'meest2.door') {
            var $doorsRadio = $('input[name="recipient_address_type"][value="doors"]');
            $doorsRadio.prop('checked', true);
            $doorsRadio.parent('label').addClass('active');
            $('input[name="recipient_address_type"][value="branch"]').parent('label').removeClass('active');
            $doorsRadio.trigger('change');

            if (cityCode) {
                var $cityAddressSelect = $('#input-recipient_city_address');
                var newOptionAddress = new Option(cityName, cityCode, true, true);
                $cityAddressSelect.append(newOptionAddress);
                $cityAddressSelect.trigger('change');
            }

            if (addressCode) {
                console.log('Inserting address:', addressCode);
                var $addressSelect = $('#input-recipient-address');
                var addressOption = new Option(addressCode, addressCode, true, true);
                $addressSelect.append(addressOption);
                $addressSelect.trigger('change');
                console.log('Address field value after insert:', $addressSelect.val());
            }
        }
    }
