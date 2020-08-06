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


$widget = (new CWidget())->setTitle($data['title']);

// append host summary to widget header
if ($data['hostid'] != 0) {
	switch ($data['elements_field']) {
		case 'group_itemid':
			$host_table_element = 'items';
			break;
		case 'g_triggerid':
			$host_table_element = 'triggers';
			break;
		case 'group_graphid':
			$host_table_element = 'graphs';
			break;
		default:
			$host_table_element = '';
	}

	$widget->addItem(get_header_host_table($host_table_element, $data['hostid']));
}

// create form
$form = (new CForm())
	->setName('elements_form')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('action', $data['action'])
	->addVar($data['elements_field'], $data['elements'])
	->addVar('hostid', $data['hostid']);

// create form list
$form_list = new CFormList('elements_form_list');

// append copy types to form list
$form_list->addRow(new CLabel(_('Target type'), 'copy_type'),
	(new CRadioButtonList('copy_type', (int) $data['copy_type']))
		->addValue(_('Host groups'), COPY_TYPE_TO_HOST_GROUP)
		->addValue(_('Hosts'), COPY_TYPE_TO_HOST)
		->addValue(_('Templates'), COPY_TYPE_TO_TEMPLATE)
		->setModern(true)
);

// append multiselect wrapper to form list
$form_list->addRow((new CLabel(_('Target'), 'copy_targetids_ms'))->setAsteriskMark(),
	(new CDiv())->setId('copy_targets')
);

// append tabs to form
$tab_view = (new CTabView())->addTab('elements_tab', '', $form_list);

// append buttons to form
$tab_view->setFooter(makeFormFooter(
	new CSubmit('copy', _('Copy')),
	[new CButtonCancel(url_param('groupid').url_param('hostid'))]
));

$form->addItem($tab_view);
$widget->addItem($form);

require_once dirname(__FILE__).'/js/configuration.copy.elements.js.php';

return $widget;
