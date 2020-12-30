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


$widget = (new CWidget())
	->setTitle(_('Working time'))
	->setControls((new CTag('nav', true,
		(new CForm())
			->cleanItems()
			->addItem((new CList())
				->addItem(makeAdministrationGeneralMenu((new CUrl('zabbix.php'))
					->setArgument('action', 'workingtime.edit')
					->getUrl()
				))
			)
		))
			->setAttribute('aria-label', _('Content controls'))
	);

$table = (new CTabView())
	->addTab('workingTime', _('Working time'),
		(new CFormList())
			->addRow((new CLabel(_('Working time'), 'work_period'))->setAsteriskMark(),
				(new CTextBox('work_period', $data['work_period']))
					->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
					->setAriaRequired()
					->setAttribute('autofocus', 'autofocus')
			)
	)
	->setFooter(makeFormFooter(new CSubmit('update', _('Update'))));

$form = (new CForm())
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->setAction((new CUrl('zabbix.php'))->setArgument('action', 'workingtime.update')->getUrl())
	->addItem($table);

$widget->addItem($form)->show();
