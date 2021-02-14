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
 * Class containing methods for operations with host interfaces.
 */
class CHostInterface extends CApiService {

	protected $tableName = 'interface';
	protected $tableAlias = 'hi';
	protected $sortColumns = ['interfaceid', 'dns', 'ip'];

	/**
	 * Get interface data.
	 *
	 * @param array  $options
	 * @param array  $options['hostids']		Interface IDs
	 * @param bool   $options['editable']		only with read-write permission. Ignored for SuperAdmins
	 * @param bool   $options['selectHosts']	select Interface hosts
	 * @param bool   $options['selectItems']	select Items
	 * @param int    $options['count']			count Interfaces, returned column name is rowscount
	 * @param string $options['pattern']		search hosts by pattern in Interface name
	 * @param int    $options['limit']			limit selection
	 * @param string $options['sortfield']		field to sort by
	 * @param string $options['sortorder']		sort order
	 *
	 * @return array|boolean Interface data as array or false if error
	 */
	public function get(array $options = []) {
		$result = [];

		$sqlParts = [
			'select'	=> ['interface' => 'hi.interfaceid'],
			'from'		=> ['interface' => 'interface hi'],
			'where'		=> [],
			'group'		=> [],
			'order'		=> [],
			'limit'		=> null
		];

		$defOptions = [
			'groupids'					=> null,
			'hostids'					=> null,
			'interfaceids'				=> null,
			'itemids'					=> null,
			'triggerids'				=> null,
			'editable'					=> false,
			'nopermissions'				=> null,
			// filter
			'filter'					=> null,
			'search'					=> null,
			'searchByAny'				=> null,
			'startSearch'				=> false,
			'excludeSearch'				=> false,
			'searchWildcardsEnabled'	=> null,
			// output
			'output'					=> API_OUTPUT_EXTEND,
			'selectHosts'				=> null,
			'selectItems'				=> null,
			'countOutput'				=> false,
			'groupCount'				=> false,
			'preservekeys'				=> false,
			'sortfield'					=> '',
			'sortorder'					=> '',
			'limit'						=> null,
			'limitSelects'				=> null
		];
		$options = zbx_array_merge($defOptions, $options);

		// editable + PERMISSION CHECK
		if (self::$userData['type'] != USER_TYPE_SUPER_ADMIN && !$options['nopermissions']) {
			$permission = $options['editable'] ? PERM_READ_WRITE : PERM_READ;
			$userGroups = getUserGroupsByUserId(self::$userData['userid']);

			$sqlParts['where'][] = 'EXISTS ('.
				'SELECT NULL'.
				' FROM hosts_groups hgg'.
					' JOIN rights r'.
						' ON r.id=hgg.groupid'.
							' AND '.dbConditionInt('r.groupid', $userGroups).
				' WHERE hi.hostid=hgg.hostid'.
				' GROUP BY hgg.hostid'.
				' HAVING MIN(r.permission)>'.PERM_DENY.
					' AND MAX(r.permission)>='.zbx_dbstr($permission).
				')';
		}

		// interfaceids
		if (!is_null($options['interfaceids'])) {
			zbx_value2array($options['interfaceids']);
			$sqlParts['where']['interfaceid'] = dbConditionInt('hi.interfaceid', $options['interfaceids']);
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);
			$sqlParts['where']['hostid'] = dbConditionInt('hi.hostid', $options['hostids']);
		}

		// itemids
		if (!is_null($options['itemids'])) {
			zbx_value2array($options['itemids']);

			$sqlParts['from']['items'] = 'items i';
			$sqlParts['where'][] = dbConditionInt('i.itemid', $options['itemids']);
			$sqlParts['where']['hi'] = 'hi.interfaceid=i.interfaceid';
		}

		// triggerids
		if (!is_null($options['triggerids'])) {
			zbx_value2array($options['triggerids']);

			$sqlParts['from']['functions'] = 'functions f';
			$sqlParts['from']['items'] = 'items i';
			$sqlParts['where'][] = dbConditionInt('f.triggerid', $options['triggerids']);
			$sqlParts['where']['hi'] = 'hi.hostid=i.hostid';
			$sqlParts['where']['fi'] = 'f.itemid=i.itemid';
		}

		// search
		if (is_array($options['search'])) {
			zbx_db_search('interface hi', $options, $sqlParts);
		}

