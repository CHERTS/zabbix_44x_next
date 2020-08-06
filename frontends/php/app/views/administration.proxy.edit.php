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


$this->includeJSfile('app/views/administration.proxy.edit.js.php');

$widget = (new CWidget())->setTitle(_('Proxies'));

$tabs = new CTabView();

if ($data['form_refresh'] == 0) {
	$tabs->setSelected(0);
}

$proxyForm = (new CForm())
	->setId('proxyForm')
	->addVar('proxyid', $data['proxyid'])
	->addVar('tls_accept', $data['tls_accept'])
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE);

if ($data['status'] == HOST_STATUS_PROXY_PASSIVE && array_key_exists('interfaceid', $data)) {
	$proxyForm->addVar('interfaceid', $data['interfaceid']);
}

$interfaceTable = (new CTable())
	->setHeader([_('IP address'), _('DNS name'), _('Connect to'), _('Port')])
	->addRow([
		(new CTextBox('ip', $data['ip'], false, 64))->setWidth(ZBX_TEXTAREA_INTERFACE_IP_WIDTH),
		(new CTextBox('dns', $data['dns'], false, DB::getFieldLength('interface', 'dns')))
			->setWidth(ZBX_TEXTAREA_INTERFACE_DNS_WIDTH),
		(new CRadioButtonList('useip', (int) $data['useip']))
			->addValue(_('IP'), INTERFACE_USE_IP)
			->addValue(_('DNS'), INTERFACE_USE_DNS)
			->setModern(true),
		(new CTextBox('port', $data['port'], false, 64))
			->setWidth(ZBX_TEXTAREA_INTERFACE_PORT_WIDTH)
			->setAriaRequired()
	]);

$proxy_form_list = (new CFormList('proxyFormList'))
	->addRow((new CLabel(_('Proxy name'), 'host'))->setAsteriskMark(),
		(new CTextBox('host', $data['host'], false, 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAttribute('autofocus', 'autofocus')
			->setAriaRequired()
	)
	->addRow((new CLabel(_('Proxy mode'), 'status')),
		(new CRadioButtonList('status', (int) $data['status']))
			->addValue(_('Active'), HOST_STATUS_PROXY_ACTIVE)
			->addValue(_('Passive'), HOST_STATUS_PROXY_PASSIVE)
			->setModern(true)
	)
	->addRow((new CLabel(_('Interface'), 'proxy_interface'))->setAsteriskMark(),
		(new CDiv($interfaceTable))
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->setId('proxy_interface')
	)
	->addRow(_('Proxy address'),
		(new CTextBox('proxy_address', $data['proxy_address'], false, 255))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(_('Description'),
		(new CTextArea('description', $data['description']))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);

// append tabs to form
$proxyTab = (new CTabView())->addTab('proxyTab', _('Proxy'), $proxy_form_list);

// Encryption form list.
$encryption_form_list = (new CFormList('encryption'))
	->addRow(_('Connections to proxy'),
		(new CRadioButtonList('tls_connect', (int) $data['tls_connect']))
			->addValue(_('No encryption'), HOST_ENCRYPTION_NONE)
			->addValue(_('PSK'), HOST_ENCRYPTION_PSK)
			->addValue(_('Certificate'), HOST_ENCRYPTION_CERTIFICATE)
			->setModern(true)
	)
	->addRow(_('Connections from proxy'),
		(new CList())
			->addClass(ZBX_STYLE_LIST_CHECK_RADIO)
			->addItem((new CCheckBox('tls_in_none'))->setLabel(_('No encryption')))
			->addItem((new CCheckBox('tls_in_psk'))->setLabel(_('PSK')))
			->addItem((new CCheckBox('tls_in_cert'))->setLabel(_('Certificate')))
	)
	->addRow((new CLabel(_('PSK identity'), 'tls_psk_identity'))->setAsteriskMark(),
		(new CTextBox('tls_psk_identity', $data['tls_psk_identity'], false, 128))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow((new CLabel(_('PSK'), 'tls_psk'))->setAsteriskMark(),
		(new CTextBox('tls_psk', $data['tls_psk'], false, 512))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow(_('Issuer'),
		(new CTextBox('tls_issuer', $data['tls_issuer'], false, 1024))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	)
	->addRow(_x('Subject', 'encryption certificate'),
		(new CTextBox('tls_subject', $data['tls_subject'], false, 1024))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	);

$tabs->addTab('proxyTab', _('Proxy'), $proxy_form_list);
$tabs->addTab('encryptionTab', _('Encryption'), $encryption_form_list);

// append buttons to form
$cancelButton = new CRedirectButton(_('Cancel'), 'zabbix.php?action=proxy.list');

if ($data['proxyid'] == 0) {
	$tabs->setFooter(makeFormFooter(
		new CSubmitButton(_('Add'), 'action', 'proxy.create'),
		[$cancelButton]
	));
}
else {
	$tabs->setFooter(makeFormFooter(
		new CSubmitButton(_('Update'), 'action', 'proxy.update'),
		[
			(new CSimpleButton(_('Clone')))->setId('clone'),
			new CRedirectButton(_('Delete'),
				'zabbix.php?action=proxy.delete&sid='.$data['sid'].'&proxyids[]='.$data['proxyid'],
				_('Delete proxy?')
			),
			$cancelButton
		]
	));
}

$proxyForm->addItem($tabs);
$widget->addItem($proxyForm)->show();
