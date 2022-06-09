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


/**
 * Converter for converting import data from 3.4 to 4.0
 */
class C34ImportConverter extends CConverter {

	public function convert($data) {
		$data['zabbix_export']['version'] = '4.0';

		if (array_key_exists('hosts', $data['zabbix_export'])) {
			$data['zabbix_export']['hosts'] = $this->convertHosts($data['zabbix_export']['hosts']);
		}

		if (array_key_exists('templates', $data['zabbix_export'])) {
			$data['zabbix_export']['templates'] = $this->convertHosts($data['zabbix_export']['templates']);
		}

		if (array_key_exists('maps', $data['zabbix_export'])) {
			$data['zabbix_export']['maps'] = $this->convertMaps($data['zabbix_export']['maps']);
		}

		return $data;
	}

	/**
	 * Convert hosts.
	 *
	 * @param array $hosts
	 *
	 * @return array
	 */
	protected function convertHosts(array $hosts) {
		foreach ($hosts as &$host) {
			if (array_key_exists('discovery_rules', $host)) {
				$host['discovery_rules'] = $this->convertDiscoveryRules($host['discovery_rules']);
			}
			if (array_key_exists('items', $host)) {
				$host['items'] = $this->convertItems($host['items']);
			}
		}
		unset($host);

		return $hosts;
	}

	/**
	 * Convert item elements.
	 *
	 * @param array  $items
	 *
	 * @return array
	 */
	protected function convertItems(array $items) {
		$default = $this->getItemDefaultFields();

		foreach ($items as &$item) {
			$item += $default;
		}
		unset($item);

		return $items;
	}

	/**
	 * Convert item prototype elements.
	 *
	 * @param array  $item_prototypes
	 *
	 * @return array
	 */
	protected function convertItemPrototypes(array $item_prototypes) {
		$default = $this->getItemDefaultFields();

		foreach ($item_prototypes as &$item_prototype) {
			$item_prototype['master_item'] = $item_prototype['master_item_prototype'];
			unset($item_prototype['master_item_prototype']);

			$item_prototype += $default;
		}
		unset($item_prototype);

		return $item_prototypes;
	}

	/**
	 * Convert discovery rule elements.
	 *
	 * @param array $discovery_rules
	 *
	 * @return array
	 */
	protected function convertDiscoveryRules(array $discovery_rules) {
		$default = $this->getDiscoveryRuleDefaultFields();

		foreach ($discovery_rules as &$discovery_rule) {
			$discovery_rule['item_prototypes'] = $this->convertItemPrototypes($discovery_rule['item_prototypes']);
			$discovery_rule += $default;
		}
		unset($discovery_rule);

		return $discovery_rules;
	}

	/**
	 * Convert maps.
	 *
	 * @param array $maps
	 *
	 * @return array
	 */
	protected function convertMaps(array $maps) {
		$default = [
			'show_suppressed' => DB::getDefault('sysmaps', 'show_suppressed')
		];

		foreach ($maps as &$map) {
			$map += $default;
		}
		unset($map);

		return $maps;
	}

	/**
	 * Return associative array of item and item prototype default fields.
	 *
	 * @return array
	 */
	protected function getItemDefaultFields() {
		$default = array_intersect_key(DB::getDefaults('items'),
			array_fill_keys([
				'timeout', 'url', 'posts', 'status_codes', 'follow_redirects', 'post_type', 'http_proxy',
				'retrieve_mode', 'request_method', 'output_format', 'ssl_cert_file', 'ssl_key_file', 'ssl_key_password',
				'verify_peer', 'verify_host', 'allow_traps'
			], ''
		));
		$default['query_fields'] = [];
		$default['headers'] = [];

		return $default;
	}

	/**
	 * Return associative array of LLD rule default fields.
	 *
	 * @return array
	 */
	protected function getDiscoveryRuleDefaultFields() {
		$default = array_intersect_key(DB::getDefaults('items'),
			array_fill_keys([
				'timeout', 'url', 'posts', 'status_codes', 'follow_redirects', 'post_type', 'http_proxy',
				'retrieve_mode', 'request_method', 'ssl_cert_file', 'ssl_key_file', 'ssl_key_password', 'verify_peer',
				'verify_host', 'allow_traps'
			], ''
		));
		$default['query_fields'] = [];
		$default['headers'] = [];

		return $default;
	}
}
