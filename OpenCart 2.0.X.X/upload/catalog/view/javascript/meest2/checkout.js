// Version: 1768481876
// Version: 1768479025
/**
 * Meest2 Checkout Integration Module
 * Модуль интеграции доставки Meest2 для OpenCart
 * 
 * @author Meest2 Team
 * @version 2.0 (Optimized)
 */

// ============================================================================
// ГЛОБАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ
// ============================================================================

if (!window.meest2Loaded) {
    window.meest2Loaded = true;

    // Глобальна мапа для зберігання адреса → UUID
    window.meest2BranchMap = {};
    window.meest2AddressMap = {};

    // ========================================================================
    // КОНСТАНТЫ И КОНФИГУРАЦИЯ
    // ========================================================================

    const MEEST2_CONFIG = {
        // Типы доставки
        DELIVERY_TYPES: {
            COURIER: 'meest2.courier',
            POSTOMAT: 'meest2.postomat',
            WAREHOUSE: 'meest2.warehouse'
        },

        // Мапинг типов для API
        SERVICE_MAPPING: {
            'warehouse': 'Branch',
            'postomat': 'Branch',
            'courier': 'Door'
        },

        // API endpoints
        API_ENDPOINTS: {
            GET_DATA: 'index.php?route=module/meest2/getMeestData',
            SAVE_ADDRESS: 'index.php?route=module/meest2/save',
            CALCULATE_COST: 'index.php?route=module/meest2/calculateShippingCost',
            GET_BRANCHES: 'index.php?route=module/meest2/getBranchesWithCoordinates'
        },

        // Настройки autocomplete
        AUTOCOMPLETE: {
            MIN_LENGTH: 2,
            DELAY: 300
        },

        // Селекторы
        SELECTORS: {
            SHIPPING_METHOD: 'input[type="radio"][name="shipping_method"]',
            CITY_INPUT: 'input[data-meest="city"]',
            MEEST_CONTAINER: '.data-meest',
            MAP_MODAL: '#meest2-map-modal'
        },

        // Таймауты
        TIMEOUTS: {
            SHIPPING_CHANGE: 1200,
            MAP_RESIZE: 100
        }
    };

    // Тексты для UI (можно вынести в локализацию)
    const MEEST2_TEXTS = {
        postomat: {
            branch: 'Введіть номер або адресу поштомату (вулиця, номер будинку)',
            city: 'Введіть назву населеного пункту'
        },
        warehouse: {
            branch: 'Введіть номер або адресу відділення (вулиця, номер будинку)',
            city: 'Введіть назву населеного пункту'
        },
        courier: {
            address: 'Кур\'єром Meest на адресу',
            city: 'Введіть назву населеного пункту'
        },
        alerts: {
            selectCity: 'Введіть назву населеного пункту',
            loading: 'Завантаження...',
            calculating: 'Розрахунок...',
            noBranches: 'Відділення не знайдено',
            loadError: 'Помилка завантаження'
        }
    };

    // Глобальные переменные для карты
    let meest2Map = null;
    let meest2Markers = [];

    // ========================================================================
    // UTILITY ФУНКЦІЇ
    // ========================================================================

    /**
     * Обновляет все checkout поля (город и адрес)
     * @param {string|null} city - Название города
     * @param {string|null} address - Адрес доставки
     */
    function updateCheckoutFields(city, address) {
        if (city !== null) {
            updateFieldsByName('shipping_address[city]', city);
            updateFieldsByName('city', city);
        }
        if (address !== null) {
            updateFieldsByName('shipping_address[address_1]', address);
            updateFieldsByName('address_1', address);
        }
    }

    /**
     * Обновляет все поля с определенным именем
     * @param {string} fieldName - Имя поля
     * @param {string} value - Значение для установки
     */
    function updateFieldsByName(fieldName, value) {
        $(`input[name="${fieldName}"]`).each(function () {
            $(this).val(value).attr('value', value).trigger('change');
        });
    }

    /**
     * Сохраняет адрес на сервере
     * @param {string} shippingMethod - Метод доставки (meest2.warehouse, meest2.postomat, meest2.courier)
     * @param {string} cityCode - UUID города
     * @param {string} branchCode - UUID отделения
     * @param {string} addressCode - UUID адреса
     * @param {string} regionCode - UUID региона
     * @param {string} building - Номер будинку/квартири
     * @param {string} addressName - Назва вулиці (текст)
     */
    function saveShippingData(shippingMethod, cityCode, branchCode, addressCode, regionCode, building, addressName) {
        $.ajax({
            url: 'index.php?route=module/meest2/saveMeestSessionData',
            type: 'post',
            data: {
                shipping_method: shippingMethod || '',
                city_code: cityCode || '',
                branch_code: branchCode || '',
                address_code: addressCode || '',
                region_code: regionCode || '',
                building: building || '',
                address_name: addressName || ''
            },
            dataType: 'json'
        });
    }

    /**
     * Уничтожает существующий autocomplete если он есть
     * @param {jQuery} $element - jQuery элемент
     */
    function destroyAutocomplete($element) {
        // Полностью удаляем все autocomplete экземпляры
        if ($element.data('ui-autocomplete')) {
            $element.autocomplete('destroy');
        }
        if ($element.data('autocomplete')) {
            $element.removeData('autocomplete');
        }
        // Удаляем все связанные события autocomplete
        $element.off('keydown.autocomplete keyup.autocomplete keypress.autocomplete focus.autocomplete');
        // Удаляем атрибут autocomplete для предотвращения реинициализации
        $element.removeAttr('autocomplete');
    }

    /**
     * Переключает видимость кнопки карты
     * @param {jQuery} $container - Контейнер с кнопкой
     * @param {boolean} show - Показать или скрыть
     */
    function toggleMapButton($container, show) {
        const $mapBtn = $container.find('.meest2-map-btn');
        $mapBtn.toggleClass('hidden', !show);
    }

    /**
     * Получает тип сервиса из строки (например, 'meest2.warehouse' -> 'warehouse')
     * @param {string} fullServiceName - Полное название сервиса
     * @returns {string|null} - Короткое название или null если не передано значение
     */
    function getServiceType(fullServiceName) {
        if (!fullServiceName || typeof fullServiceName !== 'string') {
            return null;
        }
        return fullServiceName.replace('meest2.', '');
    }

    // ========================================================================
    // AJAX ФУНКЦІЇ
    // ========================================================================

    /**
     * Загрузка данных с API Meest2
     * @param {string} action - Тип действия (getCities, getBranches, etc.)
     * @param {object} params - Дополнительные параметры
     * @returns {Promise}
     */
    function fetchMeestData(action, params = {}) {
        return $.ajax({
            url: MEEST2_CONFIG.API_ENDPOINTS.GET_DATA,
            type: 'POST',
            dataType: 'json',
            data: { action, ...params }
        });
    }

    /**
     * Расчет и обновление цены доставки
     * @param {string} service - Тип сервиса (warehouse, postomat, courier)
     * @param {string} cityUUID - UUID города получателя
     * @param {string} addressUUID - UUID отделения или адреса
     */
    function calculateAndUpdateShippingPrice(service, cityUUID, addressUUID) {
        if (!cityUUID || !addressUUID) {
            return;
        }

        const receiverService = MEEST2_CONFIG.SERVICE_MAPPING[service] || 'Branch';
        const params = {
            receiver_city_id: cityUUID,
            receiver_service: receiverService
        };

        // Добавляем UUID в зависимости от типа доставки
        if (service === 'warehouse' || service === 'postomat') {
            params.receiver_branch_id = addressUUID;
        } else if (service === 'courier') {
            params.receiver_address_id = addressUUID;
        }

        const $priceElement = $(`#meest2-price-${service}`);
        if (!$priceElement.length) return;

        const originalText = $priceElement.text();
        $priceElement.html(`<span style="opacity: 0.5;">${MEEST2_TEXTS.alerts.calculating}</span>`);

        $.ajax({
            url: MEEST2_CONFIG.API_ENDPOINTS.CALCULATE_COST,
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function (response) {
                if (response?.success && response?.data && typeof response.data.costServices !== 'undefined') {
                    const cost = parseFloat(response.data.costServices) || 0;
                    const formattedPrice = cost <= 0 ? '' : `${cost.toFixed(2)} ₴`;

                    $priceElement
                        .html(formattedPrice)
                        .attr('data-cost', cost)
                        .attr('data-cost-with-tax', cost);
                } else {
                    $priceElement.html(originalText);
                }
            },
            error: function (xhr, status, error) {
                $priceElement.html(originalText);
            }
        });
    }

    // ========================================================================
    // AUTOCOMPLETE ФУНКЦІЇ
    // ========================================================================

    /**
     * Инициализация autocomplete для города
     * @param {jQuery} $input - Поле ввода города
     * @param {function} onSelectCallback - Callback после выбора города
     */
    function initCityAutocomplete($input, onSelectCallback) {
        destroyAutocomplete($input);

        // Устанавливаем глобальные переменные для перехватчика
        currentCityInput = $input;
        currentCityCallback = onSelectCallback;

        // Маркируем элемент как инициализированный Meest2 и добавляем уникальный класс
        $input.attr('data-meest-autocomplete', 'true').addClass('meest2-city-autocomplete');

        // Отложенная инициализация чтобы обойти конфликт с common.js
        setTimeout(function () {
            if (!$input.closest('html').length) {
                return;
            }

            destroyAutocomplete($input);

            // Отключаем autocomplete от common.js окончательно
            $input.off('.autocomplete');

            $input.autocomplete({
                minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
                delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,
                appendTo: '.meest2-container', // Ограничиваем область видимости

                source: function (request, response) {
                    const inputValue = $input.val() || '';
                    const searchTerm = request.term || inputValue;

                    if (!searchTerm || searchTerm.length < MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH) {
                        response([]);
                        return;
                    }

                    fetchMeestData('getCities', { search: searchTerm })
                        .done(function (json) {
                            response($.map(json, function (item) {
                                return {
                                    label: `${item.type} ${item.name}, ${item.region} обл ( ${item.district} р-н)`,
                                    city: item.name,
                                    value: item.id
                                };
                            }));
                        })
                        .fail(function () {
                            response([]);
                        });
                },

                select: function (event, ui) {
                    // Защита от конфликта с common.js - event может не иметь preventDefault
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }

                    // Защита от конфликта с common.js - ui или ui.item может быть undefined
                    if (!ui || !ui.item) {
                        return false;
                    }

                    $input
                        .val(ui.item.city)
                        .attr('data-city-id', ui.item.value)
                        .attr('data-address', ui.item.city);

                    // Удаляем временные элементы
                    $('#meest-tmp').remove();

                    if (onSelectCallback) {
                        onSelectCallback(ui.item);
                    }

                    return false;
                }
            });

            // ПРИМЕЧАНИЕ: Обработка кликов по элементам списка теперь в глобальном обработчике
            // (см. ГЛОБАЛЬНЫЙ ОБРАБОТЧИК ДЛЯ AUTOCOMPLETE выше)

        }, 300); // закрываем setTimeout для обхода конфликта с common.js (увеличено до 300ms)
    }

    /**
     * Инициализация autocomplete для отделений/поштоматов
     * @param {jQuery} $input - Поле ввода
     * @param {string} serviceType - Тип сервиса (warehouse/postomat)
     */
    function initBranchAutocomplete($input, serviceType) {
        destroyAutocomplete($input);
        $input.autocomplete({
            minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
            delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,

            source: function (request, response) {
                const inputValue = $input.val() || '';
                const searchTerm = request.term || inputValue;
                const cityId = $(MEEST2_CONFIG.SELECTORS.CITY_INPUT).attr('data-city-id');
                const action = serviceType === 'postomat' ? 'getPoshtomat' : 'getBranches';

                if (!cityId) {
                    response([]);
                    return;
                }

                fetchMeestData(action, {
                    filter: cityId,
                    search: searchTerm
                })
                    .done(function (json) {
                        // Зберігаємо мапу адреса → UUID
                        $.each(json, function (index, item) {
                            window.meest2BranchMap[item.description] = item.id;
                        });

                        response($.map(json, function (item) {
                            return {
                                label: item.description,
                                address: item.description,
                                value: item.id
                            };
                        }));
                    })
                    .fail(function () {
                        response([]);
                    });
            },

            select: function (event, ui) {
                // Защита от конфликта с common.js - event может не иметь preventDefault
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }

                // Защита от конфликта с common.js - ui или ui.item может быть undefined
                if (!ui || !ui.item) {
                    return false;
                }

                $input
                    .val(ui.item.address)
                    .attr('data-address', ui.item.address)
                    .attr('data-branch-id', ui.item.value)
                    .trigger('change');

                const city = $(MEEST2_CONFIG.SELECTORS.CITY_INPUT).val();
                const cityId = $(MEEST2_CONFIG.SELECTORS.CITY_INPUT).attr('data-city-id');

                const shippingMethod = 'meest2.' + serviceType;
                saveShippingData(shippingMethod, cityId, ui.item.value, '', '', '');
                updateCheckoutFields(city, ui.item.address);
                $('#input-shipping-address-1').val(ui.item.address);

                if (cityId && ui.item.value) {
                    calculateAndUpdateShippingPrice(serviceType, cityId, ui.item.value);
                }

                return false;
            }
        });
    }

    /**
     * Инициализация autocomplete для улиц (курьерская доставка)
     * @param {jQuery} $input - Поле ввода адреса
     * @param {jQuery} $cityInput - Поле ввода города
     */
    function initStreetAutocomplete($input, $cityInput) {
        destroyAutocomplete($input);

        // Отложенная инициализация как у city
        setTimeout(function () {
            if (!$input.closest('html').length) {
                return;
            }

            destroyAutocomplete($input);
            $input.off('.autocomplete');

            $input.autocomplete({
                minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
                delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,

                source: function (request, response) {
                    const inputValue = $input.val() || '';
                    const searchTerm = request.term || inputValue;
                    const cityId = $input.attr('data-city-id');

                    if (!cityId) {
                        response([]);
                        return;
                    }

                    fetchMeestData('getStreets', {
                        filter: cityId,
                        search: searchTerm
                    })
                        .done(function (json) {
                            // Зберігаємо мапу адреса → UUID для кур'єрської доставки
                            $.each(json, function (index, item) {
                                window.meest2AddressMap[item.description] = item.id;
                            });

                            response($.map(json, function (item) {
                                return {
                                    label: item.description,
                                    value: item.id
                                };
                            }));
                        })
                        .fail(function () {
                            response([]);
                        });
                },

                select: function (event, ui) {
                    // Защита от конфликта с common.js - event может не иметь preventDefault
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }

                    // Защита от конфликта с common.js - ui или ui.item может быть undefined
                    if (!ui || !ui.item) {
                        return false;
                    }

                    $input
                        .val(ui.item.label)
                        .attr('data-value', ui.item.label)
                        .attr('data-address-id', ui.item.value)
                        .trigger('change')
                        .focus();

                    const city = $cityInput.val();
                    const cityId = $cityInput.attr('data-city-id') || $input.attr('data-city-id');

                    saveShippingData('meest2.courier', cityId, '', ui.item.value, '', $('#meestBuilding').val() || '', ui.item.label);
                    updateCheckoutFields(city, ui.item.label);
                    $('#input-shipping-address-1').val(ui.item.label);

                    if (cityId && ui.item.value) {
                        calculateAndUpdateShippingPrice('courier', cityId, ui.item.value);
                    }

                    return false;
                }
            });
        }, 300); // закрываем setTimeout для обхода конфликта с common.js
    }

    // ========================================================================
    // ГЕНЕРАЦІЯ HTML
    // ========================================================================

    /**
     * Генерация HTML для полей ввода (отделение/поштомат)
     * @param {string} type - Тип (warehouse/postomat)
     * @param {string} branchPlaceholder - Placeholder для поля отделения
     * @param {string} cityPlaceholder - Placeholder для поля города
     * @returns {string} - HTML разметка
     */
    function renderBranchInputs(type, branchPlaceholder, cityPlaceholder) {
        return `
            <div class="data-meest meest2-container" data-meest="${type}-container">
                <div class="meest2-field-wrapper">
                    <div class="meest2-input-group">
                        <label>${cityPlaceholder}</label>
                        <input type="text" class="form-control" data-meest="city" placeholder="${cityPlaceholder}" autocomplete="off"/>
                    </div>
                </div>
                <div class="meest2-field-wrapper">
                    <div class="meest2-input-group">
                        <label>${branchPlaceholder}</label>
                        <input disabled type="text" class="form-control" data-meest="${type}" placeholder="${branchPlaceholder}"/>
                    </div>
                    <a href="#" class="meest2-map-btn hidden" id="meest-map-link-${type}" 
                       data-map-url="https://www.google.com/maps/search/?api=1&query=Ukraine">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        Обрати на мапі
                    </a>
                </div>
            </div>
        `;
    }

    /**
     * Генерация HTML для курьерской доставки
     * @returns {string} - HTML разметка
     */
    function renderCourierInputs() {
        return `
            <div class="data-meest meest2-container" data-meest="courier-container">
                <div class="meest2-field-wrapper">
                    <div class="meest2-input-group">
                        <label>${MEEST2_TEXTS.courier.city}</label>
                        <input id="meestCity" type="text" class="form-control" placeholder="${MEEST2_TEXTS.courier.city}" autocomplete="off"/>
                    </div>
                </div>
                <div class="meest2-field-wrapper">
                    <div class="meest2-input-group">
                        <label>${MEEST2_TEXTS.courier.address}</label>
                        <input disabled id="meestAddress" type="text" class="form-control" placeholder="${MEEST2_TEXTS.courier.address}"/>
                    </div>
                </div>
                <div class="meest2-field-wrapper">
                    <div class="meest2-input-group">
                        <label>Будинок/Квартира</label>
                        <input id="meestBuilding" type="text" class="form-control" placeholder="Будинок/Квартира" autocomplete="off"/>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Генерация HTML для модального окна с картой
     * @returns {string} - HTML разметка
     */
    function renderMapModal() {
        return `
            <div id="meest2-map-modal" class="meest2-modal">
                <div class="meest2-modal-content">
                    <div class="meest2-modal-header">
                        <h3>Мапа</h3>
                        <button class="meest2-modal-close" id="meest2-modal-close">&times;</button>
                    </div>
                    <div class="meest2-modal-body">
                        <div id="meest2-map"></div>
                    </div>
                </div>
            </div>
        `;
    }

    // ========================================================================
    // ОБРАБОТЧИКИ ТИПОВ ДОСТАВКИ
    // ========================================================================

    /**
     * Обработка доставки в отделение/поштомат (общая логика)
     * @param {jQuery} $activeShip - Активный элемент доставки
     * @param {string} type - Тип (warehouse/postomat)
     */
    function handleBranchDelivery($activeShip, type) {
        const texts = MEEST2_TEXTS[type];
        const html = renderBranchInputs(type, texts.branch, texts.city);

        $activeShip.parent().after(html);

        const $container = $(`.meest2-container[data-meest="${type}-container"]`);
        const $cityInput = $container.find('input[data-meest="city"]');
        const $branchInput = $container.find(`input[data-meest="${type}"]`);

        // Инициализация autocomplete для города
        initCityAutocomplete($cityInput, function (cityItem) {
            const $mapBtn = $container.find('.meest2-map-btn');
            toggleMapButton($container, true);

            $branchInput
                .prop('disabled', false)
                .removeClass('disabled')
                .trigger('change');
        });

        // Инициализация autocomplete для отделения
        initBranchAutocomplete($branchInput, type);

        // WORKAROUND: Використовуємо подію autocompleteselect для збереження UUID
        $branchInput.on('autocompleteselect', function (event, ui) {
            if (ui && ui.item && ui.item.value) {
                $(this).attr('data-branch-id', ui.item.value);
            }
        });

        // Обработка изменения в поле отделения (множественные события для надежности)
        $branchInput.on('change blur', function () {
            const city = $cityInput.val();
            const address = $(this).val();
            const cityId = $cityInput.attr('data-city-id');
            let branchId = $(this).attr('data-branch-id');

            // WORKAROUND: Шукаємо UUID по тексту адреси в глобальній мапі
            if (!branchId && address && window.meest2BranchMap) {
                branchId = window.meest2BranchMap[address];
                if (branchId) {
                    $(this).attr('data-branch-id', branchId);
                }
            }

            if (city && address && address.trim().length > 0) {
                $('#input-shipping-address-1').val(address);
                const shippingMethod = 'meest2.' + type;
                saveShippingData(shippingMethod, cityId, branchId || '', '', '', '');
                updateCheckoutFields(null, address);
            }
        });

        // Показ/скрытие кнопки карты при вводе города
        $cityInput.on('input change', function () {
            const hasCity = $(this).val().trim().length > 0;
            toggleMapButton($container, hasCity);
        });
    }

    /**
     * Обработка курьерской доставки
     * @param {jQuery} $activeShip - Активный элемент доставки
     */
    function handleCourierDelivery($activeShip) {
        const html = renderCourierInputs();
        $activeShip.parent().after(html);

        const $cityInput = $('#meestCity');
        const $addressInput = $('#meestAddress');

        // Инициализация autocomplete для города (курьер)
        initCityAutocomplete($cityInput, function (cityItem) {
            $addressInput
                .prop('disabled', false)
                .attr('data-city-id', cityItem.value);

            const $container = $cityInput.closest('.meest2-container');
            toggleMapButton($container, true);
        });

        // Инициализация autocomplete для улиц
        initStreetAutocomplete($addressInput, $cityInput);


        // Обработка изменения адреса (множественные события для надежности)
        $addressInput.on('change blur', function () {
            const city = $cityInput.val();
            const address = $(this).val();
            // Фолбек: якщо data-city-id немає в інпуті міста, беремо з адреси
            const cityId = $cityInput.attr('data-city-id') || $(this).attr('data-city-id') || '';

            let addressId = $(this).attr('data-address-id') || '';

            // WORKAROUND: Шукаємо UUID по тексту адреси в глобальній мапі
            if (!addressId && address && window.meest2AddressMap && window.meest2AddressMap[address]) {
                addressId = window.meest2AddressMap[address];
                $(this).attr('data-address-id', addressId);
            }

            const building = $('#meestBuilding').val() || '';

            if (city && address && address.trim().length > 0) {
                $('#input-shipping-address-1').val(address);
                // Для кур'єра передаємо UUID адреси в address_code
                saveShippingData('meest2.courier', cityId, '', addressId, '', building, address);
                updateCheckoutFields(null, address);
            }
        });

        // Обработка изменения поля building
        const $buildingInput = $('#meestBuilding');
        $buildingInput.on('change blur', function () {
            const city = $cityInput.val();
            const address = $addressInput.val();
            const cityId = $cityInput.attr('data-city-id') || $addressInput.attr('data-city-id') || '';
            const addressId = $addressInput.attr('data-address-id') || '';
            const building = $(this).val() || '';

            if (city && address && address.trim().length > 0) {
                saveShippingData('meest2.courier', cityId, '', addressId, '', building, address);
            }
        });

    }

    // ========================================================================
    // ГЛАВНАЯ ФУНКЦИЯ ИНИЦИАЛИЗАЦИИ
    // ========================================================================

    /**
     * Основная функция инициализации Meest2
     */
    function meest2() {
        const $activeShip = $('input[type="radio"][name="shipping_method"]:checked');
        const meestService = $activeShip.val();

        // Проверяем что метод доставки выбран и определен
        if (!meestService) {
            return;
        }

        // Проверяем, является ли выбранный метод доставкой Meest2
        const deliveryTypes = Object.values(MEEST2_CONFIG.DELIVERY_TYPES);
        if (deliveryTypes.indexOf(meestService) === -1) {
            return;
        }

        // Удаляем предыдущие элементы Meest2
        $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();

        // Инициализация в зависимости от типа доставки
        switch (meestService) {
            case MEEST2_CONFIG.DELIVERY_TYPES.POSTOMAT:
                handleBranchDelivery($activeShip, 'postomat');
                break;

            case MEEST2_CONFIG.DELIVERY_TYPES.WAREHOUSE:
                handleBranchDelivery($activeShip, 'warehouse');
                break;

            case MEEST2_CONFIG.DELIVERY_TYPES.COURIER:
                handleCourierDelivery($activeShip);
                break;
        }
    }

    // ========================================================================
    // ФУНКЦИИ РАБОТЫ С КАРТОЙ
    // ========================================================================

    /**
     * Загрузка отделений на карту
     * @param {string} cityId - ID города
     * @param {string} type - Тип доставки
     * @param {jQuery} $container - Контейнер с полями
     */
    function loadBranchesOnMap(cityId, type, $container) {
        $('#meest2-map').html(`<div class="meest2-loading">${MEEST2_TEXTS.alerts.loading}</div>`);

        $.ajax({
            url: MEEST2_CONFIG.API_ENDPOINTS.GET_BRANCHES,
            type: 'POST',
            data: { city_id: cityId, type: type },
            dataType: 'json',
            success: function (branches) {
                if (branches.length === 0) {
                    $('#meest2-map').html(`<div class="meest2-loading">${MEEST2_TEXTS.alerts.noBranches}</div>`);
                    return;
                }

                renderMapWithBranches(branches);
            },
            error: function () {
                $('#meest2-map').html(`<div class="meest2-loading">${MEEST2_TEXTS.alerts.loadError}</div>`);
            }
        });
    }

    /**
     * Отрисовка карты с отделениями
     * @param {Array} branches - Массив отделений с координатами
     */
    function renderMapWithBranches(branches) {
        // Очищаем контейнер и удаляем старую карту
        $('#meest2-map').html('');

        if (meest2Map) {
            meest2Map.remove();
            meest2Map = null;
            meest2Markers = [];
        }

        // Создаем новую карту
        meest2Map = L.map('meest2-map').setView([50.4501, 30.5234], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(meest2Map);

        // Добавляем маркеры и собираем границы
        const bounds = [];
        branches.forEach(function (branch) {
            if (branch.latitude && branch.longitude) {
                const lat = parseFloat(branch.latitude);
                const lng = parseFloat(branch.longitude);
                bounds.push([lat, lng]);

                const marker = L.marker([lat, lng]).addTo(meest2Map);
                marker.bindPopup(createBranchPopup(branch));
                meest2Markers.push(marker);
            }
        });

        // Центрируем карту по маркерам
        if (bounds.length > 0) {
            meest2Map.fitBounds(bounds, { padding: [50, 50] });
        }

        // Обновляем размер карты после загрузки
        setTimeout(function () {
            meest2Map.invalidateSize();
        }, MEEST2_CONFIG.TIMEOUTS.MAP_RESIZE);
    }

    /**
     * Создание HTML popup для маркера на карте
     * @param {object} branch - Данные отделения
     * @returns {string} - HTML разметка popup
     */
    function createBranchPopup(branch) {
        const addressEscaped = branch.address.replace(/'/g, "\\'");
        return `
            <div style="min-width: 200px;">
                <strong>${branch.description}</strong><br>
                ${branch.address}<br>
                <button class="btn btn-primary btn-sm" style="margin-top: 10px;"
                        onclick="selectBranch('${branch.id}', '${addressEscaped}')">
                    Обрати
                </button>
            </div>
        `;
    }

    /**
     * Выбор отделения на карте
     * @param {string} branchId - ID отделения
     * @param {string} branchAddress - Адрес отделения
     */
    window.selectBranch = function (branchId, branchAddress) {
        const $activeContainer = $('.meest2-container:visible').last();
        const type = $activeContainer.attr('data-meest').replace('-container', '');
        const $input = $activeContainer.find(`input[data-meest="${type}"]`);
        const $cityInput = $activeContainer.find('input[data-meest="city"]');

        // Заполняем поле отделения
        $input.val(branchAddress).attr('value', branchAddress).attr('data-branch-id', branchId);

        const city = $cityInput.val();
        const cityId = $cityInput.attr('data-city-id');

        // Сохраняем в сессию (не в БД, т.к. заказ еще не создан)
        const shippingMethod = 'meest2.' + type;
        $.ajax({
            url: 'index.php?route=module/meest2/saveMeestSessionData',
            type: 'post',
            data: {
                shipping_method: shippingMethod,
                city_code: cityId,
                branch_code: branchId,
                region_code: ''
            },
            dataType: 'json'
        });

        $('#input-shipping-address-1').val(branchAddress);
        updateCheckoutFields(city, branchAddress);

        // Расчет цены доставки
        if (cityId && branchId) {
            calculateAndUpdateShippingPrice(type, cityId, branchId);
        }

        // Закрываем модальное окно
        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
    };

    // ========================================================================
    // КРИТИЧЕСКОЕ РЕШЕНИЕ: MutationObserver для перехвата создания списка
    // ========================================================================

    /**
     * Глобальные переменные для работы с autocomplete
     */
    let currentCityInput = null;
    let currentCityCallback = null;
    let autocompleteObserver = null;

    /**
     * Функция установки города из элемента списка
     */
    function setCityFromItem(itemData) {
        if (!itemData || !itemData.city || !currentCityInput) {
            return;
        }

        currentCityInput
            .val(itemData.city)
            .attr('data-city-id', itemData.value)
            .attr('data-address', itemData.city)
            .trigger('change');

        $('#meest-tmp').remove();

        // Закрываем autocomplete
        setTimeout(function () {
            if (currentCityInput && currentCityInput.autocomplete) {
                currentCityInput.autocomplete('close');
            }
        }, 50);

        // Вызываем callback
        if (currentCityCallback) {
            currentCityCallback(itemData);
        }
    }

    /**
     * ФИНАЛЬНОЕ РЕШЕНИЕ: Глобальный делегированный обработчик для ВСЕХ .dropdown-menu li
     * Работает независимо от того, когда dropdown появляется в DOM
     */
    function initDropdownHandler() {
        // Удаляем старые обработчики если есть
        $(document).off('mousedown.meest2-dropdown click.meest2-dropdown');

        // Глобальный делегированный обработчик на все клики по .dropdown-menu li
        $(document).on('mousedown.meest2-dropdown click.meest2-dropdown', '.dropdown-menu li', function (e) {
            const $li = $(this);
            const $dropdown = $li.closest('.dropdown-menu');

            // Проверяем что это наш dropdown (видимый)
            if (!$dropdown.is(':visible')) {
                return;
            }

            const dataValue = $li.attr('data-value');
            const $link = $li.find('a');
            const text = $link.text();

            if (!dataValue || !text) {
                return;
            }

            // Останавливаем все события немедленно
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            // Парсим название города из текста
            // Формат: "місто Харків, ХАРКІВСЬКА" -> "Харків"
            const parts = text.split(',');
            const cityPart = parts[0] || '';
            const cityName = cityPart.replace(/^(місто|село|смт)\s+/i, '').trim();

            if (!cityName) {
                return;
            }

            // Проверяем что у нас есть куда вставлять
            if (!currentCityInput) {
                return;
            }

            // Создаем объект данных
            const itemData = {
                city: cityName,
                value: dataValue,
                label: text
            };

            // Вставляем город
            setCityFromItem(itemData);

            // Скрываем dropdown
            $dropdown.hide();

            return false;
        });
    }

    // ========================================================================
    // EVENT LISTENERS
    // ========================================================================

    /**
     * КРИТИЧЕСКИЙ ОБРАБОТЧИК: Прямой перехват кликов по dropdown-menu
     * Работает для ВСЕХ полей: city, warehouse, postomat, address
     */
    $(document).on('mousedown click', '.dropdown-menu li[data-value]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const $li = $(this);
        const dataValue = $li.attr('data-value');
        const text = $li.find('a').text();

        if (!dataValue || !text) {
            return false;
        }

        // Определяем какое поле активно
        let $targetInput = null;

        // Сначала проверяем город
        if (currentCityInput && currentCityInput.is(':focus')) {
            // const cityName = text.split(',')[0].replace(/^(місто|село|смт)\s+/i, '').trim();
            const cityName = text;

            currentCityInput
                .val(cityName)
                .attr('data-city-id', dataValue)
                .attr('data-address', cityName);

            if (currentCityCallback) {
                currentCityCallback({ city: cityName, value: dataValue, label: text });
            }
        } else {
            // Проверяем другие поля (warehouse, postomat, address)
            const $visibleInputs = $('input[data-meest="warehouse"]:focus, input[data-meest="postomat"]:focus, input#meestAddress:focus');

            if ($visibleInputs.length > 0) {
                $targetInput = $visibleInputs.first();

                $targetInput
                    .val(text)
                    .attr('data-address', text);

                // Для warehouse/postomat вызываем расчет цены
                const $container = $targetInput.closest('.meest2-container');
                const cityId = $container.find('input[data-meest="city"]').attr('data-city-id');

                if (cityId && dataValue) {
                    const type = $targetInput.attr('data-meest') || 'courier';
                    calculateAndUpdateShippingPrice(type, cityId, dataValue);
                }
            }
        }

        $(this).closest('.dropdown-menu').hide();

        return false;
    });

    /**
     * Обработчик изменения способа доставки
     */
    $(document).on('change', MEEST2_CONFIG.SELECTORS.SHIPPING_METHOD, function () {
        const self = this;
        setTimeout(function () {
            const val = $(self).val();
            $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();

            if (val.indexOf('meest2.') !== -1) {
                meest2();
            }
        }, MEEST2_CONFIG.TIMEOUTS.SHIPPING_CHANGE);
    });

    /**
     * Обработчик открытия карты
     */
    $(document).on('click', '.meest2-map-btn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const $container = $btn.closest('.meest2-container');
        const type = $container.attr('data-meest').replace('-container', '');
        const cityId = $container.find('input[data-meest="city"]').attr('data-city-id');

        if (!cityId) {
            alert(MEEST2_TEXTS.alerts.selectCity);
            return;
        }

        // Создаем модальное окно если его нет
        if ($(MEEST2_CONFIG.SELECTORS.MAP_MODAL).length === 0) {
            $('body').append(renderMapModal());
        }

        // Показываем модальное окно и загружаем отделения
        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).addClass('active');
        loadBranchesOnMap(cityId, type, $container);
    });

    /**
     * Закрытие модального окна по кнопке
     */
    $(document).on('click', '#meest2-modal-close', function () {
        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
    });

    /**
     * Закрытие модального окна по клику вне его
     */
    $(document).on('click', MEEST2_CONFIG.SELECTORS.MAP_MODAL, function (e) {
        if (e.target.id === 'meest2-map-modal') {
            $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
        }
    });

    // ========================================================================
    // ИНИЦИАЛИЗАЦИЯ
    // ========================================================================

    /**
     * Проверка и инициализация блока если метод доставки уже выбран
     */
    function checkAndInitializeIfSelected() {
        const $checkedShipping = $('input[type="radio"][name="shipping_method"]:checked');
        const selectedMethod = $checkedShipping.val();

        if (selectedMethod && selectedMethod.indexOf('meest2.') !== -1) {
            // Удаляем старые контейнеры перед инициализацией
            $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();
            meest2();
        }
    }

    /**
     * MutationObserver для отслеживания появления радиокнопок доставки
     */
    function initShippingMethodObserver() {
        // Наблюдаем за всем document, так как не знаем где появится блок доставки
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                // Проверяем добавленные узлы
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        // Проверяем сам узел
                        const $node = $(node);
                        const $shippingInputs = $node.find('input[type="radio"][name="shipping_method"]');

                        // Или это сам input
                        if ($node.is('input[type="radio"][name="shipping_method"]')) {
                            $shippingInputs.push(node);
                        }

                        // Если нашли радиокнопки доставки
                        if ($shippingInputs.length > 0) {
                            // Проверяем выбранный метод
                            setTimeout(function () {
                                checkAndInitializeIfSelected();
                            }, 200);
                        }
                    }
                });
            });
        });

        // Начинаем наблюдение только если document.body существует
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            // Если body еще нет, ждем DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function () {
                if (document.body) {
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        }
    }

    /**
     * Запуск модуля после загрузки всех зависимостей
     */
    function initMeest2Module() {
        // Инициализируем глобальный обработчик для dropdown-menu
        initDropdownHandler();

        // Запускаем наблюдатель за появлением блока доставки
        initShippingMethodObserver();

        // Проверяем и инициализируем если метод уже выбран
        checkAndInitializeIfSelected();
    }

    // Первичная инициализация
    if (typeof $.fn.autocomplete === 'function') {
        initMeest2Module();
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            initMeest2Module();
        });
    }

    // Резервная проверка после полной загрузки DOM
    $(document).ready(function () {
        setTimeout(function () {
            checkAndInitializeIfSelected();
        }, 800);
    });

    // Дополнительная проверка после загрузки всех ресурсов страницы
    $(window).on('load', function () {
        setTimeout(function () {
            checkAndInitializeIfSelected();
        }, 1000);
    });
}
