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


// Overview tab.
$overviewFormList = new CFormList();

$host_name = (new CLinkAction($data['host']['host']))
	->setMenuPopup(CMenuPopupHelper::getHost($data['host']['hostid'], false));

if ($data['host']['maintenance_status'] == HOST_MAINTENANCE_STATUS_ON) {
	if (array_key_exists($data['host']['maintenanceid'], $data['maintenances'])) {
		$maintenance = $data['maintenances'][$data['host']['maintenanceid']];
		$maintenance_icon = makeMaintenanceIcon($data['host']['maintenance_type'], $maintenance['name'],
			$maintenance['description']
		);
	}
	else {
		$maintenance_icon = makeMaintenanceIcon($data['host']['maintenance_type'], _('Inaccessible maintenance'),
			''
		);
	}

	$host_name = (new CSpan([$host_name, $maintenance_icon]))->addClass(ZBX_STYLE_REL_CONTAINER);
}

$overviewFormList->addRow(_('Host name'), (new CDiv($host_name))->setWidth(ZBX_TEXTAREA_BIG_WIDTH));

if ($data['host']['host'] !== $data['host']['name']) {
	$overviewFormList->addRow(_('Visible name'), (new CDiv($data['host']['name']))->setWidth(ZBX_TEXTAREA_BIG_WIDTH));
}

$interfaces = [
	INTERFACE_TYPE_AGENT => [],
	INTERFACE_TYPE_SNMP => [],
	INTERFACE_TYPE_JMX => [],
	INTERFACE_TYPE_IPMI => []
];

$interface_names = [
	INTERFACE_TYPE_AGENT => _('Agent interfaces'),
	INTERFACE_TYPE_SNMP => _('SNMP interfaces'),
	INTERFACE_TYPE_JMX => _('JMX interfaces'),
	INTERFACE_TYPE_IPMI => _('IPMI interfaces')
];

foreach ($data['host']['interfaces'] as $interface) {
	$interfaces[$interface['type']][] = $interface;
}

$header_is_set = false;

foreach ([INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI] as $type) {
	if ($interfaces[$type]) {
		$ifTab = (new CTable());

		if (!$header_is_set) {
			$ifTab->setHeader([_('IP address'), _('DNS name'), _('Connect to'), _('Port'), _('Default')]);
			$header_is_set = true;
		}

		foreach ($interfaces[$type] as $interface) {
			$ifTab->addRow([
				(new CTextBox('ip', $interface['ip'], true, 64))
					->setWidth(ZBX_TEXTAREA_INTERFACE_IP_WIDTH)
					->removeId(),
				(new CTextBox('dns', $interface['dns'], true, 64))
					->setWidth(ZBX_TEXTAREA_INTERFACE_DNS_WIDTH)
					->removeId(),
				(new CRadioButtonList('useip['.$interface['interfaceid'].']', (int) $interface['useip']))
					->addValue(_('IP'), INTERFACE_USE_IP)
					->addValue(_('DNS'), INTERFACE_USE_DNS)
					->setModern(true)
					->setEnabled(false),
				(new CTextBox('port', $interface['port'], true, 64))
					->setWidth(ZBX_TEXTAREA_INTERFACE_PORT_WIDTH)
					->removeId(),
				(new CRadioButtonList('main['.$interface['interfaceid'].']', (int) $interface['main']))
					->addValue(null, INTERFACE_PRIMARY)
					->setEnabled(false)
					->removeId()
			]);
		}

		$overviewFormList->addRow($interface_names[$type],
			(new CDiv($ifTab))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setWidth(ZBX_HOST_INTERFACE_WIDTH)
		);
	}
}

// inventory (OS, Hardware, Software)
foreach (['os', 'hardware', 'software'] as $key) {
	if (array_key_exists($key, $data['host']['inventory'])) {
		if ($data['host']['inventory'][$key] !== '') {
			$overviewFormList->addRow($data['tableTitles'][$key]['title'],
				(new CDiv(zbx_str2links($data['host']['inventory'][$key])))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
			);
		}
	}
}

// description
if ($data['host']['description'] !== '') {
	$overviewFormList->addRow(_('Description'),
		(new CDiv(zbx_str2links($data['host']['description'])))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
	);
}

