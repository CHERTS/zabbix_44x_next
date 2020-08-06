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
 * Test suite for data collection using both active and passive agents.
 *
 * @backup history
 */
class testDataCollection extends CIntegrationTest {

	private static $hostids = [];
	private static $itemids = [];

	/**
	 * @inheritdoc
	 */
	public function prepareData() {
		// Create proxy "proxy".
		$response = $this->call('proxy.create', [
			'host' => 'proxy',
			'status' => HOST_STATUS_PROXY_ACTIVE
		]);

		$this->assertArrayHasKey('proxyids', $response['result']);
		$this->assertArrayHasKey(0, $response['result']['proxyids']);
		$proxyid = $response['result']['proxyids'][0];

		// Create host "agent", "custom_agent" and "proxy agent".
		$interfaces = [
			[
				'type' => 1,
				'main' => 1,
				'useip' => 1,
				'ip' => '127.0.0.1',
				'dns' => '',
				'port' => $this->getConfigurationValue(self::COMPONENT_AGENT, 'ListenPort')
			]
		];

		$groups = [
			[
				'groupid' => 4
			]
		];

		$response = $this->call('host.create', [
			[
				'host' => 'agent',
				'interfaces' => $interfaces,
				'groups' => $groups,
				'status' => HOST_STATUS_NOT_MONITORED
			],
			[
				'host' => 'custom_agent',
				'interfaces' => $interfaces,
				'groups' => $groups,
				'status' => HOST_STATUS_NOT_MONITORED
			],
			[
				'host' => 'proxy_agent',
				'interfaces' => $interfaces,
				'groups' => $groups,
				'proxy_hostid' => $proxyid,
				'status' => HOST_STATUS_NOT_MONITORED
			]
		]);

		$this->assertArrayHasKey('hostids', $response['result']);
		foreach (['agent', 'custom_agent', 'proxy_agent'] as $i => $name) {
			$this->assertArrayHasKey($i, $response['result']['hostids']);
			self::$hostids[$name] = $response['result']['hostids'][$i];
		}

		// Get host interface ids.
		$response = $this->call('host.get', [
			'output' => ['host'],
			'hostids' => array_values(self::$hostids),
			'selectInterfaces' => ['interfaceid']
		]);

		$interfaceids = [];
		foreach ($response['result'] as $host) {
			$interfaceids[$host['host']] = $host['interfaces'][0]['interfaceid'];
		}

		// Create items.
		$response = $this->call('item.create', [
			[
				'hostid' => self::$hostids['agent'],
				'name' => 'Agent ping',
				'key_' => 'agent.ping',
				'type' => ITEM_TYPE_ZABBIX_ACTIVE,
				'value_type' => ITEM_VALUE_TYPE_UINT64,
				'delay' => '1s',
				'interfaceid' => $interfaceids['agent']
			],
			[
				'hostid' => self::$hostids['agent'],
				'name' => 'Agent hostname',
				'key_' => 'agent.hostname',
				'type' => ITEM_TYPE_ZABBIX,
				'value_type' => ITEM_VALUE_TYPE_TEXT,
				'delay' => '1s',
				'interfaceid' => $interfaceids['agent']

			],
			[
				'hostid' => self::$hostids['custom_agent'],
				'name' => 'Custom metric 1',
				'key_' => 'custom.metric',
				'type' => ITEM_TYPE_ZABBIX_ACTIVE,
				'value_type' => ITEM_VALUE_TYPE_TEXT,
				'delay' => '5s',
				'interfaceid' => $interfaceids['custom_agent']
			],
			[
				'hostid' => self::$hostids['custom_agent'],
				'name' => 'Custom metric 2',
				'key_' => 'custom.metric[custom]',
				'type' => ITEM_TYPE_ZABBIX_ACTIVE,
				'value_type' => ITEM_VALUE_TYPE_TEXT,
				'delay' => '10s',
				'interfaceid' => $interfaceids['custom_agent']
			],
			[
				'hostid' => self::$hostids['proxy_agent'],
				'name' => 'Agent ping',
				'key_' => 'agent.ping',
				'type' => ITEM_TYPE_ZABBIX_ACTIVE,
				'value_type' => ITEM_VALUE_TYPE_UINT64,
				'delay' => '1s',
				'interfaceid' => $interfaceids['proxy_agent']
			],
			[
				'hostid' => self::$hostids['proxy_agent'],
				'name' => 'Agent hostname',
				'key_' => 'agent.hostname',
				'type' => ITEM_TYPE_ZABBIX,
				'value_type' => ITEM_VALUE_TYPE_TEXT,
				'delay' => '1s',
				'interfaceid' => $interfaceids['proxy_agent']
			]
		]);

		$this->assertArrayHasKey('itemids', $response['result']);
		$this->assertEquals(6, count($response['result']['itemids']));

		$items = [
			'agent:agent.ping',
			'agent:agent.hostname',
			'custom_agent:custom.metric',
			'custom_agent:custom.metric[custom]',
			'proxy_agent:agent.ping',
			'proxy_agent:agent.hostname'
		];

		self::$itemids = [];
		foreach ($items as $i => $name) {
			self::$itemids[$name] = $response['result']['itemids'][$i];
		}

		return true;
	}

	/**
	 * Component configuration provider for agent related tests.
	 *
	 * @return array
	 */
	public function agentConfigurationProvider() {
		return [
			self::COMPONENT_SERVER => [
				'UnreachablePeriod'	=> 5,
				'UnavailableDelay'	=> 5,
				'UnreachableDelay'	=> 1
			],
			self::COMPONENT_AGENT => [
				'Hostname'		=> 'agent',
				'ServerActive'	=> '127.0.0.1'
			]
		];
	}

