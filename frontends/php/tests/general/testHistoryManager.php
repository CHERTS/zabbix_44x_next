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


require_once dirname(__FILE__).'/../include/CTest.php';
require_once dirname(__FILE__).'/../../include/classes/api/managers/CHistoryManager.php';
require_once dirname(__FILE__).'/../../include/classes/core/CRegistryFactory.php';
require_once dirname(__FILE__).'/../../include/classes/core/Manager.php';
require_once dirname(__FILE__).'/../../include/classes/helpers/CArrayHelper.php';

/**
 * @backup history_uint
 */
class testHistoryManager extends CTest {

	public static function history_uint() {
		return [
			[
				'items' => [
					['itemid' => 23252, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [],
				'limit' => 1,
				'expected_result' => []
			],
			[
				'items' => [
					['itemid' => 23253, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [
					['itemid' => '23253', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
					['itemid' => '23253', 'clock' => '1545028053', 'value' => '4', 'ns' => '618502669'],
					['itemid' => '23253', 'clock' => '1545024453', 'value' => '4', 'ns' => '840489229']
				],
				'limit' => 1,
				'expected_result' => [
					23253 => [
						['itemid' => '23253', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726']
					]
				]
			],
			[
				'items' => [
					['itemid' => 23254, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [
					['itemid' => '23254', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
					['itemid' => '23254', 'clock' => '1545028053', 'value' => '4', 'ns' => '618502669'],
					['itemid' => '23254', 'clock' => '1545024453', 'value' => '4', 'ns' => '840489229']
				],
				'limit' => 2,
				'expected_result' => [
					23254 => [
						['itemid' => '23254', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
						['itemid' => '23254', 'clock' => '1545028053', 'value' => '4', 'ns' => '618502669']
					]
				]
			],
			[
				'items' => [
					['itemid' => 23255, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [
					['itemid' => '23255', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
					['itemid' => '23255', 'clock' => '1545028053', 'value' => '4', 'ns' => '618502669']
				],
				'limit' => 2,
				'expected_result' => [
					23255 => [
						['itemid' => '23255', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
						['itemid' => '23255', 'clock' => '1545028053', 'value' => '4', 'ns' => '618502669']
					]
				]
			],
			[
				'items' => [
					['itemid' => 23256, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [
					['itemid' => '23256', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726'],
					['itemid' => '23256', 'clock' => '1545031653', 'value' => '4', 'ns' => '618502669'],
					['itemid' => '23256', 'clock' => '1545024453', 'value' => '4', 'ns' => '840489229']
				],
				'limit' => 2,
				'expected_result' => [
					23256 => [
						['itemid' => '23256', 'clock' => '1545031653', 'value' => '4', 'ns' => '618502669'],
						['itemid' => '23256', 'clock' => '1545031653', 'value' => '4', 'ns' => '485858726']
					]
				]
			],
			[
				'items' => [
					['itemid' => 23257, 'value_type' => ITEM_VALUE_TYPE_UINT64]
				],
				'history' => [
					['itemid' => '23257', 'clock' => '1545031652', 'value' => '4', 'ns' => '485858726'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '618502669'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '840489229'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '699818807'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '733780738'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '899856970'],
					['itemid' => '23257', 'clock' => '1545031652', 'value' => '4', 'ns' => '612539560'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '802202845'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '814625539'],
					['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '874374361']
				],
				'limit' => 9,
				'expected_result' => [
					23257 => [
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '899856970'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '874374361'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '840489229'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '814625539'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '802202845'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '733780738'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '699818807'],
						['itemid' => '23257', 'clock' => '1545031653', 'value' => '4', 'ns' => '618502669'],
						['itemid' => '23257', 'clock' => '1545031652', 'value' => '4', 'ns' => '612539560']
					]
				]
			]
		];
	}

	/**
	 * @dataProvider history_uint
	 */
	public function test($items, $history, $limit, $expected_result) {
		DB::insertBatch('history_uint', $history, false);

		$values = Manager::History()->getLastValues($items, $limit);

		$this->assertSame($expected_result, $values);
	}
}
