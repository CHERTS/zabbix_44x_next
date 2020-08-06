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


class CControllerWidgetDataOverView extends CControllerWidget {

	public function __construct() {
		parent::__construct();

		$this->setType(WIDGET_DATA_OVER);
		$this->setValidationRules([
			'name' => 'string',
			'fields' => 'json',
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->getDefaultHeader()),
			'groupids' => getSubGroups($fields['groupids']),
			'application' => $fields['application'],
			'show_suppressed' => $fields['show_suppressed'],
			'style' => $fields['style'],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
