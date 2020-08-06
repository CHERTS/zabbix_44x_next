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


class CHostNameParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * An array of time periods and parsed results.
	 */
	public static function testProvider() {
		return [
			// success
			[
				'Host Name One', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'Host Name One',
					'macros' => []
				]
			],
			[
				'a{#B}c  {#D}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'a{#B}c  {#D}',
					'macros' => ['{#B}', '{#D}']
				]
			],
			[
				'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}',
					'macros' => ['{#B}', '{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}']
				]
			],
			// partial success
			[
				'Host Name Two ', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'Host Name Two',
					'macros' => []
				]
			],
			[
				'a/', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'a',
					'macros' => []
				]
			],
			[
				'abc/edf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'abc',
					'macros' => []
				]
			],
			[
				'abc/', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'abc',
					'macros' => []
				]
			],
			[
				'abc   ', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'abc',
					'macros' => []
				]
			],
			[
				'abc{#DEF}ghi', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'abc',
					'macros' => []
				]
			],
			[
				'   a{#B}c  {#D}   ', 8, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'c',
					'macros' => []
				]
			],
			[
				'   a{#B}c  {#D}   ', 8, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'c  {#D}',
					'macros' => ['{#D}']
				]
			],
			[
				'abc{#DEF}ghi    ', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'abc{#DEF}ghi',
					'macros' => ['{#DEF}']
				]
			],
			[
				'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}{#D}/e', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}{#D}',
					'macros' => ['{#B}', '{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}', '{#D}']
				]
			],
			[
				'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}{#D}     ', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'a{#B}{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}{#D}',
					'macros' => ['{#B}', '{{#C}.regsub("^([0-9]+\/[A-Za-z])", "{#C}: \1")}', '{#D}']
				]
			],
			// fail
			[
				'', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'   ', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				' Host Name Three', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'/', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'/abc', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'{#DEF', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'host$@#', 4, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			],
			[
				'{#ABC}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'macros' => []
				]
			]
		];
	}

	/**
	 * @dataProvider testProvider
	 *
	 * @param string $source
	 * @param int    $pos
	 * @param array  $options
	 * @param array  $expected
	 */
	public function testParse($source, $pos, $options, $expected) {
		$parser = new CHostNameParser($options);

		$this->assertSame($expected, [
			'rc' => $parser->parse($source, $pos),
			'match' => $parser->getMatch(),
			'macros' => $parser->getMacros(),
		]);
		$this->assertSame(strlen($expected['match']), $parser->getLength());
	}
}
