<?php
include dirname(__FILE__).'/common.item.edit.js.php';
include dirname(__FILE__).'/item.preprocessing.js.php';
include dirname(__FILE__).'/editabletable.js.php';

$this->data['valueTypeVisibility'] = [];
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'units');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'row_units');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'units');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'row_units');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'row_trends');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'row_trends');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_LOG, 'logtimefmt');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_LOG, 'row_logtimefmt');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'valuemapid');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_STR, 'valuemapid');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_STR, 'row_valuemap');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_STR, 'valuemap_name');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'row_valuemap');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_FLOAT, 'valuemap_name');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'valuemapid');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'row_valuemap');
zbx_subarray_push($this->data['valueTypeVisibility'], ITEM_VALUE_TYPE_UINT64, 'valuemap_name');
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		function typeChangeHandler() {
			// selected item type
			var type = parseInt($('#type').val()),
				asterisk = '<?= ZBX_STYLE_FIELD_LABEL_ASTERISK ?>';

			$('#keyButton').prop('disabled',
				type != <?= ITEM_TYPE_ZABBIX ?>
					&& type != <?= ITEM_TYPE_ZABBIX_ACTIVE ?>
					&& type != <?= ITEM_TYPE_SIMPLE ?>
					&& type != <?= ITEM_TYPE_INTERNAL ?>
					&& type != <?= ITEM_TYPE_AGGREGATE ?>
					&& type != <?= ITEM_TYPE_DB_MONITOR ?>
					&& type != <?= ITEM_TYPE_SNMPTRAP ?>
					&& type != <?= ITEM_TYPE_JMX ?>
			)

			if (type == <?= ITEM_TYPE_SSH ?> || type == <?= ITEM_TYPE_TELNET ?>) {
				$('label[for=username]').addClass(asterisk);
				$('input[name=username]').attr('aria-required', 'true');
			}
			else {
				$('label[for=username]').removeClass(asterisk);
				$('input[name=username]').removeAttr('aria-required');
			}
		}

		// field switchers
		<?php
		if (!empty($this->data['valueTypeVisibility'])) { ?>
			var valueTypeSwitcher = new CViewSwitcher('value_type', 'change',
				<?= zbx_jsvalue($this->data['valueTypeVisibility'], true) ?>);
		<?php } ?>

		$('#type')
			.change(function() {
				typeChangeHandler();
			})
			.trigger('change');

		// Whenever non-numeric type is changed back to numeric type, set the default value in "trends" field.
		$('#value_type')
			.change(function() {
				var new_value = $(this).val(),
					old_value = $(this).data('old-value'),
					trends = $('#trends');

				if ((old_value == <?= ITEM_VALUE_TYPE_STR ?> || old_value == <?= ITEM_VALUE_TYPE_LOG ?>
						|| old_value == <?= ITEM_VALUE_TYPE_TEXT ?>)
						&& (new_value == <?= ITEM_VALUE_TYPE_FLOAT ?>
						|| new_value == <?= ITEM_VALUE_TYPE_UINT64 ?>)) {
					if (trends.val() == 0) {
						trends.val('<?= $this->data['trends_default'] ?>');
					}
					$('#trends_mode_1').prop('checked', true);
				}

				$('#trends_mode').trigger('change');
				$(this).data('old-value', new_value);
			})
			.data('old-value', $('#value_type').val());

		$('#history_mode')
			.change(function() {
				if ($('[name="history_mode"][value=' + <?= ITEM_STORAGE_OFF ?> + ']').is(':checked')) {
					$('#history').prop('disabled', true).hide();
				}
				else {
					$('#history').prop('disabled', false).show();
				}
			})
			.trigger('change');

		$('#trends_mode')
			.change(function() {
				if ($('[name="trends_mode"][value=' + <?= ITEM_STORAGE_OFF ?> + ']').is(':checked')) {
					$('#trends').prop('disabled', true).hide();
				}
				else {
					$('#trends').prop('disabled', false).show();
				}
			})
			.trigger('change');
	});
</script>
