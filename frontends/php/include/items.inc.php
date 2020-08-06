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


/**
 * Convert windows events type constant in to the string representation
 *
 * @param int $logtype
 * @return string
 */
function get_item_logtype_description($logtype) {
	switch ($logtype) {
		case ITEM_LOGTYPE_INFORMATION:
			return _('Information');
		case ITEM_LOGTYPE_WARNING:
			return _('Warning');
		case ITEM_LOGTYPE_ERROR:
			return _('Error');
		case ITEM_LOGTYPE_FAILURE_AUDIT:
			return _('Failure Audit');
		case ITEM_LOGTYPE_SUCCESS_AUDIT:
			return _('Success Audit');
		case ITEM_LOGTYPE_CRITICAL:
			return _('Critical');
		case ITEM_LOGTYPE_VERBOSE:
			return _('Verbose');
		default:
			return _('Unknown');
	}
}

/**
 * Convert windows events type constant in to the CSS style name
 *
 * @param int $logtype
 * @return string
 */
function get_item_logtype_style($logtype) {
	switch ($logtype) {
		case ITEM_LOGTYPE_INFORMATION:
		case ITEM_LOGTYPE_SUCCESS_AUDIT:
		case ITEM_LOGTYPE_VERBOSE:
			return ZBX_STYLE_LOG_INFO_BG;

		case ITEM_LOGTYPE_WARNING:
			return ZBX_STYLE_LOG_WARNING_BG;

		case ITEM_LOGTYPE_ERROR:
		case ITEM_LOGTYPE_FAILURE_AUDIT:
			return ZBX_STYLE_LOG_HIGH_BG;

		case ITEM_LOGTYPE_CRITICAL:
			return ZBX_STYLE_LOG_DISASTER_BG;

		default:
			return ZBX_STYLE_LOG_NA_BG;
	}
}

/**
 * Get item type string name by item type number, or array of all item types if null passed.
 *
 * @param int|null $type
 *
 * @return array|string
 */
function item_type2str($type = null) {
	$types = [
		ITEM_TYPE_ZABBIX => _('Zabbix agent'),
		ITEM_TYPE_ZABBIX_ACTIVE => _('Zabbix agent (active)'),
		ITEM_TYPE_SIMPLE => _('Simple check'),
		ITEM_TYPE_SNMPV1 => _('SNMPv1 agent'),
		ITEM_TYPE_SNMPV2C => _('SNMPv2 agent'),
		ITEM_TYPE_SNMPV3 => _('SNMPv3 agent'),
		ITEM_TYPE_SNMPTRAP => _('SNMP trap'),
		ITEM_TYPE_INTERNAL => _('Zabbix internal'),
		ITEM_TYPE_TRAPPER => _('Zabbix trapper'),
		ITEM_TYPE_AGGREGATE => _('Zabbix aggregate'),
		ITEM_TYPE_EXTERNAL => _('External check'),
		ITEM_TYPE_DB_MONITOR => _('Database monitor'),
		ITEM_TYPE_HTTPAGENT => _('HTTP agent'),
		ITEM_TYPE_IPMI => _('IPMI agent'),
		ITEM_TYPE_SSH => _('SSH agent'),
		ITEM_TYPE_TELNET => _('TELNET agent'),
		ITEM_TYPE_JMX => _('JMX agent'),
		ITEM_TYPE_CALCULATED => _('Calculated'),
		ITEM_TYPE_HTTPTEST => _('Web monitoring'),
		ITEM_TYPE_DEPENDENT => _('Dependent item')
	];

	if ($type === null) {
		return $types;
	}

	return array_key_exists($type, $types) ? $types[$type] : _('Unknown');
}

/**
 * Returns human readable an item value type
 *
 * @param int $valueType
 *
 * @return string
 */
function itemValueTypeString($valueType) {
	switch ($valueType) {
		case ITEM_VALUE_TYPE_UINT64:
			return _('Numeric (unsigned)');
		case ITEM_VALUE_TYPE_FLOAT:
			return _('Numeric (float)');
		case ITEM_VALUE_TYPE_STR:
			return _('Character');
		case ITEM_VALUE_TYPE_LOG:
			return _('Log');
		case ITEM_VALUE_TYPE_TEXT:
			return _('Text');
	}
	return _('Unknown');
}

function item_status2str($type = null) {
	if (is_null($type)) {
		return [ITEM_STATUS_ACTIVE => _('Enabled'), ITEM_STATUS_DISABLED => _('Disabled')];
	}

	return ($type == ITEM_STATUS_ACTIVE) ? _('Enabled') : _('Disabled');
}

/**
 * Returns the names of supported item states.
 *
 * If the $state parameter is passed, returns the name of the specific state, otherwise - returns an array of all
 * supported states.
 *
 * @param string $state
 *
 * @return array|string
 */
function itemState($state = null) {
	$states = [
		ITEM_STATE_NORMAL => _('Normal'),
		ITEM_STATE_NOTSUPPORTED => _('Not supported')
	];

	if ($state === null) {
		return $states;
	}
	elseif (isset($states[$state])) {
		return $states[$state];
	}
	else {
		return _('Unknown');
	}
}

/**
 * Returns the text indicating the items status and state. If the $state parameter is not given, only the status of
 * the item will be taken into account.
 *
 * @param int $status
 * @param int $state
 *
 * @return string
 */
function itemIndicator($status, $state = null) {
	if ($status == ITEM_STATUS_ACTIVE) {
		return ($state == ITEM_STATE_NOTSUPPORTED) ? _('Not supported') : _('Enabled');
	}

	return _('Disabled');
}

/**
 * Returns the CSS class for the items status and state indicator. If the $state parameter is not given, only the status of
 * the item will be taken into account.
 *
 * @param int $status
 * @param int $state
 *
 * @return string
 */
function itemIndicatorStyle($status, $state = null) {
	if ($status == ITEM_STATUS_ACTIVE) {
		return ($state == ITEM_STATE_NOTSUPPORTED) ?
			ZBX_STYLE_GREY :
			ZBX_STYLE_GREEN;
	}

	return ZBX_STYLE_RED;
}

/**
 * Order items by keep history.
 *
 * @param array  $items
 * @param string $items['history']
 * @param string $sortorder
 */
function orderItemsByHistory(array &$items, $sortorder){
	$simple_interval_parser = new CSimpleIntervalParser();

	foreach ($items as &$item) {
		$item['history_sort'] = ($simple_interval_parser->parse($item['history']) == CParser::PARSE_SUCCESS)
			? timeUnitToSeconds($item['history'])
			: $item['history'];
	}
	unset($item);

	order_result($items, 'history_sort', $sortorder);

	foreach ($items as &$item) {
		unset($item['history_sort']);
	}
	unset($item);
}

/**
 * Order items by keep trends.
 *
 * @param array  $items
 * @param int    $items['value_type']
 * @param string $items['trends']
 * @param string $sortorder
 */
function orderItemsByTrends(array &$items, $sortorder){
	$simple_interval_parser = new CSimpleIntervalParser();

	foreach ($items as &$item) {
		if (in_array($item['value_type'], [ITEM_VALUE_TYPE_STR, ITEM_VALUE_TYPE_LOG, ITEM_VALUE_TYPE_TEXT])) {
			$item['trends_sort'] = '';
		}
		else {
			$item['trends_sort'] = ($simple_interval_parser->parse($item['trends']) == CParser::PARSE_SUCCESS)
				? timeUnitToSeconds($item['trends'])
				: $item['trends'];
		}
	}
	unset($item);

	order_result($items, 'trends_sort', $sortorder);

	foreach ($items as &$item) {
		unset($item['trends_sort']);
	}
	unset($item);
}

/**
 * Order items by update interval.
 *
 * @param array  $items
 * @param int    $items['type']
 * @param string $items['delay']
 * @param string $sortorder
 * @param array  $options
 * @param bool   $options['usermacros']
 * @param bool   $options['lldmacros']
 */
