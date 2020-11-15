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

require_once dirname(__FILE__).'/../include/CIntegrationTest.php';

/**
 * Test suite for items state change verification.
 *
 * @required-components server, agent
 * @configurationDataProvider serverConfigurationProvider
 * @hosts test_host
 * @backup history
 */
class testItemState extends CIntegrationTest {

	const REFRESH_ACT_CHKS_INTERVAL = 60;
	const PROCESS_ACT_CHKS_DELAY = 60;
	const LOG_LINE_WAIT_TIME	 = 30;
	const PSV_FILE_NAME = '/tmp/some_temp_file_psv';
	const ACT_FILE_NAME = '/tmp/some_temp_file_act';

	private static $hostid;
	private static $interfaceid;

	private static $items = [
		'zbx_psv_01' => [
			'key' => 'vfs.file.contents['.self::PSV_FILE_NAME.']',
			'type' => ITEM_TYPE_ZABBIX
		],
		'zbx_act_01' => [
			'key' => 'vfs.file.contents['.self::ACT_FILE_NAME.']',
			'type' => ITEM_TYPE_ZABBIX_ACTIVE
		]
	];

	private static $scenarios = [
		[
			'name' => 'zbx_psv_01',
			'delay_s' => 5,
			'refresh_unsupported' => 8,
			'after_sync' => false
		],
		[
			'name' => 'zbx_psv_01',
			'delay_s' => 8,
			'refresh_unsupported' => 5,
			'after_sync' => false
		],
		[
			'name' => 'zbx_psv_01',
			'delay_s' => 5,
			'refresh_unsupported' => 8,
			'after_sync' => true
		],
		[
			'name' => 'zbx_act_01',
			'delay_s' => 5,
			'refresh_unsupported' => 65
		]
	];

	/**
	 * @inheritdoc
	 */
	public function prepareData() {
		// Create host "test_host"
		$response = $this->call('host.create', [
			[
				'host' => 'test_host',
				'interfaces' => [
					'type' => 1,
					'main' => 1,
					'useip' => 1,
					'ip' => '127.0.0.1',
					'dns' => '',
					'port' => $this->getConfigurationValue(self::COMPONENT_AGENT, 'ListenPort')
				],
				'groups' => [['groupid' => 4]],
				'status' => HOST_STATUS_NOT_MONITORED
			]
		]);

		$this->assertArrayHasKey('hostids', $response['result']);
		$this->assertArrayHasKey(0, $response['result']['hostids']);
		self::$hostid = $response['result']['hostids'][0];

		// Get host interface ids.
		$response = $this->call('host.get', [
			'output' => ['host'],
			'hostids' => [self::$hostid],
			'selectInterfaces' => ['interfaceid']
		]);

		$this->assertArrayHasKey(0, $response['result']);
		$this->assertArrayHasKey('interfaces', $response['result'][0]);
		$this->assertArrayHasKey(0, $response['result'][0]['interfaces']);
		self::$interfaceid = $response['result'][0]['interfaces'][0]['interfaceid'];

		// Create items
		foreach (self::$items as $key => $item) {
			$items[] = [
				'name' => $key,
				'key_' => $item['key'],
				'type' => $item['type'],
				'hostid' => self::$hostid,
				'interfaceid' => self::$interfaceid,
				'value_type' => ITEM_VALUE_TYPE_UINT64,
				'delay' => '1s',
				'status' => ITEM_STATUS_DISABLED
			];
		}

		$response = $this->call('item.create', $items);
		$this->assertArrayHasKey('itemids', $response['result']);
		$this->assertEquals(count($items), count($response['result']['itemids']));
		$itemids = $response['result']['itemids'];
		$id = 0;

		foreach (self::$items as &$item) {
			$item['itemid'] = $itemids[$id++];
		}

		$this->assertTrue(@file_put_contents(self::PSV_FILE_NAME, '1') !== false);
		$this->assertTrue(@file_put_contents(self::ACT_FILE_NAME, '1') !== false);

		return true;
	}

	/**
	 * Component configuration provider for agent related tests.
	 *
	 * @return array
	 */
	public function serverConfigurationProvider() {
		return [
			self::COMPONENT_SERVER => [
				'DebugLevel' => 4,
				'LogFileSize' => 20
			],
			self::COMPONENT_AGENT => [
				'Hostname' => 'test_host',
				'ServerActive' => '127.0.0.1:'.self::getConfigurationValue(self::COMPONENT_SERVER, 'ListenPort'),
				'RefreshActiveChecks' => self::REFRESH_ACT_CHKS_INTERVAL,
				'BufferSend' => 1
			]
		];
	}

	/**
	 * Get timestamp of log last line.
	 *
	 * @param string  $line       log line
	 *
	 * @return integer|false
	 */
	protected function getTimestamp($line) {
		$matches = [];
		$regex = '/\d+:(\d+:\d+.\d+)/';

		if (preg_match($regex, $line, $matches) === 1) {
			if ($matches[1]) {
				$ts = DateTime::createFromFormat('Ymd:Gis.u', $matches[1]);
				return $ts->format('U');
			}
		}

		return false;
	}

