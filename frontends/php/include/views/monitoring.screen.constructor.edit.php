<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


$action = 'screenedit.php?form=update&screenid='.getRequest('screenid');
if (isset($_REQUEST['screenitemid'])) {
	$action .= '&screenitemid='.getRequest('screenitemid');
}

$form = (new CForm('post', $action))
	->setName('screen_item_form')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('screenid', getRequest('screenid'));

if (array_key_exists('templateid', $data['screen'])) {
	$form->addVar('templateid', $data['screen']['templateid']);
}

if (hasRequest('screenitemid')) {
	$form->addVar('screenitemid', getRequest('screenitemid'));
	$screenItems = zbx_toHash($this->data['screen']['screenitems'], 'screenitemid');
}
else {
	$form
		->addVar('x', getRequest('x'))
		->addVar('y', getRequest('y'));
}

if (isset($_REQUEST['screenitemid']) && !isset($_REQUEST['form_refresh'])) {
	$screenItem		= $screenItems[$_REQUEST['screenitemid']];
	$resourceType	= $screenItem['resourcetype'];
	$resourceId		= $screenItem['resourceid'];
	$width			= $screenItem['width'];
	$height			= $screenItem['height'];
	$colspan		= $screenItem['colspan'];
	$rowspan		= $screenItem['rowspan'];
	$elements		= $screenItem['elements'];
	$valign			= $screenItem['valign'];
	$halign			= $screenItem['halign'];
	$style			= $screenItem['style'];
	$url			= $screenItem['url'];
	$dynamic		= $screenItem['dynamic'];
	$sortTriggers	= $screenItem['sort_triggers'];
	$application	= $screenItem['application'];
	$maxColumns		= $screenItem['max_columns'];
}
else {
	$resourceType	= getRequest('resourcetype', 0);
	$resourceId		= getRequest('resourceid', 0);
	$width			= getRequest('width', 500);
	$height			= getRequest('height', 100);
	$colspan		= getRequest('colspan', 1);
	$rowspan		= getRequest('rowspan', 1);
	$elements		= getRequest('elements', 25);
	$valign			= getRequest('valign', VALIGN_DEFAULT);
	$halign			= getRequest('halign', HALIGN_DEFAULT);
	$style			= getRequest('style', 0);
	$url			= getRequest('url', '');
	$dynamic		= getRequest('dynamic', SCREEN_SIMPLE_ITEM);
	$sortTriggers	= getRequest('sort_triggers', SCREEN_SORT_TRIGGERS_DATE_DESC);
	$application	= getRequest('application', '');
	$maxColumns		= getRequest('max_columns', 3);
}

// append resource types to form list
$screenResources = screen_resources();
if (array_key_exists('templateid', $data['screen'])) {
	unset(
		$screenResources[SCREEN_RESOURCE_DATA_OVERVIEW], $screenResources[SCREEN_RESOURCE_ACTIONS],
		$screenResources[SCREEN_RESOURCE_EVENTS], $screenResources[SCREEN_RESOURCE_HOST_INFO],
		$screenResources[SCREEN_RESOURCE_MAP], $screenResources[SCREEN_RESOURCE_SERVER_INFO],
		$screenResources[SCREEN_RESOURCE_HOSTGROUP_TRIGGERS], $screenResources[SCREEN_RESOURCE_HOST_TRIGGERS],
		$screenResources[SCREEN_RESOURCE_SYSTEM_STATUS], $screenResources[SCREEN_RESOURCE_TRIGGER_INFO],
		$screenResources[SCREEN_RESOURCE_TRIGGER_OVERVIEW]
	);
}

$screenFormList = (new CFormList())
	->addRow((new CLabel(_('Resource'), 'resourcetype')),
		(new CComboBox('resourcetype', $resourceType, 'submit()', $screenResources))
	);

/*
 * Screen item: Graph
 */
