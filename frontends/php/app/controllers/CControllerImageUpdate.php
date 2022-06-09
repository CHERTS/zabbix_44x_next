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


class CControllerImageUpdate extends CController {

	protected function checkInput() {
		$fields = [
			'name'      => 'required | not_empty | db images.name',
			'imageid'   => 'required | fatal | db images.imageid',
			'imagetype' => 'required | fatal | db images.imagetype'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->getValidationError()) {
				case self::VALIDATION_ERROR:
					$url = (new CUrl('zabbix.php'))
						->setArgument('action', 'image.edit')
						->setArgument('imagetype', $this->getInput('imagetype'))
						->setArgument('imageid', $this->getInput('imageid'));

					$response = new CControllerResponseRedirect($url);
					$response->setFormData($this->getInputAll());
					$response->setMessageError(_('Cannot update image'));
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

		$images = API::Image()->get(['imageids' => (array) $this->getInput('imageid')]);
		if (!$images) {
			return false;
		}

		$this->image = $images[0];

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
				->setArgument('imageid', $this->getInput('imageid'))
			);
			error($error);
			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot update image'));

			return $this->setResponse($response);
		}

		if ($this->hasInput('imageid')) {
			$result = API::Image()->update([
				'imageid' => $this->getInput('imageid'),
				'name'    => $this->getInput('name'),
				'image'   => $image
			]);
		}
		else {
			$result = API::Image()->create([
				'imagetype' => $this->getInput('imagetype'),
				'name'      => $this->getInput('name'),
				'image'     => $image
			]);
		}

		if ($result) {
			add_audit(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_IMAGE, 'Image ['.$this->getInput('name').'] updated');

			$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
				->setArgument('action', 'image.list')
				->setArgument('imagetype', $this->getInput('imagetype'))
			);

			$response->setMessageOk(_('Image updated'));
		}
		else {
			$url = (new CUrl('zabbix.php'))
				->setArgument('action', 'image.edit')
				->setArgument('imagetype', $this->getInput('imagetype'))
				->setArgument('imageid', $this->getInput('imageid'));

			$response = new CControllerResponseRedirect($url);
			$response->setFormData($this->getInputAll());
			$response->setMessageError(_('Cannot update image'));
		}

		$this->setResponse($response);
	}
}
