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

require_once dirname(__FILE__).'/../../include/blocks.inc.php';

class CControllerWidgetMapView extends CControllerWidget {

	public function __construct() {
		parent::__construct();

		$this->setType(WIDGET_MAP);
		$this->setValidationRules([
			'name' => 'string',
			'uniqueid' => 'required|string',
			'initial_load' => 'in 0,1',
			'fields' => 'json',
			'storage' => 'array'
		]);
	}

	protected function doAction() {
		$fields = $this->getForm()->getFieldsData();
		$storage = $this->getInput('storage', []);
		$uniqueid = $this->getInput('uniqueid');
		$sysmap_data = null;
		$previous_map = null;
		$sysmapid = null;
		$error = null;

		// Get previous map.
		if (array_key_exists('previous_maps', $storage)) {
			$previous_map = array_filter(explode(',', $storage['previous_maps']), 'is_numeric');

			if ($previous_map) {
				$previous_map = API::Map()->get([
					'sysmapids' => [array_pop($previous_map)],
					'output' => ['sysmapid', 'name']
				]);

				$previous_map = reset($previous_map);
			}
		}

		$sysmapid = array_key_exists('current_sysmapid', $storage)
			? $storage['current_sysmapid']
			: (array_key_exists('sysmapid', $fields) ? $fields['sysmapid'] : null);

		$sysmap_data = CMapHelper::get(($sysmapid === null) ? [] : [$sysmapid]);

		if ($sysmapid === null || $sysmap_data['id'] < 0) {
			$error = _('No permissions to referred object or it does not exist!');
		}

		// Rewrite actions to force Submaps be opened in same widget, instead of separate window.
		foreach ($sysmap_data['elements'] as &$element) {
			$actions = CJs::decodeJson($element['actions']);
			$actions['data']['widget_uniqueid'] = $uniqueid;
			$element['actions'] = CJs::encodeJson($actions);
		}
		unset($element);

		// Pass variables to view.
		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->getDefaultHeader()),
			'sysmap_data' => $sysmap_data ?: [],
			'widget_settings' => [
				'current_sysmapid' => $sysmapid,
				'filter_widget_reference' => array_key_exists('filter_widget_reference', $fields)
					? $fields['filter_widget_reference']
					: null,
				'source_type' => $fields['source_type'],
				'previous_map' => $previous_map,
				'initial_load' => $this->getInput('initial_load', 1),
				'uniqueid' => $uniqueid,
				'error' => $error
			],
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