	/**
	 * Test if server will disable agent checks if agent is not accessible.
	 *
	 * @required-components server
	 * @configurationDataProvider agentConfigurationProvider
	 * @hosts agent
	 */
	public function testDataCollection_checkHostAvailability() {
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER,
				'temporarily disabling Zabbix agent checks on host "agent": host unavailable'
		);

		$data = $this->call('host.get', [
			'hostids'	=> self::$hostids['agent'],
			'output'	=> ['available']
		]);

		$this->assertTrue(is_array($data['result']));
		$this->assertEquals(1, count($data['result']));
		$this->assertEquals(HOST_AVAILABLE_FALSE, $data['result'][0]['available']);
	}

	/**
	 * Test if both active and passive agent checks are processed.
	 *
	 * @required-components server, agent
	 * @configurationDataProvider agentConfigurationProvider
	 * @hosts agent
	 */
	public function testDataCollection_checkAgentData() {
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, [
				'enabling Zabbix agent checks on host "agent": host became available',
				'resuming Zabbix agent checks on host "agent": connection restored'
		]);

		$passive_data = $this->call('history.get', [
			'itemids'	=> self::$itemids['agent:agent.ping'],
			'history'	=> ITEM_VALUE_TYPE_UINT64
		]);

		foreach ($passive_data['result'] as $item) {
			$this->assertEquals(1, $item['value']);
		}

		// Retrieve history data from API as soon it is available.
		$active_data = $this->callUntilDataIsPresent('history.get', [
			'itemids'	=> self::$itemids['agent:agent.hostname'],
			'history'	=> ITEM_VALUE_TYPE_TEXT
		]);

		foreach ($active_data['result'] as $item) {
			$this->assertEquals('agent', $item['value']);
		}
	}

	/**
	 * Test if custom active checks are processed.
	 *
	 * @required-components server
	 * @hosts custom_agent
	 */
	public function testDataCollection_checkCustomActiveChecks() {
		$host = 'custom_agent';
		$items = [];

		// Retrieve item data from API.
		$response = $this->call('item.get', [
			'hostids'	=> self::$hostids['custom_agent'],
			'output'	=> ['itemid', 'name', 'key_', 'type', 'value_type']
		]);

		foreach ($response['result'] as $item) {
			if ($item['type'] != ITEM_TYPE_ZABBIX_ACTIVE) {
				continue;
			}

			$items[$item['key_']] = $item;
		}

		$values = [];
		$clock = time() - 1;

		$checks = $this->getActiveAgentChecks($host);
		foreach ($checks as $i => $check) {
			$matches = null;
			$value = (preg_match('/^.*\[(.*)\]$/', $check['key'], $matches) === 1) ? $matches[1] : microtime();

			$this->assertArrayHasKey($check['key'], $items);

			$values[$items[$check['key']]['itemid']] = [
				'host' => $host,
				'key' => $check['key'],
				'value' => $value,
				'clock' => $clock,
				'ns' => $i
			];

			unset($items[$check['key']]);
		}

		$this->assertEmpty($items);
		$this->sendAgentValues(array_values($values));

		// Retrieve history data from API as soon it is available.
		$data = $this->callUntilDataIsPresent('history.get', [
			'itemids'	=> array_keys($values),
			'history'	=> ITEM_VALUE_TYPE_TEXT
		]);

		foreach ($data['result'] as $item) {
			$value = $values[$item['itemid']];

			foreach (['value', 'clock', 'ns'] as $field) {
				$this->assertEquals($value[$field], $item[$field]);
			}
		}
	}

	/**
	 * Component configuration provider for proxy related tests.
	 *
	 * @return array
	 */
	public function proxyConfigurationProvider() {
		return array_merge($this->agentConfigurationProvider(), [
			self::COMPONENT_AGENT => [
				'Hostname'		=> 'proxy_agent',
				'ServerActive'	=> '127.0.0.1:'.self::getConfigurationValue(self::COMPONENT_PROXY, 'ListenPort')
			],
			self::COMPONENT_PROXY => [
				'UnreachablePeriod'	=> 5,
				'UnavailableDelay'	=> 5,
				'UnreachableDelay'	=> 1,
				'Hostname'			=> 'proxy',
				'ServerPort'		=> self::getConfigurationValue(self::COMPONENT_SERVER, 'ListenPort')
			]
		]);
	}

	/**
	 * Test if both active and passive agent checks are processed.
	 *
	 * @required-components server, proxy, agent
	 * @configurationDataProvider proxyConfigurationProvider
	 * @hosts proxy_agent
	 */
	public function testDataCollection_checkProxyData() {
		$this->waitForLogLineToBePresent(self::COMPONENT_SERVER, 'sending configuration data to proxy "proxy"');
		$this->waitForLogLineToBePresent(self::COMPONENT_PROXY, 'received configuration data from server');
		$this->waitForLogLineToBePresent(self::COMPONENT_PROXY, [
				'enabling Zabbix agent checks on host "proxy_agent": host became available',
				'resuming Zabbix agent checks on host "proxy_agent": connection restored'
		]);

		$passive_data = $this->call('history.get', [
			'itemids'	=> self::$itemids['proxy_agent:agent.ping'],
			'history'	=> ITEM_VALUE_TYPE_UINT64
		]);

		foreach ($passive_data['result'] as $item) {
			$this->assertEquals(1, $item['value']);
		}

		// Retrieve history data from API as soon it is available.
		$active_data = $this->callUntilDataIsPresent('history.get', [
			'itemids'	=> self::$itemids['proxy_agent:agent.hostname'],
			'history'	=> ITEM_VALUE_TYPE_TEXT
		]);

		foreach ($active_data['result'] as $item) {
			$this->assertEquals('proxy_agent', $item['value']);
		}
	}
}
