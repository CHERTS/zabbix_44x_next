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

class testPageScreens extends CLegacyWebTest {

	public static function allScreens() {
		return CDBHelper::getDataProvider('SELECT screenid,name FROM screens WHERE templateid IS NULL ORDER BY screenid');
	}

	public function testPageScreens_CheckLayout() {
		$screens = CDBHelper::getAll('SELECT name FROM screens WHERE templateid IS NULL');

		$this->zbxTestLogin('screenconf.php');
		$this->zbxTestCheckTitle('Configuration of screens');

		$this->zbxTestCheckHeader('Screens');
		$this->zbxTestTextPresent('Filter');
		$this->zbxTestTextPresent(sprintf('Displaying %1$s of %1$s found', count($screens)));
		$this->zbxTestDropdownAssertSelected('config', 'Screens');

		$this->zbxTestTextPresent(['Name', 'Dimension (cols x rows)', 'Actions']);

		foreach ($screens as $screen) {
			$this->zbxTestTextPresent($screen['name']);
		}
		$this->zbxTestTextPresent(['Export', 'Delete']);
	}

	/**
	 * @dataProvider allScreens
	 * @ignore-browser-errors
	 */
	public function testPageScreens_SimpleEdit($screen) {
		$this->zbxTestLogin('screenconf.php');
		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestClickLinkText($screen['name']);
		$this->zbxTestCheckTitle('Custom screens [refreshed every 30 sec.]');
		$this->zbxTestTextPresent($screen['name']);
		$this->zbxTestTextPresent('Edit screen');
		$this->zbxTestCheckHeader('Screens');
	}

	/**
	 * @dataProvider allScreens
	 * @ignore-browser-errors
	 */
	public function testPageScreens_SimpleUpdate($screen) {
		$sqlScreen = 'SELECT * FROM screens WHERE screenid='.$screen['screenid'];
		$oldHashScreen = CDBHelper::getHash($sqlScreen);
		$sqlScreenItems = 'SELECT * FROM screens_items WHERE screenid='.$screen['screenid'].' ORDER BY screenitemid';
		$oldHashScreenItems = CDBHelper::getHash($sqlScreenItems);

		$this->zbxTestLogin('screenconf.php');
		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestHrefClickWait('?form=update&screenid='.$screen['screenid']);

		$this->zbxTestCheckHeader('Screens');
		$this->zbxTestTextPresent(['Screen','Sharing']);
		$this->zbxTestTextPresent(['Owner', 'Name', 'Columns', 'Rows']);

		$this->zbxTestClickWait('update');

		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestTextPresent('Screen updated');

		$this->assertEquals($oldHashScreen, CDBHelper::getHash($sqlScreen));
		$this->assertEquals($oldHashScreenItems, CDBHelper::getHash($sqlScreenItems));
	}

	public function testPageScreens_Create() {
		$this->zbxTestLogin('screenconf.php');
		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestClickButtonText('Create screen');

		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestTextPresent(['Owner', 'Name', 'Columns', 'Rows']);

		$this->zbxTestClickWait('cancel');

		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestTextNotPresent(['Owner', 'Columns', 'Rows']);
	}

	/**
	 * @dataProvider allScreens
	 * @backup-once screens
	 */
	public function testPageScreens_MassDelete($screen) {
		$this->zbxTestLogin('screenconf.php');
		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestCheckboxSelect('screens_'.$screen['screenid']);
		$this->zbxTestClickButton('screen.massdelete');
		$this->zbxTestAcceptAlert();

		$this->zbxTestCheckTitle('Configuration of screens');
		$this->zbxTestTextPresent('Screen deleted');
		$this->zbxTestCheckHeader('Screens');

		$sql = 'SELECT NULL FROM screens WHERE screenid='.$screen['screenid'];
		$this->assertEquals(0, CDBHelper::getCount($sql));
		$sql = 'SELECT NULL FROM screens_items WHERE screenid='.$screen['screenid'];
		$this->assertEquals(0, CDBHelper::getCount($sql));
		$sql = 'SELECT NULL FROM slides WHERE screenid='.$screen['screenid'];
		$this->assertEquals(0, CDBHelper::getCount($sql));
	}
}
