<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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

require_once dirname(__FILE__).'/../include/CWebTest.php';
require_once dirname(__FILE__).'/traits/FormParametersTrait.php';

/**
 * @backup media_type
 */
class testFormAdministrationMediaTypeWebhook extends CWebTest {

	use FormParametersTrait;

	// SQL query to get media_type and media_type_param tables to compare hash values.
	private $sql = 'SELECT * FROM media_type mt INNER JOIN media_type_param mtp ON mt.mediatypeid=mtp.mediatypeid';

	public function getValidationWebhookData() {
		return [
			// Attempt to add a webhook media type with default values and only space in script field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with default fields',
						'Type' => 'Webhook',
						'Script' => ' '
					],
					'error_message' => 'Invalid parameter "/2/script": cannot be empty.'
				]
			],
			// Attepmt to add a webhook media type with a name that is already taken.
			[
				[
					'fields' => [
						'Name' => 'Email',
						'Type' => 'Webhook',
						'Script' => 'occupied name webhook'
					],
					'error_message' => 'Media type "Email" already exists.'
				]
			],
			// Attempt to add a webhook media type with a blank name.
			[
				[
					'fields' => [
						'Name' => '',
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Incorrect value for field "name": cannot be empty.'
				]
			],
			// Attempt to add a webhook with a blank parameter name.
			[
				[
					'fields' => [
						'Name' => 'Webhook with blank parameter name',
						'Type' => 'Webhook',
						'Script' => 'blank parameter name webhook'
					],
					'parameters' => [
						[
							'name' => '',
							'value' => '{BLANK.NAME}'
						]
					],
					'error_message' => 'Invalid parameter "/2/parameters/5/name": cannot be empty.'
				]
			],
			// Attempt to add a webhook without specifying the script field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with empty script field',
						'Type' => 'Webhook'
					],
					'error_message' => 'Invalid parameter "/2/script": cannot be empty.'
				]
			],
			// Attempt to add a webhook with timeout equal to zero.
			[
				[
					'fields' => [
						'Name' => 'Webhook with zero timeout',
						'Type' => 'Webhook',
						'Script' => 'Zero timeout',
						'Timeout' => '0'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with too large timeout.
			[
				[
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '61s'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with too large timeout #2.
			[
				[
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '2m'
					],
					'error_message' => 'Invalid parameter "/2/timeout": value must be one of 1-60.'
				]
			],
			// Attempt to add a webhook with a string in the timeout field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with too large timeout',
						'Type' => 'Webhook',
						'Script' => 'String in timeout',
						'Timeout' => '30seconds'
					],
					'error_message' => 'Invalid parameter "/2/timeout": a time unit is expected.'
				]
			],
			// Attempt to add a webhook with empty menu entry name.
			[
				[
					'fields' => [
						'Name' => 'Webhook with empty menu entry name',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry name',
						'Include event menu entry' => true,
						'Menu entry URL' => 'https://zabbix.com/{EVENT.TAGS."Returned value"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_name": cannot be empty.'
				]
			],
			// Attempt to add a webhook with empty menu entry URL.
			[
				[
					'fields' => [
						'Name' => 'Webhook with empty menu entry URL',
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_url": cannot be empty.'
				]
			],
			// Attempt to add a webhook with empty Attempts field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with empty attempts field',
						'Type' => 'Webhook',
						'Script' => 'Empty attempts field'
					],
					'options' => [
						'Attempts' => ''
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with 0 in Attempts field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with 0 in attempts field',
						'Type' => 'Webhook',
						'Script' => 'Zero Attempts'
					],
					'options' => [
						'Attempts' => '0'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with too much Attempts.
			[
				[
					'fields' => [
						'Name' => 'Webhook with too much attempts',
						'Type' => 'Webhook',
						'Script' => 'Too much Attempts'
					],
					'options' => [
						'Attempts' => '11'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Attempt to add a webhook with empty Attempt interval field.
			[
				[
					'fields' => [
						'Name' => 'Webhook with an empty Attempt interval',
						'Type' => 'Webhook',
						'Script' => 'Empty attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => ''
					],
					'error_message' => 'Incorrect value for field "attempt_interval": cannot be empty.'
				]
			],
			// Attempt to add a webhook with Attempt interval out of range.
			[
				[
					'fields' => [
						'Name' => 'Webhook with Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '61'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Attempt to add a webhook with custom concurrent sessions number out of range.
			[
				[
					'fields' => [
						'Name' => 'Webhook with Attempt interval out of range',
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '5'
					],
					'concurrent_sessions' => [
						'Custom' => '101'
					],
					'error_message' => 'Incorrect value for field "maxsessions": must be between "0" and "100".'
				]
			],
			// Attempt to set concurrent sessions field to custom and set the value out of range.
			[
				[
					'fields' => [
						'Name' => 'Custom concurrent sessions out of range',
						'Type' => 'Webhook',
						'Script' => 'Custom concurrent sessions out of range'
					],
					'options' => [
						'Attempts' => '5'
					],
					'concurrent_sessions' => [
						'Custom' => '101'
					],
					'error_message' => 'Incorrect value for field "maxsessions": must be between "0" and "100".'
				]
			],
			// Adding a parameter with a blank name to the media type.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'blank parameter name webhook'
					],
					'parameters' => [
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'name' => '',
							'value' => '{BLANK.NAME}'
						]
					],
					'error_message' => 'Invalid parameter "/1/parameters/2/name": cannot be empty.'
				]
			],
			// Removing the value of field script.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => ''
					],
					'error_message' => 'Invalid parameter "/1/script": cannot be empty.'
				]
			],
			// Changing the value of timeout field to zero.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Zero timeout',
						'Timeout' => '0'
					],
					'error_message' => 'Invalid parameter "/1/timeout": value must be one of 1-60.'
				]
			],
			// Increasing timeout too high.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Large timeout',
						'Timeout' => '3d'
					],
					'error_message' => 'Invalid parameter "/1/timeout": value must be one of 1-60.'
				]
			],
			// Changing value of field timeout to a string
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'String in timeout',
						'Timeout' => '1minute'
					],
					'error_message' => 'Invalid parameter "/1/timeout": a time unit is expected.'
				]
			],
			// Add a menu entry URL without a menu entry name.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry name',
						'Include event menu entry' => true,
						'Menu entry URL' => '{EVENT.TAGS.Address}'
					],
					'error_message' => 'Incorrect value for field "event_menu_name": cannot be empty.'
				]
			],
			// Add a menu entry name without a menu entry URL.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Empty menu entry URL',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned tag"}'
					],
					'error_message' => 'Incorrect value for field "event_menu_url": cannot be empty.'
				]
			],
			// Remove the value of the attempts field.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Empty attempts field'
					],
					'options' => [
						'Attempts' => ''
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field to 0.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Zero Attempts'
					],
					'options' => [
						'Attempts' => '0'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field too high.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Too much Attempts'
					],
					'options' => [
						'Attempts' => '100'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Set the value of Attempts field to some string.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'attempts in string format'
					],
					'options' => [
						'Attempts' => 'five'
					],
					'error_message' => 'Incorrect value for field "maxattempts": must be between "1" and "10".'
				]
			],
			// Remove the value of the attempt interval field.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Empty attempt interval'
					],
					'options' => [
						'Attempts' => '1',
						'Attempt interval' => ''
					],
					'error_message' => 'Incorrect value for field "attempt_interval": cannot be empty.'
				]
			],
			// Set a value of the attempt interval that is out of range.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'Out of range attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '61'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Set a string value in the attempt interval field.
			[
				[
					'update' => true,
					'fields' => [
						'Type' => 'Webhook',
						'Script' => 'String in attempt interval'
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '10seconds'
					],
					'error_message' => 'Incorrect value for field "attempt_interval": must be between "0" and "60".'
				]
			],
			// Removing the name of a webhook media type.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => '',
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Incorrect value for field "name": cannot be empty.'
				]
			],
			// Changing the name of a webhook media type to a name that is already taken.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => 'Email',
						'Type' => 'Webhook',
						'Script' => 'blank name webhook'
					],
					'error_message' => 'Media type "Email" already exists.'
				]
			]
		];
	}

	/**
	 * @dataProvider getValidationWebhookData
	 */
	public function testFormAdministrationMediaTypeWebhook_Validate($data) {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$button = CTestArrayHelper::get($data, 'update', false) ? 'link:Validation webhook' : 'button:Create media type';
		$this->query($button)->one()->WaitUntilClickable()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill($data['fields']);
		// Fill webhook parameters if needed.
		if (array_key_exists('parameters', $data)) {
			$this->fillParameters($data['parameters']);
		}
		// Fill fields in Operations tab if needed.
		if (CTestArrayHelper::get($data, 'options', false) || CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$form->selectTab('Options');
			$this->fillOperationsTab($data, $form);
		}

		$form->submit();
		$this->page->waitUntilReady();

		// Check media type creation or update message.
		$message_title = CTestArrayHelper::get($data, 'update', false) ? 'Cannot update media type' : 'Cannot add media type';
		$message = CMessageElement::find()->one();
		$this->assertEquals($message_title, $message->getTitle());
		$this->assertTrue($message->hasLine($data['error_message']));

		// Check that no DB changes took place.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public function getCreateUpdateWebhookData() {
		return [
			// Add webhook media type without parameters.
			[
				[
					'fields' => [
						'Name' => 'Webhook without parameters',
						'Type' => 'Webhook',
						'Script' => 'all parameters should be removed'
					],
					'remove_parameters' => true
				]
			],
			// Add webhook media type with enabled menu entry fields and changed options tab fields.
			[
				[
					'fields' => [
						'Name' => 'Webhook with enabled menu entry fields',
						'Type' => 'Webhook',
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS.Name}',
						'Menu entry URL' => '{EVENT.TAGS.Url}',
						'Script' => 'Webhook with specified "Menu entry name" and "Menu entry URL" fields'
					],
					'options' => [
						'Attempts' => '10',
						'Attempt interval' => '1m'
					],
					'concurrent_sessions' => [
						'Custom' => '5'
					]
				]
			],
			// Add webhook media type with all possible parameters defined.
			[
				[
					'fields' => [
						'Name' => 'All fields specified',
						'Type' => 'Webhook',
						'Script' => '!@#$%^&*()_+-=[]{};:"|,./<>?',
						'Timeout' => '1m',
						'Process tags' => true,
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned value"}',
						'Menu entry URL' => 'http://zabbix.com/browse/{EVENT.TAGS."Returned value"}',
						'Description' => 'Webhook with all possible fields',
						'Enabled' => false
					],
					'options' => [
						'Attempts' => '5',
						'Attempt interval' => '20s'
					],
					'concurrent_sessions' => 'Unlimited',
					'parameters' => [
						[
							'name' => '1st new parameter',
							'value' => '1st new parameter value'
						],
						[
							'name' => '2nd parameter',
							'value' => '{2ND.PARAMETER}'
						],
						[
							'action' => USER_ACTION_REMOVE,
							'name' => 'URL'
						]
					]
				]
			],
			// Change the type of media type.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => 'Webhook changed to SMS',
						'Type' => 'SMS',
						'GSM modem' => '/dev/ttyS0'
					],
					'check_parameters' => false
				]
			],
			// Remove all webhook parameters and change timeout.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => 'Remove webhook parameters',
						'Type' => 'Webhook',
						'Timeout' => '10',
						'Script' => 'all parameters should be removed'
					],
					'remove_parameters' => true
				]
			],
			// Update webhook script and add Menu entry parameters.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => 'Add menu entry fields',
						'Type' => 'Webhook',
						'Include event menu entry' => true,
						'Menu entry name' => 'Menu entry name',
						'Menu entry URL' => 'https://zabbix.com',
						'Script' => 'New webhook script'
					]
				]
			],
			// Update all possible webhook parameters.
			[
				[
					'update' => true,
					'fields' => [
						'Name' => 'All fields updated',
						'Type' => 'Webhook',
						'Script' => '!@#$%^&*()_+-=[]{};:"|,./<>?',
						'Timeout' => '1m',
						'Process tags' => true,
						'Include event menu entry' => true,
						'Menu entry name' => '{EVENT.TAGS."Returned value"}',
						'Menu entry URL' => 'http://zabbix.com/browse/{EVENT.TAGS."Returned value"}',
						'Description' => 'This is the new description of this media type !@#$%^&*()_+-=[]{};:"|,./<>?',
						'Enabled' => false
					],
					'options' => [
						'Attempts' => '1',
						'Attempt interval' => '0'
					],
					'concurrent_sessions' => [
						'Custom' => '2'
					],
					'parameters' => [
						[
							'name' => '1st new parameter',
							'value' => '{1ST.PARAMETER}'
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 1,
							'name' => '1st updated parameter',
							'value' => '1st updated parameter value'
						],
						[
							'action' => USER_ACTION_UPDATE,
							'index' => 2,
							'name' => '2nd updated parameter',
							'value' => '{2ND.PARAMETER}'
						],
						[
							'action' => USER_ACTION_REMOVE,
							'name' => 'URL'
						]
					]
				]
			]
		];
	}

	/**
	 * @backup media_type
	 * @dataProvider getCreateUpdateWebhookData
	 */
	public function testFormAdministrationMediaTypeWebhook_CreateUpdate($data) {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$button = CTestArrayHelper::get($data, 'update', false) ? 'link:Reference webhook' : 'button:Create media type';
		$this->query($button)->one()->WaitUntilClickable()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill($data['fields']);
		// Fill webhook parameters if needed.
		if (array_key_exists('parameters', $data)) {
			$this->fillParameters($data['parameters']);
		}
		// Remove all parameters if corresponding flag exists.
		if (array_key_exists('remove_parameters', $data)) {
			$this->removeParameters();
		}
		// Fill fields in Operations tab if needed.
		if (CTestArrayHelper::get($data, 'options', false) || CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$form->selectTab('Options');
			$this->fillOperationsTab($data, $form);
		}
		$form->submit();
		$this->page->waitUntilReady();

		// Check media type creation or update message.
		$message_title = CTestArrayHelper::get($data, 'update', false) ? 'Media type updated' : 'Media type added';
		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals($message_title, $message->getTitle());
		// Check that the media type was actually created or updated.
		$mediatype_count = CDBHelper::getCount('SELECT mediatypeid FROM media_type WHERE name='.
				CDBHelper::escape($data['fields']['Name']));
		$this->assertEquals(1, $mediatype_count);

		// Check the values of created or updated media type fields.
		$this->checkMediaTypeFields($data);
	}

	public function testFormAdministrationMediaTypeWebhook_SimpleUpdate() {
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$this->query('link:Reference webhook')->one()->WaitUntilClickable()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one()->submit();
		$this->page->waitUntilReady();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type updated', $message->getTitle());

		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public function testFormAdministrationMediaTypeWebhook_Clone() {
		// SQL for collecting all webhook mediatype parameter values, both from media_type and media_type_param tables.
		$mediatype_sql = 'SELECT type, status, maxsessions, maxattempts, attempt_interval, content_type, script, timeout, '.
				'process_tags, show_event_menu, event_menu_name, event_menu_url, description, mtp.name, mtp.value '.
				'FROM media_type mt INNER JOIN media_type_param mtp ON mt.mediatypeid=mtp.mediatypeid WHERE mt.name=';
		$old_hash = CDBHelper::getHash($mediatype_sql.'\'Reference webhook\' ORDER BY mtp.name');

		// Clone the reference media type.
		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$this->query('link:Reference webhook')->one()->WaitUntilClickable()->click();
		$this->query('button:Clone')->one()->click();
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill(['Name' => 'Webhook clone']);
		$form->submit();
		$this->page->waitUntilReady();

		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type added', $message->getTitle());
		// Check that the parameters of the clone and of the cloned media types are equal.
		$this->assertEquals($old_hash, CDBHelper::getHash($mediatype_sql.'\'Webhook clone\' ORDER BY mtp.name'));
	}

	public function getCancelActionData() {
		return [
			[
				[
					'create'
				]
			],
			[
				[
					'update'
				]
			],
			[
				[
					'clone'
				]
			]
		];
	}

	/**
	 * @dataProvider getCancelActionData
	 */
	public function testFormAdministrationMediaTypeWebhook_Cancel($data) {
		$fields = [
			'Name' => 'To be Cancelled',
			'Type' => 'Webhook',
			'Script' => '2 B Cancelled'
		];
		$old_hash = CDBHelper::getHash($this->sql);

		$this->page->login()->open('zabbix.php?action=mediatype.list');
		$button = ($data === 'create') ? 'button:Create media type' : 'link:Reference webhook';
		$this->query($button)->one()->WaitUntilClickable()->click();
		if ($data === 'clone') {
			$this->query('button:Clone')->one()->click();
		}
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();
		$form->fill($fields);
		$form->query('button:Cancel')->one()->click();
		$this->page->waitUntilReady();
		// Make sure no changes took place.
		$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
	}

	public function testFormAdministrationMediaTypeWebhook_Delete() {
		$mediatypeid = CDBHelper::getValue('SELECT mediatypeid FROM media_type WHERE name=\'Webhook to delete\'');
		$this->page->login()->open('zabbix.php?action=mediatype.edit&mediatypeid='.$mediatypeid);
		$this->query('button:Delete')->one()->waitUntilClickable()->click();
		$this->page->acceptAlert();
		$this->page->waitUntilReady();
		// Verify that media type was deleted
		$message = CMessageElement::find()->one();
		$this->assertTrue($message->isGood());
		$this->assertEquals('Media type deleted', $message->getTitle());
		$this->assertEquals(0, CDBHelper::getCount('SELECT mediatypeid FROM media_type WHERE name=\'Webhook to delete\''));
	}

	/**
	 * Get table element with mapping set.
	 */
	protected function getTable() {
		return $this->query('id:parameters_table')->asMultifieldTable([
			'mapping' => [
				'Name' => [
					'name' => 'name',
					'selector' => 'xpath:./input',
					'class' => 'CElement'
				],
				'Value' => [
					'name' => 'value',
					'selector' => 'xpath:./input',
					'class' => 'CElement'
				]
			]
		])->one();
	}

	/**
	 * Function used to popullate fields located in the Operations tab.
	 * Field concurrent sessions has two input elelents - one of them is displayed only if concurrent sessions = Custom.
	 * Therefore, fill() method cannot be used for this field, and it needs to be popullated separately.
	 */
	private function fillOperationsTab($data, $form) {
		if (CTestArrayHelper::get($data, 'options', false)) {
			$form->fill($data['options']);
		}
		if (CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$container = $form->getFieldContainer('Concurrent sessions');
			$concurrent_sessions = is_array($data['concurrent_sessions'])
					? array_key_first($data['concurrent_sessions']) : $data['concurrent_sessions'];
			$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->select($concurrent_sessions);
			if (is_array($data['concurrent_sessions'])) {
				$container->query('id:maxsessions')->one()->fill(array_values($data['concurrent_sessions']));
			}
		}
	}

	/**
	 * Check the field values after creating or updating a media type.
	 */
	private function checkMediaTypeFields($data) {
		$mediatypeid = CDBHelper::getValue('SELECT mediatypeid FROM media_type WHERE name='.zbx_dbstr($data['fields']['Name']));
		$this->page->open('zabbix.php?action=mediatype.edit&mediatypeid='.$mediatypeid);
		$form = $this->query('id:media_type_form')->asForm()->waitUntilVisible()->one();

		// Check that fields in Media type tab are updated.
		$form->checkValue($data['fields']);
		// Check webhook parameters
		if (CTestArrayHelper::get($data, 'check_parameters', true)) {
			$this->checkParameters($data);
		}
		// Check that "Menu entry name" and "Menu entry URL" fields are enabled only if "Include event menu entry" is set.
		$menu_enabled = CTestArrayHelper::get($data, 'fields.Include event menu entry', false);
		$this->assertTrue($form->getField('Menu entry name')->isEnabled($menu_enabled));
		$this->assertTrue($form->getField('Menu entry URL')->isEnabled($menu_enabled));
		// Check that fields in Options tab are updated.
		if (CTestArrayHelper::get($data, 'options', false) || CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$this->checkmediaTypeOptions($data, $form);
		}
	}

	/**
	 * Function prepares a reference parameters array based on parameter actions in data provider and compares it with
	 * the values obtained from the media type configuration form.
	 */
	private function checkParameters($data) {
		// Check creation, update and deletion of webhook parameters, or check that all 4 default prameters are present.
		$expected_params = [
			['name' => 'Message', 'value' => '{ALERT.MESSAGE}'],
			['name' => 'Subject', 'value' => '{ALERT.SUBJECT}'],
			['name' => 'To', 'value' => '{ALERT.SENDTO}'],
			['name' => 'URL', 'value' => '']
		];
		// Add, substitute or remove parameters from the reference array based on parameter actions in data provider.
		if (CTestArrayHelper::get($data, 'parameters', false)) {
			foreach ($data['parameters'] as $parameter) {
				$action = CTestArrayHelper::get($parameter, 'action', USER_ACTION_ADD);
				switch ($action) {
					case USER_ACTION_ADD:
						array_push($expected_params, $parameter);
						break;
					case USER_ACTION_UPDATE:
						$remplacement = [$parameter['index'] => ['name' => $parameter['name'], 'value' => $parameter['value']]];
						$expected_params = array_replace($expected_params, $remplacement);
						break;
					case USER_ACTION_REMOVE:
						$expected_params = array_filter($expected_params, function($p) use ($parameter){
							return $p['name'] != $parameter['name'];
						});
						break;
				}
			}
		}
		// Sort the parameters in reference array alphabetically.
		usort($expected_params, function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});
		// If parameters were not deleted from the media type, compare them with the reference array.
		$this->assertValues((CTestArrayHelper::get($data, 'remove_parameters', false)) ? [] : $expected_params);
	}

	/**
	 * Check the values of fields located in the Operations tab.
	 */
	private function checkMediaTypeOptions($data, $form) {
		$form->selectTab('Options');
		if (CTestArrayHelper::get($data, 'options', false)) {
			$form->checkValue($data['options']);
		}
		if (CTestArrayHelper::get($data, 'concurrent_sessions', false)) {
			$container = $form->getFieldContainer('Concurrent sessions');
			$concurrent_sessions = is_array($data['concurrent_sessions'])
					? array_key_first($data['concurrent_sessions']) : $data['concurrent_sessions'];
			$this->assertEquals($concurrent_sessions,
					$container->query('id:maxsessions_type')->asSegmentedRadio()->one()->getValue());
			// If concurrent sessions type is Custom then value should be checked for this field.
			if ($concurrent_sessions === 'Custom') {
				$this->assertEquals($data['concurrent_sessions']['Custom'], $container->query('id:maxsessions')->one()->getValue());
			}
		}
	}
}
