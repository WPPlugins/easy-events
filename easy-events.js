/*
  Easy Events Plugin Javascipt
*/

/* Hide and show past events */

jQuery(document).ready(function() {

	jQuery('.easy-events-past').hide();

	jQuery('.easy-events-show-past').click(function(){

		var pastrows = jQuery(this).closest('div.easy-events-list').find('tr.easy-events-past');

		jQuery(pastrows).toggle();

		if (jQuery(pastrows).css('display') == 'none') {
			jQuery(this).html('Show Past Events &#9658');
		} else {
			jQuery(this).html('Hide Past Events &#9650');
		}
	});
});
