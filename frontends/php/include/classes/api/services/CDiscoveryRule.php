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
 * Class containing methods for operations with discovery rules.
 */
class CDiscoveryRule extends CItemGeneral {

	protected $tableName = 'items';
	protected $tableAlias = 'i';
	protected $sortColumns = ['itemid', 'name', 'key_', 'delay', 'type', 'status'];

	/**
	 * Define a set of supported pre-processing rules.
	 *
	 * @var array
	 *
	 * 5.6 would allow this to be defined constant.
	 */
	public static $supported_preprocessing_types = [ZBX_PREPROC_REGSUB, ZBX_PREPROC_JSONPATH,
		ZBX_PREPROC_VALIDATE_NOT_REGEX, ZBX_PREPROC_ERROR_FIELD_JSON, ZBX_PREPROC_THROTTLE_TIMED_VALUE,
		ZBX_PREPROC_SCRIPT, ZBX_PREPROC_PROMETHEUS_TO_JSON, ZBX_PREPROC_XPATH, ZBX_PREPROC_ERROR_FIELD_XML,
		ZBX_PREPROC_CSV_TO_JSON
	];

	public function __construct() {
		parent::__construct();

		$this->errorMessages = array_merge($this->errorMessages, [
			self::ERROR_EXISTS_TEMPLATE => _('Discovery rule "%1$s" already exists on "%2$s", inherited from another template.'),
			self::ERROR_EXISTS => _('Discovery rule "%1$s" already exists on "%2$s".'),
			self::ERROR_INVALID_KEY => _('Invalid key "%1$s" for discovery rule "%2$s" on "%3$s": %4$s.')
		]);
	}

	/**
	 * Get DiscoveryRule data
	 */
	public function get($options = []) {
		$result = [];

		$sqlParts = [
			'select'	=> ['items' => 'i.itemid'],
			'from'		=> ['items' => 'items i'],
			'where'		=> ['i.flags='.ZBX_FLAG_DISCOVERY_RULE],
			'group'		=> [],
			'order'		=> [],
			'limit'		=> null
		];

		$defOptions = [
			'groupids'						=> null,
			'templateids'					=> null,
			'hostids'						=> null,
			'itemids'						=> null,
			'interfaceids'					=> null,
			'inherited'						=> null,
			'templated'						=> null,
			'monitored'						=> null,
			'editable'						=> false,
			'nopermissions'					=> null,
			// filter
			'filter'						=> null,
			'search'						=> null,
			'searchByAny'					=> null,
			'startSearch'					=> false,
			'excludeSearch'					=> false,
			'searchWildcardsEnabled'		=> null,
			// output
			'output'						=> API_OUTPUT_EXTEND,
			'selectHosts'					=> null,
			'selectItems'					=> null,
			'selectTriggers'				=> null,
			'selectGraphs'					=> null,
			'selectHostPrototypes'			=> null,
			'selectApplicationPrototypes'	=> null,
			'selectFilter'					=> null,
			'selectLLDMacroPaths'			=> null,
			'selectPreprocessing'			=> null,
			'countOutput'					=> false,
			'groupCount'					=> false,
			'preservekeys'					=> false,
			'sortfield'						=> '',
			'sortorder'						=> '',
			'limit'							=> null,
			'limitSelects'					=> null
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
				' WHERE i.hostid=hgg.hostid'.
				' GROUP BY hgg.hostid'.
				' HAVING MIN(r.permission)>'.PERM_DENY.
					' AND MAX(r.permission)>='.zbx_dbstr($permission).
				')';
		}

		// templateids
		if (!is_null($options['templateids'])) {
			zbx_value2array($options['templateids']);

			if (!is_null($options['hostids'])) {
				zbx_value2array($options['hostids']);
				$options['hostids'] = array_merge($options['hostids'], $options['templateids']);
			}
			else {
				$options['hostids'] = $options['templateids'];
			}
		}

		// hostids
		if (!is_null($options['hostids'])) {
			zbx_value2array($options['hostids']);

			$sqlParts['where']['hostid'] = dbConditionInt('i.hostid', $options['hostids']);

			if ($options['groupCount']) {
				$sqlParts['group']['i'] = 'i.hostid';
			}
		}

		// itemids
		if (!is_null($options['itemids'])) {
			zbx_value2array($options['itemids']);

			$sqlParts['where']['itemid'] = dbConditionInt('i.itemid', $options['itemids']);
		}

		// interfaceids
		if (!is_null($options['interfaceids'])) {
			zbx_value2array($options['interfaceids']);

			$sqlParts['where']['interfaceid'] = dbConditionId('i.interfaceid', $options['interfaceids']);

			if ($options['groupCount']) {
				$sqlParts['group']['i'] = 'i.interfaceid';
			}
		}

		// inherited
		if (!is_null($options['inherited'])) {
			if ($options['inherited']) {
				$sqlParts['where'][] = 'i.templateid IS NOT NULL';
			}
			else {
				$sqlParts['where'][] = 'i.templateid IS NULL';
			}
		}

		// templated
		if (!is_null($options['templated'])) {
			$sqlParts['from']['hosts'] = 'hosts h';
			$sqlParts['where']['hi'] = 'h.hostid=i.hostid';

			if ($options['templated']) {
				$sqlParts['where'][] = 'h.status='.HOST_STATUS_TEMPLATE;
			}
			else {
				$sqlParts['where'][] = 'h.status<>'.HOST_STATUS_TEMPLATE;
			}
		}

		// monitored
		if (!is_null($options['monitored'])) {
			$sqlParts['from']['hosts'] = 'hosts h';
			$sqlParts['where']['hi'] = 'h.hostid=i.hostid';

			if ($options['monitored']) {
				$sqlParts['where'][] = 'h.status='.HOST_STATUS_MONITORED;
				$sqlParts['where'][] = 'i.status='.ITEM_STATUS_ACTIVE;
			}
			else {
				$sqlParts['where'][] = '(h.status<>'.HOST_STATUS_MONITORED.' OR i.status<>'.ITEM_STATUS_ACTIVE.')';
			}
		}

		// search
		if (is_array($options['search'])) {
			if (array_key_exists('error', $options['search']) && $options['search']['error'] !== null) {
				zbx_db_search('item_rtdata ir', ['search' => ['error' => $options['search']['error']]] + $options,
					$sqlParts
				);
			}

			zbx_db_search('items i', $options, $sqlParts);
		}

		// filter
		if (is_array($options['filter'])) {
			if (array_key_exists('delay', $options['filter']) && $options['filter']['delay'] !== null) {
				$sqlParts['where'][] = makeUpdateIntervalFilter('i.delay', $options['filter']['delay']);
				unset($options['filter']['delay']);
			}

			if (array_key_exists('lifetime', $options['filter']) && $options['filter']['lifetime'] !== null) {
				$options['filter']['lifetime'] = getTimeUnitFilters($options['filter']['lifetime']);
			}

			if (array_key_exists('state', $options['filter']) && $options['filter']['state'] !== null) {
				$this->dbFilter('item_rtdata ir', ['filter' => ['state' => $options['filter']['state']]] + $options,
					$sqlParts
				);
			}

			$this->dbFilter('items i', $options, $sqlParts);

			if (isset($options['filter']['host'])) {
				zbx_value2array($options['filter']['host']);

				$sqlParts['from']['hosts'] = 'hosts h';
				$sqlParts['where']['hi'] = 'h.hostid=i.hostid';
				$sqlParts['where']['h'] = dbConditionString('h.host', $options['filter']['host']);
			}
		}

		// limit
		if (zbx_ctype_digit($options['limit']) && $options['limit']) {
			$sqlParts['limit'] = $options['limit'];
		}

		$sqlParts = $this->applyQueryOutputOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$sqlParts = $this->applyQuerySortOptions($this->tableName(), $this->tableAlias(), $options, $sqlParts);
		$res = DBselect($this->createSelectQueryFromParts($sqlParts), $sqlParts['limit']);
		while ($item = DBfetch($res)) {
			if (!$options['countOutput']) {
				$result[$item['itemid']] = $item;
				continue;
			}

			if ($options['groupCount']) {
				$result[] = $item;
			}
			else {
				$result = $item['rowscount'];
			}
		}

		if ($options['countOutput']) {
			return $result;
		}

		if ($result) {
			$result = $this->addRelatedObjects($options, $result);
			$result = $this->unsetExtraFields($result, ['hostid'], $options['output']);

			foreach ($result as &$rule) {
				// unset the fields that are returned in the filter
				unset($rule['formula'], $rule['evaltype']);

				if ($options['selectFilter'] !== null) {
					$filter = $this->unsetExtraFields([$rule['filter']],
						['conditions', 'formula', 'evaltype'],
						$options['selectFilter']
					);
					$filter = reset($filter);
					if (isset($filter['conditions'])) {
						foreach ($filter['conditions'] as &$condition) {
							unset($condition['item_conditionid'], $condition['itemid']);
						}
						unset($condition);
					}

					$rule['filter'] = $filter;
				}
			}
			unset($rule);
		}

		// Decode ITEM_TYPE_HTTPAGENT encoded fields.
		$json = new CJson();

		foreach ($result as &$item) {
			if (array_key_exists('query_fields', $item)) {
				$query_fields = ($item['query_fields'] !== '') ? $json->decode($item['query_fields'], true) : [];
				$item['query_fields'] = $json->hasError() ? [] : $query_fields;
			}

			if (array_key_exists('headers', $item)) {
				$item['headers'] = $this->headersStringToArray($item['headers']);
			}

			// Option 'Convert to JSON' is not supported for discovery rule.
			unset($item['output_format']);
		}

		if (!$options['preservekeys']) {
			$result = zbx_cleanHashes($result);
		}

		return $result;
	}

