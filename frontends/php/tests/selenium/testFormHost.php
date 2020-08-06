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

require_once dirname(__FILE__).'/../include/CLegacyWebTest.php';

/**
 * @backup hosts
 */
class testFormHost extends CLegacyWebTest {
	public $host = 'Test host 001';
	public $host_tmp = 'Test host 001A';
	public $host_tmp_visible = 'Test host 001A (visible)';
	public $host_cloned = 'Test host 001 cloned';
	public $host_cloned_visible = 'Test host 001 cloned (visible)';
	public $host_fullcloned = 'Test host 001 full cloned';
	public $host_fullcloned_visible = 'Test host 001 full cloned (visible)';
	public $host_for_template = 'Visible host for template linkage';

	public function testFormHost_Layout() {
		$this->zbxTestLogin('hosts.php?form=1');

		$this->zbxTestTabSwitch('Inventory');

		$inventoryFields = getHostInventories();
		$inventoryFields = zbx_toHash($inventoryFields, 'db_field');
		foreach ($inventoryFields as $fieldId => $fieldName) {
			$this->zbxTestTextPresent($fieldName['title']);
			$this->zbxTestAssertElementPresentId('host_inventory_'.$fieldId.'');
		}
	}

	public function testFormHost_Create() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'Zabbix servers');
		$this->zbxTestContentControlButtonClickTextWait('Create host');
		$this->zbxTestInputTypeWait('host', $this->host);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host added');
		$this->zbxTestTextPresent($this->host);

		$sql = "SELECT * FROM hosts WHERE host='$this->host'";
		$this->assertEquals(1, CDBHelper::getCount($sql));
	}

	public function testFormHost_CreateLongHostName() {
// 64 characters long name
		$host="1234567890123456789012345678901234567890123456789012345678901234";
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'Zabbix servers');
		$this->zbxTestContentControlButtonClickTextWait('Create host');
		$this->zbxTestInputTypeWait('host', $host);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host added');
		$this->zbxTestTextPresent($host);

		$sql = "SELECT * FROM hosts WHERE host='$host'";
		$this->assertEquals(1, CDBHelper::getCount($sql));
	}

	public function testFormHost_SimpleUpdate() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'Zabbix servers');
		$this->zbxTestClickLinkTextWait($this->host);
		$this->zbxTestClickWait('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');
		$this->zbxTestTextPresent($this->host);
	}

	public function testFormHost_CreateHostNoGroups() {
		$host = 'Test host without groups';

		$sqlHosts = 'select * from hosts where host='.zbx_dbstr($host);
		$oldHashHosts = CDBHelper::getHash($sqlHosts);

		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestContentControlButtonClickTextWait('Create host');
		$this->zbxTestInputTypeWait('host', $host);

		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");

		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-bad', 'Page received incorrect data');
		$this->zbxTestTextPresent('Field "groups" is mandatory.');

		$this->assertEquals($oldHashHosts, CDBHelper::getHash($sqlHosts));
	}

	public function testFormHost_CreateHostExistingHostName() {
		$host = 'Test host';

		$sqlHosts = 'select * from hosts where host='.zbx_dbstr($host);
		$oldHashHosts = CDBHelper::getHash($sqlHosts);

		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'Zabbix servers');
		$this->zbxTestContentControlButtonClickTextWait('Create host');
		$this->zbxTestInputTypeWait('host', $host);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");

		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-bad', 'Cannot add host');
		$this->zbxTestTextPresent('Host with the same name "'.$host.'" already exists.');

		$this->assertEquals($oldHashHosts, CDBHelper::getHash($sqlHosts));
	}

	public function testFormHost_CreateHostExistingVisibleName() {
		$host = 'Test host 001 with existing visible name';
		$hostVisible = 'ЗАББИКС Сервер';

		$sqlHosts = 'select * from hosts where host='.zbx_dbstr($host);
		$oldHashHosts = CDBHelper::getHash($sqlHosts);

		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'Zabbix servers');
		$this->zbxTestContentControlButtonClickTextWait('Create host');
		$this->zbxTestInputTypeWait('host', $host);
		$this->zbxTestInputType('visiblename', $hostVisible);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");

		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-bad', 'Cannot add host');
		$this->zbxTestTextPresent('Host with the same visible name "'.$hostVisible.'" already exists.');

		$this->assertEquals($oldHashHosts, CDBHelper::getHash($sqlHosts));
	}

	public function testFormHost_CloneHost() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host);
		$this->zbxTestClickWait('clone');
		$this->zbxTestInputTypeOverwrite('host', $this->host_cloned);
		$this->zbxTestInputType('visiblename', $this->host_cloned_visible);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host added');
		$this->zbxTestTextPresent($this->host_cloned_visible);

		$sql = "SELECT * FROM hosts WHERE host='$this->host_cloned'";
		$this->assertEquals(1, CDBHelper::getCount($sql));
	}

	public function testFormHost_DeleteClonedHost() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_cloned_visible);
		$this->zbxTestClickWait('delete');
		$this->zbxTestAcceptAlert();
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host deleted');

		$sql = "SELECT * FROM hosts WHERE host='$this->host_cloned'";
		$this->assertEquals(0, CDBHelper::getCount($sql));
	}

	public function testFormHost_FullCloneHost() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host);
		$this->zbxTestClickWait('full_clone');
		$this->zbxTestInputTypeOverwrite('host', $this->host_fullcloned);
		$this->zbxTestInputType('visiblename', $this->host_fullcloned_visible);
		$this->zbxTestClickXpathWait("//button[@id='add' and @type='submit']");
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host added');
		$this->zbxTestTextPresent($this->host_fullcloned_visible);

		$sql = "SELECT * FROM hosts WHERE host='$this->host_fullcloned'";
		$this->assertEquals(1, CDBHelper::getCount($sql));
	}

	public function testFormHost_DeleteFullClonedHost() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_fullcloned_visible);
		$this->zbxTestClickWait('delete');
		$this->zbxTestAcceptAlert();
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host deleted');

		$sql = "SELECT * FROM hosts WHERE host='$this->host_fullcloned'";
		$this->assertEquals(0, CDBHelper::getCount($sql));
	}

	public function testFormHost_UpdateHostName() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host);
		$this->zbxTestInputTypeOverwrite('host', $this->host_tmp);
		$this->zbxTestClickWait('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');
		$this->zbxTestTextPresent($this->host_tmp);
	}

	public function testFormHost_Delete() {
		// save the ID of the host
		$hostid = CDBHelper::getValue('select hostid from hosts where host='.zbx_dbstr($this->host_tmp));

		// Delete Host
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_tmp);
		$this->zbxTestClickWait('delete');
		$this->zbxTestAcceptAlert();
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host deleted');

		// check if all records have been deleted
		$tables=['hosts','items','applications','interface','hostmacro','hosts_groups','hosts_templates','maintenances_hosts','host_inventory'];
		foreach ($tables as $table) {
			$count=CDBHelper::getCount("select * from $table where hostid=$hostid");
			$this->assertEquals(0, $count, "Records from table '$table' have not been deleted.");
		}
	}

	public function testFormHost_TemplateLink() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_for_template);

		$this->zbxTestTabSwitch('Templates');
		$this->zbxTestClickButtonMultiselect('add_templates_');
		$this->zbxTestLaunchOverlayDialog('Templates');
		$this->zbxTestDropdownSelectWait('groupid', 'Templates');
		$this->zbxTestClickLinkTextWait('Template OS Linux by Zabbix agent');

		$this->zbxTestTextPresent('Template OS Linux by Zabbix agent');
		$this->zbxTestClick('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');
		$this->zbxTestTextPresent($this->host_for_template);
	}

	/**
	 * @depends testFormHost_TemplateLink
	 */
	public function testFormHost_TemplateUnlink() {
		// Unlink a template from a host from host properties page

		$template = 'Template OS Linux by Zabbix agent';
		$host = 'Template linkage test host';

		$sql = 'select hostid from hosts where host='.zbx_dbstr($host).' and status in ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')';
		$this->assertEquals(1, CDBHelper::getCount($sql));
		$row = DBfetch(DBselect($sql));
		$hostid = $row['hostid'];

		$sql2 = "select hostid from hosts where host='".$template."';";
		$this->assertEquals(1, CDBHelper::getCount($sql2));
		$row2 = DBfetch(DBselect($sql2));
		$hostid2 = $row2['hostid'];

		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_for_template);
		$this->zbxTestTabSwitch('Templates');
		$this->zbxTestTextPresent($template);

		// clicks button named "Unlink" next to a template by name
		$this->zbxTestClickXpathWait("//button[contains(@onclick, 'unlink[".$hostid2."]') and text()='Unlink']");

		$this->zbxTestTextNotPresent($template);
		$this->zbxTestClickWait('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');

		// this should be a separate test
		// should check that items, triggers, graphs and applications are not linked to the template anymore
		$this->zbxTestDoubleClickXpath("//a[contains(@href,'items.php?filter_set=1&filter_hostids%5B0%5D=".$hostid."')]", 'filter_application');
		$this->zbxTestTextNotPresent($template.':');
		// using "host navigation bar" at the top of entity list
		$this->zbxTestHrefClickWait('triggers.php?filter_set=1&filter_hostids%5B0%5D='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
		$this->zbxTestHrefClickWait('graphs.php?hostid='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
		$this->zbxTestHrefClickWait('applications.php?hostid='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
	}

	public function testFormHost_TemplateLinkUpdate() {
		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_for_template);

		$this->zbxTestTabSwitch('Templates');
		$this->zbxTestClickButtonMultiselect('add_templates_');
		$this->zbxTestLaunchOverlayDialog('Templates');
		$this->zbxTestDropdownSelectWait('groupid', 'Templates');
		$this->zbxTestClickLinkTextWait('Template OS Linux by Zabbix agent');

		$this->zbxTestTextPresent('Template OS Linux by Zabbix agent');
		$this->zbxTestClickWait('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');
		$this->zbxTestTextPresent($this->host_for_template);
	}

	/**
	 * @depends testFormHost_TemplateLinkUpdate
	 */
	public function testFormHost_TemplateUnlinkAndClear() {
		// Unlink and clear a template from a host from host properties page

		$template = 'Template OS Linux by Zabbix agent';
		$host = 'Template linkage test host';

		$sql = 'select hostid from hosts where host='.zbx_dbstr($host).' and status in ('.HOST_STATUS_MONITORED.','.HOST_STATUS_NOT_MONITORED.')';
		$this->assertEquals(1, CDBHelper::getCount($sql));
		$row = DBfetch(DBselect($sql));
		$hostid = $row['hostid'];

		$sql2 = "select hostid from hosts where host='".$template."';";
		$this->assertEquals(1, CDBHelper::getCount($sql2));
		$row2 = DBfetch(DBselect($sql2));
		$hostid2 = $row2['hostid'];

		$this->zbxTestLogin('hosts.php');
		$this->zbxTestDropdownSelectWait('groupid', 'all');
		$this->zbxTestClickLinkTextWait($this->host_for_template);
		$this->zbxTestTabSwitch('Templates');
		$this->zbxTestTextPresent($template);

		// clicks button named "Unlink and clear" next to a template by name
		$this->zbxTestClickXpathWait("//button[contains(@onclick, 'unlink_and_clear[".$hostid2."]') and text()='Unlink and clear']");

		$this->zbxTestTextNotPresent($template);
		$this->zbxTestClickWait('update');
		$this->zbxTestCheckTitle('Configuration of hosts');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Host updated');

		$this->zbxTestDoubleClickXpath("//a[contains(@href,'items.php?filter_set=1&filter_hostids%5B0%5D=".$hostid."')]", 'filter_application');
		$this->zbxTestTextNotPresent($template.':');

		$this->zbxTestHrefClickWait('triggers.php?filter_set=1&filter_hostids%5B0%5D='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
		$this->zbxTestHrefClickWait('graphs.php?hostid='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
		$this->zbxTestHrefClickWait('applications.php?hostid='.$hostid);
		$this->zbxTestTextNotPresent($template.':');
	}
}
