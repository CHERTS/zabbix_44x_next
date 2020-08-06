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


$widget = (new CWidget())->setTitle(_('Items'));

$host = $data['host'];

if (!empty($data['hostid'])) {
	$widget->addItem(get_header_host_table('items', $data['hostid']));
}

// Create form.
$form = (new CForm())
	->setName('itemForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form'])
	->addVar('hostid', $data['hostid']);

if (!empty($data['itemid'])) {
	$form->addVar('itemid', $data['itemid']);
}

// Create form list.
$form_list = new CFormList('itemFormList');
if (!empty($data['templates'])) {
	$form_list->addRow(_('Parent items'), $data['templates']);
}

$discovered_item = false;
if (array_key_exists('item', $data) && $data['item']['flags'] == ZBX_FLAG_DISCOVERY_CREATED) {
	$discovered_item = true;
}
$readonly = false;
if ($data['limited'] || $discovered_item) {
	$readonly = true;
}

if ($discovered_item) {
	$form_list->addRow(_('Discovered by'), new CLink($data['item']['discoveryRule']['name'],
		(new CUrl('disc_prototypes.php'))
			->setArgument('form', 'update')
			->setArgument('parent_discoveryid', $data['item']['discoveryRule']['itemid'])
			->setArgument('itemid', $data['item']['itemDiscovery']['parent_itemid'])
	));
}

$form_list->addRow(
	(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
	(new CTextBox('name', $data['name'], $readonly))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		->setAriaRequired()
		->setAttribute('autofocus', 'autofocus')
);

// Append type to form list.
if ($readonly) {
	$form->addVar('type', $data['type']);
	$form_list->addRow((new CLabel(_('Type'), 'type_name')),
		(new CTextBox('type_name', item_type2str($data['type']), true))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
	);
}
else {
	$form_list->addRow((new CLabel(_('Type'), 'type')),
		(new CComboBox('type', $data['type'], null, $data['types']))
	);
}

// Append key to form list.
$key_controls = [(new CTextBox('key', $data['key'], $readonly))
	->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	->setAriaRequired()
];

if (!$readonly) {
	$key_controls[] = (new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN);
	$key_controls[] = (new CButton('keyButton', _('Select')))
		->addClass(ZBX_STYLE_BTN_GREY)
		->onClick('return PopUp("popup.generic",jQuery.extend('.
			CJs::encodeJson([
				'srctbl' => 'help_items',
				'srcfld1' => 'key',
				'dstfrm' => $form->getName(),
				'dstfld1' => 'key'
			]).
				',{itemtype: jQuery("#type option:selected").val()}), null, this);'
		);
}

$form_list
	// Append item key to form list.
	->addRow((new CLabel(_('Key'), 'key'))->setAsteriskMark(), $key_controls)
	// Append ITEM_TYPE_HTTPAGENT URL field to for list.
	->addRow(
		(new CLabel(_('URL'), 'url'))->setAsteriskMark(),
		[
			(new CTextBox('url', $data['url'], $readonly, DB::getFieldLength('items', 'url')))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired(),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CButton('httpcheck_parseurl', _('Parse')))
				->addClass(ZBX_STYLE_BTN_GREY)
				->setEnabled(!$readonly)
				->setAttribute('data-action', 'parse_url')
		],
		'url_row'
	);

// Append ITEM_TYPE_HTTPAGENT Query fields to form list.
$query_fields_data = [];

if (is_array($data['query_fields']) && $data['query_fields']) {
	foreach ($data['query_fields'] as $pair) {
		$query_fields_data[] = ['name' => key($pair), 'value' => reset($pair)];
	}
}
elseif (!$readonly) {
	$query_fields_data[] = ['name' => '', 'value' => ''];
}

$query_fields = (new CTag('script', true))->setAttribute('type', 'text/json');
$query_fields->items = [CJs::encodeJson($query_fields_data)];

