/**
 * Meest2 Checkout Integration Module (Select Version)
 * Модуль интеграции доставки Meest2 для OpenCart с поддержкой select
 *
 * @author Meest2 Team
 * @version 2.1 (Select-based)
 */

// ============================================================================
// ДІАГНОСТИКА jQuery UI + ЗБЕРЕЖЕННЯ ПОСИЛАННЯ
// ============================================================================
console.log('[Meest2 Debug] Файл завантажено (Select Version)');
console.log('[Meest2 Debug] jQuery:', typeof jQuery);
console.log('[Meest2 Debug] $.fn.autocomplete:', typeof (jQuery && jQuery.fn && jQuery.fn.autocomplete));

// КРИТИЧНО: Зберігаємо посилання на autocomplete ДО того як SimpleCheckout його видалить
if (!window.meest2SavedAutocomplete && jQuery && jQuery.fn && jQuery.fn.autocomplete) {
    window.meest2SavedAutocomplete = jQuery.fn.autocomplete;
    console.log('[Meest2 Debug] Autocomplete збережено в window.meest2SavedAutocomplete');
}

// Функція відновлення autocomplete якщо його видалили
window.meest2RestoreAutocomplete = function() {
    if (window.meest2SavedAutocomplete && jQuery && jQuery.fn && !jQuery.fn.autocomplete) {
        jQuery.fn.autocomplete = window.meest2SavedAutocomplete;
        console.log('[Meest2 Debug] Autocomplete відновлено з window.meest2SavedAutocomplete');
        return true;
    }
    return false;
};

// ============================================================================
// ГЛОБАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ
// ============================================================================

