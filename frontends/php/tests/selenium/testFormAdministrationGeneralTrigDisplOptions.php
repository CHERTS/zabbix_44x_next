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

class testFormAdministrationGeneralTrigDisplOptions extends CLegacyWebTest {

	public static function allValues() {
		return CDBHelper::getDataProvider(
			'SELECT custom_color,problem_unack_color,problem_unack_style,problem_ack_color,problem_ack_style,'.
				'ok_unack_color,ok_unack_style,ok_ack_color,ok_ack_style,ok_period,blink_period'.
			' FROM config'
		);
	}

	/**
	 * @dataProvider allValues
	 */
	public function testFormAdministrationGeneralTrigDisplOptions_Layout($allValues) {
		$this->zbxTestLogin('adm.triggerdisplayoptions.php');
		$this->zbxTestCheckHeader('Trigger displaying options');
		$this->zbxTestTextPresent(
			[
				'Trigger displaying options',
				'Use custom event status colours',
				'blinking',
				'Unacknowledged PROBLEM events',
				'Acknowledged PROBLEM events',
				'Unacknowledged RESOLVED events',
				'Acknowledged RESOLVED events',
				'Display OK triggers for',
				'On status change triggers blink for'
			]
		);

		$this->assertEquals($this->zbxTestCheckboxSelected('custom_color'), (bool) $allValues['custom_color']);

		$this->zbxTestAssertElementValue('problem_unack_color', $allValues['problem_unack_color']);
		$this->zbxTestAssertElementValue('problem_ack_color', $allValues['problem_ack_color']);
		$this->zbxTestAssertElementValue('ok_unack_color', $allValues['ok_unack_color']);
		$this->zbxTestAssertElementValue('ok_ack_color', $allValues['ok_ack_color']);

		$this->assertEquals($this->zbxTestCheckboxSelected('problem_unack_style'), (bool) $allValues['problem_unack_style']);
		$this->assertEquals($this->zbxTestCheckboxSelected('problem_ack_style'), (bool) $allValues['problem_ack_style']);
		$this->assertEquals($this->zbxTestCheckboxSelected('ok_unack_style'), (bool) $allValues['ok_unack_style']);
		$this->assertEquals($this->zbxTestCheckboxSelected('ok_ack_style'), (bool) $allValues['ok_ack_style']);

		$this->zbxTestAssertElementValue('ok_period', $allValues['ok_period']);
		$this->zbxTestAssertElementValue('blink_period', $allValues['blink_period']);

		$this->zbxTestAssertElementPresentXpath("//input[@id='problem_unack_color'][@disabled]");
		$this->zbxTestAssertElementPresentXpath("//input[@id='problem_ack_color'][@disabled]");
		$this->zbxTestAssertElementPresentXpath("//input[@id='ok_unack_color'][@disabled]");
		$this->zbxTestAssertElementPresentXpath("//input[@id='ok_ack_color'][@disabled]");
	}

