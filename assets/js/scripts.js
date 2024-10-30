
jQuery(document).on('click', 'a.updateCal', function(e) { 

    e.preventDefault();

    jQuery('#holiday-occupation').addClass('js-loading');

    jQuery.ajax({ 
        type: 'POST',
        url: ajaxurl,
        data: jQuery(this).attr('data-ajax') + '&action=hhomev_returnNewCalendar&nonce='+ajax_var.nonce,
        contentType: 'application/x-www-form-urlencoded;charset=utf-8',
        success: function(data) { 
            jQuery('#holiday-occupation').removeClass('js-loading');
            jQuery('#calender_occupy').html(jQuery(data).children('#calender_occupy').html());
        }
    }); 
});