	/**
	 * Add DiscoveryRule.
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public function create($items) {
		$items = zbx_toArray($items);
		$this->checkInput($items);
		$json = new CJson();

		foreach ($items as &$item) {
			if ($item['type'] == ITEM_TYPE_HTTPAGENT) {
				if (array_key_exists('query_fields', $item)) {
					$item['query_fields'] = $item['query_fields'] ? $json->encode($item['query_fields']) : '';
				}

				if (array_key_exists('headers', $item)) {
					$item['headers'] = $this->headersArrayToString($item['headers']);
				}

				if (array_key_exists('request_method', $item) && $item['request_method'] == HTTPCHECK_REQUEST_HEAD
						&& !array_key_exists('retrieve_mode', $item)) {
					$item['retrieve_mode'] = HTTPTEST_STEP_RETRIEVE_MODE_HEADERS;
				}
			}
			else {
				$item['query_fields'] = '';
				$item['headers'] = '';
			}

			// Option 'Convert to JSON' is not supported for discovery rule.
			unset($item['itemid'], $item['output_format']);
		}
		unset($item);

		// Get only hosts not templates from items
		$hosts = API::Host()->get([
			'output' => [],
			'hostids' => zbx_objectValues($items, 'hostid'),
			'preservekeys' => true
		]);
		foreach ($items as &$item) {
			if (array_key_exists($item['hostid'], $hosts)) {
				$item['rtdata'] = true;
			}
		}
		unset($item);

		$this->validateCreateLLDMacroPaths($items);
		$this->validateDependentItems($items);
		$this->createReal($items);
		$this->inherit($items);

		return ['itemids' => zbx_objectValues($items, 'itemid')];
	}

	/**
	 * Update DiscoveryRule.
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public function update($items) {
		$items = zbx_toArray($items);

		$db_items = $this->get([
			'output' => ['itemid', 'name', 'type', 'master_itemid', 'authtype', 'allow_traps', 'retrieve_mode'],
			'selectFilter' => ['evaltype', 'formula', 'conditions'],
			'itemids' => zbx_objectValues($items, 'itemid'),
			'preservekeys' => true
		]);

		$this->checkInput($items, true, $db_items);
		$this->validateUpdateLLDMacroPaths($items);

		$items = $this->extendFromObjects(zbx_toHash($items, 'itemid'), $db_items, ['flags', 'type', 'authtype',
			'master_itemid'
		]);
		$this->validateDependentItems($items);

		$defaults = DB::getDefaults('items');
		$clean = [
			ITEM_TYPE_HTTPAGENT => [
				'url' => '',
				'query_fields' => '',
				'timeout' => $defaults['timeout'],
				'status_codes' => $defaults['status_codes'],
				'follow_redirects' => $defaults['follow_redirects'],
				'request_method' => $defaults['request_method'],
				'allow_traps' => $defaults['allow_traps'],
				'post_type' => $defaults['post_type'],
				'http_proxy' => '',
				'headers' => '',
				'retrieve_mode' => $defaults['retrieve_mode'],
				'output_format' => $defaults['output_format'],
				'ssl_key_password' => '',
				'verify_peer' => $defaults['verify_peer'],
				'verify_host' => $defaults['verify_host'],
				'ssl_cert_file' => '',
				'ssl_key_file' => '',
				'posts' => ''
			]
		];

		$json = new CJson();

		// set the default values required for updating
		foreach ($items as &$item) {
			$type_change = (array_key_exists('type', $item) && $item['type'] != $db_items[$item['itemid']]['type']);

			if (isset($item['filter'])) {
				foreach ($item['filter']['conditions'] as &$condition) {
					$condition += [
						'operator' => DB::getDefault('item_condition', 'operator')
					];
				}
				unset($condition);
			}

			if ($type_change && $db_items[$item['itemid']]['type'] == ITEM_TYPE_HTTPAGENT) {
				$item = array_merge($item, $clean[ITEM_TYPE_HTTPAGENT]);

				if ($item['type'] != ITEM_TYPE_SSH) {
					$item['authtype'] = $defaults['authtype'];
					$item['username'] = '';
					$item['password'] = '';
				}

				if ($item['type'] != ITEM_TYPE_TRAPPER) {
					$item['trapper_hosts'] = '';
				}
			}

			if ($item['type'] == ITEM_TYPE_HTTPAGENT) {
				// Clean username and password when authtype is set to HTTPTEST_AUTH_NONE.
				if ($item['authtype'] == HTTPTEST_AUTH_NONE) {
					$item['username'] = '';
					$item['password'] = '';
				}

				if (array_key_exists('allow_traps', $item) && $item['allow_traps'] == HTTPCHECK_ALLOW_TRAPS_OFF
						&& $item['allow_traps'] != $db_items[$item['itemid']]['allow_traps']) {
					$item['trapper_hosts'] = '';
				}

				if (array_key_exists('query_fields', $item) && is_array($item['query_fields'])) {
					$item['query_fields'] = $item['query_fields'] ? $json->encode($item['query_fields']) : '';
				}

				if (array_key_exists('headers', $item) && is_array($item['headers'])) {
					$item['headers'] = $this->headersArrayToString($item['headers']);
				}

				if (array_key_exists('request_method', $item) && $item['request_method'] == HTTPCHECK_REQUEST_HEAD
						&& !array_key_exists('retrieve_mode', $item)
						&& $db_items[$item['itemid']]['retrieve_mode'] != HTTPTEST_STEP_RETRIEVE_MODE_HEADERS) {
					$item['retrieve_mode'] = HTTPTEST_STEP_RETRIEVE_MODE_HEADERS;
				}
			}
			else {
				$item['query_fields'] = '';
				$item['headers'] = '';
			}

			// Option 'Convert to JSON' is not supported for discovery rule.
			unset($item['output_format']);
		}
		unset($item);

		// update
		$this->updateReal($items);
		$this->inherit($items);

		return ['itemids' => zbx_objectValues($items, 'itemid')];
	}

	/**
	 * Delete DiscoveryRules.
	 *
	 * @param array $ruleids
	 * @param bool  $nopermissions
	 *
	 * @return array
	 */
	public function delete(array $ruleids, $nopermissions = false) {
		if (empty($ruleids)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Empty input parameter.'));
		}

		$ruleids = array_keys(array_flip($ruleids));

		$delRules = $this->get([
			'output' => API_OUTPUT_EXTEND,
			'itemids' => $ruleids,
			'editable' => true,
			'preservekeys' => true
		]);

		// TODO: remove $nopermissions hack
		if (!$nopermissions) {
			foreach ($ruleids as $ruleid) {
				if (!isset($delRules[$ruleid])) {
					self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
				}
				if ($delRules[$ruleid]['templateid'] != 0) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot delete templated items.'));
				}
			}
		}

		// get child discovery rules
		$parentItemids = $ruleids;
		$childTuleids = [];
		do {
			$dbItems = DBselect('SELECT i.itemid FROM items i WHERE '.dbConditionInt('i.templateid', $parentItemids));
			$parentItemids = [];
			while ($dbItem = DBfetch($dbItems)) {
				$parentItemids[$dbItem['itemid']] = $dbItem['itemid'];
				$childTuleids[$dbItem['itemid']] = $dbItem['itemid'];
			}
		} while (!empty($parentItemids));

		$delRulesChildren = $this->get([
			'output' => API_OUTPUT_EXTEND,
			'itemids' => $childTuleids,
			'nopermissions' => true,
			'preservekeys' => true
		]);

		$delRules = array_merge($delRules, $delRulesChildren);
		$ruleids = array_merge($ruleids, $childTuleids);

		$iprototypeids = [];
		$dbItems = DBselect(
			'SELECT i.itemid'.
			' FROM item_discovery id,items i'.
			' WHERE i.itemid=id.itemid'.
				' AND '.dbConditionInt('parent_itemid', $ruleids)
		);
		while ($item = DBfetch($dbItems)) {
			$iprototypeids[$item['itemid']] = $item['itemid'];
		}
		if ($iprototypeids) {
			CItemPrototypeManager::delete($iprototypeids);
		}

