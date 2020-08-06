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


class CConfigurationExportBuilder {

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param $version  current export version
	 */
	public function __construct() {
		$this->data['version'] = ZABBIX_EXPORT_VERSION;
		$this->data['date'] = date(DATE_TIME_FORMAT_SECONDS_XML, time() - date('Z'));
	}

	/**
	 * Get array with formatted export data.
	 *
	 * @return array
	 */
	public function getExport() {
		return ['zabbix_export' => $this->data];
	}

	/**
	 * Build XML data.
	 *
	 * @param array  $schema    Tag schema from validation class.
	 * @param array  $data      Export data.
	 * @param string $main_tag  XML tag (for error reporting).
	 *
	 * @return array
	 */
	protected function build(array $schema, array $data, $main_tag = null) {
		$n = 0;
		$result = [];

		$rules = $schema['rules'];

		if ($schema['type'] & XML_INDEXED_ARRAY) {
			$rules = $schema['rules'][$schema['prefix']]['rules'];
		}

		foreach ($data as $row) {
			$store = [];
			foreach ($rules as $tag => $val) {
				$is_required = $val['type'] & XML_REQUIRED;
				$is_array = $val['type'] & XML_ARRAY;
				$is_indexed_array = $val['type'] & XML_INDEXED_ARRAY;
				$has_data = array_key_exists($tag, $row);
				$default_value = array_key_exists('default', $val) ? $val['default'] : null;

				if (!$default_value && !$has_data) {
					if ($is_required) {
						throw new Exception(_s('Invalid tag "%1$s": %2$s.', $main_tag,
							_s('the tag "%1$s" is missing', $tag)
						));
					}
					continue;
				}

				$value = $has_data ? $row[$tag] : $default_value;

				if (!$is_required && $has_data && $default_value == $value) {
					continue;
				}

				if (array_key_exists('export', $val)) {
					$store[$tag] = call_user_func($val['export'], $row);
					continue;
				}

				if (($is_indexed_array || $is_array) && $has_data) {
					$temp_store = $this->build($val, $is_array ? [$value] : $value, $tag);
					if ($is_required || $temp_store) {
						$store[$tag] = $temp_store;
					}
					continue;
				}

				if (array_key_exists('in', $val)) {
					if (!array_key_exists($value, $val['in'])) {
						throw new Exception(_s('Invalid tag "%1$s": %2$s.', $tag,
							_s('unexpected constant value "%1$s"', $value)
						));
					}

					$store[$tag] = $val['in'][$value];
				}
				else {
					$store[$tag] = $value;
				}
			}

			if ($schema['type'] & XML_INDEXED_ARRAY) {
				$result[$n++] = $store;
			}
			else {
				$result = $store;
			}
		}

		return $result;
	}

	/**
	 * Format groups.
	 *
	 * @param array $schema  Tag schema from validation class.
	 * @param array $groups  Export data.
	 */
	public function buildGroups(array $schema, array $groups) {
		$groups = $this->formatGroups($groups);

		$this->data['groups'] = $this->build($schema, $groups, 'groups');
	}

	/**
	 * Format templates.
	 *
	 * @param array $schema           Tag schema from validation class.
	 * @param array $templates        Export data.
	 * @param array $simple_triggers  Simple triggers.
	 */
	public function buildTemplates(array $schema, array $templates, array $simple_triggers) {
		$templates = $this->formatTemplates($templates, $simple_triggers);

		$this->data['templates'] = $this->build($schema, $templates, 'templates');
	}

	/**
	 * Format hosts.
	 *
	 * @param array $schema           Tag schema from validation class.
	 * @param array $hosts            Export data.
	 * @param array $simple_triggers  Simple triggers.
	 */
	public function buildHosts(array $schema, array $hosts, array $simple_triggers) {
		$hosts = $this->formatHosts($hosts, $simple_triggers);

		$this->data['hosts'] = $this->build($schema, $hosts, 'hosts');
	}

	/**
	 * Format triggers.
	 *
	 * @param array $schema    Tag schema from validation class.
	 * @param array $triggers  Export data.
	 */
	public function buildTriggers(array $schema, array $triggers) {
		$triggers = $this->formatTriggers($triggers);

		$this->data['triggers'] = $this->build($schema, $triggers, 'triggers');
	}

	/**
	 * Format graphs.
	 *
	 * @param array $schema  Tag schema from validation class.
	 * @param array $graphs  Export data.
	 */
	public function buildGraphs(array $schema, array $graphs) {
		$graphs = $this->formatGraphs($graphs);

		$this->data['graphs'] = $this->build($schema, $graphs, 'graphs');
	}

	/**
	 * Format screens.
	 *
	 * @param array $screens
	 */
	public function buildScreens(array $screens) {
		$this->data['screens'] = $this->formatScreens($screens);
	}

	/**
	 * Format media types.
	 *
	 * @param array $schema       Tag schema from validation class.
	 * @param array $media_types  Export data.
	 */
	public function buildMediaTypes(array $schema, array $media_types) {
		$media_types = $this->formatMediaTypes($media_types);

		$this->data['media_types'] = $this->build($schema, $media_types, 'media_types');
	}

