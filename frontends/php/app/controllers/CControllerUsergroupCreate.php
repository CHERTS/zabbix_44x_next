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


class CControllerUsergroupCreate extends CController {

	protected function checkInput() {
		$fields = [
			'name'            => 'required|not_empty|db usrgrp.name',
			'userids'         => 'array_db users.userid',
			'gui_access'      => 'db usrgrp.gui_access|in '.implode(',', [GROUP_GUI_ACCESS_SYSTEM, GROUP_GUI_ACCESS_INTERNAL, GROUP_GUI_ACCESS_LDAP, GROUP_GUI_ACCESS_DISABLED]),
			'users_status'    => 'db usrgrp.users_status|in '.GROUP_STATUS_ENABLED.','.GROUP_STATUS_DISABLED,
			'debug_mode'      => 'db usrgrp.debug_mode|in '.GROUP_DEBUG_MODE_ENABLED.','.GROUP_DEBUG_MODE_DISABLED,

			'group_rights'    => 'array',
			'tag_filters'     => 'array',

			'new_group_right' => 'array',
			'new_tag_filter'  => 'array',

			'form_refresh'    => 'int32'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->getValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
						->setArgument('action', 'usergroup.edit')
						->getUrl()
					);
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot add user group'));
					$this->setResponse($response);
					break;

				case self::VALIDATION_FATAL_ERROR:
					$this->setResponse(new CControllerResponseFatal());
					break;
			}
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$user_group = [
			'rights' => []
		];

		$this->getInputs($user_group, ['name', 'users_status', 'gui_access', 'debug_mode', 'userids', 'tag_filters']);

		$group_rights = applyHostGroupRights($this->getInput('group_rights', []));

		foreach ($group_rights as $groupid => $group_right) {
			if ($groupid != 0 && $group_right['permission'] != PERM_NONE) {
				$user_group['rights'][] = [
					'id' => (string) $groupid,
					'permission' => $group_right['permission']
				];
			}
		}

		$result = (bool) API::UserGroup()->create($user_group);

		if ($result) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'usergroup.list')
				->setArgument('page', CPagerHelper::loadPage('usergroup.list', null))
			);
			$response->setFormData(['uncheck' => '1']);
			$response->setMessageOk(_('User group added'));
		}
		else {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'usergroup.edit')
			);
			$response->setMessageError(_('Cannot add user group'));
			$response->setFormData($this->getInputAll());
		}

		$this->setResponse($response);
	}
}