if ($resourceType == SCREEN_RESOURCE_GRAPH) {
	$graph = false;

	if ($resourceId > 0) {
		$graphs = API::Graph()->get([
			'output' => ['name'],
			'selectHosts' => ['name'],
			'graphids' => $resourceId
		]);

		if ($graphs) {
			$graph = reset($graphs);

			order_result($graph['hosts'], 'name');
			$graph['host'] = reset($graph['hosts']);
		}
	}

	$screenFormList->addRow(
		(new CLabel(_('Graph'), 'resourceid_ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'resourceid',
			'object_name' => 'graphs',
			'multiple' => false,
			'data' => $graph
				? [
					[
						'id' => $resourceId,
						'prefix' => $graph['host']['name'].NAME_DELIMITER,
						'name' => $graph['name']
					]
				]
				: [],
			'popup' => [
				'parameters' => array_merge([
					'srctbl' => 'graphs',
					'srcfld1' => 'graphid',
					'srcfld2' => 'name',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid'
				], array_key_exists('templateid', $data['screen']) ? [
					'templated_hosts' => '1',
					'hostid' => $data['screen']['templateid']
				] : [
					'real_hosts' => '1',
					'with_graphs' => '1'
				]),
			]
		]))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(true)
	);
}

/*
 * Screen item: Graph prototype
 */
elseif ($resourceType == SCREEN_RESOURCE_LLD_GRAPH) {
	$graph_prototype = false;

	if ($resourceId > 0) {
		$graph_prototypes = API::GraphPrototype()->get([
			'output' => ['name'],
			'selectHosts' => ['name'],
			'graphids' => $resourceId
		]);

		if ($graph_prototypes) {
			$graph_prototype = reset($graph_prototypes);

			order_result($graph_prototype['hosts'], 'name');
			$graph_prototype['host'] = reset($graph_prototype['hosts']);
		}
	}

	$screenFormList
		->addRow(
			(new CLabel(_('Graph prototype'), 'resourceid_ms'))->setAsteriskMark(),
			(new CMultiSelect([
				'name' => 'resourceid',
				'object_name' => 'graph_prototypes',
				'multiple' => false,
				'data' => $graph_prototype
					? [
						[
							'id' => $resourceId,
							'prefix' => $graph_prototype['host']['name'].NAME_DELIMITER,
							'name' => $graph_prototype['name']
						]
					]
					: [],
				'popup' => [
					'parameters' => array_merge([
						'srctbl' => 'graph_prototypes',
						'srcfld1' => 'graphid',
						'srcfld2' => 'name',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'resourceid'
					], array_key_exists('templateid', $data['screen']) ? [
						'templated_hosts' => '1',
						'hostid' => $data['screen']['templateid']
					] : [
						'real_hosts' => '1'
					]),
				]
			]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
		)
		->addRow(
			(new CLabel(_('Max columns'), 'max_columns'))->setAsteriskMark(),
			(new CNumericBox('max_columns', $maxColumns, 3, false, false, false))
				->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
				->setAriaRequired()
		);
}

/*
 * Screen item: Simple graph
 */
elseif ($resourceType == SCREEN_RESOURCE_SIMPLE_GRAPH) {
	$item = false;

	if ($resourceId > 0) {
		$items = API::Item()->get([
			'itemids' => $resourceId,
			'selectHosts' => ['name'],
			'output' => ['itemid', 'hostid', 'key_', 'name'],
			'webitems' => true
		]);

		if ($items) {
			$items = CMacrosResolverHelper::resolveItemNames($items);
			$item = reset($items);
		}
	}

	$screenFormList->addRow(
		(new CLabel(_('Item'), 'resourceid_ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'resourceid',
			'object_name' => 'items',
			'multiple' => false,
			'data' => $item
				? [
					[
						'id' => $resourceId,
						'prefix' => $item['hosts'][0]['name'].NAME_DELIMITER,
						'name' => $item['name_expanded']
					]
				]
				: [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'items',
					'srcfld1' => 'itemid',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'webitems' => true,
					'numeric' => true,
					'hostid' => array_key_exists('templateid', $data['screen']) ? $data['screen']['templateid'] : 0,
					'real_hosts' => !array_key_exists('templateid', $data['screen']),
					'with_simple_graph_items' => !array_key_exists('templateid', $data['screen'])
				]
			]
		]))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(true)
	);
}

/*
 * Screen item: Simple graph prototype
 */