	/**
	 * Format valuemaps.
	 *
	 * @param array $schema     Tag schema from validation class.
	 * @param array $valuemaps  Export data.
	 */
	public function buildValueMaps(array $schema, array $valuemaps) {
		$valuemaps = $this->formatValueMaps($valuemaps);

		$this->data['value_maps'] = $this->build($schema, $valuemaps, 'value_maps');
	}

	/**
	 * Separate simple triggers.
	 *
	 * @param array $triggers
	 *
	 * @return array
	 */
	public function extractSimpleTriggers(array &$triggers) {
		$simple_triggers = [];

		foreach ($triggers as $triggerid => $trigger) {
			if (count($trigger['items']) == 1 && $trigger['items'][0]['type'] != ITEM_TYPE_HTTPTEST
					&& $trigger['items'][0]['templateid'] == 0) {
				$simple_triggers[] = $trigger;
				unset($triggers[$triggerid]);
			}
		}

		return $simple_triggers;
	}

	/**
	 * Format templates.
	 *
	 * @param array $templates
	 * @param array $simple_triggers
	 */
	protected function formatTemplates(array $templates, array $simple_triggers = null) {
		$result = [];

		CArrayHelper::sort($templates, ['host']);

		foreach ($templates as $template) {
			$result[] = [
				'template' => $template['host'],
				'name' => $template['name'],
				'description' => $template['description'],
				'groups' => $this->formatGroups($template['groups']),
				'applications' => $this->formatApplications($template['applications']),
				'items' => $this->formatItems($template['items'], $simple_triggers),
				'discovery_rules' => $this->formatDiscoveryRules($template['discoveryRules']),
				'httptests' => $this->formatHttpTests($template['httptests']),
				'macros' => $this->formatMacros($template['macros']),
				'templates' => $this->formatTemplateLinkage($template['parentTemplates']),
				'screens' => $this->formatScreens($template['screens']),
				'tags' => $this->formatTags($template['tags'])
			];
		}

		return $result;
	}

	/**
	 * Format hosts.
	 *
	 * @param array $hosts
	 * @param array $simple_triggers
	 */
	protected function formatHosts(array $hosts, array $simple_triggers = null) {
		$result = [];

		CArrayHelper::sort($hosts, ['host']);

		foreach ($hosts as $host) {
			$host = $this->createInterfaceReferences($host);

			$result[] = [
				'host' => $host['host'],
				'name' => $host['name'],
				'description' => $host['description'],
				'proxy' => $host['proxy'],
				'status' => $host['status'],
				'ipmi_authtype' => $host['ipmi_authtype'],
				'ipmi_privilege' => $host['ipmi_privilege'],
				'ipmi_username' => $host['ipmi_username'],
				'ipmi_password' => $host['ipmi_password'],
				'tls_connect' => $host['tls_connect'],
				'tls_accept' => $host['tls_accept'],
				'tls_issuer' => $host['tls_issuer'],
				'tls_subject' => $host['tls_subject'],
				'tls_psk_identity' => $host['tls_psk_identity'],
				'tls_psk' => $host['tls_psk'],
				'templates' => $this->formatTemplateLinkage($host['parentTemplates']),
				'groups' => $this->formatGroups($host['groups']),
				'interfaces' => $this->formatHostInterfaces($host['interfaces']),
				'applications' => $this->formatApplications($host['applications']),
				'items' => $this->formatItems($host['items'], $simple_triggers),
				'discovery_rules' => $this->formatDiscoveryRules($host['discoveryRules']),
				'httptests' => $this->formatHttpTests($host['httptests']),
				'macros' => $this->formatMacros($host['macros']),
				'inventory_mode' => $host['inventory_mode'],
				'inventory' => $this->formatHostInventory($host['inventory']),
				'tags' => $this->formatTags($host['tags'])
			];
		}

		return $result;
	}

	/**
	 * Format images.
	 *
	 * @param array $images
	 */
	public function buildImages(array $images) {
		$this->data['images'] = [];

		foreach ($images as $image) {
			$this->data['images'][] = [
				'name' => $image['name'],
				'imagetype' => $image['imagetype'],
				'encodedImage' => $image['encodedImage']
			];
		}
	}

