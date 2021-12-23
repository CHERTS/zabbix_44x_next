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


require_once dirname(__FILE__).'/include/classes/core/APP.php';

$page['file'] = 'setup.php';

try {
	APP::getInstance()->run(APP::EXEC_MODE_SETUP);
}
catch (Exception $e) {
	(new CView('general.warning', [
		'header' => $e->getMessage(),
		'messages' => [],
		'theme' => ZBX_DEFAULT_THEME
	]))->render();

	exit;
}

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'type' =>				[T_ZBX_STR, O_OPT, null,	IN('"'.ZBX_DB_MYSQL.'","'.ZBX_DB_POSTGRESQL.'","'.ZBX_DB_ORACLE.'"'), null],
	'server' =>				[T_ZBX_STR, O_OPT, null,	null,				null],
	'port' =>				[T_ZBX_INT, O_OPT, null,	BETWEEN(0, 65535),	null, _('Database port')],
	'database' =>			[T_ZBX_STR, O_OPT, null,	NOT_EMPTY,			'(isset({type}) && {type} !== "'.ZBX_DB_ORACLE.'")', _('Database name')],
	'user' =>				[T_ZBX_STR, O_OPT, null,	null,				null],
	'password' =>			[T_ZBX_STR, O_OPT, null,	null, 				null],
	'schema' =>				[T_ZBX_STR, O_OPT, null,	null, 				null],
	'zbx_server' =>			[T_ZBX_STR, O_OPT, null,	null,				null],
	'zbx_server_name' =>	[T_ZBX_STR, O_OPT, null,	null,				null],
	'zbx_server_port' =>	[T_ZBX_INT, O_OPT, null,	BETWEEN(0, 65535),	null, _('Port')],
	// actions
	'save_config' =>		[T_ZBX_STR, O_OPT, P_SYS,	null,				null],
	'retry' =>				[T_ZBX_STR, O_OPT, P_SYS,	null,				null],
	'cancel' =>				[T_ZBX_STR, O_OPT, P_SYS,	null,				null],
	'finish' =>				[T_ZBX_STR, O_OPT, P_SYS,	null,				null],
	'next' =>				[T_ZBX_STR, O_OPT, P_SYS,	null,				null],
	'back' =>				[T_ZBX_STR, O_OPT, P_SYS,	null,				null]
];

CSession::start();

$check_fields_result = check_fields($fields, false);

if (hasRequest('cancel') || hasRequest('finish')) {
	CSession::clear();
	redirect('index.php');
}

if (CWebUser::$data && CWebUser::getType() < USER_TYPE_SUPER_ADMIN && CSession::getValue('step') != 5) {
	access_deny(ACCESS_DENY_PAGE);
}

CSession::setValue('check_fields_result', $check_fields_result);

$theme = CWebUser::$data ? getUserTheme(CWebUser::$data) : ZBX_DEFAULT_THEME;

DBclose();

/*
 * Setup wizard
 */
$ZBX_SETUP_WIZARD = new CSetupWizard();

// page title
(new CPageHeader(_('Installation')))
	->addCssFile('assets/styles/'.CHtml::encode($theme).'.css')
	->addJsFile((new CUrl('js/browsers.js'))->getUrl())
	->addJsFile((new CUrl('jsLoader.php'))
		->setArgument('ver', ZABBIX_VERSION)
		->setArgument('lang', CWebUser::$data['lang'])
		->getUrl()
	)
	->display();

/*
 * Displaying
 */
$link = (new CLink('GPL v2', 'https://www.zabbix.com/license'))
	->setTarget('_blank')
	->addClass(ZBX_STYLE_GREY)
	->addClass(ZBX_STYLE_LINK_ALT);
$sub_footer = (new CDiv(['Licensed under ', $link]))->addClass(ZBX_STYLE_SIGNIN_LINKS);

(new CTag('body', true, [(new CTag('main', true, [$ZBX_SETUP_WIZARD, $sub_footer])), makePageFooter()]))
	->setAttribute('lang', 'en_gb')
	->show();
?>
</html>
