<script type="text/x-jquery-tmpl" id="tmpl-item-row-<?= GRAPH_TYPE_NORMAL ?>">
	<tr id="items_#{number}" class="sortable">
		<!-- icon + hidden -->
		<?php if ($readonly): ?>
			<td>
		<?php else: ?>
			<td class="<?= ZBX_STYLE_TD_DRAG_ICON ?>">
				<div class="<?= ZBX_STYLE_DRAG_ICON ?>"></div>
				<span class="ui-icon ui-icon-arrowthick-2-n-s move"></span>
		<?php endif ?>
			<input type="hidden" id="items_#{number}_gitemid" name="gitemid" value="#{gitemid}">
			<input type="hidden" id="items_#{number}_itemid" name="itemid" value="#{itemid}">
			<input type="hidden" id="items_#{number}_sortorder" name="sortorder" value="#{sortorder}">
			<input type="hidden" id="items_#{number}_flags" name="flags" value="#{flags}">
			<input type="hidden" id="items_#{number}_type" name="type" value="<?= GRAPH_ITEM_SIMPLE ?>">
			<input type="hidden" id="items_#{number}_drawtype" name="drawtype" value="#{drawtype}">
			<input type="hidden" id="items_#{number}_yaxisside" name="yaxisside" value="#{yaxisside}">
		</td>

		<!-- row number -->
		<td>
			<span id="items_#{number}_number" class="items_number">#{number_nr}:</span>
		</td>

		<!-- name -->
		<td>
			<?php if ($readonly): ?>
				<span id="items_#{number}_name">#{name}</span>
			<?php else: ?>
				<a href="javascript:void(0)"><span id="items_#{number}_name">#{name}</span></a>
			<?php endif ?>
		</td>

		<!-- function -->
		<td>
			<select id="items_#{number}_calc_fnc" name="calc_fnc">
				<option value="<?= CALC_FNC_ALL ?>"><?= _('all') ?></option>
				<option value="<?= CALC_FNC_MIN ?>"><?= _('min') ?></option>
				<option value="<?= CALC_FNC_AVG ?>"><?= _('avg') ?></option>
				<option value="<?= CALC_FNC_MAX ?>"><?= _('max') ?></option>
			</select>
		</td>

		<!-- drawtype -->
		<td>
			<select name="drawtype">
			<?php foreach (graph_item_drawtypes() as $drawtype): ?>
				<option value="<?= $drawtype ?>"><?= graph_item_drawtype2str($drawtype) ?></option>
			<?php endforeach ?>
			</select>
		</td>

		<!-- yaxisside -->
		<td>
			<select name="yaxisside">
				<option value="<?= GRAPH_YAXIS_SIDE_LEFT ?>"><?= _('Left') ?></option>
				<option value="<?= GRAPH_YAXIS_SIDE_RIGHT ?>"><?= _('Right') ?></option>
			</select>
		</td>

		<td>
			<?= (new CColor('color', '#{color}', 'color_#{number}'))->appendColorPickerJs(false) ?>
		</td>

		<?php if (!$readonly): ?>
			<td class="<?= ZBX_STYLE_NOWRAP ?>">
				<button type="button" class="<?= ZBX_STYLE_BTN_LINK ?>" id="items_#{number}_remove" data-remove="#{number}" onclick="removeItem(this);"><?= _('Remove') ?></button>
			</td>
		<?php endif ?>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="tmpl-item-row-<?= GRAPH_TYPE_STACKED ?>">
	<tr id="items_#{number}" class="sortable">
		<!-- icon + hidden -->
		<?php if ($readonly): ?>
			<td>
		<?php else: ?>
			<td class="<?= ZBX_STYLE_TD_DRAG_ICON ?>">
				<div class="<?= ZBX_STYLE_DRAG_ICON ?>"></div>
				<span class="ui-icon ui-icon-arrowthick-2-n-s move"></span>
		<?php endif ?>
			<input type="hidden" id="items_#{number}_gitemid" name="gitemid" value="#{gitemid}">
			<input type="hidden" id="items_#{number}_itemid" name="itemid" value="#{itemid}">
			<input type="hidden" id="items_#{number}_sortorder" name="sortorder" value="#{sortorder}">
			<input type="hidden" id="items_#{number}_flags" name="flags" value="#{flags}">
			<input type="hidden" id="items_#{number}_type" name="type" value="<?= GRAPH_ITEM_SIMPLE ?>">
			<input type="hidden" id="items_#{number}_drawtype" name="drawtype" value="#{drawtype}">
			<input type="hidden" id="items_#{number}_yaxisside" name="yaxisside" value="#{yaxisside}">
		</td>

		<!-- row number -->
		<td>
			<span id="items_#{number}_number" class="items_number">#{number_nr}:</span>
		</td>

		<!-- name -->
		<td>
			<?php if ($readonly): ?>
				<span id="items_#{number}_name">#{name}</span>
			<?php else: ?>
				<a href="javascript:void(0)"><span id="items_#{number}_name">#{name}</span></a>
			<?php endif ?>
		</td>

		<!-- function -->
		<td>
			<select id="items_#{number}_calc_fnc" name="calc_fnc">
				<option value="<?= CALC_FNC_MIN ?>"><?= _('min') ?></option>
				<option value="<?= CALC_FNC_AVG ?>"><?= _('avg') ?></option>
				<option value="<?= CALC_FNC_MAX ?>"><?= _('max') ?></option>
			</select>
		</td>

		<!-- yaxisside -->
		<td>
			<select name="yaxisside">
				<option value="<?= GRAPH_YAXIS_SIDE_LEFT ?>"><?= _('Left') ?></option>
				<option value="<?= GRAPH_YAXIS_SIDE_RIGHT ?>"><?= _('Right') ?></option>
			</select>
		</td>

		<td>
			<?= (new CColor('color', '#{color}', 'color_#{number}'))->appendColorPickerJs(false) ?>
		</td>

		<?php if (!$readonly): ?>
			<td class="<?= ZBX_STYLE_NOWRAP ?>">
				<button type="button" class="<?= ZBX_STYLE_BTN_LINK ?>" id="items_#{number}_remove" data-remove="#{number}" onclick="removeItem(this);"><?= _('Remove') ?></button>
			</td>
		<?php endif ?>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="tmpl-item-row-<?= GRAPH_TYPE_PIE ?>">
	<tr id="items_#{number}" class="sortable">
		<!-- icon + hidden -->
		<?php if ($readonly): ?>
			<td>
		<?php else: ?>
			<td class="<?= ZBX_STYLE_TD_DRAG_ICON ?>">
				<div class="<?= ZBX_STYLE_DRAG_ICON ?>"></div>
				<span class="ui-icon ui-icon-arrowthick-2-n-s move"></span>
		<?php endif ?>
			<input type="hidden" id="items_#{number}_gitemid" name="gitemid" value="#{gitemid}">
			<input type="hidden" id="items_#{number}_itemid" name="itemid" value="#{itemid}">
			<input type="hidden" id="items_#{number}_sortorder" name="sortorder" value="#{sortorder}">
			<input type="hidden" id="items_#{number}_flags" name="flags" value="#{flags}">
			<input type="hidden" id="items_#{number}_type" name="type" value="<?= GRAPH_ITEM_SIMPLE ?>">
			<input type="hidden" id="items_#{number}_drawtype" name="drawtype" value="#{drawtype}">
			<input type="hidden" id="items_#{number}_yaxisside" name="yaxisside" value="#{yaxisside}">
		</td>

		<!-- row number -->
		<td>
			<span id="items_#{number}_number" class="items_number">#{number_nr}:</span>
		</td>

		<!-- name -->
		<td>
			<?php if ($readonly): ?>
				<span id="items_#{number}_name">#{name}</span>
			<?php else: ?>
				<a href="javascript:void(0)"><span id="items_#{number}_name">#{name}</span></a>
			<?php endif ?>
		</td>

		<!-- type -->
		<td>
			<select name="type">
				<option value="<?= GRAPH_ITEM_SIMPLE ?>"><?= _('Simple') ?></option>
				<option value="<?= GRAPH_ITEM_SUM ?>"><?= _('Graph sum') ?></option>
			</select>
		</td>

		<!-- function -->
		<td>
			<select id="items_#{number}_calc_fnc" name="calc_fnc">
				<option value="<?= CALC_FNC_MIN ?>"><?= _('min') ?></option>
				<option value="<?= CALC_FNC_AVG ?>"><?= _('avg') ?></option>
				<option value="<?= CALC_FNC_MAX ?>"><?= _('max') ?></option>
				<option value="<?= CALC_FNC_LST ?>"><?= _('last') ?></option>
			</select>
		</td>

		<td>
			<?= (new CColor('color', '#{color}', 'color_#{number}'))->appendColorPickerJs(false) ?>
		</td>

		<?php if (!$readonly): ?>
			<td class="<?= ZBX_STYLE_NOWRAP ?>">
				<button type="button" class="<?= ZBX_STYLE_BTN_LINK ?>" id="items_#{number}_remove" data-remove="#{number}" onclick="removeItem(this);"><?= _('Remove') ?></button>
			</td>
		<?php endif ?>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="tmpl-item-row-<?= GRAPH_TYPE_EXPLODED ?>">
	<tr id="items_#{number}" class="sortable">
		<!-- icon + hidden -->
		<?php if ($readonly): ?>
			<td>
		<?php else: ?>
			<td class="<?= ZBX_STYLE_TD_DRAG_ICON ?>">
				<div class="<?= ZBX_STYLE_DRAG_ICON ?>"></div>
				<span class="ui-icon ui-icon-arrowthick-2-n-s move"></span>
		<?php endif ?>
			<input type="hidden" id="items_#{number}_gitemid" name="gitemid" value="#{gitemid}">
			<input type="hidden" id="items_#{number}_itemid" name="itemid" value="#{itemid}">
			<input type="hidden" id="items_#{number}_sortorder" name="sortorder" value="#{sortorder}">
			<input type="hidden" id="items_#{number}_flags" name="flags" value="#{flags}">
			<input type="hidden" id="items_#{number}_type" name="type" value="<?= GRAPH_ITEM_SIMPLE ?>">
			<input type="hidden" id="items_#{number}_drawtype" name="drawtype" value="#{drawtype}">
			<input type="hidden" id="items_#{number}_yaxisside" name="yaxisside" value="#{yaxisside}">
		</td>

		<!-- row number -->
		<td>
			<span id="items_#{number}_number" class="items_number">#{number_nr}:</span>
		</td>

		<!-- name -->
		<td>
			<?php if ($readonly): ?>
				<span id="items_#{number}_name">#{name}</span>
			<?php else: ?>
				<a href="javascript:void(0)"><span id="items_#{number}_name">#{name}</span></a>
			<?php endif ?>
		</td>

		<!-- type -->
		<td>
			<select name="type">
				<option value="<?= GRAPH_ITEM_SIMPLE ?>"><?= _('Simple') ?></option>
				<option value="<?= GRAPH_ITEM_SUM ?>"><?= _('Graph sum') ?></option>
			</select>
		</td>

		<!-- function -->
		<td>
			<select id="items_#{number}_calc_fnc" name="calc_fnc">
				<option value="<?= CALC_FNC_MIN ?>"><?= _('min') ?></option>
				<option value="<?= CALC_FNC_AVG ?>"><?= _('avg') ?></option>
				<option value="<?= CALC_FNC_MAX ?>"><?= _('max') ?></option>
				<option value="<?= CALC_FNC_LST ?>"><?= _('last') ?></option>
			</select>
		</td>

		<td>
			<?= (new CColor('color', '#{color}', 'color_#{number}'))->appendColorPickerJs(false) ?>
		</td>

		<?php if (!$readonly): ?>
			<td class="<?= ZBX_STYLE_NOWRAP ?>">
				<button type="button" class="<?= ZBX_STYLE_BTN_LINK ?>" id="items_#{number}_remove" data-remove="#{number}" onclick="removeItem(this);"><?= _('Remove') ?></button>
			</td>
		<?php endif ?>
	</tr>