		// filter
		if (is_array($options['filter'])) {
			$this->dbFilter('interface hi', $options, $sqlParts);
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$res = DBselect(self::createSelectQueryFromParts($sqlParts), $sqlParts['limit']);
		while ($interface = DBfetch($res)) {
			if ($options['countOutput']) {
				if ($options['groupCount']) {
					$result[] = $interface;
				}
				else {
					$result = $interface['rowscount'];
				}
			}
			else {
				$result[$interface['interfaceid']] = $interface;
			}
		}

		if ($options['countOutput']) {
			return $result;
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
			$result = $this->unsetExtraFields($result, ['hostid'], $options['output']);
		}

		// removing keys (hash -> array)
		if (!$options['preservekeys']) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * Check interfaces input.
	 *
	 * @param array  $interfaces
	 * @param string $method
	 */
	public function checkInput(array &$interfaces, $method) {
		$update = ($method == 'update');

		// permissions
		if ($update) {
			$interfaceDBfields = ['interfaceid' => null];
			$dbInterfaces = $this->get([
				'output' => API_OUTPUT_EXTEND,
				'interfaceids' => zbx_objectValues($interfaces, 'interfaceid'),
				'editable' => true,
				'preservekeys' => true
			]);
		}
		else {
			$interfaceDBfields = [
				'hostid' => null,
				'ip' => null,
				'dns' => null,
				'useip' => null,
				'port' => null,
				'main' => null
			];
		}

		$dbHosts = API::Host()->get([
			'output' => ['host'],
			'hostids' => zbx_objectValues($interfaces, 'hostid'),
			'editable' => true,
			'preservekeys' => true
		]);

		$dbProxies = API::Proxy()->get([
			'output' => ['host'],
			'proxyids' => zbx_objectValues($interfaces, 'hostid'),
			'editable' => true,
			'preservekeys' => true
		]);

		$check_have_items = [];
		foreach ($interfaces as &$interface) {
			if (!check_db_fields($interfaceDBfields, $interface)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect arguments passed to function.'));
			}

			if ($update) {
				if (!isset($dbInterfaces[$interface['interfaceid']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('No permissions to referred object or it does not exist!'));
				}

				$dbInterface = $dbInterfaces[$interface['interfaceid']];
				if (isset($interface['hostid']) && bccomp($dbInterface['hostid'], $interface['hostid']) != 0) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Cannot switch host for interface.'));
				}

				if (array_key_exists('type', $interface) && $interface['type'] != $dbInterface['type']) {
					$check_have_items[] = $interface['interfaceid'];
				}

				$interface['hostid'] = $dbInterface['hostid'];

				// we check all fields on "updated" interface
				$updInterface = $interface;
				$interface = zbx_array_merge($dbInterface, $interface);
			}
			else {
				if (!isset($dbHosts[$interface['hostid']]) && !isset($dbProxies[$interface['hostid']])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('No permissions to referred object or it does not exist!'));
				}

				if (isset($dbProxies[$interface['hostid']])) {
					$interface['type'] = INTERFACE_TYPE_UNKNOWN;
				}
				elseif (!isset($interface['type'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect arguments passed to method.'));
				}
			}

			if (zbx_empty($interface['ip']) && zbx_empty($interface['dns'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('IP and DNS cannot be empty for host interface.'));
			}

			if ($interface['useip'] == INTERFACE_USE_IP && zbx_empty($interface['ip'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s('Interface with DNS "%1$s" cannot have empty IP address.', $interface['dns']));
			}

			if ($interface['useip'] == INTERFACE_USE_DNS && zbx_empty($interface['dns'])) {
				if ($dbHosts && !empty($dbHosts[$interface['hostid']]['host'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Interface with IP "%1$s" cannot have empty DNS name while having "Use DNS" property on "%2$s".',
							$interface['ip'],
							$dbHosts[$interface['hostid']]['host']
					));
				}
				elseif ($dbProxies && !empty($dbProxies[$interface['hostid']]['host'])) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Interface with IP "%1$s" cannot have empty DNS name while having "Use DNS" property on "%2$s".',
							$interface['ip'],
							$dbProxies[$interface['hostid']]['host']
					));
				}
				else {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Interface with IP "%1$s" cannot have empty DNS name.', $interface['ip']));
				}
			}

			if (isset($interface['dns'])) {
				$this->checkDns($interface);
			}
			if (isset($interface['ip'])) {
				$this->checkIp($interface);
			}
			if (isset($interface['port']) || $method == 'create') {
				$this->checkPort($interface);
			}

			$this->checkBulk($interface);

			if ($update) {
				$interface = $updInterface;
			}
		}
		unset($interface);

		// check if any of the affected hosts are discovered
		if ($update) {
			$interfaces = $this->extendObjects('interface', $interfaces, ['hostid']);

			if ($check_have_items) {
				$this->checkIfInterfaceHasItems($check_have_items);
			}
		}
		$this->checkValidator(zbx_objectValues($interfaces, 'hostid'), new CHostNormalValidator([
			'message' => _('Cannot update interface for discovered host "%1$s".')
		]));
	}

	/**
	 * Add interfaces.
	 *
	 * @param array $interfaces multidimensional array with Interfaces data
	 *
	 * @return array
	 */
	public function create(array $interfaces) {
		$interfaces = zbx_toArray($interfaces);

		$this->checkInput($interfaces, __FUNCTION__);
		$this->checkMainInterfacesOnCreate($interfaces);

		$interfaceIds = DB::insert('interface', $interfaces);

		return ['interfaceids' => $interfaceIds];
	}

	/**
	 * Update interfaces.
	 *
	 * @param array $interfaces multidimensional array with Interfaces data
	 *
	 * @return array
	 */
	public function update(array $interfaces) {
		$interfaces = zbx_toArray($interfaces);

		$this->checkInput($interfaces, __FUNCTION__);
		$this->checkMainInterfacesOnUpdate($interfaces);

		$data = [];
		foreach ($interfaces as $interface) {
			$data[] = [
				'values' => $interface,
				'where' => ['interfaceid' => $interface['interfaceid']]
			];
		}
		DB::update('interface', $data);

		return ['interfaceids' => zbx_objectValues($interfaces, 'interfaceid')];
	}

	/**
	 * Delete interfaces.
	 * Interface cannot be deleted if it's main interface and exists other interface of same type on same host.
	 * Interface cannot be deleted if it is used in items.
	 *
	 * @param array $interfaceids
	 *
	 * @return array
	 */
	public function delete(array $interfaceids) {
		if (empty($interfaceids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter.'));
		}

		$dbInterfaces = $this->get([
			'output' => API_OUTPUT_EXTEND,
			'interfaceids' => $interfaceids,
			'editable' => true,
			'preservekeys' => true
		]);
		foreach ($interfaceids as $interfaceId) {
			if (!isset($dbInterfaces[$interfaceId])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('No permissions to referred object or it does not exist!'));
			}
		}

		$this->checkMainInterfacesOnDelete($interfaceids);

		DB::delete('interface', ['interfaceid' => $interfaceids]);

		return ['interfaceids' => $interfaceids];
	}

	public function massAdd(array $data) {
		$interfaces = zbx_toArray($data['interfaces']);
		$hosts = zbx_toArray($data['hosts']);

		$insertData = [];
		foreach ($interfaces as $interface) {
			foreach ($hosts as $host) {
				$newInterface = $interface;
				$newInterface['hostid'] = $host['hostid'];

				$insertData[] = $newInterface;
			}
		}

		$interfaceIds = $this->create($insertData);

		return ['interfaceids' => $interfaceIds];
	}

	protected function validateMassRemove(array $data) {
		// Check permissions.
		$this->checkHostPermissions($data['hostids']);

		// Check interfaces.
		$this->checkValidator($data['hostids'], new CHostNormalValidator([
			'message' => _('Cannot delete interface for discovered host "%1$s".')
		]));

		foreach ($data['interfaces'] as $interface) {
			if (!isset($interface['dns']) || !isset($interface['ip']) || !isset($interface['port'])) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect arguments passed to function.'));
			}

			$filter = [
				'hostid' => $data['hostids'],
				'ip' => $interface['ip'],
				'dns' => $interface['dns'],
				'port' => $interface['port']
			];

			if (array_key_exists('bulk', $interface)) {
				$filter['bulk'] = $interface['bulk'];
			}

			// check main interfaces
			$interfacesToRemove = API::getApiService()->select($this->tableName(), [
				'output' => ['interfaceid'],
				'filter' => $filter
			]);
			if ($interfacesToRemove) {
				$this->checkMainInterfacesOnDelete(zbx_objectValues($interfacesToRemove, 'interfaceid'));
			}
		}
	}

