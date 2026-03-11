function showLoader(message) {
    const html = `
        <div class="bg_load"></div>
        <div class="loader">
            <div class="spinner"></div>
            <div class="message">${message}</div>
        </div>`;
    $('body').append(html);
    $(".bg_load").fadeIn("slow");
    $(".loader").fadeIn("slow");
}

function removeLoader() {
    $(".bg_load, .loader").fadeOut("slow", function () {
        $(this).remove();
    });
}

function updateBranches() {
    $.ajax({
        url: window.meest2Urls.importBranches,
        beforeSend: function() {
            showLoader('Branches are updating...');
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(window.meest2Urls.text_update_success);
                removeLoader();
            } else {
                alert('Error: ' + (response.error || 'Unknown error'));
                removeLoader();
            }
        },
        error: function(jqXHR, exception) {
            alert(getAjaxErrorMessage(jqXHR, exception));
            removeLoader();
        }
    });
}

function updateRegions() {
    if (confirm('This operation may take three hours. Are you sure you want to continue?')) {
        $.ajax({
            url: window.meest2Urls.importRegions,
            beforeSend: function () {
                showLoader('Regions are updating...');
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Regions updated successfully!');
                    removeLoader();
                } else {
                    alert('Error: ' + (response.error || 'Unknown error'));
                    removeLoader();
                }
            },
            error: function (jqXHR, exception) {
                alert(getAjaxErrorMessage(jqXHR, exception));
                removeLoader();
            }
        });
    }
}
function updateDistricts() {
    if (confirm('This operation may take three hours. Are you sure you want to continue?')) {
        $.ajax({
            url: window.meest2Urls.importDistricts,
            beforeSend: function () {
                showLoader('Districts are updating...');
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Districts updated successfully!');
                } else {
                    alert('Error: ' + (response.error || 'Unknown error'));
                }
                removeLoader();
            },
            error: function (jqXHR, exception) {
                alert(getAjaxErrorMessage(jqXHR, exception));
                removeLoader();
            }
        });
    }
}
function updateCities() {
    if (confirm('This operation may take some time. Are you sure you want to continue?')) {
        $.ajax({
            url: window.meest2Urls.importCity,
            type: 'GET',
            dataType: 'json',
            beforeSend: function () {
                showLoader('Cities are updating...');
            },
            success: function(response) {
                alert(response.success ? 'Cities updated successfully!' : 'Error: ' + response.error);
                removeLoader();
            },
            error: function(jqXHR, exception) {
                alert(getAjaxErrorMessage(jqXHR, exception));
                removeLoader();
            }
        });
    }
}

function updateStreets() {
    if (confirm('This operation may take some time. Are you sure you want to continue?')) {
        $.ajax({
            url: window.meest2Urls.importStreets,
            type: 'GET',
            beforeSend: function () {
                showLoader('Updating streets...');
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert(`${response.message}\nInserted: ${response.data.inserted}\nUpdated: ${response.data.updated}`);
                    removeLoader();
                } else {
                    alert('Error: ' + response.error);
                    removeLoader();
                }
            },
            error: function (jqXHR) {
                removeLoader();
                let errorMsg = 'An unexpected error occurred.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
                    errorMsg = jqXHR.responseJSON.error;
                } else if (jqXHR.responseText) {
                    errorMsg = `Server response: ${jqXHR.responseText}`;
                } else if (jqXHR.status) {
                    errorMsg = `Error ${jqXHR.status}: ${jqXHR.statusText}`;
                }
                alert(errorMsg);
            }
        });
    }
}

function getAjaxErrorMessage(jqXHR, exception) {
    if (jqXHR.status === 0) return 'Not connect. Verify Network.';
    if (jqXHR.status == 404) return 'Requested page not found (404).';
    if (jqXHR.status == 500) return 'Internal Server Error (500).';
    if (exception === 'parsererror') return 'Requested JSON parse failed.';
    if (exception === 'timeout') return 'Time out error.';
    if (exception === 'abort') return 'Ajax request aborted.';
    return 'Uncaught Error. ' + jqXHR.responseText;
}