	public function testFormAdministrationGeneralTrigDisplOptions_UpdateTrigDisplOptions() {
		$this->zbxTestLogin('adm.triggerdisplayoptions.php');
		$this->zbxTestCheckTitle('Configuration of trigger displaying options');
		$this->zbxTestCheckHeader('Trigger displaying options');
		$this->zbxTestDropdownSelectWait('configDropDown', 'Trigger displaying options');
		$this->zbxTestTextPresent(['Trigger displaying options', 'blinking', 'Unacknowledged PROBLEM events', 'Acknowledged PROBLEM events', 'Unacknowledged RESOLVED events', 'Acknowledged RESOLVED events', 'Display OK triggers for', 'On status change triggers blink for']);

		// hash calculation for not-changed DB fields
		$sql_hash = 'SELECT '.CDBHelper::getTableFields('config', ['custom_color', 'problem_unack_color', 'problem_ack_color', 'ok_unack_color', 'ok_ack_color', 'problem_unack_style', 'problem_ack_style', 'ok_unack_style', 'ok_ack_style', 'ok_period', 'blink_period']).' FROM config ORDER BY configid';
		$old_hash = CDBHelper::getHash($sql_hash);

		$this->zbxTestCheckboxSelect('custom_color');
		$this->zbxTestInputType('problem_unack_color', 'AAAAAA');
		$this->zbxTestInputType('problem_ack_color', 'BBBBBB');
		$this->zbxTestInputType('ok_unack_color', 'CCCCCC');
		$this->zbxTestInputType('ok_ack_color', 'DDDDDD');
		$this->zbxTestCheckboxSelect('problem_unack_style', false);
		$this->zbxTestCheckboxSelect('problem_ack_style', false);
		$this->zbxTestCheckboxSelect('ok_unack_style', false);
		$this->zbxTestCheckboxSelect('ok_ack_style', false);
		$this->zbxTestInputType('ok_period', '1h');
		$this->zbxTestInputType('blink_period', '5m');

		$this->zbxTestClickWait('update');
		$this->zbxTestTextPresent(['Configuration updated', 'Trigger displaying options']);

		// checking values in the DB
		$this->assertEquals(1, CDBHelper::getCount('SELECT custom_color FROM config WHERE custom_color=1'));
		$this->assertEquals(1, CDBHelper::getCount("SELECT problem_unack_color FROM config WHERE problem_unack_color='AAAAAA'"));
		$this->assertEquals(1, CDBHelper::getCount("SELECT problem_ack_color FROM config WHERE problem_ack_color='BBBBBB'"));
		$this->assertEquals(1, CDBHelper::getCount("SELECT ok_unack_color FROM config WHERE ok_unack_color='CCCCCC'"));
		$this->assertEquals(1, CDBHelper::getCount("SELECT ok_ack_color FROM config WHERE ok_ack_color='DDDDDD'"));
		$this->assertEquals(1, CDBHelper::getCount('SELECT problem_unack_style FROM config WHERE problem_unack_style=0'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT problem_ack_style FROM config WHERE problem_ack_style=0'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT ok_unack_style FROM config WHERE ok_unack_style=0'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT ok_ack_style FROM config WHERE ok_ack_style=0'));
		$this->assertEquals(1, CDBHelper::getCount("SELECT ok_period FROM config WHERE ok_period='1h'"));
		$this->assertEquals(1, CDBHelper::getCount("SELECT blink_period FROM config WHERE blink_period='5m'"));

		$this->assertEquals($old_hash, CDBHelper::getHash($sql_hash));
	}

	public static function ok_period() {
		return [
			[[
				'expected' => TEST_BAD,
				'period' => ' ',
				'error_msg' => 'Invalid displaying of OK triggers: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => 's',
				'error_msg' => 'Invalid displaying of OK triggers: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1.5',
				'error_msg' => 'Invalid displaying of OK triggers: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '{$BAD}',
				'error_msg' => 'Invalid displaying of OK triggers: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1441m',
				'error_msg' => 'Invalid displaying of OK triggers: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '2d',
				'error_msg' => 'Invalid displaying of OK triggers: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '86401',
				'error_msg' => 'Invalid displaying of OK triggers: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1y',
				'error_msg' => 'Invalid displaying of OK triggers: a time unit is expected.'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '0'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '86400s'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1440m'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1h'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1d'
			]]
		];
	}

	/**
	 * @dataProvider ok_period
	 */
	public function testFormAdministrationGeneralTrigDisplOptions_OKPeriod($data) {
		$this->zbxTestLogin('adm.triggerdisplayoptions.php');

		$this->zbxTestInputTypeOverwrite('ok_period', $data['period']);
		$this->zbxTestClickWait('update');

		$this->zbxTestCheckHeader('Trigger displaying options');

		switch ($data['expected']) {
			case TEST_GOOD:
				$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Configuration updated');
				break;

			case TEST_BAD:
				$this->zbxTestWaitUntilMessageTextPresent('msg-bad', 'Cannot update configuration');
				$this->zbxTestTextPresent($data['error_msg']);
				break;
		}
	}

	public static function blink_period() {
		return [
			[[
				'expected' => TEST_BAD,
				'period' => ' ',
				'error_msg' => 'Invalid blinking on trigger status change: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => 's',
				'error_msg' => 'Invalid blinking on trigger status change: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1.5',
				'error_msg' => 'Invalid blinking on trigger status change: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '{$BAD}',
				'error_msg' => 'Invalid blinking on trigger status change: a time unit is expected.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1441m',
				'error_msg' => 'Invalid blinking on trigger status change: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '2d',
				'error_msg' => 'Invalid blinking on trigger status change: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '86401',
				'error_msg' => 'Invalid blinking on trigger status change: value must be one of 0-86400.'
			]],
			[[
				'expected' => TEST_BAD,
				'period' => '1y',
				'error_msg' => 'Invalid blinking on trigger status change: a time unit is expected.'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '0'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '86400s'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1440m'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1h'
			]],
			[[
				'expected' => TEST_GOOD,
				'period' => '1d'
			]]
		];
	}

	/**
	 * @dataProvider blink_period
	 */
	public function testFormAdministrationGeneralTrigDisplOptions_BlinkPeriod($data) {
		$this->zbxTestLogin('adm.triggerdisplayoptions.php');

		$this->zbxTestInputTypeOverwrite('blink_period', $data['period']);
		$this->zbxTestClickWait('update');

		$this->zbxTestCheckHeader('Trigger displaying options');

		switch ($data['expected']) {
			case TEST_GOOD:
				$this->zbxTestWaitUntilMessageTextPresent('msg-good', 'Configuration updated');
				break;

			case TEST_BAD:
				$this->zbxTestWaitUntilMessageTextPresent('msg-bad', 'Cannot update configuration');
				$this->zbxTestTextPresent($data['error_msg']);
				break;
		}
	}

	public function testFormAdministrationGeneralTrigDisplOptions_ResetTrigDisplOptions() {
		$this->zbxTestLogin('adm.triggerdisplayoptions.php');
		$this->zbxTestCheckTitle('Configuration of trigger displaying options');
		$this->zbxTestCheckHeader('Trigger displaying options');
		$this->zbxTestDropdownSelectWait('configDropDown', 'Trigger displaying options');

		// hash calculation for the DB fields that should be changed in this report
		$sql_hash = 'SELECT '.CDBHelper::getTableFields('config', ['custom_color', 'problem_unack_style', 'problem_ack_style', 'ok_unack_style', 'ok_ack_style', 'ok_period', 'blink_period']).' FROM config ORDER BY configid';
		$old_hash = CDBHelper::getHash($sql_hash);

		$this->zbxTestClick('resetDefaults');
		$this->zbxTestClickXpath("//div[@id='overlay_dialogue']//button[text()='Reset defaults']");
		$this->zbxTestClickWait('update');
		$this->zbxTestTextPresent(['Configuration updated', 'Trigger displaying options']);

		// checking values in the DB
		$this->assertEquals(1, CDBHelper::getCount('SELECT custom_color FROM config WHERE custom_color=0'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT problem_unack_style FROM config WHERE problem_unack_style=1'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT problem_ack_style FROM config WHERE problem_ack_style=1'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT ok_unack_style FROM config WHERE ok_unack_style=1'));
		$this->assertEquals(1, CDBHelper::getCount('SELECT ok_ack_style FROM config WHERE ok_ack_style=1'));
		$this->assertEquals(1, CDBHelper::getCount("SELECT ok_period FROM config WHERE ok_period='5m'"));
		$this->assertEquals(1, CDBHelper::getCount("SELECT blink_period FROM config WHERE blink_period='2m'"));

		// hash calculation for the DB fields that should be changed in this report
		$this->assertEquals($old_hash, CDBHelper::getHash($sql_hash));
	}
}
