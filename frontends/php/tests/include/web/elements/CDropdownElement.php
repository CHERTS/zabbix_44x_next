<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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

require_once 'vendor/autoload.php';

require_once dirname(__FILE__).'/../CElement.php';

/**
 * Dropdown (select) element.
 */
class CDropdownElement extends CElement {

	/**
	 * Get collection of options.
	 *
	 * @return CElementCollection
	 */
	public function getOptions() {
		return $this->query('tag:option')->all();
	}

	/**
	 * Get text of selected option.
	 *
	 * @return string
	 */
	public function getText() {
		foreach ($this->getOptions() as $option) {
			if ($option->isSelected()) {
				return $option->getText();
			}
		}

		return null;
	}

	/**
	 * Select option by text.
	 *
	 * @param string $text    option text to be selected
	 *
	 * @return $this
	 */
	public function select($text) {
		$option = $this->query('xpath:.//option[text()='.CXPathHelper::escapeQuotes($text).']')->one();
		if (!$option->isSelected()) {
			if ($option->isClickable()) {
				$option->click();
			}
			else {
				throw new Exception('Cannot select disabled dropdown element.');
			}
		}

		return $this;
	}

	/**
	 * Alias for select.
	 * @see self::select
	 *
	 * @param string $text    option text to be selected
	 *
	 * @return $this
	 */
	public function fill($text) {
		return $this->select($text);
	}

	/**
	 * Alias for getText.
	 * @see self::getText
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->getText();
	}
}
