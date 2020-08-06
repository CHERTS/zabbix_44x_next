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


require_once dirname(__FILE__).'/js/configuration.host.edit.js.php';
require_once dirname(__FILE__).'/js/common.template.edit.js.php';

$widget = (new CWidget())
	->setTitle(_('Hosts'))
	->addItem(get_header_host_table('', $data['hostid']));

$divTabs = new CTabView();

if (!hasRequest('form_refresh')) {
	$divTabs->setSelected(0);
}

$frmHost = (new CForm())
	->setId('hostsForm')
	->setName('hostsForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form'])
	->addVar('clear_templates', $data['clear_templates'])
	->addVar('flags', $data['flags'])
	->addItem((new CVar('tls_connect', $data['tls_connect']))->removeId())
	->addVar('tls_accept', $data['tls_accept']);

if ($data['hostid'] != 0) {
	$frmHost->addVar('hostid', $data['hostid']);
}
if ($data['clone_hostid'] != 0) {
	$frmHost->addVar('clone_hostid', $data['clone_hostid']);
}

$hostList = new CFormList('hostlist');

// LLD rule link
if ($data['readonly']) {
	$hostList->addRow(_('Discovered by'), $data['discoveryRule']
		? new CLink($data['discoveryRule']['name'],
			(new CUrl('host_prototypes.php'))
				->setArgument('form', 'update')
				->setArgument('parent_discoveryid', $data['discoveryRule']['itemid'])
				->setArgument('hostid', $data['hostDiscovery']['parent_hostid'])
		)
		: (new CSpan(_('Inaccessible discovery rule')))->addClass(ZBX_STYLE_GREY)
	);
}