function orderItemsByDelay(array &$items, $sortorder, array $options){
	$update_interval_parser = new CUpdateIntervalParser($options);

	foreach ($items as &$item) {
		if (in_array($item['type'], [ITEM_TYPE_TRAPPER, ITEM_TYPE_SNMPTRAP, ITEM_TYPE_DEPENDENT])) {
			$item['delay_sort'] = '';
		}
		elseif ($update_interval_parser->parse($item['delay']) == CParser::PARSE_SUCCESS) {
			$item['delay_sort'] = $update_interval_parser->getDelay();

			if ($item['delay_sort'][0] !== '{') {
				$item['delay_sort'] = timeUnitToSeconds($item['delay_sort']);
			}
		}
		else {
			$item['delay_sort'] = $item['delay'];
		}
	}
	unset($item);

	order_result($items, 'delay_sort', $sortorder);

	foreach ($items as &$item) {
		unset($item['delay_sort']);
	}
	unset($item);
}

/**
 * Orders items by both status and state. Items are sorted in the following order: enabled, disabled, not supported.
 *
 * Keep in sync with orderTriggersByStatus().
 *
 * @param array  $items
 * @param string $sortorder
 */
function orderItemsByStatus(array &$items, $sortorder = ZBX_SORT_UP) {
	$sort = [];

	foreach ($items as $key => $item) {
		if ($item['status'] == ITEM_STATUS_ACTIVE) {
			$sort[$key] = ($item['state'] == ITEM_STATE_NOTSUPPORTED) ? 2 : 0;
		}
		else {
			$sort[$key] = 1;
		}
	}

	if ($sortorder == ZBX_SORT_UP) {
		asort($sort);
	}
	else {
		arsort($sort);
	}

	$sortedItems = [];
	foreach ($sort as $key => $val) {
		$sortedItems[$key] = $items[$key];
	}
	$items = $sortedItems;
}

/**
 * Returns the name of the given interface type. Items "status" and "state" properties must be defined.
 *
 * @param int $type
 *
 * @return null
 */
function interfaceType2str($type) {
	$interfaceGroupLabels = [
		INTERFACE_TYPE_AGENT => _('Agent'),
		INTERFACE_TYPE_SNMP => _('SNMP'),
		INTERFACE_TYPE_JMX => _('JMX'),
		INTERFACE_TYPE_IPMI => _('IPMI'),
	];

	return isset($interfaceGroupLabels[$type]) ? $interfaceGroupLabels[$type] : null;
}

function itemTypeInterface($type = null) {
	$types = [
		ITEM_TYPE_SNMPV1 => INTERFACE_TYPE_SNMP,
		ITEM_TYPE_SNMPV2C => INTERFACE_TYPE_SNMP,
		ITEM_TYPE_SNMPV3 => INTERFACE_TYPE_SNMP,
		ITEM_TYPE_SNMPTRAP => INTERFACE_TYPE_SNMP,
		ITEM_TYPE_IPMI => INTERFACE_TYPE_IPMI,
		ITEM_TYPE_ZABBIX => INTERFACE_TYPE_AGENT,
		ITEM_TYPE_SIMPLE => INTERFACE_TYPE_ANY,
		ITEM_TYPE_EXTERNAL => INTERFACE_TYPE_ANY,
		ITEM_TYPE_SSH => INTERFACE_TYPE_ANY,
		ITEM_TYPE_TELNET => INTERFACE_TYPE_ANY,
		ITEM_TYPE_JMX => INTERFACE_TYPE_JMX,
		ITEM_TYPE_HTTPAGENT => INTERFACE_TYPE_ANY
	];
	if (is_null($type)) {
		return $types;
	}
	elseif (isset($types[$type])) {
		return $types[$type];
	}
	else {
		return false;
	}
}

/**
 * Copies the given items to the given hosts or templates.
 *
 * @param array $src_itemids  Items which will be copied to $dst_hostids.
 * @param array $dst_hostids  Hosts and templates to whom add items.
 *
 * @return bool
 */
function copyItemsToHosts($src_itemids, $dst_hostids) {
	$items = API::Item()->get([
		'output' => ['type', 'snmp_community', 'snmp_oid', 'name', 'key_', 'delay', 'history', 'trends', 'status',
			'value_type', 'trapper_hosts', 'units', 'snmpv3_contextname', 'snmpv3_securityname', 'snmpv3_securitylevel',
			'snmpv3_authprotocol', 'snmpv3_authpassphrase', 'snmpv3_privprotocol', 'snmpv3_privpassphrase',
			'logtimefmt', 'valuemapid', 'params', 'ipmi_sensor', 'authtype', 'username', 'password', 'publickey',
			'privatekey', 'flags', 'port', 'description', 'inventory_link', 'jmx_endpoint', 'master_itemid', 'timeout',
			'url', 'query_fields', 'posts', 'status_codes', 'follow_redirects', 'post_type', 'http_proxy', 'headers',
			'retrieve_mode', 'request_method', 'output_format', 'ssl_cert_file', 'ssl_key_file', 'ssl_key_password',
			'verify_peer', 'verify_host', 'allow_traps'
		],
		'selectApplications' => ['applicationid'],
		'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
		'itemids' => $src_itemids,
		'preservekeys' => true
	]);

	// Check if dependent items have master items in same selection. If not, those could be web items.
	$master_itemids = [];

	foreach ($items as $itemid => $item) {
		if ($item['type'] == ITEM_TYPE_DEPENDENT && !array_key_exists($item['master_itemid'], $items)) {
			$master_itemids[$item['master_itemid']] = true;
		}
	}

	// Find same master items (that includes web items) on destination host.
	$dst_master_items = [];

	foreach (array_keys($master_itemids) as $master_itemid) {
		$same_master_item = get_same_item_for_host(['itemid' => $master_itemid], $dst_hostids);

		if ($same_master_item) {
			$dst_master_items[$master_itemid] = $same_master_item;
		}
	}

	$create_order = [];
	$src_itemid_to_key = [];

	// Calculate dependency level between items so that master items are created before dependent items.
	foreach ($items as $itemid => $item) {
		$dependency_level = 0;
		$master_item = $item;
		$src_itemid_to_key[$itemid] = $item['key_'];

		while ($master_item['type'] == ITEM_TYPE_DEPENDENT) {
			if (!array_key_exists($master_item['master_itemid'], $items)) {
				break;
			}

			$master_item = $items[$master_item['master_itemid']];
			++$dependency_level;
		}

		$create_order[$itemid] = $dependency_level;
	}

	asort($create_order);

	$dstHosts = API::Host()->get([
		'output' => ['hostid', 'host', 'status'],
		'selectInterfaces' => ['interfaceid', 'type', 'main'],
		'hostids' => $dst_hostids,
		'preservekeys' => true,
		'nopermissions' => true,
		'templated_hosts' => true
	]);

	foreach ($dstHosts as $dstHost) {
		$interfaceids = [];

		foreach ($dstHost['interfaces'] as $interface) {
			if ($interface['main'] == 1) {
				$interfaceids[$interface['type']] = $interface['interfaceid'];
			}
		}

		$itemkey_to_id = [];
		$create_items = [];
		$current_dependency = reset($create_order);

		foreach ($create_order as $itemid => $dependency_level) {
			if ($current_dependency != $dependency_level) {
				$current_dependency = $dependency_level;
				$created_itemids = API::Item()->create($create_items);

				if (!$created_itemids) {
					return false;
				}
				$created_itemids = $created_itemids['itemids'];

				foreach ($create_items as $index => $created_item) {
					$itemkey_to_id[$created_item['key_']] = $created_itemids[$index];
				}

				$create_items = [];
			}

			$item = $items[$itemid];

			if ($dstHost['status'] != HOST_STATUS_TEMPLATE) {
				$type = itemTypeInterface($item['type']);

				if ($type == INTERFACE_TYPE_ANY) {
					foreach ([INTERFACE_TYPE_AGENT, INTERFACE_TYPE_SNMP, INTERFACE_TYPE_JMX, INTERFACE_TYPE_IPMI] as $itype) {
						if (isset($interfaceids[$itype])) {
							$item['interfaceid'] = $interfaceids[$itype];
							break;
						}
					}
				}
				elseif ($type !== false) {
					if (!isset($interfaceids[$type])) {
						error(_s('Cannot find host interface on "%1$s" for item key "%2$s".', $dstHost['host'],
							$item['key_']
						));
						return false;
					}
					$item['interfaceid'] = $interfaceids[$type];
				}
			}
			unset($item['itemid']);
			$item['hostid'] = $dstHost['hostid'];
			$item['applications'] = get_same_applications_for_host(
				zbx_objectValues($item['applications'], 'applicationid'),
				$dstHost['hostid']
			);

			if ($item['type'] == ITEM_TYPE_DEPENDENT) {
				if (array_key_exists($item['master_itemid'], $items)) {
					$src_item_key = $src_itemid_to_key[$item['master_itemid']];
					$item['master_itemid'] = $itemkey_to_id[$src_item_key];
				}
				else {
					$item_found = false;

					if (array_key_exists($item['master_itemid'], $dst_master_items)) {
						foreach ($dst_master_items[$item['master_itemid']] as $dst_master_item) {
							if ($dst_master_item['hostid'] == $dstHost['hostid']) {
								// A matching item on destination host has been found.

								$item['master_itemid'] = $dst_master_item['itemid'];
								$item_found = true;
							}
						}
					}

					// Master item does not exist on destination host or has not been selected for copying.
					if (!$item_found) {
						error(_s('Item "%1$s" cannot be copied without its master item.', $item['name']));

						return false;
					}
				}
			}
			else {
				unset($item['master_itemid']);
			}

			$create_items[] = $item;
		}

		if ($create_items && !API::Item()->create($create_items)) {
			return false;
		}
	}

	return true;
}