	/**
	 * Format maps.
	 *
	 * @param array $maps
	 */
	public function buildMaps(array $maps) {
		$this->data['maps'] = [];

		CArrayHelper::sort($maps, ['name']);

		foreach ($maps as $map) {
			$tmpSelements = $this->formatMapElements($map['selements']);
			$this->data['maps'][] = [
				'name' => $map['name'],
				'width' => $map['width'],
				'height' => $map['height'],
				'label_type' => $map['label_type'],
				'label_location' => $map['label_location'],
				'highlight' => $map['highlight'],
				'expandproblem' => $map['expandproblem'],
				'markelements' => $map['markelements'],
				'show_unack' => $map['show_unack'],
				'severity_min' => $map['severity_min'],
				'show_suppressed' => $map['show_suppressed'],
				'grid_size' => $map['grid_size'],
				'grid_show' => $map['grid_show'],
				'grid_align' => $map['grid_align'],
				'label_format' => $map['label_format'],
				'label_type_host' => $map['label_type_host'],
				'label_type_hostgroup' => $map['label_type_hostgroup'],
				'label_type_trigger' => $map['label_type_trigger'],
				'label_type_map' => $map['label_type_map'],
				'label_type_image' => $map['label_type_image'],
				'label_string_host' => $map['label_string_host'],
				'label_string_hostgroup' => $map['label_string_hostgroup'],
				'label_string_trigger' => $map['label_string_trigger'],
				'label_string_map' => $map['label_string_map'],
				'label_string_image' => $map['label_string_image'],
				'expand_macros' => $map['expand_macros'],
				'background' => $map['backgroundid'],
				'iconmap' => $map['iconmap'],
				'urls' => $this->formatMapUrls($map['urls']),
				'selements' => $tmpSelements,
				'shapes' => $map['shapes'],
				'lines' => $map['lines'],
				'links' => $this->formatMapLinks($map['links'], $tmpSelements)
			];
		}
	}

	/**
	 * Format mappings.
	 *
	 * @param array $mappings
	 *
	 * @return array
	 */
	protected function formatMappings(array $mappings) {
		$result = [];

		CArrayHelper::sort($mappings, ['value']);

		foreach ($mappings as $mapping) {
			$result[] = [
				'value' => $mapping['value'],
				'newvalue' => $mapping['newvalue']
			];
		}

		return $result;
	}

	/**
	 * Format media types.
	 *
	 * @param array $media_types
	 */
	protected function formatMediaTypes(array $media_types) {
		$result = [];

		CArrayHelper::sort($media_types, ['name']);

		foreach ($media_types as $media_type) {
			$result[] = [
				'name' => $media_type['name'],
				'type' => $media_type['type'],
				'smtp_server' => $media_type['smtp_server'],
				'smtp_port' => $media_type['smtp_port'],
				'smtp_helo' => $media_type['smtp_helo'],
				'smtp_email' => $media_type['smtp_email'],
				'smtp_security' => $media_type['smtp_security'],
				'smtp_verify_host' => $media_type['smtp_verify_host'],
				'smtp_verify_peer' => $media_type['smtp_verify_peer'],
				'smtp_authentication' => $media_type['smtp_authentication'],
				'username' => $media_type['username'],
				'password' => $media_type['passwd'],
				'content_type' => $media_type['content_type'],
				'script_name' => $media_type['exec_path'],
				'parameters' => ($media_type['type'] == MEDIA_TYPE_WEBHOOK)
					? $media_type['parameters']
					: $media_type['exec_params'],
				'gsm_modem' => $media_type['gsm_modem'],
				'status' => $media_type['status'],
				'max_sessions' => $media_type['maxsessions'],
				'attempts' => $media_type['maxattempts'],
				'attempt_interval' => $media_type['attempt_interval'],
				'script' => $media_type['script'],
				'timeout' => $media_type['timeout'],
				'process_tags' => $media_type['process_tags'],
				'show_event_menu' => $media_type['show_event_menu'],
				'event_menu_url' => $media_type['event_menu_url'],
				'event_menu_name' => $media_type['event_menu_name'],
				'description' => $media_type['description']
			];
		}

		return $result;
	}

	/**
	 * Format value maps.
	 *
	 * @param array $valuemaps
	 */
	protected function formatValueMaps(array $valuemaps) {
		$result = [];

		CArrayHelper::sort($valuemaps, ['name']);

		foreach ($valuemaps as $valuemap) {
			$result[] = [
				'name' => $valuemap['name'],
				'mappings' => $this->formatMappings($valuemap['mappings'])
			];
		}

		return $result;
	}

	/**
	 * For each host interface an unique reference must be created and then added for all items, discovery rules
	 * and item prototypes that use the interface.
	 *
	 * @param array $host
	 *
	 * @return array
	 */
	protected function createInterfaceReferences(array $host) {
		$references = [
			'num' => 1,
			'refs' => []
		];

		// create interface references
		foreach ($host['interfaces'] as &$interface) {
			$refNum = $references['num']++;
			$referenceKey = 'if'.$refNum;
			$interface['interface_ref'] = $referenceKey;
			$references['refs'][$interface['interfaceid']] = $referenceKey;
		}
		unset($interface);

		foreach ($host['items'] as &$item) {
			if ($item['interfaceid']) {
				$item['interface_ref'] = $references['refs'][$item['interfaceid']];
			}
		}
		unset($item);

		foreach ($host['discoveryRules'] as &$discoveryRule) {
			if ($discoveryRule['interfaceid']) {
				$discoveryRule['interface_ref'] = $references['refs'][$discoveryRule['interfaceid']];
			}

			foreach ($discoveryRule['itemPrototypes'] as &$prototype) {
				if ($prototype['interfaceid']) {
					$prototype['interface_ref'] = $references['refs'][$prototype['interfaceid']];
				}
			}
			unset($prototype);
		}
		unset($discoveryRule);

		return $host;
	}