	/**
	 * Remove hosts from interfaces.
	 *
	 * @param array $data
	 * @param array $data['interfaceids']
	 * @param array $data['hostids']
	 * @param array $data['templateids']
	 *
	 * @return array
	 */
	public function massRemove(array $data) {
		$data['interfaces'] = zbx_toArray($data['interfaces']);
		$data['hostids'] = zbx_toArray($data['hostids']);

		$this->validateMassRemove($data);

		$interfaceIds = [];
		foreach ($data['interfaces'] as $interface) {
			$filter = [
				'hostid' => $data['hostids'],
				'ip' => $interface['ip'],
				'dns' => $interface['dns'],
				'port' => $interface['port']
			];
			if (array_key_exists('bulk', $interface)) {
				$filter['bulk'] = $interface['bulk'];
			}

			$interfaces = $this->get([
				'output' => ['interfaceid'],
				'filter' => $filter,
				'editable' => true,
				'preservekeys' => true
			]);

			if ($interfaces) {
				$interfaceIds = array_merge($interfaceIds, array_keys($interfaces));
			}
		}

		if ($interfaceIds) {
			$interfaceIds = array_keys(array_flip($interfaceIds));
			DB::delete('interface', ['interfaceid' => $interfaceIds]);
		}

		return ['interfaceids' => $interfaceIds];
	}

