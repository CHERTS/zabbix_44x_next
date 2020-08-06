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


class CTable extends CTag {

	protected $header;
	protected $footer;
	protected $colnum;
	protected $rownum;
	protected $heading_column;

	public function __construct() {
		parent::__construct('table', true);
		$this->rownum = 0;
		$this->header = '';
		$this->footer = '';
		$this->colnum = 1;
		$this->heading_column = null;
	}

	public function setCellPadding($value) {
		$this->attributes['cellpadding'] = strval($value);
		return $this;
	}

	public function setCellSpacing($value) {
		$this->attributes['cellspacing'] = strval($value);
		return $this;
	}

	public function prepareRow($item, $class = null, $id = null) {
		if ($item === null) {
			return null;
		}

		if ($item instanceof CCol) {
			if (isset($this->header) && !isset($item->attributes['colspan'])) {
				$item->attributes['colspan'] = $this->colnum;
			}
		}

		if (!($item instanceof CRow)) {
			$item = new CRow($item, $this->heading_column);
			if ($id !== null) {
				$item->setId($id);
			}
		}

		if ($class !== null) {
			$item->addClass($class);
		}

		return $item;
	}

	public function setHeader($value = null) {
		if (!($value instanceof CRow)) {
			$value = new CRowHeader($value);
		}
		$this->colnum = $value->itemsCount();

		$value = new CTag('thead', true, $value);
		$this->header = $value->toString();
		return $this;
	}

	/**
	 * Format given column as row header.
	 *
	 * @param int|null $heading_column  Column index for heading column. Starts from 0. 'null' if no heading column.
	 *
	 * @return CTable
	 */
	public function setHeadingColumn($heading_column) {
		$this->heading_column = $heading_column;

		return $this;
	}

	public function setFooter($value = null, $class = null) {
		$this->footer = $this->prepareRow($value, $class);
		$this->footer = $this->footer->toString();
		return $this;
	}

	public function addRow($item, $class = null, $id = null) {
		$this->addItem($this->prepareRow($item, $class, $id));
		++$this->rownum;
		return $this;
	}

	public function getNumCols() {
		return $this->colnum;
	}

	public function getNumRows() {
		return $this->rownum;
	}

	protected function startToString() {
		$ret = parent::startToString();
		$ret .= $this->header;
		$ret .= '<tbody>';
		return $ret;
	}

	protected function endToString() {
		$ret = $this->footer;
		$ret .= '</tbody>';
		$ret .= parent::endToString();
		return $ret;
	}
}