function copyItems($srcHostId, $dstHostId) {
	$srcItems = API::Item()->get([
		'output' => ['type', 'snmp_community', 'snmp_oid', 'name', 'key_', 'delay', 'history', 'trends', 'status',
			'value_type', 'trapper_hosts', 'units', 'snmpv3_contextname', 'snmpv3_securityname', 'snmpv3_securitylevel',
			'snmpv3_authprotocol', 'snmpv3_authpassphrase', 'snmpv3_privprotocol', 'snmpv3_privpassphrase',
			'logtimefmt', 'valuemapid', 'params', 'ipmi_sensor', 'authtype', 'username', 'password', 'publickey',
			'privatekey', 'flags', 'port', 'description', 'inventory_link', 'jmx_endpoint', 'master_itemid',
			'templateid', 'url', 'query_fields', 'timeout', 'posts', 'status_codes', 'follow_redirects', 'post_type',
			'http_proxy', 'headers', 'retrieve_mode', 'request_method', 'output_format', 'ssl_cert_file',
			'ssl_key_file', 'ssl_key_password', 'verify_peer', 'verify_host', 'allow_traps'
		],
		'selectApplications' => ['applicationid'],
		'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
		'hostids' => $srcHostId,
		'webitems' => true,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL],
		'preservekeys' => true
	]);
	$dstHosts = API::Host()->get([
		'output' => ['hostid', 'host', 'status'],
		'selectInterfaces' => ['interfaceid', 'type', 'main'],
		'hostids' => $dstHostId,
		'preservekeys' => true,
		'nopermissions' => true,
		'templated_hosts' => true
	]);
	$dstHost = reset($dstHosts);

	$create_order = [];
	$src_itemid_to_key = [];
	foreach ($srcItems as $itemid => $item) {
		$dependency_level = 0;
		$master_item = $item;
		$src_itemid_to_key[$itemid] = $item['key_'];

		while ($master_item['type'] == ITEM_TYPE_DEPENDENT) {
			$master_item = $srcItems[$master_item['master_itemid']];
			++$dependency_level;
		}

		$create_order[$itemid] = $dependency_level;
	}
	asort($create_order);

	$itemkey_to_id = [];
	$create_items = [];
	$current_dependency = reset($create_order);

	foreach ($create_order as $itemid => $dependency_level) {
		$srcItem = $srcItems[$itemid];

		// Skip creating web items. Those were created before.
		if ($srcItem['type'] == ITEM_TYPE_HTTPTEST) {
			continue;
		}

		if ($current_dependency != $dependency_level && $create_items) {
			$current_dependency = $dependency_level;
			$created_itemids = API::Item()->create($create_items);

			if (!$created_itemids) {
				return false;
			}
			$created_itemids = $created_itemids['itemids'];

			foreach ($create_items as $index => $created_item) {
				$itemkey_to_id[$created_item['key_']] = $created_itemids[$index];
			}

			$create_items = [];
		}

		if ($srcItem['templateid']) {
			$srcItem = get_same_item_for_host($srcItem, $dstHost['hostid']);

			if (!$srcItem) {
				return false;
			}
			$itemkey_to_id[$srcItem['key_']] = $srcItem['itemid'];
			continue;
		}

		if ($dstHost['status'] != HOST_STATUS_TEMPLATE) {
			// find a matching interface
			$interface = CItem::findInterfaceForItem($srcItem['type'], $dstHost['interfaces']);
			if ($interface) {
				$srcItem['interfaceid'] = $interface['interfaceid'];
			}
			// no matching interface found, throw an error
			elseif ($interface !== false) {
				error(_s('Cannot find host interface on "%1$s" for item key "%2$s".', $dstHost['host'], $srcItem['key_']));
			}
		}
		unset($srcItem['itemid']);
		unset($srcItem['templateid']);
		$srcItem['hostid'] = $dstHostId;
		$srcItem['applications'] = get_same_applications_for_host(zbx_objectValues($srcItem['applications'], 'applicationid'), $dstHostId);

		if (!$srcItem['preprocessing']) {
			unset($srcItem['preprocessing']);
		}

		if ($srcItem['type'] == ITEM_TYPE_DEPENDENT) {
			if ($srcItems[$srcItem['master_itemid']]['type'] == ITEM_TYPE_HTTPTEST) {
				// Web items are outside the scope and are created before regular items.
				$web_item = get_same_item_for_host($srcItems[$srcItem['master_itemid']], $dstHost['hostid']);
				$srcItem['master_itemid'] = $web_item['itemid'];
			}
			else {
				$src_item_key = $src_itemid_to_key[$srcItem['master_itemid']];
				$srcItem['master_itemid'] = $itemkey_to_id[$src_item_key];
			}
		}
		else {
			unset($srcItem['master_itemid']);
		}

		$create_items[] = $srcItem;
	}

	if ($create_items && !API::Item()->create($create_items)) {
		return false;
	}

	return true;
}

/**
 * Copy applications to a different host.
 *
 * @param string $source_hostid
 * @param string $destination_hostid
 *
 * @return bool
 */
function copyApplications($source_hostid, $destination_hostid) {
	$applications_to_create = API::Application()->get([
		'output' => ['name'],
		'hostids' => [$source_hostid],
		'inherited' => false,
		'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL]
	]);

	if (!$applications_to_create) {
		return true;
	}

	foreach ($applications_to_create as &$application) {
		$application['hostid'] = $destination_hostid;
		unset($application['applicationid'], $application['templateid']);
	}
	unset($application);

	return (bool) API::Application()->create($applications_to_create);
}

function get_item_by_itemid($itemid) {
	$db_items = DBfetch(DBselect('SELECT i.* FROM items i WHERE i.itemid='.zbx_dbstr($itemid)));
	if ($db_items) {
		return $db_items;
	}
	error(_s('No item with itemid="%1$s".', $itemid));
	return false;
}

/**
 * Description:
 * Replace items for specified host
 *
 * Comments:
 * $error= true : rise Error if item doesn't exist (error generated), false: special processing (NO error generated)
 */