if (!window.meest2LoadedSelect) {
    window.meest2LoadedSelect = true;

    // Глобальна мапа для зберігання адреса → UUID
    window.meest2BranchMap = {};
    window.meest2AddressMap = {};

    // ========================================================================
    // КОНСТАНТЫ И КОНФИГУРАЦИЯ
    // ========================================================================

    const MEEST2_CONFIG = {
        // Селектор для select доставки
        SELECTORS: {
            SHIPPING_SELECT: '#select-shipping',
            CITY_INPUT: 'input[data-meest="city"]',
            MEEST_CONTAINER: '.data-meest',
            MAP_MODAL: '#meest2-map-modal',
            TARGET_CITY_INPUT: '#input-city',
            TARGET_ADDRESS_INPUT: '#input-address'
        },

        // API endpoints
        API_ENDPOINTS: {
            GET_DATA: 'index.php?route=extension/module/meest2/getMeestData',
            SAVE_ADDRESS: 'index.php?route=extension/module/meest2/save',
            CALCULATE_COST: 'index.php?route=extension/module/meest2/calculateShippingCost',
            GET_BRANCHES: 'index.php?route=extension/module/meest2/getBranchesWithCoordinates'
        },

        // Настройки autocomplete
        AUTOCOMPLETE: {
            MIN_LENGTH: 2,
            DELAY: 300
        },

        // Таймауты
        TIMEOUTS: {
            SHIPPING_CHANGE: 300,
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
     * Обновляет checkout поля (город и адрес) в целевых полях
     * @param {string|null} city - Название города
     * @param {string|null} address - Адрес доставки
     */
    function updateCheckoutFields(city, address) {
        if (city !== null) {
            $(MEEST2_CONFIG.SELECTORS.TARGET_CITY_INPUT).val(city).trigger('change');
            $('input[name="city"]').val(city).trigger('change');
            $('input[name="shipping_address[city]"]').val(city).trigger('change');
        }
        if (address !== null) {
            $(MEEST2_CONFIG.SELECTORS.TARGET_ADDRESS_INPUT).val(address).trigger('change');
            $('input[name="address"]').val(address).trigger('change');
            $('input[name="shipping_address[address_1]"]').val(address).trigger('change');
        }
    }

    /**
     * Збереження даних Meest2 в localStorage
     */
    function saveMeestDataToLocalStorage(data) {
        try {
            localStorage.setItem('meest2_selected_data', JSON.stringify(data));
            console.log('[Meest2] Дані збережено в localStorage:', data);
        } catch (e) {
            console.error('[Meest2] Помилка збереження в localStorage:', e);
        }
    }

    /**
     * Отримання даних Meest2 з localStorage
     */
    function getMeestDataFromLocalStorage() {
        try {
            const data = localStorage.getItem('meest2_selected_data');
            return data ? JSON.parse(data) : null;
        } catch (e) {
            console.error('[Meest2] Помилка читання з localStorage:', e);
            return null;
        }
    }

    /**
     * Очистка даних Meest2 з localStorage
     */
    function clearMeestDataFromLocalStorage() {
        try {
            localStorage.removeItem('meest2_selected_data');
            console.log('[Meest2] Дані очищено з localStorage');
        } catch (e) {
            console.error('[Meest2] Помилка очистки localStorage:', e);
        }
    }

    /**
     * Сохраняет адрес на сервере
     * @param {string} shippingMethod - Метод доставки (meest2.warehouse, meest2.postomat, meest2.courier)
     * @param {string} cityCode - UUID города
     * @param {string} branchCode - UUID отделения
     * @param {string} addressCode - UUID адреса
     * @param {string} regionCode - UUID региона
     */
    function saveShippingData(shippingMethod, cityCode, branchCode, addressCode, regionCode) {
        $.ajax({
            url: 'index.php?route=extension/module/meest2/saveMeestSessionData',
            type: 'post',
            data: {
                shipping_method: shippingMethod || '',
                city_code: cityCode || '',
                branch_code: branchCode || '',
                address_code: addressCode || '',
                region_code: regionCode || ''
            },
            dataType: 'json'
        });
    }

    /**
     * Уничтожает существующий autocomplete если он есть
     * @param {jQuery} $element - jQuery элемент
     */
    function destroyAutocomplete($element) {
        if ($element.data('ui-autocomplete')) {
            $element.autocomplete('destroy');
        }
        if ($element.data('autocomplete')) {
            $element.removeData('autocomplete');
        }
        $element.off('keydown.autocomplete keyup.autocomplete keypress.autocomplete focus.autocomplete');
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

        const SERVICE_MAPPING = {
            'warehouse': 'Branch',
            'postomat': 'Branch',
            'courier': 'Door'
        };

        const receiverService = SERVICE_MAPPING[service] || 'Branch';
        const params = {
            receiver_city_id: cityUUID,
            receiver_service: receiverService
        };

        if (service === 'warehouse' || service === 'postomat') {
            params.receiver_branch_id = addressUUID;
        } else if (service === 'courier') {
            params.receiver_address_id = addressUUID;
        }

        console.log('[Meest2] Розрахунок вартості доставки:', params);
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

        currentCityInput = $input;
        currentCityCallback = onSelectCallback;

        $input.attr('data-meest-autocomplete', 'true').addClass('meest2-city-autocomplete');

        setTimeout(function() {
            if (!$input.closest('html').length) {
                return;
            }

            destroyAutocomplete($input);
            $input.off('.autocomplete');
            window.meest2RestoreAutocomplete();

            $input.autocomplete({
                minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
                delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,
                appendTo: '.meest2-container',

                source: function(request, response) {
                    const inputValue = $input.val() || '';
                    const searchTerm = request.term || inputValue;

                    if (!searchTerm || searchTerm.length < MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH) {
                        response([]);
                        return;
                    }

                    fetchMeestData('getCities', { search: searchTerm })
                        .done(function(json) {
                            response($.map(json, function(item) {
                                return {
                                    label: `${item.type} ${item.name}, ${item.region}`,
                                    city: item.name,
                                    value: item.id
                                };
                            }));
                        })
                        .fail(function() {
                            response([]);
                        });
                },

                select: function(event, ui) {
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }

                    if (!ui || !ui.item) {
                        return false;
                    }

                    $input
                        .val(ui.item.city)
                        .attr('data-city-id', ui.item.value)
                        .attr('data-address', ui.item.city);

                    $('#meest-tmp').remove();

                    if (onSelectCallback) {
                        onSelectCallback(ui.item);
                    }

                    return false;
                }
            });
        }, 300);
    }

    /**
     * Инициализация autocomplete для отделений/поштоматов
     * @param {jQuery} $input - Поле ввода
     * @param {string} serviceType - Тип сервиса (warehouse/postomat)
     */
    function initBranchAutocomplete($input, serviceType) {
        destroyAutocomplete($input);
        window.meest2RestoreAutocomplete();
        
        $input.autocomplete({
            minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
            delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,

            source: function(request, response) {
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
                    .done(function(json) {
                        $.each(json, function(index, item) {
                            window.meest2BranchMap[item.description] = item.id;
                        });

                        response($.map(json, function(item) {
                            return {
                                label: item.description,
                                address: item.description,
                                value: item.id
                            };
                        }));
                    })
                    .fail(function() {
                        response([]);
                    });
            },

            select: function(event, ui) {
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }

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
                saveShippingData(shippingMethod, cityId, ui.item.value, '', '');
                updateCheckoutFields(city, ui.item.address);

                saveMeestDataToLocalStorage({
                    deliveryType: serviceType,
                    cityName: city,
                    cityId: cityId,
                    branchAddress: ui.item.address,
                    branchId: ui.item.value
                });

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

        setTimeout(function() {
            if (!$input.closest('html').length) {
                return;
            }

            destroyAutocomplete($input);
            $input.off('.autocomplete');
            window.meest2RestoreAutocomplete();

            $input.autocomplete({
                minLength: MEEST2_CONFIG.AUTOCOMPLETE.MIN_LENGTH,
                delay: MEEST2_CONFIG.AUTOCOMPLETE.DELAY,

                source: function(request, response) {
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
                        .done(function(json) {
                            $.each(json, function(index, item) {
                                window.meest2AddressMap[item.description] = item.id;
                            });

                            response($.map(json, function(item) {
                                return {
                                    label: item.description,
                                    value: item.id
                                };
                            }));
                        })
                        .fail(function() {
                            response([]);
                        });
                },

                select: function(event, ui) {
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }

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

                    saveShippingData('meest2.courier', cityId, '', ui.item.value, '');
                    updateCheckoutFields(city, ui.item.label);

                    if (cityId && ui.item.value) {
                        calculateAndUpdateShippingPrice('courier', cityId, ui.item.value);
                    }

                    return false;
                }
            });
        }, 300);
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
     * @param {jQuery} $selectElement - Select элемент доставки
     * @param {string} type - Тип (warehouse/postomat)
     */
    function handleBranchDelivery($selectElement, type) {
        const texts = MEEST2_TEXTS[type];
        const html = renderBranchInputs(type, texts.branch, texts.city);

        $selectElement.after(html);

        const $container = $(`.meest2-container[data-meest="${type}-container"]`);
        const $cityInput = $container.find('input[data-meest="city"]');
        const $branchInput = $container.find(`input[data-meest="${type}"]`);

        initCityAutocomplete($cityInput, function(cityItem) {
            const $mapBtn = $container.find('.meest2-map-btn');
            toggleMapButton($container, true);

            $branchInput
                .prop('disabled', false)
                .removeClass('disabled')
                .trigger('change');
        });

        initBranchAutocomplete($branchInput, type);

        $branchInput.on('autocompleteselect', function(event, ui) {
            if (ui && ui.item && ui.item.value) {
                $(this).attr('data-branch-id', ui.item.value);
            }
        });

        $branchInput.on('change blur', function() {
            const city = $cityInput.val();
            const address = $(this).val();
            const cityId = $cityInput.attr('data-city-id');
            let branchId = $(this).attr('data-branch-id');

            if (!branchId && address && window.meest2BranchMap) {
                branchId = window.meest2BranchMap[address];
                if (branchId) {
                    $(this).attr('data-branch-id', branchId);
                }
            }

            if (city && address && address.trim().length > 0) {
                const shippingMethod = 'meest2.' + type;
                saveShippingData(shippingMethod, cityId, branchId || '', '', '');
                updateCheckoutFields(city, address);
            }
        });

        $cityInput.on('input change', function() {
            const hasCity = $(this).val().trim().length > 0;
            toggleMapButton($container, hasCity);
        });
    }

    /**
     * Обработка курьерской доставки
     * @param {jQuery} $selectElement - Select элемент доставки
     */
    function handleCourierDelivery($selectElement) {
        const html = renderCourierInputs();
        $selectElement.after(html);

        const $cityInput = $('#meestCity');
        const $addressInput = $('#meestAddress');

        initCityAutocomplete($cityInput, function(cityItem) {
            $addressInput
                .prop('disabled', false)
                .attr('data-city-id', cityItem.value);

            const $container = $cityInput.closest('.meest2-container');
            toggleMapButton($container, true);
        });

        initStreetAutocomplete($addressInput, $cityInput);

        $addressInput.on('change blur', function() {
            const city = $cityInput.val();
            const address = $(this).val();
            const cityId = $cityInput.attr('data-city-id');

            if (city && address && address.trim().length > 0) {
                saveShippingData('meest2.courier', cityId, '', address, '');
                updateCheckoutFields(city, address);
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
        const $selectShipping = $(MEEST2_CONFIG.SELECTORS.SHIPPING_SELECT);
        const selectedValue = $selectShipping.val();

        console.log('[Meest2] Перевірка select:', selectedValue);

        // Проверяем что выбрано именно "meest2"
        if (selectedValue !== 'meest2') {
            // Удаляем контейнеры если выбран другой метод доставки
            $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();
            return;
        }

        // Удаляем предыдущие элементы Meest2
        $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();

        // По умолчанию показываем форму для warehouse
        // Если нужна другая логика - можно добавить дополнительный select или radio buttons
        handleBranchDelivery($selectShipping, 'warehouse');

        console.log('[Meest2] Форму ініціалізовано');
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
            success: function(branches) {
                if (branches.length === 0) {
                    $('#meest2-map').html(`<div class="meest2-loading">${MEEST2_TEXTS.alerts.noBranches}</div>`);
                    return;
                }

                renderMapWithBranches(branches);
            },
            error: function() {
                $('#meest2-map').html(`<div class="meest2-loading">${MEEST2_TEXTS.alerts.loadError}</div>`);
            }
        });
    }

    /**
     * Отрисовка карты с отделениями
     * @param {Array} branches - Массив отделений с координатами
     */
    function renderMapWithBranches(branches) {
        $('#meest2-map').html('');

        if (meest2Map) {
            meest2Map.remove();
            meest2Map = null;
            meest2Markers = [];
        }

        meest2Map = L.map('meest2-map').setView([50.4501, 30.5234], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(meest2Map);

        const bounds = [];
        branches.forEach(function(branch) {
            if (branch.latitude && branch.longitude) {
                const lat = parseFloat(branch.latitude);
                const lng = parseFloat(branch.longitude);
                bounds.push([lat, lng]);

                const marker = L.marker([lat, lng]).addTo(meest2Map);
                marker.bindPopup(createBranchPopup(branch));
                meest2Markers.push(marker);
            }
        });

        if (bounds.length > 0) {
            meest2Map.fitBounds(bounds, { padding: [50, 50] });
        }

        setTimeout(function() {
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
    window.selectBranch = function(branchId, branchAddress) {
        const $activeContainer = $('.meest2-container:visible').last();
        const type = $activeContainer.attr('data-meest').replace('-container', '');
        const $input = $activeContainer.find(`input[data-meest="${type}"]`);
        const $cityInput = $activeContainer.find('input[data-meest="city"]');

        $input.val(branchAddress).attr('value', branchAddress).attr('data-branch-id', branchId);

        const city = $cityInput.val();
        const cityId = $cityInput.attr('data-city-id');

        const shippingMethod = 'meest2.' + type;
        saveShippingData(shippingMethod, cityId, branchId, '', '');
        updateCheckoutFields(city, branchAddress);

        saveMeestDataToLocalStorage({
            deliveryType: type,
            cityName: city,
            cityId: cityId,
            branchAddress: branchAddress,
            branchId: branchId
        });

        if (cityId && branchId) {
            calculateAndUpdateShippingPrice(type, cityId, branchId);
        }

        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
    };

    // ========================================================================
    // КРИТИЧЕСКОЕ РЕШЕНИЕ: MutationObserver для перехвата создания списка
    // ========================================================================

    let currentCityInput = null;
    let currentCityCallback = null;

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

        setTimeout(function() {
            if (currentCityInput && currentCityInput.autocomplete) {
                currentCityInput.autocomplete('close');
            }
        }, 50);

        if (currentCityCallback) {
            currentCityCallback(itemData);
        }
    }

    /**
     * ФИНАЛЬНОЕ РЕШЕНИЕ: Глобальный делегированный обработчик для ВСЕХ .dropdown-menu li
     */
    function initDropdownHandler() {
        $(document).off('mousedown.meest2-dropdown click.meest2-dropdown');

        $(document).on('mousedown.meest2-dropdown click.meest2-dropdown', '.dropdown-menu li', function(e) {
            const $li = $(this);
            const $dropdown = $li.closest('.dropdown-menu');

            if (!$dropdown.is(':visible')) {
                return;
            }

            const dataValue = $li.attr('data-value');
            const $link = $li.find('a');
            const text = $link.text();

            if (!dataValue || !text) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const parts = text.split(',');
            const cityPart = parts[0] || '';
            const cityName = cityPart.replace(/^(місто|село|смт)\s+/i, '').trim();

            if (!cityName) {
                return;
            }

            if (!currentCityInput) {
                return;
            }

            const itemData = {
                city: cityName,
                value: dataValue,
                label: text
            };

            setCityFromItem(itemData);
            $dropdown.hide();

            return false;
        });
    }

    // ========================================================================
    // EVENT LISTENERS
    // ========================================================================

    /**
     * КРИТИЧЕСКИЙ ОБРАБОТЧИК: Прямой перехват кликов по dropdown-menu
     */
    $(document).on('mousedown click', '.dropdown-menu li[data-value]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const $li = $(this);
        const dataValue = $li.attr('data-value');
        const text = $li.find('a').text();

        if (!dataValue || !text) {
            return false;
        }

        let $targetInput = null;

        if (currentCityInput && currentCityInput.is(':focus')) {
            const cityName = text.split(',')[0].replace(/^(місто|село|смт)\s+/i, '').trim();

            currentCityInput
                .val(cityName)
                .attr('data-city-id', dataValue)
                .attr('data-address', cityName);

            if (currentCityCallback) {
                currentCityCallback({city: cityName, value: dataValue, label: text});
            }
        } else {
            const $visibleInputs = $('input[data-meest="warehouse"]:focus, input[data-meest="postomat"]:focus, input#meestAddress:focus');

            if ($visibleInputs.length > 0) {
                $targetInput = $visibleInputs.first();

                $targetInput
                    .val(text)
                    .attr('data-address', text);

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
     * Обработчик изменения select доставки
     */
    $(document).on('change', MEEST2_CONFIG.SELECTORS.SHIPPING_SELECT, function() {
        console.log('[Meest2] Select змінено:', $(this).val());
        setTimeout(function() {
            meest2();
        }, MEEST2_CONFIG.TIMEOUTS.SHIPPING_CHANGE);
    });

    /**
     * Обработчик открытия карты
     */
    $(document).on('click', '.meest2-map-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const $container = $btn.closest('.meest2-container');
        const type = $container.attr('data-meest').replace('-container', '');
        const cityId = $container.find('input[data-meest="city"]').attr('data-city-id');

        if (!cityId) {
            alert(MEEST2_TEXTS.alerts.selectCity);
            return;
        }

        if ($(MEEST2_CONFIG.SELECTORS.MAP_MODAL).length === 0) {
            $('body').append(renderMapModal());
        }

        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).addClass('active');
        loadBranchesOnMap(cityId, type, $container);
    });

    /**
     * Закрытие модального окна по кнопке
     */
    $(document).on('click', '#meest2-modal-close', function() {
        $(MEEST2_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
    });

    /**
     * Закрытие модального окна по клику вне его
     */
    $(document).on('click', MEEST2_CONFIG.SELECTORS.MAP_MODAL, function(e) {
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
        const $selectShipping = $(MEEST2_CONFIG.SELECTORS.SHIPPING_SELECT);
        const selectedValue = $selectShipping.val();

        console.log('[Meest2] Перевірка select при ініціалізації:', selectedValue);

        if (selectedValue === 'meest2') {
            $(MEEST2_CONFIG.SELECTORS.MEEST_CONTAINER).remove();
            meest2();
        }
    }

    /**
     * MutationObserver для отслеживания появления select доставки
     */
    function initShippingSelectObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        const $node = $(node);
                        const $selectShipping = $node.find(MEEST2_CONFIG.SELECTORS.SHIPPING_SELECT);

                        if ($node.is(MEEST2_CONFIG.SELECTORS.SHIPPING_SELECT)) {
                            setTimeout(function() {
                                checkAndInitializeIfSelected();
                            }, 200);
                        } else if ($selectShipping.length > 0) {
                            setTimeout(function() {
                                checkAndInitializeIfSelected();
                            }, 200);
                        }
                    }
                });
            });
        });

        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            document.addEventListener('DOMContentLoaded', function() {
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
        initDropdownHandler();
        initShippingSelectObserver();
        checkAndInitializeIfSelected();
    }

    if (typeof $.fn.autocomplete === 'function') {
        initMeest2Module();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            initMeest2Module();
        });
    }

    $(document).ready(function() {
        setTimeout(function() {
            checkAndInitializeIfSelected();
        }, 800);
    });

    $(window).on('load', function() {
        setTimeout(function() {
            checkAndInitializeIfSelected();
        }, 1000);
    });
}