	/**
	 * Format discovery rules.
	 *
	 * @param array $discoveryRules
	 *
	 * @return array
	 */
	protected function formatDiscoveryRules(array $discoveryRules) {
		$result = [];

		CArrayHelper::sort($discoveryRules, ['key_']);

		$simple_trigger_prototypes = [];

		foreach ($discoveryRules as $discoveryRule) {
			foreach ($discoveryRule['triggerPrototypes'] as $i => $trigger_prototype) {
				if (count($trigger_prototype['items']) == 1) {
					$simple_trigger_prototypes[] = $trigger_prototype;
					unset($discoveryRule['triggerPrototypes'][$i]);
				}
			}

			$data = [
				'name' => $discoveryRule['name'],
				'type' => $discoveryRule['type'],
				'snmp_community' => $discoveryRule['snmp_community'],
				'snmp_oid' => $discoveryRule['snmp_oid'],
				'key' => $discoveryRule['key_'],
				'delay' => $discoveryRule['delay'],
				'status' => $discoveryRule['status'],
				'allowed_hosts' => $discoveryRule['trapper_hosts'],
				'snmpv3_contextname' => $discoveryRule['snmpv3_contextname'],
				'snmpv3_securityname' => $discoveryRule['snmpv3_securityname'],
				'snmpv3_securitylevel' => $discoveryRule['snmpv3_securitylevel'],
				'snmpv3_authprotocol' => $discoveryRule['snmpv3_authprotocol'],
				'snmpv3_authpassphrase' => $discoveryRule['snmpv3_authpassphrase'],
				'snmpv3_privprotocol' => $discoveryRule['snmpv3_privprotocol'],
				'snmpv3_privpassphrase' => $discoveryRule['snmpv3_privpassphrase'],
				'params' => $discoveryRule['params'],
				'ipmi_sensor' => $discoveryRule['ipmi_sensor'],
				'authtype' => $discoveryRule['authtype'],
				'username' => $discoveryRule['username'],
				'password' => $discoveryRule['password'],
				'publickey' => $discoveryRule['publickey'],
				'privatekey' => $discoveryRule['privatekey'],
				'port' => $discoveryRule['port'],
				'filter' => $discoveryRule['filter'],
				'lifetime' => $discoveryRule['lifetime'],
				'description' => $discoveryRule['description'],
				'item_prototypes' => $this->formatItems($discoveryRule['itemPrototypes'], $simple_trigger_prototypes),
				'trigger_prototypes' => $this->formatTriggers($discoveryRule['triggerPrototypes']),
				'graph_prototypes' => $this->formatGraphs($discoveryRule['graphPrototypes']),
				'host_prototypes' => $this->formatHostPrototypes($discoveryRule['hostPrototypes']),
				'jmx_endpoint' => $discoveryRule['jmx_endpoint'],
				'timeout' => $discoveryRule['timeout'],
				'url' => $discoveryRule['url'],
				'query_fields' => $discoveryRule['query_fields'],
				'posts' => $discoveryRule['posts'],
				'status_codes' => $discoveryRule['status_codes'],
				'follow_redirects' => $discoveryRule['follow_redirects'],
				'post_type' => $discoveryRule['post_type'],
				'http_proxy' => $discoveryRule['http_proxy'],
				'headers' => $discoveryRule['headers'],
				'retrieve_mode' => $discoveryRule['retrieve_mode'],
				'request_method' => $discoveryRule['request_method'],
				'allow_traps' => $discoveryRule['allow_traps'],
				'ssl_cert_file' => $discoveryRule['ssl_cert_file'],
				'ssl_key_file' => $discoveryRule['ssl_key_file'],
				'ssl_key_password' => $discoveryRule['ssl_key_password'],
				'verify_peer' => $discoveryRule['verify_peer'],
				'verify_host' => $discoveryRule['verify_host'],
				'lld_macro_paths' => $discoveryRule['lld_macro_paths'],
				'preprocessing' => $discoveryRule['preprocessing']
			];

			if (isset($discoveryRule['interface_ref'])) {
				$data['interface_ref'] = $discoveryRule['interface_ref'];
			}

			if ($discoveryRule['query_fields']) {
				$query_fields = [];

				foreach ($discoveryRule['query_fields'] as $query_field) {
					$query_fields[] = [
						'name' => key($query_field),
						'value' => reset($query_field)
					];
				}

				$data['query_fields'] = $query_fields;
			}

			if ($discoveryRule['headers']) {
				$headers = [];

				foreach ($discoveryRule['headers'] as $name => $value) {
					$headers[] = compact('name', 'value');
				}

				$data['headers'] = $headers;
			}

			$data['master_item'] = ($discoveryRule['type'] == ITEM_TYPE_DEPENDENT)
				? ['key' => $discoveryRule['master_item']['key_']]
				: [];

			$result[] = $data;
		}

		return $result;
	}