</script>

<script type="text/javascript">
	colorPalette.setThemeColors(<?= CJs::encodeJson(explode(',', getUserGraphTheme()['colorpalette'])) ?>);
	var graphs = JSON.parse('<?= json_encode([
		'graphtype' =>  $data['graphtype'],
		'readonly' => $readonly,
		'hostid' => $data['hostid'],
		'groupid' => $data['groupid'],
		'is_template' => $data['is_template'],
		'normal_only' => $data['normal_only'],
		'parent_discoveryid' => $data['parent_discoveryid'],
		'CALC_FNC_AVG' => CALC_FNC_AVG,
		'ZBX_FLAG_DISCOVERY_PROTOTYPE' => ZBX_FLAG_DISCOVERY_PROTOTYPE,
		'ZBX_STYLE_DRAG_ICON' => ZBX_STYLE_DRAG_ICON,
		'GRAPH_YAXIS_TYPE_CALCULATED' => GRAPH_YAXIS_TYPE_CALCULATED,
		'GRAPH_YAXIS_TYPE_FIXED' => GRAPH_YAXIS_TYPE_FIXED,
		'GRAPH_TYPE_NORMAL' => GRAPH_TYPE_NORMAL,
		'GRAPH_TYPE_PIE' => GRAPH_TYPE_PIE,
		'GRAPH_TYPE_EXPLODED' => GRAPH_TYPE_EXPLODED
	]) ?>');

	function loadItem(number, gitemid, itemid, name, type, calc_fnc, drawtype, yaxisside, color, flags) {
		var item = {
				number: number,
				number_nr: number + 1,
				gitemid: gitemid,
				itemid: itemid,
				calc_fnc: calc_fnc,
				color: color,
				sortorder: number,
				flags: flags,
				name: name
			},
			itemTpl = new Template(jQuery('#tmpl-item-row-' + graphs.graphtype).html()),
			$row = jQuery(itemTpl.evaluate(item));

		$row.find('[name="type"]').val(type);
		$row.find('[name="drawtype"]').val(drawtype);
		$row.find('[name="yaxisside"]').val(yaxisside);

		var $calc_fnc = $row.find('[name="calc_fnc"]');
		$calc_fnc.val(calc_fnc);
		if ($calc_fnc[0].selectedIndex < 0) {
			$calc_fnc[0].selectedIndex = 0;
		}

		jQuery('#itemButtonsRow').before($row);
		$row.find('.input-color-picker input').colorpicker();

		colorPalette.incrementNextColor();

		!graphs.readonly && rewriteNameLinks();
	}

	function addPopupValues(list) {
		if (!isset('object', list) || list.object != 'itemid') {
			return false;
		}
		var itemTpl = new Template(jQuery('#tmpl-item-row-' + graphs.graphtype).html()),
			$row;

		for (var i = 0; i < list.values.length; i++) {
			var number = jQuery('#itemsTable tr.sortable').length,
				item = {
					number: number,
					number_nr: number + 1,
					gitemid: null,
					itemid: list.values[i].itemid,
					calc_fnc: null,
					drawtype: 0,
					yaxisside: 0,
					sortorder: number,
					flags: (typeof list.values[i].flags === 'undefined') ? 0 : list.values[i].flags,
					color: colorPalette.getNextColor(),
					name: list.values[i].name
				};
			$row = jQuery(itemTpl.evaluate(item));

			$row.find('[name="calc_fnc"]').val(graphs.CALC_FNC_AVG);
			jQuery('#itemButtonsRow').before($row);
			$row.find('.input-color-picker input').colorpicker();
		}

		if (!graphs.readonly) {
			activateSortable();
			rewriteNameLinks();
		}
	}

	function getOnlyHostParam() {
		return graphs.is_template
			? {only_hostid: graphs.hostid}
			: {real_hosts: '1'};
	}

	function rewriteNameLinks() {
		var size = jQuery('#itemsTable tr.sortable').length;

		for (var i = 0; i < size; i++) {
			var popup_options = {
				srcfld1: 'itemid',
				srcfld2: 'name',
				dstfrm: 'graphForm',
				dstfld1: 'items_' + i + '_itemid',
				dstfld2: 'items_' + i + '_name',
				numeric: 1,
				with_webitems: 1,
				writeonly: 1
			};
			if (jQuery('#items_' + i + '_flags').val() == graphs.ZBX_FLAG_DISCOVERY_PROTOTYPE) {
				popup_options['srctbl'] = 'item_prototypes',
				popup_options['srcfld3'] = 'flags',
				popup_options['dstfld3'] = 'items_' + i + '_flags',
				popup_options['parent_discoveryid'] = graphs.parent_discoveryid;
			}
			else {
				popup_options['srctbl'] = 'items';
			}

			if (graphs.normal_only !== '') {
				popup_options['normal_only'] = '1';
			}

			if (!graphs.parent_discoveryid && graphs.groupid && graphs.hostid) {
				popup_options['groupid'] = graphs.groupid,
				popup_options['hostid'] = graphs.hostid;
			}

			var nameLink = 'PopUp("popup.generic",'
				+ 'jQuery.extend('+ JSON.stringify(popup_options) +',getOnlyHostParam()), null, this);';
			jQuery('#items_' + i + '_name').attr('onclick', nameLink);
		}
	}

	function removeItem(obj) {
		var number = jQuery(obj).data('remove');

		jQuery('#items_' + number).find('*').remove();
		jQuery('#items_' + number).remove();

		recalculateSortOrder();
		!graphs.readonly && activateSortable();
	}

	function recalculateSortOrder() {
		var i = 0;

		// rewrite ids, set "tmp" prefix
		jQuery('#itemsTable tr.sortable').find('*[id]').each(function() {
			var obj = jQuery(this);

			obj.attr('id', 'tmp' + obj.attr('id'));
		});

		jQuery('#itemsTable tr.sortable').each(function() {
			var obj = jQuery(this);

			obj.attr('id', 'tmp' + obj.attr('id'));
		});

		// rewrite ids to new order
		jQuery('#itemsTable tr.sortable').each(function() {
			var obj = jQuery(this);

			// rewrite ids in input fields
			obj.find('*[id]').each(function() {
				var obj = jQuery(this),
					id = obj.attr('id').substring(3),
					part1 = id.substring(0, id.indexOf('items_') + 5),
					part2 = id.substring(id.indexOf('items_') + 6);

				part2 = part2.substring(part2.indexOf('_') + 1);

				obj.attr('id', part1 + '_' + i + '_' + part2);

				// set sortorder
				if (part2 === 'sortorder') {
					obj.val(i);
				}
			});

			// rewrite ids in <tr>
			var id = obj.attr('id').substring(3),
				part1 = id.substring(0, id.indexOf('items_') + 5);

			obj.attr('id', part1 + '_' + i);

			i++;
		});

		i = 0;

		jQuery('#itemsTable tr.sortable').each(function() {
			// set row number
			jQuery('.items_number', this).text((i + 1) + ':');

			// set remove number
			jQuery('#items_' + i + '_remove').data('remove', i);

			i++;
		});

		!graphs.readonly && rewriteNameLinks();
	}

	function initSortable() {
		var itemsTable = jQuery('#itemsTable'),
			itemsTableWidth = itemsTable.width(),
			itemsTableColumns = jQuery('#itemsTable .header td'),
			itemsTableColumnWidths = [];

		itemsTableColumns.each(function() {
			itemsTableColumnWidths[itemsTableColumnWidths.length] = jQuery(this).width();
		});

		itemsTable.sortable({
			disabled: (jQuery('#itemsTable tr.sortable').length < 2),
			items: 'tbody tr.sortable',
			axis: 'y',
			containment: 'parent',
			cursor: IE ? 'move' : 'grabbing',
			handle: 'div.' + graphs.ZBX_STYLE_DRAG_ICON,
			tolerance: 'pointer',
			opacity: 0.6,
			update: recalculateSortOrder,
			create: function() {
				// force not to change table width
				itemsTable.width(itemsTableWidth);
			},
			helper: function(e, ui) {
				ui.children().each(function(i) {
					var td = jQuery(this);

					td.width(itemsTableColumnWidths[i]);
				});

				// when dragging element on safari, it jumps out of the table
				if (SF) {
					// move back draggable element to proper position
					ui.css('left', (ui.offset().left - 2) + 'px');
				}

				itemsTableColumns.each(function(i) {
					jQuery(this).width(itemsTableColumnWidths[i]);
				});

				return ui;
			},
			start: function(e, ui) {
				jQuery(ui.placeholder).height(jQuery(ui.helper).height());
			}
		});
	}

	function activateSortable() {
		jQuery('#itemsTable').sortable({disabled: (jQuery('#itemsTable tr.sortable').length < 2)});
	}

	jQuery(function($) {
		$('#tabs').on('tabsactivate', function(event, ui) {
			if (ui.newPanel.attr('id') === 'previewTab') {
				var preview_chart = $('#previewChart'),
					src = new Curl('chart3.php');

				if (preview_chart.find('.preloader').length) {
					return false;
				}

				src.setArgument('period', '3600');
				src.setArgument('name', $('#name').val());
				src.setArgument('width', $('#width').val());
				src.setArgument('height', $('#height').val());
				src.setArgument('graphtype', $('#graphtype').val());
				src.setArgument('legend', $('#show_legend').is(':checked') ? 1 : 0);

				if (graphs.graphtype == graphs.GRAPH_TYPE_PIE || graphs.graphtype == graphs.GRAPH_TYPE_EXPLODED) {
					src.setPath('chart7.php');
					src.setArgument('graph3d', $('#show_3d').is(':checked') ? 1 : 0);
				}
				else {
					if (graphs.graphtype == graphs.GRAPH_TYPE_NORMAL) {
						src.setArgument('percent_left', $('#percent_left').val());
						src.setArgument('percent_right', $('#percent_right').val());
					}
					src.setArgument('ymin_type', $('#ymin_type').val());
					src.setArgument('ymax_type', $('#ymax_type').val());
					src.setArgument('yaxismin', $('#yaxismin').val());
					src.setArgument('yaxismax', $('#yaxismax').val());
					src.setArgument('ymin_itemid', $('#ymin_itemid').val());
					src.setArgument('ymax_itemid', $('#ymax_itemid').val());
					src.setArgument('showworkperiod', $('#show_work_period').is(':checked') ? 1 : 0);
					src.setArgument('showtriggers', $('#show_triggers').is(':checked') ? 1 : 0);
				}

				$('#itemsTable tr.sortable').each(function(i, node) {
					var short_fmt = [];
					$(node).find('*[name]').each(function(_, input) {
						if (!$.isEmptyObject(input) && input.name != null) {
							short_fmt.push((input.name).substr(0, 2) + ':' + input.value);
						}
					});
					src.setArgument('i[' + i + ']', short_fmt.join(','));
				});

				var image = $('img', preview_chart);

				if (image.length != 0) {
					image.remove();
				}

				preview_chart.append($('<div>').addClass('preloader'));

				$('<img />')
					.attr('src', src.getUrl())
					.on('load', function() {
						preview_chart.html($(this));
					});
			}
		});

		if (graphs.readonly) {
			$('#itemsTable').sortable({disabled: true}).find('input').prop('readonly', true);
			$('select', '#itemsTable').prop('disabled', true);

			var size = $('#itemsTable tr.sortable').length;

			for (var i = 0; i < size; i++) {
				$('#items_' + i + '_color').removeAttr('onchange');
				$('#lbl_items_' + i + '_color').removeAttr('onclick');
			}
		}

		// Y axis min clean unused fields
		$('#ymin_type').change(function() {
			switch ($(this).val()) {
				case graphs.GRAPH_YAXIS_TYPE_CALCULATED:
					$('#yaxismin').val('');
					$('#ymin_name').val('');
					$('#ymin_itemid').val('0');
					break;

				case graphs.GRAPH_YAXIS_TYPE_FIXED:
					$('#ymin_name').val('');
					$('#ymin_itemid').val('0');
					break;

				default:
					$('#yaxismin').val('');
			}

			$('form[name="graphForm"]').submit();
		});

		// Y axis max clean unused fields
		$('#ymax_type').change(function() {
			switch ($(this).val()) {
				case graphs.GRAPH_YAXIS_TYPE_CALCULATED:
					$('#yaxismax').val('');
					$('#ymax_name').val('');
					$('#ymax_itemid').val('0');
					break;

				case graphs.GRAPH_YAXIS_TYPE_FIXED:
					$('#ymax_name').val('');
					$('#ymax_itemid').val('0');
					break;

				default:
					$('#yaxismax').val('');
			}

			$('form[name="graphForm"]').submit();
		});

		var $form = $('form[name="graphForm"]');
		$form.on('submit', function(e) {
			$form
				.find('tr.sortable[id^="items_"]')
				.each(function(i, node) {
					var item = {};

					$(node)
						.find('input, select')
						.each(function(_, node) {
							item[node.name] = node.value;
						})
						.remove();

					item.sortorder = i + 1;

					$form.append($('<input />', {
						type: 'hidden',
						name: 'items[' + i + ']',
						value: JSON.stringify(item)
					}));
				})
		});

		!graphs.readonly && initSortable();
	});
</script>