function get_same_item_for_host($item, $dest_hostids) {
	$return_array = is_array($dest_hostids);
	zbx_value2array($dest_hostids);

	if (!is_array($item)) {
		$itemid = $item;
	}
	elseif (isset($item['itemid'])) {
		$itemid = $item['itemid'];
	}

	$same_item = null;
	$same_items = [];

	if (isset($itemid)) {
		$db_items = DBselect(
			'SELECT src.*'.
			' FROM items src,items dest'.
			' WHERE dest.itemid='.zbx_dbstr($itemid).
				' AND src.key_=dest.key_'.
				' AND '.dbConditionInt('src.hostid', $dest_hostids)
		);
		while ($db_item = DBfetch($db_items)) {
			if (is_array($item)) {
				$same_item = $db_item;
				$same_items[$db_item['itemid']] = $db_item;
			}
			else {
				$same_item = $db_item['itemid'];
				$same_items[$db_item['itemid']] = $db_item['itemid'];
			}
		}
		if ($return_array) {
			return $same_items;
		}
		else {
			return $same_item;
		}
	}
	return false;
}

/**
 * Get parent templates for each given item.
 *
 * @param array  $items                  An array of items.
 * @param string $items[]['itemid']      ID of an item.
 * @param string $items[]['templateid']  ID of parent template item.
 * @param int    $flag                   Origin of the item (ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_RULE,
 *                                       ZBX_FLAG_DISCOVERY_PROTOTYPE).
 *
 * @return array
 */
function getItemParentTemplates(array $items, $flag) {
	$parent_itemids = [];
	$data = [
		'links' => [],
		'templates' => []
	];

	foreach ($items as $item) {
		if ($item['templateid'] != 0) {
			$parent_itemids[$item['templateid']] = true;
			$data['links'][$item['itemid']] = ['itemid' => $item['templateid']];
		}
	}

	if (!$parent_itemids) {
		return $data;
	}

	$all_parent_itemids = [];
	$hostids = [];
	if ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
		$lld_ruleids = [];
	}

	do {
		if ($flag == ZBX_FLAG_DISCOVERY_RULE) {
			$db_items = API::DiscoveryRule()->get([
				'output' => ['itemid', 'hostid', 'templateid'],
				'itemids' => array_keys($parent_itemids)
			]);
		}
		elseif ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
			$db_items = API::ItemPrototype()->get([
				'output' => ['itemid', 'hostid', 'templateid'],
				'itemids' => array_keys($parent_itemids),
				'selectDiscoveryRule' => ['itemid']
			]);
		}
		// ZBX_FLAG_DISCOVERY_NORMAL
		else {
			$db_items = API::Item()->get([
				'output' => ['itemid', 'hostid', 'templateid'],
				'itemids' => array_keys($parent_itemids),
				'webitems' => true
			]);
		}

		$all_parent_itemids += $parent_itemids;
		$parent_itemids = [];

		foreach ($db_items as $db_item) {
			$data['templates'][$db_item['hostid']] = [];
			$hostids[$db_item['itemid']] = $db_item['hostid'];

			if ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
				$lld_ruleids[$db_item['itemid']] = $db_item['discoveryRule']['itemid'];
			}

			if ($db_item['templateid'] != 0) {
				if (!array_key_exists($db_item['templateid'], $all_parent_itemids)) {
					$parent_itemids[$db_item['templateid']] = true;
				}

				$data['links'][$db_item['itemid']] = ['itemid' => $db_item['templateid']];
			}
		}
	}
	while ($parent_itemids);

	foreach ($data['links'] as &$parent_item) {
		$parent_item['hostid'] = array_key_exists($parent_item['itemid'], $hostids)
			? $hostids[$parent_item['itemid']]
			: 0;

		if ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
			$parent_item['lld_ruleid'] = array_key_exists($parent_item['itemid'], $lld_ruleids)
				? $lld_ruleids[$parent_item['itemid']]
				: 0;
		}
	}
	unset($parent_item);

	$db_templates = $data['templates']
		? API::Template()->get([
			'output' => ['name'],
			'templateids' => array_keys($data['templates']),
			'preservekeys' => true
		])
		: [];

	$rw_templates = $db_templates
		? API::Template()->get([
			'output' => [],
			'templateids' => array_keys($db_templates),
			'editable' => true,
			'preservekeys' => true
		])
		: [];

	$data['templates'][0] = [];

	foreach ($data['templates'] as $hostid => &$template) {
		$template = array_key_exists($hostid, $db_templates)
			? [
				'hostid' => $hostid,
				'name' => $db_templates[$hostid]['name'],
				'permission' => array_key_exists($hostid, $rw_templates) ? PERM_READ_WRITE : PERM_READ
			]
			: [
				'hostid' => $hostid,
				'name' => _('Inaccessible template'),
				'permission' => PERM_DENY
			];
	}
	unset($template);

	return $data;
}

/**
 * Returns a template prefix for selected item.
 *
 * @param string $itemid
 * @param array  $parent_templates  The list of the templates, prepared by getItemParentTemplates() function.
 * @param int    $flag              Origin of the item (ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_RULE,
 *                                  ZBX_FLAG_DISCOVERY_PROTOTYPE).
 *
 * @return array|null
 */
function makeItemTemplatePrefix($itemid, array $parent_templates, $flag) {
	if (!array_key_exists($itemid, $parent_templates['links'])) {
		return null;
	}

	while (array_key_exists($parent_templates['links'][$itemid]['itemid'], $parent_templates['links'])) {
		$itemid = $parent_templates['links'][$itemid]['itemid'];
	}

	$template = $parent_templates['templates'][$parent_templates['links'][$itemid]['hostid']];

	if ($template['permission'] == PERM_READ_WRITE) {
		if ($flag == ZBX_FLAG_DISCOVERY_RULE) {
			$url = (new CUrl('host_discovery.php'))->setArgument('hostid', $template['hostid']);
		}
		elseif ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
			$url = (new CUrl('disc_prototypes.php'))
				->setArgument('parent_discoveryid', $parent_templates['links'][$itemid]['lld_ruleid']);
		}
		// ZBX_FLAG_DISCOVERY_NORMAL
		else {
			$url = (new CUrl('items.php'))
				->setArgument('hostid', $template['hostid'])
				->setArgument('filter_set', 1);
		}

		$name = (new CLink(CHtml::encode($template['name']), $url))->addClass(ZBX_STYLE_LINK_ALT);
	}
	else {
		$name = new CSpan(CHtml::encode($template['name']));
	}

	return [$name->addClass(ZBX_STYLE_GREY), NAME_DELIMITER];
}

/**
 * Returns a list of item templates.
 *
 * @param string $itemid
 * @param array  $parent_templates  The list of the templates, prepared by getItemParentTemplates() function.
 * @param int    $flag              Origin of the item (ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_RULE,
 *                                  ZBX_FLAG_DISCOVERY_PROTOTYPE).
 *
 * @return array
 */
function makeItemTemplatesHtml($itemid, array $parent_templates, $flag) {
	$list = [];

	while (array_key_exists($itemid, $parent_templates['links'])) {
		$template = $parent_templates['templates'][$parent_templates['links'][$itemid]['hostid']];

		if ($template['permission'] == PERM_READ_WRITE) {
			if ($flag == ZBX_FLAG_DISCOVERY_RULE) {
				$url = (new CUrl('host_discovery.php'))
					->setArgument('form', 'update')
					->setArgument('itemid', $parent_templates['links'][$itemid]['itemid']);
			}
			elseif ($flag == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
				$url = (new CUrl('disc_prototypes.php'))
					->setArgument('form', 'update')
					->setArgument('itemid', $parent_templates['links'][$itemid]['itemid'])
					->setArgument('parent_discoveryid', $parent_templates['links'][$itemid]['lld_ruleid']);
			}
			// ZBX_FLAG_DISCOVERY_NORMAL
			else {
				$url = (new CUrl('items.php'))
					->setArgument('form', 'update')
					->setArgument('itemid', $parent_templates['links'][$itemid]['itemid']);
			}

			$name = new CLink(CHtml::encode($template['name']), $url);
		}
		else {
			$name = (new CSpan(CHtml::encode($template['name'])))->addClass(ZBX_STYLE_GREY);
		}

		array_unshift($list, $name, '&nbsp;&rArr;&nbsp;');

		$itemid = $parent_templates['links'][$itemid]['itemid'];
	}

	if ($list) {
		array_pop($list);
	}

	return $list;
}

