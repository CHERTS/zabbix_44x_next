﻿<?php
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


class CPrometheusPatternParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * An array of Prometheus patterns and parsed results.
	 */
	public static function testProvider() {
		return [
			// success
			[
				'metric', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric'
				]
			],
			[
				'{}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{}'
				]
			],
			[
				'metric{}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{}'
				]
			],
			[
				"{ \t}", 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => "{ \t}"
				]
			],
			[
				"metric\t  { \t}", 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => "metric\t  { \t}"
				]
			],
			[
				'metric==6', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==6'
				]
			],
			[
				'metric==-100', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==-100'
				]
			],
			[
				'metric==-.0e4', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==-.0e4'
				]
			],
			[
				'metric==1.e1', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==1.e1'
				]
			],
			[
				'metric==+Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==+Inf'
				]
			],
			[
				'metric==-Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==-Inf'
				]
			],
			[
				'metric==inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==inf'
				]
			],
			[
				'metric==NaN', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric==NaN'
				]
			],
			[
				'metric{label1="value1"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1",}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1",}'
				]
			],
			[
				'metric{label1="value1"}==.0', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==.0'
				]
			],
			[
				'metric{label1="value1",label2=~"value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1",label2=~"value2"}'
				]
			],
			[
				'metric{label1=~"value1",label2="value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1=~"value1",label2="value2"}'
				]
			],
			[
				'metric{label1=~"value1",label2="value2",}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1=~"value1",label2="value2",}'
				]
			],
			[
				'metric{label1="value1"}==+Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==+Inf'
				]
			],
			[
				'metric{label1="value1"}==333', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==333'
				]
			],
			[
				'metric{label1="value1"}==-999', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==-999'
				]
			],
			[
				'metric{label1="value1"}==.0e4', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==.0e4'
				]
			],
			[
				'metric{label1="value1"}==-Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==-Inf'
				]
			],
			[
				'metric{label1="value1"}==Nan', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'metric{label1="value1"}==Nan'
				]
			],
			[
				'{label1="value1"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}'
				]
			],
			[
				'{label1=~"value1"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1=~"value1"}'
				]
			],
			[
				'{__name__=~"value1"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{__name__=~"value1"}'
				]
			],
			[
				'{label1="\"v"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="\"v"}'
				]
			],
			[
				'{label1="value1"}==666', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==666'
				]
			],
			[
				'{label1="value1"}==-777', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==-777'
				]
			],
			[
				'{label1="value1"}==-1E-5', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==-1E-5'
				]
			],
			[
				'{label1="value1"}==-1E+5', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==-1E+5'
				]
			],
			[
				'{label1="value1"}==-1.E5', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==-1.E5'
				]
			],
			[
				'{label1="value1"}==+Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==+Inf'
				]
			],
			[
				'{label1="value1"}==-Inf', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==-Inf'
				]
			],
			[
				'{label1="value1"}==Nan', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1"}==Nan'
				]
			],
			[
				'{label1=""}==.0e4', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1=""}==.0e4'
				]
			],
			[
				'met:ric_0123456789', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => 'met:ric_0123456789'
				]
			],
			[
				'{$M}', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{$M}'
				]
			],
			[
				'{$M}{label1="{$M}"}=={$M}', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{$M}{label1="{$M}"}=={$M}'
				]
			],
			[
				'{$M}{label1="value1"}=={#LLD}', 0, ['usermacros' => true, 'lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{$M}{label1="value1"}=={#LLD}'
				]
			],
			[
				'{$M}  {   label1   =     "value1"   ,   label2  =~  "{$M}"  ,  }   ==   {#LLD}', 0, ['usermacros' => true, 'lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{$M}  {   label1   =     "value1"   ,   label2  =~  "{$M}"  ,  }   ==   {#LLD}'
				]
			],
			[
				'{$M}{{$M}="value1"}=={$M}', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{$M}{{$M}="value1"}=={$M}'
				]
			],
			[
				'{#LLD1}{{#LLD2}="value1"}=={#LLD3}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{#LLD1}{{#LLD2}="value1"}=={#LLD3}'
				]
			],
			// Label value can by anything, no user macro enabling flag is required.
			[
				'{label1="{$M}"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="{$M}"}'
				]
			],
			[
				'{{$M}="{$M"}==1', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{{$M}="{$M"}==1'
				]
			],
			// Double backslash at the end of label value.
			[
				'{label1="value1\\\\"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{label1="value1\\\\"}'
				]
			],
			// partial success
			[
				'metric=1.e1', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric==1e|1', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric==1'
				]
			],
			[
				'metric==', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric===100', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1=', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1="value1"}=', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1"}==', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1"}===', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1"}==Na', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1"}==-In', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1"}==+In', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric{label1="value1"}'
				]
			],
			[
				'metric{label1="value1",,}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1="value1""}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{{label1="value1",label2=~"value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric metric{label1=~"value1", label2="value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1="value1",label2~"value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric   {   label1  =    "value1" ,   label2  =~ "value2"  }   ==    ', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric   {   label1  =    "value1" ,   label2  =~ "value2"  }'
				]
			],
			[
				'{label1="value1"}=', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => '{label1="value1"}'
				]
			],
			[
				'{label1="value1"}==', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => '{label1="value1"}'
				]
			],
			[
				'{label1="value1"}===', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => '{label1="value1"}'
				]
			],
			[
				'random text.....{label1="value1"}....text', 16, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => '{label1="value1"}'
				]
			],
			// metric and __name__ are duplicates
			[
				'metric{__name__=~"value1"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			[
				'metric{label1=~"value1",__name__=~"value2"}', 0, [],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => 'metric'
				]
			],
			// Functional macros are not supported.
			[
				'{#LLD}  {label1="value1"}  ==  {{#LLD}.regsub("^([0-9]+)", "{#LLD}: \1")}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_SUCCESS_CONT,
					'match' => '{#LLD}  {label1="value1"}'
				]
			],
			// fail
			[
				'', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{,}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'0', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'09metric', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'==', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'==100', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{METRIC', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{METRIC}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1~', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=~', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1!=', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1"value1"', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=~"', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=~~', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=""', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1="value1', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1="value1"', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1="value1",', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1=="value1"}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{label1==="value1"}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			// duplicate __name__
			[
				'{__name__=~"value1",__name__=~"value2"}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{  label1  = "value1"  ,  __name__ =  "value2",  label3 =~ ".*", __name__=~ "value4" } ', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			// User macros are not enabled.
			[
				'{$M}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			[
				'{$M}{label1="value1"}=={$M}', 0, [],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			// LLD macros are not enabled.
			[
				'{#LLD}{label1="value1"}=={#LLD}', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			// Functional macros are not supported.
			[
				'{{#LLD}.regsub("^([0-9]+)", "{#LLD}: \1")}  {label1="value1"}  ==  {{#LLD}.regsub("^([0-9]+)", "{#LLD}: \1")}', 0, ['lldmacros' => true],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
				]
			],
			// Invalid label name using a user macro.
			[
				'{__{$M}__="value1"}', 0, ['usermacros' => true],
				[
					'rc' => CParser::PARSE_FAIL,
					'match' => ''
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
		$parser = new CPrometheusPatternParser($options);

		$this->assertSame($expected, [
			'rc' => $parser->parse($source, $pos),
			'match' => $parser->getMatch()
		]);
		$this->assertSame(strlen($expected['match']), $parser->getLength());
	}
}
