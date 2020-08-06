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


require_once dirname(__FILE__).'/../include/CAPITest.php';

/**
 * @backup httptest
 */
class testWebScenario extends CAPITest {

	public static function httptest_create() {
		return [
			[
				'httptest' => [
					'name' => 'Api web scenario',
					'hostid' => '50009',
					'httptestid' => '1'
				],
				'expected_error' => 'Invalid parameter "/1": unexpected parameter "httptestid".'
			],
			// Check web name.
			[
				'httptest' => [
					'hostid' => '50009'
				],
				'expected_error' => 'Invalid parameter "/1": the parameter "name" is missing.'
			],
			[
				'httptest' => [
					'name' => '',
					'hostid' => '50009'
				],
				'expected_error' => 'Invalid parameter "/1/name": cannot be empty.'
			],
			// Check for duplicated web scenarios names.
			[
				'httptest' => [
					'name' => 'Api web scenario',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'Web scenario "Api web scenario" already exists.'
			],
			[
				'httptest' => [
					[
						'name' => 'One web scenario with existing name',
						'hostid' => '50009',
						'steps' => [
							[
								'name' => 'Homepage',
								'url' => 'http://zabbix.com',
								'no' => 0,
							]
						]
					],
					[
						'name' => 'Api web scenario',
						'hostid' => '50009',
						'steps' => [
							[
								'name' => 'Homepage',
								'url' => 'http://zabbix.com',
								'no' => 0,
							]
						]
					],
				],
				'expected_error' => 'Web scenario "Api web scenario" already exists.'
			],
			[
				'httptest' => [
					[
						'name' => 'Web scenario with two identical name',
						'hostid' => '50009',
						'steps' => [
							[
								'name' => 'Homepage',
								'url' => 'http://zabbix.com',
								'no' => 0,
							]
						]
					],
					[
						'name' => 'Web scenario with two identical name',
						'hostid' => '50009',
						'steps' => [
							[
								'name' => 'Homepage',
								'url' => 'http://zabbix.com',
								'no' => 0,
							]
						]
					]
				],
				'expected_error' => 'Invalid parameter "/2": value (hostid, name)=(50009, Web scenario with two identical name) already exists.'
			],
			// Check web hostid.
			[
				'httptest' => [
					'name' => 'Api create web without hostid'
				],
				'expected_error' => 'Invalid parameter "/1": the parameter "hostid" is missing.'
			],
			[
				'httptest' => [
					'name' => 'Api create web with empty hostid',
					'hostid' => ''
				],
				'expected_error' => 'Invalid parameter "/1/hostid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api create web with wrong hostid',
					'hostid' => 'æų☺'
				],
				'expected_error' => 'Invalid parameter "/1/hostid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api create web with wrong hostid',
					'hostid' => '5000.9'
				],
				'expected_error' => 'Invalid parameter "/1/hostid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api create web with nonexistent hostid',
					'hostid' => '123456',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'httptest' => [
					'name' => 'Api create web with nonexistent hostid',
					'hostid' => '0',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Check successfully create.
			[
				'httptest' => [[
					'name' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'hostid' => '50009',
					'agent' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'applicationid' => 0,
					'authentication' => '2',
					'http_user' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'http_proxy' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'http_password' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'retries' => '10',
					'ssl_cert_file' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'ssl_key_file' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'ssl_key_password' => 'api-symbols☺æų""\\//!@#$%^&*()_+',
					'status' => '0',
					'headers' => [
						[
							'name' => 'header_name-symbols☺æų""\\//!@#$%^&*()_+',
							'value' => 'header_value-symbols☺æų""\\//!@#$%^&*()_+',
						],
						[
							'name' => 'header_name-symbols☺æų""\\//!@#$%^&*()_+',
							'value' => 'header_value-symbols☺æų""\\//!@#$%^&*()_+',
						],
					],
					'variables' => [
						[
							'name' => '{variables_name-symbols☺æų""\\//!@#$%^&*()_+}',
							'value' => 'variables_value-symbols☺æų""\\//!@#$%^&*()_+',
						]
					],
					'verify_host' => '1',
					'verify_peer' => '1',
					'steps' => [
						[
							'name' => 'step-symbols☺æų""\\//!@#$%^&*()_+',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				]],
				'expected_error' => null
			],
			[
				'httptest' => [[
					'name' => 'АПИ веб сценарий УТФ-8',
					'hostid' => '50009',
					'agent' => 'АПИ веб агент УТФ-8',
					'authentication' => '2',
					'http_user' => 'АПИ веб юзер УТФ-8',
					'http_proxy' => 'АПИ веб прокси УТФ-8',
					'http_password' => 'АПИ веб пароль УТФ-8',
					'ssl_cert_file' => 'АПИ веб файл УТФ-8',
					'ssl_key_file' => 'АПИ веб клуюч файл УТФ-8',
					'ssl_key_password' => 'АПИ веб ключ пароль УТФ-8',
					'headers' => [
						[
							'name' => 'АПИ веб название УТФ-8',
							'value' => 'АПИ веб значение УТФ-8',
						]
					],
					'variables' => [
						[
							'name' => '{АПИ веб переменная УТФ-8}',
							'value' => 'АПИ веб значение переменной УТФ-8',
						]
					],
					'steps' => [
						[
							'name' => 'АПИ веб шаг УТФ-8',
							'url' => 'http://zabbix.com',
							'no' => 1
						]
					]
				]],
				'expected_error' => null
			],
			[
				'httptest' => [
				[
					'name' => 'API Create two web scenarios 1',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				[
					'name' => 'API Create two web scenarios 2',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				]],
				'expected_error' => null
			],
			[
				'httptest' => [
				[
					'name' => 'API Create two web scenarios 3',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
							'retrieve_mode' => 2
						]
					]
				],
				[
					'name' => 'API Create two web scenarios 4',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
							'retrieve_mode' => 3
						]
					]
				]],
				'expected_error' => 'Invalid parameter "/2/steps/1/retrieve_mode": value must be one of 0, 1, 2.'
			],
			[
				'httptest' => [
				[
					'name' => 'API Create two web scenarios 5',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step 1',
							'url' => 'http://zabbix.com',
							'no' => 0,
							'retrieve_mode' => 0
						],
						[
							'name' => 'API create step 2',
							'url' => 'http://zabbix.com',
							'no' => 1,
							'retrieve_mode' => 1
						],
						[
							'name' => 'API create step 3',
							'url' => 'http://zabbix.com',
							'no' => 2,
							'retrieve_mode' => 2
						]
					]
				]],
				'expected_error' => null
			]
		];
	}

	/**
	* @dataProvider httptest_create
	*/
	public function testWebScenario_Create($httptests, $expected_error) {
		$result = $this->call('httptest.create', $httptests, $expected_error);

		if ($expected_error === null) {
			foreach ($result['result']['httptestids'] as $key => $id) {
				$db_result_web = DBSelect('SELECT * FROM httptest WHERE httptestid='.zbx_dbstr($id));
				$db_row_web = DBFetch($db_result_web);
				$this->assertEquals($db_row_web['name'], $httptests[$key]['name']);
				$this->assertEquals($db_row_web['hostid'], $httptests[$key]['hostid']);

				$db_result_steps = DBSelect('SELECT * FROM httpstep WHERE httptestid='.zbx_dbstr($id).' order by no;');
				$db_rows_steps = DBFetchArray($db_result_steps);
				$this->assertCount(count($httptests[$key]['steps']), $db_rows_steps);

				// It is assumed dataset steps array is sorted by 'no' field.
				foreach($db_rows_steps as $no => $db_step) {
					$dataset_step = $httptests[$key]['steps'][$no];
					// Defaults are to be tested.
					if (!array_key_exists('retrieve_mode', $dataset_step)) {
						$dataset_step['retrieve_mode'] = HTTPTEST_STEP_RETRIEVE_MODE_CONTENT;
					}

					foreach ($dataset_step as $property_name => $expected) {
						$debug_msg = 'Case, httptest['.$key.']->step['.$no.']->property['.$property_name.']';
						$this->assertEquals($expected, $db_step[$property_name], $debug_msg);
					}
				}
			}
		}
		else {
			foreach ([$httptests] as $httptest) {
				if (array_key_exists('name', $httptest) && $httptest['name'] !== 'Api web scenario'){
					$this->assertEquals(0, CDBHelper::getCount('select * from httptest where name='.zbx_dbstr($httptest['name'])));
				}
			}
		}
	}

	public static function httptest_update() {
		return [
			[
				'httptest' => [[
					'name' => 'Api update web scenario with unexpected parameter',
					'hostid' => '50009',
				]],
				'expected_error' => 'Invalid parameter "/1": unexpected parameter "hostid".'
			],
			// Check web scenario id.
			[
				'httptest' => [[
					'name' => 'Api updated web scenario without id'
				]],
				'expected_error' => 'Invalid parameter "/1": the parameter "httptestid" is missing.'
			],
			[
				'httptest' => [[
					'name' => 'Api updated web scenario with empty id',
					'httptestid' => ''
				]],
				'expected_error' => 'Invalid parameter "/1/httptestid": a number is expected.'
			],
			[
				'httptest' => [[
					'name' => 'Api updated web scenario with invalid id',
					'httptestid' => 'abc'
				]],
				'expected_error' => 'Invalid parameter "/1/httptestid": a number is expected.'
			],
			[
				'httptest' => [[
					'name' => 'Api updated web scenario with invalid id',
					'httptestid' => '1.1'
				]],
				'expected_error' => 'Invalid parameter "/1/httptestid": a number is expected.'
			],
			[
				'httptest' => [[
					'name' => 'Api updated web scenario with nonexistent id',
					'httptestid' => '123456'
				]],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'httptest' => [
					[
						'httptestid' => '15001',
						'name' => 'Web scenarios with the same id 1'
					],
					[
						'httptestid' => '15001',
						'name' => 'Web scenarios the same id 2'
					]
				],
				'expected_error' => 'Invalid parameter "/2": value (httptestid)=(15001) already exists.'
			],
			// Check web scenario name.
			[
				'httptest' => [[
					'httptestid' => '15001',
					'name' => '',
				]],
				'expected_error' => 'Invalid parameter "/1/name": cannot be empty.'
			],
			[
				'httptest' => [[
					'httptestid' => '15001',
					'name' => 'Api web scenario',
				]],
				'expected_error' => 'Web scenario "Api web scenario" already exists.'
			],
			[
				'httptest' => [
					[
						'httptestid' => '15001',
						'name' => 'Web scenarios with the same name',
					],
					[
						'httptestid' => '15002',
						'name' => 'Web scenarios with the same name',
					]
				],
				'expected_error' => 'Invalid parameter "/2": value (hostid, name)=(50009, Web scenarios with the same name) already exists.'
			],
			// Check successfully web scenario update.
			[
				'httptest' => [
					[
						'httptestid' => '15001',
						'name' => 'Апи скрипт обнавлён утф-8',
					]
				],
				'expected_error' => null
			],
			[
				'httptest' => [
					[
						'httptestid' => '15001',
						'name' => 'Api updated one web scenario',
					],
					[
						'httptestid' => '15002',
						'name' => 'Api updated two web scenario',
					]
				],
				'expected_error' => null
			],
			// Check successfully web scenario update. Including steps.
			// 15012 has one step.
			// 15013 has two steps.
			[
				'httptest' => [
					[
						'httptestid' => '15012',
						'name' => 'Api updated into scenario without steps.',
						'steps' => []
					]
				],
				'expected_error' => 'Invalid parameter "/1/steps": cannot be empty.'
			]
		];
	}

	/**
	* @dataProvider httptest_update
	*/
	public function testWebScenario_Update($httptests, $expected_error) {
		$result = $this->call('httptest.update', $httptests, $expected_error);

		if ($expected_error === null) {
			foreach ($result['result']['httptestids'] as $key => $id) {
				$dbResult = DBSelect('select * from httptest where httptestid='.zbx_dbstr($id));
				$dbRow = DBFetch($dbResult);
				$this->assertEquals($dbRow['name'], $httptests[$key]['name']);
				$this->assertEquals($dbRow['httptestid'], $httptests[$key]['httptestid']);
				$this->assertEquals($dbRow['applicationid'], 0);
				$this->assertEquals($dbRow['nextcheck'], 0);
				$this->assertEquals($dbRow['delay'], 60);
				$this->assertEquals($dbRow['status'], 0);
				$this->assertEquals($dbRow['agent'], 'Zabbix');
				$this->assertEquals($dbRow['authentication'], 0);
				$this->assertEquals($dbRow['http_user'], '');
				$this->assertEquals($dbRow['http_password'], '');
				$this->assertEquals($dbRow['hostid'], 50009);
				$this->assertEquals($dbRow['templateid'], 0);
				$this->assertEquals($dbRow['http_proxy'], '');
				$this->assertEquals($dbRow['retries'], 1);
				$this->assertEquals($dbRow['ssl_cert_file'], '');
				$this->assertEquals($dbRow['ssl_key_file'], '');
				$this->assertEquals($dbRow['ssl_key_password'], '');
				$this->assertEquals($dbRow['verify_peer'], 0);
				$this->assertEquals($dbRow['verify_host'], 0);
			}
		}
		else {
			foreach ($httptests as $httptest) {
				if (array_key_exists('name', $httptest) && $httptest['name'] !== 'Api web scenario'){
					$this->assertEquals(0, CDBHelper::getCount('select * from httptest where name='.zbx_dbstr($httptest['name'])));
				}
			}
		}
	}

	public static function web_properties() {
		return [
			// Check  unexpected parameter.
			[
				'httptest' => [
					'name' => 'Api web scenario with readonly parameter',
					'templateid' => '1'
				],
				'expected_error' => 'Invalid parameter "/1": unexpected parameter "templateid".'
			],
			// Check web name.
			[
				'httptest' => [
					'name' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
				],
				'expected_error' => 'Invalid parameter "/1/name": value is too long.'
			],
			// Check web agent.
			[
				'httptest' => [
					'name' => 'Api web with long agent',
					'agent' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/agent": value is too long.'
			],
			// Check web applicationid.
			[
				'httptest' => [
					'name' => 'Api web with empty applicationid',
					'applicationid' => ''
				],
				'expected_error' => 'Invalid parameter "/1/applicationid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with nonexistent applicationid',
					'applicationid' => '123456',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Application with applicationid "123456" does not exist.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong applicationid',
					'applicationid' => 'test',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/applicationid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong applicationid',
					'applicationid' => '☺æų',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/applicationid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong applicationid',
					'applicationid' => '36.6',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/applicationid": a number is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with applicationid from another host',
					'applicationid' => '376',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'The web scenario application belongs to a different host than the web scenario host.'
			],
			[
				'httptest' => [
					'name' => 'Api web with discovered applicationid',
					'applicationid' => '375',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Cannot add a discovered application "API discovery application" to a web scenario.'
			],
			// Check web authentication.
			[
				'httptest' => [
					'name' => 'Api web with nonexistent authentication',
					'authentication' => '4'
				],
				'expected_error' => 'Invalid parameter "/1/authentication": value must be one of 0, 1, 2, 3.'
			],
			[
				'httptest' => [
					'name' => 'Api web with nonexistent authentication',
					'authentication' => '-2'
				],
				'expected_error' => 'Invalid parameter "/1/authentication": value must be one of 0, 1, 2, 3.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong authentication',
					'authentication' => '☺'
				],
				'expected_error' => 'Invalid parameter "/1/authentication": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong authentication',
					'authentication' => '0.1'
				],
				'expected_error' => 'Invalid parameter "/1/authentication": an integer is expected.'
			],
			// Check web delay.
			[
				'httptest' => [
					'name' => 'Api web with wrong delay',
					'delay' => '-1'
				],
				'expected_error' => 'Invalid parameter "/1/delay": value must be one of 1-86400.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong delay',
					'delay' => '0'
				],
				'expected_error' => 'Invalid parameter "/1/delay": value must be one of 1-86400.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong delay',
					'delay' => '86401'
				],
				'expected_error' => 'Invalid parameter "/1/delay": value must be one of 1-86400.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong delay',
					'delay' => '☺'
				],
				'expected_error' => 'Invalid parameter "/1/delay": a time unit is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong delay',
					'delay' => '1.5'
				],
				'expected_error' => 'Invalid parameter "/1/delay": a time unit is expected.'
			],
			// Check web headers.
			[
				'httptest' => [
					'name' => 'Api web with wrong headers',
					'headers' => [
						['name' => '☺', 'value' => '']
					]
				],
				'expected_error' => 'Invalid parameter "/1/headers/1/value": cannot be empty.'
			],
			[
				'httptest' => [
					'name' => 'Api web with empty headers value',
					'headers' => [
						[
							'name' => 'login',
							'value' => '',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/headers/1/value": cannot be empty.'
			],
			[
				'httptest' => [
					'name' => 'Api web with empty headers name',
					'headers' => [
						[
							'name' => '',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/headers/1/name": cannot be empty.'
			],
			[
				'httptest' => [
					'name' => 'Api web with long headers name',
					'headers' => [
						[
							'name' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/headers/1/name": value is too long.'
			],
			// Check web password used for basic HTTP authentication.
			[
				'httptest' => [
					'name' => 'Api web with long http_password',
					'authentication' => '1',
					'http_user' => 'admin',
					'http_password' => 'Phasellus imperdiet sapien sed justo elementum, quis maximuslpi65',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/http_password": value is too long.'
			],
			[
				'httptest' => [
					'name' => 'Api web none authentication but with http_password',
					'authentication' => '0',
					'http_password' => '☺',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Incorrect value for field "http_password": should be empty.'
			],
			// Check web password used for NTLM  authentication.
			[
				'httptest' => [
					'name' => 'Api web with long http_password',
					'authentication' => '2',
					'http_user' => 'admin',
					'http_password' => 'Phasellus imperdiet sapien sed justo elementum, quis maximuslpi65',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Invalid parameter "/1/http_password": value is too long.'
			],
			// Check web user name used for basic HTTP authentication .
			[
				'httptest' => [
					'name' => 'Api web with none http authentication but with http_user',
					'authentication' => '0',
					'http_user' => 'admin',
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					],
				],
				'expected_error' => 'Incorrect value for field "http_user": should be empty.'
			],
			[
				'httptest' => [
					'name' => 'Api web with long http_user',
					'authentication' => '1',
					'http_user' => 'Phasellus imperdiet sapien sed justo elementum, quis maximuslpi65',
				],
				'expected_error' => 'Invalid parameter "/1/http_user": value is too long.'
			],
			// Check web user name used for NTLM authentication .
			[
				'httptest' => [
					'name' => 'Api web with long http_user',
					'authentication' => '2',
					'http_user' => 'Phasellus imperdiet sapien sed justo elementum, quis maximuslpi65',
				],
				'expected_error' => 'Invalid parameter "/1/http_user": value is too long.'
			],
			// Check web proxy
			[
				'httptest' => [
					'name' => 'Api web with long http_proxy',
					'http_proxy' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
				],
				'expected_error' => 'Invalid parameter "/1/http_proxy": value is too long.'
			],
			// Check web retries
			[
				'httptest' => [
					'name' => 'Api web with empty retries',
					'retries' => '',
				],
				'expected_error' => 'Invalid parameter "/1/retries": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '☺',
				],
				'expected_error' => 'Invalid parameter "/1/retries": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '1.5',
				],
				'expected_error' => 'Invalid parameter "/1/retries": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '1s',
				],
				'expected_error' => 'Invalid parameter "/1/retries": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '-5',
				],
				'expected_error' => 'Invalid parameter "/1/retries": value must be one of 1-10.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '0',
				],
				'expected_error' => 'Invalid parameter "/1/retries": value must be one of 1-10.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong retries',
					'retries' => '11',
				],
				'expected_error' => 'Invalid parameter "/1/retries": value must be one of 1-10.'
			],
			// Check web ssl_cert_file
			[
				'httptest' => [
					'name' => 'Api web with long ssl_cert_file',
					'ssl_cert_file' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
				],
				'expected_error' => 'Invalid parameter "/1/ssl_cert_file": value is too long.'
			],
			// Check web ssl_key_file
			[
				'httptest' => [
					'name' => 'Api web with long ssl_key_file',
					'ssl_key_file' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
				],
				'expected_error' => 'Invalid parameter "/1/ssl_key_file": value is too long.'
			],
			// Check web ssl_key_password
			[
				'httptest' => [
					'name' => 'Api web with long ssl_key_password ',
					'ssl_key_password' => 'Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condimen256',
				],
				'expected_error' => 'Invalid parameter "/1/ssl_key_password": value is too long.'
			],
			// Check web status
			[
				'httptest' => [
					'name' => 'Api web with empty status',
					'status' => ''
				],
				'expected_error' => 'Invalid parameter "/1/status": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong status',
					'status' => '☺'
				],
				'expected_error' => 'Invalid parameter "/1/status": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong status',
					'status' => '0.0'
				],
				'expected_error' => 'Invalid parameter "/1/status": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong status',
					'status' => '2'
				],
				'expected_error' => 'Invalid parameter "/1/status": value must be one of 0, 1.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong status',
					'status' => '-1'
				],
				'expected_error' => 'Invalid parameter "/1/status": value must be one of 0, 1.'
			],
			// Check web variables.
			[
				'httptest' => [
					'name' => 'Api web with wrong variable',
					'variables' => [
						['name' => '☺']
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": is not enclosed in {} or is malformed.'
			],
			[
				'httptest' => [
					'name' => 'Api web with not enclosed variables name',
					'variables' => [
						[
							'name' => '{}',
							'value' => '☺',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": is not enclosed in {} or is malformed.'
			],
			[
				'httptest' => [
					'name' => 'Api web with not enclosed variables name',
					'variables' => [
						[
							'name' => '{test',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": is not enclosed in {} or is malformed.'
			],
			[
				'httptest' => [
					'name' => 'Api web with not enclosed variables name',
					'variables' => [
						[
							'name' => 'test}',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": is not enclosed in {} or is malformed.'
			],
			[
				'httptest' => [
					'name' => 'Api web with empty variables name',
					'variables' => [
						[
							'name' => '',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": cannot be empty.'
			],
			[
				'httptest' => [
					'name' => 'Api web with long variables name',
					'variables' => [
						[
							'name' => '{Phasellus imperdiet sapien sed justo elementum, quis maximus ipsum iaculis! Proin egestas, felis non efficitur molestie, nulla risus facilisis nisi, sed consectetur lorem mauris non arcu. Aliquam hendrerit massa vel metus maximus consequat. Sed condin256}',
							'value' => 'admin',
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/1/name": value is too long.'
			],
			[
				'httptest' => [
					'name' => 'Api web with identical variables names',
					'variables' => [
						[
							'name' => '{duplicate name}',
							'value' => 'admin',
						],
						[
							'name' => '{duplicate name}',
							'value' => 'admin',
						]
					],
					'steps' => [
						[
							'name' => 'Homepage',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'Invalid parameter "/1/variables/2": value (name)=({duplicate name}) already exists.'
			],
			// Check web verify_host
			[
				'httptest' => [
					'name' => 'Api web with empty verify_host',
					'verify_host' => ''
				],
				'expected_error' => 'Invalid parameter "/1/verify_host": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_host',
					'verify_host' => '☺',
				],
				'expected_error' => 'Invalid parameter "/1/verify_host": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_host',
					'verify_host' => '-1'
				],
				'expected_error' => 'Invalid parameter "/1/verify_host": value must be one of 0, 1.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_host',
					'verify_host' => '1.5'
				],
				'expected_error' => 'Invalid parameter "/1/verify_host": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_host',
					'verify_host' => '2'
				],
				'expected_error' => 'Invalid parameter "/1/verify_host": value must be one of 0, 1.'
			],
			// Check web verify_peer
			[
				'httptest' => [
					'name' => 'Api web with empty verify_peer',
					'verify_peer' => ''
				],
				'expected_error' => 'Invalid parameter "/1/verify_peer": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_peer',
					'verify_peer' => '☺',
				],
				'expected_error' => 'Invalid parameter "/1/verify_peer": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_peer',
					'verify_peer' => '-1'
				],
				'expected_error' => 'Invalid parameter "/1/verify_peer": value must be one of 0, 1.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_peer',
					'verify_peer' => '1.5'
				],
				'expected_error' => 'Invalid parameter "/1/verify_peer": an integer is expected.'
			],
			[
				'httptest' => [
					'name' => 'Api web with wrong verify_peer',
					'verify_peer' => '2'
				],
				'expected_error' => 'Invalid parameter "/1/verify_peer": value must be one of 0, 1.'
			]
		];
	}

	/**
	* @dataProvider web_properties
	*/
	public function testWebScenario_NotRequiredProperties($httptests, $expected_error) {
		$methods = ['httptest.create', 'httptest.update'];

		foreach ($methods as $method) {
			if ($method == 'httptest.create') {
				$httptests['hostid'] = '50009';
			}
			elseif ($method == 'httptest.update') {
				unset($httptests['hostid']);
				$httptests['httptestid'] = '15001';
				$httptests['name'] = 'Update '.$httptests['name'];
			}
			$result = $this->call($method, $httptests, $expected_error);

			if ($expected_error === null) {
				$dbResult = DBSelect('select * from httptest where httptestid='.
						zbx_dbstr($result['result']['httptestid'][0])
				);
				$dbRow = DBFetch($dbResult);
				$this->assertEquals($dbRow['name'], $httptests['name']);
			}
			else {
				$this->assertEquals(0, CDBHelper::getCount('select * from httptest where name='.zbx_dbstr($httptests['name'])));
			}
		}
	}

	public static function web_delete() {
		return [
			// Check web scenario id.
			[
				'httptest' => [''],
				'expected_error' => 'Invalid parameter "/1": a number is expected.'
			],
			[
				'httptest' => ['abc'],
				'expected_error' => 'Invalid parameter "/1": a number is expected.'
			],
			[
				'httptest' => ['1.1'],
				'expected_error' => 'Invalid parameter "/1": a number is expected.'
			],
			[
				'httptest' => ['123456'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'httptest' => ['15003', '15003'],
				'expected_error' => 'Invalid parameter "/2": value (15003) already exists.'
			],
			// Try to delete templated web scenario.
			[
				'httptest' => ['15007'],
				'expected_error' => 'Cannot delete templated web scenario "Api templated web scenario".'
			],
			[
				'httptest' => ['15007', '15003'],
				'expected_error' => 'Cannot delete templated web scenario "Api templated web scenario".'
			],
			// Successfully delete web scenario.
			[
				'httptest' => ['15003'],
				'expected_error' => null
			],
			[
				'httptest' => ['15004', '15005'],
				'expected_error' => null
			]
		];
	}

	/**
	* @dataProvider web_delete
	*/
	public function testWebScenario_Delete($httptests, $expected_error) {
		$result = $this->call('httptest.delete', $httptests, $expected_error);

		if ($expected_error === null) {
			foreach ($result['result']['httptestids'] as $id) {
				$this->assertEquals(0, CDBHelper::getCount('select * from httptest where httptestid='.zbx_dbstr($id)));
			}
		}
	}

	public static function web_user_permissions() {
		return [
			// Zabbix admin have read-write permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix admin with read-write permissions',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step as zabbix admin with read-write permissions',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => null
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix admin with read-write permissions',
					'httptestid' => '15001'
				],
				'expected_error' => null
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15010'],
				'expected_error' => null
			],
			// Zabbix admin have read permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix admin with read permissionss',
					'hostid' => '50012',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix admin with read permissionss',
					'httptestid' => '15008'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15008'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Zabbix admin have deny permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix admin with deny permissionss',
					'hostid' => '50014',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix admin with read permissionss',
					'httptestid' => '15009'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15009'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Zabbix admin have None permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix admin with none permissionss',
					'hostid' => '50010',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix admin with none permissionss',
					'httptestid' => '15006'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15006'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Zabbix user have read-write permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix user with read-write permissions',
					'hostid' => '50009',
					'steps' => [
						[
							'name' => 'API create step as zabbix user with read-write permissions',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => null
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix user with read-write permissions',
					'httptestid' => '15001'
				],
				'expected_error' => null
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => ['15011'],
				'expected_error' => null
			],
			// Zabbix user have read permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix user with read permissionss',
					'hostid' => '50012',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix user with read permissionss',
					'httptestid' => '15008'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15008'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Zabbix admin have deny permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix admin with deny permissionss',
					'hostid' => '50014',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix user with read permissionss',
					'httptestid' => '15009'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'httptest' => ['15009'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Zabbix user have None permissions to host.
			[
				'method' => 'httptest.create',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API create web as zabbix user with none permissionss',
					'hostid' => '50010',
					'steps' => [
						[
							'name' => 'API create step',
							'url' => 'http://zabbix.com',
							'no' => 0,
						]
					]
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.update',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => [
					'name' => 'API update web as zabbix user with none permissionss',
					'httptestid' => '15006'
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'method' => 'httptest.delete',
				'login' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'httptest' => ['15006'],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
		];
	}

	/**
	* @dataProvider web_user_permissions
	*/
	public function testWebScenario_UserPermissions($method, $login, $user, $expected_error) {
		$this->authorize($login['user'], $login['password']);
		$this->call($method, $user, $expected_error);
	}
}
