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


require_once dirname(__FILE__).'/js/monitoring.screen.edit.js.php';

$widget = (new CWidget())->setTitle(_('Screens'));

$tabs = new CTabView();

if (!$data['form_refresh']) {
	$tabs->setSelected(0);
}

if (array_key_exists('templateid', $data['screen'])) {
	$widget->addItem(get_header_host_table('screens', $data['screen']['templateid']));
}

// create form
$form = (new CForm())
	->setName('screenForm')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', $data['form']);

if (array_key_exists('templateid', $data['screen'])) {
	$form->addVar('templateid', $data['screen']['templateid']);
}
else {
	$form->addVar('current_user_userid', $data['current_user_userid'])
		->addVar('current_user_fullname', getUserFullname($data['users'][$data['current_user_userid']]));
}

if ($data['screen']['screenid']) {
	$form->addVar('screenid', $data['screen']['screenid']);
}

$user_type = CWebUser::getType();

// Create screen form list.
$screen_tab = (new CFormList());

if (!array_key_exists('templateid', $data['screen'])) {
	// Screen owner multiselect.
	$multiselect_data = [
		'name' => 'userid',
		'object_name' => 'users',
		'multiple' => false,
		'disabled' => ($user_type != USER_TYPE_SUPER_ADMIN && $user_type != USER_TYPE_ZABBIX_ADMIN),
		'data' => [],
		'popup' => [
			'parameters' => [
				'srctbl' => 'users',
				'srcfld1' => 'userid',
				'srcfld2' => 'fullname',
				'dstfrm' => $form->getName(),
				'dstfld1' => 'userid'
			]
		]
	];

	$screen_ownerid = $data['screen']['userid'];

	if ($screen_ownerid !== '') {
		$multiselect_data['data'][] = array_key_exists($screen_ownerid, $data['users'])
			? [
				'id' => $screen_ownerid,
				'name' => getUserFullname($data['users'][$screen_ownerid])
			]
			: [
				'id' => $screen_ownerid,
				'name' => _('Inaccessible user'),
				'inaccessible' => true
			];
	}

	// Append multiselect to screen tab.
	$screen_tab->addRow((new CLabel(_('Owner'), 'userid_ms'))->setAsteriskMark(),
		(new CMultiSelect($multiselect_data))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
	);
}

$screen_tab->addRow(
		(new CLabel(_('Name'), 'name'))->setAsteriskMark(),
		(new CTextBox('name', $data['screen']['name']))
			->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
			->setAriaRequired()
			->setAttribute('autofocus', 'autofocus')
	)
	->addRow((new CLabel(_('Columns'), 'hsize'))->setAsteriskMark(),
		(new CNumericBox('hsize', $data['screen']['hsize'], 3))
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
			->setAriaRequired()
	)
	->addRow((new CLabel(_('Rows'), 'vsize'))->setAsteriskMark(),
		(new CNumericBox('vsize', $data['screen']['vsize'], 3))
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
			->setAriaRequired()
	);

// append tab to form
$tabs->addTab('screen_tab', _('Screen'), $screen_tab);
if (!array_key_exists('templateid', $data['screen'])) {
	// User group sharing table.
	$user_group_shares_table = (new CTable())
		->setHeader([_('User groups'), _('Permissions'), _('Action')])
		->setAttribute('style', 'width: 100%;');

	$add_user_group_btn = ([(new CButton(null, _('Add')))
		->onClick('return PopUp("popup.generic",'.
			CJs::encodeJson([
				'srctbl' => 'usrgrp',
				'srcfld1' => 'usrgrpid',
				'srcfld2' => 'name',
				'dstfrm' => $form->getName(),
				'multiselect' => '1'
			]).', null, this);'
		)
		->addClass(ZBX_STYLE_BTN_LINK)]);

	$user_group_shares_table->addRow(
		(new CRow(
			(new CCol($add_user_group_btn))->setColSpan(3)
		))->setId('user_group_list_footer')
	);

	$user_groups = [];

	foreach ($data['screen']['userGroups'] as $user_group) {
		$user_groupid = $user_group['usrgrpid'];
		$user_groups[$user_groupid] = [
			'usrgrpid' => $user_groupid,
			'name' => $data['user_groups'][$user_groupid]['name'],
			'permission' => $user_group['permission']
		];
	}

	$js_insert = 'addPopupValues('.zbx_jsvalue(['object' => 'usrgrpid', 'values' => $user_groups]).');';

	// User sharing table.
	$user_shares_table = (new CTable())
		->setHeader([_('Users'), _('Permissions'), _('Action')])
		->setAttribute('style', 'width: 100%;');

	$add_user_btn = ([(new CButton(null, _('Add')))
		->onClick('return PopUp("popup.generic",'.
			CJs::encodeJson([
				'srctbl' => 'users',
				'srcfld1' => 'userid',
				'srcfld2' => 'fullname',
				'dstfrm' => $form->getName(),
				'multiselect' => '1'
			]).', null, this);'
		)
		->addClass(ZBX_STYLE_BTN_LINK)]);

	$user_shares_table->addRow(
		(new CRow(
			(new CCol($add_user_btn))->setColSpan(3)
		))->setId('user_list_footer')
	);

	$users = [];

	foreach ($data['screen']['users'] as $user) {
		$userid = $user['userid'];
		$users[$userid] = [
			'id' => $userid,
			'name' => getUserFullname($data['users'][$userid]),
			'permission' => $user['permission']
		];
	}

	$js_insert .= 'addPopupValues('.zbx_jsvalue(['object' => 'userid', 'values' => $users]).');';

	zbx_add_post_js($js_insert);

	$sharing_tab = (new CFormList('sharing_form'))
		->addRow(_('Type'),
		(new CRadioButtonList('private', (int) $data['screen']['private']))
			->addValue(_('Private'), PRIVATE_SHARING)
			->addValue(_('Public'), PUBLIC_SHARING)
			->setModern(true)
		)
		->addRow(_('List of user group shares'),
			(new CDiv($user_group_shares_table))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;')
		)
		->addRow(_('List of user shares'),
			(new CDiv($user_shares_table))
				->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
				->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;')
		);

	// Append data to form.
	$tabs->addTab('sharing_tab', _('Sharing'), $sharing_tab);
}

if ($data['form'] === 'update') {
	$tabs->setFooter(makeFormFooter(
		new CSubmit('update', _('Update')),
		[
			(new CSimpleButton(_('Clone')))->setId('clone'),
			new CButton('full_clone', _('Full clone')),
			new CButtonDelete(_('Delete screen?'), url_params(['form', 'screenid', 'templateid'])),
			new CButtonCancel(url_param('templateid'))
		]
	));
}
else {
	$tabs->setFooter(makeFormFooter(
		new CSubmit('add', _('Add')),
		[new CButtonCancel(url_param('templateid'))]
	));
}

$form->addItem($tabs);

$widget->addItem($form);

return $widget;