	/**
	 * Format web scenarios.
	 *
	 * @param array $httptests
	 *
	 * @return array
	 */
	protected function formatHttpTests(array $httptests) {
		$result = [];

		order_result($httptests, 'name');

		foreach ($httptests as $httptest) {
			$result[] = [
				'name' => $httptest['name'],
				'application' => $httptest['application'],
				'delay' => $httptest['delay'],
				'attempts' => $httptest['retries'],
				'agent' => $httptest['agent'],
				'http_proxy' => $httptest['http_proxy'],
				'variables' => $httptest['variables'],
				'headers' => $httptest['headers'],
				'status' => $httptest['status'],
				'authentication' => $httptest['authentication'],
				'http_user' => $httptest['http_user'],
				'http_password' => $httptest['http_password'],
				'verify_peer' => $httptest['verify_peer'],
				'verify_host' => $httptest['verify_host'],
				'ssl_cert_file' => $httptest['ssl_cert_file'],
				'ssl_key_file' => $httptest['ssl_key_file'],
				'ssl_key_password' => $httptest['ssl_key_password'],
				'steps' => $this->formatHttpSteps($httptest['steps'])
			];
		}

		return $result;
	}

	/**
	 * Format web scenario steps.
	 *
	 * @param array $httpsteps
	 *
	 * @return array
	 */
	protected function formatHttpSteps(array $httpsteps) {
		$result = [];

		order_result($httpsteps, 'no');

		foreach ($httpsteps as $httpstep) {
			$result[] = [
				'name' => $httpstep['name'],
				'url' => $httpstep['url'],
				'query_fields' => $httpstep['query_fields'],
				'posts' => $httpstep['posts'],
				'variables' => $httpstep['variables'],
				'headers' => $httpstep['headers'],
				'follow_redirects' => $httpstep['follow_redirects'],
				'retrieve_mode' => $httpstep['retrieve_mode'],
				'timeout' => $httpstep['timeout'],
				'required' => $httpstep['required'],
				'status_codes' => $httpstep['status_codes']
			];
		}

		return $result;
	}

	/**
	 * Format host inventory.
	 *
	 * @param array $inventory
	 *
	 * @return array
	 */
	protected function formatHostInventory(array $inventory) {
		unset($inventory['hostid']);

		return $inventory;
	}

	/**
	 * Format graphs.
	 *
	 * @param array $graphs
	 *
	 * @return array
	 */
	protected function formatGraphs(array $graphs) {
		$result = [];

		CArrayHelper::sort($graphs, ['name']);

		foreach ($graphs as $graph) {
			$result[] = [
				'name' => $graph['name'],
				'width' => $graph['width'],
				'height' => $graph['height'],
				'yaxismin' => $graph['yaxismin'],
				'yaxismax' => $graph['yaxismax'],
				'show_work_period' => $graph['show_work_period'],
				'show_triggers' => $graph['show_triggers'],
				'type' => $graph['graphtype'],
				'show_legend' => $graph['show_legend'],
				'show_3d' => $graph['show_3d'],
				'percent_left' => $graph['percent_left'],
				'percent_right' => $graph['percent_right'],
				'ymin_type_1' => $graph['ymin_type'],
				'ymax_type_1' => $graph['ymax_type'],
				'ymin_item_1' => $graph['ymin_itemid'],
				'ymax_item_1' => $graph['ymax_itemid'],
				'graph_items' => $this->formatGraphItems($graph['gitems'])
			];
		}

		return $result;
	}

	/**
	 * Format host prototypes.
	 *
	 * @param array $hostPrototypes
	 *
	 * @return array
	 */
	protected function formatHostPrototypes(array $hostPrototypes) {
		$result = [];

		CArrayHelper::sort($hostPrototypes, ['host']);

		foreach ($hostPrototypes as $hostPrototype) {
			$result[] = [
				'host' => $hostPrototype['host'],
				'name' => $hostPrototype['name'],
				'status' => $hostPrototype['status'],
				'group_links' => $this->formatGroupLinks($hostPrototype['groupLinks']),
				'group_prototypes' => $this->formatGroupPrototypes($hostPrototype['groupPrototypes']),
				'templates' => $this->formatTemplateLinkage($hostPrototype['templates']),
				'inventory_mode' => $hostPrototype['inventory_mode']
			];
		}

		return $result;
	}

	/**
	 * Format group links.
	 *
	 * @param array $groupLinks
	 *
	 * @return array
	 */
	protected function formatGroupLinks(array $groupLinks) {
		$result = [];

		CArrayHelper::sort($groupLinks, ['name']);

		foreach ($groupLinks as $groupLink) {
			$result[] = [
				'group' => $groupLink['groupid'],
			];
		}

		return $result;
	}

	/**
	 * Format group prototypes.
	 *
	 * @param array $groupPrototypes
	 *
	 * @return array
	 */
	protected function formatGroupPrototypes(array $groupPrototypes) {
		$result = [];

		CArrayHelper::sort($groupPrototypes, ['name']);

		foreach ($groupPrototypes as $groupPrototype) {
			$result[] = [
				'name' => $groupPrototype['name']
			];
		}

		return $result;
	}

	/**
	 * Format template linkage.
	 *
	 * @param array $templates
	 *
	 * @return array
	 */
	protected function formatTemplateLinkage(array $templates) {
		$result = [];

		CArrayHelper::sort($templates, ['host']);

		foreach ($templates as $template) {
			$result[] = [
				'name' => $template['host']
			];
		}

		return $result;
	}

