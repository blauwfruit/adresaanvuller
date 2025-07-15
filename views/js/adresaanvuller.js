/**
 *   AdresAanvuller
 *
 *   Do not copy, modify or distribute this document in any form.
 *
 *   @author     Matthijs <matthijs@blauwfruit.nl>
 *   @copyright  Copyright (c) 2013-2020 blauwfruit (http://blauwfruit.nl)
 *   @license    Proprietary Software
 *   @category   checkout
 *
 */

$(document).ready(function($) {
    AdresAanvuller();

    if (typeof prestashop !== 'undefined') {
        prestashop.on('updatedAddressForm',function(event) {
            AdresAanvuller();
        });
    }
});

function AdresAanvuller() {
    var $postcode = postcode_selector.length ? $(postcode_selector) : $('input[name=postcode_invoice]');
    var $address2 = address2_selector.length ? $(address2_selector) : $('input[name=address2_invoice]');
    var $address1 = address1_selector.length ? $(address1_selector) : $('input[name=address1_invoice]');
    var $city = city_selector.length ? $(city_selector) : $('input[name=city_invoice]');
    var $country = id_country_selector.length ? $(id_country_selector) : $('select[name=id_country_invoice] option:selected');

    $postcodeContainer = $postcode.parents('.form-group').first();
    $address1Container = $address1.parents('.form-group').first();

    if ($country.val() == netherlands) {
        if (address_format_type == 1) {
            var $postcodeClone = $postcodeContainer.clone();
            $address1Container.before($postcodeClone);
            var $address1Clone = $address1Container.clone();
            $postcodeContainer.before($address1Clone);

            $postcodeContainer.first().remove();
            $address1Container.first().remove();

            var $postcode = $(postcode_selector);
            var $address1 = $(address1_selector);
        }
    }

    obligatory = parseInt(obligatory);
    $postcode.on('blur', function() {
        $('.is_customer_param').show();
        complete();
    });
    $address2.on('blur', function() {
        complete();
    });

    function complete() {
        var $country = id_country_selector.length ? $(id_country_selector) : $('select[name=id_country_invoice] option:selected');

        if (obligatory && $country.val() == netherlands) {
            $address1.attr('readonly', true);
            $city.attr('readonly', true);
        } else {
            $address1.attr('readonly', false);
            $city.attr('readonly', false);
        }

        if ($postcode.val() !== '' && $address2.val() !== '' && $country.val() == netherlands) {
            $address1.attr('readonly', true);
            $city.attr('readonly', true);
            $.ajax({
                dataType: "json",
                method: 'GET',
                url: retrieve,
                data: {
                    postcode: $postcode.val(),
                    number: $address2.val()
                },
                success: function(data) {
                    if ($('#error_zipcode_not_found').length) {
                        $('#error_zipcode_not_found').hide();
                    }

                    if (data.success) {

                        $address1
                            .val(data.address.street)
                            .focus();

                        $city
                            .val(data.address.city)
                            .focus();

                        if (!obligatory) {
                            $address1.attr('readonly', false);
                            $city.attr('readonly', false);
                        }
                        if ($('#error_zipcode_not_found').length) {
                            $('#error_zipcode_not_found').hide();
                        }
                    } else {
                        if (data.limit_exceeded == true) {
                            $address1.attr('readonly', false);
                            $city.attr('readonly', false);
                        } else {
                            $address1.val('');
                            $city.val('');
                            if ($('#error_zipcode_not_found').length) {
                                $('#error_zipcode_not_found').text(data.message).show();
                            } else {
                                $html_message = '<span id="error_zipcode_not_found" class="alert alert-danger" style="display: none;">' + data.message + '</span>';
                                $address2.parents('.form-group').first().after($html_message);
                                $('#error_zipcode_not_found').slideDown();
                            }
                            if (!obligatory) {
                                $address1.attr('readonly', false);
                                $city.attr('readonly', false);
                            }
                        }

                    }
                },
                error: function(data) {
                    if (!obligatory) {
                        $address1.attr('readonly', false);
                        $city.attr('readonly', false);
                    }
                },
                complete: function() {
                    if (!obligatory) {
                        $address1.attr('readonly', false);
                        $city.attr('readonly', false);
                    }
                }
            });
        } else {
            if ($('#error_zipcode_not_found').length)
                $('#error_zipcode_not_found').hide();
        }
    }
}