$hostList
	->addRow(
		(new CLabel(_('Host name'), 'host'))->setAsteriskMark(),
		(new CTextBox('host', $data['host'], $data['readonly'], 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
	)
	->addRow(_('Visible name'),
		(new CTextBox('visiblename', $data['visiblename'], $data['readonly'], 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow((new CLabel(_('Groups'), 'groups__ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'groups[]',
			'object_name' => 'hostGroup',
			'disabled' => $data['readonly'],
			'add_new' => (CWebUser::$data['type'] == USER_TYPE_SUPER_ADMIN),
			'data' => $data['groups_ms'],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $frmHost->getName(),
					'dstfld1' => 'groups_',
					'editable' => true
				]
			]
		]))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);

// Interfaces for discovered hosts.
if ($data['readonly']) {
	$existingInterfaceTypes = [];
	foreach ($data['interfaces'] as $interface) {
		$existingInterfaceTypes[$interface['type']] = true;
	}
	zbx_add_post_js('hostInterfacesManager.add('.CJs::encodeJson($data['interfaces']).');');
	zbx_add_post_js('hostInterfacesManager.disable();');

	$hostList->addVar('interfaces', $data['interfaces']);

	// Zabbix agent interfaces.
	$ifTab = (new CTable())
		->setId('agentInterfaces')
		->setHeader([
			new CColHeader(),
			new CColHeader(_('IP address')),
			new CColHeader(_('DNS name')),
			new CColHeader(_('Connect to')),
			new CColHeader(_('Port')),
			(new CColHeader(_('Default')))->setColSpan(2)
		]);

	$row = (new CRow())->setId('agentInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_AGENT, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No agent interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('Agent interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'agent')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// SNMP interfaces.
	$ifTab = (new CTable())->setId('SNMPInterfaces');

	$row = (new CRow())->setId('SNMPInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_SNMP, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No SNMP interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('SNMP interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'snmp')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// JMX interfaces.
	$ifTab = (new CTable())->setId('JMXInterfaces');

	$row = (new CRow())->setId('JMXInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_JMX, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No JMX interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('JMX interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'jmx')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// IPMI interfaces.
	$ifTab = (new CTable())->setId('IPMIInterfaces');

	$row = (new CRow())->setId('IPMIInterfacesFooter');
	if (!array_key_exists(INTERFACE_TYPE_IPMI, $existingInterfaceTypes)) {
		$row->addItem(new CCol());
		$row->addItem((new CCol(_('No IPMI interfaces found.')))->setColSpan(6));
	}
	$ifTab->addRow($row);

	$hostList->addRow(_('IPMI interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'ipmi')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);
}
// Interfaces for normal hosts.
else {
	zbx_add_post_js($data['interfaces']
		? 'hostInterfacesManager.add('.CJs::encodeJson($data['interfaces']).');'
		: 'hostInterfacesManager.addNew("agent");');

	$hostList->addRow('',
		(new CLabel(_('At least one interface must exist.')))->setAsteriskMark()
	);
	// Zabbix agent interfaces.
	$ifTab = (new CTable())
		->setId('agentInterfaces')
		->setHeader([
			new CColHeader(),
			new CColHeader(_('IP address')),
			new CColHeader(_('DNS name')),
			new CColHeader(_('Connect to')),
			new CColHeader(_('Port')),
			(new CColHeader(_('Default')))->setColSpan(2)
		])
		->addRow((new CRow([
			(new CCol(
				(new CButton('addAgentInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('agentInterfacesFooter'));

	$hostList->addRow(_('Agent interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'agent')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// SNMP interfaces.
	$ifTab = (new CTable())
		->setId('SNMPInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addSNMPInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('SNMPInterfacesFooter'));

	$hostList->addRow(_('SNMP interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'snmp')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// JMX interfaces.
	$ifTab = (new CTable())
		->setId('JMXInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addJMXInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('JMXInterfacesFooter'));

	$hostList->addRow(_('JMX interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'jmx')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);

	// IPMI interfaces.
	$ifTab = (new CTable())
		->setId('IPMIInterfaces')
		->addRow((new CRow([
			(new CCol(
				(new CButton('addIPMIInterface', _('Add')))->addClass(ZBX_STYLE_BTN_LINK)
			))->setColSpan(7)
		]))->setId('IPMIInterfacesFooter'));

	$hostList->addRow(_('IPMI interfaces'),
		(new CDiv($ifTab))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setAttribute('data-type', 'ipmi')
			->setWidth(ZBX_HOST_INTERFACE_WIDTH)
	);
}

$hostList->addRow(_('Description'),
	(new CTextArea('description', $data['description']))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		->setMaxlength(DB::getFieldLength('hosts', 'description'))
);

// Proxy
if ($data['readonly']) {
	$proxy = (new CTextBox(null,
		($data['proxy_hostid'] == 0) ? _('(no proxy)') : $data['proxies'][$data['proxy_hostid']], true)
	)->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);
	$hostList->addVar('proxy_hostid', $data['proxy_hostid']);
}
else {
	$proxy = new CComboBox('proxy_hostid', $data['proxy_hostid'], null, [0 => _('(no proxy)')] + $data['proxies']);
	$proxy->setEnabled(true);
}

$hostList->addRow(_('Monitored by proxy'), $proxy);

$hostList->addRow(_('Enabled'),
	(new CCheckBox('status', HOST_STATUS_MONITORED))->setChecked($data['status'] == HOST_STATUS_MONITORED)
);

if ($data['clone_hostid'] != 0) {
	// host applications
	$hostApps = API::Application()->get([
		'output' => ['name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'preservekeys' => true,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL]
	]);

	if ($hostApps) {
		$applicationsList = [];
		foreach ($hostApps as $hostAppId => $hostApp) {
			$applicationsList[$hostAppId] = $hostApp['name'];
		}
		order_result($applicationsList);

		$listBox = new CListBox('applications', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($applicationsList);
		$hostList->addRow(_('Applications'), $listBox);
	}

	// host items
	$hostItems = API::Item()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL]
	]);

	if ($hostItems) {
		$hostItems = CMacrosResolverHelper::resolveItemNames($hostItems);

		$itemsList = [];
		foreach ($hostItems as $hostItem) {
			$itemsList[$hostItem['itemid']] = $hostItem['name_expanded'];
		}
		order_result($itemsList);

		$listBox = new CListBox('items', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($itemsList);
		$hostList->addRow(_('Items'), $listBox);
	}

	// host triggers
	$hostTriggers = API::Trigger()->get([
		'output' => ['triggerid', 'description'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]]
	]);

	if ($hostTriggers) {
		$triggersList = [];

		foreach ($hostTriggers as $hostTrigger) {
			if (httpItemExists($hostTrigger['items'])) {
				continue;
			}
			$triggersList[$hostTrigger['triggerid']] = $hostTrigger['description'];
		}

		if ($triggersList) {
			order_result($triggersList);

			$listBox = new CListBox('triggers', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($triggersList);
			$hostList->addRow(_('Triggers'), $listBox);
		}
	}

	// host graphs
	$hostGraphs = API::Graph()->get([
		'output' => ['graphid', 'name'],
		'selectHosts' => ['hostid'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false,
		'filter' => ['flags' => [ZBX_FLAG_DISCOVERY_NORMAL]]
	]);

	if ($hostGraphs) {
		$graphsList = [];
		foreach ($hostGraphs as $hostGraph) {
			if (count($hostGraph['hosts']) > 1) {
				continue;
			}
			if (httpItemExists($hostGraph['items'])) {
				continue;
			}
			$graphsList[$hostGraph['graphid']] = $hostGraph['name'];
		}

		if ($graphsList) {
			order_result($graphsList);

			$listBox = new CListBox('graphs', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($graphsList);
			$hostList->addRow(_('Graphs'), $listBox);
		}
	}

	// discovery rules
	$hostDiscoveryRuleIds = [];

	$hostDiscoveryRules = API::DiscoveryRule()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false
	]);

	if ($hostDiscoveryRules) {
		$hostDiscoveryRules = CMacrosResolverHelper::resolveItemNames($hostDiscoveryRules);

		$discoveryRuleList = [];
		foreach ($hostDiscoveryRules as $discoveryRule) {
			$discoveryRuleList[$discoveryRule['itemid']] = $discoveryRule['name_expanded'];
		}
		order_result($discoveryRuleList);
		$hostDiscoveryRuleIds = array_keys($discoveryRuleList);

		$listBox = new CListBox('discoveryRules', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($discoveryRuleList);
		$hostList->addRow(_('Discovery rules'), $listBox);
	}

	// item prototypes
	$hostItemPrototypes = API::ItemPrototype()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostItemPrototypes) {
		$hostItemPrototypes = CMacrosResolverHelper::resolveItemNames($hostItemPrototypes);

		$prototypeList = [];
		foreach ($hostItemPrototypes as $itemPrototype) {
			$prototypeList[$itemPrototype['itemid']] = $itemPrototype['name_expanded'];
		}
		order_result($prototypeList);

		$listBox = new CListBox('itemsPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Item prototypes'), $listBox);
	}

	// Trigger prototypes
	$hostTriggerPrototypes = API::TriggerPrototype()->get([
		'output' => ['triggerid', 'description'],
		'selectItems' => ['type'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostTriggerPrototypes) {
		$prototypeList = [];
		foreach ($hostTriggerPrototypes as $triggerPrototype) {
			// skip trigger prototypes with web items
			if (httpItemExists($triggerPrototype['items'])) {
				continue;
			}
			$prototypeList[$triggerPrototype['triggerid']] = $triggerPrototype['description'];
		}

		if ($prototypeList) {
			order_result($prototypeList);

			$listBox = new CListBox('triggerprototypes', null, 8);
			$listBox->setAttribute('disabled', 'disabled');
			$listBox->addItems($prototypeList);
			$hostList->addRow(_('Trigger prototypes'), $listBox);
		}
	}

	// Graph prototypes
	$hostGraphPrototypes = API::GraphPrototype()->get([
		'output' => ['graphid', 'name'],
		'selectHosts' => ['hostid'],
		'hostids' => [$data['clone_hostid']],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostGraphPrototypes) {
		$prototypeList = [];
		foreach ($hostGraphPrototypes as $graphPrototype) {
			if (count($graphPrototype['hosts']) == 1) {
				$prototypeList[$graphPrototype['graphid']] = $graphPrototype['name'];
			}
		}
		order_result($prototypeList);

		$listBox = new CListBox('graphPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Graph prototypes'), $listBox);
	}

	// host prototypes
	$hostPrototypes = API::HostPrototype()->get([
		'output' => ['hostid', 'name'],
		'discoveryids' => $hostDiscoveryRuleIds,
		'inherited' => false
	]);

	if ($hostPrototypes) {
		$prototypeList = [];
		foreach ($hostPrototypes as $hostPrototype) {
			$prototypeList[$hostPrototype['hostid']] = $hostPrototype['name'];
		}
		order_result($prototypeList);

		$listBox = new CListBox('hostPrototypes', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($prototypeList);
		$hostList->addRow(_('Host prototypes'), $listBox);
	}

	// web scenarios
	$httpTests = API::HttpTest()->get([
		'output' => ['httptestid', 'name'],
		'hostids' => [$data['clone_hostid']],
		'inherited' => false
	]);

	if ($httpTests) {
		$httpTestList = [];

		foreach ($httpTests as $httpTest) {
			$httpTestList[$httpTest['httptestid']] = $httpTest['name'];
		}

		order_result($httpTestList);

		$listBox = new CListBox('httpTests', null, 8);
		$listBox->setAttribute('disabled', 'disabled');
		$listBox->addItems($httpTestList);
		$hostList->addRow(_('Web scenarios'), $listBox);
	}
}

$divTabs->addTab('hostTab', _('Host'), $hostList);

// templates
$tmplList = new CFormList();

// Templates for discovered hosts.
if ($data['readonly']) {
	$linkedTemplateTable = (new CTable())
		->setHeader([_('Name')])
		->addStyle('width: 100%;');

	foreach ($data['linked_templates'] as $template) {
		$tmplList->addVar('templates[]', $template['templateid']);

		if (array_key_exists($template['templateid'], $data['writable_templates'])) {
			$template_link = (new CLink($template['name'],
				(new CUrl('templates.php'))
					->setArgument('form','update')
					->setArgument('templateid', $template['templateid'])
			))->setTarget('_blank');
		}
		else {
			$template_link = new CSpan($template['name']);
		}

		$linkedTemplateTable->addRow($template_link, null, 'conditions_'.$template['templateid']);
	}

	$tmplList->addRow(_('Linked templates'),
		(new CDiv($linkedTemplateTable))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->addStyle('min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
	);
}
// Templates for normal hosts.
else {
	$disableids = [];

	$linkedTemplateTable = (new CTable())
		->setAttribute('style', 'width: 100%;')
		->setHeader([_('Name'), _('Action')]);

	foreach ($data['linked_templates'] as $template) {
		$tmplList->addItem((new CVar('templates['.$template['templateid'].']', $template['templateid']))->removeId());

		if (array_key_exists($template['templateid'], $data['writable_templates'])) {
			$template_link = (new CLink($template['name'],
				'templates.php?form=update&templateid='.$template['templateid']
			))->setTarget('_blank');
		}
		else {
			$template_link = new CSpan($template['name']);
		}

		$linkedTemplateTable->addRow([
			$template_link,
			(new CCol(
				new CHorList([
					(new CSimpleButton(_('Unlink')))
						->onClick('javascript: submitFormWithParam('.
							'"'.$frmHost->getName().'", "unlink['.$template['templateid'].']", "1"'.
						');')
						->addClass(ZBX_STYLE_BTN_LINK),
					array_key_exists($template['templateid'], $data['original_templates'])
						? (new CSimpleButton(_('Unlink and clear')))
							->onClick('javascript: submitFormWithParam('.
								'"'.$frmHost->getName().'", "unlink_and_clear['.$template['templateid'].']", "1"'.
							');')
							->addClass(ZBX_STYLE_BTN_LINK)
						: null
				])
			))->addClass(ZBX_STYLE_NOWRAP)
		], null, 'conditions_'.$template['templateid']);

		$disableids[] = $template['templateid'];
	}

	$add_templates_ms = (new CMultiSelect([
		'name' => 'add_templates[]',
		'object_name' => 'templates',
		'data' => $data['add_templates'],
		'popup' => [
			'parameters' => [
				'srctbl' => 'templates',
				'srcfld1' => 'hostid',
				'srcfld2' => 'host',
				'dstfrm' => $frmHost->getName(),
				'dstfld1' => 'add_templates_',
				'disableids' => $disableids
			]
		]
	]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

	$tmplList
		->addRow(_('Linked templates'),
			(new CDiv($linkedTemplateTable))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		)
		->addRow((new CLabel(_('Link new templates'), 'add_templates__ms')),
			(new CDiv(
				(new CTable())->addRow([$add_templates_ms])
			))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH.'px;')
		);
}

$divTabs->addTab('templateTab', _('Templates'), $tmplList);

/*
 * IPMI
 */
if ($data['readonly']) {
	$cmbIPMIAuthtype = [
		(new CTextBox('ipmi_authtype_name', ipmiAuthTypes($data['ipmi_authtype']), true))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		new CVar('ipmi_authtype', $data['ipmi_authtype'])
	];
	$cmbIPMIPrivilege = [
		(new CTextBox('ipmi_privilege_name', ipmiPrivileges($data['ipmi_privilege']), true))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		new CVar('ipmi_privilege', $data['ipmi_privilege'])
	];
}
else {
	$cmbIPMIAuthtype = new CListBox('ipmi_authtype', $data['ipmi_authtype'], 7, null, ipmiAuthTypes());
	$cmbIPMIPrivilege = new CListBox('ipmi_privilege', $data['ipmi_privilege'], 5, null, ipmiPrivileges());
}

$divTabs->addTab('ipmiTab', _('IPMI'),
	(new CFormList())
		->addRow(_('Authentication algorithm'), $cmbIPMIAuthtype)
		->addRow(_('Privilege level'), $cmbIPMIPrivilege)
		->addRow(_('Username'),
			(new CTextBox('ipmi_username', $data['ipmi_username'], $data['readonly']))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
		)
		->addRow(_('Password'),
			(new CTextBox('ipmi_password', $data['ipmi_password'], $data['readonly']))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
		)
);

/*
 * Tags
 */
if (!$data['readonly']) {
	$tags_view = new CView('configuration.tags.tab', [
		'source' => 'host',
		'tags' => $data['tags'],
		'readonly' => false
	]);
	$divTabs->addTab('tags-tab', _('Tags'), $tags_view->render());
}

/*
 * Macros
 */
$divTabs->addTab('macroTab', _('Macros'),
	(new CFormList('macrosFormList'))
		->addRow(null, (new CRadioButtonList('show_inherited_macros', (int) $data['show_inherited_macros']))
			->addValue(_('Host macros'), 0)
			->addValue(_('Inherited and host macros'), 1)
			->setModern(true)
		)
		->addRow(null, new CObject((new CView('hostmacros.list.html', [
			'macros' => $data['macros'],
			'show_inherited_macros' => $data['show_inherited_macros'],
			'readonly' => $data['readonly']
		]))->getOutput()), 'macros_container')
);

$inventoryFormList = new CFormList('inventorylist');

$inventoryFormList->addRow(null,
	(new CRadioButtonList('inventory_mode', (int) $data['inventory_mode']))
		->addValue(_('Disabled'), HOST_INVENTORY_DISABLED)
		->addValue(_('Manual'), HOST_INVENTORY_MANUAL)
		->addValue(_('Automatic'), HOST_INVENTORY_AUTOMATIC)
		->setEnabled(!$data['readonly'])
		->setModern(true)
);
if ($data['readonly']) {
	$inventoryFormList->addVar('inventory_mode', $data['inventory_mode']);
}

$hostInventoryTable = DB::getSchema('host_inventory');
$hostInventoryFields = getHostInventories();

foreach ($hostInventoryFields as $inventoryNo => $inventoryInfo) {
	$field_name = $inventoryInfo['db_field'];

	if (!array_key_exists($field_name, $data['host_inventory'])) {
		$data['host_inventory'][$field_name] = '';
	}

	if ($hostInventoryTable['fields'][$field_name]['type'] == DB::FIELD_TYPE_TEXT) {
		$input = (new CTextArea('host_inventory['.$field_name.']', $data['host_inventory'][$field_name]))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH);
	}
	else {
		$input = (new CTextBox('host_inventory['.$field_name.']', $data['host_inventory'][$field_name]))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAttribute('maxlength', $hostInventoryTable['fields'][$field_name]['length']);
	}

	if ($data['inventory_mode'] == HOST_INVENTORY_DISABLED) {
		$input->setAttribute('disabled', 'disabled');
	}

	// link to populating item at the right side (if any)
	if (array_key_exists($inventoryNo, $data['inventory_items'])) {
		$name = $data['inventory_items'][$inventoryNo]['name_expanded'];

		$link = (new CLink($name, 'items.php?form=update&itemid='.$data['inventory_items'][$inventoryNo]['itemid']))
			->setTitle(_s('This field is automatically populated by item "%s".', $name));

		$inventory_item = (new CSpan([' &larr; ', $link]))->addClass('populating_item');
		if ($data['inventory_mode'] != HOST_INVENTORY_AUTOMATIC) {
			// those links are visible only in automatic mode
			$inventory_item->addStyle('display: none');
		}

		// this will be used for disabling fields via jquery
		$input->addClass('linked_to_item');
		if ($data['inventory_mode'] == HOST_INVENTORY_AUTOMATIC) {
			$input->setAttribute('disabled', 'disabled');
		}
	}
	else {
		$inventory_item = null;
	}

	$inventoryFormList->addRow($inventoryInfo['title'], [$input, $inventory_item]);
}

$divTabs->addTab('inventoryTab', _('Inventory'), $inventoryFormList);

// Encryption form list.
$encryption_form_list = (new CFormList('encryption'))
	->addRow(_('Connections to host'),
		(new CRadioButtonList('tls_connect', (int) $data['tls_connect']))
			->addValue(_('No encryption'), HOST_ENCRYPTION_NONE)
			->addValue(_('PSK'), HOST_ENCRYPTION_PSK)
			->addValue(_('Certificate'), HOST_ENCRYPTION_CERTIFICATE)
			->setModern(true)
			->setEnabled(!$data['readonly'])
	)
	->addRow(_('Connections from host'),
		(new CList())
			->addClass(ZBX_STYLE_LIST_CHECK_RADIO)
			->addItem((new CCheckBox('tls_in_none'))
				->setLabel(_('No encryption'))
				->setEnabled(!$data['readonly'])
			)
			->addItem((new CCheckBox('tls_in_psk'))
				->setLabel(_('PSK'))
				->setEnabled(!$data['readonly'])
			)
			->addItem((new CCheckBox('tls_in_cert'))
				->setLabel(_('Certificate'))
				->setEnabled(!$data['readonly'])
			)
	)
	->addRow(
		(new CLabel(_('PSK identity'), 'tls_psk_identity'))->setAsteriskMark(),
		(new CTextBox('tls_psk_identity', $data['tls_psk_identity'], $data['readonly'], 128))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAriaRequired()
	)
	->addRow(
		(new CLabel(_('PSK'), 'tls_psk'))->setAsteriskMark(),
		(new CTextBox('tls_psk', $data['tls_psk'], $data['readonly'], 512))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAriaRequired()
	)
	->addRow(_('Issuer'),
		(new CTextBox('tls_issuer', $data['tls_issuer'], $data['readonly'], 1024))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
	)
	->addRow(_x('Subject', 'encryption certificate'),
		(new CTextBox('tls_subject', $data['tls_subject'], $data['readonly'], 1024))
			->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
	);

$divTabs->addTab('encryptionTab', _('Encryption'), $encryption_form_list);

/*
 * footer
 */
// Do not display the clone and delete buttons for clone forms and new host forms.
if ($data['hostid'] != 0) {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			new CSubmit('clone', _('Clone')),
			new CSubmit('full_clone', _('Full clone')),
			new CButtonDelete(_('Delete selected host?'), url_param('form').url_param('hostid').url_param('groupid')),
			new CButtonCancel(url_param('groupid'))
		]
	));
}
else {
	$divTabs->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('groupid'))]
	));
}

$frmHost->addItem($divTabs);
$widget->addItem($frmHost);

return $widget;
