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


class CWidgetFieldCheckBoxList extends CWidgetField {

	const ORIENTATION_HORIZONTAL = 0;
	const ORIENTATION_VERTICAL = 1;

	private $orientation;

	public function __construct($name, $label) {
		parent::__construct($name, $label);

		$this->setSaveType(ZBX_WIDGET_FIELD_TYPE_INT32);
		$this->setDefault([]);
		$this->setValidationRules(['type' => API_INTS32]);
		$this->orientation = self::ORIENTATION_VERTICAL;
	}

	public function setValue($value) {
		$this->value = (array) $value;

		return $this;
	}

	public function setOrientation($orientation) {
		$this->orientation = $orientation;

		return $this;
	}

	public function getOrientation() {
		return $this->orientation;
	}
}
