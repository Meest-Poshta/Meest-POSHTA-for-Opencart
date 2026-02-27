// Version: 1768481979
/**
 * meest_express Checkout Integration Module
 * Модуль интеграции доставки meest_express для OpenCart
 *
 * @author meest_express Team
 * @version 2.0 (Optimized)
 */

// ============================================================================
// ДІАГНОСТИКА jQuery UI + ЗБЕРЕЖЕННЯ ПОСИЛАННЯ
// ============================================================================

// КРИТИЧНО: Зберігаємо посилання на autocomplete ДО того як SimpleCheckout його видалить
if (!window.meest_expressSavedAutocomplete && jQuery && jQuery.fn && jQuery.fn.autocomplete) {
    window.meest_expressSavedAutocomplete = jQuery.fn.autocomplete;
}

// Функція відновлення autocomplete якщо його видалили
window.meest_expressRestoreAutocomplete = function () {
    if (window.meest_expressSavedAutocomplete && jQuery && jQuery.fn && !jQuery.fn.autocomplete) {
        jQuery.fn.autocomplete = window.meest_expressSavedAutocomplete;
        return true;
    }
    return false;
};

// ============================================================================
// ГЛОБАЛЬНАЯ ИНИЦИАЛИЗАЦИЯ
// ============================================================================

