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


class CControllerUserDelete extends CController {

	protected function checkInput() {
		$fields = [
			'userids' =>	'required|array_db users.userid'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$userids = $this->getInput('userids');

		$result = (bool) API::User()->delete($userids);

		$deleted = count($userids);

		$url = (new CUrl('zabbix.php'))
			->setArgument('action', 'user.list')
			->setArgument('uncheck', '1');

		$response = new CControllerResponseRedirect($url->getUrl());

		if ($result) {
			$response->setMessageOk(_n('User deleted', 'Users deleted', $deleted));
		}
		else {
			$response->setMessageError(_n('Cannot delete user', 'Cannot delete users', $deleted));
		}

		$this->setResponse($response);
	}
}