$form_list
	->addRow(
		new CLabel(_('Query fields'), 'query_fields_pairs'),
		(new CDiv([
			(new CTable())
				->setAttribute('style', 'width: 100%;')
				->setHeader(['', _('Name'), '', _('Value'), ''])
				->addRow((new CRow)->setAttribute('data-insert-point', 'append'))
				->setFooter(new CRow(
					(new CCol(
						(new CButton(null, _('Add')))
							->addClass(ZBX_STYLE_BTN_LINK)
							->setEnabled(!$readonly)
							->setAttribute('data-row-action', 'add_row')
					))->setColSpan(5)
				)),
			(new CTag('script', true))
				->setAttribute('type', 'text/x-jquery-tmpl')
				->addItem(new CRow([
					(new CCol((new CDiv)->addClass(ZBX_STYLE_DRAG_ICON)))->addClass(ZBX_STYLE_TD_DRAG_ICON),
					(new CTextBox('query_fields[name][#{index}]', '#{name}', $readonly))
						->setAttribute('placeholder', _('name'))
						->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
					'&rArr;',
					(new CTextBox('query_fields[value][#{index}]', '#{value}', $readonly))
						->setAttribute('placeholder', _('value'))
						->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
					(new CButton(null, _('Remove')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->setEnabled(!$readonly)
						->setAttribute('data-row-action', 'remove_row')
				])),
			$query_fields
		]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setId('query_fields_pairs')
			->setAttribute('data-sortable-pairs-table', $readonly ? '0': '1')
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;'),
		'query_fields_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Request type to form list.
	->addRow(
		new CLabel(_('Request type'), 'request_method'),
		[
			$readonly ? new CVar('request_method', $data['request_method']) : null,
			(new CComboBox($readonly ? '' : 'request_method', $data['request_method'], null, [
				HTTPCHECK_REQUEST_GET => 'GET',
				HTTPCHECK_REQUEST_POST => 'POST',
				HTTPCHECK_REQUEST_PUT => 'PUT',
				HTTPCHECK_REQUEST_HEAD => 'HEAD'
			]))->setEnabled(!$readonly)
		],
		'request_method_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Timeout field to form list.
	->addRow(
		new CLabel(_('Timeout'), 'timeout'),
		(new CTextBox('timeout', $data['timeout'], $readonly))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		'timeout_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Request body type to form list.
	->addRow(
		new CLabel(_('Request body type'), 'post_type'),
		(new CRadioButtonList('post_type', (int) $data['post_type']))
			->addValue(_('Raw data'), ZBX_POSTTYPE_RAW)
			->addValue(_('JSON data'), ZBX_POSTTYPE_JSON)
			->addValue(_('XML data'), ZBX_POSTTYPE_XML)
			->setEnabled(!$readonly)
			->setModern(true),
		'post_type_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Request body to form list.
	->addRow(
		new CLabel(_('Request body'), 'posts'),
		(new CTextArea('posts', $data['posts'], compact('readonly')))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'posts_row'
	);

$headers_data = [];

if (is_array($data['headers']) && $data['headers']) {
	foreach ($data['headers'] as $pair) {
		$headers_data[] = ['name' => key($pair), 'value' => reset($pair)];
	}
}
elseif (!$readonly) {
	$headers_data[] = ['name' => '', 'value' => ''];
}
$headers = (new CTag('script', true))->setAttribute('type', 'text/json');
$headers->items = [CJs::encodeJson($headers_data)];

$form_list
	// Append ITEM_TYPE_HTTPAGENT Headers fields to form list.
	->addRow(
		new CLabel(_('Headers'), 'headers_pairs'),
		(new CDiv([
			(new CTable())
				->setAttribute('style', 'width: 100%;')
				->setHeader(['', _('Name'), '', _('Value'), ''])
				->addRow((new CRow)->setAttribute('data-insert-point', 'append'))
				->setFooter(new CRow(
					(new CCol(
						(new CButton(null, _('Add')))
							->addClass(ZBX_STYLE_BTN_LINK)
							->setEnabled(!$readonly)
							->setAttribute('data-row-action', 'add_row')
					))->setColSpan(5)
				)),
			(new CTag('script', true))
				->setAttribute('type', 'text/x-jquery-tmpl')
				->addItem(new CRow([
					(new CCol((new CDiv)->addClass(ZBX_STYLE_DRAG_ICON)))->addClass(ZBX_STYLE_TD_DRAG_ICON),
					(new CTextBox('headers[name][#{index}]', '#{name}', $readonly))
						->setAttribute('placeholder', _('name'))
						->setWidth(ZBX_TEXTAREA_HTTP_PAIR_NAME_WIDTH),
					'&rArr;',
					(new CTextBox('headers[value][#{index}]', '#{value}', $readonly, 2000))
						->setAttribute('placeholder', _('value'))
						->setWidth(ZBX_TEXTAREA_HTTP_PAIR_VALUE_WIDTH),
					(new CButton(null, _('Remove')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->setEnabled(!$readonly)
						->setAttribute('data-row-action', 'remove_row')
				])),
			$headers
		]))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setId('headers_pairs')
			->setAttribute('data-sortable-pairs-table', $readonly ? '0': '1')
			->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_BIG_WIDTH . 'px;'),
		'headers_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Required status codes to form list.
	->addRow(
		new CLabel(_('Required status codes'), 'status_codes'),
		(new CTextBox('status_codes', $data['status_codes'], $readonly))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'status_codes_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Follow redirects to form list.
	->addRow(
		new CLabel(_('Follow redirects'), 'follow_redirects'),
		(new CCheckBox('follow_redirects', HTTPTEST_STEP_FOLLOW_REDIRECTS_ON))
			->setEnabled(!$readonly)
			->setChecked($data['follow_redirects'] == HTTPTEST_STEP_FOLLOW_REDIRECTS_ON),
		'follow_redirects_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Retrieve mode to form list.
	->addRow(
		new CLabel(_('Retrieve mode'), 'retrieve_mode'),
		(new CRadioButtonList('retrieve_mode', (int) $data['retrieve_mode']))
			->addValue(_('Body'), HTTPTEST_STEP_RETRIEVE_MODE_CONTENT)
			->addValue(_('Headers'), HTTPTEST_STEP_RETRIEVE_MODE_HEADERS)
			->addValue(_('Body and headers'), HTTPTEST_STEP_RETRIEVE_MODE_BOTH)
			->setEnabled(!($readonly || $data['request_method'] == HTTPCHECK_REQUEST_HEAD))
			->setModern(true),
		'retrieve_mode_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Convert to JSON to form list.
	->addRow(
		new CLabel(_('Convert to JSON'), 'output_format'),
		(new CCheckBox('output_format', HTTPCHECK_STORE_JSON))
			->setEnabled(!$readonly)
			->setChecked($data['output_format'] == HTTPCHECK_STORE_JSON),
		'output_format_row'
	)
	// Append ITEM_TYPE_HTTPAGENT HTTP proxy to form list.
	->addRow(
		new CLabel(_('HTTP proxy'), 'http_proxy'),
		(new CTextBox('http_proxy', $data['http_proxy'], $readonly, DB::getFieldLength('items', 'http_proxy')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('placeholder', '[protocol://][user[:password]@]proxy.example.com[:port]'),
		'http_proxy_row'
	)
	// Append ITEM_TYPE_HTTPAGENT HTTP authentication to form list.
	->addRow(
		new CLabel(_('HTTP authentication'), 'http_authtype'),
		[
			$readonly ? new CVar('http_authtype', $data['http_authtype']) : null,
			(new CComboBox($readonly ? '' : 'http_authtype', $data['http_authtype'], null, [
				HTTPTEST_AUTH_NONE => _('None'),
				HTTPTEST_AUTH_BASIC => _('Basic'),
				HTTPTEST_AUTH_NTLM => _('NTLM'),
				HTTPTEST_AUTH_KERBEROS => _('Kerberos')
			]))->setEnabled(!$readonly)
		],
		'http_authtype_row'
	)
	// Append ITEM_TYPE_HTTPAGENT User name to form list.
	->addRow(
		new CLabel(_('User name'), 'http_username'),
		(new CTextBox('http_username', $data['http_username'], $readonly, DB::getFieldLength('items', 'username')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'http_username_row'
	)
	// Append ITEM_TYPE_HTTPAGENT Password to form list.
	->addRow(
		new CLabel(_('Password'), 'http_password'),
		(new CTextBox('http_password', $data['http_password'], $readonly, DB::getFieldLength('items', 'password')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'http_password_row'
	)
	// Append ITEM_TYPE_HTTPAGENT SSL verify peer to form list.
	->addRow(
		new CLabel(_('SSL verify peer'), 'verify_peer'),
		(new CCheckBox('verify_peer', HTTPTEST_VERIFY_PEER_ON))
			->setEnabled(!$readonly)
			->setChecked($data['verify_peer'] == HTTPTEST_VERIFY_PEER_ON),
		'verify_peer_row'
	)
	// Append ITEM_TYPE_HTTPAGENT SSL verify host to form list.
	->addRow(
		new CLabel(_('SSL verify host'), 'verify_host'),
		(new CCheckBox('verify_host', HTTPTEST_VERIFY_HOST_ON))
			->setEnabled(!$readonly)
			->setChecked($data['verify_host'] == HTTPTEST_VERIFY_HOST_ON),
		'verify_host_row'
	)
	// Append ITEM_TYPE_HTTPAGENT SSL certificate file to form list.
	->addRow(
		new CLabel(_('SSL certificate file'), 'ssl_cert_file'),
		(new CTextBox('ssl_cert_file', $data['ssl_cert_file'], $readonly, DB::getFieldLength('items', 'ssl_cert_file')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'ssl_cert_file_row'
	)
	// Append ITEM_TYPE_HTTPAGENT SSL key file to form list.
	->addRow(
		new CLabel(_('SSL key file'), 'ssl_key_file'),
		(new CTextBox('ssl_key_file', $data['ssl_key_file'], $readonly, DB::getFieldLength('items', 'ssl_key_file')))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'ssl_key_file_row'
	)
	// Append ITEM_TYPE_HTTPAGENT SSL key password to form list.
	->addRow(
		new CLabel(_('SSL key password'), 'ssl_key_password'),
		(new CTextBox('ssl_key_password', $data['ssl_key_password'], $readonly,
			DB::getFieldLength('items', 'ssl_key_password')
		))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'ssl_key_password_row'
	)
	// Append master item select to form list.
	->addRow(
		(new CLabel(_('Master item'), 'master_itemid_ms'))->setAsteriskMark(),
		(new CMultiSelect([
			'name' => 'master_itemid',
			'object_name' => 'items',
			'multiple' => false,
			'disabled' => $readonly,
			'data' => ($data['master_itemid'] > 0)
				? [
					[
						'id' => $data['master_itemid'],
						'prefix' => $host['name'].NAME_DELIMITER,
						'name' => $data['master_itemname']
					]
				]
				: [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'items',
					'srcfld1' => 'itemid',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'master_itemid',
					'hostid' => $data['hostid'],
					'excludeids' => $data['itemid'] != 0 ? [$data['itemid']] : [],
					'webitems' => true,
					'normal_only' => true
				]
			]
		]))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		'row_master_item'
	);

// Append interface(s) to form list.
if ($data['interfaces']) {
	if ($discovered_item) {
		if ($data['interfaceid'] != 0) {
			$data['interfaces'] = zbx_toHash($data['interfaces'], 'interfaceid');
			$interface = $data['interfaces'][$data['interfaceid']];

			$form_list->addRow((new CLabel(_('Host interface'), 'interface'))->setAsteriskMark(),
				(new CTextBox('interface',
					$interface['useip']
						? $interface['ip'].' : '.$interface['port']
						: $interface['dns'].' : '.$interface['port'],
					true
				))->setAriaRequired(),
				'interface_row'
			);
		}
	}
	else {
		$interfacesComboBox = (new CComboBox('interfaceid', $data['interfaceid']))
			->setAriaRequired();

		// Set up interface groups sorted by priority.
		$interface_types = zbx_objectValues($this->data['interfaces'], 'type');
		$interface_groups = [];
		foreach ([INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI] as $interface_type) {
			if (in_array($interface_type, $interface_types)) {
				$interface_groups[$interface_type] = new COptGroup(interfaceType2str($interface_type));
			}
		}

		// add interfaces to groups
		foreach ($data['interfaces'] as $interface) {
			$option = new CComboItem($interface['interfaceid'],
				$interface['useip']
					? $interface['ip'].' : '.$interface['port']
					: $interface['dns'].' : '.$interface['port'],
				($interface['interfaceid'] == $data['interfaceid'])
			);
			$option->setAttribute('data-interfacetype', $interface['type']);
			$interface_groups[$interface['type']]->addItem($option);
		}
		foreach ($interface_groups as $interface_group) {
			$interfacesComboBox->addItem($interface_group);
		}

		$span = (new CSpan(_('No interface found')))
			->addClass(ZBX_STYLE_RED)
			->setId('interface_not_defined')
			->setAttribute('style', 'display: none;');

		$form_list->addRow((new CLabel(_('Host interface'), 'interfaceid'))->setAsteriskMark(),
			[$interfacesComboBox, $span], 'interface_row'
		);
		$form->addVar('selectedInterfaceId', $data['interfaceid']);
	}
}

$form_list
	// Append SNMP common fields fields.
	->addRow(
		(new CLabel(_('SNMP OID'), 'snmp_oid'))->setAsteriskMark(),
		(new CTextBox('snmp_oid', $data['snmp_oid'], $readonly, 512))
			->setAttribute('placeholder', '[IF-MIB::]ifInOctets.1')
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		'row_snmp_oid'
	)
	->addRow(_('Context name'),
		(new CTextBox('snmpv3_contextname', $data['snmpv3_contextname'], $discovered_item))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_snmpv3_contextname'
	)
	->addRow(
		(new CLabel(_('SNMP community'), 'snmp_community'))->setAsteriskMark(),
		(new CTextBox('snmp_community', $data['snmp_community'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		'row_snmp_community'
	)
	->addRow(_('Security name'),
		(new CTextBox('snmpv3_securityname', $data['snmpv3_securityname'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_snmpv3_securityname'
	);

// Append SNMPv3 security level to form list.
$security_levels = [
	ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV => 'noAuthNoPriv',
	ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV => 'authNoPriv',
	ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV => 'authPriv'
];
if ($discovered_item) {
	$form->addVar('snmpv3_securitylevel', $data['snmpv3_securitylevel']);
	$securityLevelComboBox = new CTextBox('snmpv3_securitylevel_name', $security_levels[$data['snmpv3_securitylevel']],
		true
	);
}
else {
	$securityLevelComboBox = new CComboBox('snmpv3_securitylevel', $data['snmpv3_securitylevel'], null,
		$security_levels
	);
}
$form_list->addRow(_('Security level'), $securityLevelComboBox, 'row_snmpv3_securitylevel');

// Append SNMPv3 authentication protocol to form list.
if ($discovered_item) {
	$form->addVar('snmpv3_authprotocol', (int) $data['snmpv3_authprotocol']);
	$snmpv3_authprotocol = (new CRadioButtonList('snmpv3_authprotocol_names', (int) $data['snmpv3_authprotocol']))
		->addValue(_('MD5'), ITEM_AUTHPROTOCOL_MD5)
		->addValue(_('SHA'), ITEM_AUTHPROTOCOL_SHA)
		->setModern(true)
		->setEnabled(!$discovered_item);
}
else {
	$snmpv3_authprotocol = (new CRadioButtonList('snmpv3_authprotocol', (int) $data['snmpv3_authprotocol']))
		->addValue(_('MD5'), ITEM_AUTHPROTOCOL_MD5)
		->addValue(_('SHA'), ITEM_AUTHPROTOCOL_SHA)
		->setModern(true);
}

$form_list
	->addRow((new CLabel(_('Authentication protocol'), 'snmpv3_authprotocol')),
		$snmpv3_authprotocol,
		'row_snmpv3_authprotocol'
	)
	// Append SNMPv3 authentication passphrase to form list.
	->addRow(_('Authentication passphrase'),
		(new CTextBox('snmpv3_authpassphrase', $data['snmpv3_authpassphrase'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_snmpv3_authpassphrase'
	);

// Append SNMPv3 privacy protocol to form list.
if ($discovered_item) {
	$form->addVar('snmpv3_privprotocol', (int) $data['snmpv3_privprotocol']);
	$snmpv3_privprotocol = (new CRadioButtonList('snmpv3_privprotocol_names', (int) $data['snmpv3_privprotocol']))
		->addValue(_('DES'), ITEM_PRIVPROTOCOL_DES)
		->addValue(_('AES'), ITEM_PRIVPROTOCOL_AES)
		->setModern(true)
		->setEnabled(!$discovered_item);
}
else {
	$snmpv3_privprotocol = (new CRadioButtonList('snmpv3_privprotocol', (int) $data['snmpv3_privprotocol']))
		->addValue(_('DES'), ITEM_PRIVPROTOCOL_DES)
		->addValue(_('AES'), ITEM_PRIVPROTOCOL_AES)
		->setModern(true);
}
$form_list
	->addRow((new CLabel(_('Privacy protocol'), 'snmpv3_privprotocol')),
		$snmpv3_privprotocol,
		'row_snmpv3_privprotocol'
	)
	// Append SNMPv3 privacy passphrase to form list.
	->addRow(_('Privacy passphrase'),
		(new CTextBox('snmpv3_privpassphrase', $data['snmpv3_privpassphrase'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_snmpv3_privpassphrase'
	)
	->addRow(_('Port'),
		(new CTextBox('port', $data['port'], $discovered_item, 64))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		'row_port'
	)
	->addRow(
		(new CLabel(_('IPMI sensor'), 'ipmi_sensor'))->setAsteriskMark(),
		(new CTextBox('ipmi_sensor', $data['ipmi_sensor'], $readonly, 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		'row_ipmi_sensor'
	);

// Append authentication method to form list.
$auth_types = [
	ITEM_AUTHTYPE_PASSWORD => _('Password'),
	ITEM_AUTHTYPE_PUBLICKEY => _('Public key')
];
if ($discovered_item) {
	$form->addVar('authtype', $data['authtype']);
	$authTypeComboBox = new CTextBox('authtype_name', $auth_types[$data['authtype']], true);
}
else {
	$authTypeComboBox = new CComboBox('authtype', $data['authtype'], null, $auth_types);
}

$form_list
	->addRow(_('Authentication method'), $authTypeComboBox, 'row_authtype')
	->addRow((new CLabel(_('JMX endpoint'), 'jmx_endpoint'))->setAsteriskMark(),
		(new CTextBox('jmx_endpoint', $data['jmx_endpoint'], $discovered_item, 255))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired(),
		'row_jmx_endpoint'
	)
	->addRow(_('User name'),
		(new CTextBox('username', $data['username'], $discovered_item, 64))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		'row_username'
	)
	->addRow(
		(new CLabel(_('Public key file'), 'publickey'))->setAsteriskMark(),
		(new CTextBox('publickey', $data['publickey'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAriaRequired(),
		'row_publickey'
	)
	->addRow(
		(new CLabel(_('Private key file'), 'privatekey'))->setAsteriskMark(),
		(new CTextBox('privatekey', $data['privatekey'], $discovered_item, 64))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAriaRequired(),
		'row_privatekey'
	)
	->addRow(_('Password'),
		(new CTextBox('password', $data['password'], $discovered_item, 64))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH),
		'row_password'
	)
	->addRow(
		(new CLabel(_('Executed script'), 'params_es'))->setAsteriskMark(),
		(new CTextArea('params_es', $data['params']))
			->addClass(ZBX_STYLE_MONOSPACE_FONT)
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setReadonly($discovered_item),
		'label_executed_script'
	)
	->addRow(
		(new CLabel(_('SQL query'), 'params_ap'))->setAsteriskMark(),
		(new CTextArea('params_ap', $data['params']))
			->addClass(ZBX_STYLE_MONOSPACE_FONT)
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setReadonly($discovered_item),
		'label_params'
	)
	->addRow(
		(new CLabel(_('Formula'), 'params_f'))->setAsteriskMark(),
		(new CTextArea('params_f', $data['params'], $discovered_item))
			->addClass(ZBX_STYLE_MONOSPACE_FONT)
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setReadonly($discovered_item),
		'label_formula'
	);

// Append value type to form list.
if ($readonly) {
	$form->addVar('value_type', $data['value_type']);
	$form_list->addRow(new CLabel(_('Type of information'), 'value_type_name'),
		(new CTextBox('value_type_name', itemValueTypeString($data['value_type']), true))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
	);
}
else {
	$form_list->addRow(new CLabel(_('Type of information'), 'value_type'),
		(new CComboBox('value_type', $data['value_type'], null, [
			ITEM_VALUE_TYPE_UINT64 => _('Numeric (unsigned)'),
			ITEM_VALUE_TYPE_FLOAT => _('Numeric (float)'),
			ITEM_VALUE_TYPE_STR => _('Character'),
			ITEM_VALUE_TYPE_LOG => _('Log'),
			ITEM_VALUE_TYPE_TEXT => _('Text')
		]))
	);
}

$form_list
	->addRow(_('Units'),
		(new CTextBox('units', $data['units'], $readonly))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_units'
	)
	->addRow((new CLabel(_('Update interval'), 'delay'))->setAsteriskMark(),
		(new CTextBox('delay', $data['delay'], $discovered_item))
			->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			->setAriaRequired(),
		'row_delay'
	);

// Append custom intervals to form list.
$delayFlexTable = (new CTable())
	->setId('delayFlexTable')
	->setHeader([_('Type'), _('Interval'), _('Period'), $discovered_item ? null : _('Action')])
	->setAttribute('style', 'width: 100%;');

foreach ($data['delay_flex'] as $i => $delay_flex) {
	if ($discovered_item) {
		$form->addVar('delay_flex['.$i.'][type]', (int) $delay_flex['type']);
		$type_input = (new CRadioButtonList('delay_flex['.$i.'][type_name]', (int) $delay_flex['type']))
			->addValue(_('Flexible'), ITEM_DELAY_FLEXIBLE)
			->addValue(_('Scheduling'), ITEM_DELAY_SCHEDULING)
			->setModern(true)
			->setEnabled(!$discovered_item);
	}
	else {
		$type_input = (new CRadioButtonList('delay_flex['.$i.'][type]', (int) $delay_flex['type']))
			->addValue(_('Flexible'), ITEM_DELAY_FLEXIBLE)
			->addValue(_('Scheduling'), ITEM_DELAY_SCHEDULING)
			->setModern(true);
	}

	if ($delay_flex['type'] == ITEM_DELAY_FLEXIBLE) {
		$delay_input = (new CTextBox('delay_flex['.$i.'][delay]', $delay_flex['delay'], $discovered_item))
			->setAttribute('placeholder', ZBX_ITEM_FLEXIBLE_DELAY_DEFAULT);
		$period_input = (new CTextBox('delay_flex['.$i.'][period]', $delay_flex['period'], $discovered_item))
			->setAttribute('placeholder', ZBX_DEFAULT_INTERVAL);
		$schedule_input = (new CTextBox('delay_flex['.$i.'][schedule]', '', $discovered_item))
			->setAttribute('placeholder', ZBX_ITEM_SCHEDULING_DEFAULT)
			->setAttribute('style', 'display: none;');
	}
	else {
		$delay_input = (new CTextBox('delay_flex['.$i.'][delay]', $discovered_item))
			->setAttribute('placeholder', ZBX_ITEM_FLEXIBLE_DELAY_DEFAULT)
			->setAttribute('style', 'display: none;');
		$period_input = (new CTextBox('delay_flex['.$i.'][period]', '', $discovered_item))
			->setAttribute('placeholder', ZBX_DEFAULT_INTERVAL)
			->setAttribute('style', 'display: none;');
		$schedule_input = (new CTextBox('delay_flex['.$i.'][schedule]', $delay_flex['schedule'], $discovered_item))
			->setAttribute('placeholder', ZBX_ITEM_SCHEDULING_DEFAULT);
	}

	$button = $discovered_item
		? null
		: (new CButton('delay_flex['.$i.'][remove]', _('Remove')))
			->addClass(ZBX_STYLE_BTN_LINK)
			->addClass('element-table-remove');

	$delayFlexTable->addRow([$type_input, [$delay_input, $schedule_input], $period_input, $button], 'form_row');
}

if (!$discovered_item) {
	$delayFlexTable->addRow([(new CButton('interval_add', _('Add')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->addClass('element-table-add')]);
}

$form_list->addRow(_('Custom intervals'),
	(new CDiv($delayFlexTable))
		->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
		->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;'),
	'row_flex_intervals'
);

// Append history storage to form list.
$keep_history_hint = null;
if ($data['config']['hk_history_global']
		&& ($host['status'] == HOST_STATUS_MONITORED || $host['status'] == HOST_STATUS_NOT_MONITORED)) {
	$link = (CWebUser::getType() == USER_TYPE_SUPER_ADMIN)
		? (new CLink(_x('global housekeeping settings', 'item_form'), 'adm.housekeeper.php'))
				->setAttribute('target', '_blank')
		: _x('global housekeeping settings', 'item_form');

	$keep_history_hint = (new CDiv(makeInformationIcon([
		' '._x('Overridden by', 'item_form').' ',
		$link,
		' ('.$data['config']['hk_history'].')'
	])))
		->addStyle('margin: 5px 0 0 5px;')
		->setId('history_mode_hint');
}

$form_list->addRow((new CLabel(_('History storage period'), 'history'))->setAsteriskMark(),
	(new CDiv([
		(new CRadioButtonList('history_mode', (int) $data['history_mode']))
			->addValue(_('Do not keep history'), ITEM_STORAGE_OFF)
			->addValue(_('Storage period'), ITEM_STORAGE_CUSTOM)
			->setReadonly($discovered_item)
			->setModern(true),
		(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CTextBox('history', $data['history'], $discovered_item))
			->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
			->setAriaRequired(),
		$keep_history_hint
	]))->addClass('wrap-multiple-controls')
);

// Append trend storage to form list.
$keep_trend_hint = null;
if ($data['config']['hk_trends_global']
		&& ($host['status'] == HOST_STATUS_MONITORED || $host['status'] == HOST_STATUS_NOT_MONITORED)) {
	$link = (CWebUser::getType() == USER_TYPE_SUPER_ADMIN)
		? (new CLink(_x('global housekeeping settings', 'item_form'), 'adm.housekeeper.php'))
				->setAttribute('target', '_blank')
		: _x('global housekeeping settings', 'item_form');

	$keep_trend_hint = (new CDiv(makeInformationIcon([
		' '._x('Overridden by', 'item_form').' ',
		$link,
		' ('.$data['config']['hk_trends'].')'
	])))
		->addStyle('margin: 5px 0 0 5px;')
		->setId('trends_mode_hint');
}

$form_list
	->addRow((new CLabel(_('Trend storage period'), 'trends'))->setAsteriskMark(),
		(new CDiv([
			(new CRadioButtonList('trends_mode', (int) $data['trends_mode']))
				->addValue(_('Do not keep trends'), ITEM_STORAGE_OFF)
				->addValue(_('Storage period'), ITEM_STORAGE_CUSTOM)
				->setReadonly($discovered_item)
				->setModern(true),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CTextBox('trends', $data['trends'], $discovered_item))
				->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
				->setAriaRequired(),
			$keep_trend_hint
		]))->addClass('wrap-multiple-controls'),
		'row_trends'
	)
	->addRow(_('Log time format'),
		(new CTextBox('logtimefmt', $data['logtimefmt'], $readonly, 64))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_logtimefmt'
	);

// Append valuemap to form list.
if ($readonly) {
	$form->addVar('valuemapid', $data['valuemapid']);
	$valuemapComboBox = (new CTextBox('valuemap_name',
		!empty($data['valuemaps']) ? $data['valuemaps'] : _('As is'),
		true
	))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);
}
else {
	$valuemapComboBox = new CComboBox('valuemapid', $data['valuemapid']);
	$valuemapComboBox->addItem(0, _('As is'));
	foreach ($data['valuemaps'] as $valuemap) {
		$valuemapComboBox->addItem($valuemap['valuemapid'], CHtml::encode($valuemap['name']));
	}
}

if (CWebUser::getType() == USER_TYPE_SUPER_ADMIN) {
	$valuemapComboBox = [$valuemapComboBox, '&nbsp;',
		(new CLink(_('show value mappings'), 'adm.valuemapping.php'))->setAttribute('target', '_blank')
	];
}

$form_list
	->addRow(_('Show value'), $valuemapComboBox, 'row_valuemap')
	->addRow(
		new CLabel(_('Enable trapping'), 'allow_traps'),
		[
			$discovered_item ? new CVar('allow_traps', $data['allow_traps']) : null,
			(new CCheckBox($discovered_item ? '' : 'allow_traps', HTTPCHECK_ALLOW_TRAPS_ON))
				->setEnabled(!$discovered_item)
				->setChecked($data['allow_traps'] == HTTPCHECK_ALLOW_TRAPS_ON)
		],
		'allow_traps_row'
	)
	->addRow(_('Allowed hosts'),
		(new CTextBox('trapper_hosts', $data['trapper_hosts'], $discovered_item))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'row_trapper_hosts'
	);

// Add "New application" and list of applications to form list.
if ($discovered_item) {
	$form->addVar('new_application', '');
	foreach ($data['db_applications'] as $db_application) {
		foreach ($data['applications'] as $application) {
			if ($db_application['applicationid'] == $application) {
				$form->addVar('applications[]', $db_application['applicationid']);
			}
		}
	}

	$applicationComboBox = new CListBox('applications_names[]', $data['applications'], 6);
	foreach ($data['db_applications'] as $application) {
		$applicationComboBox->addItem($application['applicationid'], CHtml::encode($application['name']));
	}
	$applicationComboBox->setEnabled(!$discovered_item);
}
else {
	$form_list->addRow(new CLabel(_('New application'), 'new_application'), (new CSpan(
		(new CTextBox('new_application', $data['new_application']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	))->addClass(ZBX_STYLE_FORM_NEW_GROUP));

	$applicationComboBox = new CListBox('applications[]', $data['applications'], 6);
	$applicationComboBox->addItem(0, '-'._('None').'-');
	foreach ($data['db_applications'] as $application) {
		$applicationComboBox->addItem($application['applicationid'], CHtml::encode($application['name']));
	}
}

$form_list->addRow(_('Applications'), $applicationComboBox);

// Append populate host to form list.
if ($discovered_item) {
	$form->addVar('inventory_link', 0);
}
else {
	$hostInventoryFieldComboBox = new CComboBox('inventory_link');
	$hostInventoryFieldComboBox->addItem(0, '-'._('None').'-', $data['inventory_link'] == '0' ? 'yes' : null);

	// A list of available host inventory fields.
	foreach ($data['possibleHostInventories'] as $fieldNo => $fieldInfo) {
		if (isset($data['alreadyPopulated'][$fieldNo])) {
			$enabled = isset($data['item']['inventory_link'])
				? $data['item']['inventory_link'] == $fieldNo
				: $data['inventory_link'] == $fieldNo && !hasRequest('clone');
		}
		else {
			$enabled = true;
		}
		$hostInventoryFieldComboBox->addItem(
			$fieldNo,
			$fieldInfo['title'],
			$data['inventory_link'] == $fieldNo && $enabled ? 'yes' : null,
			$enabled
		);
	}

	$form_list->addRow(_('Populates host inventory field'), $hostInventoryFieldComboBox, 'row_inventory_link');
}

// Append description to form list.
$form_list
	->addRow(_('Description'),
		(new CTextArea('description', $data['description']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setMaxlength(DB::getFieldLength('items', 'description'))
			->setReadonly($discovered_item)
	)
	// Append status to form list.
	->addRow(_('Enabled'),
		(new CCheckBox('status', ITEM_STATUS_ACTIVE))->setChecked($data['status'] == ITEM_STATUS_ACTIVE)
	);

// Append tabs to form.
$itemTab = (new CTabView())
	->addTab('itemTab', $data['caption'], $form_list)
	->addTab('preprocTab', _('Preprocessing'),
		(new CFormList('item_preproc_list'))
			->addRow(_('Preprocessing steps'),
				getItemPreprocessing($form, $data['preprocessing'], $readonly, $data['preprocessing_types'])
			)
	);

if (!hasRequest('form_refresh')) {
	$itemTab->setSelected(0);
}

// Append buttons to form.
if ($data['itemid'] != 0) {
	$buttons = [new CSubmit('clone', _('Clone'))];

	if ($data['host']['status'] != HOST_STATUS_TEMPLATE) {
		$buttons[] = (new CSubmit('check_now', _('Check now')))
			->setEnabled(in_array($data['item']['type'], checkNowAllowedTypes())
					&& $data['item']['status'] == ITEM_STATUS_ACTIVE
					&& $data['host']['status'] == HOST_STATUS_MONITORED
			);
	}

	if ($host['status'] == HOST_STATUS_MONITORED || $host['status'] == HOST_STATUS_NOT_MONITORED) {
		$buttons[] = new CButtonQMessage(
			'del_history',
			_('Clear history and trends'),
			_('History clearing can take a long time. Continue?')
		);
	}

	$buttons[] = (new CButtonDelete(_('Delete item?'), url_params(['form', 'itemid', 'hostid'])))
		->setEnabled(!$data['limited']);
	$buttons[] = new CButtonCancel(url_param('hostid'));

	$itemTab->setFooter(makeFormFooter(new CSubmit('update', _('Update')), $buttons));
}
else {
	$itemTab->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('hostid'))]
	));
}

$form->addItem($itemTab);
$widget->addItem($form);

require_once dirname(__FILE__).'/js/configuration.item.edit.js.php';

return $widget;
