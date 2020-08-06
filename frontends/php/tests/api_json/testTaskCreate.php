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
 * @backup items
 */
class testTaskCreate extends CAPITest {

	public static function tasks() {
		return [
			[
				'task' => [
					'type' => '6',
					'itemids' => ['40068'],
					'flag' => true
				],
				'expected_error' => 'Invalid parameter "/": unexpected parameter "flag".'
			],
			// Check type validation
			[
				'task' => [
					'itemids' => ['40068']
				],
				'expected_error' => 'Invalid parameter "/": the parameter "type" is missing.'
			],
			[
				'task' => [
					'type' => '',
					'itemids' => ['40068']
				],
				'expected_error' => 'Invalid parameter "/type": an integer is expected.'
			],
			[
				'task' => [
					'type' => 'æų',
					'itemids' => ['40068']
				],
				'expected_error' => 'Invalid parameter "/type": an integer is expected.'
			],
			[
				'task' => [
					'type' => '1',
					'itemids' => ['40068']
				],
				'expected_error' => 'Invalid parameter "/type": value must be one of 6.'
			],
			// Check itemids validation
			[
				'task' => [
					'type' => '6'
				],
				'expected_error' => 'Invalid parameter "/": the parameter "itemids" is missing.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ''
				],
				'expected_error' => 'Invalid parameter "/itemids": an array is expected.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['']
				],
				'expected_error' => 'Invalid parameter "/itemids/1": a number is expected.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['123456']
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// One itemid correct, one wrong
			[
				'task' => [
					'type' => '6',
					'itemids' => ['40068', '']
				],
				'expected_error' => 'Invalid parameter "/itemids/2": a number is expected.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['40068', '123456']
				],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			// Host disabled (check item, LLD rule)
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90002']
				],
				'expected_error' => 'Cannot send request: host is not monitored.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90003']
				],
				'expected_error' => 'Cannot send request: host is not monitored.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['29179', '90002']
				],
				'expected_error' => 'Cannot send request: host is not monitored.'
			],
			// Item disabled
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90000']
				],
				'expected_error' => 'Cannot send request: item is disabled.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['29179', '90000']
				],
				'expected_error' => 'Cannot send request: item is disabled.'
			],
			// LLD rule disabled
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90001']
				],
				'expected_error' => 'Cannot send request: discovery rule is disabled.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['29179', '90001']
				],
				'expected_error' => 'Cannot send request: discovery rule is disabled.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['29207', '90001']
				],
				'expected_error' => 'Cannot send request: discovery rule is disabled.'
			],
			// Success item check now
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90004']
				],
				'expected_error' => null
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['90004', '29179']
				],
				'expected_error' => null
			],
			// Success LLD rule check now
			[
				'task' => [
					'type' => '6',
					'itemids' => ['29207']
				],
				'expected_error' => null
			],
			// Item (ID: 110001): Zabbix agent (active)
			[
				'task' => [
					'type' => '6',
					'itemids' => ['110001', '110002']
				],
				'expected_error' => 'Cannot send request: wrong item type.'
			],
			// Item (ID: 110002): Zabbix agent (passive)
			[
				'task' => [
					'type' => '6',
					'itemids' => ['110002']
				],
				'expected_error' => null
			],
			// Templated lld and items do not accept "Check now" command
			[
				'task' => [
					'type' => '6',
					'itemids' => ['110004']
				],
				'expected_error' => 'Cannot send request: host is not monitored.'
			],
			[
				'task' => [
					'type' => '6',
					'itemids' => ['110005']
				],
				'expected_error' => 'Cannot send request: host is not monitored.'
			]
		];
	}

	/**
	 * Test parameters validation, disabled host/item/lld rule and successful scenarios
	 *
	 * @dataProvider tasks
	 */
	public function testTaskCreate_CheckNow($task, $expected_error) {
		$sqlTask = 'select NULL from task_check_now';
		$oldHashTasks = CDBHelper::getHash($sqlTask);

		$result = $this->call('task.create', $task, $expected_error);

		if ($expected_error === null) {
			foreach ($result['result']['taskids'] as $key => $id) {
				$dbResult = DBSelect('select * from task_check_now where taskid='.zbx_dbstr($id));
				$dbRow = DBFetch($dbResult);
				$this->assertEquals($dbRow['itemid'], $task['itemids'][$key]);
			}
		}
		else {
			$this->assertEquals($oldHashTasks, CDBHelper::getHash($sqlTask));
		}
	}

	public static function types() {
		return [
			// Item type: Zabbix agent (active)
			[
				'method' => 'item.update',
				'item' => [
					'itemid' => '90004',
					'type' => '7'
				],
				'expected_error' => 'Cannot send request: wrong item type.'
			],
			// Item type: SNMP trap
			[
				'method' => 'item.update',
				'item' => [
					'itemid' => '90004',
					'type' => '17',
					'key_' => 'snmptrap.fallback',
					'interfaceid' => '99004'
				],
				'expected_error' => 'Cannot send request: wrong item type.'
			],
			// Item type: Zabbix trapper
			[
				'method' => 'item.update',
				'item' => [
					'itemid' => '90004',
					'type' => '2'
				],
				'expected_error' => 'Cannot send request: wrong item type.'
			],
			// Item type: Dependent item
			[
				'method' => 'item.update',
				'item' => [
					'itemid' => '90004',
					'type' => '18',
					'master_itemid' => '23287',
				],
				'expected_error' => 'Cannot send request: wrong item type.'
			],
			// LLD rule type: Zabbix agent (active)
			[
				'method' => 'discoveryrule.update',
				'lld' => [
					'itemid' => '90005',
					'type' => '7'
				],
				'expected_error' => 'Cannot send request: wrong discovery rule type.'
			],
			// LLD rule type: Zabbix trapper
			[
				'method' => 'discoveryrule.update',
				'lld' => [
					'itemid' => '90005',
					'type' => '2'
				],
				'expected_error' => 'Cannot send request: wrong discovery rule type.'
			]
		];
	}

	/**
	 * Test item/lld rule types that not allow "check now" functionality
	 *
	 * @dataProvider types
	 */
	public function testTaskCreate_DifferentItemTypes($method, $object, $expected_error) {
		$sqlTask = "select NULL from task_check_now";
		$oldHashTasks = CDBHelper::getHash($sqlTask);

		// Change item/LLD rule type to not allowed for check now
		$this->call($method, $object);

		// Create task for check now
		$task = [
			'type' => '6',
			'itemids' => [$object['itemid']],
		];

		$this->call('task.create', $task, $expected_error);
		$this->assertEquals($oldHashTasks, CDBHelper::getHash($sqlTask));
	}

	public static function user_permissions() {
		return [
			[
				'user' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'task' => [
						'type' => '6',
						'itemids' => ['23287']
					],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'user' => ['user' => 'zabbix-admin', 'password' => 'zabbix'],
				'task' => [
						'type' => '6',
						'itemids' => ['23279']
					],
				'expected_error' => 'No permissions to referred object or it does not exist!'
			],
			[
				'user' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'task' => [
						'type' => '6',
						'itemids' => ['23287']
					],
				'expected_error' => 'You do not have permission to perform this operation.'
			],
			[
				'user' => ['user' => 'zabbix-user', 'password' => 'zabbix'],
				'task' => [
						'type' => '6',
						'itemids' => ['23279']
					],
				'expected_error' => 'You do not have permission to perform this operation.'
			]
		];
	}

	/**
	 * Test user permissions on "check now" functionality
	 *
	 * @dataProvider user_permissions
	 */
	public function testTaskCreate_UserPermissions($user, $task, $expected_error) {
		$sqlTask = "select NULL from task_check_now";
		$oldHashTasks = CDBHelper::getHash($sqlTask);

		$this->authorize($user['user'], $user['password']);
		$this->call('task.create', $task, $expected_error);

		$this->assertEquals($oldHashTasks, CDBHelper::getHash($sqlTask));
	}
}
