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


class CControllerIconMapUpdate extends CController {

	protected function checkInput() {
		$fields = [
			'iconmapid' => 'fatal | required | db icon_map.iconmapid',
			'iconmap'   => 'required | array'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		if ($this->getUserType() == USER_TYPE_SUPER_ADMIN) {
			return (bool) API::IconMap()->get([
				'output' => [],
				'iconmapids' => (array) $this->getInput('iconmapid')
			]);
		}

		return false;
	}

	protected function doAction() {
		$iconmap = (array) $this->getInput('iconmap') + ['mappings' => []];
		$iconmap['iconmapid'] = $this->getInput('iconmapid');

		foreach ($iconmap['mappings'] as &$mapping) {
			$mapping['expression'] = trim($mapping['expression']);
		}
		unset($mapping);

		$result = (bool) API::IconMap()->update($iconmap);

		if ($result) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'iconmap.list')
			);
			$response->setMessageOk(_('Icon map updated'));
		}
		else {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'iconmap.edit')
				->setArgument('iconmapid', $iconmap['iconmapid'])
			);
			$form_data = $this->getInputAll();
			if (!array_key_exists('mappings', $form_data['iconmap'])) {
				$form_data['iconmap']['mappings'] = [];
			}
			$response->setFormData($form_data);
			$response->setMessageError(_('Cannot update icon map'));
		}

		$this->setResponse($response);
	}
}
