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


class CMacrosResolverTest extends PHPUnit_Framework_TestCase {

	private $stub;

	public function setUp() {
		$user_macros = [
			30896 => [
				'hostids' => [10084],
				'macros' => [
					'{$TMG.PROXY.CHECK.URL1}' => 'http://zabbix.com',
					'{$CITY}' => 'Tokyo'
				]
			]
		];

		// Such mocking approach allows to mock protected class methods, but still will not work with private methods.
		/** @var $stub CMacrosResolver */
		$this->stub = $this->getMockBuilder(CMacrosResolver::class)
			->setMethods(['getUserMacros'])
			->getMock();

		$this->stub->method('getUserMacros')
			->willReturn($user_macros);
	}

	public function dataProviderInput() {
		return [
			'expand valid user macro' => [
				'item' => [
					30896 => [
						'hostid' => 10084,
						'description' => 'Response from {$TMG.PROXY.CHECK.URL1} through proxy in {$CITY}'
					]
				],
				'expected_item' => [
					30896 => [
						'hostid' => 10084,
						'description' => 'Response from http://zabbix.com through proxy in Tokyo'
					]
				]
			],
			'leave unknown macros unresolved' => [
				'item' => [
					30896 => [
						'hostid' => 10084,
						'description' => 'Number of packages in {$UNKNOWN_MACRO}'
					]
				],
				'expected_item' => [
					30896 => [
						'hostid' => 10084,
						'description' => 'Number of packages in {$UNKNOWN_MACRO}'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider dataProviderInput
	 */
	public function testResolveItemDescriptions($item, $expected_item) {
		$resolved_item = $this->stub->resolveItemDescriptions($item);

		$this->assertEquals($resolved_item, $expected_item);
	}
}