	/**
	 * Format triggers.
	 *
	 * @param array $triggers
	 *
	 * @return array
	 */
	protected function formatTriggers(array $triggers) {
		$result = [];

		CArrayHelper::sort($triggers, ['description', 'expression', 'recovery_expression']);

		foreach ($triggers as $trigger) {
			$result[] = [
				'expression' => $trigger['expression'],
				'recovery_mode' => $trigger['recovery_mode'],
				'recovery_expression' => $trigger['recovery_expression'],
				'name' => $trigger['description'],
				'opdata' => $trigger['opdata'],
				'correlation_mode' => $trigger['correlation_mode'],
				'correlation_tag' => $trigger['correlation_tag'],
				'url' => $trigger['url'],
				'status' => $trigger['status'],
				'priority' => $trigger['priority'],
				'description' => $trigger['comments'],
				'type' => $trigger['type'],
				'manual_close' => $trigger['manual_close'],
				'dependencies' => $this->formatDependencies($trigger['dependencies']),
				'tags' => $this->formatTags($trigger['tags'])
			];
		}

		return $result;
	}

	/**
	 * Format host interfaces.
	 *
	 * @param array $interfaces
	 *
	 * @return array
	 */
	protected function formatHostInterfaces(array $interfaces) {
		$result = [];

		CArrayHelper::sort($interfaces, ['type', 'ip', 'dns', 'port']);

		foreach ($interfaces as $interface) {
			$result[] = [
				'default' => $interface['main'],
				'type' => $interface['type'],
				'useip' => $interface['useip'],
				'ip' => $interface['ip'],
				'dns' => $interface['dns'],
				'port' => $interface['port'],
				'bulk' => $interface['bulk'],
				'interface_ref' => $interface['interface_ref']
			];
		}

		return $result;
	}

	/**
	 * Format groups.
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	protected function formatGroups(array $groups) {
		$result = [];

		CArrayHelper::sort($groups, ['name']);

		foreach ($groups as $group) {
			$result[] = [
				'name' => $group['name']
			];
		}

		return $result;
	}

	/**
	 * Format items.
	 *
	 * @param array $items
	 * @param array $simple_triggers
	 *
	 * @return array
	 */
	protected function formatItems(array $items, array $simple_triggers) {
		$result = [];
		$expression_data = $simple_triggers ? new CTriggerExpression() : null;

		CArrayHelper::sort($items, ['key_']);

		foreach ($items as $item) {
			$data = [
				'name' => $item['name'],
				'type' => $item['type'],
				'snmp_community' => $item['snmp_community'],
				'snmp_oid' => $item['snmp_oid'],
				'key' => $item['key_'],
				'delay' => $item['delay'],
				'history' => $item['history'],
				'trends' => $item['trends'],
				'status' => $item['status'],
				'value_type' => $item['value_type'],
				'allowed_hosts' => $item['trapper_hosts'],
				'units' => $item['units'],
				'snmpv3_contextname' => $item['snmpv3_contextname'],
				'snmpv3_securityname' => $item['snmpv3_securityname'],
				'snmpv3_securitylevel' => $item['snmpv3_securitylevel'],
				'snmpv3_authprotocol' => $item['snmpv3_authprotocol'],
				'snmpv3_authpassphrase' => $item['snmpv3_authpassphrase'],
				'snmpv3_privprotocol' => $item['snmpv3_privprotocol'],
				'snmpv3_privpassphrase' => $item['snmpv3_privpassphrase'],
				'params' => $item['params'],
				'ipmi_sensor' => $item['ipmi_sensor'],
				'authtype' => $item['authtype'],
				'username' => $item['username'],
				'password' => $item['password'],
				'publickey' => $item['publickey'],
				'privatekey' => $item['privatekey'],
				'port' => $item['port'],
				'description' => $item['description'],
				'inventory_link' => $item['inventory_link'],
				'applications' => $this->formatApplications($item['applications']),
				'valuemap' => $item['valuemap'],
				'logtimefmt' => $item['logtimefmt'],
				'preprocessing' => $item['preprocessing'],
				'jmx_endpoint' => $item['jmx_endpoint'],
				'timeout' => $item['timeout'],
				'url' => $item['url'],
				'query_fields' => $item['query_fields'],
				'posts' => $item['posts'],
				'status_codes' => $item['status_codes'],
				'follow_redirects' => $item['follow_redirects'],
				'post_type' => $item['post_type'],
				'http_proxy' => $item['http_proxy'],
				'headers' => $item['headers'],
				'retrieve_mode' => $item['retrieve_mode'],
				'request_method' => $item['request_method'],
				'output_format' => $item['output_format'],
				'allow_traps' => $item['allow_traps'],
				'ssl_cert_file' => $item['ssl_cert_file'],
				'ssl_key_file' => $item['ssl_key_file'],
				'ssl_key_password' => $item['ssl_key_password'],
				'verify_peer' => $item['verify_peer'],
				'verify_host' => $item['verify_host']
			];

			$master_item = ($item['type'] == ITEM_TYPE_DEPENDENT) ? ['key' => $item['master_item']['key_']] : [];

			if ($item['flags'] == ZBX_FLAG_DISCOVERY_PROTOTYPE) {
				$data['application_prototypes'] = $this->formatApplications($item['applicationPrototypes']);
			}

			$data['master_item'] = $master_item;

			if (isset($item['interface_ref'])) {
				$data['interface_ref'] = $item['interface_ref'];
			}

			if ($item['query_fields']) {
				$query_fields = [];

				foreach ($item['query_fields'] as $query_field) {
					$query_fields[] = [
						'name' => key($query_field),
						'value' => reset($query_field)
					];
				}

				$data['query_fields'] = $query_fields;
			}

			if ($item['headers']) {
				$headers = [];

				foreach ($item['headers'] as $name => $value) {
					$headers[] = compact('name', 'value');
				}

				$data['headers'] = $headers;
			}

			if ($simple_triggers) {
				$triggers = [];
				$prefix_length = strlen($item['host'].':'.$item['key_'].'.');

				foreach ($simple_triggers as $simple_trigger) {
					if (bccomp($item['itemid'], $simple_trigger['items'][0]['itemid']) == 0) {
						if ($expression_data->parse($simple_trigger['expression'])) {
							foreach (array_reverse($expression_data->expressions) as $expression) {
								if ($expression['host'] === $item['host'] && $expression['item'] === $item['key_']) {
									$simple_trigger['expression'] = substr_replace($simple_trigger['expression'], '',
										$expression['pos'] + 1, $prefix_length
									);
								}
							}
						}

						if ($simple_trigger['recovery_mode'] == ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION
								&& $expression_data->parse($simple_trigger['recovery_expression'])) {
							foreach (array_reverse($expression_data->expressions) as $expression) {
								if ($expression['host'] === $item['host'] && $expression['item'] === $item['key_']) {
									$simple_trigger['recovery_expression'] = substr_replace(
										$simple_trigger['recovery_expression'], '', $expression['pos'] + 1,
										$prefix_length
									);
								}
							}
						}

						$triggers[] = $simple_trigger;
					}
				}

				if ($triggers) {
					$key = array_key_exists('discoveryRule', $item) ? 'trigger_prototypes' : 'triggers';
					$data[$key] = $this->formatTriggers($triggers);
				}
			}

			$result[] = $data;
		}

		return $result;
	}