elseif ($resourceType == SCREEN_RESOURCE_LLD_SIMPLE_GRAPH) {
	$caption = '';
	$id = 0;

	$items = API::ItemPrototype()->get([
		'output' => ['hostid', 'key_', 'name'],
		'itemids' => $resourceId,
		'selectHosts' => ['name']
	]);

	if ($items) {
		$items = CMacrosResolverHelper::resolveItemNames($items);

		$id = $resourceId;
		$item = reset($items);
		$item['host'] = reset($item['hosts']);

		$caption = $item['host']['name'].NAME_DELIMITER.$item['name_expanded'];
	}

	if (array_key_exists('templateid', $data['screen'])) {
		$selectButton = (new CButton('select', _('Select')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('return PopUp("popup.generic",'.
				CJs::encodeJson([
					'srctbl' => 'item_prototypes',
					'srcfld1' => 'itemid',
					'srcfld2' => 'name',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'dstfld2' => 'caption',
					'templated_hosts' => '1',
					'only_hostid' => $data['screen']['templateid'],
					'numeric' => '1'
				]).', null, this);'
			);
	}
	else {
		$selectButton = (new CButton('select', _('Select')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('return PopUp("popup.generic",'.
				CJs::encodeJson([
					'srctbl' => 'item_prototypes',
					'srcfld1' => 'itemid',
					'srcfld2' => 'name',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'dstfld2' => 'caption',
					'real_hosts' => '1',
					'with_discovery_rule' => '1',
					'items' => '1',
					'numeric' => '1'
				]).', null, this);'
			);
	}

	$form->addVar('resourceid', $id);
	$screenFormList->addRow((new CLabel(_('Item prototype'), 'caption'))->setAsteriskMark(), [
		(new CTextBox('caption', $caption, true))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		$selectButton
	]);

	$screenFormList->addRow(
		(new CLabel(_('Max columns'), 'max_columns'))->setAsteriskMark(),
		(new CNumericBox('max_columns', $maxColumns, 3, false, false, false))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
}

/*
 * Screen item: Map
 */
elseif ($resourceType == SCREEN_RESOURCE_MAP) {
	$caption = '';
	$id = 0;

	$maps = API::Map()->get([
		'sysmapids' => $resourceId,
		'output' => API_OUTPUT_EXTEND
	]);
	if (!empty($maps)) {
		$id = $resourceId;
		$map = reset($maps);
		$caption = $map['name'];
	}

	$form->addVar('resourceid', $id);
	$screenFormList->addRow((new CLabel(_('Map'), 'caption'))->setAsteriskMark(), [
		(new CTextBox('caption', $caption, true))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CButton('select', _('Select')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('return PopUp("popup.generic",'.
				CJs::encodeJson([
					'srctbl' => 'sysmaps',
					'srcfld1' => 'sysmapid',
					'srcfld2' => 'name',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'dstfld2' => 'caption'
				]).', null, this);'
			)
	]);
}

/*
 * Screen item: Plain text
 */
elseif ($resourceType == SCREEN_RESOURCE_PLAIN_TEXT) {
	$item = false;

	if ($resourceId != 0) {
		$items = API::Item()->get([
			'output' => ['itemid', 'hostid', 'key_', 'name'],
			'selectHosts' => ['name'],
			'itemids' => $resourceId,
			'webitems' => true
		]);

		if ($items) {
			$items = CMacrosResolverHelper::resolveItemNames($items);
			$item = reset($items);
		}
	}

	$screenFormList
		->addRow(
			(new CLabel(_('Item'), 'resourceid_ms'))->setAsteriskMark(),
			(new CMultiSelect([
				'name' => 'resourceid',
				'object_name' => 'items',
				'multiple' => false,
				'data' => $item
					? [
						[
							'id' => $resourceId,
							'prefix' => $item['hosts'][0]['name'].NAME_DELIMITER,
							'name' => $item['name_expanded']
						]
					]
					: [],
				'popup' => [
					'parameters' => [
						'srctbl' => 'items',
						'srcfld1' => 'itemid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'resourceid',
						'hostid' => array_key_exists('templateid', $data['screen']) ? $data['screen']['templateid'] : 0,
						'real_hosts' => !array_key_exists('templateid', $data['screen']),
						'webitems' => true
					]
				],
			]))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(true)
		)
		->addRow((new CLabel(_('Show lines'), 'elements'))->setAsteriskMark(),
			(new CNumericBox('elements', $elements, 3))
				->setAriaRequired()
				->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
		)
		->addRow(_('Show text as HTML'),
			(new CCheckBox('style'))
				->setChecked($style == 1)
				->removeId()
		);
}

/*
 * Screen item: Triggers
 */
elseif (in_array($resourceType, [SCREEN_RESOURCE_HOSTGROUP_TRIGGERS, SCREEN_RESOURCE_HOST_TRIGGERS])) {
	$data = [];

	if ($resourceType == SCREEN_RESOURCE_HOSTGROUP_TRIGGERS) {
		if ($resourceId > 0) {
			$data = API::HostGroup()->get([
				'groupids' => $resourceId,
				'output' => ['groupid', 'name']
			]);

			if ($data) {
				$data = reset($data);
			}
		}

		$screenFormList->addRow((new CLabel(_('Group'), 'resourceid_ms')),
			(new CMultiSelect([
				'name' => 'resourceid',
				'object_name' => 'hostGroup',
				'multiple' => false,
				'default_value' => 0,
				'data' => $data
					? [
						[
							'id' => $data['groupid'],
							'name' => $data['name']
						]
					]
					: [],
				'popup' => [
					'parameters' => [
						'srctbl' => 'host_groups',
						'srcfld1' => 'groupid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'resourceid',
						'real_hosts' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		);
	}
	else {
		if ($resourceId > 0) {
			$data = API::Host()->get([
				'hostids' => $resourceId,
				'output' => ['hostid', 'name']
			]);

			if ($data) {
				$data = reset($data);
			}
		}

		$screenFormList->addRow((new CLabel(_('Host'), 'resourceid_ms')),
			(new CMultiSelect([
				'name' => 'resourceid',
				'object_name' => 'hosts',
				'multiple' => false,
				'default_value' => 0,
				'data' => $data
					? [
						[
							'id' => $data['hostid'],
							'name' => $data['name']
						]
					]
					: [],
				'popup' => [
					'parameters' => [
						'srctbl' => 'hosts',
						'srcfld1' => 'hostid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'resourceid'
					]
				]
			]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		);
	}

	$screenFormList->addRow(
		(new CLabel(_('Show lines'), 'elements'))->setAsteriskMark(),
		(new CNumericBox('elements', $elements, 3))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
	$screenFormList->addRow(
		_('Sort triggers by'),
		new CComboBox('sort_triggers', $sortTriggers, null, [
			SCREEN_SORT_TRIGGERS_DATE_DESC => _('Last change (descending)'),
			SCREEN_SORT_TRIGGERS_SEVERITY_DESC => _('Severity (descending)'),
			SCREEN_SORT_TRIGGERS_HOST_NAME_ASC => _('Host (ascending)')
		])
	);
}

/*
 * Screen item: Action log
 */
elseif ($resourceType == SCREEN_RESOURCE_ACTIONS) {
	$screenFormList->addRow(
		(new CLabel(_('Show lines'), 'elements'))->setAsteriskMark(),
		(new CNumericBox('elements', $elements, 3))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
	$screenFormList->addRow(
		_('Sort entries by'),
		new CComboBox('sort_triggers', $sortTriggers, null, [
			SCREEN_SORT_TRIGGERS_TIME_DESC => _('Time (descending)'),
			SCREEN_SORT_TRIGGERS_TIME_ASC => _('Time (ascending)'),
			SCREEN_SORT_TRIGGERS_TYPE_DESC => _('Type (descending)'),
			SCREEN_SORT_TRIGGERS_TYPE_ASC => _('Type (ascending)'),
			SCREEN_SORT_TRIGGERS_STATUS_DESC => _('Status (descending)'),
			SCREEN_SORT_TRIGGERS_STATUS_ASC => _('Status (ascending)'),
			SCREEN_SORT_TRIGGERS_RECIPIENT_DESC => _('Recipient (descending)'),
			SCREEN_SORT_TRIGGERS_RECIPIENT_ASC => _('Recipient (ascending)')
		])
	);
}

/*
 * Screen item: History of events
 */
elseif ($resourceType == SCREEN_RESOURCE_EVENTS) {
	$screenFormList->addRow(
		(new CLabel(_('Show lines'), 'elements'))->setAsteriskMark(),
		(new CNumericBox('elements', $elements, 3))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
	$form->addVar('resourceid', 0);
}

/*
 * Screen item: Overviews
 */
elseif (in_array($resourceType, [SCREEN_RESOURCE_TRIGGER_OVERVIEW, SCREEN_RESOURCE_DATA_OVERVIEW])) {
	$data = [];

	if ($resourceId > 0) {
		$data = API::HostGroup()->get([
			'groupids' => $resourceId,
			'output' => ['groupid', 'name']
		]);

		if ($data) {
			$data = reset($data);
		}
	}

	$screenFormList->addRow((new CLabel(_('Group'), 'resourceid_ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'resourceid',
			'object_name' => 'hostGroup',
			'multiple' => false,
			'data' => $data
				? [
					[
						'id' => $data['groupid'],
						'name' => $data['name']
					]
				]
				: [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'real_hosts' => true
				]
			]
		]))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow(_('Application'), [
		(new CTextBox('application', $application))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CButton('application_select', _('Select')))
			->addClass(ZBX_STYLE_BTN_GREY)
			->onClick('return PopUp("popup.generic", '.
				CJs::encodeJson([
					'srctbl' => 'applications',
					'srcfld1' => 'name',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'application',
					'real_hosts' => '1',
					'with_applications' => '1'
				]).', null, this);'
			)
	]);
}

/*
 * Screen item: Host info
 */
elseif ($resourceType == SCREEN_RESOURCE_HOST_INFO || $resourceType == SCREEN_RESOURCE_TRIGGER_INFO) {
	$data = [];

	if ($resourceId > 0) {
		$data = API::HostGroup()->get([
			'groupids' => $resourceId,
			'output' => ['groupid', 'name']
		]);

		if ($data) {
			$data = reset($data);
		}
	}

	$screenFormList->addRow((new CLabel(_('Group'), 'resourceid_ms')),
		(new CMultiSelect([
			'name' => 'resourceid',
			'object_name' => 'hostGroup',
			'multiple' => false,
			'default_value' => 0,
			'data' => $data
				? [
					[
						'id' => $data['groupid'],
						'name' => $data['name']
					]
				]
				: [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'resourceid',
					'real_hosts' => true
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);
}

/*
 * Screen item: Clock
 */
elseif ($resourceType == SCREEN_RESOURCE_CLOCK) {
	$item = false;

	$screenFormList->addRow(_('Time type'), new CComboBox('style', $style, 'submit()', [
		TIME_TYPE_LOCAL => _('Local time'),
		TIME_TYPE_SERVER => _('Server time'),
		TIME_TYPE_HOST => _('Host time')
	]));

	if ($style == TIME_TYPE_HOST) {
		if ($resourceId > 0) {
			$items = API::Item()->get([
				'output' => ['itemid', 'hostid', 'key_', 'name'],
				'selectHosts' => ['name'],
				'itemids' => $resourceId,
				'webitems' => true
			]);

			if ($items) {
				$items = CMacrosResolverHelper::resolveItemNames($items);
				$item = reset($items);
			}
		}

		$screenFormList->addRow(
			(new CLabel(_('Item'), 'resourceid_ms'))->setAsteriskMark(),
			(new CMultiSelect([
				'name' => 'resourceid',
				'object_name' => 'items',
				'multiple' => false,
				'data' => $item
					? [
						[
							'id' => $resourceId,
							'prefix' => $item['hosts'][0]['name'].NAME_DELIMITER,
							'name' => $item['name_expanded']
						]
					]
					: [],
				'popup' => [
					'parameters' => [
						'srctbl' => 'items',
						'srcfld1' => 'itemid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'resourceid',
						'hostid' => array_key_exists('templateid', $data['screen']) ? $data['screen']['templateid'] : 0,
						'real_hosts' => !array_key_exists('templateid', $data['screen']),
						'webitems' => true
					]
				]
			]))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired(true)
		);
	}
}

/*
 * Append common fields
 */
if (in_array($resourceType, [SCREEN_RESOURCE_HOST_INFO, SCREEN_RESOURCE_TRIGGER_INFO])) {
	$screenFormList->addRow(_('Style'),
		(new CRadioButtonList('style', (int) $style))
			->addValue(_('Horizontal'), STYLE_HORIZONTAL)
			->addValue(_('Vertical'), STYLE_VERTICAL)
			->setModern(true)
	);
}
elseif (in_array($resourceType, [SCREEN_RESOURCE_TRIGGER_OVERVIEW, SCREEN_RESOURCE_DATA_OVERVIEW])) {
	$screenFormList->addRow(_('Hosts location'),
		(new CRadioButtonList('style', (int) $style))
			->addValue(_('Left'), STYLE_LEFT)
			->addValue(_('Top'), STYLE_TOP)
			->setModern(true)
	);
}
elseif ($resourceType != SCREEN_RESOURCE_CLOCK) {
	$form->addVar('style', 0);
}

if (in_array($resourceType, [SCREEN_RESOURCE_URL])) {
	$screenFormList->addRow((new CLabel(_('URL'), 'url'))->setAsteriskMark(),
		(new CTextBox('url', $url))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)->setAriaRequired()
	);
}
else {
	$form->addVar('url', '');
}

$resourcesWithWidthAndHeight = [
	SCREEN_RESOURCE_GRAPH,
	SCREEN_RESOURCE_SIMPLE_GRAPH,
	SCREEN_RESOURCE_CLOCK,
	SCREEN_RESOURCE_URL,
	SCREEN_RESOURCE_LLD_GRAPH,
	SCREEN_RESOURCE_LLD_SIMPLE_GRAPH
];
if (in_array($resourceType, $resourcesWithWidthAndHeight)) {
	$screenFormList->addRow(_('Width'),
		(new CNumericBox('width', $width, 5))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
	$screenFormList->addRow(_('Height'),
		(new CNumericBox('height', $height, 5))->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	);
}
else {
	$form
		->addVar('width', 500)
		->addVar('height', 100);
}

$resourcesWithHAlign = [
	SCREEN_RESOURCE_GRAPH,
	SCREEN_RESOURCE_SIMPLE_GRAPH,
	SCREEN_RESOURCE_MAP,
	SCREEN_RESOURCE_CLOCK,
	SCREEN_RESOURCE_URL,
	SCREEN_RESOURCE_LLD_GRAPH,
	SCREEN_RESOURCE_LLD_SIMPLE_GRAPH
];
if (in_array($resourceType, $resourcesWithHAlign)) {
	$screenFormList->addRow(_('Horizontal align'),
		(new CRadioButtonList('halign', (int) $halign))
			->addValue(_('Left'), HALIGN_LEFT)
			->addValue(_('Centre'), HALIGN_CENTER)
			->addValue(_('Right'), HALIGN_RIGHT)
			->setModern(true)
	);
}
else {
	$form->addVar('halign', 0);
}

$screenFormList->addRow(_('Vertical align'),
	(new CRadioButtonList('valign', (int) $valign))
		->addValue(_('Top'), VALIGN_TOP)
		->addValue(_('Middle'), VALIGN_MIDDLE)
		->addValue(_('Bottom'), VALIGN_BOTTOM)
		->setModern(true)
);
$screenFormList->addRow(
	(new CLabel(_('Column span'), 'colspan'))->setAsteriskMark(),
	(new CNumericBox('colspan', $colspan, 3))
		->setAriaRequired()
		->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
);
$screenFormList->addRow(
	(new CLabel(_('Row span'), 'rowspan'))->setAsteriskMark(),
	(new CNumericBox('rowspan', $rowspan, 3))
		->setAriaRequired()
		->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
);

// dynamic addon
$resourcesWithDynamic = [
	SCREEN_RESOURCE_GRAPH,
	SCREEN_RESOURCE_SIMPLE_GRAPH,
	SCREEN_RESOURCE_PLAIN_TEXT,
	SCREEN_RESOURCE_URL,
	SCREEN_RESOURCE_LLD_GRAPH,
	SCREEN_RESOURCE_LLD_SIMPLE_GRAPH
];
if (!array_key_exists('templateid', $this->data['screen']) && in_array($resourceType, $resourcesWithDynamic)) {
	$screenFormList->addRow(_('Dynamic item'), (new CCheckBox('dynamic'))->setChecked($dynamic == 1));
}

// append list to form
$form->addItem($screenFormList);

// append buttons to form
if (isset($_REQUEST['screenitemid'])) {
	$form->addItem(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			new CButtonDelete(null, url_params(['form', 'screenid', 'templateid', 'screenitemid'])),
			new CButtonCancel(url_params(['screenid', 'templateid']))
		]
	));
}
else {
	$form->addItem(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_params(['screenid', 'templateid']))]
	));
}

return $form;