/**
 * Retrieve overview table object for items.
 *
 * @param array  $groupids
 * @param string $application      IDs of applications to filter items by.
 * @param int    $viewMode
 * @param int    $show_suppressed  Whether to show suppressed problems.
 *
 * @return CTableInfo
 */

function getItemsDataOverview(array $groupids, $application, $viewMode,
		$show_suppressed = ZBX_PROBLEM_SUPPRESSED_TRUE) {
	// application filter
	if ($application !== '') {
		$applicationids = array_keys(API::Application()->get([
			'output' => [],
			'groupids' => $groupids ? $groupids : null,
			'search' => ['name' => $application],
			'preservekeys' => true
		]));
		$groupids = [];
	}
	else {
		$applicationids = null;
	}

	$db_items = API::Item()->get([
		'output' => ['itemid', 'hostid', 'key_', 'name', 'value_type', 'units', 'valuemapid'],
		'selectHosts' => ['name'],
		'groupids' => $groupids ? $groupids : null,
		'applicationids' => $applicationids,
		'monitored' => true,
		'webitems' => true,
		'preservekeys' => true
	]);

	$db_triggers = getTriggersWithActualSeverity([
		'output' => ['triggerid', 'priority', 'value'],
		'selectItems' => ['itemid'],
		'groupids' => $groupids ? $groupids : null,
		'applicationids' => $applicationids,
		'monitored' => true,
		'preservekeys' => true
	], ['show_suppressed' => $show_suppressed]);

	foreach ($db_triggers as $db_trigger) {
		foreach ($db_trigger['items'] as $item) {
			if (array_key_exists($item['itemid'], $db_items)) {
				$db_item = &$db_items[$item['itemid']];

				// a little tricky check for attempt to overwrite active trigger (value=1) with
				// inactive or active trigger with lower priority.
				if (!array_key_exists('triggerid', $db_item)
						|| ($db_item['value'] == TRIGGER_VALUE_FALSE && $db_trigger['value'] == TRIGGER_VALUE_TRUE)
						|| (($db_item['value'] == TRIGGER_VALUE_FALSE || $db_trigger['value'] == TRIGGER_VALUE_TRUE)
							&& $db_item['priority'] < $db_trigger['priority'])) {
					$db_item['triggerid'] = $db_trigger['triggerid'];
					$db_item['priority'] = $db_trigger['priority'];
					$db_item['value'] = $db_trigger['value'];
				}

				$db_item['acknowledged'] = $db_trigger['problem']['acknowledged'];

				unset($db_item);
			}
		}
	}

	$db_items = CMacrosResolverHelper::resolveItemNames($db_items);

	CArrayHelper::sort($db_items, [
		['field' => 'name_expanded', 'order' => ZBX_SORT_UP],
		['field' => 'itemid', 'order' => ZBX_SORT_UP]
	]);

	// fetch latest values
	$history = Manager::History()->getLastValues(zbx_toHash($db_items, 'itemid'), 1, ZBX_HISTORY_PERIOD);

	// fetch data for the host JS menu
	$hosts = API::Host()->get([
		'output' => ['name', 'hostid'],
		'monitored_hosts' => true,
		'groupids' => $groupids ? $groupids : null,
		'applicationids' => $applicationids,
		'with_monitored_items' => true,
		'preservekeys' => true
	]);

	$items = [];
	$item_counter = [];
	$host_items = [];
	$host_names = [];

	foreach ($db_items as $db_item) {
		$item_name = $db_item['name_expanded'];
		$host_name = $db_item['hosts'][0]['name'];
		$host_names[$db_item['hostid']] = $host_name;

		if (!array_key_exists($host_name, $item_counter)) {
			$item_counter[$host_name] = [];
		}

		if (!array_key_exists($item_name, $item_counter[$host_name])) {
			$item_counter[$host_name][$item_name] = 0;
		}

		if (!array_key_exists($item_name, $host_items) || !array_key_exists($host_name, $host_items[$item_name])) {
			$host_items[$item_name][$host_name] = [];
		}

		if (!array_key_exists($db_item['itemid'], $host_items[$item_name][$host_name])) {
			if (array_key_exists($db_item['itemid'], $host_items[$item_name][$host_name])) {
				$item_place = $host_items[$item_name][$host_name][$db_item['itemid']]['item_place'];
			}
			else {
				$item_place = $item_counter[$host_name][$item_name];
				$item_counter[$host_name][$item_name]++;
			}

			$item = [
				'itemid' => $db_item['itemid'],
				'value_type' => $db_item['value_type'],
				'value' => isset($history[$db_item['itemid']]) ? $history[$db_item['itemid']][0]['value'] : null,
				'units' => $db_item['units'],
				'valuemapid' => $db_item['valuemapid'],
				'item_place' => $item_place,
				'acknowledged' => array_key_exists('acknowledged', $db_item) ? $db_item['acknowledged'] : 0
			];

			if (array_key_exists('triggerid', $db_item)) {
				$item += [
					'triggerid' => $db_item['triggerid'],
					'severity' => $db_item['priority'],
					'tr_value' => $db_item['value']
				];
			}
			else {
				$item += [
					'triggerid' => null,
					'severity' => null,
					'tr_value' => null
				];
			}

			$items[$item_name][$item_place][$host_name] = $item;

			$host_items[$item_name][$host_name][$db_item['itemid']] = $items[$item_name][$item_place][$host_name];
		}
	}

	$table = (new CTableInfo())->setHeadingColumn(0);
	if (!$host_names) {
		return $table;
	}
	$table->makeVerticalRotation();

	order_result($host_names);

	if ($viewMode == STYLE_TOP) {
		$header = [_('Items')];
		foreach ($host_names as $host_name) {
			$header[] = (new CColHeader($host_name))
				->addClass('vertical_rotation')
				->setTitle($host_name);
		}
		$table->setHeader($header);

		foreach ($items as $item_name => $item_data) {
			foreach ($item_data as $ithosts) {
				$tableRow = [(new CColHeader($item_name))->addClass(ZBX_STYLE_NOWRAP)];
				foreach ($host_names as $host_name) {
					$tableRow = getItemDataOverviewCells($tableRow, $ithosts, $host_name);
				}
				$table->addRow($tableRow);
			}
		}
	}
	else {
		$header = [_('Hosts')];
		foreach ($items as $item_name => $item_data) {
			foreach ($item_data as $ithosts) {
				$header[] = (new CColHeader($item_name))
					->addClass('vertical_rotation')
					->setTitle($item_name);
			}
		}
		$table->setHeader($header);

		foreach ($host_names as $hostId => $host_name) {
			$host = $hosts[$hostId];

			$name = (new CLinkAction($host['name']))->setMenuPopup(CMenuPopupHelper::getHost($hostId));

			$tableRow = [(new CColHeader($name))->addClass(ZBX_STYLE_NOWRAP)];
			foreach ($items as $item_data) {
				foreach ($item_data as $ithosts) {
					$tableRow = getItemDataOverviewCells($tableRow, $ithosts, $host_name);
				}
			}
			$table->addRow($tableRow);
		}
	}

	return $table;
}

