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


require_once dirname(__FILE__).'/../../blocks.inc.php';

class CScreenSystemStatus extends CScreenBase {

	/**
	 * Process screen.
	 *
	 * @return CDiv (screen inside container)
	 */
	public function get() {
		global $page;

		// rewrite page file
		$page['file'] = $this->pageFile;

		$config = select_config();
		$severity_config = [
			'severity_name_0' => $config['severity_name_0'],
			'severity_name_1' => $config['severity_name_1'],
			'severity_name_2' => $config['severity_name_2'],
			'severity_name_3' => $config['severity_name_3'],
			'severity_name_4' => $config['severity_name_4'],
			'severity_name_5' => $config['severity_name_5']
		];
		$data = getSystemStatusData([]);
		$table = makeSystemStatus([], $data, $severity_config, $this->pageFile.'?screenid='.$this->screenid);

		$footer = (new CList())
			->addItem(_s('Updated: %s', zbx_date2str(TIME_FORMAT_SECONDS)))
			->addClass(ZBX_STYLE_DASHBRD_WIDGET_FOOT);

		return $this->getOutput(
			(new CUiWidget('hat_syssum', [$table, $footer]))->setHeader(_('Problems by severity'))
		);
	}
}