	/**
	 * Replace existing interfaces with input interfaces.
	 *
	 * @param array $host
	 */
	public function replaceHostInterfaces(array $host) {
		if (isset($host['interfaces']) && !is_null($host['interfaces'])) {
			$host['interfaces'] = zbx_toArray($host['interfaces']);

			$this->checkHostInterfaces($host['interfaces'], $host['hostid']);

			$interfacesToDelete = API::HostInterface()->get([
				'output' => [],
				'hostids' => $host['hostid'],
				'preservekeys' => true,
				'nopermissions' => true
			]);

			$interfacesToAdd = [];
			$interfacesToUpdate = [];

			foreach ($host['interfaces'] as $interface) {
				$interface['hostid'] = $host['hostid'];

				if (!isset($interface['interfaceid'])) {
					$interfacesToAdd[] = $interface;
				}
				elseif (isset($interfacesToDelete[$interface['interfaceid']])) {
					$interfacesToUpdate[] = $interface;
					unset($interfacesToDelete[$interface['interfaceid']]);
				}
			}

			if ($interfacesToUpdate) {
				API::HostInterface()->checkInput($interfacesToUpdate, 'update');

				$data = [];
				foreach ($interfacesToUpdate as $interface) {
					$data[] = [
						'values' => $interface,
						'where' => ['interfaceid' => $interface['interfaceid']]
					];
				}
				DB::update('interface', $data);
			}

			if ($interfacesToAdd) {
				$this->checkInput($interfacesToAdd, 'create');
				$interfaceids = DB::insert('interface', $interfacesToAdd);

				foreach ($host['interfaces'] as &$interface) {
					if (!array_key_exists('interfaceid', $interface)) {
						$interface['interfaceid'] = array_shift($interfaceids);
					}
				}
				unset($interface);
			}

			if ($interfacesToDelete) {
				$this->delete(zbx_objectValues($interfacesToDelete, 'interfaceid'));
			}

			return ['interfaceids' => zbx_objectValues($host['interfaces'], 'interfaceid')];
		}

		return ['interfaceids' => []];
	}