function getItemDataOverviewCells($tableRow, $ithosts, $hostName) {
	$ack = null;
	$css = '';
	$value = UNKNOWN_VALUE;

	if (isset($ithosts[$hostName])) {
		$item = $ithosts[$hostName];

		if ($item['tr_value'] == TRIGGER_VALUE_TRUE) {
			$css = getSeverityStyle($item['severity']);
			$ack = ($item['acknowledged'] == 1) ? [' ', (new CSpan())->addClass(ZBX_STYLE_ICON_ACKN)] : null;
		}

		if ($item['value'] !== null) {
			$value = formatHistoryValue($item['value'], $item);
		}
	}

	if ($value != UNKNOWN_VALUE) {
		$value = $value;
	}

	$column = (new CCol([$value, $ack]))->addClass($css);

	if (isset($ithosts[$hostName])) {
		$column
			->setMenuPopup(CMenuPopupHelper::getHistory($item['itemid']))
			->addClass(ZBX_STYLE_CURSOR_POINTER)
			->addClass(ZBX_STYLE_NOWRAP);
	}

	$tableRow[] = $column;

	return $tableRow;
}

/**
 * Get same application IDs on destination host and return array with keys as source application IDs
 * and values as destination application IDs.
 *
 * Comments: !!! Don't forget sync code with C !!!
 *
 * @param array  $applicationIds
 * @param string $hostId
 *
 * @return array
 */
function get_same_applications_for_host(array $applicationIds, $hostId) {
	$applications = [];

	$dbApplications = DBselect(
		'SELECT a1.applicationid AS dstappid,a2.applicationid AS srcappid'.
		' FROM applications a1,applications a2'.
		' WHERE a1.name=a2.name'.
			' AND a1.hostid='.zbx_dbstr($hostId).
			' AND '.dbConditionInt('a2.applicationid', $applicationIds)
	);

	while ($dbApplication = DBfetch($dbApplications)) {
		$applications[$dbApplication['srcappid']] = $dbApplication['dstappid'];
	}

	return $applications;
}

/******************************************************************************
 *                                                                            *
 * Comments: !!! Don't forget sync code with C !!!                            *
 *                                                                            *
 ******************************************************************************/
function get_applications_by_itemid($itemids, $field = 'applicationid') {
	zbx_value2array($itemids);
	$result = [];
	$db_applications = DBselect(
		'SELECT DISTINCT app.'.$field.' AS result'.
		' FROM applications app,items_applications ia'.
		' WHERE app.applicationid=ia.applicationid'.
			' AND '.dbConditionInt('ia.itemid', $itemids)
	);
	while ($db_application = DBfetch($db_applications)) {
		array_push($result, $db_application['result']);
	}

	return $result;
}

/**
 * Format history value.
 * First format the value according to the configuration of the item. Then apply the value mapping to the formatted (!)
 * value.
 *
 * @param mixed     $value
 * @param array     $item
 * @param int       $item['value_type']     type of the value: ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64, ...
 * @param string    $item['units']          units of item
 * @param int       $item['valuemapid']     id of mapping set of values
 * @param bool      $trim
 *
 * @return string
 */
function formatHistoryValue($value, array $item, $trim = true) {
	$mapping = false;

	// format value
	if ($item['value_type'] == ITEM_VALUE_TYPE_FLOAT || $item['value_type'] == ITEM_VALUE_TYPE_UINT64) {
		$value = convert_units([
				'value' => $value,
				'units' => $item['units']
		]);
	}
	elseif ($item['value_type'] != ITEM_VALUE_TYPE_STR
		&& $item['value_type'] != ITEM_VALUE_TYPE_TEXT
		&& $item['value_type'] != ITEM_VALUE_TYPE_LOG) {

		$value = _('Unknown value type');
	}

	// apply value mapping
	switch ($item['value_type']) {
		case ITEM_VALUE_TYPE_STR:
			$mapping = getMappedValue($value, $item['valuemapid']);
		// break; is not missing here
		case ITEM_VALUE_TYPE_TEXT:
		case ITEM_VALUE_TYPE_LOG:
			if ($trim && mb_strlen($value) > 20) {
				$value = mb_substr($value, 0, 20).'...';
			}

			if ($mapping !== false) {
				$value = $mapping.' ('.$value.')';
			}
			break;
		default:
			$value = applyValueMap($value, $item['valuemapid']);
	}

	return $value;
}

/**
 * Retrieves from DB historical data for items and applies functional calculations.
 * If fails for some reason, returns UNRESOLVED_MACRO_STRING.
 *
 * @param array		$item
 * @param string	$item['value_type']	type of item, allowed: ITEM_VALUE_TYPE_FLOAT and ITEM_VALUE_TYPE_UINT64
 * @param string	$item['itemid']		ID of item
 * @param string	$item['units']		units of item
 * @param string	$function			function to apply to time period from param, allowed: min, max and avg
 * @param string	$parameter			formatted parameter for function, example: "2w" meaning 2 weeks
 *
 * @return string item functional value from history
 */
function getItemFunctionalValue($item, $function, $parameter) {
	// check whether function is allowed
	if (!in_array($function, ['min', 'max', 'avg']) || $parameter === '') {
		return UNRESOLVED_MACRO_STRING;
	}

	$parameter = convertFunctionValue($parameter);

	if (bccomp($parameter, 0) == 0) {
		return UNRESOLVED_MACRO_STRING;
	}

	// allowed item types for min, max and avg function
	$history_tables = [ITEM_VALUE_TYPE_FLOAT => 'history', ITEM_VALUE_TYPE_UINT64 => 'history_uint'];

	if (!array_key_exists($item['value_type'], $history_tables)) {
		return UNRESOLVED_MACRO_STRING;
	}
	else {
		$result = Manager::History()->getAggregatedValue($item, $function, (time() - $parameter));

		if ($result !== null) {
			return convert_units(['value' => $result, 'units' => $item['units']]);
		}
		// no data in history
		else {
			return UNRESOLVED_MACRO_STRING;
		}
	}
}

/**
 * Check if current time is within the given period.
 *
 * @param string $period	time period format: "wd[-wd2],hh:mm-hh:mm"
 * @param int $now			current timestamp
 *
 * @return bool		true - within period, false - out of period
 */
function checkTimePeriod($period, $now) {
	if (sscanf($period, '%d-%d,%d:%d-%d:%d', $d1, $d2, $h1, $m1, $h2, $m2) != 6) {
		if (sscanf($period, '%d,%d:%d-%d:%d', $d1, $h1, $m1, $h2, $m2) != 5) {
			// delay period format is wrong - skip
			return false;
		}
		$d2 = $d1;
	}

	$tm = localtime($now, true);
	$day = ($tm['tm_wday'] == 0) ? 7 : $tm['tm_wday'];
	$sec = SEC_PER_HOUR * $tm['tm_hour'] + SEC_PER_MIN * $tm['tm_min'] + $tm['tm_sec'];

	$sec1 = SEC_PER_HOUR * $h1 + SEC_PER_MIN * $m1;
	$sec2 = SEC_PER_HOUR * $h2 + SEC_PER_MIN * $m2;

	return $d1 <= $day && $day <= $d2 && $sec1 <= $sec && $sec < $sec2;
}

/**
 * Get item minimum delay.
 *
 * @param string $delay
 * @param array $flexible_intervals
 *
 * @return string
 */
function getItemDelay($delay, array $flexible_intervals) {
	$delay = timeUnitToSeconds($delay);

	if ($delay != 0 || !$flexible_intervals) {
		return $delay;
	}

	$min_delay = SEC_PER_YEAR;

	foreach ($flexible_intervals as $flexible_interval) {
		$flexible_interval_parts = explode('/', $flexible_interval);
		$flexible_delay = timeUnitToSeconds($flexible_interval_parts[0]);

		$min_delay = min($min_delay, $flexible_delay);
	}

	return $min_delay;
}

/**
 * Return delay value that is currently applicable
 *
 * @param int $delay					default delay
 * @param array $flexible_intervals		array of intervals in format: "d/wd[-wd2],hh:mm-hh:mm"
 * @param int $now						current timestamp
 *
 * @return int							delay for a current timestamp
 */
