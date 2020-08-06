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


class CHtmlUrlValidatorTest extends PHPUnit_Framework_TestCase {

	// Expected results are defined assuming that VALIDATE_URI_SCHEMES is enabled (set to be true).
	public function providerValidateURL() {
		return [
			// Valid URLs.
			['http',													[],															true],
			['http://zabbix.com',										[],															true],
			['https://zabbix.com',										[],															true],
			['http://localhost',										[],															true],
			['http://192.168.1.1',										[],															true],
			['http://localhost/file.php',								[],															true],
			['http://localhost/file.html',								[],															true],
			['http://localhost/file',									[],															true],
			['http://hosts.php',										[],															true],
			['http://hello/world/hosts.html?abc=123',					[],															true],
			['http:/hosts.php',											[],															true], // Because we allow tel:1-111-111-1111 and "/hosts.php" is a valid path which falls in same category.
			['http:localost',											[],															true], // Because we allow tel:1-111-111-1111 and "localost" is a valid path which falls in same category.
			['http:/localost',											[],															true], // Because we allow tel:1-111-111-1111 and "/localost" is a valid path which falls in same category.
			['http/',													[],															true], // Because "http/" is a valid relative path.
			['http:/localhost/hosts.php',								[],															true], // Because we allow tel:1-111-111-1111 and "/localhost/hosts.php" is a valid path which falls in same category.
			['http:myhost/hosts.php',									[],															true], // Because we allow tel:1-111-111-1111 and "myhost/hosts.php" is a valid path which falls in same category.
			['localhost',												[],															true],
			['notzabbix.php',											[],															true],
			['hosts.php',												[],															true],
			['hosts.html',												[],															true],
			['/secret/.htaccess',										[],															true], // No file type restrictions.
			['/hosts.php',												[],															true],
			['subdir/hosts.php',										[],															true],
			['subdir/hosts/id/10084',									[],															true],
			['subdir/'.'/100500/',										[],															true], // Comment hook does not allow "//".
			['hosts.php/..',											[],															true],
			['hosts/..php',												[],															true],
			['subdir1/../subdir2/../subdir3/',							[],															true],
			['subdir1/subdir2/hosts.php',								[],															true],
			['192.168.1.1.',											[],															true], // Not a valid IP, but it is accepted as "path".
			['zabbix.php?a=1',											[],															true],
			['adm.images.php?a=1',										[],															true],
			['chart_bar.php?a=1&b=2',									[],															true],
			['mailto:example@example.com',								[],															true],
			['file://localhost/path',									[],															true],
			['tel:1-111-111-1111',										[],															true],
			['ssh://username@hostname:/path ',							[],															true],
			['/chart_bar.php?a=1&b=2',									[],															true],
			['http://localhost:{$PORT}',								[],															true], // Macros allowed.
			['http://{$INVALID!MACRO}',									[],															true], // Macros allowed, but it's not a valid macro.
			['/',														[],															true], // "/" is a valid path to home directory.
			['/../',													[],															true],
			['../',														[],															true],
			['/..',														[],															true],
			['../././not_so_zabbix',									[],															true],
			['jav&#x09;ascript:alert(1];',								[],															true], // "jav" is a valid path with everything else in "fragment".
			['ftp://user@host:21',										[],															true],
			['ftp://somehost',											[],															true],
			['ftp://user@host',											[],															true],
			['{$USER_URL_MACRO}',										[],															true],
			['{$USER_URL_MACRO}?a=1',									[],															true],
			['http://{$USER_URL_MACRO}?a=1',							[],															true],
			['http://{$USER_URL_MACRO}',								[],															true],
			['http://{{{$USER_URL_MACRO}',								[],															true],
			['http://{$MACRO{$MACRO}}',									[],															true],
			['{$MACRO{',												[],															true],
			// Inventory macros are going to be considered as "path".
			['{INVENTORY.URL.A}',										['allow_inventory_macro' => INVENTORY_URL_MACRO_HOST],		true],
			['{INVENTORY.URL.A1}',										['allow_inventory_macro' => INVENTORY_URL_MACRO_TRIGGER],	true],
			['{INVENTORY.URL.A1}',										['allow_inventory_macro' => INVENTORY_URL_MACRO_HOST],		true],
			['{INVENTORY.URL.A0}',										['allow_inventory_macro' => INVENTORY_URL_MACRO_TRIGGER],	true],
			['{INVENTORY.URL.A}',										['allow_inventory_macro' => INVENTORY_URL_MACRO_NONE],		true],
			// Event tag macros are going to be considered as "path".
			['text{EVENT.TAGS."JIRAID"}text',							[],															true],
			['text{EVENT.TAGS."JIRAID"}text',							['allow_event_tags_macro' => true],							true],
			// Macros not allowed.
			['http://{$USER_URL_MACRO}',								['allow_user_macro' => false],								true], // User macros not allowed, but it's a host.
			['{$USER_URL_MACRO}',										['allow_user_macro' => false],								true],
			['{INVENTORY.URL.A}',										['allow_user_macro' => false],								true],
			['http://localhost/{$USER_URL_MACRO}/',						['allow_user_macro' => false],								true], // User macros not allowed, but it's a subdir.
			['http://localhost/hosts.php?hostid={$ID}',					['allow_user_macro' => false],								true], // User macros not allowed, but it's in query.
			['http://localhost/hosts.php?hostid=1#comment={$COMMENT}',	['allow_user_macro' => false],								true],
			['http://localhost/{NOT_AUSER_MACRO}/',						['allow_user_macro' => false],								true], // User macros not allowed, but it's not a macro.
			['http://localhost?host={HOST.NAME}',						['allow_user_macro' => false],								true],
			// Invalid URLs.
			['http:?abc',												[],															false], // Scheme with no host.
			['http:/',													[],															false], // Special case where single "/" is not allowed in path.
			['http://',													[],															false], // url_parse() returs false.
			['http:///',												[],															false], // url_parse() returs false.
			['http:',													[],															false], // Scheme with no host.
			['http://?',												[],															false], // url_parse() returns false.
			['javascript:alert(]',										[],															false], // Invalid scheme.
			['protocol://{$INVALID!MACRO}',								[],															false], // Invalid scheme. Also macro is not valid, but that's secondary.
			['',														[],															false], // Cannot be empty.
			['ftp://user@host:port',									[],															false], // Scheme is allowed, but "port" is not a valid number and url_parse() returs false.
			['vbscript:msgbox(]',										[],															false], // Invalid scheme.
			['notexist://localhost',									[],															false] // Invalid scheme.
		];
	}

	/**
	 * @dataProvider providerValidateURL
	 */
	public function test_validateURL($url, $options, $expected) {
		$this->assertSame($expected, CHtmlUrlValidator::validate($url, $options));
	}
}
