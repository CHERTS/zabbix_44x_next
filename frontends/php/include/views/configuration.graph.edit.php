<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


$widget = new CWidget();

if ($data['parent_discoveryid'] === null) {
	$widget
		->setTitle(_('Graphs'))
		->addItem(get_header_host_table('graphs', $data['hostid']));
}
else {
	$widget
		->setTitle(_('Graph prototypes'))
		->addItem(get_header_host_table('graphs', $data['hostid'], $data['parent_discoveryid']));
}

// Create form.
$graphForm = (new CForm())
	->setName('graphForm')
	->setAttribute('aria-labelledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form'])
	->addVar('hostid', $data['hostid']);

if ($data['parent_discoveryid'] !== null) {
	$graphForm->addItem((new CVar('parent_discoveryid', $data['parent_discoveryid']))->removeId());
}

if ($data['graphid'] != 0) {
	$graphForm->addVar('graphid', $data['graphid']);
}

// Create form list.
$graphFormList = new CFormList('graphFormList');

$is_templated = (bool) $data['templates'];
if ($is_templated) {
	$graphFormList->addRow(_('Parent graphs'), $data['templates']);
}

$discovered_graph = false;
if (array_key_exists('flags', $data) && $data['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
	$discovered_graph = true;
}

$readonly = false;
if ($is_templated || $discovered_graph) {
	$readonly = true;
}

if ($discovered_graph) {
	$graphFormList->addRow(_('Discovered by'), new CLink($data['discoveryRule']['name'],
		(new CUrl('graphs.php'))
			->setArgument('form', 'update')
			->setArgument('parent_discoveryid', $data['discoveryRule']['itemid'])
			->setArgument('graphid', $data['graphDiscovery']['parent_graphid'])
	));
}

$graphFormList
	->addRow(
		(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
		(new CTextBox('name', $data['name'], $readonly))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
	)
	->addRow((new CLabel(_('Width'), 'width'))->setAsteriskMark(),
		(new CNumericBox('width', $data['width'], 5, $readonly))
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow((new CLabel(_('Height'), 'height'))->setAsteriskMark(),
		(new CNumericBox('height', $data['height'], 5, $readonly))
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow((new CLabel(_('Graph type'), 'graphtype')),
		(new CComboBox('graphtype', $data['graphtype'], 'jQuery(\'form[name="graphForm"]\').submit()',
			graphType())
		)->setEnabled(!$readonly)
	)
	->addRow(_('Show legend'),
		(new CCheckBox('show_legend'))
			->setChecked($data['show_legend'] == 1)
			->setEnabled(!$readonly)
	);

// Append graph types to form list.
if ($data['graphtype'] == GRAPH_TYPE_NORMAL || $data['graphtype'] == GRAPH_TYPE_STACKED) {
	$graphFormList->addRow(_('Show working time'),
		(new CCheckBox('show_work_period'))
			->setChecked($data['show_work_period'] == 1)
			->setEnabled(!$readonly)
	);
	$graphFormList->addRow(_('Show triggers'),
		(new CCheckbox('show_triggers'))
			->setchecked($data['show_triggers'] == 1)
			->setEnabled(!$readonly)
	);

	if ($data['graphtype'] == GRAPH_TYPE_NORMAL) {
		// Percent left.
		$percentLeftTextBox = (new CTextBox('percent_left', $data['percent_left'], $readonly, 7))
			->setWidth(ZBX_TEXTAREA_TINY_WIDTH);
		$percentLeftCheckbox = (new CCheckBox('visible[percent_left]'))
			->setChecked(true)
			->onClick('javascript: showHideVisible("percent_left");')
			->setEnabled(!$readonly);

		if (array_key_exists('visible', $data) && array_key_exists('percent_left', $data['visible'])) {
			$percentLeftCheckbox->setChecked(true);
		}
		elseif ($data['percent_left'] == 0) {
			$percentLeftTextBox->addStyle('visibility: hidden;');
			$percentLeftCheckbox->setChecked(false);
		}

		$graphFormList->addRow(_('Percentile line (left)'), [$percentLeftCheckbox, ' ', $percentLeftTextBox]);

		// Percent right.
		$percentRightTextBox = (new CTextBox('percent_right', $data['percent_right'], $readonly, 7))
			->setWidth(ZBX_TEXTAREA_TINY_WIDTH);
		$percentRightCheckbox = (new CCheckBox('visible[percent_right]'))
			->setChecked(true)
			->onClick('javascript: showHideVisible("percent_right");')
			->setEnabled(!$readonly);

		if (array_key_exists('visible', $data) && array_key_exists('percent_right', $data['visible'])) {
			$percentRightCheckbox->setChecked(true);
		}
		elseif ($data['percent_right'] == 0) {
			$percentRightTextBox->addStyle('visibility: hidden;');
			$percentRightCheckbox->setChecked(false);
		}

		$graphFormList->addRow(_('Percentile line (right)'), [$percentRightCheckbox, ' ', $percentRightTextBox]);
	}

	$yaxisMinData = [(new CComboBox('ymin_type', $data['ymin_type'], null, [
		GRAPH_YAXIS_TYPE_CALCULATED => _('Calculated'),
		GRAPH_YAXIS_TYPE_FIXED => _('Fixed'),
		GRAPH_YAXIS_TYPE_ITEM_VALUE => _('Item')
	]))->setEnabled(!$readonly)];

	if ($data['ymin_type'] == GRAPH_YAXIS_TYPE_FIXED) {
		$yaxisMinData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$yaxisMinData[] = (new CTextBox('yaxismin', $data['yaxismin'], $readonly))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH);
	}
	elseif ($data['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
		$graphForm->addVar('yaxismin', $data['yaxismin']);

		$ymin_axis_ms_data = [];

		if ($data['ymin_itemid'] != 0) {
			if (array_key_exists($data['ymin_itemid'], $data['yaxis_items'])) {
				$ymin_axis_ms_data = [[
					'id' => $data['ymin_itemid'],
					'name' => $data['yaxis_items'][$data['ymin_itemid']]['name_expanded'],
					'prefix' => $data['yaxis_items'][$data['ymin_itemid']]['hosts'][0]['name'].NAME_DELIMITER
				]];
			}
			else {
				$ymin_axis_ms_data = [[
					'id' => $data['ymin_itemid'],
					'name' => _('Inaccessible item'),
					'prefix' => ''
				]];
			}
		}

		$yaxisMinData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$yaxisMinData[] = (new CMultiSelect([
			'name' => 'ymin_itemid',
			'object_name' => 'items',
			'data' => $ymin_axis_ms_data,
			'multiple' => false,
			'disabled' => $readonly,
			'styles' => [
				'display' => 'inline-flex'
			],
			'popup' => [
				'parameters' => [
					'srctbl' => 'items',
					'srcfld1' => 'itemid',
					'srcfld2' => 'name',
					'dstfrm' => $graphForm->getName(),
					'dstfld1' => 'ymin_itemid',
					'hostid' => $data['is_template'] ? $data['hostid'] : 0,
					'numeric' => '1',
					'webitems' => '1',
					'real_hosts' => !$data['is_template']
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

		// Select item prototype button.
		if ($data['parent_discoveryid'] !== null) {
			$yaxisMinData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
			$yaxisMinData[] = (new CButton('yaxis_min_prototype', _('Select prototype')))
				->addClass(ZBX_STYLE_BTN_GREY)
				->onClick('return PopUp("popup.generic",'.
					CJs::encodeJson([
						'srctbl' => 'item_prototypes',
						'srcfld1' => 'itemid',
						'srcfld2' => 'name',
						'dstfrm' => $graphForm->getName(),
						'dstfld1' => 'ymin_itemid',
						'parent_discoveryid' => $data['parent_discoveryid'],
						'numeric' => '1'
					]).', null, this);'
				)
				->setEnabled(!$readonly);
		}
	}
	else {
		$graphForm->addVar('yaxismin', $data['yaxismin']);
	}

	$yaxismin_label = new CLabel(_('Y axis MIN value'));
	if ($data['ymin_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
		$yaxismin_label
			->setAsteriskMark()
			->setAttribute('for', 'ymin_name');
	}

	$graphFormList->addRow($yaxismin_label, $yaxisMinData);

	$yaxisMaxData = [(new CComboBox('ymax_type', $data['ymax_type'], null, [
		GRAPH_YAXIS_TYPE_CALCULATED => _('Calculated'),
		GRAPH_YAXIS_TYPE_FIXED => _('Fixed'),
		GRAPH_YAXIS_TYPE_ITEM_VALUE => _('Item')
	]))->setEnabled(!$readonly)];

	if ($data['ymax_type'] == GRAPH_YAXIS_TYPE_FIXED) {
		$yaxisMaxData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$yaxisMaxData[] = (new CTextBox('yaxismax', $data['yaxismax'], $readonly))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH);
	}
	elseif ($data['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
		$graphForm->addVar('yaxismax', $data['yaxismax']);

		$ymax_axis_ms_data = [];

		if ($data['ymax_itemid'] != 0) {
			if (array_key_exists($data['ymax_itemid'], $data['yaxis_items'])) {
				$ymax_axis_ms_data = [[
					'id' => $data['ymax_itemid'],
					'name' => $data['yaxis_items'][$data['ymax_itemid']]['name_expanded'],
					'prefix' => $data['yaxis_items'][$data['ymax_itemid']]['hosts'][0]['name'].NAME_DELIMITER
				]];
			}
			else {
				$ymax_axis_ms_data = [[
					'id' => $data['ymax_itemid'],
					'name' => _('Inaccessible item'),
					'prefix' => ''
				]];
			}
		}

		$yaxisMaxData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
		$yaxisMaxData[] = (new CMultiSelect([
			'name' => 'ymax_itemid',
			'object_name' => 'items',
			'data' => $ymax_axis_ms_data,
			'multiple' => false,
			'disabled' => $readonly,
			'styles' => [
				'display' => 'inline-flex'
			],
			'popup' => [
				'parameters' => [
					'srctbl' => 'items',
					'srcfld1' => 'itemid',
					'srcfld2' => 'name',
					'dstfrm' => $graphForm->getName(),
					'dstfld1' => 'ymax_itemid',
					'hostid' => $data['is_template'] ? $data['hostid'] : 0,
					'numeric' => '1',
					'webitems' => '1',
					'real_hosts' => !$data['is_template']
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

		// Select item prototype button.
		if ($data['parent_discoveryid'] !== null) {
			$yaxisMaxData[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
			$yaxisMaxData[] = (new CButton('yaxis_max_prototype', _('Select prototype')))
				->addClass(ZBX_STYLE_BTN_GREY)
				->onClick('return PopUp("popup.generic",'.
					CJs::encodeJson([
						'srctbl' => 'item_prototypes',
						'srcfld1' => 'itemid',
						'srcfld2' => 'name',
						'dstfrm' => $graphForm->getName(),
						'dstfld1' => 'ymax_itemid',
						'parent_discoveryid' => $data['parent_discoveryid'],
						'numeric' => '1'
					]).', null, this);'
				)
				->setEnabled(!$readonly);
		}
	}
	else {
		$graphForm->addVar('yaxismax', $data['yaxismax']);
	}

	$yaxismax_label = new CLabel(_('Y axis MAX value'));
	if ($data['ymax_type'] == GRAPH_YAXIS_TYPE_ITEM_VALUE) {
		$yaxismax_label
			->setAsteriskMark()
			->setAttribute('for', 'ymax_name');
	}

	$graphFormList->addRow($yaxismax_label, $yaxisMaxData);
}
else {
	$graphFormList->addRow(_('3D view'),
		(new CCheckBox('show_3d'))
			->setChecked($data['show_3d'] == 1)
			->setEnabled(!$readonly)
	);
}

// Append items to form list.
$items_table = (new CTable())
	->setId('itemsTable')
	->setHeader([
		(new CColHeader())->setWidth(15),
		(new CColHeader())->setWidth(15),
		(new CColHeader(_('Name')))->setWidth(($data['graphtype'] == GRAPH_TYPE_NORMAL) ? 280 : 360),
		($data['graphtype'] == GRAPH_TYPE_PIE || $data['graphtype'] == GRAPH_TYPE_EXPLODED)
			? (new CColHeader(_('Type')))->setWidth(80)
			: null,
		(new CColHeader(_('Function')))->setWidth(80),
		($data['graphtype'] == GRAPH_TYPE_NORMAL)
			? (new CColHeader(_('Draw style')))
				->addClass(ZBX_STYLE_NOWRAP)
				->setWidth(80)
			: null,
		($data['graphtype'] == GRAPH_TYPE_NORMAL || $data['graphtype'] == GRAPH_TYPE_STACKED)
			? (new CColHeader(_('Y axis side')))
				->addClass(ZBX_STYLE_NOWRAP)
				->setWidth(80)
			: null,
		(new CColHeader(_('Colour')))->setWidth(100),
		$readonly ? null : (new CColHeader(_('Action')))->setWidth(50)
	]);

$popup_options_add = [
	'srctbl' => 'items',
	'srcfld1' => 'itemid',
	'srcfld2' => 'name',
	'dstfrm' => $graphForm->getName(),
	'numeric' => '1',
	'writeonly' => '1',
	'multiselect' => '1',
	'with_webitems' => '1'
];
if ($data['normal_only']) {
	$popup_options_add['normal_only'] = '1';
}
if ($data['groupid'] && $data['hostid']) {
	$popup_options_add['groupid'] = $data['groupid'];
	$popup_options_add['hostid'] = $data['hostid'];
}

$popup_options_add_prototype = [
	'srctbl' => 'item_prototypes',
	'srcfld1' => 'itemid',
	'srcfld2' => 'name',
	'dstfrm' => $graphForm->getName(),
	'numeric' => '1',
	'writeonly' => '1',
	'multiselect' => '1',
	'graphtype' => $data['graphtype']
];
if ($data['normal_only']) {
	$popup_options_add_prototype['normal_only'] = '1';
}
if ($data['parent_discoveryid']) {
	$popup_options_add_prototype['parent_discoveryid'] = $data['parent_discoveryid'];
}

$items_table->addRow(
	(new CRow(
		$readonly
			? null
			: (new CCol(
				new CHorList([
					(new CButton('add_item', _('Add')))
						->onClick('return PopUp("popup.generic",jQuery.extend('.
							CJs::encodeJson($popup_options_add).',getOnlyHostParam()), null, this);'
						)
						->addClass(ZBX_STYLE_BTN_LINK),
					$data['parent_discoveryid']
						? (new CButton('add_protoitem', _('Add prototype')))
							->onClick('return PopUp("popup.generic",'.
								CJs::encodeJson($popup_options_add_prototype).', null, this);'
							)
							->addClass(ZBX_STYLE_BTN_LINK)
						: null
				])
			))->setColSpan(8)
	))->setId('itemButtonsRow')
);

foreach ($data['items'] as $n => $item) {
	if (!$item['itemid']) {
		continue;
	}

	$name = $item['host'].NAME_DELIMITER.$item['name_expanded'];

	if (zbx_empty($item['drawtype'])) {
		$item['drawtype'] = 0;
	}

	if (zbx_empty($item['yaxisside'])) {
		$item['yaxisside'] = 0;
	}

	if (!array_key_exists('gitemid', $item)) {
		$item['gitemid'] = '';
	}

	insert_js('loadItem('.$n.', '.CJs::encodeJson($item['gitemid']).', '.$item['itemid'].', '.
		CJs::encodeJson($name).', '.$item['type'].', '.$item['calc_fnc'].', '.$item['drawtype'].', '.
		$item['yaxisside'].', \''.$item['color'].'\', '.$item['flags'].');',
		true
	);
}

$graphFormList->addRow(
	(new CLabel(_('Items'), $items_table->getId()))->setAsteriskMark(),
	(new CDiv($items_table))->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
);

// Append tabs to form.
$graphTab = (new CTabView())
	->setSelected(0)
	->addTab('graphTab', ($data['parent_discoveryid'] === null) ? _('Graph') : _('Graph prototype'), $graphFormList);

/*
 * Preview tab
 */
$graphPreviewTable = (new CTable())
	->addStyle('width: 100%;')
	->addRow(
		(new CRow(
			(new CDiv())->setId('previewChart')
		))->addClass(ZBX_STYLE_CENTER)
	);
$graphTab->addTab('previewTab', _('Preview'), $graphPreviewTable);

// Append buttons to form.
if ($data['graphid'] != 0) {
	$updateButton = new CSubmit('update', _('Update'));
	$deleteButton = new CButtonDelete(
		($data['parent_discoveryid'] === null) ? _('Delete graph?') : _('Delete graph prototype?'),
		url_params(['graphid', 'parent_discoveryid', 'hostid'])
	);

	if ($readonly) {
		$updateButton->setEnabled(false);
	}

	if ($is_templated) {
		$deleteButton->setEnabled(false);
	}

	$graphTab->setFooter(makeFormFooter(
		$updateButton, [
			new CSubmit('clone', _('Clone')),
			$deleteButton,
			new CButtonCancel(url_param('parent_discoveryid').url_param('hostid', $data['hostid']))
		]
	));
}
else {
	$graphTab->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('parent_discoveryid').url_param('hostid', $data['hostid']))]
	));
}

// Insert js (depended from some variables inside the file).
require_once dirname(__FILE__).'/js/configuration.graph.edit.js.php';

$graphForm->addItem($graphTab);

// Append form to widget.
$widget->addItem($graphForm);

return $widget;