function getCurrentDelay($delay, array $flexible_intervals, $now) {
	if (!$flexible_intervals) {
		return $delay;
	}

	$current_delay = -1;

	foreach ($flexible_intervals as $flexible_interval) {
		list($flexible_delay, $flexible_period) = explode('/', $flexible_interval);
		$flexible_delay = (int) $flexible_delay;

		if (($current_delay == -1 || $flexible_delay < $current_delay) && checkTimePeriod($flexible_period, $now)) {
			$current_delay = $flexible_delay;
		}
	}

	if ($current_delay == -1) {
		return $delay;
	}

	return $current_delay;
}

/**
 * Return time of next flexible interval
 *
 * @param array $flexible_intervals  array of intervals in format: "d/wd[-wd2],hh:mm-hh:mm"
 * @param int $now                   current timestamp
 * @param int $next_interval          timestamp of a next interval
 *
 * @return bool                      false if no flexible intervals defined
 */
function getNextDelayInterval(array $flexible_intervals, $now, &$next_interval) {
	if (!$flexible_intervals) {
		return false;
	}

	$next = 0;
	$tm = localtime($now, true);
	$day = ($tm['tm_wday'] == 0) ? 7 : $tm['tm_wday'];
	$sec = SEC_PER_HOUR * $tm['tm_hour'] + SEC_PER_MIN * $tm['tm_min'] + $tm['tm_sec'];

	foreach ($flexible_intervals as $flexible_interval) {
		$flexible_interval_parts = explode('/', $flexible_interval);

		if (sscanf($flexible_interval_parts[1], '%d-%d,%d:%d-%d:%d', $d1, $d2, $h1, $m1, $h2, $m2) != 6) {
			if (sscanf($flexible_interval_parts[1], '%d,%d:%d-%d:%d', $d1, $h1, $m1, $h2, $m2) != 5) {
				continue;
			}
			$d2 = $d1;
		}

		$sec1 = SEC_PER_HOUR * $h1 + SEC_PER_MIN * $m1;
		$sec2 = SEC_PER_HOUR * $h2 + SEC_PER_MIN * $m2;

		// current period
		if ($d1 <= $day && $day <= $d2 && $sec1 <= $sec && $sec < $sec2) {
			if ($next == 0 || $next > $now - $sec + $sec2) {
				// the next second after the current interval's upper bound
				$next = $now - $sec + $sec2;
			}
		}
		// will be active today
		elseif ($d1 <= $day && $d2 >= $day && $sec < $sec1) {
			if ($next == 0 || $next > $now - $sec + $sec1) {
				$next = $now - $sec + $sec1;
			}
		}
		else {
			$nextDay = ($day + 1 <= 7) ? $day + 1 : 1;

			// will be active tomorrow
			if ($d1 <= $nextDay && $nextDay <= $d2) {
				if ($next == 0 || $next > $now - $sec + SEC_PER_DAY + $sec1) {
					$next = $now - $sec + SEC_PER_DAY + $sec1;
				}
			}
			// later in the future
			else {
				$dayDiff = -1;

				if ($day < $d1) {
					$dayDiff = $d1 - $day;
				}
				if ($day >= $d2) {
					$dayDiff = ($d1 + 7) - $day;
				}
				if ($d1 <= $day && $day < $d2) {
					// should never happen, could not deduce day difference
					$dayDiff = -1;
				}
				if ($dayDiff != -1 && ($next == 0 || $next > $now - $sec + SEC_PER_DAY * $dayDiff + $sec1)) {
					$next = $now - $sec + SEC_PER_DAY * $dayDiff + $sec1;
				}
			}
		}
	}

	if ($next != 0) {
		$next_interval = $next;
	}

	return ($next != 0);
}

/**
 * Calculate nextcheck timestamp for an item using flexible intervals.
 *
 * the parameter $flexible_intervals is an array if strings that are in the following format:
 *
 *           +------------[;]<----------+
 *           |                          |
 *         ->+-[d/wd[-wd2],hh:mm-hh:mm]-+
 *
 *         d       - delay (0-n)
 *         wd, wd2 - day of week (1-7)
 *         hh      - hours (0-24)
 *         mm      - minutes (0-59)
 *
 * @param int $seed						seed value applied to delay to spread item checks over the delay period
 * @param string $delay					default delay, can be overridden
 * @param array $flexible_intervals		array of flexible intervals
 * @param int $now						current timestamp
 *
 * @return int
 */
function calculateItemNextCheck($seed, $delay, $flexible_intervals, $now) {
	/*
	 * Try to find the nearest 'nextcheck' value with condition 'now' < 'nextcheck' < 'now' + SEC_PER_YEAR
	 * If it is not possible to check the item within a year, fail.
	 */

	$t = $now;
	$tMax = $now + SEC_PER_YEAR;
	$try = 0;

	while ($t < $tMax) {
		// Calculate 'nextcheck' value for the current interval.
		$currentDelay = getCurrentDelay($delay, $flexible_intervals, $t);

		if ($currentDelay != 0) {
			$nextCheck = $currentDelay * floor($t / $currentDelay) + ($seed % $currentDelay);

			if ($try == 0) {
				while ($nextCheck <= $t) {
					$nextCheck += $currentDelay;
				}
			}
			else {
				while ($nextCheck < $t) {
					$nextCheck += $currentDelay;
				}
			}
		}
		else {
			$nextCheck = ZBX_JAN_2038;
		}

		/*
		 * Is 'nextcheck' < end of the current interval and the end of the current interval
		 * is the beginning of the next interval - 1.
		 */
		if (getNextDelayInterval($flexible_intervals, $t, $nextInterval) && $nextCheck >= $nextInterval) {
			// 'nextcheck' is beyond the current interval.
			$t = $nextInterval;
			$try++;
		}
		else {
			break;
		}
	}

	return $nextCheck;
}

/*
 * Description:
 *	Function returns true if http items exists in the $items array.
 *	The array should contain a field 'type'
 */
function httpItemExists($items) {
	foreach ($items as $item) {
		if ($item['type'] == ITEM_TYPE_HTTPTEST) {
			return true;
		}
	}
	return false;
}

function getParamFieldNameByType($itemType) {
	switch ($itemType) {
		case ITEM_TYPE_SSH:
		case ITEM_TYPE_TELNET:
		case ITEM_TYPE_JMX:
			return 'params_es';
		case ITEM_TYPE_DB_MONITOR:
			return 'params_ap';
		case ITEM_TYPE_CALCULATED:
			return 'params_f';
		default:
			return 'params';
	}
}

function getParamFieldLabelByType($itemType) {
	switch ($itemType) {
		case ITEM_TYPE_SSH:
		case ITEM_TYPE_TELNET:
		case ITEM_TYPE_JMX:
			return _('Executed script');
		case ITEM_TYPE_DB_MONITOR:
			return _('SQL query');
		case ITEM_TYPE_CALCULATED:
			return _('Formula');
		default:
			return 'params';
	}
}

/**
 * Get either one or all item preprocessing types.
 * If $grouped set to true, returns group labels. Returns empty string if no specific type is found.
 *
 * Usage examples:
 *    - get_preprocessing_types(null, true, [5, 4, 2])             Returns array as defined.
 *    - get_preprocessing_types(4, true, [5, 4, 2])                Returns string: 'Trim'.
 *    - get_preprocessing_types(<wrong type>, true, [5, 4, 2])     Returns an empty string: ''.
 *    - get_preprocessing_types(null, false, [5, 12, 15, 16, 20])  Returns subarrays in one array maintaining index:
 *                                                                     [5] => Regular expression
 *                                                                     [12] => JSONPath
 *                                                                     [15] => Does not match regular expression
 *                                                                     [16] => Check for error in JSON
 *                                                                     [20] => Discard unchanged with heartbeat
 *
 * @param int   $type             Item preprocessing type.
 * @param bool  $grouped          Group label flag. If specific type is given, this parameter does not matter.
 * @param array $supported_types  Array of supported pre-processing types. If none are given, empty array is returned.
 *
 * @return array|string
 */
