/* Set Missed Alt admin jQuery */

jQuery(document).ready(function($) {

	$('.check_missed_alt_button').on('click', function () {
		$(".loaderimage").show();
		$(".check_missed_alt_button").before('<p>Please wait....</p>');
		$.ajax({
			type: "POST",
			url: ajaxurl+"?action=ajax_check_missed_alt",
			cache: false,
			dataType: 'json',
			success: function (data) {
				$(".loaderimage").hide();
				if (data.code == "success") {
					$(".check_missed_alt_button").before(data.insert);
				}
			},
			error: function (a,b,c) {console.log(a,b,c);}
		});
		return false;
	});
	$('.add_missed_alt_button').on('click', function () {
		$(".loaderimage").show();
		$(".check_missed_alt_button").before('<p>Please wait....</p>');
		$.ajax({
			type: "POST",
			url: ajaxurl+"?action=ajax_add_missed_alt",
			cache: false,
			dataType: 'json',
			success: function (data) {
				$(".loaderimage").hide();
				if (data.code == "success") {
					$(".check_missed_alt_button").before(data.insert);
				}
			},
			error: function (a,b,c) {console.log(a,b,c);}
		});
		return false;
	});
	
	$('.imgalt_button').on('click', function () {
		img_id = this.value;
		elem = $(this).parent();
		imgalt = elem.children(".imgalt_input").val();
		if (imgalt == '') return false;
		elem.find('.loaderimage.'+img_id).show();
		datastring = "img_id=" + img_id + "&img_alt=" + imgalt;
		$.ajax({
			type: "POST",
			url: ajaxurl+"?action=ajax_set_missed_alt",
			cache: false,
			data: datastring,
			dataType: 'json',
			success: function (data) {
				if (data.code == "success") {
					elem.siblings().html(imgalt);
					elem.find('.loaderimage.'+img_id).hide();
				}
			},
			error: function (a,b,c) {console.log(a,b,c);}
		});
		return false;
	});


});