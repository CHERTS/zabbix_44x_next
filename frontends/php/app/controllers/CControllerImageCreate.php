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


class CControllerImageCreate extends CController {

	protected function checkInput() {
		$fields = [
			'name'      => 'required | not_empty | db images.name',
			'imagetype' => 'required | fatal | db images.imagetype'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->getValidationError()) {
				case self::VALIDATION_ERROR:
					$url = (new CUrl('zabbix.php'))
						->setArgument('action', 'image.edit')
						->setArgument('imagetype', $this->getInput('imagetype'));

					$response = new CControllerResponseRedirect($url);
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot add image'));
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
		if ($this->getUserType() != USER_TYPE_SUPER_ADMIN) {
			return false;
		}

		$this->image = [
			'imageid'   => 0,
			'imagetype' => $this->getInput('imagetype'),
			'name'      => $this->getInput('name', '')
		];

		return true;
	}

	/**
	 * @param $error string
	 *
	 * @return string|null
	 */
	protected function uploadImage(&$error) {
		try {
			if (array_key_exists('image', $_FILES)) {
				$file = new CUploadFile($_FILES['image']);

				if ($file->wasUploaded()) {
					$file->validateImageSize();
					return base64_encode($file->getContent());
				}

				return null;
			}
			else {
				return null;
			}
		}
		catch (Exception $e) {
			$error = $e->getMessage();
		}

		return null;
	}

	protected function doAction() {
		$image = $this->uploadImage($error);

		if ($error) {
			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'image.edit')
				->setArgument('imagetype', $this->getInput('imagetype'))
			);
			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot add image'));

			return $this->setResponse($response);
		}

		$result = API::Image()->create([
			'imagetype' => $this->getInput('imagetype'),
			'name'      => $this->getInput('name'),
			'image'     => $image
		]);

		if ($result) {
			add_audit(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_IMAGE, 'Image ['.$this->getInput('name').'] created');

			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'image.list')
				->setArgument('imagetype', $this->getInput('imagetype'))
			);
			$response->setMessageOk(_('Image added'));
		}
		else {
			$url = (new CUrl('zabbix.php'))
				->setArgument('action', 'image.edit')
				->setArgument('imagetype', $this->getInput('imagetype'));


			$response = new CControllerResponseRedirect($url);
			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot add image'));
		}

		$this->setResponse($response);
	}
}
