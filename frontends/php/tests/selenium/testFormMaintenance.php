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

use Facebook\WebDriver\WebDriverBy;

/**
 * Tests for "Configuration -> Maintenance".
 *
 * Forms:
 * - Create maintenance.
 * - Clone maintenance.
 * - Delete maintenance.
 *
 * @backup maintenances
 */
class testFormMaintenance extends CLegacyWebTest {
	public $name = 'Test maintenance';

	/**
	 * Create maintenance with periods and host group.
	 */
	public function testFormMaintenance_Create() {
		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestCheckTitle('Configuration of maintenance periods');
		$this->zbxTestCheckHeader('Maintenance periods');
		$this->zbxTestContentControlButtonClickTextWait('Create maintenance period');

		// Type maintenance name.
		$this->zbxTestInputTypeWait('mname', $this->name);

		// "Periods" tab.
		$this->zbxTestTabSwitchById('tab_periodsTab', 'Periods');

		// Add "One time only" maintenance period.
		$this->zbxTestClickButtonText('New');
		$this->zbxTestClickXpathWait('//button[@class=\'btn-link\' and text()=\'Add\']');
		$this->zbxTestAssertElementText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr/td','One time only');

		// Add "Daily" maintenance period.
		$this->zbxTestClickButtonText('New');
		$this->zbxTestDropdownSelectWait('new_timeperiod_timeperiod_type', 'Daily');
		$this->zbxTestClickXpathWait('//button[@class=\'btn-link\' and text()=\'Add\']');
		$this->zbxTestAssertElementText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[2]/td','Daily');

		// Add "Weekly" maintenance period with "Monday" and "Sunday".
		$this->zbxTestClickButtonText('New');
		$this->zbxTestDropdownSelectWait('new_timeperiod_timeperiod_type', 'Weekly');
		$this->zbxTestCheckboxSelect('new_timeperiod_dayofweek_mo');
		$this->zbxTestCheckboxSelect('new_timeperiod_dayofweek_su');
		$this->zbxTestClickXpathWait('//button[@class=\'btn-link\' and text()=\'Add\']');
		// Check weekly period in frontend.
		$this->zbxTestAssertElementText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[3]/td','Weekly');
		$text = $this->zbxTestGetText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[3]/td[2]');
		$this->assertRegexp('/Monday/', $text);
		$this->assertRegexp('/Sunday/', $text);

		// Add "Monthly" maintenance period with "January" and "November".
		$this->zbxTestClickButtonText('New');
		$this->zbxTestDropdownSelectWait('new_timeperiod_timeperiod_type', 'Monthly');
		$this->zbxTestCheckboxSelect('new_timeperiod_month_jan');
		$this->zbxTestCheckboxSelect('new_timeperiod_month_nov');
		$this->zbxTestClickXpathWait('//button[@class=\'btn-link\' and text()=\'Add\']');
		// Check monthly period in frontend.
		$this->zbxTestAssertElementText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[4]/td','Monthly');
		$text = $this->zbxTestGetText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[4]/td[2]');
		$this->assertRegexp('/January/', $text);
		$this->assertRegexp('/November/', $text);

		// Open "Hosts and groups" tab and add group.
		$this->zbxTestTabSwitchById('tab_hostTab', 'Hosts and groups');
		$this->zbxTestClickButtonMultiselect('groupids_');
		$this->zbxTestLaunchOverlayDialog('Host groups');
		$this->zbxTestClickLinkTextWait('Zabbix servers');

		// Add problem tags.
		$this->zbxTestClickXpath('//label[text()="Or"]');
		$tags = ['Tag1', 'Tag2', 'Tag3'];
		$value = 'Value';
		foreach ($tags as $i => $tag) {
			$this->zbxTestInputTypeWait('tags_'.$i.'_tag', $tag);
			$this->zbxTestClickXpath('//label[@for="tags_'.$i.'_operator_1"]');
			$this->zbxTestInputType('tags_'.$i.'_value', $value );
			$this->zbxTestClick('tags_add');
		}

		// Create maintenance and check the results in frontend.
		$this->zbxTestClickXpath('//button[@id=\'add\'][@type=\'submit\']');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Maintenance added');
		$this->zbxTestTextPresent($this->name);

		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenances WHERE name='.zbx_dbstr($this->name)));
		$this->assertEquals(3, CDBHelper::getCount('SELECT NULL FROM maintenance_tag WHERE value='.zbx_dbstr($value)));
	}