function get_preprocessing_types($type = null, $grouped = true, array $supported_types = []) {
	$types = [
		ZBX_PREPROC_REGSUB => [
			'group' => _('Text'),
			'name' => _('Regular expression')
		],
		ZBX_PREPROC_TRIM => [
			'group' => _('Text'),
			'name' => _('Trim')
		],
		ZBX_PREPROC_RTRIM => [
			'group' => _('Text'),
			'name' => _('Right trim')
		],
		ZBX_PREPROC_LTRIM => [
			'group' => _('Text'),
			'name' => _('Left trim')
		],
		ZBX_PREPROC_XPATH => [
			'group' => _('Structured data'),
			'name' => _('XML XPath')
		],
		ZBX_PREPROC_JSONPATH => [
			'group' => _('Structured data'),
			'name' => _('JSONPath')
		],
		ZBX_PREPROC_CSV_TO_JSON => [
			'group' => _('Structured data'),
			'name' => _('CSV to JSON')
		],
		ZBX_PREPROC_MULTIPLIER => [
			'group' => _('Arithmetic'),
			'name' => _('Custom multiplier')
		],
		ZBX_PREPROC_DELTA_VALUE => [
			'group' => _x('Change', 'noun'),
			'name' => _('Simple change')
		],
		ZBX_PREPROC_DELTA_SPEED => [
			'group' => _x('Change', 'noun'),
			'name' => _('Change per second')
		],
		ZBX_PREPROC_BOOL2DEC => [
			'group' => _('Numeral systems'),
			'name' => _('Boolean to decimal')
		],
		ZBX_PREPROC_OCT2DEC => [
			'group' => _('Numeral systems'),
			'name' => _('Octal to decimal')
		],
		ZBX_PREPROC_HEX2DEC => [
			'group' => _('Numeral systems'),
			'name' => _('Hexadecimal to decimal')
		],
		ZBX_PREPROC_SCRIPT => [
			'group' => _('Custom scripts'),
			'name' => _('JavaScript')
		],
		ZBX_PREPROC_VALIDATE_RANGE => [
			'group' => _('Validation'),
			'name' => _('In range')
		],
		ZBX_PREPROC_VALIDATE_REGEX => [
			'group' => _('Validation'),
			'name' => _('Matches regular expression')
		],
		ZBX_PREPROC_VALIDATE_NOT_REGEX => [
			'group' => _('Validation'),
			'name' => _('Does not match regular expression')
		],
		ZBX_PREPROC_ERROR_FIELD_JSON => [
			'group' => _('Validation'),
			'name' => _('Check for error in JSON')
		],
		ZBX_PREPROC_ERROR_FIELD_XML => [
			'group' => _('Validation'),
			'name' => _('Check for error in XML')
		],
		ZBX_PREPROC_ERROR_FIELD_REGEX => [
			'group' => _('Validation'),
			'name' => _('Check for error using regular expression')
		],
		ZBX_PREPROC_THROTTLE_VALUE => [
			'group' => _('Throttling'),
			'name' => _('Discard unchanged')
		],
		ZBX_PREPROC_THROTTLE_TIMED_VALUE => [
			'group' => _('Throttling'),
			'name' => _('Discard unchanged with heartbeat')
		],
		ZBX_PREPROC_PROMETHEUS_PATTERN => [
			'group' => _('Prometheus'),
			'name' => _('Prometheus pattern')
		],
		ZBX_PREPROC_PROMETHEUS_TO_JSON => [
			'group' => _('Prometheus'),
			'name' => _('Prometheus to JSON')
		]
	];

	$filtered_types = [];

	foreach ($types as $_type => $data) {
		if (in_array($_type, $supported_types)) {
			$filtered_types[$data['group']][$_type] = $data['name'];
		}
	}

	$groups = [];

	foreach ($filtered_types as $label => $types) {
		$groups[] = [
			'label' => $label,
			'types' => $types
		];
	}

	if ($type !== null) {
		foreach ($groups as $group) {
			if (array_key_exists($type, $group['types'])) {
				return $group['types'][$type];
			}
		}

		return '';
	}
	elseif ($grouped) {
		return $groups;
	}
	else {
		$types = [];

		foreach ($groups as $group) {
			$types += $group['types'];
		}

		return $types;
	}
}

/*
 * Quoting $param if it contain special characters.
 *
 * @param string $param
 * @param bool   $forced
 *
 * @return string
 */
function quoteItemKeyParam($param, $forced = false) {
	if (!$forced) {
		if (!isset($param[0]) || ($param[0] != '"' && false === strpbrk($param, ',]'))) {
			return $param;
		}
	}

	return '"'.str_replace('"', '\\"', $param).'"';
}

/**
 * Expands item name and for dependent item master item name.
 *
 * @param array  $items        Array of items.
 * @param string $data_source  'items' or 'itemprototypes'.
 *
 * @return array
 */
function expandItemNamesWithMasterItems($items, $data_source) {
	$items = CMacrosResolverHelper::resolveItemNames($items);
	$itemids = [];
	$master_itemids = [];

	foreach ($items as $item_index => &$item) {
		if ($item['type'] == ITEM_TYPE_DEPENDENT) {
			$master_itemids[$item['master_itemid']] = true;
		}

		// The "source" is required to tell the frontend where the link should point at - item or item prototype.
		$item['source'] = $data_source;
		$itemids[$item_index] = $item['itemid'];
	}
	unset($item);

	$master_itemids = array_diff(array_keys($master_itemids), $itemids);

	if ($master_itemids) {
		$options = [
			'output' => ['itemid', 'type', 'hostid', 'name', 'key_'],
			'itemids' => $master_itemids,
			'editable' => true,
			'preservekeys' => true
		];
		$master_items = API::Item()->get($options + ['webitems' => true]);

		foreach ($master_items as &$master_item) {
			$master_item['source'] = 'items';
		}
		unset($master_item);

		$master_item_prototypes = API::ItemPrototype()->get($options);

		foreach ($master_item_prototypes as &$master_item_prototype) {
			$master_item_prototype['source'] = 'itemprototypes';
		}
		unset($master_item_prototype);

		$master_items = CMacrosResolverHelper::resolveItemNames($master_items + $master_item_prototypes);
	}

	foreach ($items as &$item) {
		if ($item['type'] == ITEM_TYPE_DEPENDENT) {
			$master_itemid = $item['master_itemid'];
			$items_index = array_search($master_itemid, $itemids);

			$item['master_item'] = [
				'itemid' => $master_itemid,
				'name_expanded' => ($items_index === false)
					? $master_items[$master_itemid]['name_expanded']
					: $items[$items_index]['name_expanded'],
				'type' => ($items_index === false)
					? $master_items[$master_itemid]['type']
					: $items[$items_index]['type'],
				'source' => ($items_index === false)
					? $master_items[$master_itemid]['source']
					: $items[$items_index]['source'],
			];
		}
	}
	unset($item);

	return $items;
}

/**
 * Returns an array of allowed item types for "Check now" functionality.
 *
 * @return array
 */
function checkNowAllowedTypes() {
	return [
		ITEM_TYPE_ZABBIX,
		ITEM_TYPE_SNMPV1,
		ITEM_TYPE_SIMPLE,
		ITEM_TYPE_SNMPV2C,
		ITEM_TYPE_INTERNAL,
		ITEM_TYPE_SNMPV3,
		ITEM_TYPE_AGGREGATE,
		ITEM_TYPE_EXTERNAL,
		ITEM_TYPE_DB_MONITOR,
		ITEM_TYPE_IPMI,
		ITEM_TYPE_SSH,
		ITEM_TYPE_TELNET,
		ITEM_TYPE_CALCULATED,
		ITEM_TYPE_JMX,
		ITEM_TYPE_HTTPAGENT
	];
}