	/**
	 * Validates the "dns" field.
	 *
	 * @throws APIException if the field is invalid.
	 *
	 * @param array $interface
	 * @param string $interface['dns']
	 */
	protected function checkDns(array $interface) {
		if ($interface['dns'] === '') {
			return;
		}

		$user_macro_parser = new CUserMacroParser();

		if (!preg_match('/^'.ZBX_PREG_DNS_FORMAT.'$/', $interface['dns'])
				&& $user_macro_parser->parse($interface['dns']) != CParser::PARSE_SUCCESS) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Incorrect interface DNS parameter "%s" provided.', $interface['dns'])
			);
		}
	}

	/**
	 * Validates the "ip" field.
	 *
	 * @throws APIException if the field is invalid.
	 *
	 * @param array $interface
	 * @param string $interface['ip']
	 */
	protected function checkIp(array $interface) {
		if ($interface['ip'] === '') {
			return;
		}

		$user_macro_parser = new CUserMacroParser();

		if (preg_match('/^'.ZBX_PREG_MACRO_NAME_FORMAT.'$/', $interface['ip'])
				|| $user_macro_parser->parse($interface['ip']) == CParser::PARSE_SUCCESS) {
			return;
		}

		$ip_parser = new CIPParser(['v6' => ZBX_HAVE_IPV6]);

		if ($ip_parser->parse($interface['ip']) != CParser::PARSE_SUCCESS) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Invalid IP address "%1$s".', $interface['ip']));
		}
	}

	/**
	 * Validates the "port" field.
	 *
	 * @throws APIException if the field is empty or invalid.
	 *
	 * @param array $interface
	 */
	protected function checkPort(array $interface) {
		if (!isset($interface['port']) || zbx_empty($interface['port'])) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Port cannot be empty for host interface.'));
		}
		elseif (!validatePortNumberOrMacro($interface['port'])) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _s('Incorrect interface port "%s" provided.', $interface['port']));
		}
	}

	/**
	 * Checks if the current user has access to the given hosts. Assumes the "hostid" field is valid.
	 *
	 * @throws APIException if the user doesn't have write permissions for the given hosts
	 *
	 * @param array $hostIds	an array of host IDs
	 */
	protected function checkHostPermissions(array $hostids) {
		if ($hostids) {
			$hostids = array_unique($hostids);

			$count = API::Host()->get([
				'countOutput' => true,
				'hostids' => $hostids,
				'editable' => true
			]);

			if ($count != count($hostids)) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_('No permissions to referred object or it does not exist!')
				);
			}
		}
	}

	/**
	 * Validates interface "bulk" field.
	 * For SNMP interfaces bulk value should be either 0 (disabled) or 1 (enabled).
	 * For other non-SNMP interfaces bulk value should be 1 (default).
	 *
	 * @throws APIException if bulk field is incorrect.
	 *
	 * @param array $interface
	 */
	protected function checkBulk(array $interface) {
		if ($interface['type'] !== null && (($interface['type'] != INTERFACE_TYPE_SNMP && isset($interface['bulk'])
				&& $interface['bulk'] != SNMP_BULK_ENABLED)
				|| ($interface['type'] == INTERFACE_TYPE_SNMP && isset($interface['bulk'])
					&& (zbx_empty($interface['bulk'])
						|| ($interface['bulk'] != SNMP_BULK_DISABLED && $interface['bulk'] != SNMP_BULK_ENABLED))))) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect bulk value for interface.'));
		}
	}

	private function checkHostInterfaces(array $interfaces, $hostid) {
		$interfaces_with_missing_data = [];

		foreach ($interfaces as $interface) {
			if (array_key_exists('interfaceid', $interface)) {
				if (!array_key_exists('type', $interface) || !array_key_exists('main', $interface)) {
					$interfaces_with_missing_data[$interface['interfaceid']] = true;
				}
			}
			elseif (!array_key_exists('type', $interface) || !array_key_exists('main', $interface)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Incorrect arguments passed to function.'));
			}
		}

		if ($interfaces_with_missing_data) {
			$dbInterfaces = API::HostInterface()->get([
				'interfaceids' => array_keys($interfaces_with_missing_data),
				'output' => ['main', 'type'],
				'preservekeys' => true,
				'nopermissions' => true
			]);
			if (count($interfaces_with_missing_data) != count($dbInterfaces)) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_('No permissions to referred object or it does not exist!')
				);
			}
		}

		foreach ($interfaces as $id => $interface) {
			if (isset($interface['interfaceid']) && isset($dbInterfaces[$interface['interfaceid']])) {
				$interfaces[$id] = array_merge($interface, $dbInterfaces[$interface['interfaceid']]);
			}
			$interfaces[$id]['hostid'] = $hostid;
		}

		$this->checkMainInterfaces($interfaces);
	}

	private function checkMainInterfacesOnCreate(array $interfaces) {
		$hostIds = [];
		foreach ($interfaces as $interface) {
			$hostIds[$interface['hostid']] = $interface['hostid'];
		}

		$dbInterfaces = API::HostInterface()->get([
			'hostids' => $hostIds,
			'output' => ['hostid', 'main', 'type'],
			'preservekeys' => true,
			'nopermissions' => true
		]);
		$interfaces = array_merge($dbInterfaces, $interfaces);

		$this->checkMainInterfaces($interfaces);
	}

	/**
	 * Prepares data to validate main interface for every interface type. Executes main interface validation.
	 *
	 * @param array $interfaces                     Array of interfaces to validate.
	 * @param int   $interfaces[]['hostid']         Updated interface's hostid.
	 * @param int   $interfaces[]['interfaceid']    Updated interface's interfaceid.
	 *
	 * @throws APIException
	 */
	private function checkMainInterfacesOnUpdate(array $interfaces) {
		$hostids = array_keys(array_flip(zbx_objectValues($interfaces, 'hostid')));

		$dbInterfaces = API::HostInterface()->get([
			'hostids' => $hostids,
			'output' => ['hostid', 'main', 'type'],
			'preservekeys' => true,
			'nopermissions' => true
		]);

		// update interfaces from DB with data that will be updated.
		foreach ($interfaces as $interface) {
			if (isset($dbInterfaces[$interface['interfaceid']])) {
				$dbInterfaces[$interface['interfaceid']] = array_merge(
					$dbInterfaces[$interface['interfaceid']],
					$interface
				);
			}
		}

		$this->checkMainInterfaces($dbInterfaces);
	}

	private function checkMainInterfacesOnDelete(array $interfaceIds) {
		$this->checkIfInterfaceHasItems($interfaceIds);

		$hostids = [];
		$dbResult = DBselect('SELECT DISTINCT i.hostid FROM interface i WHERE '.dbConditionInt('i.interfaceid', $interfaceIds));
		while ($hostData = DBfetch($dbResult)) {
			$hostids[$hostData['hostid']] = $hostData['hostid'];
		}

		$interfaces = API::HostInterface()->get([
			'hostids' => $hostids,
			'output' => ['hostid', 'main', 'type'],
			'preservekeys' => true,
			'nopermissions' => true
		]);
		$db_interfaces = $interfaces;

		foreach ($interfaceIds as $interfaceId) {
			unset($interfaces[$interfaceId]);
		}

		$this->checkMainInterfaces($interfaces, $db_interfaces);
	}

	/**
	 * Check if main interfaces are correctly set for every interface type. Each host must either have only one main
	 * interface for each interface type, or have no interface of that type at all. If no interfaces are given, it means
	 * the last remaining main interface is trying to be deleted. In that case use $db_interfaces as reference.
	 *
	 * @param array $interfaces     Array of interfaces that are created, updated (plus DB) or deleted (plus DB).
	 * @param array $db_interfaces  Array of interfaces from DB (used for delete only and if no interfaces are given).
	 */
	private function checkMainInterfaces(array $interfaces, array $db_interfaces = []) {
		if (!$interfaces && $db_interfaces) {
			$host = API::Host()->get([
				'output' => ['name', 'hostid'],
				'hostids' => zbx_objectValues($db_interfaces, 'hostid'),
				'preservekeys' => true,
				'nopermissions' => true
			]);
			$host = reset($host);

			if ($host) {
				foreach ($db_interfaces as $db_interface) {
					if (bccomp($db_interface['hostid'], $host['hostid']) == 0) {
						$type = $db_interface['type'];
						break;
					}
				}

				self::exception(ZBX_API_ERROR_PARAMETERS, _s(
					'No default interface for "%1$s" type on "%2$s".', hostInterfaceTypeNumToName($type), $host['name']
				));
			}
			// Otherwise it's not a host. Could be a Proxy.
		}

		$interface_count = [];

		if ($db_interfaces) {
			foreach ($db_interfaces as $db_interface) {
				$hostid = $db_interface['hostid'];
				$type = $db_interface['type'];

				if (!array_key_exists($hostid, $interface_count)) {
					$interface_count[$hostid] = [];
				}

				if (!array_key_exists($type, $interface_count[$hostid])) {
					$interface_count[$hostid][$type] = ['main' => 0, 'all' => 0];
				}
			}
		}

		foreach ($interfaces as $interface) {
			$hostid = $interface['hostid'];
			$type = $interface['type'];

			if (!array_key_exists($hostid, $interface_count)) {
				$interface_count[$hostid] = [];
			}

			if (!array_key_exists($type, $interface_count[$hostid])) {
				$interface_count[$hostid][$type] = ['main' => 0, 'all' => 0];
			}

			if ($interface['main'] == INTERFACE_PRIMARY) {
				$interface_count[$hostid][$type]['main']++;
			}
			else {
				$interface_count[$hostid][$type]['all']++;
			}
		}

		$main_interface_count = [];
		$all_interface_count = [];

		foreach ($interface_count as $hostid => $interface_type) {
			foreach ($interface_type as $type => $counters) {
				if (!array_key_exists($hostid, $main_interface_count)) {
					$main_interface_count[$hostid] = 0;
				}

				$main_interface_count[$hostid] += $counters['main'];

				if (!array_key_exists($hostid, $all_interface_count)) {
					$all_interface_count[$hostid] = 0;
				}

				$all_interface_count[$hostid] += $counters['all'];
			}
		}

		foreach ($interface_count as $hostid => $interface_type) {
			foreach ($interface_type as $type => $counters) {
				if (($counters['all'] > 0 && $counters['main'] == 0)
						|| ($main_interface_count[$hostid] == 0 && $all_interface_count[$hostid] == 0)) {
					$host = API::Host()->get([
						'output' => ['name'],
						'hostids' => $hostid,
						'preservekeys' => true,
						'nopermissions' => true
					]);
					$host = reset($host);

					if ($host) {
						self::exception(ZBX_API_ERROR_PARAMETERS,_s('No default interface for "%1$s" type on "%2$s".',
							hostInterfaceTypeNumToName($type), $host['name']
						));
					}
					// Otherwise it's not a host. Could be a Proxy.
				}

				if ($counters['main'] > 1) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_('Host cannot have more than one default interface of the same type.')
					);
				}
			}
		}
	}

	private function checkIfInterfaceHasItems(array $interfaceIds) {
		$items = API::Item()->get([
			'output' => ['name'],
			'selectHosts' => ['name'],
			'interfaceids' => $interfaceIds,
			'preservekeys' => true,
			'nopermissions' => true,
			'limit' => 1
		]);

		foreach ($items as $item) {
			$host = reset($item['hosts']);

			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Interface is linked to item "%1$s" on "%2$s".', $item['name'], $host['name']));
		}
	}

	protected function applyQueryOutputOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryOutputOptions($tableName, $tableAlias, $options, $sqlParts);

		if (!$options['countOutput'] && $options['selectHosts'] !== null) {
			$sqlParts = $this->addQuerySelect('hi.hostid', $sqlParts);
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$interfaceIds = array_keys($result);

		// adding hosts
		if ($options['selectHosts'] !== null && $options['selectHosts'] != API_OUTPUT_COUNT) {
			$relationMap = $this->createRelationMap($result, 'interfaceid', 'hostid');
			$hosts = API::Host()->get([
				'output' => $options['selectHosts'],
				'hosts' => $relationMap->getRelatedIds(),
				'preservekeys' => true
			]);
			$result = $relationMap->mapMany($result, $hosts, 'hosts');
		}

		// adding items
		if ($options['selectItems'] !== null) {
			if ($options['selectItems'] != API_OUTPUT_COUNT) {
				$items = API::Item()->get([
					'output' => $this->outputExtend($options['selectItems'], ['itemid', 'interfaceid']),
					'interfaceids' => $interfaceIds,
					'nopermissions' => true,
					'preservekeys' => true,
					'filter' => ['flags' => null]
				]);
				$relationMap = $this->createRelationMap($items, 'interfaceid', 'itemid');

				$items = $this->unsetExtraFields($items, ['interfaceid', 'itemid'], $options['selectItems']);
				$result = $relationMap->mapMany($result, $items, 'items', $options['limitSelects']);
			}
			else {
				$items = API::Item()->get([
					'interfaceids' => $interfaceIds,
					'nopermissions' => true,
					'filter' => ['flags' => null],
					'countOutput' => true,
					'groupCount' => true
				]);
				$items = zbx_toHash($items, 'interfaceid');
				foreach ($result as $interfaceid => $interface) {
					$result[$interfaceid]['items'] = array_key_exists($interfaceid, $items)
						? $items[$interfaceid]['rowscount']
						: '0';
				}
			}
		}

		return $result;
	}
}