	/**
	 * Changes not preserve when close edit form using cancel button.
	 *
	 * @depends testFormMaintenance_Create
	 */
	public function testFromMaintenance_Cancel() {
		$sql_hash = 'SELECT * FROM maintenances ORDER BY maintenanceid';
		$old_hash = CDBHelper::getHash($sql_hash);

		// Open form and change maintenance name.
		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestClickLinkTextWait($this->name);
		$this->zbxTestInputTypeOverwrite('mname', 'Some random text');

		// Open "Periods" tab and remove 4th defined period.
		$this->zbxTestTabSwitchById('tab_periodsTab', 'Periods');
		$this->zbxTestClickXpathWait('//td[contains(text(), \'Monthly\')]/..//button[text()=\'Remove\']');
		$this->zbxTestWaitForPageToLoad();

		// Close the form.
		$this->zbxTestClickWait('cancel');
		$this->zbxTestWaitForPageToLoad();

		// Check the result in DB.
		$this->assertEquals($old_hash, CDBHelper::getHash($sql_hash));

		// Open form to check changes was not saved.
		$this->zbxTestClickLinkTextWait($this->name);

		// "Maintenance" tab, check name.
		$this->zbxTestAssertElementValue('mname', $this->name);

		// "Periods" tab, check that 4th period exist.
		$this->zbxTestTabSwitchById('tab_periodsTab', 'Periods');
		$this->zbxTestAssertElementPresentXpath('//td[contains(text(), \'Monthly\')]/..//button[text()=\'Edit\']');
	}

	/**
	 * Test update by changing maintenance period and type.
	 *
	 * @depends testFormMaintenance_Create
	 */
	public function testFormMaintenance_Update() {
		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestCheckTitle('Configuration of maintenance periods');
		$this->zbxTestCheckHeader('Maintenance periods');
		$this->zbxTestClickLinkTextWait($this->name);

		// Change maintenance type.
		$this->zbxTestClickXpathWait('//label[contains(text(), \'No data collection\')]');

		// Open "Periods" tab.
		$this->zbxTestTabSwitchById('tab_periodsTab', 'Periods');
		// Remove "One time only".
		$this->zbxTestClickXpath('//td[contains(text(), \'One time only\')]/..//button[text()=\'Remove\']');
		$this->zbxTestWaitForPageToLoad();
		// Edit "Weekly".
		$this->zbxTestClickXpathWait('//td[contains(text(), \'Weekly\')]/..//button[text()=\'Edit\']');
		$this->zbxTestCheckboxSelect('new_timeperiod_dayofweek_we');
		$this->zbxTestCheckboxSelect('new_timeperiod_dayofweek_fr');
		$this->zbxTestClickXpath('//button[contains(@onclick, \'add_timeperiod\')]');
		$this->zbxTestWaitForPageToLoad();
		$text = $this->zbxTestGetText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[2]/td[2]');
		$this->assertRegexp('/Monday/', $text);
		$this->assertRegexp('/Wednesday/', $text);
		$this->assertRegexp('/Friday/', $text);
		$this->assertRegexp('/Sunday/', $text);
		// Edit "Monthly".
		$this->zbxTestClickXpath('//td[contains(text(), \'Monthly\')]/..//button[text()=\'Edit\']');
		$this->zbxTestCheckboxSelect('new_timeperiod_month_sep');
		$this->zbxTestCheckboxSelect('new_timeperiod_month_jun');
		$this->zbxTestClickXpath('//label[contains(text(), \'Day of week\')]');
		$this->zbxTestCheckboxSelect('new_timeperiod_dayofweek_we');
		$this->zbxTestClickXpath('//button[contains(@onclick, \'add_timeperiod\')]');
		$this->zbxTestWaitForPageToLoad();
		$text = $this->zbxTestGetText('//ul[@id=\'maintenancePeriodFormList\']//tbody/tr[3]/td[2]');
		$this->assertRegexp('/Wednesday/', $text);
		$this->assertRegexp('/January/', $text);
		$this->assertRegexp('/June/', $text);
		$this->assertRegexp('/September/', $text);
		$this->assertRegexp('/November/', $text);

		$this->zbxTestClick('update');

		// Check the results in frontend.
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Maintenance updated');
		$this->zbxTestAssertElementText('//a[text()=\''.$this->name.'\']/../../td[3]', 'No data collection');
		$this->zbxTestTextPresent($this->name);

		// Check the results in DB.
		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenances WHERE name='.zbx_dbstr($this->name)));
	}