if (!window.meest_expressLoaded) {
    window.meest_expressLoaded = true;

    // Глобальна мапа для зберігання адреса → UUID
    window.meest_expressBranchMap = {};
    window.meest_expressAddressMap = {};

    // ========================================================================
    // КОНСТАНТЫ И КОНФИГУРАЦИЯ
    // ========================================================================

    const meest_express_CONFIG = {
        // Типы доставки
        DELIVERY_TYPES: {
            COURIER: 'meest_express.courier',
            POSTOMAT: 'meest_express.postomat',
            WAREHOUSE: 'meest_express.warehouse'
        },

        // Мапинг типов для API
        SERVICE_MAPPING: {
            'warehouse': 'Branch',
            'postomat': 'Branch',
            'courier': 'Door'
        },

        // API endpoints
        API_ENDPOINTS: {
            GET_DATA: 'index.php?route=extension/MeestExpress/shipping/meest_express.getMeestData',
            SAVE_ADDRESS: 'index.php?route=extension/MeestExpress/shipping/meest_express.save',
            CALCULATE_COST: 'index.php?route=extension/MeestExpress/shipping/meest_express.calculateShippingCost',
            GET_BRANCHES: 'index.php?route=extension/MeestExpress/shipping/meest_express.getBranchesWithCoordinates'
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
            MAP_MODAL: '#meest_express-map-modal'
        },

        // Таймауты
        TIMEOUTS: {
            SHIPPING_CHANGE: 1200,
            MAP_RESIZE: 100
        }
    };

    // Тексты для UI (можно вынести в локализацию)
    const meest_express_TEXTS = {
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
    let meest_expressMap = null;
    let meest_expressMarkers = [];

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
     * Збереження даних meest_express в localStorage
     */
    function saveMeestDataToLocalStorage(data) {
        try {
            localStorage.setItem('meest_express_selected_data', JSON.stringify(data));
        } catch (e) {
            console.error('[meest_express] Помилка збереження в localStorage:', e);
        }
    }

    /**
     * Отримання даних meest_express з localStorage
     */
    function getMeestDataFromLocalStorage() {
        try {
            const data = localStorage.getItem('meest_express_selected_data');
            return data ? JSON.parse(data) : null;
        } catch (e) {
            console.error('[meest_express] Помилка читання з localStorage:', e);
            return null;
        }
    }

    /**
     * Очистка даних meest_express з localStorage
     */
    function clearMeestDataFromLocalStorage() {
        try {
            localStorage.removeItem('meest_express_selected_data');
        } catch (e) {
            console.error('[meest_express] Помилка очистки localStorage:', e);
        }
    }

    /**
     * Відновлення полів форми з localStorage
     */
    function restoreMeestDataFromLocalStorage() {
        const savedData = getMeestDataFromLocalStorage();
        if (!savedData) return false;


        // Відновлюємо поле міста
        const $cityInput = $(meest_express_CONFIG.SELECTORS.CITY_INPUT);
        if (savedData.cityName && savedData.cityId) {
            $cityInput.val(savedData.cityName).attr('data-city-id', savedData.cityId);
        }

        // Відновлюємо поле відділення/адреси
        let $deliveryInput;
        if (savedData.deliveryType === 'warehouse' || savedData.deliveryType === 'postomat') {
            $deliveryInput = $(`[data-meest="${savedData.deliveryType}"]`);
            if (savedData.branchAddress && savedData.branchId) {
                $deliveryInput
                    .val(savedData.branchAddress)
                    .attr('data-branch-id', savedData.branchId)
                    .attr('data-address', savedData.branchAddress);
            }
        } else if (savedData.deliveryType === 'courier') {
            $deliveryInput = $('[data-meest="street"]');
            if (savedData.streetAddress && savedData.addressId) {
                $deliveryInput
                    .val(savedData.streetAddress)
                    .attr('data-address-id', savedData.addressId);
            }
        }

        // Приховуємо форму і показуємо summary
        showMeestSummary(savedData);
        return true;
    }

    /**
     * Показує summary з вибраними даними і кнопкою "Змінити"
     */
    function showMeestSummary(data) {
        const $container = $('.meest_express-container');

        // Ховаємо форму
        $container.find('.meest_express-field-wrapper').hide();

        // Видаляємо старий summary якщо є
        $container.find('.meest_express-summary').remove();

        // Створюємо summary блок
        let summaryHtml = '<div class="meest_express-summary" style="padding: 15px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px;">';
        summaryHtml += '<h4 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold;">Обрана доставка Meest:</h4>';
        summaryHtml += '<p style="margin: 5px 0;"><strong>Місто:</strong> ' + (data.cityName || '') + '</p>';

        if (data.deliveryType === 'warehouse' || data.deliveryType === 'postomat') {
            summaryHtml += '<p style="margin: 5px 0;"><strong>Відділення:</strong> ' + (data.branchAddress || '') + '</p>';
        } else if (data.deliveryType === 'courier') {
            summaryHtml += '<p style="margin: 5px 0;"><strong>Адреса:</strong> ' + (data.streetAddress || '') + '</p>';
        }

        summaryHtml += '<button type="button" class="btn btn-primary meest_express-change-btn" style="margin-top: 10px;">Змінити адресу доставки</button>';
        summaryHtml += '</div>';

        $container.prepend(summaryHtml);

        // Обробник кнопки "Змінити"
        $container.find('.meest_express-change-btn').on('click', function () {
            clearMeestDataFromLocalStorage();
            $container.find('.meest_express-summary').remove();
            $container.find('.meest_express-field-wrapper').show();
        });

    }

    /**
     * Сохраняет адрес на сервере
     * @param {string} shippingMethod - Метод доставки (meest_express.warehouse, meest_express.postomat, meest_express.courier)
     * @param {string} cityCode - UUID города
     * @param {string} branchCode - UUID отделения
     * @param {string} addressCode - UUID адреса
     * @param {string} regionCode - UUID региона
     */
    function saveShippingData(shippingMethod, cityCode, branchCode, addressCode, regionCode, building) {
        $.ajax({
            url: 'index.php?route=extension/MeestExpress/shipping/meest_express.saveMeestSessionData',
            type: 'post',
            data: {
                shipping_method: shippingMethod || '',
                city_code: cityCode || '',
                branch_code: branchCode || '',
                address_code: addressCode || '',
                region_code: regionCode || '',
                building: building || ''
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
        const $mapBtn = $container.find('.meest_express-map-btn');
        $mapBtn.toggleClass('hidden', !show);
    }

    /**
     * Получает тип сервиса из строки (например, 'meest_express.warehouse' -> 'warehouse')
     * @param {string} fullServiceName - Полное название сервиса
     * @returns {string|null} - Короткое название или null если не передано значение
     */
    function getServiceType(fullServiceName) {
        if (!fullServiceName || typeof fullServiceName !== 'string') {
            return null;
        }
        return fullServiceName.replace('meest_express.', '');
    }

    // ========================================================================
    // AJAX ФУНКЦІЇ
    // ========================================================================

    /**
     * Загрузка данных с API meest_express
     * @param {string} action - Тип действия (getCities, getBranches, etc.)
     * @param {object} params - Дополнительные параметры
     * @returns {Promise}
     */
    function fetchMeestData(action, params = {}) {
        return $.ajax({
            url: meest_express_CONFIG.API_ENDPOINTS.GET_DATA,
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

        const receiverService = meest_express_CONFIG.SERVICE_MAPPING[service] || 'Branch';
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

        const $priceElement = $(`#meest_express-price-${service}`);
        if (!$priceElement.length) return;

        const originalText = $priceElement.text();
        $priceElement.html(`<span style="opacity: 0.5;">${meest_express_TEXTS.alerts.calculating}</span>`);

        $.ajax({
            url: meest_express_CONFIG.API_ENDPOINTS.CALCULATE_COST,
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

        // Маркируем элемент как инициализированный meest_express и добавляем уникальный класс
        $input.attr('data-meest-autocomplete', 'true').addClass('meest_express-city-autocomplete');

        // Отложенная инициализация чтобы обойти конфликт с common.js
        setTimeout(function () {
            if (!$input.closest('html').length) {
                return;
            }

            destroyAutocomplete($input);

            // Отключаем autocomplete от common.js окончательно
            $input.off('.autocomplete');

            // КРИТИЧНО: Відновлюємо autocomplete якщо SimpleCheckout його видалив
            window.meest_expressRestoreAutocomplete();

            // Проверяем что jQuery UI autocomplete доступен
            if (typeof $.fn.autocomplete !== 'function') {
                console.error('[ERROR] jQuery UI autocomplete is NOT available!');
                return;
            }


            // Переменные для ручного dropdown
            let $dropdown = null;
            let debounceTimer = null;

            $input.attr('autocomplete', 'off');

            // Обработчик ввода текста
            $input.on('input.meest-city', function () {
                const searchTerm = $(this).val();

                clearTimeout(debounceTimer);

                // Удаляем dropdown если текст слишком короткий
                if (!searchTerm || searchTerm.length < meest_express_CONFIG.AUTOCOMPLETE.MIN_LENGTH) {
                    if ($dropdown) {
                        $dropdown.remove();
                        $dropdown = null;
                    }
                    return;
                }

                // Debounce
                debounceTimer = setTimeout(function () {

                    fetchMeestData('getCities', { search: searchTerm })
                        .done(function (json) {

                            // Удаляем старый dropdown
                            if ($dropdown) {
                                $dropdown.remove();
                            }

                            // Создаем новый dropdown
                            $dropdown = $('<ul class="dropdown-menu"></ul>');
                            $dropdown.css({
                                display: 'block',
                                position: 'absolute',
                                zIndex: 10000,
                                maxHeight: '300px',
                                overflowY: 'auto',
                                background: 'white',
                                border: '1px solid #ddd',
                                borderRadius: '4px',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                                padding: 0,
                                margin: 0,
                                listStyle: 'none'
                            });

                            // Добавляем элементы
                            $.each(json, function (index, item) {
                                const label = `${item.type} ${item.name}, ${item.region} обл ( ${item.district} р-н)`;
                                const $li = $('<li data-value="' + item.id + '"></li>');
                                $li.css({ padding: 0, margin: 0, listStyle: 'none' });

                                const $a = $('<a href="#"></a>');
                                $a.text(label);
                                $a.css({
                                    display: 'block',
                                    padding: '10px 15px',
                                    color: '#333',
                                    textDecoration: 'none',
                                    cursor: 'pointer'
                                });

                                // Hover эффект
                                $a.on('mouseenter', function () {
                                    $(this).css('background', '#f0f0f0');
                                });
                                $a.on('mouseleave', function () {
                                    $(this).css('background', 'white');
                                });

                                // Обработчик клика
                                $a.on('click', function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();


                                    $input.val(item.name);
                                    $input.attr('data-city-id', item.id);
                                    $input.attr('data-address', item.name);

                                    if ($dropdown) {
                                        $dropdown.remove();
                                        $dropdown = null;
                                    }

                                    if (onSelectCallback) {
                                        onSelectCallback({
                                            city: item.name,
                                            value: item.id,
                                            label: label
                                        });
                                    }
                                });

                                $li.append($a);
                                $dropdown.append($li);
                            });

                            // Позиционируем dropdown
                            const offset = $input.offset();
                            $dropdown.css({
                                top: offset.top + $input.outerHeight(),
                                left: offset.left,
                                width: $input.outerWidth()
                            });

                            $('body').append($dropdown);
                        })
                        .fail(function () {
                            console.error('[DEBUG] Failed to fetch cities');
                        });
                }, meest_express_CONFIG.AUTOCOMPLETE.DELAY);
            });

            // Закрываем dropdown при клике вне его
            $(document).on('click.meest-city-autocomplete', function (e) {
                if (!$(e.target).closest($input).length && $dropdown && !$(e.target).closest($dropdown).length) {
                    $dropdown.remove();
                    $dropdown = null;
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

        // КРИТИЧНО: Відновлюємо autocomplete якщо SimpleCheckout його видалив
        window.meest_expressRestoreAutocomplete();


        let $dropdown = null;
        let debounceTimer = null;

        $input.attr('autocomplete', 'off');

        $input.on('input.meest-branch', function () {
            const searchTerm = $(this).val();

            clearTimeout(debounceTimer);

            const cityId = $(meest_express_CONFIG.SELECTORS.CITY_INPUT).attr('data-city-id');

            if (!cityId || !searchTerm || searchTerm.length < meest_express_CONFIG.AUTOCOMPLETE.MIN_LENGTH) {
                if ($dropdown) {
                    $dropdown.remove();
                    $dropdown = null;
                }
                return;
            }

            debounceTimer = setTimeout(function () {
                const action = serviceType === 'postomat' ? 'getPoshtomat' : 'getBranches';

                fetchMeestData(action, { filter: cityId, search: searchTerm })
                    .done(function (json) {

                        // Зберігаємо мапу
                        $.each(json, function (index, item) {
                            window.meest_expressBranchMap[item.description] = item.id;
                        });

                        if ($dropdown) $dropdown.remove();

                        $dropdown = $('<ul class="dropdown-menu"></ul>').css({
                            display: 'block', position: 'absolute', zIndex: 10000,
                            maxHeight: '300px', overflowY: 'auto', background: 'white',
                            border: '1px solid #ddd', borderRadius: '4px',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.15)', padding: 0, margin: 0
                        });

                        $.each(json, function (i, item) {
                            const $li = $('<li data-value="' + item.id + '"></li>');
                            const $a = $('<a href="#">' + item.description + '</a>').css({
                                display: 'block', padding: '10px 15px', color: '#333', textDecoration: 'none'
                            });

                            $a.hover(
                                function () { $(this).css('background', '#f0f0f0'); },
                                function () { $(this).css('background', 'white'); }
                            );

                            $a.on('click', function (e) {
                                e.preventDefault();

                                $input.val(item.description);
                                $input.attr('data-address', item.description);
                                $input.attr('data-branch-id', item.id);
                                $input.trigger('change');

                                const city = $(meest_express_CONFIG.SELECTORS.CITY_INPUT).val();
                                const cityId = $(meest_express_CONFIG.SELECTORS.CITY_INPUT).attr('data-city-id');

                                const shippingMethod = 'meest_express.' + serviceType;
                                saveShippingData(shippingMethod, cityId, item.id, '', '');
                                updateCheckoutFields(city, item.description);
                                $('#input-shipping-address-1').val(item.description);

                                saveMeestDataToLocalStorage({
                                    deliveryType: serviceType,
                                    cityName: city,
                                    cityId: cityId,
                                    branchAddress: item.description,
                                    branchId: item.id
                                });

                                if (cityId && item.id) {
                                    calculateAndUpdateShippingPrice(serviceType, cityId, item.id);
                                }

                                if ($dropdown) {
                                    $dropdown.remove();
                                    $dropdown = null;
                                }
                            });

                            $li.append($a);
                            $dropdown.append($li);
                        });

                        const offset = $input.offset();
                        $dropdown.css({
                            top: offset.top + $input.outerHeight(),
                            left: offset.left,
                            width: $input.outerWidth()
                        });

                        $('body').append($dropdown);
                    })
                    .fail(function () {
                        console.error('[DEBUG] Failed to fetch branches');
                    });
            }, meest_express_CONFIG.AUTOCOMPLETE.DELAY);
        });

        $(document).on('click.meest-branch-autocomplete', function (e) {
            if (!$(e.target).closest($input).length && $dropdown && !$(e.target).closest($dropdown).length) {
                $dropdown.remove();
                $dropdown = null;
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

            // КРИТИЧНО: Відновлюємо autocomplete якщо SimpleCheckout його видалив
            window.meest_expressRestoreAutocomplete();


            let $dropdown = null;
            let debounceTimer = null;

            $input.attr('autocomplete', 'off');

            $input.on('input.meest-street', function () {
                const searchTerm = $(this).val();

                clearTimeout(debounceTimer);

                const cityId = $input.attr('data-city-id');

                if (!cityId || !searchTerm || searchTerm.length < meest_express_CONFIG.AUTOCOMPLETE.MIN_LENGTH) {
                    if ($dropdown) {
                        $dropdown.remove();
                        $dropdown = null;
                    }
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetchMeestData('getStreets', { filter: cityId, search: searchTerm })
                        .done(function (json) {

                            // Зберігаємо мапу
                            $.each(json, function (index, item) {
                                window.meest_expressAddressMap[item.description] = item.id;
                            });

                            if ($dropdown) $dropdown.remove();

                            $dropdown = $('<ul class="dropdown-menu"></ul>').css({
                                display: 'block', position: 'absolute', zIndex: 10000,
                                maxHeight: '300px', overflowY: 'auto', background: 'white',
                                border: '1px solid #ddd', borderRadius: '4px',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.15)', padding: 0, margin: 0
                            });

                            $.each(json, function (i, item) {
                                const $li = $('<li data-value="' + item.id + '"></li>');
                                const $a = $('<a href="#">' + item.description + '</a>').css({
                                    display: 'block', padding: '10px 15px', color: '#333', textDecoration: 'none'
                                });

                                $a.hover(
                                    function () { $(this).css('background', '#f0f0f0'); },
                                    function () { $(this).css('background', 'white'); }
                                );

                                $a.on('click', function (e) {
                                    e.preventDefault();

                                    $input.val(item.description);
                                    $input.attr('data-value', item.description);
                                    $input.attr('data-address-id', item.id);
                                    $input.trigger('change');

                                    const city = $cityInput.val();
                                    const cityId = $cityInput.attr('data-city-id') || $input.attr('data-city-id');

                                    saveShippingData('meest_express.courier', cityId, '', item.id, '', $('#meestBuilding').val() || '');
                                    updateCheckoutFields(city, item.description);
                                    $('#input-shipping-address-1').val(item.description);

                                    if (cityId && item.id) {
                                        calculateAndUpdateShippingPrice('courier', cityId, item.id);
                                    }

                                    if ($dropdown) {
                                        $dropdown.remove();
                                        $dropdown = null;
                                    }
                                });

                                $li.append($a);
                                $dropdown.append($li);
                            });

                            const offset = $input.offset();
                            $dropdown.css({
                                top: offset.top + $input.outerHeight(),
                                left: offset.left,
                                width: $input.outerWidth()
                            });

                            $('body').append($dropdown);
                        })
                        .fail(function () {
                            console.error('[DEBUG] Failed to fetch streets');
                        });
                }, meest_express_CONFIG.AUTOCOMPLETE.DELAY);
            });

            $(document).on('click.meest-street-autocomplete', function (e) {
                if (!$(e.target).closest($input).length && $dropdown && !$(e.target).closest($dropdown).length) {
                    $dropdown.remove();
                    $dropdown = null;
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
            <div class="data-meest meest_express-container" data-meest="${type}-container">
                <div class="meest_express-field-wrapper">
                    <div class="meest_express-input-group">
                        <label>${cityPlaceholder}</label>
                        <input type="text" class="form-control" data-meest="city" placeholder="${cityPlaceholder}" autocomplete="off"/>
                    </div>
                </div>
                <div class="meest_express-field-wrapper">
                    <div class="meest_express-input-group">
                        <label>${branchPlaceholder}</label>
                        <input disabled type="text" class="form-control" data-meest="${type}" placeholder="${branchPlaceholder}"/>
                    </div>
                    <a href="#" class="meest_express-map-btn hidden" id="meest-map-link-${type}" 
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
            <div class="data-meest meest_express-container" data-meest="courier-container">
                <div class="meest_express-field-wrapper">
                    <div class="meest_express-input-group">
                        <label>${meest_express_TEXTS.courier.city}</label>
                        <input id="meestCity" type="text" class="form-control" placeholder="${meest_express_TEXTS.courier.city}" autocomplete="off"/>
                    </div>
                </div>
                <div class="meest_express-field-wrapper">
                    <div class="meest_express-input-group">
                        <label>${meest_express_TEXTS.courier.address}</label>
                        <input disabled id="meestAddress" type="text" class="form-control" placeholder="${meest_express_TEXTS.courier.address}"/>
                    </div>
                </div>
                 <div class="meest_express-field-wrapper">
                    <div class="meest_express-input-group">
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
            <div id="meest_express-map-modal" class="meest_express-modal">
                <div class="meest_express-modal-content">
                    <div class="meest_express-modal-header">
                        <h3>Мапа</h3>
                        <button class="meest_express-modal-close" id="meest_express-modal-close">&times;</button>
                    </div>
                    <div class="meest_express-modal-body">
                        <div id="meest_express-map"></div>
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
        const texts = meest_express_TEXTS[type];
        const html = renderBranchInputs(type, texts.branch, texts.city);

        $activeShip.parent().after(html);

        const $container = $(`.meest_express-container[data-meest="${type}-container"]`);
        const $cityInput = $container.find('input[data-meest="city"]');
        const $branchInput = $container.find(`input[data-meest="${type}"]`);

        // Инициализация autocomplete для города
        initCityAutocomplete($cityInput, function (cityItem) {
            const $mapBtn = $container.find('.meest_express-map-btn');
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
            if (!branchId && address && window.meest_expressBranchMap) {
                branchId = window.meest_expressBranchMap[address];
                if (branchId) {
                    $(this).attr('data-branch-id', branchId);
                }
            }

            if (city && address && address.trim().length > 0) {
                $('#input-shipping-address-1').val(address);
                const shippingMethod = 'meest_express.' + type;
                saveShippingData(shippingMethod, cityId, branchId || '', '', '');
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

            const $container = $cityInput.closest('.meest_express-container');
            toggleMapButton($container, true);
        });

        // Инициализация autocomplete для улиц
        initStreetAutocomplete($addressInput, $cityInput);

        // Обработка изменения адреса (множественные события для надежности)
        $addressInput.on('change blur', function () {
            const city = $cityInput.val();
            const address = $(this).val();
            const cityId = $cityInput.attr('data-city-id') || $(this).attr('data-city-id');
            let addressId = $(this).attr('data-address-id') || '';

            // WORKAROUND: Шукаємо UUID по тексту адреси в глобальній мапі
            if (!addressId && address && window.meest_expressAddressMap && window.meest_expressAddressMap[address]) {
                addressId = window.meest_expressAddressMap[address];
                $(this).attr('data-address-id', addressId);
            }

            const building = $('#meestBuilding').val() || '';

            if (city && address && address.trim().length > 0) {
                $('#input-shipping-address-1').val(address);
                // Для кур'єра передаємо UUID адреси в address_code
                saveShippingData('meest_express.courier', cityId, '', addressId, '', building);
                updateCheckoutFields(null, address);
            }
        });

        // Обработка изменения поля building
        const $buildingInput = $('#meestBuilding');
        $buildingInput.on('change blur', function () {
            const city = $cityInput.val();
            const address = $addressInput.val();
            const cityId = $cityInput.attr('data-city-id') || $addressInput.attr('data-city-id');
            const addressId = $addressInput.attr('data-address-id') || '';
            const building = $(this).val() || '';

            if (city && address && address.trim().length > 0) {
                saveShippingData('meest_express.courier', cityId, '', addressId, '', building);
            }
        });
    }

    // ========================================================================
    // ГЛАВНАЯ ФУНКЦИЯ ИНИЦИАЛИЗАЦИИ
    // ========================================================================

    /**
     * Основная функция инициализации meest_express
     */
    function meest_express() {
        const $activeShip = $('input[type="radio"][name="shipping_method"]:checked');
        const meestService = $activeShip.val();

        // Проверяем что метод доставки выбран и определен
        if (!meestService) {
            return;
        }

        // Проверяем, является ли выбранный метод доставкой meest_express
        const deliveryTypes = Object.values(meest_express_CONFIG.DELIVERY_TYPES);
        if (deliveryTypes.indexOf(meestService) === -1) {
            return;
        }

        // Удаляем предыдущие элементы meest_express
        $(meest_express_CONFIG.SELECTORS.MEEST_CONTAINER).remove();

        // Инициализация в зависимости от типа доставки
        switch (meestService) {
            case meest_express_CONFIG.DELIVERY_TYPES.POSTOMAT:
                handleBranchDelivery($activeShip, 'postomat');
                break;

            case meest_express_CONFIG.DELIVERY_TYPES.WAREHOUSE:
                handleBranchDelivery($activeShip, 'warehouse');
                break;

            case meest_express_CONFIG.DELIVERY_TYPES.COURIER:
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
        $('#meest_express-map').html(`<div class="meest_express-loading">${meest_express_TEXTS.alerts.loading}</div>`);

        $.ajax({
            url: meest_express_CONFIG.API_ENDPOINTS.GET_BRANCHES,
            type: 'POST',
            data: { city_id: cityId, type: type },
            dataType: 'json',
            success: function (branches) {
                if (branches.length === 0) {
                    $('#meest_express-map').html(`<div class="meest_express-loading">${meest_express_TEXTS.alerts.noBranches}</div>`);
                    return;
                }

                renderMapWithBranches(branches);
            },
            error: function () {
                $('#meest_express-map').html(`<div class="meest_express-loading">${meest_express_TEXTS.alerts.loadError}</div>`);
            }
        });
    }

    /**
     * Отрисовка карты с отделениями
     * @param {Array} branches - Массив отделений с координатами
     */
    function renderMapWithBranches(branches) {
        // Очищаем контейнер и удаляем старую карту
        $('#meest_express-map').html('');

        if (meest_expressMap) {
            meest_expressMap.remove();
            meest_expressMap = null;
            meest_expressMarkers = [];
        }

        // Создаем новую карту
        meest_expressMap = L.map('meest_express-map').setView([50.4501, 30.5234], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(meest_expressMap);

        // Добавляем маркеры и собираем границы
        const bounds = [];
        branches.forEach(function (branch) {
            if (branch.latitude && branch.longitude) {
                const lat = parseFloat(branch.latitude);
                const lng = parseFloat(branch.longitude);
                bounds.push([lat, lng]);

                const marker = L.marker([lat, lng]).addTo(meest_expressMap);
                marker.bindPopup(createBranchPopup(branch));
                meest_expressMarkers.push(marker);
            }
        });

        // Центрируем карту по маркерам
        if (bounds.length > 0) {
            meest_expressMap.fitBounds(bounds, { padding: [50, 50] });
        }

        // Обновляем размер карты после загрузки
        setTimeout(function () {
            meest_expressMap.invalidateSize();
        }, meest_express_CONFIG.TIMEOUTS.MAP_RESIZE);
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
        const $activeContainer = $('.meest_express-container:visible').last();
        const type = $activeContainer.attr('data-meest').replace('-container', '');
        const $input = $activeContainer.find(`input[data-meest="${type}"]`);
        const $cityInput = $activeContainer.find('input[data-meest="city"]');

        // Заполняем поле отделения
        $input.val(branchAddress).attr('value', branchAddress).attr('data-branch-id', branchId);

        const city = $cityInput.val();
        const cityId = $cityInput.attr('data-city-id');

        // Сохраняем в сессию (не в БД, т.к. заказ еще не создан)
        const shippingMethod = 'meest_express.' + type;
        $.ajax({
            url: 'index.php?route=extension/MeestExpress/shipping/meest_express.saveMeestSessionData',
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

        // Зберігаємо в localStorage
        saveMeestDataToLocalStorage({
            deliveryType: type,
            cityName: city,
            cityId: cityId,
            branchAddress: branchAddress,
            branchId: branchId
        });

        // Расчет цены доставки
        if (cityId && branchId) {
            calculateAndUpdateShippingPrice(type, cityId, branchId);
        }

        // Закрываем модальное окно
        $(meest_express_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
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
        $(document).off('mousedown.meest_express-dropdown click.meest_express-dropdown');

        // Глобальный делегированный обработчик на все клики по .dropdown-menu li
        $(document).on('mousedown.meest_express-dropdown click.meest_express-dropdown', '.dropdown-menu li', function (e) {
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
            const cityName = text.split(',')[0].replace(/^(місто|село|смт)\s+/i, '').trim();

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
                const $container = $targetInput.closest('.meest_express-container');
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
    $(document).on('change', meest_express_CONFIG.SELECTORS.SHIPPING_METHOD, function () {
        const self = this;
        setTimeout(function () {
            const val = $(self).val();
            $(meest_express_CONFIG.SELECTORS.MEEST_CONTAINER).remove();

            if (val.indexOf('meest_express.') !== -1) {
                meest_express();
            }
        }, meest_express_CONFIG.TIMEOUTS.SHIPPING_CHANGE);
    });

    /**
     * Обработчик открытия карты
     */
    $(document).on('click', '.meest_express-map-btn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const $container = $btn.closest('.meest_express-container');
        const type = $container.attr('data-meest').replace('-container', '');
        const cityId = $container.find('input[data-meest="city"]').attr('data-city-id');

        if (!cityId) {
            alert(meest_express_TEXTS.alerts.selectCity);
            return;
        }

        // Создаем модальное окно если его нет
        if ($(meest_express_CONFIG.SELECTORS.MAP_MODAL).length === 0) {
            $('body').append(renderMapModal());
        }

        // Показываем модальное окно и загружаем отделения
        $(meest_express_CONFIG.SELECTORS.MAP_MODAL).addClass('active');
        loadBranchesOnMap(cityId, type, $container);
    });

    /**
     * Закрытие модального окна по кнопке
     */
    $(document).on('click', '#meest_express-modal-close', function () {
        $(meest_express_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
    });

    /**
     * Закрытие модального окна по клику вне его
     */
    $(document).on('click', meest_express_CONFIG.SELECTORS.MAP_MODAL, function (e) {
        if (e.target.id === 'meest_express-map-modal') {
            $(meest_express_CONFIG.SELECTORS.MAP_MODAL).removeClass('active');
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

        if (selectedMethod && selectedMethod.indexOf('meest_express.') !== -1) {
            // Удаляем старые контейнеры перед инициализацией
            $(meest_express_CONFIG.SELECTORS.MEEST_CONTAINER).remove();
            meest_express();

            // Відновлюємо дані з localStorage після ініціалізації
            setTimeout(function () {
                restoreMeestDataFromLocalStorage();
            }, 500);
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
    function initmeest_expressModule() {
        // Инициализируем глобальный обработчик для dropdown-menu
        initDropdownHandler();

        // Запускаем наблюдатель за появлением блока доставки
        initShippingMethodObserver();

        // Проверяем и инициализируем если метод уже выбран
        checkAndInitializeIfSelected();
    }

    // Первичная инициализация
    if (typeof $.fn.autocomplete === 'function') {
        initmeest_expressModule();
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            initmeest_expressModule();
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
