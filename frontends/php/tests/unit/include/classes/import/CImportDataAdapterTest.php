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


class CImportDataAdapterTest extends PHPUnit_Framework_TestCase {

	/**
	 * Cached XMl sources
	 *
	 * @var array
	 */
	protected $sources = [];

	public function testEmptyXml() {
		$adapter = $this->getAdapter($this->getEmptyXml());

		$this->assertEquals($adapter->getGroups(), []);
		$this->assertEquals($adapter->getHosts(), []);
		$this->assertEquals($adapter->getTemplates(), []);
		$this->assertEquals($adapter->getApplications(), []);
		$this->assertEquals($adapter->getItems(), []);
		$this->assertEquals($adapter->getTriggers(), []);
		$this->assertEquals($adapter->getGraphs(), []);
		$this->assertEquals($adapter->getDiscoveryRules(), []);
		$this->assertEquals($adapter->getTemplateScreens(), []);
		$this->assertEquals($adapter->getScreens(), []);
		$this->assertEquals($adapter->getImages(), []);
		$this->assertEquals($adapter->getMaps(), []);
		$this->assertEquals($adapter->getMediaTypes(), []);
	}

	public function testGetGroups() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getGroups(), [
			[
				'name' => 'Linux servers'
			],
			[
				'name' => 'Zabbix servers'
			],
			[
				'name' => 'Templates'
			]
		]);
	}

	public function testGetHosts() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getHosts(), [
			[
				'inventory' => [],
				'proxy' => [],
				'groups' => [
					[
						'name' => 'Linux servers'
					]
				],
				'templates' => [],
				'macros' => [],
				'interfaces' => [
					[
						'type' => '1',
						'useip' => '1',
						'ip' => '127.0.0.1',
						'dns' => '',
						'port' => '10050',
						'interface_ref' => 'if1',
						'main' => '1'
					]
				],
				'host' => 'empty-host',
				'status' => '0',
				'description' => '',
				'ipmi_authtype' => '-1',
				'ipmi_privilege' => '2',
				'ipmi_username' => '',
				'ipmi_password' => '',
				'name' => 'empty-host',
				'inventory_mode' => '0',
				'tags' => [],
				'tls_connect' => '1',
				'tls_accept' => '1',
				'tls_issuer' => '',
				'tls_subject' => '',
				'tls_psk_identity' => '',
				'tls_psk' => ''
			],
			[
				'inventory' => [
					'type' => 'my-type',
					'type_full' => '',
					'name' => '',
					'alias' => '',
					'os' => '',
					'os_full' => '',
					'os_short' => '',
					'serialno_a' => '',
					'serialno_b' => '',
					'tag' => '',
					'asset_tag' => '',
					'macaddress_a' => '',
					'macaddress_b' => '',
					'hardware' => '',
					'hardware_full' => '',
					'software' => '',
					'software_full' => '',
					'software_app_a' => '',
					'software_app_b' => '',
					'software_app_c' => '',
					'software_app_d' => '',
					'software_app_e' => '',
					'contact' => '',
					'location' => '',
					'location_lat' => '',
					'location_lon' => '',
					'notes' => '',
					'chassis' => '',
					'model' => '',
					'hw_arch' => '',
					'vendor' => '',
					'contract_number' => '',
					'installer_name' => '',
					'deployment_status' => '',
					'url_a' => '',
					'url_b' => '',
					'url_c' => '',
					'host_networks' => '',
					'host_netmask' => '',
					'host_router' => '',
					'oob_ip' => '',
					'oob_netmask' => '',
					'oob_router' => '',
					'date_hw_purchase' => '',
					'date_hw_install' => '',
					'date_hw_expiry' => '',
					'date_hw_decomm' => '',
					'site_address_a' => '',
					'site_address_b' => '',
					'site_address_c' => '',
					'site_city' => '',
					'site_state' => '',
					'site_country' => '',
					'site_zip' => '',
					'site_rack' => '',
					'site_notes' => '',
					'poc_1_name' => '',
					'poc_1_email' => '',
					'poc_1_phone_a' => '',
					'poc_1_phone_b' => '',
					'poc_1_cell' => '',
					'poc_1_screen' => '',
					'poc_1_notes' => '',
					'poc_2_name' => '',
					'poc_2_email' => '',
					'poc_2_phone_a' => '',
					'poc_2_phone_b' => '',
					'poc_2_cell' => '',
					'poc_2_screen' => '',
					'poc_2_notes' => ''
				],
				'proxy' => [],
				'groups' => [
						[
							'name' => 'Linux servers'
						]
					],
				'templates' => [
					[
						'name' => 'Template App FTP Service'
					],
					[
						'name' => 'Template OS Linux by Zabbix agent'
					]
				],
				'macros' => [
					[
						'macro' => '{$MACRO}',
						'value' => '2',
						'description' => ''
					],
					[
						'macro' => '{$SNMP_COMMUNITY}',
						'value' => '1',
						'description' => ''
					]
				],
				'interfaces' => [
					[
						'type' => '1',
						'useip' => '1',
						'ip' => '0.0.0.0',
						'dns' => '',
						'port' => '10050',
						'interface_ref' => 'if1',
						'main' => '1'
					],
					[
						'type' => '3',
						'useip' => '1',
						'ip' => '127.0.0.1',
						'dns' => '',
						'port' => '623',
						'interface_ref' => 'if4',
						'main' => '1'
					],
					[
						'type' => '2',
						'useip' => '1',
						'ip' => '127.0.0.1',
						'dns' => '',
						'port' => '161',
						'bulk' => '1',
						'interface_ref' => 'if2',
						'main' => '1'
					],
					[
						'type' => '4',
						'useip' => '1',
						'ip' => '127.0.0.1',
						'dns' => '',
						'port' => '12345',
						'interface_ref' => 'if3',
						'main' => '1'
					]
				],
				'host' => 'export-host',
				'status' => '0',
				'description' => '',
				'ipmi_authtype' => '-1',
				'ipmi_privilege' => '2',
				'ipmi_username' => 'impi username',
				'ipmi_password' => 'impi password',
				'name' => 'export-host',
				'inventory_mode' => '0',
				'tags' => [],
				'tls_connect' => '1',
				'tls_accept' => '1',
				'tls_issuer' => '',
				'tls_subject' => '',
				'tls_psk_identity' => '',
				'tls_psk' => '',
			]
		]);
	}

	public function testGetTemplates() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getTemplates(), [
			[
				'groups' => [
					[
						'name' => 'Templates'
					]
				],
				'macros' => [],
				'templates' => [],
				'host' => 'empty-template',
				'name' => 'empty-template',
				'description' => '',
				'tags' => []
			],
			[
				'groups' =>	[
					[
						'name' => 'Templates'
					]
				],
				'macros' => [
					[
						'macro' => '{$MACRO}',
						'value' => '2',
						'description' => ''
					],
					[
						'macro' => '{$SNMP_COMMUNITY}',
						'value' => '1',
						'description' => ''
					]
				],
				'templates' => [
					[
						'name' => 'Template App FTP Service'
					],
					[
						'name' => 'Template OS Linux by Zabbix agent'
					]
				],
				'host' => 'export-template',
				'name' => 'export-template',
				'description' => '',
				'tags' => []
			]
		]);
	}

	public function testGetApplications() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getApplications(), [
			'export-host' => [
				'app' => [
					'name' => 'app'
				]
			],
			'export-template' => [
				'app' => [
					'name' => 'app'
				]
			]
		]);
	}

	public function testGetItems() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getItems(), [
			'export-host' => [
				'item' => [
					'name' => 'item',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [],
					'valuemap' => [],
					'logtimefmt' => '',
					'interface_ref' => 'if1',
					'jmx_endpoint' => '',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item',
					'trapper_hosts' => '',
					'preprocessing' => []
				],
				'item-jmx' => [
					'name' => 'item-jmx',
					'type' => '16',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [],
					'valuemap' => [],
					'logtimefmt' => '',
					'interface_ref' => 'if3',
					'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item-jmx',
					'trapper_hosts' => '',
					'preprocessing' => []
				],
				'item2' => [
					'name' => 'item2',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [
						[
							'name' => 'app'
						]
					],
					'valuemap' => [],
					'logtimefmt' => '',
					'interface_ref' => 'if1',
					'jmx_endpoint' => '',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item2',
					'trapper_hosts' => '',
					'preprocessing' => []
				]
			],
			'export-template' => [
				'item' => [
					'name' => 'item',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [],
					'valuemap' => [],
					'logtimefmt' => '',
					'jmx_endpoint' => '',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item',
					'trapper_hosts' => '',
					'preprocessing' => []
				],
				'item-jmx' => [
					'name' => 'item-jmx',
					'type' => '16',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [],
					'valuemap' => [],
					'logtimefmt' => '',
					'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item-jmx',
					'trapper_hosts' => '',
					'preprocessing' => []
				],
				'item2' => [
					'name' => 'item2',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'history' => '90d',
					'trends' => '365d',
					'status' => '0',
					'value_type' => '3',
					'units' => '',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'description' => '',
					'inventory_link' => '0',
					'applications' => [
						[
							'name' => 'app'
						]
					],
					'valuemap' => [],
					'logtimefmt' => '',
					'jmx_endpoint' => '',
					'master_item' => [],
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'output_format' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'triggers' => [],
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'item2',
					'trapper_hosts' => '',
					'preprocessing' => []
				]
			]
		]);
	}

	public function testGetTriggers() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getTriggers(), [
			[
				'expression' => '{export-host:item.last(0)}<>0',
				'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
				'recovery_expression' => '',
				'url' => '',
				'status' => '0',
				'priority' => '0',
				'type' => '0',
				'dependencies' =>[
					[
						'name' => 'trigger2',
						'expression' => '{export-host:item.last(0)}<>0',
						'recovery_expression' => ''
					]
				],
				'tags' => [],
				'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
				'correlation_tag' => '',
				'opdata' => '',
				'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
				'comments' => '',
				'description' => 'trigger'
			],
			[
				'expression' => '{export-host:item.last(0)}<>0',
				'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
				'recovery_expression' => '',
				'url' => '',
				'status' => '0',
				'priority' => '0',
				'type' => '0',
				'dependencies' => [],
				'tags' => [],
				'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
				'correlation_tag' => '',
				'opdata' => '',
				'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
				'comments' => '',
				'description' => 'trigger2'
			],
			[
				'expression' => '{export-template:item.last(0)}<>0',
				'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
				'recovery_expression' => '',
				'url' => '',
				'status' => '0',
				'priority' => '0',
				'type' => '0',
				'dependencies' =>[
					[
						'name' => 'trigger2',
						'expression' => '{export-template:item.last(0)}<>0',
						'recovery_expression' => ''
					]
				],
				'tags' => [],
				'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
				'correlation_tag' => '',
				'opdata' => '',
				'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
				'comments' => '',
				'description' => 'trigger'
			],
			[
				'expression' => '{export-template:item.last(0)}<>0',
				'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
				'recovery_expression' => '',
				'url' => '',
				'status' => '0',
				'priority' => '0',
				'type' => '0',
				'dependencies' => [],
				'tags' => [],
				'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
				'correlation_tag' => '',
				'opdata' => '',
				'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
				'comments' => '',
				'description' => 'trigger2'
			]
		]);
	}

	public function testGetGraphs() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getGraphs(), [
			[
				'name' => 'simple',
				'width' => '900',
				'height' => '200',
				'yaxismin' => '0.0000',
				'yaxismax' => '100.0000',
				'show_work_period' => '0',
				'show_triggers' => '0',
				'show_legend' => '1',
				'show_3d' => '0',
				'percent_left' => '0.0000',
				'percent_right' => '0.0000',
				'ymin_item_1' => '0',
				'ymax_item_1' => '0',
				'graphtype' => '0',
				'ymin_type' => '0',
				'ymax_type' => '0',
				'gitems' => [
					[
						'sortorder' => '0',
						'drawtype' => '0',
						'color' => '009900',
						'yaxisside' => '0',
						'calc_fnc' => '2',
						'type' => '0',
						'item' => [
							'host' => 'export-host',
							'key' => 'item'
						]
					]
				]
			],
			[
				'name' => 'simple',
				'width' => '900',
				'height' => '200',
				'yaxismin' => '0.0000',
				'yaxismax' => '100.0000',
				'show_work_period' => '0',
				'show_triggers' => '0',
				'show_legend' => '1',
				'show_3d' => '0',
				'percent_left' => '0.0000',
				'percent_right' => '0.0000',
				'ymin_item_1' => '0',
				'ymax_item_1' => '0',
				'graphtype' => '0',
				'ymin_type' => '0',
				'ymax_type' => '0',
				'gitems' => [
					[
						'sortorder' => '0',
						'drawtype' => '0',
						'color' => '009900',
						'yaxisside' => '0',
						'calc_fnc' => '2',
						'type' => '0',
						'item' => [
							'host' => 'export-template',
							'key' => 'item'
						]
					]
				]
			]
		]);
	}

	public function testGetDiscoveryRules() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getDiscoveryRules(), [
			'export-host' => [
				'empty-lld-rule' => [
					'name' => 'empty-lld-rule',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => 0,
						'formula' => '',
						'conditions' => []
					],
					'lifetime' => '30d',
					'description' => '',
					'item_prototypes' => [],
					'trigger_prototypes' => [],
					'graph_prototypes' => [],
					'host_prototypes' => [],
					'interface_ref' => 'if1',
					'jmx_endpoint' => '',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'empty-lld-rule',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				],
				'empty-lld-rule-jmx' => [
					'name' => 'empty-lld-rule-jmx',
					'type' => '16',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => 0,
						'formula' => '',
						'conditions' => []
					],
					'lifetime' => '30d',
					'description' => '',
					'item_prototypes' => [],
					'trigger_prototypes' => [],
					'graph_prototypes' => [],
					'host_prototypes' => [],
					'interface_ref' => 'if3',
					'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'empty-lld-rule-jmx',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				],
				'lld-rule' => [
					'name' => 'lld-rule',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => '0',
						'formula' => '',
						'conditions' => [
							[
								'macro' => '{#FSTYPE}',
								'value' => '1',
								'operator' => '8',
								'formulaid' => 'A'
							],
							[
								'macro' => '{#FSTYPE2}',
								'value' => '2',
								'operator' => '8',
								'formulaid' => 'B'
							]
						]
					],
					'lifetime' => '30d',
					'interface_ref' => 'if3',
					'description' => '',
					'item_prototypes' => [
						[
							'name' => 'lld-item',
							'type' => '0',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'applications' => [],
							'valuemap' => [],
							'logtimefmt' => '',
							'interface_ref' => 'if1',
							'jmx_endpoint' => '',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item',
							'trapper_hosts' => '',
							'application_prototypes' => [],
							'preprocessing' => []
						],
						[
							'name' => 'lld-item-jmx',
							'type' => '16',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'applications' => [],
							'valuemap' => [],
							'logtimefmt' => '',
							'interface_ref' => 'if3',
							'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item-jmx',
							'trapper_hosts' => '',
							'application_prototypes' => [],
							'preprocessing' => [],
						],
						[
							'name' => 'lld-item2',
							'type' => '0',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'application_prototypes' => [],
							'applications' => [
								[
									'name' => 'app'
								]
							],
							'valuemap' => [],
							'logtimefmt' => '',
							'interface_ref' => 'if1',
							'jmx_endpoint' => '',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item2',
							'trapper_hosts' => '',
							'preprocessing' => []
						]
					],
					'trigger_prototypes' => [
						[
							'expression' => '{export-host:lld-item.last()}=0',
							'description' => 'lld-trigger',
							'url' => '',
							'status' => '0',
							'priority' => '0',
							'comments' => '',
							'type' => '0',
							'recovery_expression' => '',
							'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
							'tags' => [],
							'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
							'correlation_tag' => '',
							'opdata' => '',
							'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
							'dependencies' => []
						]
					],
					'graph_prototypes' => [
						[
							'name' => 'lld-graph',
							'width' => '900',
							'height' => '200',
							'yaxismin' => '0.0000',
							'yaxismax' => '100.0000',
							'show_work_period' => '1',
							'show_triggers' => '1',
							'show_legend' => '1',
							'show_3d' => '0',
							'percent_left' => '0.0000',
							'percent_right' => '0.0000',
							'ymin_item_1' => '0',
							'ymax_item_1' => '0',
							'graphtype' => '0',
							'ymin_type' => '0',
							'ymax_type' => '0',
							'gitems' => [
								[
									'sortorder' => '0',
									'drawtype' => '0',
									'color' => '00C800',
									'yaxisside' => '0',
									'calc_fnc' => '2',
									'type' => '0',
									'item' => [
										'host' => 'export-host',
										'key' => 'lld-item'
									]
								]
							]
						]
					],
					'host_prototypes' => [
						[
							'host' => '{#VMID}',
							'name' => '{#VMID}',
							'status' => '0',
							'group_links' => [
								[
									'group' => [
										'name' => 'Zabbix servers'
									]
								]
							],
							'group_prototypes' => [
								[
									'name' => '{#VMNAME}'
								]
							],
							'inventory_mode' => '0',
							'templates' => []
						]
					],
					'jmx_endpoint' => '',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'interface_ref' => 'if1',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'lld-rule',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				]
			],
			'export-template' => [
				'empty-lld-rule' => [
					'name' => 'empty-lld-rule',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => 0,
						'formula' => '',
						'conditions' => []
					],
					'lifetime' => '30d',
					'description' => '',
					'item_prototypes' => [],
					'trigger_prototypes' => [],
					'graph_prototypes' => [],
					'host_prototypes' => [],
					'jmx_endpoint' => '',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'empty-lld-rule',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				],
				'lld-rule-jmx' => [
					'name' => 'lld-rule-jmx',
					'type' => '16',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => 0,
						'formula' => '',
						'conditions' => []
					],
					'lifetime' => '30d',
					'description' => '',
					'item_prototypes' => [],
					'trigger_prototypes' => [],
					'graph_prototypes' => [],
					'host_prototypes' => [],
					'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'lld-rule-jmx',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				],
				'lld-rule' => [
					'name' => 'lld-rule',
					'type' => '0',
					'snmp_community' => '',
					'snmp_oid' => '',
					'delay' => '30',
					'status' => '0',
					'snmpv3_contextname' => '',
					'snmpv3_securityname' => '',
					'snmpv3_securitylevel' => '0',
					'snmpv3_authprotocol' => '0',
					'snmpv3_authpassphrase' => '',
					'snmpv3_privprotocol' => '0',
					'snmpv3_privpassphrase' => '',
					'params' => '',
					'ipmi_sensor' => '',
					'authtype' => '0',
					'username' => '',
					'password' => '',
					'publickey' => '',
					'privatekey' => '',
					'port' => '',
					'filter' => [
						'evaltype' => '0',
						'formula' => '',
						'conditions' => [
							[
								'macro' => '{#FSTYPE}',
								'value' => '1',
								'operator' => '8',
								'formulaid' => 'A'
							],
							[
								'macro' => '{#FSTYPE2}',
								'value' => '2',
								'operator' => '8',
								'formulaid' => 'B'
							]
						]
					],
					'lifetime' => '30d',
					'description' => '',
					'item_prototypes' => [
						[
							'name' => 'lld-item',
							'type' => '0',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'application_prototypes' => [],
							'applications' => [],
							'valuemap' => [],
							'logtimefmt' => '',
							'jmx_endpoint' => '',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'preprocessing' => [],
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item',
							'trapper_hosts' => ''
						],
						[
							'name' => 'lld-item-jmx',
							'type' => '16',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'application_prototypes' => [],
							'applications' => [],
							'valuemap' => [],
							'logtimefmt' => '',
							'jmx_endpoint' => 'service:jmx:rmi:///jndi/rmi://{HOST.CONN}:{HOST.PORT}/jmxrmi',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'preprocessing' => [],
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item-jmx',
							'trapper_hosts' => ''
						],
						[
							'name' => 'lld-item2',
							'type' => '0',
							'snmp_community' => '',
							'snmp_oid' => '',
							'delay' => '30',
							'history' => '90d',
							'trends' => '365d',
							'status' => '0',
							'value_type' => '3',
							'units' => '',
							'snmpv3_contextname' => '',
							'snmpv3_securityname' => '',
							'snmpv3_securitylevel' => '0',
							'snmpv3_authprotocol' => '0',
							'snmpv3_authpassphrase' => '',
							'snmpv3_privprotocol' => '0',
							'snmpv3_privpassphrase' => '',
							'params' => '',
							'ipmi_sensor' => '',
							'authtype' => '0',
							'username' => '',
							'password' => '',
							'publickey' => '',
							'privatekey' => '',
							'port' => '',
							'description' => '',
							'inventory_link' => '0',
							'application_prototypes' => [],
							'applications' => [
								[
									'name' => 'app'
								]
							],
							'valuemap' => [],
							'logtimefmt' => '',
							'jmx_endpoint' => '',
							'master_item' => [],
							'timeout' => '3s',
							'url' => '',
							'posts' => '',
							'preprocessing' => [],
							'status_codes' => '200',
							'follow_redirects' => '1',
							'post_type' => '0',
							'http_proxy' => '',
							'retrieve_mode' => '0',
							'request_method' => '0',
							'output_format' => '0',
							'ssl_cert_file' => '',
							'ssl_key_file' => '',
							'ssl_key_password' => '',
							'verify_peer' => '0',
							'verify_host' => '0',
							'allow_traps' => '0',
							'query_fields' => [],
							'headers' => [],
							'key_' => 'lld-item2',
							'trapper_hosts' => ''
						]
					],
					'trigger_prototypes' => [
						[
							'expression' => '{export-template:lld-item.last()}=0',
							'description' => 'lld-trigger',
							'url' => '',
							'status' => '0',
							'priority' => '0',
							'comments' => '',
							'type' => '0',
							'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
							'recovery_expression' => '',
							'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
							'correlation_tag' => '',
							'opdata' => '',
							'tags' => [],
							'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
							'dependencies' => []
						]
					],
					'graph_prototypes' => [
						[
							'name' => 'lld-graph',
							'width' => '900',
							'height' => '200',
							'yaxismin' => '0.0000',
							'yaxismax' => '100.0000',
							'show_work_period' => '1',
							'show_triggers' => '1',
							'show_legend' => '1',
							'show_3d' => '0',
							'percent_left' => '0.0000',
							'percent_right' => '0.0000',
							'ymin_item_1' => '0',
							'ymax_item_1' => '0',
							'graphtype' => '0',
							'ymin_type' => '0',
							'ymax_type' => '0',
							'gitems' => [
								[
									'sortorder' => '0',
									'drawtype' => '0',
									'color' => '00C800',
									'yaxisside' => '0',
									'calc_fnc' => '2',
									'type' => '0',
									'item' => [
										'host' => 'export-template',
										'key' => 'lld-item'
									]
								]
							]
						]
					],
					'host_prototypes' => [
						[
							'host' => '{#VMID}',
							'name' => '{#VMID}',
							'status' => '0',
							'group_links' => [
								[
									'group' => [
										'name' => 'Zabbix servers'
									]
								]
							],
							'group_prototypes' => [
								[
									'name' => '{#VMNAME}'
								]
							],
							'templates' => []
						]
					],
					'jmx_endpoint' => '',
					'timeout' => '3s',
					'url' => '',
					'posts' => '',
					'status_codes' => '200',
					'follow_redirects' => '1',
					'post_type' => '0',
					'http_proxy' => '',
					'retrieve_mode' => '0',
					'request_method' => '0',
					'ssl_cert_file' => '',
					'ssl_key_file' => '',
					'ssl_key_password' => '',
					'verify_peer' => '0',
					'verify_host' => '0',
					'allow_traps' => '0',
					'query_fields' => [],
					'headers' => [],
					'key_' => 'lld-rule',
					'trapper_hosts' => '',
					'lld_macro_paths' => [],
					'preprocessing' => [],
					'master_item' => []
				]
			]
		]);
	}

	public function testGetTemplateScreens() {
		$adapter = $this->getAdapter($this->getHostAndTemplateXml());

		$this->assertEquals($adapter->getTemplateScreens(), [
			'export-template' => [
				'screen' => [
					'name' => 'screen',
					'hsize' => '1',
					'vsize' => '1',
					'screenitems' => [
						[
							'resourcetype' => '0',
							'width' => '500',
							'height' => '100',
							'x' => '0',
							'y' => '0',
							'colspan' => '1',
							'rowspan' => '1',
							'elements' => '0',
							'valign' => '0',
							'halign' => '0',
							'style' => '0',
							'url' => '',
							'dynamic' => '0',
							'sort_triggers' => '0',
							'resource' => [
								'name' => 'graph',
								'host' => 'export-template'
							],
							'max_columns' => '',
							'application' => ''
						]
					]
				]
			]
		]);
	}

	public function testGetImages() {
		$adapter = $this->getAdapter($this->getMapXml());

		$this->assertEquals($adapter->getImages(), [
			[
				'name' => 'Server_(96)',
				'imagetype' => '1',
				'image' => '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',
			]
		]);
	}

	public function testGetMaps() {
		$adapter = $this->getAdapter($this->getMapXml());

		$this->assertEquals($adapter->getMaps(), [
			[
				'name' => 'empty-map',
				'width' => '800',
				'height' => '600',
				'label_type' => '0',
				'label_location' => '0',
				'highlight' => '0',
				'expandproblem' => '0',
				'markelements' => '0',
				'show_unack' => '0',
				'severity_min' => '0',
				'show_suppressed' => '0',
				'grid_size' => '50',
				'grid_show' => '1',
				'grid_align' => '1',
				'label_format' => '0',
				'label_type_host' => '2',
				'label_type_hostgroup' => '2',
				'label_type_trigger' => '2',
				'label_type_map' => '2',
				'label_type_image' => '2',
				'label_string_host' => '',
				'label_string_hostgroup' => '',
				'label_string_trigger' => '',
				'label_string_map' => '',
				'label_string_image' => '',
				'expand_macros' => '0',
				'background' => [
					'name' => ''
				],
				'iconmap' => [
					'name' => ''
				],
				'urls' => [],
				'selements' => [],
				'links' => [],
				'shapes' => [[
					'type' => 0,
					'x' => '0',
					'y' => '0',
					'width' => '800',
					'height' => 15,
					'text' => '{MAP.NAME}',
					'font' => '9',
					'font_size' => '11',
					'font_color' => '000000',
					'text_halign' => '0',
					'text_valign' => '0',
					'border_type' => '0',
					'border_width' => '1',
					'border_color' => '000000',
					'background_color' => '',
					'zindex' => '0'
				]],
				'lines' => []
			],
			[
				'name' => 'map',
				'width' => '800',
				'height' => '600',
				'label_type' => '0',
				'label_location' => '0',
				'highlight' => '0',
				'expandproblem' => '0',
				'markelements' => '0',
				'show_unack' => '0',
				'severity_min' => '0',
				'show_suppressed' => '0',
				'grid_size' => '50',
				'grid_show' => '1',
				'grid_align' => '1',
				'label_format' => '0',
				'label_type_host' => '2',
				'label_type_hostgroup' => '2',
				'label_type_trigger' => '2',
				'label_type_map' => '2',
				'label_type_image' => '2',
				'label_string_host' => '',
				'label_string_hostgroup' => '',
				'label_string_trigger' => '',
				'label_string_map' => '',
				'label_string_image' => '',
				'expand_macros' => '0',
				'background' => [
					'name' => ''
				],
				'iconmap' => [
					'name' => ''
				],
				'urls' => [
					[
						'name' => 'url',
						'url' => 'http://google.com',
						'elementtype' => '0'
					]
				],
				'selements' => [
					[
						'elementtype' => '2',
						'label' => 'New element',
						'label_location' => '-1',
						'x' => '239',
						'y' => '127',
						'elementsubtype' => '0',
						'areatype' => '0',
						'width' => '200',
						'height' => '200',
						'viewtype' => '0',
						'use_iconmap' => '0',
						'selementid' => '32',
						'icon_off' => [
							'name' => 'Server_(96)'
						],
						'icon_on' => [
							'name' => ''
						],
						'icon_disabled' => [
							'name' => ''
						],
						'icon_maintenance' => [
							'name' => ''
						],
						'application' => '',
						'urls' => [
							[
								'name' => 'url',
								'url' => 'http://google.com'
							]
						],
						'elements' => [
							[
								'description' => 'trigger',
								'expression' => '{export-host:item.last(0)}<>0 or {export-host:item.last(0)}<>0 and {export-host:item.last(0)}<>0',
								'recovery_expression' => ''
							]
						]
					],
					[
						'elementtype' => '0',
						'label' => 'New element',
						'label_location' => '-1',
						'x' => '489',
						'y' => '127',
						'elementsubtype' => '0',
						'areatype' => '0',
						'width' => '200',
						'height' => '200',
						'viewtype' => '0',
						'use_iconmap' => '0',
						'selementid' => '33',
						'icon_off' => [
							'name' => 'Server_(96)'
						],
						'icon_on' => [
							'name' => ''
						],
						'icon_disabled' => [
							'name' => ''
						],
						'icon_maintenance' => [
							'name' => ''
						],
						'application' => '',
						'urls' => [],
						'elements' => [
							[
								'host' => 'export-host'
							]
						]
					],
					[
						'elementtype' => '3',
						'label' => 'New element',
						'label_location' => '-1',
						'x' => '239',
						'y' => '377',
						'elementsubtype' => '0',
						'areatype' => '0',
						'width' => '200',
						'height' => '200',
						'viewtype' => '0',
						'use_iconmap' => '0',
						'selementid' => '34',
						'icon_off' => [
							'name' => 'Server_(96)'
						],
						'icon_on' => [
							'name' => ''
						],
						'icon_disabled' => [
							'name' => ''
						],
						'icon_maintenance' => [
							'name' => ''
						],
						'application' => '',
						'urls' => [],
						'elements' => [
							[
								'name' => 'Zabbix servers'
							]
						]
					]
				],
				'links' => [
					[
						'drawtype' => '0',
						'color' => '00CC00',
						'label' => '',
						'selementid1' => '32',
						'selementid2' => '33',
						'linktriggers' => [
							[
								'drawtype' => '0',
								'color' => 'DD0000',
								'trigger' => [
									'description' => 'trigger',
									'expression' => '{export-host:item.last(0)}<>0 or {export-host:item.last(0)}<>0 and {export-host:item.last(0)}<>0',
									'recovery_expression' => ''
								]
							]
						]
					],
					[
						'drawtype' => '0',
						'color' => '00CC00',
						'label' => '',
						'selementid1' => '32',
						'selementid2' => '34',
						'linktriggers' => []
					]
				],
				'shapes' => [[
					'type' => 0,
					'x' => '0',
					'y' => '0',
					'width' => '800',
					'height' => 15,
					'text' => '{MAP.NAME}',
					'font' => '9',
					'font_size' => '11',
					'font_color' => '000000',
					'text_halign' => '0',
					'text_valign' => '0',
					'border_type' => '0',
					'border_width' => '1',
					'border_color' => '000000',
					'background_color' => '',
					'zindex' => '0'
				]],
				'lines' => []
			]
		]);
	}

	public function testGetScreens() {
		$adapter = $this->getAdapter($this->getScreenXml());

		$this->assertEquals($adapter->getScreens(), [
			[
				'name' => 'empty-screen',
				'hsize' => '1',
				'vsize' => '1',
				'screenitems' => []
			],
			[
				'name' => 'screen',
				'hsize' => '1',
				'vsize' => '1',
				'screenitems' => [
					[
						'resourcetype' => '0',
						'width' => '500',
						'height' => '100',
						'x' => '0',
						'y' => '0',
						'colspan' => '1',
						'rowspan' => '1',
						'elements' => '0',
						'valign' => '0',
						'halign' => '0',
						'style' => '0',
						'url' => '',
						'dynamic' => '0',
						'sort_triggers' => '0',
						'resource' => [
							'name' => 'simple',
							'host' => 'export-host'
						],
						'max_columns' => '',
						'application' => ''
					]
				]
			]
		]);
	}

	public function testGetMediaTypes() {
		$adapter = $this->getAdapter($this->getMediaTypeXml());

		$defaults = DB::getDefaults('media_type');
		unset($defaults['exec_params']);

		$this->assertEquals($adapter->getMediaTypes(), [
			[
				'name' => 'Email',
				'type' => (string) CXmlConstantValue::MEDIA_TYPE_EMAIL,
				'smtp_server' => 'mail.example.com',
				'smtp_helo' => 'example.com',
				'smtp_email' => 'zabbix@example.com',
				'parameters' => ''
			] + $defaults,
			[
				'name' => 'Script',
				'type' => (string) CXmlConstantValue::MEDIA_TYPE_SCRIPT,
				'exec_path' => 'script.sh',
				'exec_params' => ''
			] + $defaults,
			[
				'name' => 'SMS',
				'type' => (string) CXmlConstantValue::MEDIA_TYPE_SMS,
				'gsm_modem' => '/dev/ttyS0',
				'parameters' => ''
			] + $defaults,
			[
				'name' => 'Webhook',
				'type' => (string) CXmlConstantValue::MEDIA_TYPE_WEBHOOK,
				'parameters' => [
					[
						'name' => 'URL',
						'value' => ''
					],
					[
						'name' => 'To',
						'value' => '{ALERT.SENDTO}'
					],
					[
						'name' => 'Subject',
						'value' => '{ALERT.SUBJECT}'
					],
					[
						'name' => 'Message',
						'value' => '{ALERT.MESSAGE}'
					],
				],
				'script' => 'return true;'
			] + $defaults
		]);
	}

	public function testConversion() {
		$adapter = $this->getAdapter($this->get18Xml());

		$this->assertEquals($adapter->getGroups(), [
				[
					'name' => 'Linux servers'
				],
				[
					'name' => 'Templates'
				]
			]
		);

		$this->assertEquals($adapter->getHosts(), [
				[
					'macros' => [],
					'interfaces' => [
						[
							'type' => 1,
							'useip' => '1',
							'ip' => '10.0.0.6',
							'dns' => 'localhost',
							'port' => '10050',
							'interface_ref' => 'if0',
							'main' => 1
						]
					],
					'host' => 'host',
					'status' => '0',
					'ipmi_authtype' => '0',
					'ipmi_privilege' => '2',
					'ipmi_username' => '',
					'ipmi_password' => '',
					'inventory_mode' => -1,
					'inventory' => [],
					'groups' =>	[
						[
							'name' => 'Linux servers'
						]
					],
					'templates' => [],
					'tags' => [],
					'proxy' => [
						'name' => '0'
					],
					'description' => '',
					'name' => 'host',
					'tls_connect' => '1',
					'tls_accept' => '1',
					'tls_issuer' => '',
					'tls_subject' => '',
					'tls_psk_identity' => '',
					'tls_psk' => '',
				]
			]
		);

		$this->assertEquals($adapter->getTemplates(), [
				[
					'macros' => [
						[
							'value' => '21',
							'macro' => '{$PORT.FTP}',
							'description' => ''
						],
						[
							'value' => '22',
							'macro' => '{$PORT.SSH}',
							'description' => ''
						]
					],
					'host' => 'Template_Linux',
					'groups' =>	[
						[
							'name' => 'Templates'
						]
					],
					'templates' => [],
					'tags' => [],
					'name' => 'Template_Linux',
					'description' => ''
				],
				[
					'macros' => [],
					'host' => 'Template_Simple',
					'groups' =>	[
						[
							'name' => 'Templates'
						]
					],
					'templates' => [],
					'tags' => [],
					'name' => 'Template_Simple',
					'description' => ''
				]
			]
		);

		$this->assertEquals($adapter->getItems(), [
				'Template_Linux' => [
					'vfs.fs.size[/,pfree]' => [
						'type' => '0',
						'value_type' => '0',
						'ipmi_sensor' => '',
						'delay' => '30',
						'history' => '7d',
						'trends' => '365d',
						'status' => '0',
						'units' => '%',
						'logtimefmt' => '',
						'authtype' => '0',
						'username' => '',
						'password' => '',
						'publickey' => '',
						'privatekey' => '',
						'params' => '',
						'trapper_hosts' => '',
						'snmp_community' => '',
						'snmp_oid' => '',
						'snmpv3_securityname' => '',
						'snmpv3_securitylevel' => '0',
						'snmpv3_authpassphrase' => '',
						'snmpv3_privpassphrase' => '',
						'applications' => [
							[
								'name' => 'Filesystem'
							],
							[
								'name' => 'Availability'
							]
						],
						'name' => 'Free disk space on / in %',
						'key_' => 'vfs.fs.size[/,pfree]',
						'jmx_endpoint' => '',
						'master_item' => [],
						'timeout' => '3s',
						'url' => '',
						'query_fields' => [],
						'posts' => '',
						'status_codes' => '200',
						'follow_redirects' => '1',
						'post_type' => '0',
						'http_proxy' => '',
						'headers' => [],
						'retrieve_mode' => '0',
						'request_method' => '0',
						'output_format' => '0',
						'allow_traps' => '0',
						'ssl_cert_file' => '',
						'ssl_key_file' => '',
						'ssl_key_password' => '',
						'triggers' => [],
						'verify_peer' => '0',
						'verify_host' => '0',
						'description' => 'Free disk space on / in %',
						'inventory_link' => '0',
						'port' => '',
						'preprocessing' => [],
						'snmpv3_authprotocol' => '0',
						'snmpv3_contextname' => '',
						'snmpv3_privprotocol' => '0',
						'valuemap' => [
							'name' => '0'
						]
					]
				],
				'Template_Simple' => [
					'net.tcp.service[ftp,,21]' => [
						'type' => '3',
						'value_type' => '3',
						'ipmi_sensor' => '',
						'delay' => '30',
						'history' => '90d',
						'trends' => '365d',
						'status' => '0',
						'units' => '',
						'logtimefmt' => '',
						'authtype' => '0',
						'username' => '',
						'password' => '',
						'publickey' => '',
						'privatekey' => '',
						'params' => '',
						'trapper_hosts' => '',
						'snmp_community' => '',
						'snmp_oid' => '',
						'snmpv3_securityname' => '',
						'snmpv3_securitylevel' => '0',
						'snmpv3_authpassphrase' => '',
						'snmpv3_privpassphrase' => '',
						'applications' => [
							[
								'name' => 'Simple checks'
							]
						],
						'name' => 'FTP check',
						'key_' => 'net.tcp.service[ftp,,21]',
						'jmx_endpoint' => '',
						'master_item' => [],
						'timeout' => '3s',
						'url' => '',
						'query_fields' => [],
						'posts' => '',
						'status_codes' => '200',
						'follow_redirects' => '1',
						'post_type' => '0',
						'http_proxy' => '',
						'headers' => [],
						'retrieve_mode' => '0',
						'request_method' => '0',
						'output_format' => '0',
						'allow_traps' => '0',
						'ssl_cert_file' => '',
						'ssl_key_file' => '',
						'ssl_key_password' => '',
						'triggers' => [],
						'verify_peer' => '0',
						'verify_host' => '0',
						'description' => 'FTP check',
						'inventory_link' => '0',
						'port' => '',
						'preprocessing' => [],
						'snmpv3_authprotocol' => '0',
						'snmpv3_contextname' => '',
						'snmpv3_privprotocol' => '0',
						'valuemap' => [
							'name' => '0'
						]
					],
					'net.tcp.service[ftp,,{$PORT.FTP}]' => [
						'type' => '3',
						'value_type' => '3',
						'ipmi_sensor' => '',
						'delay' => '30',
						'history' => '90d',
						'trends' => '365d',
						'status' => '0',
						'units' => '',
						'logtimefmt' => '',
						'authtype' => '0',
						'username' => '',
						'password' => '',
						'publickey' => '',
						'privatekey' => '',
						'params' => '',
						'trapper_hosts' => '',
						'snmp_community' => '',
						'snmp_oid' => '',
						'snmpv3_securityname' => '',
						'snmpv3_securitylevel' => '0',
						'snmpv3_authpassphrase' => '',
						'snmpv3_privpassphrase' => '',
						'applications' => [
							[
								'name' => 'Simple checks'
							]
						],
						'name' => 'FTP check with macro',
						'key_' => 'net.tcp.service[ftp,,{$PORT.FTP}]',
						'jmx_endpoint' => '',
						'master_item' => [],
						'timeout' => '3s',
						'url' => '',
						'query_fields' => [],
						'posts' => '',
						'status_codes' => '200',
						'follow_redirects' => '1',
						'post_type' => '0',
						'http_proxy' => '',
						'headers' => [],
						'retrieve_mode' => '0',
						'request_method' => '0',
						'output_format' => '0',
						'allow_traps' => '0',
						'ssl_cert_file' => '',
						'ssl_key_file' => '',
						'ssl_key_password' => '',
						'triggers' => [],
						'verify_peer' => '0',
						'verify_host' => '0',
						'description' => 'FTP check with macro',
						'inventory_link' => '0',
						'port' => '',
						'preprocessing' => [],
						'snmpv3_authprotocol' => '0',
						'snmpv3_contextname' => '',
						'snmpv3_privprotocol' => '0',
						'valuemap' => [
							'name' => '0'
						]
					]
				]
			]
		);

		$this->assertEquals($adapter->getApplications(), [
				'Template_Linux' => [
					'Filesystem' => [
						'name' => 'Filesystem'
					],
					'Availability' => [
						'name' => 'Availability'
					]
				],
				'Template_Simple' => [
					'Simple checks' => [
						'name' => 'Simple checks'
					]
				]
			]
		);

		$this->assertEquals($adapter->getTriggers(), [
				[
					'type' => '0',
					'expression' => '{Template_Linux:vfs.fs.size[/,pfree].last(0)}<10',
					'url' => 'http://www.zabbix.com/',
					'status' => '0',
					'priority' => '4',
					'comments' => 'test comments',
					'description' => 'Low free disk space on {HOSTNAME} volume /',
					'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
					'recovery_expression' => '',
					'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
					'correlation_tag' => '',
					'opdata' => '',
					'tags' => [],
					'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
					'dependencies' => []
				],
				[
					'type' => '1',
					'expression' => '{Template_Simple:net.tcp.service[ftp,,21].last(0)}<>0 or {Template_Simple:net.tcp.service[ftp,,{$PORT.FTP}].last(0)}<>0',
					'url' => 'triggers.php',
					'status' => '1',
					'priority' => '3',
					'comments' => 'comments',
					'description' => 'simple triggert',
					'correlation_mode' => (string) ZBX_TRIGGER_CORRELATION_NONE,
					'recovery_mode' => (string) ZBX_RECOVERY_MODE_EXPRESSION,
					'recovery_expression' => '',
					'correlation_tag' => '',
					'opdata' => '',
					'tags' => [],
					'manual_close' => (string) ZBX_TRIGGER_MANUAL_CLOSE_NOT_ALLOWED,
					'dependencies' => []
				]
			]
		);

		$this->assertEquals($adapter->getGraphs(), [
				[
					'name' => 'simple graph fixed',
					'width' => '755',
					'height' => '332',
					'ymin_type' => '1',
					'ymax_type' => '1',
					'show_work_period' => '1',
					'show_triggers' => '1',
					'yaxismin' => '5.5000',
					'yaxismax' => '95.6000',
					'show_3d' => '0',
					'percent_left' => '25.5000',
					'percent_right' => '27.6000',
					'ymin_item_1' => '0',
					'ymax_item_1' => '0',
					'graphtype' => '0',
					'gitems' => [
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,21]'
							],
							'drawtype' => '0',
							'sortorder' => '0',
							'color' => '3333FF',
							'yaxisside' => '0',
							'calc_fnc' => '7',
							'type' => '0'
						],
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,{$PORT.FTP}]'
							],
							'drawtype' => '1',
							'sortorder' => '1',
							'color' => '009999',
							'yaxisside' => '1',
							'calc_fnc' => '4',
							'type' => '0'
						]
					],
					'show_legend' => '0'
				],
				[
					'name' => 'simple graph',
					'width' => '900',
					'height' => '200',
					'ymin_type' => '0',
					'ymax_type' => '0',
					'show_work_period' => '0',
					'show_triggers' => '0',
					'yaxismin' => '0.0000',
					'yaxismax' => '100.0000',
					'show_3d' => '0',
					'percent_left' => '0.0000',
					'percent_right' => '0.0000',
					'ymin_item_1' => '0',
					'ymax_item_1' => '0',
					'graphtype' => '0',
					'gitems' => [
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,21]'
							],
							'drawtype' => '0',
							'sortorder' => '0',
							'color' => '3333FF',
							'yaxisside' => '0',
							'calc_fnc' => '2',
							'type' => '0'
						],
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,{$PORT.FTP}]'
							],
							'drawtype' => '0',
							'sortorder' => '1',
							'color' => '009999',
							'yaxisside' => '0',
							'calc_fnc' => '2',
							'type' => '0'
						]
					],
					'show_legend' => '0'
				],
				[
					'name' => 'simple graph min/max',
					'width' => '1024',
					'height' => '768',
					'ymin_type' => '2',
					'ymax_type' => '2',
					'show_work_period' => '1',
					'show_triggers' => '1',
					'yaxismin' => '0.0000',
					'yaxismax' => '0.0000',
					'show_3d' => '0',
					'percent_left' => '0.0000',
					'percent_right' => '0.0000',
					'ymin_item_1' => [
						'host' => 'Template_Simple',
						'key' => 'net.tcp.service[ftp,,21]'
					],
					'ymax_item_1' => [
						'host' => 'Template_Simple',
						'key' => 'net.tcp.service[ftp,,{$PORT.FTP}]'
					],
					'graphtype' => '1',
					'gitems' => [
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,21]'
							],
							'drawtype' => '0',
							'sortorder' => '0',
							'color' => '3333FF',
							'yaxisside' => '0',
							'calc_fnc' => '2',
							'type' => '0'
						],
						[
							'item' => [
								'host' => 'Template_Simple',
								'key' => 'net.tcp.service[ftp,,{$PORT.FTP}]'
							],
							'drawtype' => '0',
							'sortorder' => '1',
							'color' => '009999',
							'yaxisside' => '0',
							'calc_fnc' => '2',
							'type' => '0'
						]
					],
					'show_legend' => '0'
				]
			]
		);
	}

	public function testUnsupportedVersion() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<zabbix_export>
				<version>0.1</version>
				<date>2014-11-14T09:41:02Z</date>
			</zabbix_export>';

		//$this->setExpectedException('Exception', 'Invalid tag "/zabbix_export/version": unsupported version number.');
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid tag "/zabbix_export/version": unsupported version number.');
		$this->getAdapter($xml);
	}

	public function test10SchemaTranformationToLatest() {
		$adapter = $this->getAdapter($this->get10Xml());

		$this->assertEquals($adapter->getHosts(), [
				[
					'name' => 'host',
					'status' => '0',
					'ipmi_authtype' => '0',
					'ipmi_privilege' => '2',
					'groups' => [
						[
							'name' => 'Linux servers',
						]
					],
					'host' => 'host',
					'interfaces' =>
					[
						[
							'type' => '1',
							'useip' => '1',
							'ip' => '10.0.0.6',
							'dns' => 'localhost',
							'port' => '10050',
							'main' => '1',
							'interface_ref' => 'if0',
						]
					],
					'proxy' =>
					[
						'name' => '0',
					],
					'tls_connect' => '1',
					'tls_accept' => '1',
					'inventory_mode' => '-1',
					'description' => '',
					'inventory' => [],
					'ipmi_password' => '',
					'ipmi_username' => '',
					'macros' => [],
					'tags' => [],
					'templates' => [],
					'tls_issuer' => '',
					'tls_psk' => '',
					'tls_psk_identity' => '',
					'tls_subject' => '',
				]
			]
		);

		$this->assertEquals($adapter->getScreens(), [
				[
					'name' => 'test',
					'hsize' => 'test',
					'vsize' => 'test',
					'screenitems' => [
						[
							'resourcetype' => '1',
							'width' => 'test',
							'height' => 'test',
							'x' => 'test',
							'y' => 'test',
							'colspan' => '1',
							'rowspan' => '1',
							'elements' => 'test',
							'valign' => 'test',
							'halign' => 'test',
							'style' => 'test',
							'dynamic' => 'test',
							'url' => 'test',
							'resource' => [
								'host' => 'Template_Simple',
								'key' => 'ftp,21',
							],
							'sort_triggers' => '',
							'application' => '',
							'max_columns' => '',
						]
					]
				]
			]
		);

		$this->assertEquals($adapter->getTemplates(), [
				[
					'groups' => [
						[
							'name' => 'Templates'
						]
					],
					'macros' => [
						[
							'value' => '23',
							'macro' => '{#VALUE}',
							'description' => ''
						]
					],
					'templates' => [
						[
							'name' => 'template'
						]
					],
					'host' => 'Template_Simple',
					'name' => 'Template_Simple',
					'description' => '',
					'tags' => []
				]
			]
		);

		$this->assertEquals($adapter->getItems(), [
				'Template_Simple' => [
					'net.tcp.service[ftp,,21]' => [
						'type' => '3',
						'value_type' => '3',
						'description' => 'FTP check',
						'delay' => '30',
						'history' => '90d',
						'trends' => '365d',
						'status' => '0',
						'authtype' => '0',
						'snmpv3_securitylevel' => '0',
						'applications' => [
							[
								'name' => 'Simple checks',
							]
						],
						'name' => 'FTP check',
						'timeout' => '3s',
						'status_codes' => '200',
						'follow_redirects' => '1',
						'post_type' => '0',
						'retrieve_mode' => '0',
						'request_method' => '0',
						'output_format' => '0',
						'verify_peer' => '0',
						'verify_host' => '0',
						'allow_traps' => '0',
						'headers' => [],
						'http_proxy' => '',
						'inventory_link' => '0',
						'ipmi_sensor' => '',
						'jmx_endpoint' => '',
						'logtimefmt' => '',
						'master_item' => [],
						'params' => '',
						'password' => '',
						'port' => '',
						'posts' => '',
						'preprocessing' => [],
						'privatekey' => '',
						'publickey' => '',
						'query_fields' => [],
						'snmp_community' => '',
						'snmp_oid' => '',
						'snmpv3_authpassphrase' => '',
						'snmpv3_authprotocol' => '0',
						'snmpv3_contextname' => '',
						'snmpv3_privpassphrase' => '',
						'snmpv3_privprotocol' => '0',
						'snmpv3_securityname' => '',
						'ssl_cert_file' => '',
						'ssl_key_file' => '',
						'ssl_key_password' => '',
						'triggers' => [],
						'units' => '',
						'url' => '',
						'username' => '',
						'valuemap' => [
							'name' => '0'
						],
						'key_' => 'net.tcp.service[ftp,,21]',
						'trapper_hosts' => ''
					],
					'net.tcp.service[ftp,,{$PORT.FTP}]' => [
						'type' => '3',
						'value_type' => '3',
						'description' => 'FTP check with macro',
						'delay' => '30',
						'history' => '90d',
						'trends' => '365d',
						'status' => '0',
						'authtype' => '0',
						'snmpv3_securitylevel' => '0',
						'applications' => [
							[
								'name' => 'Simple checks',
							],
						],
						'name' => 'FTP check with macro',
						'timeout' => '3s',
						'status_codes' => '200',
						'follow_redirects' => '1',
						'post_type' => '0',
						'retrieve_mode' => '0',
						'request_method' => '0',
						'output_format' => '0',
						'verify_peer' => '0',
						'verify_host' => '0',
						'allow_traps' => '0',
						'headers' => [],
						'http_proxy' => '',
						'inventory_link' => '0',
						'ipmi_sensor' => '',
						'jmx_endpoint' => '',
						'logtimefmt' => '',
						'master_item' => [],
						'params' => '',
						'password' => '',
						'port' => '',
						'posts' => '',
						'preprocessing' => [],
						'privatekey' => '',
						'publickey' => '',
						'query_fields' => [],
						'snmp_community' => '',
						'snmp_oid' => '',
						'snmpv3_authpassphrase' => '',
						'snmpv3_authprotocol' => '0',
						'snmpv3_contextname' => '',
						'snmpv3_privpassphrase' => '',
						'snmpv3_privprotocol' => '0',
						'snmpv3_securityname' => '',
						'ssl_cert_file' => '',
						'ssl_key_file' => '',
						'ssl_key_password' => '',
						'triggers' => [],
						'units' => '',
						'url' => '',
						'username' => '',
						'valuemap' => [
							'name' => '0'
						],
						'key_' => 'net.tcp.service[ftp,,{$PORT.FTP}]',
						'trapper_hosts' => ''
					],
				],
			]
		);
	}

	public function testConstantConverter() {
		$schema = ['type' => XML_ARRAY, 'rules' => [
			'constants' => ['type' => XML_INDEXED_ARRAY, 'prefix' => 'constant', 'rules' => [
				'constant' => ['type' => XML_ARRAY, 'rules' => [
					'first' => ['type' => XML_STRING, 'in' => [0 => 'ZERO', 1 => 'FIRST']],
					'second' => ['type' => XML_STRING, 'ex_rules' => [$this, 'constantConverterExRules']],
					'third' => ['type' => XML_STRING],
				]]
			]]
		]];
		$source = [
			'zabbix_export' => [
				'constants' => [
					'constant' => [
						'first' => 'FIRST',
						'second' => 'SECOND',
						'third' => 'test'
					]
				]
			]
		];
		$source = (new CConstantImportConverter($schema))->convert($source);

		$this->assertEquals($source, [
				'zabbix_export' => [
					'constants' => [
						'constant' => [
							'first' => '1',
							'second' => '2',
							'third' => 'test'
						]
					]
				]
			]
		);
	}

	public function constantConverterExRules(array $data) {
		return ['type' => XML_STRING, 'in' => [0 => 'ZERO', 1 => 'FIRST', 2 => 'SECOND']];
	}

	public function testConstantConverterValueError() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid tag "first": unexpected constant value "1".');

		$schema = ['type' => XML_ARRAY, 'rules' => [
			'constants' => ['type' => XML_INDEXED_ARRAY, 'prefix' => 'constant', 'rules' => [
				'constant' => ['type' => XML_ARRAY, 'rules' => [
					'first' => ['type' => XML_STRING, 'in' => ['0' => 'ZERO']],
				]]
			]]
		]];
		$source = [
			'zabbix_export' => [
				'constants' => [
					'constant' => [
						'first' => '1',
					]
				]
			]
		];
		$source = (new CConstantImportConverter($schema))->convert($source);
	}

	public function testDefaultValueConverter() {
		$schema = ['type' => XML_ARRAY, 'rules' => [
			'default_values' => ['type' => XML_INDEXED_ARRAY, 'prefix' => 'values', 'rules' => [
				'values' => ['type' => XML_ARRAY, 'rules' => [
					'var0' => ['type' => XML_STRING, 'default' => 1],
					'var1' => ['type' => XML_STRING, 'default' => 1],
					'var2' => ['type' => XML_STRING],
					'var3' => ['type' => XML_ARRAY, 'rules' => [
						'subvar1' => ['type' => XML_STRING, 'default' => 'array']
					]],
					'var4' => ['type' => XML_ARRAY, 'rules' => [
						'subvar1' => ['type' => XML_STRING, 'default' => 'array']
					]],
				]]
			]]
		]];
		$source = [
			'zabbix_export' => [
				'default_values' => [
					'values' => [
						'var0' => 'test',
						'var4' => []
					]
				]
			]
		];
		$source = (new CDefaultImportConverter($schema))->convert($source);

		$this->assertEquals($source, [
				'zabbix_export' => [
					'default_values' => [
						'values' => [
							'var0' => 'test',
							'var1' => '1',
							'var2' => '',
							'var3' => [],
							'var4' => [
								'subvar1' => 'array'
							]
						]
					]
				]
			]
		);
	}

	public function testArrayKeysConverter() {
		$schema = ['type' => XML_ARRAY, 'rules' => [
			'tests' => ['type' => XML_INDEXED_ARRAY, 'prefix' => 'test', 'rules' => [
				'test' => ['type' => XML_ARRAY, 'rules' => [
					'value' => ['type' => XML_STRING],
					'values' => ['type' => XML_INDEXED_ARRAY, 'prefix' => 'value', 'rules' => [
						'value' => ['type' => XML_ARRAY, 'rules' => [
							'host' => ['type' => XML_STRING],
						]]
					]]
				]]
			]]
		]];
		$source = [
			'zabbix_export' => [
				'tests' => [
					'test' => [
						'values' => [
							'value' => [
								'host' => ''
							],
							'value2' => [
								'host' => ''
							],
							'value3' => [
								'host' => ''
							],
						]
					],
					'test2' => [
						'value' => ''
					],
					'test3' => [
						'values' => [
							'value' => [
								'host' => ''
							],
							'value2' => [
								'host' => ''
							],
							'value3' => [
								'host' => ''
							],
						]
					]
				]
			]
		];
		$source = (new CArrayKeysImportConverter($schema))->convert($source);

		$this->assertEquals($source, [
				'zabbix_export' => [
					'tests' => [
						[
							'values' => [
								[
									'host' => ''
								],
								[
									'host' => ''
								],
								[
									'host' => ''
								],
							]
						],
						[
							'value' => ''
						],
						[
							'values' => [
								[
									'host' => ''
								],
								[
									'host' => ''
								],
								[
									'host' => ''
								],
							]
						],
					]
				]
			]
		);
	}


	protected function getAdapter($source) {
		$reader = CImportReaderFactory::getReader(CImportReaderFactory::XML);
		$source = $reader->read($source);

		$importConverterFactory = new CImportConverterFactory();

		$source = (new CXmlValidator)->validate($source, 'xml');

		foreach (['1.0', '2.0', '3.0', '3.2', '3.4', '4.0', '4.2'] as $version) {
			if ($source['zabbix_export']['version'] !== $version) {
				continue;
			}

			$source = $importConverterFactory
				->getObject($version)
				->convert($source);
			$source = (new CXmlValidator)->validate($source, 'xml');
		}

		$schema = (new CImportValidatorFactory('xml'))->getObject(ZABBIX_EXPORT_VERSION)->getSchema();

		$source = (new CConstantImportConverter($schema))->convert($source);
		$source = (new CDefaultImportConverter($schema))->convert($source);
		$source = (new CArrayKeysImportConverter($schema))->convert($source);
		$source = (new CTransformImportConverter($schema))->convert($source);

		$adapter = new CImportDataAdapter();
		$adapter->load($source);

		return $adapter;
	}

	protected function get18Xml() {
		return $this->getFile('host18.xml');
	}

	protected function getEmptyXml() {
		return '<?xml version="1.0" encoding="UTF-8"?>
			<zabbix_export>
				<version>2.0</version>
				<date>2014-11-14T09:41:02Z</date>
			</zabbix_export>';
	}

	protected function getHostAndTemplateXml() {
		return $this->getFile('hostAndTemplate.xml');
	}

	protected function getMapXml() {
		return $this->getFile('map.xml');
	}

	protected function getScreenXml() {
		return $this->getFile('screen.xml');
	}

	protected function getMediaTypeXml() {
		return $this->getFile('mediatype.xml');
	}

	protected function get10Xml() {
		return $this->getFile('schema_1.0.xml');
	}

	protected function getFile($name) {
		if (!isset($this->sources[$name])) {
			$this->sources[$name] = file_get_contents(__DIR__.'/xml/'.$name);
		}

		return $this->sources[$name];
	}
}
