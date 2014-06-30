jQuery( document ).ready(function($) {
	var watchField = 'field_bufc3y'; //field to watch for changes
	var changeField = 'field_qo87ub'; //field to apply changes to

	$('#'+watchField).on('change', function() {
		var value = this.value;
		var newValue;
		newValue = eval(changeField+'Arr')[eval(watchField+'Arr').indexOf(value.toString())];
		if(newValue.length <= 0) {
			newValue = '';
		}

		$('#'+changeField).attr("readonly", false);
		if($('#'+changeField).prop("tagName").toLowerCase() == 'input') {
			$('#'+changeField).val(newValue);
		}
	});
})