	/**
	 * Wait until line is present in log.
	 *
	 * @param string       $component     name of the component
	 * @param string|array $lines         line(s) to look for
	 * @param integer      $iterations    iteration count
	 *
	 * @return integer
	 *
	 * @throws Exception    on failed wait or if not able to retrieve timestamp
	 */
	protected function getLogLineTimestamp($component, $lines, $iterations = null) {
		if ($iterations === null) {
			$iterations = self::LOG_LINE_WAIT_TIME;
		}

		for ($r = 0; $r < $iterations; $r++) {
			$log_content = CLogHelper::readLogUntil(self::getLogPath($component), $lines);

			if ($log_content !== null) {
				$log_content = $this->getTimestamp(strrchr(rtrim($log_content, "\n"), "\n"));

				if ($log_content === false) {
					throw new Exception('Failed to get timestamp of the log line');
				}

				return $log_content;
			}

			sleep(1);
		}

		if (is_array($lines)) {
			$quoted = [];
			foreach ($lines as $line) {
				$quoted[] = '"'.$line.'"';
			}

			$description = 'any of the lines ['.implode(', ', $quoted).']';
		}
		else {
			$description = 'line "'.$lines.'"';
		}

		throw new Exception('Failed to wait for '.$description.' to be present in '.$component.' log file.');
	}

	/**
	 * Routine to prepare item.
	 */
	protected function prepareItem($itemid, $delay) {
		// Disable all items
		foreach (self::$items as $item) {
			if ($item['itemid'] == $itemid) {
				$items[] = [
					'itemid' => $itemid,
					'status' => ITEM_STATUS_ACTIVE,
					'delay' => $delay,
				];
			} else {
				$items[] = [
					'itemid' => $item['itemid'],
					'status' => ITEM_STATUS_DISABLED
				];
			}
		}

		$response = $this->call('item.update', $items);
		$this->assertArrayHasKey('itemids', $response['result']);
		$this->assertEquals(count($items), count($response['result']['itemids']));
		$this->reloadConfigurationCache();

		// Clear log
		$this->clearLog(self::COMPONENT_SERVER);
	}

	/**
	 * Routine to check item state and intervals.
	 */
	protected function checkItemStatePassive($scenario, $state) {
		$delay = ($scenario['after_sync'] === true || $state === ITEM_STATE_NORMAL)
				? $scenario['delay_s']
				: $scenario['refresh_unsupported'];

		$wait = $delay + self::LOG_LINE_WAIT_TIME;
		$key = self::$items[$scenario['name']]['key'];

		// Wait for item to be checked
		$first_check = $this->getLogLineTimestamp(self::COMPONENT_SERVER, ["In get_value() key:'".$key."'"], $wait);

		// Check item state
		sleep(1);

		$response = $this->call('item.get', [
			'itemids' => self::$items[$scenario['name']]['itemid'],
			'output' => ['state']
		]);

		$this->assertEquals($state, $response['result'][0]['state'], 'Unexpected item state='.
				$response['result'][0]['state'].' (expected='.$state.')'
		);

		// Verify item checks intervals
		$check = $this->getLogLineTimestamp(self::COMPONENT_SERVER, ["In get_value() key:'".$key."'"], $wait);
		$this->assertTrue($check <= $first_check + $delay + 1);

		$next_check = $this->getLogLineTimestamp(self::COMPONENT_SERVER, ["In get_value() key:'".$key."'"], $wait);
		$this->assertTrue($next_check <= $check + $delay + 1 && $next_check >= $check + $delay - 1);
	}

