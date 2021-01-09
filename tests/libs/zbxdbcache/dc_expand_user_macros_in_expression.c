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

#include "zbxmocktest.h"
#include "zbxmockdata.h"
#include "zbxmockassert.h"
#include "zbxmockutil.h"

#include "zbxserver.h"
#include "common.h"
#include "zbxalgo.h"
#include "dbcache.h"
#include "mutexs.h"
#define ZBX_DBCONFIG_IMPL
#include "dbconfig.h"

#include "configcache_mock.h"

/******************************************************************************
 *                                                                            *
 * Function: zbx_mock_test_entry                                              *
 *                                                                            *
 ******************************************************************************/
void	zbx_mock_test_entry(void **state)
{
	char		*returned_expression;
	const char	*expression, *expected_expression;

	ZBX_UNUSED(state);

	mock_config_init();
	mock_config_load_user_macros("in.macros");

	expression = zbx_mock_get_parameter_string("in.expression");
	expected_expression = zbx_mock_get_parameter_string("out.expression");
	returned_expression = dc_expand_user_macros_in_expression(expression, NULL, 0);
	zbx_mock_assert_str_eq("Expanded expression", expected_expression, returned_expression);

	zbx_free(returned_expression);

	mock_config_free();
}