	public function testFormMaintenance_UpdateTags() {
		$maintenance = 'Maintenance for update (data collection)';
		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestCheckTitle('Configuration of maintenance periods');
		$this->zbxTestCheckHeader('Maintenance periods');
		$this->zbxTestClickLinkTextWait($maintenance);

		// Update tags.
		$this->zbxTestTabSwitch('Hosts and groups');
		$this->zbxTestClickXpathWait('//label[text()="And/Or"]');
		$tag = 'Tag';
		$values = ['A1','B1'];
		foreach ($values as $i => $value) {
			$this->zbxTestInputTypeOverwrite('tags_'.$i.'_tag', $tag);
			$this->zbxTestInputTypeOverwrite('tags_'.$i.'_value', $value);
		}
		$this->zbxTestClickXpath('//label[@for="tags_0_operator_1"]');
		$this->zbxTestClickXpath('//label[@for="tags_1_operator_0"]');

		$this->zbxTestClick('update');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Maintenance updated');

		$this->assertEquals(2, CDBHelper::getCount('SELECT NULL FROM maintenance_tag WHERE tag='.zbx_dbstr($tag)));
		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenance_tag WHERE value=\'A1\' AND operator=0'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenance_tag WHERE value=\'B1\' AND operator=2'));
	}

	/**
	 * Test cloning of maintenance.
	 *
	 * @depends testFormMaintenance_Create
	 */
	public function testFormMaintenance_Clone() {
		$suffix = ' (clone)';

		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestCheckTitle('Configuration of maintenance periods');
		$this->zbxTestCheckHeader('Maintenance periods');

		// Open existing maintenance form.
		$this->zbxTestClickLinkText($this->name);

		// Clone maintenance, rename the clone and save it.
		$this->zbxTestClickWait('clone');
		$this->zbxTestInputTypeOverwrite('mname', $this->name.$suffix);
		$this->zbxTestClickXpath('//button[@id=\'add\'][@type=\'submit\']');

		// Check the result in frontend.
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Maintenance added');
		$this->zbxTestTextPresent([$this->name.$suffix, $this->name]);

		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenances WHERE name='.zbx_dbstr($this->name)));
		$this->assertEquals(1, CDBHelper::getCount('SELECT NULL FROM maintenances WHERE name='.zbx_dbstr($this->name.$suffix)));
	}

	/**
	 * Test deleting of maintenance.
	 *
	 * @depends testFormMaintenance_Create
	 */
	public function testFormMaintenance_Delete() {
		$this->zbxTestLogin('maintenance.php?ddreset=1');
		$this->zbxTestCheckTitle('Configuration of maintenance periods');
		$this->zbxTestCheckHeader('Maintenance periods');

		$this->zbxTestClickLinkText($this->name);

		// Delete a maintenance and check the result in frontend.
		$this->zbxTestClickAndAcceptAlert('delete');
		$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Maintenance deleted');

		$this->assertEquals(0, CDBHelper::getCount('SELECT NULL FROM maintenances WHERE name='.zbx_dbstr($this->name)));
	}
}
