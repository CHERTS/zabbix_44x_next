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


if ($data['uncheck']) {
	uncheckTableRows();
}

$page_url = (new CUrl('zabbix.php'))->setArgument('action', 'regex.list');
$widget = (new CWidget())
	->setTitle(_('Regular expressions'))
	->setControls((new CTag('nav', true,
		(new CForm())
			->cleanItems()
			->addItem((new CList())
				->addItem(makeAdministrationGeneralMenu((new CUrl('zabbix.php'))
					->setArgument('action', 'regex.list')
					->getUrl()
				))
			)
			->setAction($page_url->getUrl())
			->addItem((new CList())
				->addItem(new CRedirectButton(_('New regular expression'), (new CUrl('zabbix.php'))
					->setArgument('action', 'regex.edit')
				))
			)
		))
			->setAttribute('aria-label', _('Content controls'))
	);

$form = (new CForm())->setName('regularExpressionsForm');

$table = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all-regexes'))->onClick("checkAll('".$form->getName()."', 'all-regexes', 'regexids');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		_('Name'),
		_('Expressions')
	]);

$expressions = [];
$values = [];

foreach($data['db_exps'] as $exp) {
	if (!isset($expressions[$exp['regexid']])) {
		$values[$exp['regexid']] = 1;
	}
	else {
		$values[$exp['regexid']] ++;
	}

	if (!isset($expressions[$exp['regexid']])) {
		$expressions[$exp['regexid']] = new CTable();
	}

	$expressions[$exp['regexid']]->addRow([
		new CCol($values[$exp['regexid']]),
		new CCol(' &raquo; '),
		new CCol($exp['expression']),
		new CCol(' ['.expression_type2str($exp['expression_type']).']')
	]);
}

foreach($data['regexes'] as $regexid => $regex) {
	$table->addRow([
		new CCheckBox('regexids['.$regexid.']', $regexid),
		new CLink($regex['name'], (new CUrl('zabbix.php'))
			->setArgument('action', 'regex.edit')
			->setArgument('regexid', $regexid)
		),
		array_key_exists($regexid, $expressions) ? $expressions[$regexid] : ''
	]);
}

$form->addItem([
	$table,
	new CActionButtonList('action', 'regexids', [
		'regex.delete' => ['name' => _('Delete'), 'confirm' => _('Delete selected regular expressions?')]
	])
]);

$widget->addItem($form)->show();