	/**
	 * Routine to check item state and intervals (active agent items).
	 */
	protected function checkItemStateActive($scenario, $state, &$refresh) {
		$wait = max($scenario['delay_s'], $scenario['refresh_unsupported'], self::REFRESH_ACT_CHKS_INTERVAL) +
				self::PROCESS_ACT_CHKS_DELAY + self::LOG_LINE_WAIT_TIME;
		$key = self::$items[$scenario['name']]['key'];

		// Wait for item to be checked
		$check = $this->getLogLineTimestamp(self::COMPONENT_SERVER,
				[',"data":[{"host":"test_host","key":"'.$key.'","value":"'], $wait
		);

		// Update last refresh timestamp
		while ($check > $refresh + self::REFRESH_ACT_CHKS_INTERVAL) {
			$refresh += self::REFRESH_ACT_CHKS_INTERVAL;
		}

		// Check item state and read update interval
		sleep(1);

		$response = $this->call('item.get', [
			'itemids' => self::$items[$scenario['name']]['itemid'],
			'output' => ['state']
		]);

		$this->assertEquals($state, $response['result'][0]['state'],
				'Unexpected item state='.$response['result'][0]['state'].' (expected='.$state.')'
		);

		// Verify item checks intervals
		$next_check = $this->getLogLineTimestamp(self::COMPONENT_SERVER,
				[',"data":[{"host":"test_host","key":"'.$key.'","value":"'], $wait
		);

		while ($next_check > $refresh + self::REFRESH_ACT_CHKS_INTERVAL) {
			$refresh += self::REFRESH_ACT_CHKS_INTERVAL;
		}

		if ($state === ITEM_STATE_NOTSUPPORTED) {
			$exp_nextcheck_item = $check + $scenario['refresh_unsupported'];
			while ($refresh < $exp_nextcheck_item) {
				$refresh += self::REFRESH_ACT_CHKS_INTERVAL;
			}

			$exp_nextcheck_process = $check;
			while ($exp_nextcheck_process < $refresh) {
				$exp_nextcheck_process += self::PROCESS_ACT_CHKS_DELAY;
			}

			if ($scenario['delay_s'] > self::PROCESS_ACT_CHKS_DELAY) {
				$exp_nextcheck_item = $check;

				while ($exp_nextcheck_item < $exp_nextcheck_process) {
					$exp_nextcheck_item += $scenario['delay_s'];
				}
			} else {
				$exp_nextcheck_item = $exp_nextcheck_process;
			}

			$this->assertTrue($next_check <= $exp_nextcheck_item + 1 && $next_check >= $exp_nextcheck_item - 1);
		} else {
			$this->assertTrue($next_check <= $check + $scenario['delay_s'] + 1
					&& $next_check >= $check + $scenario['delay_s'] - 1
			);
		}

		return $refresh;
	}

	/**
	 * Function to get scenarios by type.
	 *
	 * @param integer      $type     type
	 *
	 * @return array
	 */
	protected function getScenariosByType($type) {
		$scenarios = [];

		foreach (self::$scenarios as $scenario) {
			if (self::$items[$scenario['name']]['type'] === $type) {
				$scenarios[] = [$scenario];
			}
		}

		return $scenarios;
	}

	/**
	 * Data provider (passive checks).
	 *
	 * @return array
	 */
	public function getDataPassive() {
		return $this->getScenariosByType(ITEM_TYPE_ZABBIX);
	}

	/**
	 * Data provider (active checks).
	 *
	 * @return array
	 */
	public function getDataActive() {
		return $this->getScenariosByType(ITEM_TYPE_ZABBIX_ACTIVE);
	}

	/**
	 * Test if item becomes supported/not supported within expected time span (passive checks).
	 *
	 * @dataProvider getDataPassive
	 */
	public function testItemState_checkPassive($data) {
		// Set refresh unsupported items interval
		$this->assertTrue(DBexecute('UPDATE config SET refresh_unsupported='.
				zbx_dbstr($data['refresh_unsupported'].'s').' WHERE configid=1')
		);

		// Prepare item
		$this->prepareItem(self::$items[$data['name']]['itemid'], $data['delay_s'].'s');

		// Check item state and intervals
		$this->checkItemStatePassive($data, ITEM_STATE_NORMAL);

		// Make item not supported
		if ($data['after_sync'] === true) {
			$this->assertTrue(@file_put_contents(self::PSV_FILE_NAME, 'text') !== false);
		} else {
			$this->assertTrue(@unlink(self::PSV_FILE_NAME) !== false);
		}

		// Check item state and intervals
		$this->checkItemStatePassive($data, ITEM_STATE_NOTSUPPORTED);

		// Make item supported
		$this->assertTrue(@file_put_contents(self::PSV_FILE_NAME, '1') !== false);

		// Check item state and intervals
		$this->checkItemStatePassive($data, ITEM_STATE_NORMAL);
	}

	/**
	 * Test if item becomes supported/not supported within expected time span (active checks).
	 *
	 * @dataProvider getDataActive
	 */
	public function testItemState_checkActive($data) {
		// Set refresh unsupported items interval
		$this->assertTrue(DBexecute('UPDATE config SET refresh_unsupported='.
				zbx_dbstr($data['refresh_unsupported'].'s').' WHERE configid=1')
		);

		// Prepare item
		$this->prepareItem(self::$items[$data['name']]['itemid'], $data['delay_s'].'s');

		// Wait for the refresh active checks
		$refresh_active = $this->getLogLineTimestamp(self::COMPONENT_SERVER,
				['trapper got \'{"request":"active checks","host":"test_host"'],
				self::REFRESH_ACT_CHKS_INTERVAL + self::LOG_LINE_WAIT_TIME
		);

		// Check item state and intervals
		$this->checkItemStateActive($data, ITEM_STATE_NORMAL, $refresh_active);

		// Make item not supported
		$this->assertTrue(@unlink(self::ACT_FILE_NAME) !== false);

		// Check item state and intervals
		$this->checkItemStateActive($data, ITEM_STATE_NOTSUPPORTED, $refresh_active);

		// Make item supported
		$this->assertTrue(@file_put_contents(self::ACT_FILE_NAME, '1') !== false);

		// Check item state and intervals
		$this->checkItemStateActive($data, ITEM_STATE_NORMAL, $refresh_active);
	}
}
