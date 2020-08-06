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


/**
 * Class to create a date textbox and calendar button.
 */
class CDateSelector extends CTag {
	/**
	 * Default CSS class name for HTML root element.
	 */
	const ZBX_STYLE_CLASS = 'calendar-control';

	/**
	 * Default date format.
	 *
	 * @var string
	 */
	private $date_format = ZBX_FULL_DATE_TIME;

	/**
	 * Set aria-required to textbox.
	 *
	 * @var bool
	 */
	private $is_required = false;

	/**
	 * Placeholder for date textbox field.
	 *
	 * @var string
	 */
	private $placeholder = null;

	/**
	 * Date and time set from view. Absolute (Y-m-d H:i:s) or relative time (now+1d, now/M ...).
	 *
	 * @var string
	 */
	private $value = null;

	/**
	 * Enabled or disabled state of HTML element.
	 *
	 * @var bool
	 */
	private $enabled = true;

	/**
	 * Create array with all inputs required for date selection and calendar.
	 *
	 * @param string $name   Textbox field name and calendar name prefix.
	 * @param string $value  Date and time set from view. Absolute (Y-m-d H:i:s) or relative time (now+1d, now/M ...).
	 *
	 * @return CDateSelector
	 */
	public function __construct($name = 'calendar', $value = null) {
		parent::__construct('div', true);

		$this->name = $name;
		$this->value = $value;
		$this->addClass(static::ZBX_STYLE_CLASS);
	}

	/**
	 * Set or reset element 'aria-required' attribute to textbox (not the container).
	 *
	 * @param bool $is_required  True to set field as required or false if field is not required.
	 *
	 * @return CDateSelector
	 */
	public function setAriaRequired($is_required = false) {
		$this->is_required = $is_required;

		return $this;
	}

	/**
	 * Set date format which calendar will return upon selection.
	 *
	 * @param string $format  Date and time format. Usually Y-m-d H:i:s or Y-m-d H:i
	 *
	 * @return CDateSelector
	 */
	public function setDateFormat($format) {
		$this->date_format = $format;

		return $this;
	}

	/**
	 * Add placeholder to date textbox field.
	 *
	 * @param type $text  Placeholder text for date textbox field.
	 *
	 * @return CDateSelector
	 */
	public function setPlaceholder($text) {
		$this->placeholder = $text;

		return $this;
	}

	/**
	 * Set enabled or disabled  state to field.
	 *
	 * @param bool $enabled  Field state.
	 *
	 * @return CDateSelector
	 */
	public function setEnabled($enabled) {
		$this->enabled = $enabled;

		return $this;
	}

	/**
	 * Gets string representation of date textbox and calendar button.
	 *
	 * @param bool $destroy
	 *
	 * @return string
	 */
	public function toString($destroy = true) {
		$this
			->addItem(
				(new CTextBox($this->name, $this->value))
					->setId($this->name)
					->setAttribute('placeholder', $this->placeholder)
					->setAriaRequired($this->is_required)
					->setEnabled($this->enabled)
			)
			->addItem((new CButton($this->name.'_calendar'))
				->addClass(ZBX_STYLE_ICON_CAL)
				->setEnabled($this->enabled)
				->onClick('toggleCalendar(this, "'.$this->name.'", "'.$this->date_format.'");'));

		return parent::toString($destroy);
	}
}