// latest data
$overviewFormList->addRow(_('Monitoring'),
	new CHorList([
		new CLink(_('Web'),
			'zabbix.php?action=web.view&hostid='.$data['host']['hostid'].url_param('groupid')
		),
		new CLink(_('Latest data'),
			(new CUrl('latest.php'))
				->setArgument('form', '1')
				->setArgument('select', '')
				->setArgument('show_details', '1')
				->setArgument('filter_set', 'Filter')
				->setArgument('hostids[]', $data['host']['hostid'])
		),
		new CLink(_('Problems'),
			(new CUrl('zabbix.php'))
				->setArgument('action', 'problem.view')
				->setArgument('filter_hostids[]', $data['host']['hostid'])
				->setArgument('filter_set', '1')
		),
		new CLink(_('Graphs'), 'charts.php?hostid='.$data['host']['hostid'].url_param('groupid')),
		new CLink(_('Screens'),
			'host_screen.php?hostid='.$data['host']['hostid'].url_param('groupid')
		)
	])
);

// configuration
if ($data['rwHost']) {
	$hostLink = new CLink(_('Host'), 'hosts.php?form=update&hostid='.$data['host']['hostid'].url_param('groupid'));
	$applicationsLink = new CLink(_('Applications'),
		'applications.php?hostid='.$data['host']['hostid'].url_param('groupid')
	);

	$itemsLink = new CLink(_('Items'),
		(new CUrl('items.php'))
			->setArgument('filter_set', '1')
			->setArgument('filter_hostids', [$data['host']['hostid']])
	);
	$triggersLink = new CLink(_('Triggers'),
		(new CUrl('triggers.php'))
			->setArgument('filter_set', '1')
			->setArgument('filter_hostids', [$data['host']['hostid']])
	);
	$graphsLink = new CLink(_('Graphs'), 'graphs.php?hostid='.$data['host']['hostid'].url_param('groupid'));
	$discoveryLink = new CLink(_('Discovery'),
		'host_discovery.php?hostid='.$data['host']['hostid'].url_param('groupid')
	);
	$webLink = new CLink(_('Web'), 'httpconf.php?hostid='.$data['host']['hostid'].url_param('groupid'));
}
else {
	$hostLink = _('Host');
	$applicationsLink = _('Application');
	$itemsLink = _('Items');
	$triggersLink = _('Triggers');
	$graphsLink = _('Graphs');
	$discoveryLink = _('Discovery');
	$webLink = _('Web');
}

$overviewFormList->addRow(_('Configuration'),
	new CHorList([
		$hostLink,
		(new CSpan([$applicationsLink, CViewHelper::showNum($data['host']['applications'])])),
		(new CSpan([$itemsLink, CViewHelper::showNum($data['host']['items'])])),
		(new CSpan([$triggersLink, CViewHelper::showNum($data['host']['triggers'])])),
		(new CSpan([$graphsLink, CViewHelper::showNum($data['host']['graphs'])])),
		(new CSpan([$discoveryLink, CViewHelper::showNum($data['host']['discoveries'])])),
		(new CSpan([$webLink, CViewHelper::showNum($data['host']['httpTests'])]))
	])
);

$hostInventoriesTab = (new CTabView(['remember' => true]))
	->setSelected(0)
	->addTab('overviewTab', _('Overview'), $overviewFormList);

/*
 * Details tab
 */
$detailsFormList = new CFormList();

$inventoryValues = false;
foreach ($data['host']['inventory'] as $key => $value) {
	if ($value !== '') {
		$detailsFormList->addRow($data['tableTitles'][$key]['title'],
			(new CDiv(zbx_str2links($value)))->setWidth(ZBX_TEXTAREA_BIG_WIDTH)
		);

		$inventoryValues = true;
	}
}

if (!$inventoryValues) {
	$hostInventoriesTab->setDisabled([1]);
}

$hostInventoriesTab->addTab('detailsTab', _('Details'), $detailsFormList);

// append tabs and form
$hostInventoriesTab->setFooter(makeFormFooter(null, [new CButtonCancel(url_param('groupid'))]));

return (new CWidget())
	->setTitle(_('Host inventory'))
	->setWebLayoutMode(CView::getLayoutMode())
	->setControls((new CList())->addItem(get_icon('fullscreen')))
	->addItem((new CForm())
		->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
		->addItem($hostInventoriesTab)
	);