		// delete host prototypes
		$hostPrototypeIds = DBfetchColumn(DBselect(
			'SELECT hd.hostid'.
			' FROM host_discovery hd'.
			' WHERE '.dbConditionInt('hd.parent_itemid', $ruleids)
		), 'hostid');
		if ($hostPrototypeIds) {
			if (!API::HostPrototype()->delete($hostPrototypeIds, true)) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot delete host prototype.'));
			}
		}

		// delete LLD rules
		DB::delete('items', ['itemid' => $ruleids]);

		$insert = [];
		foreach ($ruleids as $ruleid) {
			$insert[] = [
				'tablename' => 'events',
				'field' => 'lldruleid',
				'value' => $ruleid
			];
		}
		DB::insertBatch('housekeeper', $insert);

		return ['ruleids' => $ruleids];
	}

	/**
	 * Checks if the current user has access to the given hosts and templates. Assumes the "hostid" field is valid.
	 *
	 * @param array $hostids    an array of host or template IDs
	 *
	 * @throws APIException if the user doesn't have write permissions for the given hosts.
	 */
	protected function checkHostPermissions(array $hostids) {
		if ($hostids) {
			$hostids = array_unique($hostids);

			$count = API::Host()->get([
				'countOutput' => true,
				'hostids' => $hostids,
				'editable' => true
			]);

			if ($count == count($hostids)) {
				return;
			}

			$count += API::Template()->get([
				'countOutput' => true,
				'templateids' => $hostids,
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
	 * Copies the given discovery rules to the specified hosts.
	 *
	 * @throws APIException if no discovery rule IDs or host IDs are given or
	 * the user doesn't have the necessary permissions.
	 *
	 * @param array $data
	 * @param array $data['discoveryids']  An array of item ids to be cloned.
	 * @param array $data['hostids']       An array of host ids were the items should be cloned to.
	 *
	 * @return bool
	 */
	public function copy(array $data) {
		// validate data
		if (!isset($data['discoveryids']) || !$data['discoveryids']) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('No discovery rule IDs given.'));
		}
		if (!isset($data['hostids']) || !$data['hostids']) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('No host IDs given.'));
		}

		$this->checkHostPermissions($data['hostids']);

		// check if the given discovery rules exist
		$count = $this->get([
			'countOutput' => true,
			'itemids' => $data['discoveryids']
		]);

		if ($count != count($data['discoveryids'])) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		// copy
		foreach ($data['discoveryids'] as $discoveryid) {
			foreach ($data['hostids'] as $hostid) {
				$this->copyDiscoveryRule($discoveryid, $hostid);
			}
		}

		return true;
	}

	public function syncTemplates($data) {
		$data['templateids'] = zbx_toArray($data['templateids']);
		$data['hostids'] = zbx_toArray($data['hostids']);

		$output = [];
		foreach ($this->fieldRules as $field_name => $rules) {
			if (!array_key_exists('system', $rules) && !array_key_exists('host', $rules)) {
				$output[] = $field_name;
			}
		}

		$tpl_items = $this->get([
			'output' => $output,
			'hostids' => $data['templateids'],
			'selectFilter' => ['formula', 'evaltype', 'conditions'],
			'selectLLDMacroPaths' => ['lld_macro', 'path'],
			'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
			'preservekeys' => true
		]);
		$json = new CJson();

		foreach ($tpl_items as &$item) {
			if ($item['type'] == ITEM_TYPE_HTTPAGENT) {
				if (array_key_exists('query_fields', $item) && is_array($item['query_fields'])) {
					$item['query_fields'] = $item['query_fields'] ? $json->encode($item['query_fields']) : '';
				}

				if (array_key_exists('headers', $item) && is_array($item['headers'])) {
					$item['headers'] = $this->headersArrayToString($item['headers']);
				}
			}
			else {
				$item['query_fields'] = '';
				$item['headers'] = '';
			}

			// Option 'Convert to JSON' is not supported for discovery rule.
			unset($item['output_format']);
		}
		unset($item);

		$this->inherit($tpl_items, $data['hostids']);

		return true;
	}

	/**
	 * Copies all of the triggers from the source discovery to the target discovery rule.
	 *
	 * @throws APIException if trigger saving fails
	 *
	 * @param array $srcDiscovery    The source discovery rule to copy from
	 * @param array $srcHost         The host the source discovery belongs to
	 * @param array $dstHost         The host the target discovery belongs to
	 *
	 * @return array
	 */
	protected function copyTriggerPrototypes(array $srcDiscovery, array $srcHost, array $dstHost) {
		$srcTriggers = API::TriggerPrototype()->get([
			'discoveryids' => $srcDiscovery['itemid'],
			'output' => ['triggerid', 'expression', 'description', 'url', 'status', 'priority', 'comments',
				'templateid', 'type', 'recovery_mode', 'recovery_expression', 'correlation_mode', 'correlation_tag',
				'opdata'
			],
			'selectHosts' => API_OUTPUT_EXTEND,
			'selectItems' => ['itemid', 'type'],
			'selectDiscoveryRule' => API_OUTPUT_EXTEND,
			'selectFunctions' => API_OUTPUT_EXTEND,
			'selectDependencies' => ['triggerid'],
			'selectTags' => ['tag', 'value'],
			'preservekeys' => true
		]);

		foreach ($srcTriggers as $id => $trigger) {
			// Skip trigger prototypes with web items and remove them from source.
			if (httpItemExists($trigger['items'])) {
				unset($srcTriggers[$id]);
			}
		}

		if (!$srcTriggers) {
			return [];
		}

		/*
		 * Copy the remaining trigger prototypes to a new source. These will contain IDs and original dependencies.
		 * The dependencies from $srcTriggers will be removed.
		 */
		$trigger_prototypes = $srcTriggers;

		// Contains original trigger prototype dependency IDs.
		$dep_triggerids = [];

		/*
		 * Collect dependency trigger IDs and remove them from source. Otherwise these IDs do not pass
		 * validation, since they don't belong to destination discovery rule.
		 */
		$add_dependencies = false;

		foreach ($srcTriggers as $id => &$trigger) {
			if ($trigger['dependencies']) {
				foreach ($trigger['dependencies'] as $dep_trigger) {
					$dep_triggerids[] = $dep_trigger['triggerid'];
				}
				$add_dependencies = true;
			}
			unset($trigger['dependencies']);
		}
		unset($trigger);

		// Save new trigger prototypes and without dependencies for now.
		$dstTriggers = $srcTriggers;
		$dstTriggers = CMacrosResolverHelper::resolveTriggerExpressions($dstTriggers,
			['sources' => ['expression', 'recovery_expression']]
		);
		foreach ($dstTriggers as $id => &$trigger) {
			unset($trigger['triggerid'], $trigger['templateid']);

			// Update the destination expressions.
			$trigger['expression'] = triggerExpressionReplaceHost($trigger['expression'], $srcHost['host'],
				$dstHost['host']
			);
			if ($trigger['recovery_mode'] == ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION) {
				$trigger['recovery_expression'] = triggerExpressionReplaceHost($trigger['recovery_expression'],
					$srcHost['host'], $dstHost['host']
				);
			}
		}
		unset($trigger);

		$result = API::TriggerPrototype()->create($dstTriggers);
		if (!$result) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone trigger prototypes.'));
		}

		// Process dependencies, if at least one trigger prototype has a dependency.
		if ($add_dependencies) {
			$trigger_prototypeids = array_keys($trigger_prototypes);

			foreach ($result['triggerids'] as $i => $triggerid) {
				$new_trigger_prototypes[$trigger_prototypeids[$i]] = [
					'new_triggerid' => $triggerid,
					'new_hostid' => $dstHost['hostid'],
					'new_host' => $dstHost['host'],
					'src_hostid' => $srcHost['hostid'],
					'src_host' => $srcHost['host']
				];
			}

			/*
			 * Search for original dependent triggers and expressions to find corresponding triggers on destination host
			 * with same expression.
			 */
			$dep_triggers = API::Trigger()->get([
				'output' => ['description', 'expression'],
				'selectHosts' => ['hostid'],
				'triggerids' => $dep_triggerids,
				'preservekeys' => true
			]);
			$dep_triggers = CMacrosResolverHelper::resolveTriggerExpressions($dep_triggers);

			// Map dependencies to the new trigger IDs and save.
			foreach ($trigger_prototypes as &$trigger_prototype) {
				// Get corresponding created trigger prototype ID.
				$new_trigger_prototype = $new_trigger_prototypes[$trigger_prototype['triggerid']];

				if ($trigger_prototype['dependencies']) {
					foreach ($trigger_prototype['dependencies'] as &$dependency) {
						$dep_triggerid = $dependency['triggerid'];

						/*
						 * We have added a dependent trigger prototype and we know corresponding trigger prototype ID
						 * for newly created trigger prototype.
						 */
						if (array_key_exists($dependency['triggerid'], $new_trigger_prototypes)) {
							/*
							 * Dependency is within same host according to $srcHostId parameter or dep trigger has
							 * single host.
							 */
							if ($new_trigger_prototype['src_hostid'] ==
									$new_trigger_prototypes[$dep_triggerid]['src_hostid']) {
								$dependency['triggerid'] = $new_trigger_prototypes[$dep_triggerid]['new_triggerid'];
							}
						}
						elseif (in_array(['hostid' => $new_trigger_prototype['src_hostid']],
								$dep_triggers[$dep_triggerid]['hosts'])) {
							// Get all possible $depTrigger matching triggers by description.
							$target_triggers = API::Trigger()->get([
								'output' => ['hosts', 'triggerid', 'expression'],
								'hostids' => $new_trigger_prototype['new_hostid'],
								'filter' => ['description' => $dep_triggers[$dep_triggerid]['description']],
								'preservekeys' => true
							]);
							$target_triggers = CMacrosResolverHelper::resolveTriggerExpressions($target_triggers);

							// Compare exploded expressions for exact match.
							$expr1 = $dep_triggers[$dep_triggerid]['expression'];
							$dependency['triggerid'] = null;

							foreach ($target_triggers as $target_trigger) {
								$expr2 = triggerExpressionReplaceHost($target_trigger['expression'],
									$new_trigger_prototype['new_host'],
									$new_trigger_prototype['src_host']
								);

								if ($expr2 === $expr1) {
									// Matching trigger has been found.
									$dependency['triggerid'] = $target_trigger['triggerid'];
									break;
								}
							}

							// If matching trigger was not found, raise exception.
							if ($dependency['triggerid'] === null) {
								$expr2 = triggerExpressionReplaceHost($dep_triggers[$dep_triggerid]['expression'],
									$new_trigger_prototype['src_host'],
									$new_trigger_prototype['new_host']
								);
								self::exception(ZBX_API_ERROR_PARAMETERS, _s(
									'Cannot add dependency from trigger "%1$s:%2$s" to non existing trigger "%3$s:%4$s".',
									$trigger_prototype['description'],
									$trigger_prototype['expression'],
									$dep_triggers[$dep_triggerid]['description'],
									$expr2
								));
							}
						}
					}
					unset($dependency);

					$trigger_prototype['triggerid'] = $new_trigger_prototype['new_triggerid'];
				}
			}
			unset($trigger_prototype);

			// If adding a dependency fails, the exception will be raised in TriggerPrototype API.
			API::TriggerPrototype()->addDependencies($trigger_prototypes);
		}

		return $result;
	}

	protected function createReal(array &$items) {
		$items_rtdata = [];
		$create_items = [];

		// create items without formulas, they will be updated when items and conditions are saved
		foreach ($items as $key => $item) {
			if (array_key_exists('filter', $item)) {
				$item['evaltype'] = $item['filter']['evaltype'];
				unset($item['filter']);
			}

			if (array_key_exists('rtdata', $item)) {
				$items_rtdata[$key] = [];
				unset($item['rtdata']);
			}

			$create_items[] = $item;
		}
		$create_items = DB::save('items', $create_items);

		foreach ($items_rtdata as $key => &$value) {
			$value['itemid'] = $create_items[$key]['itemid'];
		}
		unset($value);

		DB::insert('item_rtdata', $items_rtdata, false);

		$conditions = [];
		foreach ($items as $key => &$item) {
			$item['itemid'] = $create_items[$key]['itemid'];

			// conditions
			if (isset($item['filter'])) {
				foreach ($item['filter']['conditions'] as $condition) {
					$condition['itemid'] = $item['itemid'];

					$conditions[] = $condition;
				}
			}
		}
		unset($item);

		$conditions = DB::save('item_condition', $conditions);

		$item_conditions = [];

		foreach ($conditions as $condition) {
			$item_conditions[$condition['itemid']][] = $condition;
		}

		$lld_macro_paths = [];

		foreach ($items as $item) {
			// update formulas
			if (isset($item['filter']) && $item['filter']['evaltype'] == CONDITION_EVAL_TYPE_EXPRESSION) {
				$this->updateFormula($item['itemid'], $item['filter']['formula'], $item_conditions[$item['itemid']]);
			}

			// $item['lld_macro_paths'] expects to be filled with validated fields 'lld_macro' and 'path' and values.
			if (array_key_exists('lld_macro_paths', $item)) {
				foreach ($item['lld_macro_paths'] as $lld_macro_path) {
					$lld_macro_paths[] = $lld_macro_path + ['itemid' => $item['itemid']];
				}
			}
		}

		DB::insertBatch('lld_macro_path', $lld_macro_paths);

		$this->createItemPreprocessing($items);
	}

	protected function updateReal(array $items) {
		CArrayHelper::sort($items, ['itemid']);

		$ruleIds = zbx_objectValues($items, 'itemid');

		$data = [];
		foreach ($items as $item) {
			$values = $item;

			if (isset($item['filter'])) {
				// clear the formula for non-custom expression rules
				if ($item['filter']['evaltype'] != CONDITION_EVAL_TYPE_EXPRESSION) {
					$values['formula'] = '';
				}

				$values['evaltype'] = $item['filter']['evaltype'];
				unset($values['filter']);
			}

			$data[] = ['values' => $values, 'where' => ['itemid' => $item['itemid']]];
		}
		DB::update('items', $data);

		$newRuleConditions = null;
		foreach ($items as $item) {
			// conditions
			if (isset($item['filter'])) {
				if ($newRuleConditions === null) {
					$newRuleConditions = [];
				}

				$newRuleConditions[$item['itemid']] = [];
				foreach ($item['filter']['conditions'] as $condition) {
					$condition['itemid'] = $item['itemid'];

					$newRuleConditions[$item['itemid']][] = $condition;
				}
			}
		}

		// replace conditions
		$ruleConditions = [];
		if ($newRuleConditions !== null) {
			// fetch existing conditions
			$exConditions = DBfetchArray(DBselect(
				'SELECT item_conditionid,itemid,macro,value,operator'.
				' FROM item_condition'.
				' WHERE '.dbConditionInt('itemid', $ruleIds).
				' ORDER BY item_conditionid'
			));
			$exRuleConditions = [];
			foreach ($exConditions as $condition) {
				$exRuleConditions[$condition['itemid']][] = $condition;
			}

			// replace and add the new IDs
			$conditions = DB::replaceByPosition('item_condition', $exRuleConditions, $newRuleConditions);
			foreach ($conditions as $condition) {
				$ruleConditions[$condition['itemid']][] = $condition;
			}
		}

		$itemids = [];
		$lld_macro_paths = [];
		$db_lld_macro_paths = [];

		foreach ($items as $item) {
			// update formulas
			if (isset($item['filter']) && $item['filter']['evaltype'] == CONDITION_EVAL_TYPE_EXPRESSION) {
				$this->updateFormula($item['itemid'], $item['filter']['formula'], $ruleConditions[$item['itemid']]);
			}

			// "lld_macro_paths" could be empty or filled with fields "lld_macro", "path" or "lld_macro_pathid".
			if (array_key_exists('lld_macro_paths', $item)) {
				$itemids[$item['itemid']] = true;

				if ($item['lld_macro_paths']) {
					foreach ($item['lld_macro_paths'] as $lld_macro_path) {
						$lld_macro_paths[] = $lld_macro_path + ['itemid' => $item['itemid']];
					}
				}
			}
		}

		// Gather all existing LLD macros from given discovery rules.
		if ($itemids) {
			$db_lld_macro_paths = DB::select('lld_macro_path', [
				'output' => ['lld_macro_pathid', 'itemid', 'lld_macro', 'path'],
				'filter' => ['itemid' => array_keys($itemids)]
			]);
		}

		/*
		 * DB::replaceByPosition() does not allow to change records one by one due to unique indexes on two table
		 * columns. Problems arise when given records are the same as records in DB and they are sorted differently.
		 * That's why checking differences between old and new records is done manually.
		 */

		$lld_macro_paths_to_update = [];

		foreach ($lld_macro_paths as $idx1 => $lld_macro_path) {
			foreach ($db_lld_macro_paths as $idx2 => $db_lld_macro_path) {
				if (array_key_exists('lld_macro_pathid', $lld_macro_path)) {
					// Update records by primary key.

					// Find matching "lld_macro_pathid" and update fields accordingly.
					if (bccomp($lld_macro_path['lld_macro_pathid'], $db_lld_macro_path['lld_macro_pathid']) == 0) {
						$fields_to_update = [];

						if (array_key_exists('lld_macro', $lld_macro_path)
								&& $lld_macro_path['lld_macro'] === $db_lld_macro_path['lld_macro']) {
							// If same "lld_macro" is found in DB, update only "path" if necessary.

							if (array_key_exists('path', $lld_macro_path)
									&& $lld_macro_path['path'] !== $lld_macro_path['path']) {
								$fields_to_update['path'] = $lld_macro_path['path'];
							}
						}
						else {
							/*
							 * Update all other fields that correspond to given "lld_macro_pathid". Except for primary
							 * key "lld_macro_pathid" and "itemid".
							 */

							foreach ($lld_macro_path as $field => $value) {
								if ($field !== 'itemid' && $field !== 'lld_macro_pathid') {
									$fields_to_update[$field] = $value;
								}
							}
						}

						/*
						 * If there are any changes made, update fields in DB. Otherwise skip updating and result in
						 * success anyway.
						 */
						if ($fields_to_update) {
							$lld_macro_paths_to_update[] = $fields_to_update
								+ ['lld_macro_pathid' => $lld_macro_path['lld_macro_pathid']];
						}

						/*
						 * Remove processed LLD macros from the list. Macros left in $db_lld_macro_paths will be removed
						 * afterwards.
						 */
						unset($db_lld_macro_paths[$idx2]);
						unset($lld_macro_paths[$idx1]);
					}
					// Incorrect "lld_macro_pathid" cannot be given due to validation done previously.
				}
				else {
					// Add or update fields by given "lld_macro".

					if (bccomp($lld_macro_path['itemid'], $db_lld_macro_path['itemid']) == 0) {
						if ($lld_macro_path['lld_macro'] === $db_lld_macro_path['lld_macro']) {
							// If same "lld_macro" is given, add primary key and update only "path", if necessary.

							if ($lld_macro_path['path'] !== $db_lld_macro_path['path']) {
								$lld_macro_paths_to_update[] = [
									'lld_macro_pathid' => $db_lld_macro_path['lld_macro_pathid'],
									'path' => $lld_macro_path['path']
								];
							}

							/*
							 * Remove processed LLD macros from the list. Macros left in $db_lld_macro_paths will
							 * be removed afterwards. And macros left in $lld_macro_paths will be created.
							 */
							unset($db_lld_macro_paths[$idx2]);
							unset($lld_macro_paths[$idx1]);
						}
					}
				}
			}
		}

		// After all data has been collected, proceed with record update in DB.
		$lld_macro_pathids_to_delete = zbx_objectValues($db_lld_macro_paths, 'lld_macro_pathid');

		if ($lld_macro_pathids_to_delete) {
			DB::delete('lld_macro_path', ['lld_macro_pathid' => $lld_macro_pathids_to_delete]);
		}

		if ($lld_macro_paths_to_update) {
			$data = [];

			foreach ($lld_macro_paths_to_update as $lld_macro_path) {
				$data[] = [
					'values' => $lld_macro_path,
					'where' => [
						'lld_macro_pathid' => $lld_macro_path['lld_macro_pathid']
					]
				];
			}

			DB::update('lld_macro_path', $data);
		}

		DB::insertBatch('lld_macro_path', $lld_macro_paths);

		$this->updateItemPreprocessing($items);
	}

	/**
	 * Converts a formula with letters to a formula with IDs and updates it.
	 *
	 * @param string 	$itemId
	 * @param string 	$evalFormula		formula with letters
	 * @param array 	$conditions
	 */
	protected function updateFormula($itemId, $evalFormula, array $conditions) {
		$ids = [];
		foreach ($conditions as $condition) {
			$ids[$condition['formulaid']] = $condition['item_conditionid'];
		}
		$formula = CConditionHelper::replaceLetterIds($evalFormula, $ids);

		DB::updateByPk('items', $itemId, [
			'formula' => $formula
		]);
	}

	/**
	 * Check item data and set missing default values.
	 *
	 * @param array $items passed by reference
	 * @param bool  $update
	 * @param array $dbItems
	 */
	protected function checkInput(array &$items, $update = false, array $dbItems = []) {
		// add the values that cannot be changed, but are required for further processing
		foreach ($items as &$item) {
			$item['flags'] = ZBX_FLAG_DISCOVERY_RULE;
			$item['value_type'] = ITEM_VALUE_TYPE_TEXT;

			// unset fields that are updated using the 'filter' parameter
			unset($item['evaltype']);
			unset($item['formula']);
		}
		unset($item);

		parent::checkInput($items, $update);

		$validateItems = $items;
		if ($update) {
			$validateItems = $this->extendFromObjects(zbx_toHash($validateItems, 'itemid'), $dbItems, ['name']);
		}

		// filter validator
		$filterValidator = new CSchemaValidator($this->getFilterSchema());

		// condition validation
		$conditionValidator = new CSchemaValidator($this->getFilterConditionSchema());
		foreach ($validateItems as $item) {
			// validate custom formula and conditions
			if (isset($item['filter'])) {
				$filterValidator->setObjectName($item['name']);
				$this->checkValidator($item['filter'], $filterValidator);

				foreach ($item['filter']['conditions'] as $condition) {
					$conditionValidator->setObjectName($item['name']);
					$this->checkValidator($condition, $conditionValidator);
				}
			}
		}
	}

	/**
	 * Returns the parameters for creating a discovery rule filter validator.
	 *
	 * @return array
	 */
	protected function getFilterSchema() {
		return [
			'validators' => [
				'evaltype' => new CLimitedSetValidator([
					'values' => [
						CONDITION_EVAL_TYPE_OR,
						CONDITION_EVAL_TYPE_AND,
						CONDITION_EVAL_TYPE_AND_OR,
						CONDITION_EVAL_TYPE_EXPRESSION
					],
					'messageInvalid' => _('Incorrect type of calculation for discovery rule "%1$s".')
				]),
				'formula' => new CStringValidator([
					'empty' => true
				]),
				'conditions' => new CCollectionValidator([
					'empty' => true,
					'messageInvalid' => _('Incorrect conditions for discovery rule "%1$s".')
				])
			],
			'postValidators' => [
				new CConditionValidator([
					'messageInvalidFormula' => _('Incorrect custom expression "%2$s" for discovery rule "%1$s": %3$s.'),
					'messageMissingCondition' => _('Condition "%2$s" used in formula "%3$s" for discovery rule "%1$s" is not defined.'),
					'messageUnusedCondition' => _('Condition "%2$s" is not used in formula "%3$s" for discovery rule "%1$s".')
				])
			],
			'required' => ['evaltype', 'conditions'],
			'messageRequired' => _('No "%2$s" given for the filter of discovery rule "%1$s".'),
			'messageUnsupported' => _('Unsupported parameter "%2$s" for the filter of discovery rule "%1$s".')
		];
	}

	/**
	 * Returns the parameters for creating a discovery rule filter condition validator.
	 *
	 * @return array
	 */
	protected function getFilterConditionSchema() {
		return [
			'validators' => [
				'macro' => new CStringValidator([
					'regex' => '/^'.ZBX_PREG_EXPRESSION_LLD_MACROS.'$/',
					'messageEmpty' => _('Empty filter condition macro for discovery rule "%1$s".'),
					'messageRegex' => _('Incorrect filter condition macro for discovery rule "%1$s".')
				]),
				'value' => new CStringValidator([
					'empty' => true
				]),
				'formulaid' => new CStringValidator([
					'regex' => '/[A-Z]+/',
					'messageEmpty' => _('Empty filter condition formula ID for discovery rule "%1$s".'),
					'messageRegex' => _('Incorrect filter condition formula ID for discovery rule "%1$s".')
				]),
				'operator' => new CLimitedSetValidator([
					'values' => [CONDITION_OPERATOR_REGEXP, CONDITION_OPERATOR_NOT_REGEXP],
					'messageInvalid' => _('Incorrect filter condition operator for discovery rule "%1$s".')
				])
			],
			'required' => ['macro', 'value'],
			'messageRequired' => _('No "%2$s" given for a filter condition of discovery rule "%1$s".'),
			'messageUnsupported' => _('Unsupported parameter "%2$s" for a filter condition of discovery rule "%1$s".')
		];
	}

	/**
	 * Check discovery rule specific fields.
	 *
	 * @param array  $item    An array of single item data.
	 * @param string $method  A string of "create" or "update" method.
	 *
	 * @throws APIException if the input is invalid.
	 */
	protected function checkSpecificFields(array $item, $method) {
		if (array_key_exists('lifetime', $item)
				&& !validateTimeUnit($item['lifetime'], SEC_PER_HOUR, 25 * SEC_PER_YEAR, true, $error,
					['usermacros' => true])) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Incorrect value for field "%1$s": %2$s.', 'lifetime', $error)
			);
		}
	}

	/**
	 * Checks if LLD macros contain duplicate names in "lld_macro".
	 *
	 * @param array  $lld_macro_paths                 Array of items to validate.
	 * @param string $lld_macro_paths[]['lld_macro']  LLD macro string (optional for update method).
	 * @param array  $macro_names                     Array where existing macro names are collected.
	 * @param string $path                            Path to API object.
	 *
	 * @throws APIException if same discovery rules contains duplicate LLD macro names.
	 */
	protected function checkDuplicateLLDMacros(array $lld_macro_paths, $macro_names, $path) {
		foreach ($lld_macro_paths as $num => $lld_macro_path) {
			if (array_key_exists('lld_macro', $lld_macro_path)) {
				if (array_key_exists($lld_macro_path['lld_macro'], $macro_names)) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Invalid parameter "%1$s": %2$s.', $path.'/lld_macro_paths/'.($num + 1).'/lld_macro',
							_s('value "%1$s" already exists', $lld_macro_path['lld_macro'])
						)
					);
				}

				$macro_names[$lld_macro_path['lld_macro']] = true;
			}
		}
	}

	/**
	 * Validates parameters in "lld_macro_paths" property for each item in create method.
	 *
	 * @param array  $items                                      Array of items to validate.
	 * @param array  $items[]['lld_macro_paths']                 Array of LLD macro paths to validate for each
	 *                                                           discovery rule (optional).
	 * @param string $items[]['lld_macro_paths'][]['lld_macro']  LLD macro string. Required if "lld_macro_paths" exists.
	 * @param string $items[]['lld_macro_paths'][]['path']       Path string. Validates as regular string. Required if
	 *                                                           "lld_macro_paths" exists.
	 *
	 * @throws APIException if incorrect fields and values given.
	 */
	protected function validateCreateLLDMacroPaths(array $items) {
		$rules = [
			'lld_macro_paths' =>	['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY, 'fields' => [
				'lld_macro' =>			['type' => API_LLD_MACRO, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('lld_macro_path', 'lld_macro')],
				'path' =>				['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('lld_macro_path', 'path')]
			]]
		];

		foreach ($items as $key => $item) {
			if (array_key_exists('lld_macro_paths', $item)) {
				$item = array_intersect_key($item, $rules);
				$path = '/'.($key + 1);

				if (!CApiInputValidator::validate(['type' => API_OBJECT, 'fields' => $rules], $item, $path, $error)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, $error);
				}

				$this->checkDuplicateLLDMacros($item['lld_macro_paths'], [], $path);
			}
		}
	}

	/**
	 * Validates parameters in "lld_macro_paths" property for each item in create method.
	 *
	 * @param array  $items                                             Array of items to validate.
	 * @param array  $items[]['lld_macro_paths']                        Array of LLD macro paths to validate for each
	 *                                                                  discovery rule (optional).
	 * @param string $items[]['lld_macro_paths'][]['lld_macro_pathid']  LLD macro path ID from DB (optional).
	 * @param string $items[]['lld_macro_paths'][]['lld_macro']         LLD macro string. Required if "lld_macro_pathid"
	 *                                                                  does not exist.
	 * @param string $items[]['lld_macro_paths'][]['path']              Path string. Validates as regular string.
	 *                                                                  Required if "lld_macro_pathid" and "lld_macro"
	 *                                                                  do not exist.
	 *
	 * @throws APIException if incorrect fields and values given.
	 */
	protected function validateUpdateLLDMacroPaths(array $items) {
		$rules = [
			'lld_macro_paths' =>	['type' => API_OBJECTS, 'fields' => [
				'lld_macro_pathid' =>	['type' => API_ID],
				'lld_macro' =>			['type' => API_LLD_MACRO, 'length' => DB::getFieldLength('lld_macro_path', 'lld_macro')],
				'path' =>				['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('lld_macro_path', 'path')]
			]]
		];

		$items = $this->extendObjects('items', $items, ['templateid']);

		foreach ($items as $key => $item) {
			if (array_key_exists('lld_macro_paths', $item)) {
				$itemid = $item['itemid'];
				$templateid = $item['templateid'];

				$item = array_intersect_key($item, $rules);
				$path = '/'.($key + 1);

				if (!CApiInputValidator::validate(['type' => API_OBJECT, 'fields' => $rules], $item, $path, $error)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, $error);
				}

				if (array_key_exists('lld_macro_paths', $item)) {
					if ($templateid != 0) {
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Invalid parameter "%1$s": %2$s.', $path.'/lld_macro_paths',
								_('cannot update property for templated discovery rule')
							)
						);
					}

					$lld_macro_pathids = [];

					// Check that fields exists, are not empty, do not duplicate and collect IDs to compare with DB.
					foreach ($item['lld_macro_paths'] as $num => $lld_macro_path) {
						$subpath = $num + 1;

						// API_NOT_EMPTY will not work, so we need at least one field to be present.
						if (!array_key_exists('lld_macro', $lld_macro_path)
								&& !array_key_exists('path', $lld_macro_path)
								&& !array_key_exists('lld_macro_pathid', $lld_macro_path)) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Invalid parameter "%1$s": %2$s.', $path.'/lld_macro_paths/'.$subpath,
									_('cannot be empty')
								)
							);
						}

						// API 'uniq' => true will not work, because we validate API_ID not API_IDS. So make IDs unique.
						if (array_key_exists('lld_macro_pathid', $lld_macro_path)) {
							$lld_macro_pathids[$lld_macro_path['lld_macro_pathid']] = true;
						}
						else {
							/*
							 * In case "lld_macro_pathid" does not exist, we need to treat it as a new LLD macro with
							 * both fields present.
							 */
							if (array_key_exists('lld_macro', $lld_macro_path)
									&& !array_key_exists('path', $lld_macro_path)) {
								self::exception(ZBX_API_ERROR_PARAMETERS,
									_s('Invalid parameter "%1$s": %2$s.', $path.'/lld_macro_paths/'.$subpath,
										_s('the parameter "%1$s" is missing', 'path')
									)
								);
							}
							elseif (array_key_exists('path', $lld_macro_path)
									&& !array_key_exists('lld_macro', $lld_macro_path)) {
								self::exception(ZBX_API_ERROR_PARAMETERS,
									_s('Invalid parameter "%1$s": %2$s.', $path.'/lld_macro_paths/'.$subpath,
										_s('the parameter "%1$s" is missing', 'lld_macro')
									)
								);
							}
						}
					}

					$this->checkDuplicateLLDMacros($item['lld_macro_paths'], [], $path);

					/*
					 * Validate "lld_macro_pathid" field. If "lld_macro_pathid" doesn't correspond to given "itemid"
					 * or does not exist, throw an exception.
					 */
					if ($lld_macro_pathids) {
						$lld_macro_pathids = array_keys($lld_macro_pathids);

						$db_lld_macro_paths = DBfetchArrayAssoc(DBselect(
							'SELECT lmp.lld_macro_pathid,lmp.lld_macro'.
							' FROM lld_macro_path lmp'.
							' WHERE lmp.itemid='.zbx_dbstr($itemid).
								' AND '.dbConditionId('lmp.lld_macro_pathid', $lld_macro_pathids)
						), 'lld_macro_pathid');

						if (count($db_lld_macro_paths) != count($lld_macro_pathids)) {
							self::exception(ZBX_API_ERROR_PERMISSIONS,
								_('No permissions to referred object or it does not exist!')
							);
						}

						$macro_names = [];

						foreach ($item['lld_macro_paths'] as $num => $lld_macro_path) {
							if (array_key_exists('lld_macro_pathid', $lld_macro_path)
									&& !array_key_exists('lld_macro', $lld_macro_path)) {
								$db_lld_macro_path = $db_lld_macro_paths[$lld_macro_path['lld_macro_pathid']];
								$macro_names[$db_lld_macro_path['lld_macro']] = true;
							}
						}

						$this->checkDuplicateLLDMacros($item['lld_macro_paths'], $macro_names, $path);
					}
				}
			}
		}
	}

	/**
	 * Copies the given discovery rule to the specified host.
	 *
	 * @throws APIException if the discovery rule interfaces could not be mapped
	 * to the new host interfaces.
	 *
	 * @param string $discoveryid  The ID of the discovery rule to be copied
	 * @param string $hostid       Destination host id
	 *
	 * @return bool
	 */
	protected function copyDiscoveryRule($discoveryid, $hostid) {
		// fetch discovery to clone
		$srcDiscovery = $this->get([
			'itemids' => $discoveryid,
			'output' => ['itemid', 'type', 'snmp_community', 'snmp_oid', 'hostid', 'name', 'key_', 'delay', 'history',
				'trends', 'status', 'value_type', 'trapper_hosts', 'units', 'snmpv3_securityname',
				'snmpv3_securitylevel', 'snmpv3_authpassphrase', 'snmpv3_privpassphrase', 'lastlogsize', 'logtimefmt',
				'valuemapid', 'params', 'ipmi_sensor', 'authtype', 'username', 'password', 'publickey', 'privatekey',
				'mtime', 'flags', 'interfaceid', 'port', 'description', 'inventory_link', 'lifetime',
				'snmpv3_authprotocol', 'snmpv3_privprotocol', 'snmpv3_contextname', 'jmx_endpoint', 'url',
				'query_fields', 'timeout', 'posts', 'status_codes', 'follow_redirects', 'post_type', 'http_proxy',
				'headers', 'retrieve_mode', 'request_method', 'ssl_cert_file', 'ssl_key_file', 'ssl_key_password',
				'verify_peer', 'verify_host', 'allow_traps', 'master_itemid'
			],
			'selectFilter' => ['evaltype', 'formula', 'conditions'],
			'selectLLDMacroPaths' => ['lld_macro', 'path'],
			'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
			'preservekeys' => true
		]);
		$srcDiscovery = reset($srcDiscovery);

		// fetch source and destination hosts
		$hosts = API::Host()->get([
			'hostids' => [$srcDiscovery['hostid'], $hostid],
			'output' => ['hostid', 'host', 'name', 'status'],
			'selectInterfaces' => API_OUTPUT_EXTEND,
			'templated_hosts' => true,
			'preservekeys' => true
		]);
		$srcHost = $hosts[$srcDiscovery['hostid']];
		$dstHost = $hosts[$hostid];

		$dstDiscovery = $srcDiscovery;
		$dstDiscovery['hostid'] = $hostid;
		unset($dstDiscovery['itemid']);
		if ($dstDiscovery['filter']) {
			foreach ($dstDiscovery['filter']['conditions'] as &$condition) {
				unset($condition['itemid'], $condition['item_conditionid']);
			}
			unset($condition);
		}

		if (!$dstDiscovery['lld_macro_paths']) {
			unset($dstDiscovery['lld_macro_paths']);
		}

		// if this is a plain host, map discovery interfaces
		if ($srcHost['status'] != HOST_STATUS_TEMPLATE) {
			// find a matching interface
			$interface = self::findInterfaceForItem($dstDiscovery['type'], $dstHost['interfaces']);
			if ($interface) {
				$dstDiscovery['interfaceid'] = $interface['interfaceid'];
			}
			// no matching interface found, throw an error
			elseif ($interface !== false) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _s(
					'Cannot find host interface on "%1$s" for item key "%2$s".',
					$dstHost['name'],
					$dstDiscovery['key_']
				));
			}
		}

		// Master item should exists for LLD rule with type dependent item.
		if ($srcDiscovery['type'] == ITEM_TYPE_DEPENDENT) {
			$master_items = DBfetchArray(DBselect(
				'SELECT i1.itemid'.
				' FROM items i1,items i2'.
				' WHERE i1.key_=i2.key_'.
					' AND i1.hostid='.zbx_dbstr($dstDiscovery['hostid']).
					' AND i2.itemid='.zbx_dbstr($srcDiscovery['master_itemid'])
			));

			if (!$master_items) {
				self::exception(ZBX_API_ERROR_PERMISSIONS,
					_s('Discovery rule "%1$s" cannot be copied without its master item.', $srcDiscovery['name'])
				);
			}

			$dstDiscovery['master_itemid'] = $master_items[0]['itemid'];
		}

		// save new discovery
		$newDiscovery = $this->create([$dstDiscovery]);
		$dstDiscovery['itemid'] = $newDiscovery['itemids'][0];

		// copy prototypes
		$new_prototypeids = $this->copyItemPrototypes($srcDiscovery, $dstDiscovery, $dstHost);

		// if there were prototypes defined, clone everything else
		if ($new_prototypeids) {
			// fetch new prototypes
			$dstDiscovery['items'] = API::ItemPrototype()->get([
				'output' => ['itemid', 'key_'],
				'itemids' => $new_prototypeids,
				'preservekeys' => true
			]);

			// copy graphs
			$this->copyGraphPrototypes($srcDiscovery, $dstDiscovery);

			// copy triggers
			$this->copyTriggerPrototypes($srcDiscovery, $srcHost, $dstHost);
		}

		// copy host prototypes
		$this->copyHostPrototypes($discoveryid, $dstDiscovery);

		return true;
	}

	/**
	 * Copies all of the item prototypes from the source discovery to the target
	 * discovery rule. Return array of created item prototype ids.
	 *
	 * @throws APIException if prototype saving fails
	 *
	 * @param array $srcDiscovery   The source discovery rule to copy from
	 * @param array $dstDiscovery   The target discovery rule to copy to
	 * @param array $dstHost        The target host to copy the deiscovery rule to
	 *
	 * @return array
	 */
	protected function copyItemPrototypes(array $srcDiscovery, array $dstDiscovery, array $dstHost) {
		$item_prototypes = API::ItemPrototype()->get([
			'output' => ['itemid', 'type', 'snmp_community', 'snmp_oid', 'name', 'key_', 'delay', 'history', 'trends',
				'status', 'value_type', 'trapper_hosts', 'units', 'snmpv3_securityname', 'snmpv3_securitylevel',
				'snmpv3_authpassphrase', 'snmpv3_privpassphrase', 'logtimefmt', 'valuemapid', 'params', 'ipmi_sensor',
				'authtype', 'username', 'password', 'publickey', 'privatekey', 'interfaceid', 'port', 'description',
				'snmpv3_authprotocol', 'snmpv3_privprotocol', 'snmpv3_contextname', 'jmx_endpoint', 'master_itemid',
				'templateid', 'url', 'query_fields', 'timeout', 'posts', 'status_codes', 'follow_redirects',
				'post_type', 'http_proxy', 'headers', 'retrieve_mode', 'request_method', 'output_format',
				'ssl_cert_file', 'ssl_key_file', 'ssl_key_password', 'verify_peer', 'verify_host', 'allow_traps'
			],
			'selectApplications' => ['applicationid'],
			'selectApplicationPrototypes' => ['name'],
			'selectPreprocessing' => ['type', 'params', 'error_handler', 'error_handler_params'],
			'discoveryids' => $srcDiscovery['itemid'],
			'preservekeys' => true
		]);
		$new_itemids = [];
		$itemkey_to_id = [];
		$create_items = [];

		if ($item_prototypes) {
			$create_order = [];
			$src_itemid_to_key = [];
			$unresolved_master_itemids = [];

			// Gather all master item IDs and check if master item IDs already belong to item prototypes.
			foreach ($item_prototypes as $itemid => $item_prototype) {
				if ($item_prototype['type'] == ITEM_TYPE_DEPENDENT
						&& !array_key_exists($item_prototype['master_itemid'], $item_prototypes)) {
					$unresolved_master_itemids[$item_prototype['master_itemid']] = true;
				}
			}

			$items = [];

			// It's possible that master items are non-prototype items.
			if ($unresolved_master_itemids) {
				$items = API::Item()->get([
					'output' => ['itemid'],
					'itemids' => array_keys($unresolved_master_itemids),
					'webitems' => true,
					'filter' => ['flags' => ZBX_FLAG_DISCOVERY_NORMAL],
					'preservekeys' => true
				]);

				foreach ($items as $item) {
					if (array_key_exists($item['itemid'], $unresolved_master_itemids)) {
						unset($unresolved_master_itemids[$item['itemid']]);
					}
				}

				// If still there are IDs left, there's nothing more we can do.
				if ($unresolved_master_itemids) {
					reset($unresolved_master_itemids);
					self::exception(ZBX_API_ERROR_PERMISSIONS, _s('Incorrect value for field "%1$s": %2$s.',
						'master_itemid', _s('Item "%1$s" does not exist or you have no access to this item',
							key($unresolved_master_itemids)
					)));
				}
			}

			foreach ($item_prototypes as $itemid => $item_prototype) {
				$dependency_level = 0;
				$master_item_prototype = $item_prototype;
				$src_itemid_to_key[$itemid] = $item_prototype['key_'];

				while ($master_item_prototype['type'] == ITEM_TYPE_DEPENDENT) {
					if (array_key_exists($master_item_prototype['master_itemid'], $item_prototypes)) {
						$master_item_prototype = $item_prototypes[$master_item_prototype['master_itemid']];
						++$dependency_level;
					}
					else {
						break;
					}
				}

				$create_order[$itemid] = $dependency_level;
			}
			asort($create_order);

			$current_dependency = reset($create_order);

			foreach ($create_order as $key => $dependency_level) {
				if ($current_dependency != $dependency_level && $create_items) {
					$current_dependency = $dependency_level;
					$created_itemids = API::ItemPrototype()->create($create_items);

					if (!$created_itemids) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone item prototypes.'));
					}

					$created_itemids = $created_itemids['itemids'];
					$new_itemids = array_merge($new_itemids, $created_itemids);

					foreach ($create_items as $index => $created_item) {
						$itemkey_to_id[$created_item['key_']] = $created_itemids[$index];
					}

					$create_items = [];
				}

				$item_prototype = $item_prototypes[$key];
				$item_prototype['ruleid'] = $dstDiscovery['itemid'];
				$item_prototype['hostid'] = $dstDiscovery['hostid'];

				// map prototype interfaces
				if ($dstHost['status'] != HOST_STATUS_TEMPLATE) {
					// find a matching interface
					$interface = self::findInterfaceForItem($item_prototype['type'], $dstHost['interfaces']);
					if ($interface) {
						$item_prototype['interfaceid'] = $interface['interfaceid'];
					}
					// no matching interface found, throw an error
					elseif ($interface !== false) {
						self::exception(ZBX_API_ERROR_PARAMETERS, _s(
							'Cannot find host interface on "%1$s" for item key "%2$s".',
							$dstHost['name'],
							$item_prototype['key_']
						));
					}
				}

				// add new applications
				$item_prototype['applications'] = get_same_applications_for_host(
					zbx_objectValues($item_prototype['applications'], 'applicationid'),
					$dstHost['hostid']
				);

				if (!$item_prototype['preprocessing']) {
					unset($item_prototype['preprocessing']);
				}

				if ($item_prototype['type'] == ITEM_TYPE_DEPENDENT) {
					$master_itemid = $item_prototype['master_itemid'];

					if (array_key_exists($master_itemid, $src_itemid_to_key)) {
						$src_item_key = $src_itemid_to_key[$master_itemid];
						$item_prototype['master_itemid'] = $itemkey_to_id[$src_item_key];
					}
					else {
						// It's a non-prototype item, so look for it on destination host.
						$dst_item = get_same_item_for_host($items[$master_itemid], $dstHost['hostid']);

						if (!$dst_item) {
							self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone item prototypes.'));
						}

						$item_prototype['master_itemid'] = $dst_item['itemid'];
					}
				}
				else {
					unset($item_prototype['master_itemid']);
				}

				unset($item_prototype['templateid']);
				$create_items[] = $item_prototype;
			}

			if ($create_items) {
				$created_itemids = API::ItemPrototype()->create($create_items);

				if (!$created_itemids) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone item prototypes.'));
				}

				$new_itemids = array_merge($new_itemids, $created_itemids['itemids']);
			}
		}

		return $new_itemids;
	}

	/**
	 * Copies all of the graphs from the source discovery to the target discovery rule.
	 *
	 * @throws APIException if graph saving fails
	 *
	 * @param array $srcDiscovery    The source discovery rule to copy from
	 * @param array $dstDiscovery    The target discovery rule to copy to
	 *
	 * @return array
	 */
	protected function copyGraphPrototypes(array $srcDiscovery, array $dstDiscovery) {
		// fetch source graphs
		$srcGraphs = API::GraphPrototype()->get([
			'output' => ['graphid', 'name', 'width', 'height', 'yaxismin', 'yaxismax', 'show_work_period',
				'show_triggers', 'graphtype', 'show_legend', 'show_3d', 'percent_left', 'percent_right',
				'ymin_type', 'ymax_type', 'ymin_itemid', 'ymax_itemid'
			],
			'selectGraphItems' => ['itemid', 'drawtype', 'sortorder', 'color', 'yaxisside', 'calc_fnc', 'type'],
			'selectHosts' => ['hostid'],
			'discoveryids' => $srcDiscovery['itemid'],
			'preservekeys' => true
		]);

		if (!$srcGraphs) {
			return [];
		}

		$srcItemIds = [];
		foreach ($srcGraphs as $key => $graph) {
			// skip graphs with items from multiple hosts
			if (count($graph['hosts']) > 1) {
				unset($srcGraphs[$key]);
				continue;
			}

			// skip graphs with http items
			if (httpItemExists($graph['gitems'])) {
				unset($srcGraphs[$key]);
				continue;
			}

			// save all used item ids to map them to the new items
			foreach ($graph['gitems'] as $item) {
				$srcItemIds[$item['itemid']] = $item['itemid'];
			}
			if ($graph['ymin_itemid']) {
				$srcItemIds[$graph['ymin_itemid']] = $graph['ymin_itemid'];
			}
			if ($graph['ymax_itemid']) {
				$srcItemIds[$graph['ymax_itemid']] = $graph['ymax_itemid'];
			}
		}

		// fetch source items
		$items = API::Item()->get([
			'itemids' => $srcItemIds,
			'output' => ['itemid', 'key_'],
			'preservekeys' => true,
			'filter' => ['flags' => null]
		]);

		$srcItems = [];
		$itemKeys = [];
		foreach ($items as $item) {
			$srcItems[$item['itemid']] = $item;
			$itemKeys[$item['key_']] = $item['key_'];
		}

		// fetch newly cloned items
		$newItems = API::Item()->get([
			'hostids' => $dstDiscovery['hostid'],
			'filter' => [
				'key_' => $itemKeys,
				'flags' => null
			],
			'output' => ['itemid', 'key_'],
			'preservekeys' => true
		]);

		$items = array_merge($dstDiscovery['items'], $newItems);
		$dstItems = [];
		foreach ($items as $item) {
			$dstItems[$item['key_']] = $item;
		}

		$dstGraphs = $srcGraphs;
		foreach ($dstGraphs as &$graph) {
			unset($graph['graphid']);

			foreach ($graph['gitems'] as &$gitem) {
				// replace the old item with the new one with the same key
				$item = $srcItems[$gitem['itemid']];
				$gitem['itemid'] = $dstItems[$item['key_']]['itemid'];
			}
			unset($gitem);

			// replace the old axis items with the new one with the same key
			if ($graph['ymin_itemid']) {
				$yMinSrcItem = $srcItems[$graph['ymin_itemid']];
				$graph['ymin_itemid'] = $dstItems[$yMinSrcItem['key_']]['itemid'];
			}
			if ($graph['ymax_itemid']) {
				$yMaxSrcItem = $srcItems[$graph['ymax_itemid']];
				$graph['ymax_itemid'] = $dstItems[$yMaxSrcItem['key_']]['itemid'];
			}
		}
		unset($graph);

		// save graphs
		$rs = API::GraphPrototype()->create($dstGraphs);
		if (!$rs) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone graph prototypes.'));
		}

		return $rs;
	}

	/**
	 * Copies all of the host prototypes from the source discovery to the target
	 * discovery rule.
	 *
	 * @throws APIException if prototype saving fails.
	 *
	 * @param int   $srcid          The source discovery rule id to copy from.
	 * @param array $dstDiscovery   The target discovery rule to copy to.
	 *
	 * @return array
	 */
	protected function copyHostPrototypes($srcid, array $dstDiscovery) {
		$prototypes = API::HostPrototype()->get([
			'discoveryids' => $srcid,
			'output' => ['host', 'name', 'status', 'inventory_mode'],
			'selectGroupLinks' => ['groupid'],
			'selectGroupPrototypes' => ['name'],
			'selectTemplates' => ['templateid'],
			'preservekeys' => true
		]);

		$rs = [];
		if ($prototypes) {
			foreach ($prototypes as &$prototype) {
				$prototype['ruleid'] = $dstDiscovery['itemid'];
				unset($prototype['hostid'], $prototype['inventory']['hostid']);

				foreach ($prototype['groupLinks'] as &$groupLinks) {
					unset($groupLinks['group_prototypeid']);
				}
				unset($groupLinks);

				foreach ($prototype['groupPrototypes'] as &$groupPrototype) {
					unset($groupPrototype['group_prototypeid']);
				}
				unset($groupPrototype);
			}
			unset($prototype);

			$rs = API::HostPrototype()->create($prototypes);
			if (!$rs) {
				self::exception(ZBX_API_ERROR_PARAMETERS, _('Cannot clone host prototypes.'));
			}
		}
		return $rs;
	}

	protected function applyQueryOutputOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryOutputOptions($tableName, $tableAlias, $options, $sqlParts);

		if ($this->outputIsRequested('state', $options['output'])
				|| $this->outputIsRequested('error', $options['output'])
				|| (is_array($options['search']) && array_key_exists('error', $options['search']))
				|| (is_array($options['filter']) && array_key_exists('state', $options['filter']))) {
			$sqlParts['left_join']['item_rtdata'] = ['from' => 'item_rtdata ir', 'on' => 'ir.itemid=i.itemid'];
			$sqlParts['left_table'] = $tableName;
		}

		if (!$options['countOutput']) {
			if ($this->outputIsRequested('state', $options['output'])) {
				$sqlParts = $this->addQuerySelect('ir.state', $sqlParts);
			}
			if ($this->outputIsRequested('error', $options['output'])) {
				/*
				 * SQL func COALESCE use for template items because they don't have record
				 * in item_rtdata table and DBFetch convert null to '0'
				 */
				$sqlParts = $this->addQuerySelect(dbConditionCoalesce('ir.error', '', 'error'), $sqlParts);
			}

			// add filter fields
			if ($this->outputIsRequested('formula', $options['selectFilter'])
					|| $this->outputIsRequested('eval_formula', $options['selectFilter'])
					|| $this->outputIsRequested('conditions', $options['selectFilter'])) {

				$sqlParts = $this->addQuerySelect('i.formula', $sqlParts);
				$sqlParts = $this->addQuerySelect('i.evaltype', $sqlParts);
			}
			if ($this->outputIsRequested('evaltype', $options['selectFilter'])) {
				$sqlParts = $this->addQuerySelect('i.evaltype', $sqlParts);
			}

			if ($options['selectHosts'] !== null) {
				$sqlParts = $this->addQuerySelect('i.hostid', $sqlParts);
			}
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$itemIds = array_keys($result);

		// adding items
		if (!is_null($options['selectItems'])) {
			if ($options['selectItems'] != API_OUTPUT_COUNT) {
				$relationMap = $this->createRelationMap($result, 'parent_itemid', 'itemid', 'item_discovery');
				$items = API::ItemPrototype()->get([
					'output' => $options['selectItems'],
					'itemids' => $relationMap->getRelatedIds(),
					'nopermissions' => true,
					'preservekeys' => true
				]);
				$result = $relationMap->mapMany($result, $items, 'items', $options['limitSelects']);
			}
			else {
				$items = API::ItemPrototype()->get([
					'discoveryids' => $itemIds,
					'nopermissions' => true,
					'countOutput' => true,
					'groupCount' => true
				]);

				$items = zbx_toHash($items, 'parent_itemid');
				foreach ($result as $itemid => $item) {
					$result[$itemid]['items'] = array_key_exists($itemid, $items) ? $items[$itemid]['rowscount'] : '0';
				}
			}
		}

		// adding triggers
		if (!is_null($options['selectTriggers'])) {
			if ($options['selectTriggers'] != API_OUTPUT_COUNT) {
				$relationMap = new CRelationMap();
				$res = DBselect(
					'SELECT id.parent_itemid,f.triggerid'.
					' FROM item_discovery id,items i,functions f'.
					' WHERE '.dbConditionInt('id.parent_itemid', $itemIds).
						' AND id.itemid=i.itemid'.
						' AND i.itemid=f.itemid'
				);
				while ($relation = DBfetch($res)) {
					$relationMap->addRelation($relation['parent_itemid'], $relation['triggerid']);
				}

				$triggers = API::TriggerPrototype()->get([
					'output' => $options['selectTriggers'],
					'triggerids' => $relationMap->getRelatedIds(),
					'preservekeys' => true
				]);
				$result = $relationMap->mapMany($result, $triggers, 'triggers', $options['limitSelects']);
			}
			else {
				$triggers = API::TriggerPrototype()->get([
					'discoveryids' => $itemIds,
					'countOutput' => true,
					'groupCount' => true
				]);

				$triggers = zbx_toHash($triggers, 'parent_itemid');
				foreach ($result as $itemid => $item) {
					$result[$itemid]['triggers'] = array_key_exists($itemid, $triggers)
						? $triggers[$itemid]['rowscount']
						: '0';
				}
			}
		}

		// adding graphs
		if (!is_null($options['selectGraphs'])) {
			if ($options['selectGraphs'] != API_OUTPUT_COUNT) {
				$relationMap = new CRelationMap();
				$res = DBselect(
					'SELECT id.parent_itemid,gi.graphid'.
					' FROM item_discovery id,items i,graphs_items gi'.
					' WHERE '.dbConditionInt('id.parent_itemid', $itemIds).
						' AND id.itemid=i.itemid'.
						' AND i.itemid=gi.itemid'
				);
				while ($relation = DBfetch($res)) {
					$relationMap->addRelation($relation['parent_itemid'], $relation['graphid']);
				}

				$graphs = API::GraphPrototype()->get([
					'output' => $options['selectGraphs'],
					'graphids' => $relationMap->getRelatedIds(),
					'preservekeys' => true
				]);
				$result = $relationMap->mapMany($result, $graphs, 'graphs', $options['limitSelects']);
			}
			else {
				$graphs = API::GraphPrototype()->get([
					'discoveryids' => $itemIds,
					'countOutput' => true,
					'groupCount' => true
				]);

				$graphs = zbx_toHash($graphs, 'parent_itemid');
				foreach ($result as $itemid => $item) {
					$result[$itemid]['graphs'] = array_key_exists($itemid, $graphs)
						? $graphs[$itemid]['rowscount']
						: '0';
				}
			}
		}

		// adding hosts
		if ($options['selectHostPrototypes'] !== null) {
			if ($options['selectHostPrototypes'] != API_OUTPUT_COUNT) {
				$relationMap = $this->createRelationMap($result, 'parent_itemid', 'hostid', 'host_discovery');
				$hostPrototypes = API::HostPrototype()->get([
					'output' => $options['selectHostPrototypes'],
					'hostids' => $relationMap->getRelatedIds(),
					'nopermissions' => true,
					'preservekeys' => true
				]);
				$result = $relationMap->mapMany($result, $hostPrototypes, 'hostPrototypes', $options['limitSelects']);
			}
			else {
				$hostPrototypes = API::HostPrototype()->get([
					'discoveryids' => $itemIds,
					'nopermissions' => true,
					'countOutput' => true,
					'groupCount' => true
				]);
				$hostPrototypes = zbx_toHash($hostPrototypes, 'parent_itemid');

				foreach ($result as $itemid => $item) {
					$result[$itemid]['hostPrototypes'] = array_key_exists($itemid, $hostPrototypes)
						? $hostPrototypes[$itemid]['rowscount']
						: '0';
				}
			}
		}

		if ($options['selectApplicationPrototypes'] !== null
				&& $options['selectApplicationPrototypes'] != API_OUTPUT_COUNT) {
			$relation_map = $this->createRelationMap($result, 'itemid', 'application_prototypeid',
				'application_prototype'
			);

			$application_prototypes = API::getApiService()->select('application_prototype', [
				'output' => $options['selectApplicationPrototypes'],
				'filter' => ['application_prototypeid' => $relation_map->getRelatedIds()],
				'limit' => $options['limitSelects'],
				'preservekeys' => true
			]);

			$result = $relation_map->mapMany($result, $application_prototypes, 'applicationPrototypes',
				$options['limitSelects']
			);
		}

		if ($options['selectFilter'] !== null) {
			$formulaRequested = $this->outputIsRequested('formula', $options['selectFilter']);
			$evalFormulaRequested = $this->outputIsRequested('eval_formula', $options['selectFilter']);
			$conditionsRequested = $this->outputIsRequested('conditions', $options['selectFilter']);

			$filters = [];
			foreach ($result as $rule) {
				$filters[$rule['itemid']] = [
					'evaltype' => $rule['evaltype'],
					'formula' => isset($rule['formula']) ? $rule['formula'] : ''
				];
			}

			// adding conditions
			if ($formulaRequested || $evalFormulaRequested || $conditionsRequested) {
				$conditions = API::getApiService()->select('item_condition', [
					'output' => ['item_conditionid', 'macro', 'value', 'itemid', 'operator'],
					'filter' => ['itemid' => $itemIds],
					'preservekeys' => true,
					'sortfield' => 'item_conditionid'
				]);
				$relationMap = $this->createRelationMap($conditions, 'itemid', 'item_conditionid');

				$filters = $relationMap->mapMany($filters, $conditions, 'conditions');

				foreach ($filters as &$filter) {
					// in case of a custom expression - use the given formula
					if ($filter['evaltype'] == CONDITION_EVAL_TYPE_EXPRESSION) {
						$formula = $filter['formula'];
					}
					// in other cases - generate the formula automatically
					else {
						// sort the conditions by macro before generating the formula
						$conditions = zbx_toHash($filter['conditions'], 'item_conditionid');
						$conditions = order_macros($conditions, 'macro');

						$formulaConditions = [];
						foreach ($conditions as $condition) {
							$formulaConditions[$condition['item_conditionid']] = $condition['macro'];
						}
						$formula = CConditionHelper::getFormula($formulaConditions, $filter['evaltype']);
					}

					// generate formulaids from the effective formula
					$formulaIds = CConditionHelper::getFormulaIds($formula);
					foreach ($filter['conditions'] as &$condition) {
						$condition['formulaid'] = $formulaIds[$condition['item_conditionid']];
					}
					unset($condition);

					// generated a letter based formula only for rules with custom expressions
					if ($formulaRequested && $filter['evaltype'] == CONDITION_EVAL_TYPE_EXPRESSION) {
						$filter['formula'] = CConditionHelper::replaceNumericIds($formula, $formulaIds);
					}

					if ($evalFormulaRequested) {
						$filter['eval_formula'] = CConditionHelper::replaceNumericIds($formula, $formulaIds);
					}
				}
				unset($filter);
			}

			// add filters to the result
			foreach ($result as &$rule) {
				$rule['filter'] = $filters[$rule['itemid']];
			}
			unset($rule);
		}

		// Add LLD macro paths.
		if ($options['selectLLDMacroPaths'] !== null && $options['selectLLDMacroPaths'] != API_OUTPUT_COUNT) {
			$lld_macro_paths = API::getApiService()->select('lld_macro_path', [
				'output' => $this->outputExtend($options['selectLLDMacroPaths'], ['itemid', 'lld_macro_pathid']),
				'filter' => ['itemid' => $itemIds],
			]);

			foreach ($result as &$lld_macro_path) {
				$lld_macro_path['lld_macro_paths'] = [];
			}
			unset($lld_macro_path);

			foreach ($lld_macro_paths as $lld_macro_path) {
				$itemid = $lld_macro_path['itemid'];

				if (!$this->outputIsRequested('lld_macro_pathid', $options['selectLLDMacroPaths'])) {
					unset($lld_macro_path['lld_macro_pathid']);
				}
				unset($lld_macro_path['itemid']);

				$result[$itemid]['lld_macro_paths'][] = $lld_macro_path;
			}
		}

		return $result;
	}
}