$(document).ready(function() {
    $('#input-sender-city, #input-sender-address, #input-sender-branch').select2({
        placeholder: window.meest2Urls.text_select,
        allowClear: true,
        width: '100%',
        dropdownPosition: 'below'
    });

    $('#input-sender-region').change(function() {
        populateCities($(this).val());
    });

    $('#input-sender-city').change(function() {
        populateAddresses($(this).val());
        populateBranches($(this).val());
    });

    if (window.meest2Urls.shipping_meest2_sender_region) {
        populateCities(window.meest2Urls.shipping_meest2_sender_region);
    }
    if (window.meest2Urls.shipping_meest2_sender_city) {
        populateAddresses(window.meest2Urls.shipping_meest2_sender_city);
    }

    $('#input-auth-mode').change(function() {
        if ($(this).val() === 'default') {
            $('#auth-default').show();
            $('#auth-api-key').hide();
        } else {
            $('#auth-default').hide();
            $('#auth-api-key').show();
        }
    }).trigger('change');

    $('#button-add-contract').on('click', function() {
        const contract_id = $('#input-contract-id').val();
        if (contract_id) {
            $.post(window.meest2Urls.addContract, { contract_id }, function(json) {
                if (json.success) location.reload();
                else if (json.error) alert(json.error);
            }, 'json');
        } else {
            alert(window.meest2Urls.text_error_contract_id);
        }
    });

    $('.btn-delete-contract').on('click', function() {
        const contract_id = $(this).data('contract-id');
        if (confirm(window.meest2Urls.text_confirm_delete)) {
            $.post(window.meest2Urls.deleteContract, { contract_id }, function(json) {
                if (json.success) location.reload();
                else if (json.error) alert(json.error);
            }, 'json');
        }
    });

    $('#button-add-contact').on('click', function() {
        const phone = $('#input-phone').val();
        const firstname = $('#input-firstname').val();
        const lastname = $('#input-lastname').val();
        const middlename = $('#input-middlename').val();

        if (phone && firstname && lastname && middlename) {
            $.post(window.meest2Urls.addContact, { phone, firstname, lastname, middlename }, function(json) {
                if (json.success) location.reload();
                else if (json.error) alert(json.error);
            }, 'json');
        } else {
            alert(window.meest2Urls.text_error_fill_fields);
        }
    });

    $('.btn-delete-contact').on('click', function() {
        const contact_id = $(this).data('contact-id');
        if (confirm(window.meest2Urls.text_confirm_delete)) {
            $.post(window.meest2Urls.deleteContact, { contact_id }, function(json) {
                if (json.success) location.reload();
                else if (json.error) alert(json.error);
            }, 'json');
        }
    });
});

function populateCities(region_id) {
    $.get(window.meest2Urls.ajax_get_cities_url, { region_id }, function(data) {
        const citySelect = $('#input-sender-city');
        citySelect.empty().append(`<option value="">${window.meest2Urls.text_select}</option>`);
        $.each(data, function(_, city) {
            citySelect.append(`<option value="${city.city_id}">${city.name_ua}</option>`);
        });
        citySelect.val(window.meest2Urls.shipping_meest2_sender_city).trigger('change');
    }, 'json');
}

function populateAddresses(city_id) {
    $.get(window.meest2Urls.ajax_get_addresses_url, { city_id }, function(data) {
        const addressSelect = $('#input-sender-address');
        addressSelect.empty().append(`<option value="">${window.meest2Urls.text_select}</option>`);
        $.each(data, function(_, address) {
            addressSelect.append(`<option value="${address.street_id}">${address.name_ua}</option>`);
        });
        addressSelect.val(window.meest2Urls.shipping_meest2_sender_address).trigger('change');
    }, 'json');
}

function populateBranches(city_id) {
    $.get(window.meest2Urls.ajax_get_branches_url, { city_id }, function(data) {
        const branchSelect = $('#input-sender-branch');
        branchSelect.empty().append(`<option value="">${window.meest2Urls.text_select}</option>`);
        $.each(data, function(_, branch) {
            branchSelect.append(`<option value="${branch.branch_id}">${branch.short_name} (${branch.address_more_information})</option>`);
        });
        branchSelect.val(window.meest2Urls.shipping_meest2_sender_branch).trigger('change');
    }, 'json');
}