	/**
	 * Format applications.
	 *
	 * @param array $applications
	 *
	 * @return array
	 */
	protected function formatApplications(array $applications) {
		$result = [];

		CArrayHelper::sort($applications, ['name']);

		foreach ($applications as $application) {
			$result[] = [
				'name' => $application['name']
			];
		}

		return $result;
	}

	/**
	 * Format macros.
	 *
	 * @param array $macros
	 *
	 * @return array
	 */
	protected function formatMacros(array $macros) {
		$result = [];

		$macros = order_macros($macros, 'macro');

		foreach ($macros as $macro) {
			$result[] = [
				'macro' => $macro['macro'],
				'value' => $macro['value'],
				'description' => $macro['description']
			];
		}

		return $result;
	}

	/**
	 * Format screens.
	 *
	 * @param array $screens
	 *
	 * @return array
	 */
	protected function formatScreens(array $screens) {
		$result = [];

		CArrayHelper::sort($screens, ['name']);

		foreach ($screens as $screen) {
			$result[] = [
				'name' => $screen['name'],
				'hsize' => $screen['hsize'],
				'vsize' => $screen['vsize'],
				'screen_items' => $this->formatScreenItems($screen['screenitems'])
			];
		}

		return $result;
	}

	/**
	 * Format trigger dependencies.
	 *
	 * @param array $dependencies
	 *
	 * @return array
	 */
	protected function formatDependencies(array $dependencies) {
		$result = [];

		CArrayHelper::sort($dependencies, ['description', 'expression', 'recovery_expression']);

		foreach ($dependencies as $dependency) {
			$result[] = [
				'name' => $dependency['description'],
				'expression' => $dependency['expression'],
				'recovery_expression' => $dependency['recovery_expression']
			];
		}

		return $result;
	}

	/**
	 * Format tags.
	 *
	 * @param array $tags
	 *
	 * @return array
	 */
	protected function formatTags(array $tags) {
		$result = [];

		CArrayHelper::sort($tags, ['tag', 'value']);

		foreach ($tags as $tag) {
			$result[] = [
				'tag' => $tag['tag'],
				'value' => $tag['value']
			];
		}

		return $result;
	}

	/**
	 * Format screen items.
	 *
	 * @param array $screenItems
	 *
	 * @return array
	 */
	protected function formatScreenItems(array $screenItems) {
		$result = [];

		CArrayHelper::sort($screenItems, ['y', 'x']);

		foreach ($screenItems as $screenItem) {
			$result[] = [
				'resourcetype' => $screenItem['resourcetype'],
				'width' => $screenItem['width'],
				'height' => $screenItem['height'],
				'x' => $screenItem['x'],
				'y' => $screenItem['y'],
				'colspan' => $screenItem['colspan'],
				'rowspan' => $screenItem['rowspan'],
				'elements' => $screenItem['elements'],
				'valign' => $screenItem['valign'],
				'halign' => $screenItem['halign'],
				'style' => $screenItem['style'],
				'url' => $screenItem['url'],
				'dynamic' => $screenItem['dynamic'],
				'sort_triggers' => $screenItem['sort_triggers'],
				'resource' => $screenItem['resourceid'],
				'max_columns' => $screenItem['max_columns'],
				'application' => $screenItem['application']
			];
		}

		return $result;
	}

