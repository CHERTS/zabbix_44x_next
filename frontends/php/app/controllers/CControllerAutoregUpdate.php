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


class CControllerAutoregUpdate extends CController {

	protected function checkInput() {
		$fields = [
			'tls_accept' =>				'in '.HOST_ENCRYPTION_NONE.','.HOST_ENCRYPTION_PSK.','.(HOST_ENCRYPTION_NONE | HOST_ENCRYPTION_PSK),
			'tls_psk_identity' =>		'db config_autoreg_tls.tls_psk_identity',
			'tls_psk' =>				'db config_autoreg_tls.tls_psk'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->GetValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
						->setArgument('action', 'autoreg.edit')
						->getUrl()
					);
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot update configuration'));
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
		$autoreg = [];

		$this->getInputs($autoreg, ['tls_accept', 'tls_psk_identity', 'tls_psk']);

		$result = (bool) API::Autoregistration()->update($autoreg);

		$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
			->setArgument('action', 'autoreg.edit')
			->getUrl()
		);

		if ($result) {
			$response->setMessageOk(_('Configuration updated'));
		}
		else {
			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot update configuration'));
		}

		$this->setResponse($response);
	}
}