	/**
	 * Format graph items.
	 *
	 * @param array $graphItems
	 *
	 * @return array
	 */
	protected function formatGraphItems(array $graphItems) {
		$result = [];

		CArrayHelper::sort($graphItems, ['sortorder']);

		foreach ($graphItems as $graphItem) {
			$result[] = [
				'sortorder'=> $graphItem['sortorder'],
				'drawtype'=> $graphItem['drawtype'],
				'color'=> $graphItem['color'],
				'yaxisside'=> $graphItem['yaxisside'],
				'calc_fnc'=> $graphItem['calc_fnc'],
				'type'=> $graphItem['type'],
				'item'=> $graphItem['itemid']
			];
		}

		return $result;
	}

	/**
	 * Format map urls.
	 *
	 * @param array $urls
	 *
	 * @return array
	 */
	protected function formatMapUrls(array $urls) {
		$result = [];

		CArrayHelper::sort($urls, ['name', 'url']);

		foreach ($urls as $url) {
			$result[] = [
				'name' => $url['name'],
				'url' => $url['url'],
				'elementtype' => $url['elementtype']
			];
		}

		return $result;
	}

	/**
	 * Format map element urls.
	 *
	 * @param array $urls
	 *
	 * @return array
	 */
	protected function formatMapElementUrls(array $urls) {
		$result = [];

		CArrayHelper::sort($urls, ['name', 'url']);

		foreach ($urls as $url) {
			$result[] = [
				'name' => $url['name'],
				'url' => $url['url']
			];
		}

		return $result;
	}

	/**
	 * Format map links.
	 *
	 * @param array $links			Map links
	 * @param array $selements		Map elements
	 *
	 * @return array
	 */
	protected function formatMapLinks(array $links, array $selements) {
		$result = [];

		// Get array where key is selementid and value is sort position.
		$flipped_selements = [];
		$selements = array_values($selements);

		foreach ($selements as $key => $item) {
			if (array_key_exists('selementid', $item)) {
				$flipped_selements[$item['selementid']] = $key;
			}
		}

		foreach ($links as &$link) {
			$link['selementpos1'] = $flipped_selements[$link['selementid1']];
			$link['selementpos2'] = $flipped_selements[$link['selementid2']];

			// Sort selements by position asc.
			if ($link['selementpos2'] < $link['selementpos1']) {
				zbx_swap($link['selementpos1'], $link['selementpos2']);
			}
		}
		unset($link);

		CArrayHelper::sort($links, ['selementpos1', 'selementpos2']);

		foreach ($links as $link) {
			$result[] = [
				'drawtype' => $link['drawtype'],
				'color' => $link['color'],
				'label' => $link['label'],
				'selementid1' => $link['selementid1'],
				'selementid2' => $link['selementid2'],
				'linktriggers' => $this->formatMapLinkTriggers($link['linktriggers'])
			];
		}

		return $result;
	}

	/**
	 * Format map link triggers.
	 *
	 * @param array $linktriggers
	 *
	 * @return array
	 */
	protected function formatMapLinkTriggers(array $linktriggers) {
		$result = [];

		foreach ($linktriggers as &$linktrigger) {
			$linktrigger['description'] = $linktrigger['triggerid']['description'];
			$linktrigger['expression'] = $linktrigger['triggerid']['expression'];
			$linktrigger['recovery_expression'] = $linktrigger['triggerid']['recovery_expression'];
		}
		unset($linktrigger);

		CArrayHelper::sort($linktriggers, ['description', 'expression', 'recovery_expression']);

		foreach ($linktriggers as $linktrigger) {
			$result[] = [
				'drawtype' => $linktrigger['drawtype'],
				'color' => $linktrigger['color'],
				'trigger' => $linktrigger['triggerid']
			];
		}

		return $result;
	}

	/**
	 * Format map elements.
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function formatMapElements(array $elements) {
		$result = [];

		CArrayHelper::sort($elements, ['y', 'x']);

		foreach ($elements as $element) {
			$result[] = [
				'elementtype' => $element['elementtype'],
				'label' => $element['label'],
				'label_location' => $element['label_location'],
				'x' => $element['x'],
				'y' => $element['y'],
				'elementsubtype' => $element['elementsubtype'],
				'areatype' => $element['areatype'],
				'width' => $element['width'],
				'height' => $element['height'],
				'viewtype' => $element['viewtype'],
				'use_iconmap' => $element['use_iconmap'],
				'selementid' => $element['selementid'],
				'elements' => $element['elements'],
				'icon_off' => $element['iconid_off'],
				'icon_on' => $element['iconid_on'],
				'icon_disabled' => $element['iconid_disabled'],
				'icon_maintenance' => $element['iconid_maintenance'],
				'application' => $element['application'],
				'urls' => $this->formatMapElementUrls($element['urls'])
			];
		}

		return $result;
	}
}
